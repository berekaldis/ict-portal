<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kaldisbp_ict');
define('DB_USER', 'kaldisbp_it');
define('DB_PASS', '@IT2026!');
define('TABLE_PREFIX', 'servey_');

// Site URL
define('SITE_URL', 'https://ict.kaldisbunnaet.com/servey/');

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . 'uploads/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure upload directory exists
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Create database connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Helper: escape HTML
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: sanitize input
function clean($str) {
    return trim(strip_tags($str));
}

// Helper: get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Helper: log activity
function logActivity($action, $entityType = null, $entityId = null, $details = null) {
    $db = getDB();
    $ip = getClientIP();
    $admin = $_SESSION['admin_username'] ?? 'system';
    
    $actionData = json_encode([
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details' => $details,
        'admin' => $admin
    ]);
    
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "activity_log (action, ip_address, user_agent, created_at) VALUES (?, ?, ?, NOW())");
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt->bind_param("sss", $actionData, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

// Helper: get branch list
function getBranches($activeOnly = false) {
    $db = getDB();
    $sql = "SELECT id, name, address, phone, manager_name, is_active FROM " . TABLE_PREFIX . "branches";
    if ($activeOnly) $sql .= " WHERE is_active = 1";
    $sql .= " ORDER BY name";
    $result = $db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Helper: get default technician by type
function getDefaultTechnician($type) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name FROM " . TABLE_PREFIX . "technicians WHERE type = ? AND is_default = 1 AND is_active = 1 LIMIT 1");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Helper: get all active technicians by type (for dropdown)
function getTechnicians($type) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, is_default FROM " . TABLE_PREFIX . "technicians WHERE type = ? AND is_active = 1 ORDER BY is_default DESC, name ASC");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $technicians = $result->fetch_all(MYSQLI_ASSOC);
    
    // Fallback: if no technicians found, return the default one
    if (empty($technicians)) {
        $default = getDefaultTechnician($type);
        if ($default) {
            $technicians[] = $default;
        }
    }
    
    return $technicians;
}

// Helper: get checklist categories & items
function getChecklist($type) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.id as cat_id, c.name as cat_name, c.icon, c.has_photo, c.description,
               i.id as item_id, i.question, i.help_text
        FROM " . TABLE_PREFIX . "categories c
        LEFT JOIN " . TABLE_PREFIX . "checklist_items i ON c.id = i.category_id AND i.is_active = 1
        WHERE c.type = ? AND c.is_active = 1
        ORDER BY c.display_order, i.display_order
    ");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $catId = $row['cat_id'];
        if (!isset($categories[$catId])) {
            $categories[$catId] = [
                'id' => $catId,
                'name' => $row['cat_name'],
                'icon' => $row['icon'],
                'has_photo' => (bool)$row['has_photo'],
                'description' => $row['description'],
                'items' => []
            ];
        }
        if ($row['item_id']) {
            $categories[$catId]['items'][] = [
                'id' => $row['item_id'],
                'question' => $row['question'],
                'help_text' => $row['help_text']
            ];
        }
    }
    return array_values($categories);
}

// ═══════════════════════════════════════════
// TELEGRAM INTEGRATION FUNCTIONS
// ═══════════════════════════════════════════

// Helper: Get Telegram Config (Cached)
function getTelegramConfig() {
    static $config = null;
    if ($config === null) {
        $db = getDB();
        $result = $db->query("SELECT bot_token, admin_chat_id, enabled FROM " . TABLE_PREFIX . "telegram_config LIMIT 1");
        $config = $result ? $result->fetch_assoc() : null;
    }
    return $config;
}

// Helper: Core cURL request for Telegram
function sendTelegramRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) error_log("Telegram API error: " . $error);
    return $response !== false;
}

// Helper: Send basic Telegram text message
function sendTelegram($message, $parseMode = 'Markdown') {
    $config = getTelegramConfig();
    if (!$config || !$config['enabled'] || empty($config['bot_token']) || empty($config['admin_chat_id'])) {
        return false;
    }
    
    return sendTelegramRequest(
        "https://api.telegram.org/bot" . $config['bot_token'] . "/sendMessage",
        [
            'chat_id' => $config['admin_chat_id'],
            'text' => $message,
            'parse_mode' => $parseMode
        ]
    );
}

