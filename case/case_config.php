<?php
// ============================================
// Kaldis CNET Case Management — Configuration
// PHP 5.4+ Compatible
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', '0');

define('CASE_DB_HOST', 'localhost');
define('CASE_DB_NAME', 'kaldisbp_ict');
define('CASE_DB_USER', 'kaldisbp_it');
define('CASE_DB_PASS', '@IT2026!');

date_default_timezone_set('Africa/Addis_Ababa');

// Set UTF-8 for MySQL connection
define('CASE_DB_CHARSET', 'utf8mb4');

// -------------------------------------------------------------------
// Database Connection
// -------------------------------------------------------------------
function caseDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . CASE_DB_HOST . ';dbname=' . CASE_DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, CASE_DB_USER, CASE_DB_PASS, array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ));
        } catch (PDOException $e) {
            error_log("[CaseDB] Connection failed: " . $e->getMessage());
            caseJsonResponse(array('error' => 'Database connection failed'), 500);
        }
    }
    return $pdo;
}

// -------------------------------------------------------------------
// Database Initialization
// -------------------------------------------------------------------
function caseInitDatabase() {
    try {
        $db = caseDB();

        $db->exec("CREATE TABLE IF NOT EXISTS case_cases (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(50) NOT NULL UNIQUE,
            case_type VARCHAR(100) NOT NULL DEFAULT 'Support',
            description TEXT NOT NULL,
            created_date DATE NOT NULL,
            due_date DATE DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Not Started',
            priority VARCHAR(50) NOT NULL DEFAULT 'Medium',
            assigned_to VARCHAR(100) DEFAULT NULL,
            created_by VARCHAR(100) NOT NULL,
            remarks TEXT DEFAULT NULL,
            attachments TEXT DEFAULT NULL,
            last_modified DATETIME DEFAULT NULL,
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_due_date (due_date),
            INDEX idx_created_date (created_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add missing columns if they don't exist
        $checkCols = array(
            'remarks' => 'TEXT DEFAULT NULL',
            'attachments' => 'TEXT DEFAULT NULL',
            'last_modified' => 'DATETIME DEFAULT NULL'
        );
        foreach ($checkCols as $col => $def) {
            $check = $db->query("SHOW COLUMNS FROM case_cases LIKE '" . $col . "'")->fetch();
            if (!$check) {
                $db->exec("ALTER TABLE case_cases ADD COLUMN " . $col . " " . $def);
            }
        }

        $db->exec("CREATE TABLE IF NOT EXISTS case_comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(50) NOT NULL,
            author VARCHAR(100) NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_case_id (case_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS case_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(50) NOT NULL,
            action VARCHAR(200) NOT NULL,
            details TEXT DEFAULT NULL,
            user VARCHAR(100) NOT NULL,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_case_id (case_id),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS case_assignees (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            telegram_id VARCHAR(100) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS case_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'kaldis',
            telegram_id VARCHAR(100) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            last_login DATETIME DEFAULT NULL,
            INDEX idx_username (username),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS case_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS case_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ============================================
        // PASSWORD RESET TABLE
        // ============================================
        $db->exec("CREATE TABLE IF NOT EXISTS case_password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            otp VARCHAR(10) NOT NULL,
            token VARCHAR(255) DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ============================================
        // ADD EMAIL AND SECURITY FIELDS TO case_users
        // ============================================
        $checkEmail = $db->query("SHOW COLUMNS FROM case_users LIKE 'email'")->fetch();
        if (!$checkEmail) {
            $db->exec("ALTER TABLE case_users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
        }

        $checkSecurityQuestion = $db->query("SHOW COLUMNS FROM case_users LIKE 'security_question'")->fetch();
        if (!$checkSecurityQuestion) {
            $db->exec("ALTER TABLE case_users ADD COLUMN security_question TEXT DEFAULT NULL");
        }

        $checkSecurityAnswer = $db->query("SHOW COLUMNS FROM case_users LIKE 'security_answer'")->fetch();
        if (!$checkSecurityAnswer) {
            $db->exec("ALTER TABLE case_users ADD COLUMN security_answer VARCHAR(255) DEFAULT NULL");
        }

        // Insert default case types if table is empty
        $typeCount = $db->query("SELECT COUNT(*) FROM case_types")->fetchColumn();
        if ($typeCount == 0) {
            $defaultTypes = array('Support', 'Project', 'Maintenance', 'Ongoing Project', 'New Project');
            $stmt = $db->prepare("INSERT INTO case_types (name) VALUES (?)");
            foreach ($defaultTypes as $t) {
                $stmt->execute(array($t));
            }
        }

        // Create default admin user if no users exist
        $userCount = $db->query("SELECT COUNT(*) FROM case_users")->fetchColumn();
        if ($userCount == 0) {
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO case_users (username, password, role, status, email) VALUES (?, ?, ?, ?, ?)")
                ->execute(array('admin', $defaultPassword, 'admin', 'active', 'admin@kaldisbunna.et'));
        }

    } catch (Exception $e) {
        error_log("[CaseInit] " . $e->getMessage());
    }
}

// -------------------------------------------------------------------
// Post Body Helper (Fixed for empty body)
// -------------------------------------------------------------------
function casePostBody() {
    $raw = file_get_contents('php://input');
    
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    
    // Return $_POST as fallback
    if (!empty($_POST)) {
        return $_POST;
    }
    
    return array();
}

// -------------------------------------------------------------------
// JSON Response Helper
// -------------------------------------------------------------------
function caseJsonResponse($d, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    if (ob_get_level()) ob_end_clean();
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// -------------------------------------------------------------------
// Authentication Helpers
// -------------------------------------------------------------------
function caseRequireAuth($roles = array()) {
    if (!isset($_SESSION['case_user']) || empty($_SESSION['case_user'])) {
        caseJsonResponse(array('error' => 'Unauthorized. Please login again.'), 401);
    }
    if (!empty($roles) && !in_array($_SESSION['case_user']['role'], $roles)) {
        caseJsonResponse(array('error' => 'Permission denied.'), 403);
    }
}

function caseRequireAdmin()  { caseRequireAuth(array('admin')); }
function caseRequireKaldis() { caseRequireAuth(array('admin', 'kaldis')); }
function caseRequireCnet()   { caseRequireAuth(array('admin', 'cnet')); }

// -------------------------------------------------------------------
// Settings Helpers
// -------------------------------------------------------------------
function caseGetSetting($key) {
    try {
        $db = caseDB();
        $s = $db->prepare('SELECT setting_value FROM case_settings WHERE setting_key = ?');
        $s->execute(array($key));
        $r = $s->fetch();
        return $r ? (string) $r['setting_value'] : '';
    } catch (Exception $e) {
        return '';
    }
}

function caseUpdateSettings($pairs) {
    try {
        $db = caseDB();
        $s = $db->prepare('INSERT INTO case_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
        foreach ($pairs as $k => $v) {
            $s->execute(array($k, $v, $v));
        }
    } catch (Exception $e) {
        // silent
    }
}

// -------------------------------------------------------------------
// URL Helper
// -------------------------------------------------------------------
function caseBaseUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base   = $scheme . '://' . $host;
    if ($dir !== '/' && $dir !== '') {
        $base .= $dir;
    }
    return $base;
}

// -------------------------------------------------------------------
// Fetch Single Case by ID
// -------------------------------------------------------------------
function caseGetById($db, $caseId) {
    $stmt = $db->prepare("SELECT * FROM case_cases WHERE case_id = ?");
    $stmt->execute(array($caseId));
    $row = $stmt->fetch();
    return $row ? $row : null;
}

// -------------------------------------------------------------------
// Activity Logging for Cases
// -------------------------------------------------------------------
function logCaseActivity($caseId, $action, $details) {
    try {
        $db       = caseDB();
        $username = isset($_SESSION['case_user']['username']) ? $_SESSION['case_user']['username'] : 'System';
        $stmt     = $db->prepare("INSERT INTO case_history (case_id, action, details, user, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(array($caseId, $action, $details, $username));
    } catch (Exception $e) {
        error_log("[LogCaseActivity] " . $e->getMessage());
    }
}

// -------------------------------------------------------------------
// Telegram: Get Settings
// -------------------------------------------------------------------
function caseTelegramSettings() {
    try {
        $db     = caseDB();
        $token  = $db->query("SELECT setting_value FROM case_settings WHERE setting_key = 'telegramBotToken'")->fetchColumn();
        $chatId = $db->query("SELECT setting_value FROM case_settings WHERE setting_key = 'telegramGroupChatId'")->fetchColumn();
        return array('token' => ($token ? $token : null), 'chatId' => ($chatId ? $chatId : null));
    } catch (Exception $e) {
        return array('token' => null, 'chatId' => null);
    }
}

// -------------------------------------------------------------------
// Telegram: Send Direct Message to group
// -------------------------------------------------------------------
function caseTelegramSend($message, $parseMode = 'HTML') {
    try {
        $tg = caseTelegramSettings();
        if (!$tg['token'] || !$tg['chatId']) return false;

        $ch = curl_init("https://api.telegram.org/bot" . $tg['token'] . "/sendMessage");
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array(
                'chat_id' => $tg['chatId'], 
                'text' => $message, 
                'parse_mode' => $parseMode, 
                'disable_web_page_preview' => true
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5
        ));
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code !== 200) {
            error_log("[TelegramSend] HTTP " . $code . " - " . $response);
        }
        
        return $code === 200;
    } catch (Exception $e) {
        error_log("[TelegramSend] " . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------------------
// Telegram: Notify Individual Assignee by name
// -------------------------------------------------------------------
function caseNotifyAssignee($assigneeName, $caseId, $message) {
    try {
        $db   = caseDB();
        $stmt = $db->prepare("SELECT telegram_id FROM case_assignees WHERE name = ? AND status = 'active' AND telegram_id IS NOT NULL AND telegram_id != ''");
        $stmt->execute(array($assigneeName));
        $row = $stmt->fetch();
        if (!$row) return;

        $tg = caseTelegramSettings();
        if (!$tg['token']) return;

        $chatId = $row['telegram_id'];

        $ch = curl_init("https://api.telegram.org/bot" . $tg['token'] . "/sendMessage");
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array(
                'chat_id' => $chatId, 
                'text' => $message, 
                'parse_mode' => 'HTML', 
                'disable_web_page_preview' => true
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5
        ));
        curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        error_log("[NotifyAssignee] " . $e->getMessage());
    }
}

// -------------------------------------------------------------------
// Telegram: Case Status Notification (to group)
// -------------------------------------------------------------------
function caseSendTelegramNotification($caseId, $status, $assignedTo, $remarks = '', $oldStatus = '') {
    try {
        $tg = caseTelegramSettings();
        if (!$tg['token'] || !$tg['chatId']) return false;

        $db   = caseDB();
        $stmt = $db->prepare("SELECT priority, due_date FROM case_cases WHERE case_id = ?");
        $stmt->execute(array($caseId));
        $case = $stmt->fetch();

        $pEmoji = array('Critical' => '🔴', 'Medium' => '🟡', 'Low' => '🟢');
        $sEmoji = array('Not Started' => '⏳', 'In Progress' => '🔄', 'Fixed' => '✅', 'Return' => '↩️', 'Reject' => '❌', 'Completed' => '🎉');
        $p = ($case && isset($pEmoji[$case['priority']])) ? $pEmoji[$case['priority']] : '⚪';
        $s = isset($sEmoji[$status]) ? $sEmoji[$status] : '📋';

        $username = isset($_SESSION['case_user']['username']) ? $_SESSION['case_user']['username'] : 'System';

        $message = $p . " " . $s . " <b>Case Update: " . $caseId . "</b>\n\n";
        if ($oldStatus && $oldStatus !== $status) {
            $message .= "📌 Old: " . $oldStatus . "\n";
            $message .= "🎯 New: <b>" . $status . "</b>\n";
        } else {
            $message .= "📋 Status: <b>" . $status . "</b>\n";
        }
        $message .= "👤 Assigned: " . ($assignedTo ? $assignedTo : 'Unassigned') . "\n";
        $message .= "👨‍💻 By: " . $username . "\n";
        
        if ($case && !empty($case['due_date'])) {
            $today = date('Y-m-d');
            $diff = (strtotime($case['due_date']) - strtotime($today)) / 86400;
            if ($diff < 0) {
                $message .= "⚠️ OVERDUE by " . abs((int)$diff) . " days\n";
            }
        }
        
        if (!empty($remarks)) {
            $message .= "\n💬 <b>Remarks:</b>\n" . mb_substr($remarks, 0, 300);
        }

        return caseTelegramSend($message);
    } catch (Exception $e) {
        error_log("[TelegramNotify] " . $e->getMessage());
        return false;
    }
}