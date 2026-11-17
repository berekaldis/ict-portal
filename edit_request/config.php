<?php
// ============================================
// Kaldis Voucher System - Configuration
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', '0');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kaldisbp_ict');
define('DB_USER', 'kaldisbp_it');
define('DB_PASS', '@IT2026!');

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8');
define('TELEGRAM_CHAT_ID_ADMIN', '-1001502645326');

// Timezone
date_default_timezone_set('Africa/Addis_Ababa');

// Company System Database Configuration (kaldisbp_company_system / company_system)
define('COMPANY_DB_HOST', 'localhost');
define('COMPANY_DB_NAME', 'kaldisbp_company_system');
define('COMPANY_DB_USER', 'kaldisbp_it');
define('COMPANY_DB_PASS', '@IT2026!');

// ============================================
// Database Connection Function
// ============================================
function db() {
    static $pdo = null;
    static $schemaSynced = false;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
        }
    }

    if (!$schemaSynced) {
        syncRequestsSchema($pdo);
        syncUsersSchema($pdo);
        $schemaSynced = true;
    }
    
    return $pdo;
}

// ============================================
// Company System Database Connection Function
// (Connects to kaldisbp_company_system or company_system with automatic fallback)
// ============================================
function companyDb() {
    static $companyPdo = null;
    if ($companyPdo !== null) {
        return $companyPdo;
    }

    $connectionCandidates = [
        ['host' => '127.0.0.1', 'db' => 'company_system', 'user' => 'root', 'pass' => ''],
        ['host' => COMPANY_DB_HOST, 'db' => COMPANY_DB_NAME, 'user' => 'kaldisbp_IT', 'pass' => '@Kaldis2026!'],
        ['host' => COMPANY_DB_HOST, 'db' => COMPANY_DB_NAME, 'user' => COMPANY_DB_USER, 'pass' => COMPANY_DB_PASS],
        ['host' => COMPANY_DB_HOST, 'db' => 'company_system', 'user' => COMPANY_DB_USER, 'pass' => COMPANY_DB_PASS],
        ['host' => '127.0.0.1', 'db' => COMPANY_DB_NAME, 'user' => 'root', 'pass' => ''],
        ['host' => DB_HOST, 'db' => DB_NAME, 'user' => DB_USER, 'pass' => DB_PASS]
    ];

    foreach ($connectionCandidates as $cand) {
        try {
            $companyPdo = new PDO(
                "mysql:host={$cand['host']};dbname={$cand['db']};charset=utf8mb4",
                $cand['user'],
                $cand['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            return $companyPdo;
        } catch (Exception $e) {
            // try next candidate
        }
    }

    error_log("companyDb: Failed to connect to company system database");
    return null;
}

// Backward compatibility alias for editDB
function editDB() {
    return db();
}

function tableExists(PDO $db, $tableName) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$tableName]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $db, $tableName, $columnName) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$tableName, $columnName]);
    return (int)$stmt->fetchColumn() > 0;
}

function getTableName(PDO $db, $baseName) {
    static $cache = [];
    if (isset($cache[$baseName])) {
        return $cache[$baseName];
    }
    
    if ($baseName === 'departments' || $baseName === 'department') {
        if (tableExists($db, 'edit_departments')) {
            $cache[$baseName] = 'edit_departments';
            return 'edit_departments';
        }
        if (tableExists($db, 'edit_department')) {
            $cache[$baseName] = 'edit_department';
            return 'edit_department';
        }
        if (tableExists($db, 'departments')) {
            $cache[$baseName] = 'departments';
            return 'departments';
        }
        $cache[$baseName] = 'edit_departments';
        return 'edit_departments';
    }
    
    if ($baseName === 'usernames' || $baseName === 'username' || $baseName === 'request_username') {
        if (tableExists($db, 'edit_request_username')) {
            $cache[$baseName] = 'edit_request_username';
            return 'edit_request_username';
        }
        if (tableExists($db, 'edit_request_usernames')) {
            $cache[$baseName] = 'edit_request_usernames';
            return 'edit_request_usernames';
        }
        if (tableExists($db, 'request_username')) {
            $cache[$baseName] = 'request_username';
            return 'request_username';
        }
        $cache[$baseName] = 'edit_request_username';
        return 'edit_request_username';
    }
    
    $editName = (strpos($baseName, 'edit_') === 0) ? $baseName : 'edit_' . $baseName;
    if (tableExists($db, $editName)) {
        $cache[$baseName] = $editName;
        return $editName;
    }
    
    $cleanName = str_replace('edit_', '', $baseName);
    if (tableExists($db, $cleanName)) {
        $cache[$baseName] = $cleanName;
        return $cleanName;
    }
    
    $cache[$baseName] = $editName;
    return $editName;
}

