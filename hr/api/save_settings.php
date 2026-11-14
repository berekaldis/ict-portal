<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Rest of your code...

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$type = $data['type'] ?? '';
$value = $data['value'] ?? '';
$chatId = $data['chat_id'] ?? '';

if ($action == 'add') {
    $stmt = $pdo->prepare("INSERT INTO hr_settings (type, value) VALUES (?, ?)");
    $stmt->execute([$type, $value]);
    echo json_encode(['success' => true, 'message' => 'Added successfully']);
} elseif ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM hr_settings WHERE type = ? AND value = ?");
    $stmt->execute([$type, $value]);
    echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
} elseif ($action == 'update_chat') {
    $stmt = $pdo->prepare("UPDATE hr_settings SET chat_id = ? WHERE type = ? AND value = ?");
    $stmt->execute([$chatId, $type, $value]);
    echo json_encode(['success' => true, 'message' => 'Chat ID updated']);
} elseif ($action == 'update_logo') {
    $stmt = $pdo->prepare("UPDATE hr_settings SET value = ? WHERE type = 'logo'");
    $stmt->execute([$value]);
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO hr_settings (type, value) VALUES ('logo', ?)");
        $stmt->execute([$value]);
    }
    echo json_encode(['success' => true, 'message' => 'Logo updated']);
}
?>