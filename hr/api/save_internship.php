<?php
// api/save_internship.php
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['name','gender','to_branch','work_branch','job','start_date'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        echo json_encode(['error' => 'Missing required field: ' . $f]);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    $requestId = $data['existing_id'] ?? generateId('IRF', $pdo, 'hr_internship_requests', 'request_id');
    $isNew = !isset($data['existing_id']);
    $pdfPath = '';
    
    if ($isNew) {
        $stmt = $pdo->prepare("INSERT INTO hr_internship_requests (request_id, name, gender, to_branch, work_branch, job, start_date, pdf_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$requestId, $data['name'], $data['gender'], $data['to_branch'], $data['work_branch'], $data['job'], $data['start_date'], $pdfPath]);
    } else {
        $stmt = $pdo->prepare("UPDATE hr_internship_requests SET name = ?, gender = ?, to_branch = ?, work_branch = ?, job = ?, start_date = ?, pdf_path = ? WHERE request_id = ?");
        $stmt->execute([$data['name'], $data['gender'], $data['to_branch'], $data['work_branch'], $data['job'], $data['start_date'], $pdfPath, $requestId]);
    }
    
    // Load settings before PDF generation and for Telegram
    $settings = getSettings($pdo);
    $logoUrl = $settings['logo_url'] ?? '';

    $formData = [
        'date' => date('Y-m-d'),
        'to_branch' => $data['to_branch'],
        'name' => $data['name'],
        'gender' => $data['gender'],
        'work_branch' => $data['work_branch'],
        'job' => $data['job'],
        'start_date' => $data['start_date']
    ];
    $html = generateInternshipPDFHTML($formData, $requestId, $logoUrl);
    $pdfPath = createPDF($html, 'internship_' . $requestId);
    $stmt = $pdo->prepare("UPDATE hr_internship_requests SET pdf_path = ? WHERE request_id = ?");
    $stmt->execute([$pdfPath, $requestId]);
    
    // Send Telegram notification
    $stmt = $pdo->query("SELECT config_value FROM hr_system_config WHERE config_key = 'bot_token'");
    $botToken = $stmt->fetch()['config_value'] ?? '';
    $notificationTitle = $isNew ? '📢 🆕 Internship Request' : '🔄 Updated Internship Request';
    
    if ($botToken && isset($settings['branch_chat_ids'][$data['to_branch']])) {
        $message = "<b>{$notificationTitle}</b>\n<b>🆔 ID:</b> {$requestId}\n<b>👤Name:</b> {$data['name']}\n<b>🏢 To Branch:</b> {$data['to_branch']}\n<b>🏦 Work Branch:</b> {$data['work_branch']}\n<b>💼Job:</b> {$data['job']}\n<b>📅Start Date:</b> {$data['start_date']}";
        sendTelegramMessage($settings['branch_chat_ids'][$data['to_branch']], $message, $botToken);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'id' => $requestId, 'pdf_url' => $pdfPath]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}

function generateInternshipPDFHTML($data, $id, $logoUrl) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: abyssinicasil; margin: 40px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { max-width: 200px; margin-bottom: 20px; }
            .title { font-weight: bold; margin: 20px 0; }
            .content { margin-top: 30px; line-height: 1; text-align: justify; font-family: abyssinicasil; }
            .signature { margin-top: 50px; }
            p { margin: 0 0 8px 0; line-height: 1; text-align: justify; font-family: abyssinicasil; }
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
            <p>ቀን፦ ' . $data['date'] . '</p>
            <p>ለ፦ ' . $data['to_branch'] . ' ቅርንጫፍ</p>
            <p>ከ፦ ሰው ኃይል አስተዳደር</p>
            
            <p style="text-align: center;"><strong>ጉዳይ፦ የሥራ ላይ ልምምድን ይመለከታል</strong></p>
            
            <p style="text-align: left;">' . $data['gender'] . ' ' . $data['name'] . ' በ ' . $data['work_branch'] . ' ቅርንጫፍ ውስጥ በ ' . $data['job'] . ' የሥራ መደብ ላይ እንዲቀጠሩ</p>
            <p style="text-align: left;">ይታወቃል። ከ ' . $data['start_date'] . ' ጀምሮ በዚሁ የሥራ መደብ የሥራ ላይ ልምምድ እንዲያደርጉ ወደ እርስዎ ልከናል።ብቁ መሆናቸውን ሪፖርት ሲያደርጉልን፣ የቅጥር ውላቸውን እንደምንፈጽም እንገልጻለን።</p>
            
        </div>
    </body>
    </html>';
    
    return $html;
}
?>