// ============================================
// Track & Record Request Usernames
// ============================================
function trackRequestUsername(PDO $pdo, $username, $branch = '') {
    $cleanUser = trim((string)$username);
    if ($cleanUser === '') return;
    
    $table = getTableName($pdo, 'usernames');
    try {
        if (!tableExists($pdo, $table)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_name VARCHAR(150) NOT NULL UNIQUE,
                branch VARCHAR(200) DEFAULT NULL,
                request_count INT UNSIGNED DEFAULT 1,
                first_seen DATETIME NOT NULL,
                last_seen DATETIME NOT NULL,
                INDEX idx_username (user_name),
                INDEX idx_count (request_count)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        
        $cleanBranch = trim((string)$branch);
        $stmt = $pdo->prepare("INSERT INTO `{$table}` (user_name, branch, request_count, first_seen, last_seen) 
            VALUES (?, ?, 1, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE 
            request_count = request_count + 1, 
            branch = IF(? != '', ?, branch),
            last_seen = NOW()");
        $stmt->execute([$cleanUser, $cleanBranch, $cleanBranch, $cleanBranch]);
    } catch (Exception $e) {
        error_log("Failed to track request username: " . $e->getMessage());
    }
}

function syncRequestsSchema(PDO $db) {
    $reqTable = getTableName($db, 'requests');

    if (!tableExists($db, $reqTable)) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `{$reqTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(50) NOT NULL UNIQUE,
                timestamp DATETIME NOT NULL,
                branch VARCHAR(200) NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                store_user VARCHAR(150) DEFAULT NULL,
                voucher_code VARCHAR(100) DEFAULT NULL,
                voucher_type VARCHAR(100) DEFAULT NULL,
                action_type VARCHAR(100) NOT NULL,
                edit_type VARCHAR(100) DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                target_dept VARCHAR(200) NOT NULL,
                incorrect_date VARCHAR(50) DEFAULT NULL,
                correct_date VARCHAR(50) DEFAULT NULL,
                shift VARCHAR(50) DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'Pending',
                approver VARCHAR(200) DEFAULT NULL,
                telegram_msg_id VARCHAR(100) DEFAULT NULL,
                INDEX idx_request_id (request_id),
                INDEX idx_branch (branch),
                INDEX idx_status (status),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            error_log("Failed to create requests table: " . $e->getMessage());
        }
        return;
    }

    if (!columnExists($db, $reqTable, 'request_id')) {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN request_id VARCHAR(50) NOT NULL AFTER id");
        try {
            $db->exec("ALTER TABLE `{$reqTable}` ADD UNIQUE INDEX idx_request_id (request_id)");
        } catch (Exception $e) {}
    }

    if (columnExists($db, $reqTable, 'action_type')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN action_type VARCHAR(100) NOT NULL");
    }

    if (columnExists($db, $reqTable, 'voucher_code')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN voucher_code VARCHAR(100) DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN voucher_code VARCHAR(100) DEFAULT NULL AFTER user_name");
    }

    if (columnExists($db, $reqTable, 'voucher_type')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN voucher_type VARCHAR(100) DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN voucher_type VARCHAR(100) DEFAULT NULL AFTER voucher_code");
    }

    if (columnExists($db, $reqTable, 'reason')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN reason TEXT DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN reason TEXT DEFAULT NULL AFTER edit_type");
    }

    if (columnExists($db, $reqTable, 'shift')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN shift VARCHAR(50) DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN shift VARCHAR(50) DEFAULT NULL AFTER correct_date");
    }

    if (columnExists($db, $reqTable, 'store_user')) {
        $db->exec("ALTER TABLE `{$reqTable}` MODIFY COLUMN store_user VARCHAR(150) DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE `{$reqTable}` ADD COLUMN store_user VARCHAR(150) DEFAULT NULL AFTER user_name");
    }
}

function syncUsersSchema(PDO $db) {
    $userTable = getTableName($db, 'users');
    
    if (!tableExists($db, $userTable)) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `{$userTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) DEFAULT NULL,
                role VARCHAR(50) NOT NULL,
                name VARCHAR(150) DEFAULT NULL,
                full_name VARCHAR(150) DEFAULT NULL,
                department VARCHAR(100) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                must_change_password TINYINT(1) DEFAULT 1,
                chat_id VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_role (role),
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            error_log("Failed to create users table: " . $e->getMessage());
        }
    }

    $columnsToAdd = [
        'username' => 'VARCHAR(100) DEFAULT NULL',
        'role' => 'VARCHAR(50) NOT NULL DEFAULT "Admin"',
        'name' => 'VARCHAR(150) DEFAULT NULL',
        'full_name' => 'VARCHAR(150) DEFAULT NULL',
        'department' => 'VARCHAR(100) DEFAULT NULL',
        'email' => 'VARCHAR(150) DEFAULT NULL',
        'must_change_password' => 'TINYINT(1) DEFAULT 1',
        'chat_id' => 'VARCHAR(50) DEFAULT NULL',
        'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];

    foreach ($columnsToAdd as $col => $def) {
        try {
            if (!columnExists($db, $userTable, $col)) {
                $db->exec("ALTER TABLE `{$userTable}` ADD COLUMN `{$col}` {$def}");
            }
        } catch (Exception $e) {
            error_log("Failed to add column {$col} to {$userTable}: " . $e->getMessage());
        }
    }

    $defaultPassHash = password_hash('admin123', PASSWORD_DEFAULT);
    $defaultAccounts = [
        ['Admin', 'admin', "Kaldi's ICT Admin", 'ICT', '814523272'],
        ['Finance', 'finance_trainer', 'Finance Department Manager', 'Finance', '-1003609053132'],
        ['F&B', 'fb_mgr', 'F&B Department Manager', 'F&B', '-1003821366530'],
        ['IT', 'it_trainer', 'IT Department Trainer', 'IT', '814523272'],
        ['Coordinator', 'coordinator', 'Training Coordinator Team', 'HR / Training', '']
    ];

    foreach ($defaultAccounts as $acc) {
        [$rRole, $rUser, $rName, $rDept, $rChat] = $acc;
        try {
            $hasUsername = columnExists($db, $userTable, 'username');
            $hasRole = columnExists($db, $userTable, 'role');
            
            $existing = null;
            if ($hasUsername && $hasRole) {
                $stmt = $db->prepare("SELECT * FROM `{$userTable}` WHERE role = ? OR username = ? LIMIT 1");
                $stmt->execute([$rRole, $rUser]);
                $existing = $stmt->fetch();
            } elseif ($hasRole) {
                $stmt = $db->prepare("SELECT * FROM `{$userTable}` WHERE role = ? LIMIT 1");
                $stmt->execute([$rRole]);
                $existing = $stmt->fetch();
            }

            if (!$existing) {
                $cols = ['role', 'password'];
                $vals = [$rRole, $defaultPassHash];
                $placeholders = ['?', '?'];
                
                if (columnExists($db, $userTable, 'username')) { $cols[] = 'username'; $vals[] = $rUser; $placeholders[] = '?'; }
                if (columnExists($db, $userTable, 'name')) { $cols[] = 'name'; $vals[] = $rName; $placeholders[] = '?'; }
                if (columnExists($db, $userTable, 'full_name')) { $cols[] = 'full_name'; $vals[] = $rName; $placeholders[] = '?'; }
                if (columnExists($db, $userTable, 'department')) { $cols[] = 'department'; $vals[] = $rDept; $placeholders[] = '?'; }
                if (columnExists($db, $userTable, 'must_change_password')) { $cols[] = 'must_change_password'; $vals[] = 1; $placeholders[] = '?'; }
                if (columnExists($db, $userTable, 'chat_id')) { $cols[] = 'chat_id'; $vals[] = $rChat; $placeholders[] = '?'; }

                $sql = "INSERT INTO `{$userTable}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $db->prepare($sql)->execute($vals);
            } else {
                $updates = [];
                $uParams = [];
                if (columnExists($db, $userTable, 'username') && empty($existing['username'])) {
                    $updates[] = "username = ?"; $uParams[] = $rUser;
                }
                if (columnExists($db, $userTable, 'name') && empty($existing['name'])) {
                    $updates[] = "name = ?"; $uParams[] = $rName;
                }
                if (columnExists($db, $userTable, 'full_name') && empty($existing['full_name'])) {
                    $updates[] = "full_name = ?"; $uParams[] = $rName;
                }
                if (columnExists($db, $userTable, 'department') && empty($existing['department'])) {
                    $updates[] = "department = ?"; $uParams[] = $rDept;
                }
                if (!empty($updates)) {
                    $uParams[] = $existing['id'];
                    $db->prepare("UPDATE `{$userTable}` SET " . implode(', ', $updates) . " WHERE id = ?")->execute($uParams);
                }
            }
        } catch (Exception $e) {
            error_log("Error syncing user account {$rRole}: " . $e->getMessage());
        }
    }
}

// ============================================
// Helper to Get Department Telegram Chat ID
// ============================================
function getDeptChatId(PDO $pdo, $deptName) {
    $deptTable = getTableName($pdo, 'departments');
    if (tableExists($pdo, $deptTable)) {
        $stmt = $pdo->prepare("SELECT telegram_chat_id FROM `{$deptTable}` WHERE dept_name = ? OR dept_name LIKE ? LIMIT 1");
        $stmt->execute([$deptName, $deptName . '%']);
        $row = $stmt->fetch();
        if ($row && !empty($row['telegram_chat_id'])) {
            return $row['telegram_chat_id'];
        }
    }
    
    if ($deptName === 'IT') {
        return '-1001502645326';
    }
    
    if ($deptName === 'Admin') {
        $userTable = getTableName($pdo, 'users');
        if (tableExists($pdo, $userTable)) {
            $uStmt = $pdo->prepare("SELECT chat_id FROM `{$userTable}` WHERE role = 'Admin' AND chat_id != '' AND chat_id IS NOT NULL LIMIT 1");
            $uStmt->execute();
            $uRow = $uStmt->fetch();
            if ($uRow && !empty($uRow['chat_id'])) {
                return $uRow['chat_id'];
            }
        }
    }
    
    return TELEGRAM_CHAT_ID_ADMIN;
}

// ============================================
// JSON Response Helper
// ============================================
function jsonResponse($data, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }
    
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================
// Generate Department ID (e.g., FIN-20241215-001)
// ============================================
function generateDepartmentId($targetDept, $actionType = '') {
    $pdo = db();
    $reqTable = getTableName($pdo, 'requests');
    
    // Department prefix mapping
    $prefixMap = [
        "Finance" => "FIN",
        "F&B" => "FB",
        "Admin" => "ADM",
        "IT" => "IT",
        "HR" => "HR",
        "Logistics" => "LOG",
        "Marketing" => "MKT",
        "Sales" => "SAL",
        "Operations" => "OPS"
    ];
    
    if ($actionType === 'On Hand Delete') {
        $prefix = 'OHD';
    } else {
        $cleanDept = is_string($targetDept) ? trim($targetDept) : '';
        $prefix = isset($prefixMap[$cleanDept]) ? $prefixMap[$cleanDept] : strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cleanDept), 0, 3));
        if ($prefix === '') {
            $prefix = 'REQ';
        }
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }
    }
    
    $date = date('Ymd');
    
    $stmt = $pdo->prepare("SELECT request_id FROM `{$reqTable}` WHERE request_id LIKE ? ORDER BY id DESC LIMIT 1");
    $pattern = $prefix . '-' . $date . '-%';
    $stmt->execute([$pattern]);
    $last = $stmt->fetch();
    
    if ($last && preg_match('/' . $prefix . '-' . $date . '-(\d+)/', $last['request_id'], $matches)) {
        $num = (int)$matches[1] + 1;
    } else {
        $num = 1;
    }
    
    return $prefix . '-' . $date . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ============================================
