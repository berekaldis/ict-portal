<?php
// webhook.php - Handle Telegram callback queries
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get the incoming webhook data
$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    http_response_code(400);
    exit('Invalid request');
}

// Handle callback queries
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callbackId = $callback['id'];
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $data = $callback['data'];
    
    // Get bot token from database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT bot_token FROM sales_telegram_destinations LIMIT 1");
    $result = $stmt->fetch();
    $botToken = $result['bot_token'] ?? '';
    
    if (!$botToken) {
        answerCallbackQuery($callbackId, 'Bot token not found', true);
        exit();
    }
    
    // Parse the callback data
    if (strpos($data, 'refresh_data') === 0) {
        // Refresh data - send updated stats
        $response = "<b>Data Refreshed!</b>\n\n";
        $response .= "Total Cases: " . getTotalCases() . "\n";
        $response .= "Completed: " . getCompletedCasesCount() . "\n";
        $response .= "Pending: " . getPendingCasesCount() . "\n";
        $response .= "Attachments: " . getTotalAttachments();
        
        answerCallbackQuery($callbackId, 'Data refreshed successfully!');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'get_stats') === 0) {
        // Get statistics
        $stats = getDashboardStats();
        $response = "<b>System Statistics</b>\n\n";
        $response .= "Total Cases: {$stats['total']}\n";
        $response .= "Completed: {$stats['completed']}\n";
        $response .= "In Progress: {$stats['in_progress']}\n";
        $response .= "Escalated: {$stats['escalated']}\n";
        $response .= "Total Attachments: {$stats['attachments']}\n";
        $response .= "Active Users: {$stats['users']}";
        
        answerCallbackQuery($callbackId, 'Statistics loaded!');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'approve_all_') === 0) {
        // Approve all cases
        $caseIds = explode(',', str_replace('approve_all_', '', $data));
        $approved = 0;
        $failed = 0;
        
        foreach ($caseIds as $caseId) {
            $result = approveCase($caseId);
            if ($result['success']) {
                $approved++;
            } else {
                $failed++;
            }
        }
        
        $response = "<b>Approval Complete!</b>\n\n";
        $response .= "Approved: $approved cases\n";
        if ($failed > 0) {
            $response .= "Failed: $failed cases";
        }
        
        answerCallbackQuery($callbackId, "$approved cases approved successfully!");
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'view_files_') === 0) {
        // View files for cases
        $caseIds = explode(',', str_replace('view_files_', '', $data));
        $response = "<b>Attachments</b>\n\n";
        
        foreach ($caseIds as $caseId) {
            $attachments = getCaseAttachments($caseId);
            if (!empty($attachments)) {
                $response .= "Case: $caseId\n";
                foreach ($attachments as $att) {
                    $response .= "  File: " . htmlspecialchars($att['file_name']) . " (" . round($att['file_size']/1024, 1) . " KB)\n";
                }
                $response .= "\n";
            }
        }
        
        if (empty($attachments)) {
            $response .= "No attachments found for the selected cases.";
        }
        
        answerCallbackQuery($callbackId, 'Files list loaded!');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'view_dashboard') === 0) {
        $baseUrl = getBaseUrl();
        $response = "<b>Dashboard Access</b>\n\n";
        $response .= "<a href='{$baseUrl}/dashboard.php'>Click here to open Dashboard</a>\n\n";
        $response .= "<i>Base URL: {$baseUrl}</i>";
        answerCallbackQuery($callbackId, 'Dashboard link');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'view_cases') === 0) {
        $baseUrl = getBaseUrl();
        $response = "<b>Cases Access</b>\n\n";
        $response .= "<a href='{$baseUrl}/cases.php'>Click here to view Cases</a>\n\n";
        $response .= "<i>Base URL: {$baseUrl}</i>";
        answerCallbackQuery($callbackId, 'Cases link');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } elseif (strpos($data, 'local_url_notice') === 0) {
        answerCallbackQuery($callbackId, 'Local URLs (localhost) cannot be opened directly via Telegram buttons. Configure App Base URL in Settings for direct links.', true);
        
    } elseif (strpos($data, 'help') === 0) {
        // Help message
        $response = "<b>Help - Kaldis Coffee Bot</b>\n\n";
        $response .= "<b>Dashboard</b> - View system dashboard\n";
        $response .= "<b>Cases</b> - View all cases\n";
        $response .= "<b>Refresh</b> - Update data\n";
        $response .= "<b>Statistics</b> - View system stats\n";
        $response .= "<b>Approve All</b> - Approve all cases\n";
        $response .= "<b>View Files</b> - View attachments\n\n";
        $response .= "<i>Click any button to interact!</i>";
        
        answerCallbackQuery($callbackId, 'Help displayed!');
        editMessageText($chatId, $messageId, $response, $botToken);
        
    } else {
        answerCallbackQuery($callbackId, 'Action processed', false);
    }
}

// ============ HELPER FUNCTIONS ============

function answerCallbackQuery($callbackId, $text, $showAlert = false) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT bot_token FROM sales_telegram_destinations LIMIT 1");
    $result = $stmt->fetch();
    $botToken = $result['bot_token'] ?? '';
    
    if (!$botToken) return;
    
    $url = "https://api.telegram.org/bot{$botToken}/answerCallbackQuery";
    
    $data = [
        'callback_query_id' => $callbackId,
        'text' => $text,
        'show_alert' => $showAlert
    ];
    
    sendTelegramCurl($url, $data);
}

function editMessageText($chatId, $messageId, $text, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/editMessageText";
    
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    sendTelegramCurl($url, $data);
}

function getTotalCases() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_cases");
    return $stmt->fetch()['count'] ?? 0;
}

function getCompletedCasesCount() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_completed_cases");
    return $stmt->fetch()['count'] ?? 0;
}

function getPendingCasesCount() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress != 'Done'");
    return $stmt->fetch()['count'] ?? 0;
}

function getTotalAttachments() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_case_attachments");
    return $stmt->fetch()['count'] ?? 0;
}

function getDashboardStats() {
    return [
        'total' => getTotalCases(),
        'completed' => getCompletedCasesCount(),
        'in_progress' => getPendingCasesCount(),
        'escalated' => getEscalatedCasesCount(),
        'attachments' => getTotalAttachments(),
        'users' => getTotalUsers()
    ];
}

function getEscalatedCasesCount() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress = 'Escalated'");
    return $stmt->fetch()['count'] ?? 0;
}

function getTotalUsers() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales_users");
    return $stmt->fetch()['count'] ?? 0;
}
