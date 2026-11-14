<?php
// api/get_bot_token.php
require_once '../config/database.php';

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT config_value FROM hr_system_config WHERE config_key = 'bot_token'");
$token = $stmt->fetch();

echo json_encode(['token' => $token ? $token['config_value'] : '']);
?>