// Send Telegram Notification
// ============================================
function telegramHtmlEscape($text) {
    if ($text === null || $text === '') {
        return '';
    }
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string)$text);
}

function sendTelegram($chatId, $text, $parseMode = 'HTML') {
    if (!TELEGRAM_BOT_TOKEN || empty($chatId)) {
        error_log("Telegram: Missing bot token or chat ID");
        return false;
    }
    
    try {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
            'disable_notification' => false
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Telegram API error: HTTP $httpCode - Response: $response");
            // If HTML entity parsing failed, retry as plain text without parse_mode
            if ($httpCode === 400 && strpos($response, "can't parse entities") !== false && $parseMode !== null) {
                error_log("Telegram entity parse error. Retrying notification as plain text...");
                $fallbackPayload = [
                    'chat_id' => $chatId,
                    'text' => strip_tags($text),
                    'disable_web_page_preview' => true,
                    'disable_notification' => false
                ];
                $chFallback = curl_init($url);
                curl_setopt($chFallback, CURLOPT_POST, true);
                curl_setopt($chFallback, CURLOPT_POSTFIELDS, json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE));
                curl_setopt($chFallback, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($chFallback, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chFallback, CURLOPT_TIMEOUT, 10);
                curl_setopt($chFallback, CURLOPT_SSL_VERIFYPEER, false);
                $fallbackResponse = curl_exec($chFallback);
                $fallbackHttpCode = curl_getinfo($chFallback, CURLINFO_HTTP_CODE);
                curl_close($chFallback);
                if ($fallbackHttpCode === 200) {
                    $decodedFallback = json_decode($fallbackResponse, true);
                    return isset($decodedFallback['ok']) && $decodedFallback['ok'] === true;
                }
            }
            return false;
        }
        
        $decoded = json_decode($response, true);
        return isset($decoded['ok']) && $decoded['ok'] === true;
        
    } catch (Exception $e) {
        error_log("Telegram exception: " . $e->getMessage());
        return false;
    }
}

