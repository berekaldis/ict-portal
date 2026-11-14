<?php
// set_webhook.php - Set the webhook URL for your bot
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get bot token from database
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT bot_token FROM sales_telegram_destinations WHERE active = 1 LIMIT 1");
$result = $stmt->fetch();
$botToken = $result['bot_token'] ?? '';

if (!$botToken) {
    $stmt = $db->query("SELECT bot_token FROM sales_telegram_destinations LIMIT 1");
    $result = $stmt->fetch();
    $botToken = $result['bot_token'] ?? '';
}

if (!$botToken) {
    die('No bot token found. Please add a Telegram destination first.');
}

// Your webhook URL (automatically dynamically constructed from getBaseUrl())
$baseUrl = getBaseUrl();
$webhookUrl = rtrim($baseUrl, '/') . '/webhook.php';

$url = "https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($webhookUrl);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Telegram Webhook Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4>Telegram Webhook Status</h4>
            </div>
            <div class="card-body">
                <p><strong>Base URL:</strong> <code><?= htmlspecialchars($baseUrl) ?></code></p>
                <p><strong>Webhook Target:</strong> <code><?= htmlspecialchars($webhookUrl) ?></code></p>
                <p><strong>HTTP Status:</strong> <span class="badge bg-info"><?= $httpCode ?></span></p>
                <div class="alert alert-secondary">
                    <strong>Telegram API Response:</strong><br>
                    <pre class="mb-0"><?= htmlspecialchars($response) ?></pre>
                </div>
                <a href="settings.php" class="btn btn-primary">&laquo; Back to Settings</a>
            </div>
        </div>
    </div>
</body>
</html>
