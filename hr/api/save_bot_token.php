<?php
// api/save_bot_token.php
require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

$stmt = $pdo->prepare("INSERT INTO hr_system_config (config_key, config_value) VALUES ('bot_token', ?) ON DUPLICATE KEY UPDATE config_value = ?");
$stmt->execute([$token, $token]);

echo json_encode(['success' => true, 'message' => 'Bot token saved']);
?>