function sendTelegramDocument($chatId, $filePath, $caption = '') {
    if (!TELEGRAM_BOT_TOKEN || empty($chatId)) {
        error_log("Telegram Document: Missing bot token or chat ID");
        return false;
    }
    
    try {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendDocument";
        $cFile = new CURLFile($filePath);
        $payload = [
            'chat_id' => $chatId,
            'document' => $cFile,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Telegram sendDocument error: HTTP $httpCode - Response: $response");
            return false;
        }
        
        $decoded = json_decode($response, true);
        return isset($decoded['ok']) && $decoded['ok'] === true;
        
    } catch (Exception $e) {
        error_log("Telegram sendDocument exception: " . $e->getMessage());
        return false;
    }
}

// ============================================
// Log System Action
// ============================================
function logSystemAction($action, $actor, $requestId = null) {
    try {
        $pdo = db();
        $logsTable = getTableName($pdo, 'system_logs');
        
        if (!tableExists($pdo, $logsTable)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$logsTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                action VARCHAR(100) NOT NULL,
                actor VARCHAR(100) NOT NULL,
                request_id VARCHAR(50) DEFAULT NULL,
                INDEX idx_timestamp (timestamp),
                INDEX idx_action (action)
            )");
        }
        
        $stmt = $pdo->prepare("INSERT INTO `{$logsTable}` (timestamp, action, actor, request_id) VALUES (NOW(), ?, ?, ?)");
        $stmt->execute([$action, $actor, $requestId]);
        
    } catch (Exception $e) {
        error_log("Failed to log system action: " . $e->getMessage());
    }
}

