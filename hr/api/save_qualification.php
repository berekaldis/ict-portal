<?php
// api/save_qualification.php
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['q_name','q_gender','q_branch','q_job','q_probation_start'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        echo json_encode(['error' => 'Missing required field: ' . $f]);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    $requestId = $data['existing_id'] ?? generateId('QF', $pdo, 'hr_qualifications', 'request_id');
    $isNew = !isset($data['existing_id']);
    $pdfPath = '';
    
    if ($isNew) {
        $stmt = $pdo->prepare("INSERT INTO hr_qualifications (request_id, name, gender, branch, job, probation_start, manager_name, manager_rank, destination, pdf_path, internship_ref_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$requestId, $data['q_name'], $data['q_gender'], $data['q_branch'], $data['q_job'], $data['q_probation_start'], $data['q_manager_name'], $data['q_manager_rank'], $data['q_destination'], $pdfPath, $data['ref_intern_id']]);
        
        // Update internship reference status
        if ($data['ref_intern_id']) {
            $stmt = $pdo->prepare("UPDATE hr_internship_requests SET reference_status = 'Done' WHERE request_id = ?");
            $stmt->execute([$data['ref_intern_id']]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE hr_qualifications SET name = ?, gender = ?, branch = ?, job = ?, probation_start = ?, manager_name = ?, manager_rank = ?, destination = ?, pdf_path = ? WHERE request_id = ?");
        $stmt->execute([$data['q_name'], $data['q_gender'], $data['q_branch'], $data['q_job'], $data['q_probation_start'], $data['q_manager_name'], $data['q_manager_rank'], $data['q_destination'], $pdfPath, $requestId]);
    }
    
    // Load settings before PDF generation and for Telegram
    $settings = getSettings($pdo);
    $logoUrl = $settings['logo_url'] ?? '';

    $formData = [
        'q_date' => date('Y-m-d'),
        'q_branch' => $data['q_branch'],
        'q_name' => $data['q_name'],
        'q_gender' => $data['q_gender'],
        'q_job' => $data['q_job'],
        'q_intern_start' => $data['q_intern_start'] ?? '',
        'q_probation_start' => $data['q_probation_start'],
        'q_manager_name' => $data['q_manager_name'],
        'q_manager_rank' => $data['q_manager_rank'],
        'q_destination' => $data['q_destination']
    ];
    
    $html = generateQualificationPDFHTML($formData, $requestId, $logoUrl);
    $pdfPath = createPDF($html, 'qualification_' . $requestId);
    $stmt = $pdo->prepare("UPDATE hr_qualifications SET pdf_path = ? WHERE request_id = ?");
    $stmt->execute([$pdfPath, $requestId]);
    
    // Get settings for Telegram
    $stmt = $pdo->query("SELECT config_value FROM hr_system_config WHERE config_key = 'bot_token'");
    $botToken = $stmt->fetch()['config_value'] ?? '';
    $notificationTitle = $isNew ? '📢 New Qualification' : '🔄 Updated Qualification';
    
    if ($botToken) {
        $targetBranch = $data['q_destination'] ?: $data['q_branch'];
        $targetChatId = $settings['dest_chat_ids'][$targetBranch] ?? $settings['branch_chat_ids'][$targetBranch] ?? null;
        
        if ($targetChatId) {
            $message = "<b>{$notificationTitle}</b>\n<b>🆔 ID:</b> {$requestId}\n<b>👤 Name:</b> {$data['q_name']}\n<b>🏢 Branch:</b> {$data['q_branch']}\n<b>📨 Sent To:</b> {$targetBranch}\n<b>💼 Job:</b> {$data['q_job']}\n<b>📅 Probation Start:</b> {$data['q_probation_start']}";
            sendTelegramMessage($targetChatId, $message, $botToken);
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'id' => $requestId, 'pdf_url' => $pdfPath]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}

function generateQualificationPDFHTML($data, $id, $logoUrl) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: abyssinicasil; margin: 40px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { max-width: 200px; margin-bottom: 20px; }
            .title { font-weight: bold; font-size: 16px; margin: 20px 0; text-align: center; font-family: abyssinicasil; }
            .content { margin-top: 30px; line-height: 1.2; text-align: justify; font-family: abyssinicasil; }
            .signature { margin-top: 30px; }
            p { margin: 0 0 4px 0; line-height: 1.2; text-align: justify; font-family: abyssinicasil; }
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
        <div class="title">የተለማማጅ ሠራተኞች የብቃት መግለጫ ቅጽ</div>
        <div class="content">
            <p>ቀን፡- ' . $data['q_date'] . '</p>
            <p>ከ፡- ' . $data['q_branch'] . ' ቅርንጫፍ</p>
            <p>ለቅርንጫፋችን በ ' . $data['q_job'] . ' የሥራ መደብ ላይ እ.ኤ.አ. ከ ' . $data['q_intern_start'] . ' ጀምሮ ለልምምድ የተላኩለን ' . $data['q_gender'] . ' ' . $data['q_name'] . ' በተሰጣቸው የልምምድ ጊዜ ያሳዩት የሥራ እንቅስቃሴ ጥሩ በመሆኑ፣ ከ ' . $data['q_probation_start'] . ' ቀን ጀምሮ ለ 60 ቀናት የሙከራ ጊዜ ቅጥራቸው እንዲፈጸምልን በአክብሮት እንጠይቃለን።</p>
            <p><em>ማሳሰቢያ፡- ተያዥ በሚያስፈላጋቸው የሥራ መደቦች ላይ የቅጥር ቀን መሙላት አስፈላጊ አይደለም።</em></p>
            <p>በአክብሮት</p>
            <p>ፊርማ፡- ___________________</p>
            <p>የኃላፊ ስም፡- ' . $data['q_manager_name'] . '</p>
            <p>የኃላፊነት ደረጃ፡- ' . $data['q_manager_rank'] . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>