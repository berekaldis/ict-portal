<?php
// api/save_recruitment.php
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['r_name','r_gender','r_branch','r_req_job','r_job','r_start_date'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        echo json_encode(['error' => 'Missing required field: ' . $f]);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    $requestId = $data['existing_id'] ?? generateId('RF', $pdo, 'hr_recruitments', 'request_id');
    $isNew = !isset($data['existing_id']);
    $pdfPath = '';
    
    if ($isNew) {
        $stmt = $pdo->prepare("INSERT INTO hr_recruitments (request_id, name, gender, branch, requested_job, assigned_job, start_date, destination, pdf_path, qualification_ref_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$requestId, $data['r_name'], $data['r_gender'], $data['r_branch'], $data['r_req_job'], $data['r_job'], $data['r_start_date'], $data['r_destination'], $pdfPath, $data['ref_qual_id']]);
        
        // Update qualification reference status
        if ($data['ref_qual_id']) {
            $stmt = $pdo->prepare("UPDATE hr_qualifications SET reference_status = 'Done' WHERE request_id = ?");
            $stmt->execute([$data['ref_qual_id']]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE hr_recruitments SET name = ?, gender = ?, branch = ?, requested_job = ?, assigned_job = ?, start_date = ?, destination = ?, pdf_path = ? WHERE request_id = ?");
        $stmt->execute([$data['r_name'], $data['r_gender'], $data['r_branch'], $data['r_req_job'], $data['r_job'], $data['r_start_date'], $data['r_destination'], $pdfPath, $requestId]);
    }
    
    // Load settings before PDF generation and for Telegram
    $settings = getSettings($pdo);
    $logoUrl = $settings['logo_url'] ?? '';

    $formData = [
        'r_date' => date('Y-m-d'),
        'r_branch' => $data['r_branch'],
        'r_name' => $data['r_name'],
        'r_gender' => $data['r_gender'],
        'r_req_job' => $data['r_req_job'],
        'r_job' => $data['r_job'],
        'r_start_date' => $data['r_start_date'],
        'r_destination' => $data['r_destination']
    ];
    $html = generateRecruitmentPDFHTML($formData, $requestId, $logoUrl);
    $pdfPath = createPDF($html, 'recruitment_' . $requestId);
    $stmt = $pdo->prepare("UPDATE hr_recruitments SET pdf_path = ? WHERE request_id = ?");
    $stmt->execute([$pdfPath, $requestId]);
    
    // Get settings for Telegram
    $stmt = $pdo->query("SELECT config_value FROM hr_system_config WHERE config_key = 'bot_token'");
    $botToken = $stmt->fetch()['config_value'] ?? '';
    
    // Fixed Telegram notification
    $notificationTitle = $isNew ? '📢 New Recruitment' : '🔄 Updated Recruitment';

    if ($botToken) {
        $message = "<b>{$notificationTitle}</b>\n<b>🆔 ID:</b> {$requestId}\n<b>👤 Name:</b> {$data['r_name']}\n<b>🏢 Branch:</b> {$data['r_branch']}\n<b>💼 Assigned Job:</b> {$data['r_job']}\n<b>📅 Start Date:</b> {$data['r_start_date']}";
        
        // Send to branch
        if (isset($settings['branch_chat_ids'][$data['r_branch']])) {
            sendTelegramMessage($settings['branch_chat_ids'][$data['r_branch']], $message, $botToken);
        }
        
        // Send to destination if different
        if ($data['r_destination'] && $data['r_destination'] != $data['r_branch']) {
            $destChatId = $settings['dest_chat_ids'][$data['r_destination']] ?? $settings['branch_chat_ids'][$data['r_destination']] ?? null;
            if ($destChatId) {
                sendTelegramMessage($destChatId, $message . "\n<b>(Copy Sent to Destination: {$data['r_destination']})</b>", $botToken);
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'id' => $requestId, 'pdf_url' => $pdfPath]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}

function generateRecruitmentPDFHTML($data, $id, $logoUrl) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: abyssinicasil; margin: 40px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { max-width: 200px; margin-bottom: 20px; }
            .content { margin-top: 30px; line-height: 1.2; text-align: justify; font-family: abyssinicasil; }
            p { margin: 0 0 4px 0; line-height: 1.2; text-align: left; font-family: abyssinicasil; }
        </style>
    </head>
    <body>';
    
    if ($logoUrl) {
        $html .= '<div class="header"><img src="' . $logoUrl . '" class="logo"></div>';
    }
    
    $html .= '
        <div class="header">
            <strong>ካልዲስ ኮፊ ኃ/የተ/የግ/ማህበር</strong>
        </div>
        <div class="content">
            <p>ቀን፡- ' . $data['r_date'] . '</p>
            <p>ለ፡- ' . $data['r_branch'] . ' ቅርንጫፍ</p>
            <p>ከ፡- የሰው ኃይል አስተዳደር</p>
             <p style="text-align: center;"><strong>ጉዳዩ፡- ቅጥርን ይመለከታል<strong></p>
           
            <p>ለቅርንጫፋችሁ በ ' . $data['r_req_job'] . ' የሥራ መደብ ላይ ሠራተኛ እንዲቀጠርላችሁ በጠየቃችሁት መሠረት፣</p>
            <p>' . $data['r_gender'] . ' ' . $data['r_name'] . ' በ ' . $data['r_job'] . ' የሥራ መደብ ላይ ከ ' . $data['r_start_date'] . ' ጀምሮ ቅጥራቸው የተፈጸመ ስለሆነ፣</p>
            <p>መድባችሁ እንድታሰሯቸው የላከናቸው መሆኑን እንገልጻለን።</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>