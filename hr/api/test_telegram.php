<?php
// api/test_telegram.php
require_once '../config/database.php';
require_once '../config/functions.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    jsonResponse(['error' => "PHP Error: $errstr in $errfile on line $errline"]);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        jsonResponse(['error' => "Shutdown Error: {$error['message']} in {$error['file']} on line {$error['line']}"]);
    }
});

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT config_value FROM hr_system_config WHERE config_key = 'bot_token'");
$botToken = $stmt->fetch()['config_value'] ?? '';

if (!$botToken) {
    echo json_encode(['error' => 'Please save bot token first']);
    exit;
}

// Test bot token
$url = "https://api.telegram.org/bot{$botToken}/getMe";
$result = @file_get_contents($url);

if (!$result) {
    echo json_encode(['error' => 'Invalid bot token']);
    exit;
}

$settings = getSettings($pdo);
$firstChatId = null;

foreach ($settings['branch_chat_ids'] as $chatId) {
    if ($chatId) {
        $firstChatId = $chatId;
        break;
    }
}

if (!$firstChatId) {
    echo json_encode(['error' => 'No branch chat IDs configured']);
    exit;
}

sendTelegramMessage($firstChatId, '✅ KALDIS HR System: Test Successful!', $botToken);
echo json_encode(['success' => true, 'message' => 'Test message sent']);
?>