// ============================================
// Send Detailed Notification
// ============================================
function sendDetailedNotificationHtml($rowData, $status, $actor = "System") {
    if (empty($rowData)) {
        return false;
    }

    $pdo = db();
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . $host . '/edit_request/index.php';

    $actionTypeRaw = trim((string)($rowData['action_type'] ?? ''));
    $targetDeptRaw = trim((string)($rowData['target_dept'] ?? ''));
    $isOnHandDelete = ($actionTypeRaw === 'On Hand Delete');

    $targetDeptItems = array_values(array_filter(array_map('trim', explode(',', $targetDeptRaw))));
    $targetDeptDisplay = '';

    if ($isOnHandDelete && !empty($targetDeptItems)) {
        $targetDeptDisplay = implode("\n• ", array_map(function ($item) {
            return telegramHtmlEscape($item);
        }, $targetDeptItems));
    } else {
        $targetDeptDisplay = telegramHtmlEscape($targetDeptRaw);
    }

    // Determine target Chat ID based on workflow specifications
    if ($isOnHandDelete) {
        if ($status === 'NEW_REQUEST') {
            // Step 1: User submits -> Send to F&B Group
            $targetChatId = getDeptChatId($pdo, 'F&B');
        } elseif ($status === 'Approved') {
            // Step 2: F&B approves -> Send to IT Group
            $targetChatId = getDeptChatId($pdo, 'IT');
        } elseif ($status === 'Done') {
            // Step 3: IT marks Done -> Send back to specified Group
            $targetChatId = '-1002426639974';
        } elseif ($status === 'Rejected') {
            // Rejected -> Send notification to specified Group
            $targetChatId = '-1002426639974';
        } else {
            $targetChatId = getDeptChatId($pdo, 'F&B');
        }
    } else {
        // Normal Edit / Void Request
        if ($status === 'NEW_REQUEST') {
            // New request -> Send ONLY to approving department (Finance / F&B). Never to IT!
            $cleanTargetDept = trim(explode('(', $targetDeptRaw)[0]);
            $cleanTargetDept = trim(explode(',', $cleanTargetDept)[0]);
            $targetChatId = getDeptChatId($pdo, $cleanTargetDept ?: 'Finance');
        } elseif ($status === 'Approved') {
            // Approved by Finance or F&B -> Send to IT Department for execution!
            $targetChatId = getDeptChatId($pdo, 'IT');
        } else {
            // Done / Rejected -> Send back to approving department
            $cleanTargetDept = trim(explode('(', $targetDeptRaw)[0]);
            $cleanTargetDept = trim(explode(',', $cleanTargetDept)[0]);
            $targetChatId = getDeptChatId($pdo, $cleanTargetDept ?: 'Finance');
        }
    }

    // Construct Header based on Action Type & Workflow Status
    if ($isOnHandDelete) {
        switch ($status) {
            case "NEW_REQUEST":
                $msgHeader = "<b>NEW ON HAND DELETE REQUEST</b>\nTarget: F&B Group";
                break;
            case "Approved":
                $msgHeader = "<b>ON HAND DELETE REQUEST APPROVED</b>\nApproved By: " . telegramHtmlEscape($actor) . "\n➡️ Sent to IT Group for Action";
                break;
            case "Rejected":
                $msgHeader = "<b>ON HAND DELETE REQUEST REJECTED</b>\nBy: " . telegramHtmlEscape($actor);
                break;
            case "Done":
                $msgHeader = "<b>ON HAND DELETE REQUEST COMPLETED ✅</b>\nProcessed By IT: " . telegramHtmlEscape($actor) . "\n➡️ Notification sent back to F&B Group";
                break;
            default:
                $msgHeader = "<b>ON HAND DELETE REQUEST UPDATE</b>\nStatus: " . telegramHtmlEscape($status);
        }
    } else {
        switch ($status) {
            case "NEW_REQUEST":
                $msgHeader = "<b>NEW EDIT/VOID REQUEST</b>";
                break;
            case "Approved":
                $msgHeader = "<b>REQUEST APPROVED</b>\nBy: " . telegramHtmlEscape($actor);
                break;
            case "Rejected":
                $msgHeader = "<b>REQUEST REJECTED</b>\nBy: " . telegramHtmlEscape($actor);
                break;
            case "Done":
                $msgHeader = "<b>REQUEST COMPLETED</b>\nProcessed By: " . telegramHtmlEscape($actor);
                break;
            default:
                $msgHeader = "<b>REQUEST UPDATE</b>\nStatus: " . telegramHtmlEscape($status);
        }
    }

    $requestId = telegramHtmlEscape($rowData['request_id'] ?? '');
    $timestamp = date('Y-m-d H:i', strtotime($rowData['timestamp'] ?? 'now'));
    $branch = telegramHtmlEscape($rowData['branch'] ?? '');
    $userName = telegramHtmlEscape($rowData['user_name'] ?? '');
    $storeUser = telegramHtmlEscape($rowData['store_user'] ?? '');
    $voucherCode = telegramHtmlEscape($rowData['voucher_code'] ?? '');
    $voucherType = telegramHtmlEscape($rowData['voucher_type'] ?? '');
    $actionType = telegramHtmlEscape($actionTypeRaw);
    $editType = telegramHtmlEscape($rowData['edit_type'] ?? '');
    $incorrectDate = telegramHtmlEscape($rowData['incorrect_date'] ?? '');
    $correctDate = telegramHtmlEscape($rowData['correct_date'] ?? '');
    $reason = telegramHtmlEscape($rowData['reason'] ?? '');
    $statusText = telegramHtmlEscape($rowData['status'] ?? '');
    $shift = telegramHtmlEscape($rowData['shift'] ?? '');

    $msg = $msgHeader . "\n\n" .
        "<b>ID:</b> <code>" . $requestId . "</code>\n" .
        "<b>Date:</b> " . $timestamp . "\n" .
        "<b>Branch:</b> " . $branch . "\n" .
        "<b>User:</b> " . $userName . "\n";

    if ($isOnHandDelete) {
        if (!empty($storeUser)) {
            $msg .= "<b>Store User:</b> " . $storeUser . "\n";
        }
        $msg .= "<b>Shift:</b> " . ($shift ?: 'shft 1') . "\n";
        $msg .= "<b>On Hand Delete Date:</b> " . ($correctDate ?: '-') . "\n";
    } else {
        $msg .= "<b>Voucher:</b> <code>" . $voucherCode . "</code>\n" .
            "<b>Type:</b> " . $voucherType . "\n";
    }

    $msg .= "<b>Action:</b> " . $actionType . "\n";

    if (!$isOnHandDelete && !empty($editType) && $editType !== 'null') {
        $msg .= "<b>Edit Type:</b> " . $editType . "\n";
    }

    if (!$isOnHandDelete && (!empty($incorrectDate) || !empty($correctDate))) {
        $msg .= "<b>Date Change:</b> " . ($incorrectDate ?: '-') . " -> " . ($correctDate ?: '-') . "\n";
    }

    if ($isOnHandDelete && !empty($targetDeptDisplay)) {
        $msg .= "<b>Department Stores:</b>\n• " . $targetDeptDisplay . "\n";
    } else {
        $msg .= "<b>Department:</b> " . $targetDeptDisplay . "\n";
    }

    $msg .= "<b>Reason:</b> " . $reason . "\n" .
        "<b>Status:</b> " . $statusText . "\n\n" .
        "<a href=\"" . htmlspecialchars($baseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">Open Dashboard</a>";

    return sendTelegram($targetChatId, $msg, 'HTML');
}

function sendDetailedNotification($rowData, $status, $actor = "System") {
    return sendDetailedNotificationHtml($rowData, $status, $actor);
}
?>