// Helper: Send Photo to Telegram with Caption
function sendTelegramPhoto($photoUrl, $caption = '', $parseMode = 'MarkdownV2') {
    $config = getTelegramConfig();
    if (!$config || !$config['enabled'] || empty($config['bot_token']) || empty($config['admin_chat_id'])) {
        return false;
    }
    
    return sendTelegramRequest(
        "https://api.telegram.org/bot" . $config['bot_token'] . "/sendPhoto",
        [
            'chat_id' => $config['admin_chat_id'],
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => $parseMode
        ]
    );
}

// Helper: Escape text for Telegram MarkdownV2
function escapeTelegramMarkdown($text) {
    $characters = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($characters as $char) {
        $text = str_replace($char, '\\' . $char, $text);
    }
    return $text;
}

// Helper: Truncate text for Telegram (max 4096 chars per message)
function truncateTelegramMessage($msg, $maxLen = 4000) {
    if (strlen($msg) <= $maxLen) return $msg;
    return substr($msg, 0, $maxLen) . "\n\\.\\.\\. \\(truncated\\)";
}

// Helper: Send detailed survey notification to admin
function sendSurveyNotification($surveyId, $score, $state, $branchName, $techName, $managerName, $issuesSummary, $actionsTaken, $photoUrls = []) {
    $statusEmoji = $score >= 85 ? "🟢" : ($score >= 60 ? "🟡" : "🔴");
    
    // ── Header ──
    $msg = "📝 *New Survey Submitted\\!* \#" . $surveyId . "\n\n";
    $msg .= "🏢 *Branch:* " . escapeTelegramMarkdown($branchName) . "\n";
    $msg .= "👨‍💻 *Technician:* " . escapeTelegramMarkdown($techName) . "\n";
    $msg .= "📋 *Manager:* " . escapeTelegramMarkdown($managerName) . "\n";
    $msg .= $statusEmoji . " *Health Score:* " . $score . "%\n\n";

    // ── Extract detailed info from state ──
    $issues = [];
    $skippedItems = [];
    $okItems = [];
    
    foreach ($state as $item) {
        $questionText = $item['question'] ?? 'Unknown item';
        $categoryName = $item['category'] ?? 'General';
        $status = $item['status'] ?? 'Pending';
        
        if ($status === 'Issue') {
            $detailText = !empty($item['detail']) ? $item['detail'] : 'No description provided';
            $issueLine = "⚠️ *" . escapeTelegramMarkdown($categoryName) . ":* " . escapeTelegramMarkdown($questionText);
            $issueLine .= "\n    ↳ _" . escapeTelegramMarkdown($detailText) . "_";
            $issues[] = $issueLine;
        } elseif ($status === 'Skip') {
            $skipReason = !empty($item['skipReason']) ? $item['skipReason'] : 'No reason';
            $skippedItems[] = "⏭️ " . escapeTelegramMarkdown($questionText) . " _\\(" . escapeTelegramMarkdown($skipReason) . "\\)_";
        } elseif ($status === 'OK') {
            $okItems[] = $categoryName . ": " . $questionText;
        }
    }

    $okCount = count($okItems);
    $issueCount = count($issues);
    $skipCount = count($skippedItems);
    
    // ── Stats Line ──
    $msg .= "✅ OK: $okCount | ⚠️ Issues: $issueCount | ⏭️ Skipped: $skipCount\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    // ── Issues Section ──
    if ($issueCount > 0) {
        $msg .= "*🚨 Issues Found \\(" . $issueCount . "\\):*\n\n";
        $msg .= implode("\n\n", $issues) . "\n\n";
    }
    
    // ── Skipped Section ──
    if ($skipCount > 0) {
        $msg .= "*⏭️ Skipped Items \\(" . $skipCount . "\\):*\n";
        $msg .= implode("\n", $skippedItems) . "\n\n";
    }

    // ── Summary Section ──
    if (!empty($issuesSummary)) {
        $msg .= "*⚠️ Summary:*\n" . escapeTelegramMarkdown($issuesSummary) . "\n\n";
    }
    
    // ── Actions Section ──
    if (!empty($actionsTaken)) {
        $msg .= "*🔧 Actions Taken:*\n" . escapeTelegramMarkdown($actionsTaken) . "\n\n";
    }

    // ── Footer ──
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "_Submitted via Kaldis ICT Survey System_";

    // Truncate if too long for Telegram
    $msg = truncateTelegramMessage($msg);

    // Send the text summary
    sendTelegram($msg, 'MarkdownV2');

    // ── Send photos separately to Telegram ──
    if (!empty($photoUrls)) {
        foreach ($photoUrls as $index => $url) {
            $caption = "📸 Evidence " . ($index + 1) . " for Report \#" . $surveyId . "\n" . escapeTelegramMarkdown($branchName);
            sendTelegramPhoto($url, $caption, 'MarkdownV2');
            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
    }
    
    return true;
}

// ═══════════════════════════════════════════
// GENERAL HELPER FUNCTIONS
// ═══════════════════════════════════════════

// Helper: format date
function formatDate($date, $format = 'Y-m-d') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// Helper: get health status badge
function getHealthBadge($score) {
    if ($score >= 90) return '<span class="badge badge-success">Excellent</span>';
    if ($score >= 75) return '<span class="badge badge-warning">Good</span>';
    if ($score >= 50) return '<span class="badge badge-orange">Fair</span>';
    return '<span class="badge badge-danger">Poor</span>';
}

// Helper: paginate
function paginate($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Helper: activity log icon
function getActivityIcon($a) {
    // If it's JSON, decode it first
    if (is_string($a) && strpos($a, '{') === 0) {
        $data = json_decode($a, true);
        $a = $data['action'] ?? 'unknown';
    }
    
    $m = [
        'branch_added' => '🏢', 'branch_updated' => '✏️', 'branch_deleted' => '🗑️',
        'technician_added' => '👨‍🔧', 'technician_updated' => '✏️', 'technician_deleted' => '🗑️',
        'technician_set_default' => '⭐', 'category_added' => '📁', 'category_updated' => '✏️',
        'category_deleted' => '🗑️', 'checklist_item_added' => '📝', 'checklist_item_updated' => '✏️',
        'checklist_item_deleted' => '🗑️', 'telegram_config_updated' => '🤖',
        'password_changed' => '🔒', 'report_deleted' => '📋', 'survey_submitted' => '📝',
        'login' => '🔑', 'login_failed' => '🚫', 'logout' => '🚪',
        'hardware_added' => '💻', 'hardware_updated' => '✏️', 'hardware_deleted' => '🗑️',
        'hardware_audit_completed' => '📊', 'view_report' => '👁️'
    ];
    return $m[$a] ?? '📌';
}

// Helper: activity log background color
function getActivityColor($a) {
    // If it's JSON, decode it first
    if (is_string($a) && strpos($a, '{') === 0) {
        $data = json_decode($a, true);
        $a = $data['action'] ?? 'unknown';
    }
    
    if (strpos($a, 'deleted') !== false) return '#fef2f2';
    if (strpos($a, 'updated') !== false) return '#eff6ff';
    if (strpos($a, 'added') !== false || strpos($a, 'submitted') !== false) return '#ecfdf5';
    if ($a === 'login_failed') return '#fef2f2';
    return '#f9fafb';
}

// Helper: activity log action text
function formatActivityAction($a) {
    // If it's JSON, decode it first
    if (is_string($a) && strpos($a, '{') === 0) {
        $data = json_decode($a, true);
        $a = $data['action'] ?? 'unknown';
    }
    
    $m = [
        'branch_added' => 'Added branch', 'branch_updated' => 'Updated branch',
        'branch_deleted' => 'Deleted branch', 'technician_added' => 'Added technician',
        'technician_updated' => 'Updated technician', 'technician_deleted' => 'Deleted technician',
        'technician_set_default' => 'Set default', 'category_added' => 'Added category',
        'category_updated' => 'Updated category', 'category_deleted' => 'Deleted category',
        'checklist_item_added' => 'Added item', 'checklist_item_updated' => 'Updated item',
        'checklist_item_deleted' => 'Deleted item', 'telegram_config_updated' => 'Updated Telegram',
        'password_changed' => 'Changed password', 'report_deleted' => 'Deleted report',
        'survey_submitted' => 'Submitted survey', 
        'login' => 'Signed in', 'login_failed' => 'Failed sign-in', 'logout' => 'Signed out',
        'hardware_added' => 'Added hardware asset', 'hardware_updated' => 'Updated hardware asset',
        'hardware_deleted' => 'Deleted hardware asset', 'hardware_audit_completed' => 'Completed hardware audit',
        'view_report' => 'Viewed report'
    ];
    return $m[$a] ?? str_replace('_', ' ', $a);
}

// Helper: health bar CSS class
function healthBarClass($s) {
    if ($s >= 90) return 'excellent';
    if ($s >= 75) return 'good';
    if ($s >= 50) return 'fair';
    return 'poor';
}

// ═══════════════════════════════════════════
// HARDWARE AUDIT FUNCTIONS
// ═══════════════════════════════════════════

// Helper: Get all hardware assets for a branch
function getHardwareAssets($branchId = null, $assetType = null, $status = null) {
    $db = getDB();
    $sql = "SELECT a.*, b.name as branch_name 
            FROM " . TABLE_PREFIX . "hardware_assets a 
            LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id 
            WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($branchId) {
        $sql .= " AND a.branch_id = ?";
        $params[] = $branchId;
        $types .= "i";
    }
    if ($assetType) {
        $sql .= " AND a.asset_type = ?";
        $params[] = $assetType;
        $types .= "s";
    }
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY a.branch_id, a.asset_type, a.created_at DESC";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $assets;
}

// Helper: Get single hardware asset
function getHardwareAsset($assetId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, b.name as branch_name 
                          FROM " . TABLE_PREFIX . "hardware_assets a 
                          LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id 
                          WHERE a.id = ?");
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset = $result->fetch_assoc();
    $stmt->close();
    return $asset;
}

// Helper: Add or update hardware asset
function saveHardwareAsset($data, $assetId = null) {
    $db = getDB();
    $result = false;
    
    if ($assetId) {
        // Get old values for logging
        $oldAsset = getHardwareAsset($assetId);
        
        $sql = "UPDATE " . TABLE_PREFIX . "hardware_assets SET 
                branch_id = ?, asset_tag = ?, serial_number = ?, asset_type = ?,
                brand = ?, model = ?, specifications = ?, purchase_date = ?,
                warranty_expiry = ?, status = ?, assigned_to = ?, location = ?, notes = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issssssssssssi", 
            $data['branch_id'], $data['asset_tag'], $data['serial_number'], $data['asset_type'],
            $data['brand'], $data['model'], $data['specifications'], $data['purchase_date'],
            $data['warranty_expiry'], $data['status'], $data['assigned_to'], $data['location'], 
            $data['notes'], $assetId
        );
        $result = $stmt->execute();
        
        if ($result) {
            // Log the update
            logHardwareAudit($assetId, 'updated', json_encode($oldAsset), json_encode($data));
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO " . TABLE_PREFIX . "hardware_assets 
                (branch_id, asset_tag, serial_number, asset_type, brand, model, 
                 specifications, purchase_date, warranty_expiry, status, assigned_to, location, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issssssssssss",
            $data['branch_id'], $data['asset_tag'], $data['serial_number'], $data['asset_type'],
            $data['brand'], $data['model'], $data['specifications'], $data['purchase_date'],
            $data['warranty_expiry'], $data['status'], $data['assigned_to'], $data['location'], $data['notes']
        );
        $result = $stmt->execute();
        $newId = $db->insert_id;
        
        if ($result) {
            logHardwareAudit($newId, 'created', null, json_encode($data));
        }
        $stmt->close();
    }
    
    return $result;
}

// Helper: Delete hardware asset
function deleteHardwareAsset($assetId) {
    $db = getDB();
    $asset = getHardwareAsset($assetId);
    if ($asset) {
        logHardwareAudit($assetId, 'deleted', json_encode($asset), null);
    }
    $stmt = $db->prepare("DELETE FROM " . TABLE_PREFIX . "hardware_assets WHERE id = ?");
    $stmt->bind_param("i", $assetId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Helper: Log hardware audit actions
function logHardwareAudit($assetId, $action, $oldValues = null, $newValues = null) {
    $db = getDB();
    $ip = getClientIP();
    $admin = $_SESSION['admin_username'] ?? $_SESSION['user_name'] ?? 'system';
    
    // Convert null to empty string for database storage
    $oldValuesStr = $oldValues ?? '';
    $newValuesStr = $newValues ?? '';
    
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_audit_logs 
                          (asset_id, action, old_values, new_values, performed_by, ip_address) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $assetId, $action, $oldValuesStr, $newValuesStr, $admin, $ip);
    $stmt->execute();
    $stmt->close();
}

// Helper: Get hardware audit checklists by asset type
function getHardwareChecklist($assetType) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM " . TABLE_PREFIX . "hardware_audit_checklists 
                          WHERE asset_type = ? AND is_active = 1 
                          ORDER BY display_order");
    $stmt->bind_param("s", $assetType);
    $stmt->execute();
    $result = $stmt->get_result();
    $checklist = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $checklist;
}

// Helper: Get asset statistics
function getHardwareStats($branchId = null) {
    $db = getDB();
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as operational,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                SUM(CASE WHEN status = 'repair' THEN 1 ELSE 0 END) as repair,
                SUM(CASE WHEN status = 'decommissioned' THEN 1 ELSE 0 END) as decommissioned,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
                asset_type,
                COUNT(DISTINCT branch_id) as branches_with_assets
            FROM " . TABLE_PREFIX . "hardware_assets";
    
    if ($branchId) {
        $sql .= " WHERE branch_id = $branchId";
    }
    
    $sql .= " GROUP BY asset_type WITH ROLLUP";
    
    $result = $db->query($sql);
    if (!$result) {
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Helper: Get warranty expiring soon (next 90 days)
function getExpiringWarranties($days = 90) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, b.name as branch_name 
                          FROM " . TABLE_PREFIX . "hardware_assets a
                          LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id
                          WHERE a.warranty_expiry IS NOT NULL 
                          AND a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                          AND a.status != 'decommissioned'
                          ORDER BY a.warranty_expiry ASC");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $warranties = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $warranties;
}

// Helper: Get assets needing audit (not audited in last 6 months)
function getAssetsNeedingAudit($months = 6) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, b.name as branch_name 
                          FROM " . TABLE_PREFIX . "hardware_assets a
                          LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id
                          WHERE (a.last_audit_date IS NULL 
                          OR a.last_audit_date < DATE_SUB(NOW(), INTERVAL ? MONTH))
                          AND a.status != 'decommissioned'
                          ORDER BY a.last_audit_date ASC");
    $stmt->bind_param("i", $months);
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $assets;
}

// Helper: Create hardware audit report
function createHardwareAuditReport($branchId, $assets, $auditor, $summary, $recommendations) {
    $db = getDB();
    
    // Calculate counts
    $totalAssets = count($assets);
    $operational = 0;
    $maintenance = 0;
    $repair = 0;
    $decommissioned = 0;
    $lost = 0;
    
    foreach ($assets as $asset) {
        switch ($asset['status']) {
            case 'operational': $operational++; break;
            case 'maintenance': $maintenance++; break;
            case 'repair': $repair++; break;
            case 'decommissioned': $decommissioned++; break;
            case 'lost': $lost++; break;
        }
    }
    
    // SQL with 11 placeholders (10 variables + 'completed' as the 11th)
    $sql = "INSERT INTO " . TABLE_PREFIX . "hardware_audit_reports 
            (branch_id, audited_by, total_assets, operational_count, 
             maintenance_count, repair_count, decommissioned_count, lost_count, 
             summary, recommendations, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $db->error);
        return false;
    }
    
    // Status is 'completed' - bind all 11 parameters
    $status = 'completed';
    
    // Bind parameters - 11 parameters matching the 11 placeholders
    $stmt->bind_param("isiiiiiiiss", 
        $branchId,      // i - branch_id
        $auditor,       // s - audited_by
        $totalAssets,   // i - total_assets
        $operational,   // i - operational_count
        $maintenance,   // i - maintenance_count
        $repair,        // i - repair_count
        $decommissioned,// i - decommissioned_count
        $lost,          // i - lost_count
        $summary,       // s - summary
        $recommendations,// s - recommendations
        $status         // s - status
    );
    
    if (!$stmt->execute()) {
        error_log("Failed to create hardware audit report: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $reportId = $db->insert_id;
    $stmt->close();
    
    // Update last audit date for assets
    foreach ($assets as $asset) {
        $updateStmt = $db->prepare("UPDATE " . TABLE_PREFIX . "hardware_assets 
                                    SET last_audit_date = NOW() WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("i", $asset['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    return $reportId;
}
// Helper: Get hardware audit schedules
function getAuditSchedule() {
    $db = getDB();
    $schedules = [];
    
    // Check if table exists first
    $tableExists = false;
    $check = $db->query("SHOW TABLES LIKE '" . TABLE_PREFIX . "hardware_audit_schedule'");
    if ($check && $check->num_rows > 0) {
        $tableExists = true;
    }
    
    if ($tableExists) {
        $result = $db->query("
            SELECT s.*, b.name as branch_name 
            FROM " . TABLE_PREFIX . "hardware_audit_schedule s
            LEFT JOIN " . TABLE_PREFIX . "branches b ON s.branch_id = b.id
            ORDER BY s.next_audit_date ASC
        ");
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // Fallback: Generate dynamically based on branches and their latest audit reports
    $branches = getBranches(true); // Active branches
    foreach ($branches as $branch) {
        // Find latest audit report for this branch
        $stmt = $db->prepare("
            SELECT MAX(created_at) as last_audit 
            FROM " . TABLE_PREFIX . "hardware_audit_reports 
            WHERE branch_id = ?
        ");
        $lastAuditDate = null;
        if ($stmt) {
            $stmt->bind_param("i", $branch['id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $lastAuditDate = $res['last_audit'] ?? null;
            $stmt->close();
        }
        
        // If no report, check the minimum last_audit_date of assets in this branch
        if (!$lastAuditDate) {
            $stmt = $db->prepare("
                SELECT MIN(last_audit_date) as last_audit 
                FROM " . TABLE_PREFIX . "hardware_assets 
                WHERE branch_id = ? AND status != 'decommissioned'
            ");
            if ($stmt) {
                $stmt->bind_param("i", $branch['id']);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $lastAuditDate = $res['last_audit'] ?? null;
                $stmt->close();
            }
        }
        
        $frequency = '6 months';
        if ($lastAuditDate) {
            $nextAuditDate = date('Y-m-d', strtotime($lastAuditDate . ' + 6 months'));
        } else {
            $nextAuditDate = date('Y-m-d'); // Due now
        }
        
        $schedules[] = [
            'id' => $branch['id'],
            'branch_id' => $branch['id'],
            'branch_name' => $branch['name'],
            'asset_type' => null, // All Types
            'audit_frequency' => $frequency,
            'last_audit_date' => $lastAuditDate,
            'next_audit_date' => $nextAuditDate,
            'assigned_to' => $branch['manager_name'] ?: 'IT Team'
        ];
    }
    
    // Sort by next audit date
    usort($schedules, function($a, $b) {
        return strcmp($a['next_audit_date'], $b['next_audit_date']);
    });
    
    return $schedules;
}

// Helper: Get hardware audit reports
function getHardwareAuditReports($branchId = null) {
    $db = getDB();
    $sql = "SELECT r.*, b.name as branch_name 
            FROM " . TABLE_PREFIX . "hardware_audit_reports r
            LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id";
    $params = [];
    $types = "";
    
    if ($branchId) {
        $sql .= " WHERE r.branch_id = ?";
        $params[] = $branchId;
        $types .= "i";
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($sql);
    if ($branchId) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $reports;
}

// Helper: Get asset audit trail logs
function getAssetAuditLogs($assetId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM " . TABLE_PREFIX . "hardware_audit_logs 
        WHERE asset_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $logs;
}

// Helper: Get hardware dashboard stats
function getHardwareDashboardStats() {
    $db = getDB();
    $stats = [];
    
    // Total assets by status
    $statusStats = $db->query("SELECT status, COUNT(*) as count FROM " . TABLE_PREFIX . "hardware_assets GROUP BY status");
    $stats['by_status'] = $statusStats ? $statusStats->fetch_all(MYSQLI_ASSOC) : [];
    
    // Assets by type
    $typeStats = $db->query("SELECT asset_type, COUNT(*) as count FROM " . TABLE_PREFIX . "hardware_assets GROUP BY asset_type");
    $stats['by_type'] = $typeStats ? $typeStats->fetch_all(MYSQLI_ASSOC) : [];
    
    // Assets by branch
    $branchStats = $db->query("SELECT b.name, COUNT(a.id) as count 
        FROM " . TABLE_PREFIX . "branches b 
        LEFT JOIN " . TABLE_PREFIX . "hardware_assets a ON b.id = a.branch_id 
        GROUP BY b.id ORDER BY count DESC");
    $stats['by_branch'] = $branchStats ? $branchStats->fetch_all(MYSQLI_ASSOC) : [];
    
    // Monthly additions (last 12 months)
    $monthlyStats = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM " . TABLE_PREFIX . "hardware_assets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
        GROUP BY month ORDER BY month");
    $stats['monthly_additions'] = $monthlyStats ? $monthlyStats->fetch_all(MYSQLI_ASSOC) : [];
    
    // Warranty expiring in next 3 months
    $stats['warranty_expiring'] = count(getExpiringWarranties(90));
    
    // Open issues count
    $openIssues = $db->query("SELECT COUNT(*) as count FROM " . TABLE_PREFIX . "hardware_issues WHERE status IN ('open', 'in_progress')");
    $stats['open_issues'] = $openIssues ? ($openIssues->fetch_assoc()['count'] ?? 0) : 0;
    
    // Upcoming maintenance
    $upcomingMaintenance = getUpcomingMaintenance(30);
    $stats['upcoming_maintenance'] = count($upcomingMaintenance);
    
    // Total maintenance cost (last year)
    $costStats = $db->query("SELECT SUM(cost) as total_cost FROM " . TABLE_PREFIX . "hardware_maintenance 
        WHERE maintenance_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)");
    $stats['maintenance_cost'] = $costStats ? ($costStats->fetch_assoc()['total_cost'] ?? 0) : 0;
    
    return $stats;
}

// Helper: Get upcoming maintenance
function getUpcomingMaintenance($days = 30) {
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, a.brand, a.model, a.asset_tag, b.name as branch_name 
        FROM " . TABLE_PREFIX . "hardware_maintenance m
        LEFT JOIN " . TABLE_PREFIX . "hardware_assets a ON m.asset_id = a.id
        LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id
        WHERE m.next_maintenance_date IS NOT NULL 
        AND m.next_maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY m.next_maintenance_date ASC");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $maintenance = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $maintenance;
}

// Helper: Add maintenance record
function addMaintenanceRecord($assetId, $data) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_maintenance 
        (asset_id, maintenance_type, description, cost, performed_by, maintenance_date, next_maintenance_date, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssss", 
        $assetId, $data['maintenance_type'], $data['description'], $data['cost'], 
        $data['performed_by'], $data['maintenance_date'], $data['next_maintenance_date'], $data['notes']
    );
    $result = $stmt->execute();
    
    if ($result) {
        logHardwareAudit($assetId, 'maintenance_added', null, json_encode($data));
        // Update asset status if needed
        if ($data['maintenance_type'] == 'corrective' || $data['maintenance_type'] == 'emergency') {
            $updateStmt = $db->prepare("UPDATE " . TABLE_PREFIX . "hardware_assets SET status = 'maintenance' WHERE id = ?");
            $updateStmt->bind_param("i", $assetId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    $stmt->close();
    return $result;
}

// Helper: Get maintenance history
function getMaintenanceHistory($assetId, $limit = null) {
    $db = getDB();
    $sql = "SELECT * FROM " . TABLE_PREFIX . "hardware_maintenance WHERE asset_id = ? ORDER BY maintenance_date DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $history;
}

// Helper: Add software to asset
function addAssetSoftware($assetId, $data) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_software 
        (asset_id, software_name, version, license_key, license_type, expiry_date, installed_date, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", 
        $assetId, $data['software_name'], $data['version'], $data['license_key'], 
        $data['license_type'], $data['expiry_date'], $data['installed_date'], $data['notes']
    );
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Helper: Get software installed on asset
function getAssetSoftware($assetId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM " . TABLE_PREFIX . "hardware_software WHERE asset_id = ? AND is_active = 1 ORDER BY software_name");
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $software = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $software;
}

// Helper: Update network info
function updateNetworkInfo($assetId, $data) {
    $db = getDB();
    $checkStmt = $db->prepare("SELECT id FROM " . TABLE_PREFIX . "hardware_network WHERE asset_id = ?");
    $checkStmt->bind_param("i", $assetId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "hardware_network SET 
            ip_address = ?, mac_address = ?, hostname = ?, domain = ?, vlan = ?, switch_port = ?, notes = ? 
            WHERE asset_id = ?");
        $stmt->bind_param("sssssssi", 
            $data['ip_address'], $data['mac_address'], $data['hostname'], 
            $data['domain'], $data['vlan'], $data['switch_port'], $data['notes'], $assetId
        );
    } else {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_network 
            (asset_id, ip_address, mac_address, hostname, domain, vlan, switch_port, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", 
            $assetId, $data['ip_address'], $data['mac_address'], $data['hostname'], 
            $data['domain'], $data['vlan'], $data['switch_port'], $data['notes']
        );
    }
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Helper: Get network info
function getNetworkInfo($assetId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM " . TABLE_PREFIX . "hardware_network WHERE asset_id = ?");
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();
    return $info;
}

// Helper: Report hardware issue
function reportHardwareIssue($assetId, $data) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_issues 
        (asset_id, reported_by, issue_type, severity, title, description, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'open')");
    $stmt->bind_param("isssss", 
        $assetId, $data['reported_by'], $data['issue_type'], $data['severity'], $data['title'], $data['description']
    );
    $result = $stmt->execute();
    
    if ($result) {
        $issueId = $db->insert_id;
        // Send notification
        $asset = getHardwareAsset($assetId);
        sendTelegram("⚠️ *NEW HARDWARE ISSUE REPORTED*\n\n" .
            "Asset: " . ($asset['brand'] ?? '') . " " . ($asset['model'] ?? '') . "\n" .
            "Branch: " . ($asset['branch_name'] ?? '') . "\n" .
            "Severity: " . strtoupper($data['severity']) . "\n" .
            "Title: " . $data['title'] . "\n" .
            "Reported by: " . $data['reported_by'], 'MarkdownV2');
    }
    $stmt->close();
    return $result;
}

// Helper: Get open issues
function getOpenIssues($assetId = null) {
    $db = getDB();
    $sql = "SELECT i.*, a.brand, a.model, a.asset_tag, b.name as branch_name 
            FROM " . TABLE_PREFIX . "hardware_issues i
            LEFT JOIN " . TABLE_PREFIX . "hardware_assets a ON i.asset_id = a.id
            LEFT JOIN " . TABLE_PREFIX . "branches b ON a.branch_id = b.id
            WHERE i.status IN ('open', 'in_progress')";
    if ($assetId) {
        $sql .= " AND i.asset_id = " . intval($assetId);
    }
    $sql .= " ORDER BY FIELD(i.severity, 'critical', 'high', 'medium', 'low'), i.reported_at DESC";
    $result = $db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Helper: Resolve issue
function resolveIssue($issueId, $resolutionNotes) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "hardware_issues 
        SET status = 'resolved', resolved_at = NOW(), resolution_notes = ? 
        WHERE id = ?");
    $stmt->bind_param("si", $resolutionNotes, $issueId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Helper: Export hardware data to CSV
function exportHardwareToCSV() {
    $assets = getHardwareAssets();
    $filename = 'hardware_assets_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Asset Tag', 'Branch', 'Type', 'Brand', 'Model', 'Serial Number', 
        'Status', 'Purchase Date', 'Warranty Expiry', 'Location', 'Assigned To', 'Last Audit']);
    
    // Data
    foreach ($assets as $asset) {
        fputcsv($output, [
            $asset['asset_tag'] ?? '',
            $asset['branch_name'] ?? '',
            $asset['asset_type'],
            $asset['brand'] ?? '',
            $asset['model'] ?? '',
            $asset['serial_number'] ?? '',
            $asset['status'],
            $asset['purchase_date'] ?? '',
            $asset['warranty_expiry'] ?? '',
            $asset['location'] ?? '',
            $asset['assigned_to'] ?? '',
            $asset['last_audit_date'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}
?>