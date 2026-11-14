<?php
require_once 'config.php';

 $db = getDB();
 $result = $db->query("SELECT bot_token FROM " . TABLE_PREFIX . "telegram_config LIMIT 1");
 $config = $result->fetch_assoc();

if (!$config || empty($config['bot_token'])) {
    die("❌ Bot token not found in database! Please set it in the Admin Panel first.");
}

 $botToken = $config['bot_token'];
 $webhookUrl = SITE_URL . 'telegram_webhook.php';

// Check if the webhook URL is a valid HTTPS URL (Telegram requires HTTPS)
if (strpos($webhookUrl, 'https://') !== 0) {
    die("❌ Webhook URL must be HTTPS. Current URL: " . $webhookUrl);
}

 $apiUrl = "https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($webhookUrl);

// Use cURL instead of file_get_contents (more reliable on shared hosting)
 $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production if you have proper SSL certs
 $response = curl_exec($ch);
 $error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("❌ cURL Error connecting to Telegram: " . $error);
}

if ($response) {
    $result = json_decode($response, true);
    if (isset($result['ok']) && $result['ok'] === true) {
        echo "✅ <strong>Success!</strong> Telegram Webhook set to: " . $webhookUrl;
        echo "<br>You can now go to Telegram and message your bot using /start";
    } else {
        echo "❌ Failed to set webhook.<br>";
        echo "Telegram Response: <pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "❌ No response received from Telegram API.";
}
?>