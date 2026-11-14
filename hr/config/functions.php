<?php
// config/functions.php
function generateId($prefix, $pdo, $table, $idColumn = 'id') {
    $stmt = $pdo->query("SELECT $idColumn FROM $table ORDER BY id DESC LIMIT 1");
    $lastId = $stmt->fetch();
    
    if ($lastId && preg_match('/' . $prefix . '-(\d+)/', $lastId[$idColumn], $matches)) {
        $num = intval($matches[1]) + 1;
    } else {
        $num = 1;
    }
    
    return $prefix . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

function formatDate($date) {
    if (!$date) return '';
    return date('Y-m-d', strtotime($date));
}

function getSettings($pdo) {
    $settings = [
        'branches' => [],
        'jobs' => [],
        'ranks' => [],
        'destinations' => [],
        'logo_url' => '',
        'branch_chat_ids' => [],
        'dest_chat_ids' => []
    ];
    
    $stmt = $pdo->query("SELECT * FROM hr_settings ORDER BY id");
    while ($row = $stmt->fetch()) {
        if ($row['type'] == 'branch') {
            $settings['branches'][] = $row['value'];
            if ($row['chat_id']) $settings['branch_chat_ids'][$row['value']] = $row['chat_id'];
        } elseif ($row['type'] == 'job') {
            $settings['jobs'][] = $row['value'];
        } elseif ($row['type'] == 'rank') {
            $settings['ranks'][] = $row['value'];
        } elseif ($row['type'] == 'destination') {
            $settings['destinations'][] = $row['value'];
            if ($row['chat_id']) $settings['dest_chat_ids'][$row['value']] = $row['chat_id'];
        } elseif ($row['type'] == 'logo') {
            $settings['logo_url'] = $row['value'];
        }
    }
    
    return $settings;
}

function sendTelegramMessage($chatId, $text, $botToken) {
    if (!$botToken || !$chatId) return false;
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result !== false;
}

function createPDF($html, $filename) {
    $tcpdfPath = __DIR__ . '/../lib/tcpdf/TCPDF-main/tcpdf.php';

    if (!file_exists($tcpdfPath)) {
        throw new Exception('TCPDF library not found at: ' . $tcpdfPath);
    }

    require_once $tcpdfPath;

    // Ensure uploads directory exists
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception('Failed to create uploads directory: ' . $uploadsDir);
        }
    }

    try {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('KALDIS HR System');
        $pdf->SetAuthor('KALDIS Coffee');
        $pdf->SetTitle($filename);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // Set Amharic font - use abyssinicasil which is pre-installed in TCPDF
        try {
            $pdf->SetFont('abyssinicasil', '', 12);
        } catch (Exception $e) {
            // Fallback to DejaVuSans if Amharic font fails
            $pdf->SetFont('dejavusans', '', 12);
        }
        
        $pdf->SetFontSubsetting(false);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdfPath = 'uploads/' . $filename . '.pdf';
        $fullPath = __DIR__ . '/../' . $pdfPath;
        $pdf->Output($fullPath, 'F');

        return $pdfPath;
    } catch (Exception $e) {
        throw new Exception('PDF generation failed: ' . $e->getMessage());
    }
}

function getLogoBlob($url) {
    if (!$url) return null;
    $imageData = @file_get_contents($url);
    return $imageData ? $imageData : null;
}

function deleteFile($filepath) {
    if ($filepath && file_exists($filepath)) {
        unlink($filepath);
    }
}

// ====== ROLE-BASED AUTHORIZATION HELPERS ======

function require_login() {
    if (!isset($_SESSION['username'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized - Please login']);
        exit;
    }
}
function has_role($roles) {
    // $roles can be a string or array
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $currentRole = $_SESSION['role'] ?? 'User';
    return in_array($currentRole, $roles, true);
}

function enforce_role($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Access denied - Insufficient permissions']);
        exit;
    }
}
function get_current_role() {
    return $_SESSION['role'] ?? 'User';
}

?>