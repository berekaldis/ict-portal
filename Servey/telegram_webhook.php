<?php
require_once 'config.php';

 $input = file_get_contents('php://input');
 $update = json_decode($input, true);

if (!$update || !isset($update['message'])) exit;

 $chatId = $update['message']['chat']['id'] ?? null;
 $text = trim($update['message']['text'] ?? '');

if (!$chatId) exit;

 $db = getDB();
 $res = $db->query("SELECT bot_token, admin_chat_id FROM " . TABLE_PREFIX . "telegram_config LIMIT 1");
 $config = $res->fetch_assoc();
 $botToken = $config['bot_token'] ?? null;

if (!$botToken) exit;

function replyToChat($token, $chatId, $text, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'];
    if ($replyMarkup) $data['reply_markup'] = json_encode($replyMarkup);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

if (strpos($text, '/') === 0) {
    $command = strtolower(explode(' ', $text)[0]);
    
    switch ($command) {
        case '/start':
            $msg = "☕ *Welcome to Kaldis Coffee IT Survey Bot\\!*\n\nSelect a survey type below to begin\\. The survey will open inside Telegram\\.";
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '💻 Software Survey', 'web_app' => ['url' => SITE_URL . 'software.php']],
                        ['text' => '🛠️ Hardware Survey', 'web_app' => ['url' => SITE_URL . 'hardware.php']]
                    ],
                    [
                        ['text' => '🏠 Open Home Page', 'url' => SITE_URL]
                    ]
                ]
            ];
            replyToChat($botToken, $chatId, $msg, $keyboard);
            break;

        case '/help':
            $msg = "📋 *Available Commands:*\n\n/start \\- Get survey links\n/status \\- View latest survey\n/branches \\- List branches\n/help \\- Show this message";
            replyToChat($botToken, $chatId, $msg);
            break;

        case '/status':
            $stmt = $db->prepare("SELECT s.health_score, s.type, s.submitted_at, b.name as branch_name FROM " . TABLE_PREFIX . "reports s JOIN " . TABLE_PREFIX . "branches b ON s.branch_id = b.id ORDER BY s.submitted_at DESC LIMIT 1");
            $stmt->execute();
            $latest = $stmt->get_result()->fetch_assoc();
            if ($latest) {
                $e = $latest['health_score'] >= 85 ? "🟢" : ($latest['health_score'] >= 60 ? "🟡" : "🔴");
                $msg = "📊 *Latest Report*\n\n🏢 {$latest['branch_name']}\n💻 Type: " . ucfirst($latest['type']) . "\n{$e} Score: {$latest['health_score']}%\n🕒 {$latest['submitted_at']}";
            } else {
                $msg = "No surveys submitted yet.";
            }
            replyToChat($botToken, $chatId, $msg);
            break;

        case '/branches':
            $branches = getBranches(true);
            $msg = "🏢 *Active Branches:*\n\n";
            foreach ($branches as $b) $msg .= "• {$b['name']}\n";
            if (empty($branches)) $msg .= "No active branches.";
            replyToChat($botToken, $chatId, $msg);
            break;

        default:
            replyToChat($botToken, $chatId, "❓ Unknown command. Type /help");
            break;
    }
}
?>