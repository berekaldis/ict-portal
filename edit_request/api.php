<?php
require_once __DIR__ . '/config.php';
session_start();

$action = $_GET['action'] ?? '';
if ($action === 'noop') {
    return;
}

function getRequestData() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) return $parsed;
    }
    if (!empty($GLOBALS['mockPostData']) && is_array($GLOBALS['mockPostData'])) {
        return $GLOBALS['mockPostData'];
    }
    if (!empty($GLOBALS['mock_input'])) {
        $parsed = json_decode($GLOBALS['mock_input'], true);
        if (is_array($parsed)) return $parsed;
    }
    return !empty($_POST) ? $_POST : [];
}

// ==================== AUTHENTICATION HELPERS ====================
function buildDeptUserSession($cand, $targetRole = null, $isDefaultPassword = false) {
    $effectiveRole = $cand['role'] ?? 'Admin';
    $effectiveDept = $cand['department'] ?? 'ICT';

    // Prioritize explicitly requested department role
    if ($targetRole === 'Finance') {
        $effectiveRole = 'Finance';
        $effectiveDept = 'Finance';
    } elseif ($targetRole === 'F&B') {
        $effectiveRole = 'F&B';
        $effectiveDept = 'F&B';
    } elseif ($targetRole === 'IT') {
        $effectiveRole = 'IT';
        $effectiveDept = 'IT';
    } elseif ($targetRole === 'Coordinator') {
        $effectiveRole = 'Coordinator';
        $effectiveDept = 'HR / Training';
    } elseif ($targetRole === 'Admin') {
        $effectiveRole = 'Admin';
        $effectiveDept = 'ICT';
    } else {
        if ($effectiveRole === 'IT' || ($cand['username'] ?? '') === 'it_trainer' || $effectiveDept === 'IT') {
            $effectiveRole = 'IT';
            $effectiveDept = 'IT';
        } elseif ($effectiveRole === 'Finance' || ($cand['username'] ?? '') === 'finance_trainer' || $effectiveDept === 'Finance') {
            $effectiveRole = 'Finance';
            $effectiveDept = 'Finance';
        } elseif ($effectiveRole === 'F&B' || ($cand['username'] ?? '') === 'fb_mgr' || $effectiveDept === 'F&B') {
            $effectiveRole = 'F&B';
            $effectiveDept = 'F&B';
        } elseif ($effectiveRole === 'Coordinator' || ($cand['username'] ?? '') === 'coordinator' || $effectiveDept === 'HR / Training') {
            $effectiveRole = 'Coordinator';
            $effectiveDept = 'HR / Training';
        } else {
            $effectiveRole = 'Admin';
            $effectiveDept = 'ICT';
        }
    }

    $displayName = !empty($cand['name']) ? $cand['name'] : (!empty($cand['full_name']) ? $cand['full_name'] : ($cand['username'] ?? ($cand['role'] ?? 'Department User')));
    $mustChange = (bool)$isDefaultPassword;

    $_SESSION['user'] = [
        'id' => $cand['id'] ?? null,
        'role' => $effectiveRole,
        'name' => $displayName,
        'username' => $cand['username'] ?? strtolower($effectiveRole),
        'email' => $cand['email'] ?? '',
        'department' => $effectiveDept,
        'must_change_password' => 0
    ];

    return [
        'success' => true,
        'id' => $cand['id'] ?? null,
        'role' => $effectiveRole,
        'name' => $displayName,
        'username' => $cand['username'] ?? strtolower($effectiveRole),
        'email' => $cand['email'] ?? '',
        'department' => $effectiveDept,
        'requirePasswordChange' => false
    ];
}

function authenticateDeptUser(PDO $pdo, $identifier, $password) {
    $userTable = getTableName($pdo, 'users');
    $cleanId = trim((string)$identifier);
    $cleanPass = trim((string)$password);
    
    if ($cleanPass === '' || $cleanId === '') {
        return ['success' => false, 'message' => 'Username/Role and password required'];
    }

    $lowerId = strtolower($cleanId);
    $targetRole = 'Admin';
    if (strpos($lowerId, 'fin') !== false) {
        $targetRole = 'Finance';
    } elseif (strpos($lowerId, 'f&b') !== false || strpos($lowerId, 'fb') !== false || strpos($lowerId, 'food') !== false) {
        $targetRole = 'F&B';
    } elseif ($lowerId === 'it' || strpos($lowerId, 'it_train') !== false || strpos($lowerId, 'it train') !== false) {
        $targetRole = 'IT';
    } elseif (strpos($lowerId, 'admin') !== false || strpos($lowerId, 'ict') !== false) {
        $targetRole = 'Admin';
    } elseif (strpos($lowerId, 'coord') !== false || strpos($lowerId, 'train') !== false || strpos($lowerId, 'hr') !== false) {
        $targetRole = 'Coordinator';
    }

    // Auto-sync accounts if needed
    if (function_exists('syncUsersSchema')) {
        syncUsersSchema($pdo);
    }

    $allUsers = [];
    try {
        $allUsers = $pdo->query("SELECT * FROM `{$userTable}`")->fetchAll();
    } catch (Exception $e) {
        $allUsers = [];
    }

    // Find the specific user record for the targeted role
    $targetUser = null;
    $roleLower = strtolower($targetRole);

    foreach ($allUsers as $u) {
        $uRole = strtolower($u['role'] ?? '');
        $uUser = strtolower($u['username'] ?? '');
        $uDept = strtolower($u['department'] ?? '');

        if ($roleLower === 'admin' && ($uRole === 'admin' || $uUser === 'admin')) {
            $targetUser = $u; break;
        } elseif ($roleLower === 'finance' && ($uDept === 'finance' || $uRole === 'finance' || strpos($uUser, 'fin') !== false)) {
            $targetUser = $u; break;
        } elseif (($roleLower === 'f&b' || $roleLower === 'fb') && ($uDept === 'f&b' || $uRole === 'f&b' || strpos($uUser, 'fb') !== false)) {
            $targetUser = $u; break;
        } elseif ($roleLower === 'it' && ($uDept === 'it' || $uRole === 'it' || strpos($uUser, 'it_train') !== false) && $uRole !== 'admin' && $uUser !== 'admin') {
            $targetUser = $u; break;
        } elseif ($roleLower === 'coordinator' && ($uRole === 'coordinator' || strpos($uUser, 'coord') !== false || strpos($uDept, 'train') !== false)) {
            $targetUser = $u; break;
        }
    }

    // Secondary fallback: match by exact role or username
    if (!$targetUser) {
        foreach ($allUsers as $u) {
            if (strcasecmp($u['role'] ?? '', $targetRole) === 0 || strcasecmp($u['username'] ?? '', $cleanId) === 0) {
                $targetUser = $u; break;
            }
        }
    }

    if (!$targetUser) {
        return ['success' => false, 'message' => "Account record not found for {$targetRole}"];
    }

    // Verify password strictly against this user record
    $storedHash = (string)($targetUser['password'] ?? '');
    $mustChange = ((int)($targetUser['must_change_password'] ?? 0) === 1);

    // 1. Check if the entered password matches the stored password in database
    $passMatch = false;
    if ($storedHash !== '') {
        if (password_verify($cleanPass, $storedHash)) {
            $passMatch = true;
        } elseif ($storedHash === $cleanPass) {
            $passMatch = true;
        }
    }

    // 2. Default password 'admin123' handling:
    // admin123 is ONLY allowed for first login (before password is changed).
    // Once the password is changed, storedHash contains their new custom hash,
    // so password_verify('admin123', storedHash) is false and must_change_password is 0.
    // In that case, admin123 MUST BE REJECTED!
    $isAllowedDefault = false;
    if ($cleanPass === 'admin123') {
        if ($passMatch) {
            // Stored hash actually matches admin123 (still on default password)
            $isAllowedDefault = true;
        } elseif ($storedHash === '') {
            // Account has no password set in database yet
            $isAllowedDefault = true;
            $passMatch = true;
        } elseif ($mustChange && (empty($storedHash) || password_verify('admin123', $storedHash))) {
            $isAllowedDefault = true;
            $passMatch = true;
        }
    }

    if (!$passMatch && !$isAllowedDefault) {
        return ['success' => false, 'message' => 'Invalid password for ' . $targetRole];
    }

    // Determine if this login is on default password / first login:
    // Prompt for password change ONLY if logging in with 'admin123' or must_change_password == 1
    $isFirstLogin = ($cleanPass === 'admin123' || $mustChange);

    return buildDeptUserSession($targetUser, $targetRole, $isFirstLogin);
}

// ==================== AUTHENTICATION ====================
if ($action === 'performServerLogin') {
    $data = getRequestData();
    $role = $data['role'] ?? ($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($role) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Role and password required'], 400);
    }
    
    try {
        $pdo = db();
        $res = authenticateDeptUser($pdo, $role, $password);
        if (!empty($res['success'])) {
            jsonResponse($res);
        } else {
            jsonResponse(['success' => false, 'message' => $res['message'] ?? 'Invalid credentials'], 401);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ==================== SYSTEM INITIALIZATION ====================
if ($action === 'checkStatus') {
    try {
        $pdo = db();
        syncRequestsSchema($pdo);

        $tables = ['requests', 'settings', 'branches', 'departments', 'users'];
        $allExist = true;
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmt->execute([$table]);
            if ((int)$stmt->fetchColumn() === 0) {
                $allExist = false;
                break;
            }
        }
        jsonResponse(['ready' => $allExist]);
    } catch (Exception $e) {
        jsonResponse(['ready' => false, 'error' => $e->getMessage()]);
    }
}

if ($action === 'initSystem') {
    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        $settTable = getTableName($pdo, 'settings');
        $branchTable = getTableName($pdo, 'branches');
        $deptTable = getTableName($pdo, 'departments');
        $userTable = getTableName($pdo, 'users');
        $logTable = getTableName($pdo, 'system_logs');
        
        // Create requests table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$reqTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(50) NOT NULL UNIQUE,
            timestamp DATETIME NOT NULL,
            branch VARCHAR(200) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            voucher_code VARCHAR(100) DEFAULT NULL,
            voucher_type VARCHAR(100) DEFAULT NULL,
            action_type VARCHAR(100) NOT NULL,
            edit_type VARCHAR(100) DEFAULT NULL,
            reason TEXT DEFAULT NULL,
            target_dept VARCHAR(200) NOT NULL,
            incorrect_date VARCHAR(50) DEFAULT NULL,
            correct_date VARCHAR(50) DEFAULT NULL,
            shift VARCHAR(50) DEFAULT NULL,
            status ENUM('Pending', 'Approved', 'Rejected', 'Done') DEFAULT 'Pending',
            approver VARCHAR(200) DEFAULT NULL,
            telegram_msg_id VARCHAR(100) DEFAULT NULL,
            INDEX idx_request_id (request_id),
            INDEX idx_status (status),
            INDEX idx_timestamp (timestamp)
        )");
        
        // Create settings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$settTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            INDEX idx_key (setting_key)
        )");
        
        // Create branches table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$branchTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            branch_code VARCHAR(10) NOT NULL UNIQUE,
            branch_name VARCHAR(100) NOT NULL,
            INDEX idx_branch_code (branch_code)
        )");
        
        // Create departments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$deptTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dept_name VARCHAR(100) NOT NULL UNIQUE,
            telegram_chat_id VARCHAR(50) DEFAULT NULL,
            INDEX idx_dept_name (dept_name)
        )");
        
        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$userTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            chat_id VARCHAR(50) DEFAULT NULL,
            INDEX idx_role (role)
        )");
        
        // Create logs table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$logTable}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            action VARCHAR(100) NOT NULL,
            actor VARCHAR(100) NOT NULL,
            request_id VARCHAR(50) DEFAULT NULL,
            INDEX idx_timestamp (timestamp)
        )");
        
        syncRequestsSchema($pdo);
        
        // Insert default settings
        $voucherTypes = json_encode(["Store Issue Voucher","Fresh Bazar Voucher","Item Consumption Voucher","Damage Return Voucher","Cash Good Receiving Voucher","Bank Deposit Voucher","Branch Department Transfer Voucher","Credit Good Receiving Voucher","Internal Store Transfer Voucher","Store Transfer Voucher","Cash Sales Adjustment Voucher","Motivation Voucher","Customer Complaint Voucher","Product Transfer Voucher"]);
        $editTypes = json_encode(["Date Change","Quantity and Price","Quantity Only","Price Only","Supplier Name","Incorrect Store Selection","Incorrect Item Selection","FS Number","Item Only"]);
        $storeDepts = json_encode([
            "Hot Beverage Store", "Bottled Beverage Store", "Pastry Store", "Food Store",
            "Juice Store", "Stuff Store", "Cup Store", "Ice Cream Store",
            "Coffee Roasted Store", "Milk Store", "Supply Store", "Stationery Store",
            "Cleaning Store", "Packing Material Store", "Spare Part Store", "Uniform Store",
            "Hot Kitchen", "Bottle & Can Bar", "Pastry", "Juice & Smoothie Bar",
            "Steward", "Ice Cream & Shake Bar", "Coffee Bar", "Super Market Store", "Staff Canteen"
        ]);
        
        $pdo->prepare("INSERT INTO `{$settTable}` (setting_key, setting_value) VALUES ('voucher_types', ?), ('edit_types', ?), ('store_departments', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$voucherTypes, $editTypes, $storeDepts]);
        
        // Insert default branches
        $branches = [
            ['1', 'Adama German'], ['2', 'Addisu Gebya'], ['3', 'New Airport'], ['4', 'Betel Adebabay'],
            ['5', 'Betel Bicha Foq'], ['6', 'Bishoftu'], ['7', 'Bole Medhanialem'], ['8', 'Meskel Adebabay'],
            ['9', 'Bulbula Medhanialm'], ['10', 'Bulbula 93'], ['11', 'Coffee Room'], ['12', 'ECA'],
            ['13', 'Entoto Park'], ['14', 'Grand Palace'], ['15', 'Gulele Medhanialm'], ['16', 'Gurdshola'],
            ['17', 'Imperial'], ['18', 'Jemo'], ['19', 'Kality Sheger'], ['20', 'Kera'],
            ['21', 'Lancha'], ['22', '4 kilo'], ['23', 'Main Store'], ['24', 'Megenaga'],
            ['25', 'Mexico'], ['26', 'Old Airport'], ['27', 'Production'], ['28', 'Sarbet'],
            ['29', 'Semen Hotel'], ['30', 'Betel Total'], ['31', 'Torhailoch']
        ];
        
        foreach ($branches as $branch) {
            $pdo->prepare("INSERT INTO `{$branchTable}` (branch_code, branch_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE branch_name = VALUES(branch_name)")->execute([$branch[0], $branch[1]]);
        }
        
        // Insert default departments
        $pdo->prepare("INSERT INTO `{$deptTable}` (dept_name, telegram_chat_id) VALUES ('Finance', '-1003609053132'), ('F&B', '-1003821366530'), ('IT', '-1001502645326') ON DUPLICATE KEY UPDATE telegram_chat_id = VALUES(telegram_chat_id)")->execute();
        
        // Insert default users (initial default password: admin123, must change on first login)
        $defaultAdminPassHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        if (columnExists($pdo, $userTable, 'must_change_password')) {
            $pdo->prepare("INSERT INTO `{$userTable}` (role, password, full_name, chat_id, must_change_password) VALUES 
                ('Finance', ?, 'Finance Manager', '', 1),
                ('F&B', ?, 'F&B Manager', '', 1),
                ('Admin', ?, 'IT Admin', '814523272', 1)
                ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), chat_id = VALUES(chat_id)")->execute([$defaultAdminPassHash, $defaultAdminPassHash, $defaultAdminPassHash]);
        } else {
            $pdo->prepare("INSERT INTO `{$userTable}` (role, password, full_name, chat_id) VALUES 
                ('Finance', ?, 'Finance Manager', ''),
                ('F&B', ?, 'F&B Manager', ''),
                ('Admin', ?, 'IT Admin', '814523272')
                ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), chat_id = VALUES(chat_id)")->execute([$defaultAdminPassHash, $defaultAdminPassHash, $defaultAdminPassHash]);
        }
        
        jsonResponse(['success' => true, 'message' => 'System ready']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== GET SETTINGS ====================
if ($action === 'getSettings') {
    try {
        $pdo = db();
        $settTable = getTableName($pdo, 'settings');
        $deptTable = getTableName($pdo, 'departments');
        $branchTable = getTableName($pdo, 'branches');

        $stmt = $pdo->query("SELECT setting_key, setting_value FROM `{$settTable}`");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = json_decode($row['setting_value'], true) ?: [];
        }
        
        $voucherTypes = $settings['voucher_types'] ?? ["Store Issue Voucher", "Fresh Bazar Voucher", "Item Consumption Voucher", "Damage Return Voucher", "Cash Good Receiving Voucher", "Bank Deposit Voucher", "Branch Department Transfer Voucher", "Credit Good Receiving Voucher", "Internal Store Transfer Voucher", "Store Transfer Voucher", "Cash Sales Adjustment Voucher", "Motivation Voucher", "Customer Complaint Voucher", "Product Transfer Voucher"];
        $editTypes = $settings['edit_types'] ?? ["Date Change", "Quantity and Price", "Quantity Only", "Price Only", "Supplier Name", "Incorrect Store Selection", "Incorrect Item Selection", "FS Number", "Item Only"];
        $defaultStoreDepts = [
            "Hot Beverage Store", "Bottled Beverage Store", "Pastry Store", "Food Store",
            "Juice Store", "Stuff Store", "Cup Store", "Ice Cream Store",
            "Coffee Roasted Store", "Milk Store", "Supply Store", "Stationery Store",
            "Cleaning Store", "Packing Material Store", "Spare Part Store", "Uniform Store",
            "Hot Kitchen", "Bottle & Can Bar", "Pastry", "Juice & Smoothie Bar",
            "Steward", "Ice Cream & Shake Bar", "Coffee Bar", "Super Market Store", "Staff Canteen"
        ];
        $storeDepartments = (!empty($settings['store_departments']) && is_array($settings['store_departments'])) ? $settings['store_departments'] : $defaultStoreDepts;
        
        $departments = [];
        if (tableExists($pdo, $deptTable)) {
            $deptStmt = $pdo->query("SELECT dept_name as name, telegram_chat_id as id FROM `{$deptTable}` ORDER BY dept_name");
            $departments = $deptStmt->fetchAll();
        }
        if (empty($departments)) {
            $departments = [
                ['name' => 'F&B', 'id' => '-1001502645326'],
                ['name' => 'Finance', 'id' => '-1001502645326'],
                ['name' => 'IT', 'id' => '-1001502645326'],
                ['name' => 'Admin', 'id' => '814523272']
            ];
        }
        
        $branches = [];
        if (tableExists($pdo, $branchTable)) {
            $branchStmt = $pdo->query("SELECT DISTINCT branch_name as name FROM `{$branchTable}` WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name");
            $branches = $branchStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        if (empty($branches)) {
            try {
                $cDb = companyDb();
                if ($cDb && tableExists($cDb, 'branches')) {
                    $cBranches = $cDb->query("SELECT DISTINCT name FROM branches WHERE name IS NOT NULL AND name != '' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($cBranches)) {
                        $branches = $cBranches;
                    }
                }
            } catch (Exception $e) {}
        }

        // Clean any residual ID prefixes (e.g. '01 - Bole' -> 'Bole')
        $branches = array_values(array_unique(array_filter(array_map(function($b) {
            $name = is_array($b) ? ($b['name'] ?? '') : (string)$b;
            return trim(preg_replace('/^[A-Za-z0-9]+\s*-\s*/', '', $name));
        }, $branches))));

        // Fetch Branch Managers from kaldisbp_company_system / company_system
        $branchManagers = [];
        try {
            $cDb = companyDb();
            if ($cDb) {
                $bmStmt = $cDb->query("
                    SELECT u.id, u.name, u.email, p.title as position, b.name as branch_name, b.branch_code
                    FROM users u
                    JOIN employees e ON u.employee_id = e.id
                    JOIN positions p ON e.position_id = p.id
                    JOIN branches b ON e.branch_id = b.id
                    WHERE p.title LIKE '%Branch Manager%' OR p.title LIKE '%Manager%' OR p.title LIKE '%Supervisor%' OR p.title LIKE '%Shift Leader%'
                    ORDER BY b.name, u.name
                ");
                $branchManagers = $bmStmt->fetchAll();
            }
        } catch (Exception $e) {
            error_log("Failed to load branch managers: " . $e->getMessage());
        }

        // Fetch Dept & Admin Users from local users table
        $deptUsers = [];
        $userTable = getTableName($pdo, 'users');
        if (tableExists($pdo, $userTable)) {
            $rawUsers = $pdo->query("SELECT * FROM `{$userTable}`")->fetchAll();
            foreach ($rawUsers as $ru) {
                $deptUsers[] = [
                    'id' => $ru['id'],
                    'username' => $ru['username'] ?? strtolower($ru['role'] ?? 'user'),
                    'name' => $ru['name'] ?? $ru['full_name'] ?? ($ru['role'] ?? 'User'),
                    'role' => $ru['role'] ?? 'Admin',
                    'department' => $ru['department'] ?? ($ru['role'] ?? 'ICT'),
                    'email' => $ru['email'] ?? ''
                ];
            }
        }

        // Fetch distinct requester usernames from requests table for filter datalists
        $reqTable = getTableName($pdo, 'requests');
        $usersList = [];
        if (tableExists($pdo, $reqTable)) {
            $uListStmt = $pdo->query("SELECT DISTINCT user_name FROM `{$reqTable}` WHERE user_name IS NOT NULL AND user_name != '' ORDER BY user_name");
            $usersList = $uListStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        jsonResponse([
            'voucherTypes' => $voucherTypes,
            'editTypes' => $editTypes,
            'storeDepartments' => $storeDepartments,
            'departments' => $departments,
            'branches' => $branches,
            'branchManagers' => $branchManagers,
            'deptUsers' => $deptUsers,
            'usersList' => $usersList,
            'currentUser' => $_SESSION['user'] ?? null
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// ==================== LOGIN ====================
if ($action === 'login') {
    $data = getRequestData();
    $loginType = trim($data['type'] ?? '');
    $role = trim($data['role'] ?? '');
    $username = trim($data['username'] ?? $data['email'] ?? $role);
    $password = trim($data['password'] ?? '');
    
    if (empty($password) || (empty($role) && empty($username))) {
        jsonResponse(['success' => false, 'message' => 'Username/Email and password required'], 400);
    }
    
    try {
        // 1. If explicit Branch Manager / Requester login
        if ($loginType === 'branch_manager') {
            $cDb = companyDb();
            if ($cDb) {
                $stmt = $cDb->prepare("
                    SELECT u.id as user_id, u.name as user_name, u.email, u.password,
                           e.id as emp_id, e.first_name, e.last_name, e.employee_code,
                           p.id as position_id, p.title as position_title,
                           b.id as branch_id, b.name as branch_name, b.branch_code
                    FROM users u
                    LEFT JOIN employees e ON u.employee_id = e.id
                    LEFT JOIN positions p ON e.position_id = p.id
                    LEFT JOIN branches b ON e.branch_id = b.id
                    WHERE u.email = ? OR u.name = ? OR u.name LIKE ? OR u.email LIKE ?
                    LIMIT 5
                ");
                $stmt->execute([$username, $username, "%{$username}%", "{$username}%"]);
                $candidates = $stmt->fetchAll();

                foreach ($candidates as $cand) {
                    if (password_verify($password, $cand['password'])) {
                        $_SESSION['user'] = [
                            'role' => 'Requester',
                            'name' => $cand['user_name'],
                            'email' => $cand['email'],
                            'branch' => $cand['branch_name'] ?? '',
                            'branch_code' => $cand['branch_code'] ?? '',
                            'position' => $cand['position_title'] ?? 'Branch Manager',
                            'department' => 'Branch'
                        ];
                        jsonResponse([
                            'success' => true,
                            'role' => 'Requester',
                            'name' => $cand['user_name'],
                            'email' => $cand['email'],
                            'branch' => $cand['branch_name'] ?? '',
                            'branch_code' => $cand['branch_code'] ?? '',
                            'position' => $cand['position_title'] ?? 'Branch Manager',
                            'department' => 'Branch'
                        ]);
                    }
                }
            }
        }

        // 2. Department & Admin Login against local users table (by username, email, role, or name)
        $pdo = db();
        $authDept = authenticateDeptUser($pdo, $username, $password);
        if (!empty($authDept['success'])) {
            jsonResponse($authDept);
        }

        // 3. Fallback: try companyDb() if not already matched
        $cDb = companyDb();
        if ($cDb) {
            $stmt = $cDb->prepare("
                SELECT u.id as user_id, u.name as user_name, u.email, u.password,
                       e.id as emp_id, e.first_name, e.last_name,
                       p.title as position_title,
                       b.name as branch_name, b.branch_code
                FROM users u
                LEFT JOIN employees e ON u.employee_id = e.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN branches b ON e.branch_id = b.id
                WHERE u.email = ? OR u.name = ? OR u.name LIKE ? OR u.email LIKE ?
                LIMIT 5
            ");
            $stmt->execute([$username, $username, "%{$username}%", "{$username}%"]);
            $candidates = $stmt->fetchAll();

            foreach ($candidates as $cand) {
                if (password_verify($password, $cand['password'])) {
                    $_SESSION['user'] = [
                        'role' => 'Requester',
                        'name' => $cand['user_name'],
                        'email' => $cand['email'],
                        'branch' => $cand['branch_name'] ?? '',
                        'branch_code' => $cand['branch_code'] ?? '',
                        'position' => $cand['position_title'] ?? 'Branch Manager',
                        'department' => 'Branch'
                    ];
                    jsonResponse([
                        'success' => true,
                        'role' => 'Requester',
                        'name' => $cand['user_name'],
                        'email' => $cand['email'],
                        'branch' => $cand['branch_name'] ?? '',
                        'branch_code' => $cand['branch_code'] ?? '',
                        'position' => $cand['position_title'] ?? 'Branch Manager',
                        'department' => 'Branch'
                    ]);
                }
            }
        }

        jsonResponse(['success' => false, 'message' => 'Invalid username/email or password'], 401);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ==================== LOGOUT ====================
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

// ==================== CHANGE PASSWORD / FIRST LOGIN FORCE CHANGE ====================
if ($action === 'changePassword') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
    
    $data = getRequestData();
    $currentPassword = trim($data['currentPassword'] ?? '');
    $newPassword = trim($data['newPassword'] ?? '');
    $confirmPassword = trim($data['confirmPassword'] ?? '');
    
    if (empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'New password is required'], 400);
    }
    if (strlen($newPassword) < 4) {
        jsonResponse(['success' => false, 'message' => 'New password must be at least 4 characters long'], 400);
    }
    if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => 'New password and confirmation do not match'], 400);
    }
    if ($newPassword === 'admin123') {
        jsonResponse(['success' => false, 'message' => 'Please choose a new password different from the default "admin123"'], 400);
    }
    
    try {
        $pdo = db();
        $userTable = getTableName($pdo, 'users');
        $userId = $_SESSION['user']['id'] ?? null;
        $role = $_SESSION['user']['role'] ?? '';
        $username = $_SESSION['user']['username'] ?? '';
        
        $user = null;
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM `{$userTable}` WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
        if (!$user && $username && columnExists($pdo, $userTable, 'username')) {
            $stmt = $pdo->prepare("SELECT * FROM `{$userTable}` WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }
        if (!$user && $role && columnExists($pdo, $userTable, 'role')) {
            $stmt = $pdo->prepare("SELECT * FROM `{$userTable}` WHERE role = ?");
            $stmt->execute([$role]);
            $user = $stmt->fetch();
        }

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User record not found in system'], 404);
        }
        
        // If currentPassword is provided and user has set password, verify it
        if (!empty($currentPassword) && !empty($user['password'])) {
            $currentValid = password_verify($currentPassword, $user['password']);
            // Allow admin123 ONLY if user has never changed password yet (still on first login / default password)
            if (!$currentValid && $currentPassword === 'admin123' && ((int)($user['must_change_password'] ?? 0) === 1 || password_verify('admin123', $user['password']))) {
                $currentValid = true;
            }
            if (!$currentValid) {
                jsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
            }
        }
        
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $setFields = ["password = ?"];
        $setParams = [$newHash];
        if (columnExists($pdo, $userTable, 'must_change_password')) {
            $setFields[] = "must_change_password = 0";
        }
        if (columnExists($pdo, $userTable, 'updated_at')) {
            $setFields[] = "updated_at = NOW()";
        }
        $setParams[] = $user['id'];
        $updateStmt = $pdo->prepare("UPDATE `{$userTable}` SET " . implode(', ', $setFields) . " WHERE id = ?");
        $updateStmt->execute($setParams);
        
        $_SESSION['user']['must_change_password'] = 0;
        logSystemAction("Password Changed", $_SESSION['user']['name'] ?? $user['name'] ?? $role, "User-{$user['id']}");
        
        jsonResponse(['success' => true, 'message' => 'Password updated successfully!']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DISMISS FORCE PASSWORD PROMPT (ASK ONLY ONCE) ====================
if ($action === 'dismissPasswordPrompt') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
    
    try {
        $pdo = db();
        $userTable = getTableName($pdo, 'users');
        $userId = $_SESSION['user']['id'] ?? null;
        $role = $_SESSION['user']['role'] ?? '';
        $username = $_SESSION['user']['username'] ?? '';
        
        if ($userId && columnExists($pdo, $userTable, 'must_change_password')) {
            $pdo->prepare("UPDATE `{$userTable}` SET must_change_password = 0 WHERE id = ?")->execute([$userId]);
        } elseif ($role && columnExists($pdo, $userTable, 'must_change_password')) {
            $pdo->prepare("UPDATE `{$userTable}` SET must_change_password = 0 WHERE role = ?")->execute([$role]);
        }
        
        $_SESSION['user']['must_change_password'] = 0;
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== SUBMIT REQUEST ====================
if ($action === 'submitRequest') {
    if (empty($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Authentication required. Please log in first.'], 401);
    }

    $data = getRequestData();
    $actionType = trim($data['actionType'] ?? '');
    $targetDept = trim($data['targetDept'] ?? 'F&B');
    $shift = trim($data['shift'] ?? '');
    $reason = trim($data['reason'] ?? '');
    $storeUser = ($actionType === 'On Hand Delete') ? trim($data['storeUser'] ?? $data['store_user'] ?? '') : null;

    if ($actionType === 'On Hand Delete') {
        if (empty($data['branch']) || empty($data['username']) || empty($data['correctDate'])) {
            jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }
        $storeDepts = '';
        if (!empty($data['departments']) && is_array($data['departments'])) {
            $storeDepts = implode(', ', array_values(array_filter(array_map('trim', $data['departments']))));
        }
        // If store departments selected, append to target dept or format target_dept
        $fullTargetDept = $targetDept;
        if ($storeDepts !== '') {
            $fullTargetDept = $targetDept . ' (' . $storeDepts . ')';
        }
        $targetDept = $fullTargetDept;
        
        if ($shift === '') {
            $shift = 'shft 1';
        }
        if ($reason === '') {
            $reason = 'On Hand Delete Request';
        }
    } else {
        if (empty($data['branch']) || empty($data['username']) || empty($data['voucherCode']) || empty($data['voucherType']) || $actionType === '' || $targetDept === '' || $reason === '') {
            jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }
    }
    
    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        $requestId = generateDepartmentId($targetDept, $actionType);
        $voucherCode = $actionType === 'On Hand Delete' ? null : ($data['voucherCode'] ?? null);
        $voucherType = $actionType === 'On Hand Delete' ? null : ($data['voucherType'] ?? null);
        $editType = $actionType === 'On Hand Delete' ? null : ($data['editType'] ?? null);
        $incorrectDate = $actionType === 'On Hand Delete' ? null : ($data['incorrectDate'] ?? null);
        $correctDate = $data['correctDate'] ?? null;
        $reasonValue = $reason !== '' ? $reason : null;
        
        $stmt = $pdo->prepare("INSERT INTO `{$reqTable}` 
            (request_id, timestamp, branch, user_name, store_user, voucher_code, voucher_type, action_type, edit_type, reason, target_dept, incorrect_date, correct_date, shift, status) 
            VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        
        $stmt->execute([
            $requestId,
            $data['branch'],
            $data['username'],
            $storeUser !== '' ? $storeUser : null,
            $voucherCode,
            $voucherType,
            $actionType,
            $editType,
            $reasonValue,
            $targetDept,
            $incorrectDate,
            $correctDate,
            $shift !== '' ? $shift : null
        ]);
        
        $getStmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
        $getStmt->execute([$requestId]);
        $rowData = $getStmt->fetch();
        
        sendDetailedNotification($rowData, "NEW_REQUEST");
        logSystemAction("Request Submitted", $data['username'], $requestId);
        trackRequestUsername($pdo, $data['username'], $data['branch']);
        
        jsonResponse(['success' => true, 'id' => $requestId]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== GET DASHBOARD ====================
if ($action === 'getDashboard') {
    if (empty($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    $period = $_GET['period'] ?? 'all';
    $filterType = $_GET['filterType'] ?? 'none';
    $filterValue = $_GET['filterValue'] ?? '';
    $userRole = $_GET['userRole'] ?? ($_SESSION['user']['role'] ?? 'Requester');
    $userDept = $_GET['userDept'] ?? ($_SESSION['user']['department'] ?? null);
    
    try {
        $pdo = db();
        $conditions = [];
        $params = [];
        if ($period === '7') {
            $conditions[] = "timestamp >= ?";
            $params[] = date('Y-m-d H:i:s', strtotime('-7 days'));
        } elseif ($period === '30') {
            $conditions[] = "timestamp >= ?";
            $params[] = date('Y-m-d H:i:s', strtotime('-30 days'));
        }

        if ($filterType === 'branch' && $filterValue) {
            $conditions[] = "branch = ?";
            $params[] = $filterValue;
        } elseif ($filterType === 'voucher' && $filterValue) {
            $conditions[] = "voucher_type = ?";
            $params[] = $filterValue;
        } elseif ($filterType === 'user' && $filterValue) {
            $conditions[] = "user_name = ?";
            $params[] = $filterValue;
        }

        $sessionRole = $_SESSION['user']['role'] ?? $userRole;
        if ($sessionRole === 'Requester' && !empty($_SESSION['user']['branch'])) {
            $conditions[] = "branch = ?";
            $params[] = $_SESSION['user']['branch'];
        } elseif ($sessionRole !== 'Admin' && $sessionRole !== 'Requester') {
            $conditions[] = "(target_dept = ? OR target_dept LIKE ?)";
            $params[] = $userDept ?: $sessionRole;
            $params[] = ($userDept ?: $sessionRole) . '%';
        }

        $reqTable = getTableName($pdo, 'requests');
        $sql = "SELECT * FROM `{$reqTable}`";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY timestamp DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        $metrics = [
            'totalRequests' => count($rows),
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'done' => 0,
            'overdueSlaCount' => 0,
            'peakHours' => array_fill(0, 24, 0),
            'statusData' => [],
            'deptData' => [],
            'topUsers' => [],
            'topBranches' => [],
            'topVouchers' => [],
            'topEditTypes' => [],
            'actionData' => ['Edit' => 0, 'Void' => 0, 'On Hand Delete' => 0],
            'topEditUsers' => [],
            'topVoidUsers' => [],
            'topOnHandUsers' => [],
            'topEditBranches' => [],
            'topVoidBranches' => [],
            'topOnHandBranches' => [],
            'avgResolutionTime' => '24h'
        ];
        
        $resolutionTimes = [];
        $nowTs = time();
        foreach ($rows as $row) {
            $metrics['statusData'][$row['status']] = ($metrics['statusData'][$row['status']] ?? 0) + 1;
            
            $reqTs = strtotime($row['timestamp']);
            if ($reqTs > 0) {
                $hour = (int)date('G', $reqTs);
                if ($hour >= 0 && $hour <= 23) {
                    $metrics['peakHours'][$hour]++;
                }
            }

            if ($row['status'] == 'Pending') {
                $metrics['pending']++;
                if ($reqTs > 0 && ($nowTs - $reqTs) > 86400) {
                    $metrics['overdueSlaCount']++;
                }
            } elseif ($row['status'] == 'Approved') {
                $metrics['approved']++;
            } elseif ($row['status'] == 'Rejected') {
                $metrics['rejected']++;
            } elseif ($row['status'] == 'Done') {
                $metrics['done']++;
                $resolutionTimes[] = 24;
            }
            
            if ($row['action_type'] === 'On Hand Delete') {
                $stores = array_values(array_filter(array_map('trim', explode(',', (string)$row['target_dept']))));
                foreach ($stores as $store) {
                    $metrics['deptData'][$store] = ($metrics['deptData'][$store] ?? 0) + 1;
                }
                $metrics['topOnHandUsers'][$row['user_name']] = ($metrics['topOnHandUsers'][$row['user_name']] ?? 0) + 1;
                $metrics['topOnHandBranches'][$row['branch']] = ($metrics['topOnHandBranches'][$row['branch']] ?? 0) + 1;
            } else {
                $metrics['deptData'][$row['target_dept']] = ($metrics['deptData'][$row['target_dept']] ?? 0) + 1;
            }

            $metrics['topUsers'][$row['user_name']] = ($metrics['topUsers'][$row['user_name']] ?? 0) + 1;
            $metrics['topBranches'][$row['branch']] = ($metrics['topBranches'][$row['branch']] ?? 0) + 1;
            
            $voucherType = trim((string)($row['voucher_type'] ?? ''));
            if ($voucherType !== '') {
                $metrics['topVouchers'][$voucherType] = ($metrics['topVouchers'][$voucherType] ?? 0) + 1;
            }
            $metrics['actionData'][$row['action_type']] = ($metrics['actionData'][$row['action_type']] ?? 0) + 1;
            
            if ($row['action_type'] == 'Edit') {
                $metrics['topEditUsers'][$row['user_name']] = ($metrics['topEditUsers'][$row['user_name']] ?? 0) + 1;
                $metrics['topEditBranches'][$row['branch']] = ($metrics['topEditBranches'][$row['branch']] ?? 0) + 1;
                if ($row['edit_type']) {
                    $metrics['topEditTypes'][$row['edit_type']] = ($metrics['topEditTypes'][$row['edit_type']] ?? 0) + 1;
                }
            } elseif ($row['action_type'] == 'Void') {
                $metrics['topVoidUsers'][$row['user_name']] = ($metrics['topVoidUsers'][$row['user_name']] ?? 0) + 1;
                $metrics['topVoidBranches'][$row['branch']] = ($metrics['topVoidBranches'][$row['branch']] ?? 0) + 1;
            }
        }
        
        if (count($resolutionTimes) > 0) {
            $avgHours = array_sum($resolutionTimes) / count($resolutionTimes);
            $metrics['avgResolutionTime'] = round($avgHours) . 'h';
        }
        
        $logs = [];
        $usersList = [];
        $userFrequency = [];

        if ($userRole === 'Admin') {
            $logTable = getTableName($pdo, 'system_logs');
            $userTable = getTableName($pdo, 'users');
            $usernameTable = getTableName($pdo, 'usernames');

            $logStmt = $pdo->query("SELECT * FROM `{$logTable}` ORDER BY timestamp DESC LIMIT 100");
            while ($log = $logStmt->fetch()) {
                $logs[] = [
                    'timestamp' => date('Y-m-d H:i:s', strtotime($log['timestamp'])),
                    'action' => $log['action'],
                    'actor' => $log['actor'],
                    'requestId' => $log['request_id']
                ];
            }

            $rawUsers = $pdo->query("SELECT * FROM `{$userTable}` ORDER BY id ASC")->fetchAll();
            $usersList = [];
            foreach ($rawUsers as $ru) {
                $usersList[] = [
                    'id' => $ru['id'],
                    'username' => $ru['username'] ?? strtolower($ru['role'] ?? 'user'),
                    'role' => $ru['role'] ?? 'Admin',
                    'name' => $ru['name'] ?? $ru['full_name'] ?? ($ru['role'] ?? 'User'),
                    'department' => $ru['department'] ?? ($ru['role'] ?? 'ICT'),
                    'email' => $ru['email'] ?? ''
                ];
            }

            if (tableExists($pdo, $usernameTable)) {
                $ufStmt = $pdo->query("SELECT user_name, branch, request_count, first_seen, last_seen FROM `{$usernameTable}` ORDER BY request_count DESC LIMIT 100");
                $userFrequency = $ufStmt->fetchAll();
            }
        }
        
        jsonResponse([
            'requests' => $rows,
            'metrics' => $metrics,
            'logs' => $logs,
            'usersList' => $usersList,
            'userFrequency' => $userFrequency
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// ==================== EDIT REQUEST (IT ADMIN) ====================
if ($action === 'editRequest') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized. IT Admin only.'], 401);
    }
    
    $data = getRequestData();
    $requestId = trim($data['requestId'] ?? '');
    
    if (empty($requestId)) {
        jsonResponse(['success' => false, 'message' => 'Request ID required'], 400);
    }
    
    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        
        $checkStmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
        $checkStmt->execute([$requestId]);
        $existing = $checkStmt->fetch();
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Request not found'], 404);
        }

        $actionType = $data['actionType'] ?? $existing['action_type'];
        $branch = $data['branch'] ?? $existing['branch'];
        $username = $data['username'] ?? $existing['user_name'];
        $status = $data['status'] ?? $existing['status'];
        $reason = isset($data['reason']) ? $data['reason'] : $existing['reason'];
        $targetDept = $data['targetDept'] ?? $existing['target_dept'];
        $storeUser = ($actionType === 'On Hand Delete') ? ($data['storeUser'] ?? $data['store_user'] ?? $existing['store_user']) : null;

        if ($actionType === 'On Hand Delete') {
            $voucherCode = null;
            $voucherType = null;
            $editType = null;
            $incorrectDate = null;
            $correctDate = isset($data['correctDate']) ? $data['correctDate'] : $existing['correct_date'];
            $shift = isset($data['shift']) ? $data['shift'] : $existing['shift'];
        } else {
            $voucherCode = isset($data['voucherCode']) ? $data['voucherCode'] : $existing['voucher_code'];
            $voucherType = isset($data['voucherType']) ? $data['voucherType'] : $existing['voucher_type'];
            $editType = isset($data['editType']) ? $data['editType'] : $existing['edit_type'];
            $incorrectDate = isset($data['incorrectDate']) ? $data['incorrectDate'] : $existing['incorrect_date'];
            $correctDate = isset($data['correctDate']) ? $data['correctDate'] : $existing['correct_date'];
            $shift = null;
        }

        $stmt = $pdo->prepare("UPDATE `{$reqTable}` SET 
            branch = ?, 
            user_name = ?, 
            store_user = ?,
            voucher_code = ?, 
            voucher_type = ?, 
            action_type = ?, 
            edit_type = ?, 
            reason = ?, 
            target_dept = ?, 
            incorrect_date = ?, 
            correct_date = ?, 
            shift = ?, 
            status = ? 
            WHERE request_id = ?");
        
        $stmt->execute([
            $branch,
            $username,
            $storeUser,
            $voucherCode,
            $voucherType,
            $actionType,
            $editType,
            $reason,
            $targetDept,
            $incorrectDate,
            $correctDate,
            $shift,
            $status,
            $requestId
        ]);
        
        logSystemAction("Edited Request", $_SESSION['user']['name'], $requestId);
        if (!empty($data['username'])) {
            trackRequestUsername($pdo, $data['username'], $data['branch'] ?? '');
        }
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== TEST TELEGRAM ====================
if ($action === 'testTelegram') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $data = getRequestData();
    $deptName = trim($data['deptName'] ?? 'Department');
    $chatId = trim($data['chatId'] ?? '');

    if (empty($chatId)) {
        jsonResponse(['success' => false, 'message' => 'Telegram Chat ID is required'], 400);
    }

    $msg = "<b>TEST TELEGRAM NOTIFICATION</b>\n\n" .
        "<b>Department:</b> " . telegramHtmlEscape($deptName) . "\n" .
        "<b>Status:</b> Connection Test Successful ✅\n" .
        "<b>Timestamp:</b> " . date('Y-m-d H:i:s');

    $sent = sendTelegram($chatId, $msg, 'HTML');
    if ($sent) {
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to send test message to Telegram'], 500);
    }
}

// ==================== ADMIN RESET USER PASSWORD ====================
if ($action === 'adminResetPassword') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $data = getRequestData();
    $role = trim($data['role'] ?? '');
    $newPassword = $data['newPassword'] ?? '';

    if (empty($role) || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'Role and new password required'], 400);
    }

    try {
        $pdo = db();
        $userTable = getTableName($pdo, 'users');
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $mustChangeVal = ($newPassword === 'admin123') ? 1 : 0;
        
        if (columnExists($pdo, $userTable, 'must_change_password')) {
            $stmt = $pdo->prepare("UPDATE `{$userTable}` SET password = ?, must_change_password = ? WHERE role = ? OR username = ? OR department = ?");
            $stmt->execute([$hashed, $mustChangeVal, $role, $role, $role]);
        } else {
            $stmt = $pdo->prepare("UPDATE `{$userTable}` SET password = ? WHERE role = ? OR username = ? OR department = ?");
            $stmt->execute([$hashed, $role, $role, $role]);
        }
        logSystemAction("Password Reset", $_SESSION['user']['name'], "User: " . $role);
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== BULK UPDATE STATUS ====================
if ($action === 'bulkUpdateStatus') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }

    $data = getRequestData();
    $ids = $data['ids'] ?? [];
    $newStatus = $data['newStatus'] ?? '';
    $approverName = trim($data['approverName'] ?? $data['actorName'] ?? '');
    $approverRole = trim($data['approverRole'] ?? $_SESSION['user']['role'] ?? '');

    if (empty($ids) || !is_array($ids) || empty($newStatus)) {
        jsonResponse(['success' => false, 'message' => 'Invalid batch parameters'], 400);
    }
    if (empty($approverName)) {
        jsonResponse(['success' => false, 'message' => 'User name is required to approve/reject requests'], 400);
    }

    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        $count = 0;
        foreach ($ids as $id) {
            $stmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
            $stmt->execute([$id]);
            $rowData = $stmt->fetch();
            if ($rowData) {
                $fullActor = $approverRole ? "$approverName ($approverRole)" : $approverName;
                $updateStmt = $pdo->prepare("UPDATE `{$reqTable}` SET status = ?, approver = ? WHERE request_id = ?");
                $updateStmt->execute([$newStatus, $fullActor, $id]);
                sendDetailedNotification($rowData, $newStatus, $fullActor);
                logSystemAction("Bulk " . $newStatus, $approverName, $id);
                $count++;
            }
        }
        jsonResponse(['success' => true, 'count' => $count]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== BULK MARK DONE ====================
if ($action === 'bulkMarkDone') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }

    $data = getRequestData();
    $ids = $data['ids'] ?? [];
    $finalizerName = trim($data['finalizerName'] ?? $data['actorName'] ?? '');

    if (empty($ids) || !is_array($ids)) {
        jsonResponse(['success' => false, 'message' => 'Invalid batch parameters'], 400);
    }
    if (empty($finalizerName)) {
        jsonResponse(['success' => false, 'message' => 'User name is required to mark requests as Done'], 400);
    }

    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        $count = 0;
        foreach ($ids as $id) {
            $stmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
            $stmt->execute([$id]);
            $rowData = $stmt->fetch();
            if ($rowData) {
                $fullActor = "$finalizerName (Done)";
                $updateStmt = $pdo->prepare("UPDATE `{$reqTable}` SET status = 'Done', approver = ? WHERE request_id = ?");
                $updateStmt->execute([$fullActor, $id]);
                sendDetailedNotification($rowData, "Done", $fullActor);
                logSystemAction("Bulk Done", $finalizerName, $id);
                $count++;
            }
        }
        jsonResponse(['success' => true, 'count' => $count]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== UPDATE STATUS ====================
if ($action === 'updateStatus') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
    
    $data = getRequestData();
    $id = $data['id'] ?? '';
    $newStatus = $data['newStatus'] ?? '';
    $approverName = trim($data['approverName'] ?? $data['actorName'] ?? '');
    $approverRole = trim($data['approverRole'] ?? $_SESSION['user']['role'] ?? '');
    
    if (empty($approverName)) {
        jsonResponse(['success' => false, 'message' => 'User name is required to update status'], 400);
    }

    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        
        $stmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
        $stmt->execute([$id]);
        $rowData = $stmt->fetch();
        
        if (!$rowData) {
            jsonResponse(['success' => false, 'message' => 'Request not found'], 404);
        }
        
        $fullActor = $approverRole ? "$approverName ($approverRole)" : $approverName;
        $updateStmt = $pdo->prepare("UPDATE `{$reqTable}` SET status = ?, approver = ? WHERE request_id = ?");
        $updateStmt->execute([$newStatus, $fullActor, $id]);
        
        sendDetailedNotification($rowData, $newStatus, $fullActor);
        logSystemAction($newStatus, $approverName, $id);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== MARK DONE ====================
if ($action === 'markDone') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
    
    $data = getRequestData();
    $id = $data['id'] ?? '';
    $finalizerName = trim($data['finalizerName'] ?? $data['actorName'] ?? '');
    
    if (empty($finalizerName)) {
        jsonResponse(['success' => false, 'message' => 'User name is required to mark request as Done'], 400);
    }

    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        
        $stmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE request_id = ?");
        $stmt->execute([$id]);
        $rowData = $stmt->fetch();
        
        if (!$rowData) {
            jsonResponse(['success' => false, 'message' => 'Request not found'], 404);
        }
        
        $fullActor = "$finalizerName (Done)";
        $updateStmt = $pdo->prepare("UPDATE `{$reqTable}` SET status = 'Done', approver = ? WHERE request_id = ?");
        $updateStmt->execute([$fullActor, $id]);
        
        sendDetailedNotification($rowData, "Done", $fullActor);
        logSystemAction("Done", $finalizerName, $id);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DELETE REQUEST ====================
if ($action === 'deleteRequest') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $id = $data['id'] ?? '';
    
    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        $stmt = $pdo->prepare("DELETE FROM `{$reqTable}` WHERE request_id = ?");
        $stmt->execute([$id]);
        
        logSystemAction("Deleted", $_SESSION['user']['name'], $id);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== ADD SETTING ====================
if ($action === 'addSetting') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $type = $data['type'] ?? '';
    $value = trim($data['value'] ?? '');
    
    if (empty($value)) {
        jsonResponse(['success' => false, 'message' => 'Value required'], 400);
    }
    
    try {
        $pdo = db();
        $settTable = getTableName($pdo, 'settings');
        $settingKey = ($type === 'VoucherType') ? 'voucher_types' : (($type === 'StoreDepartment') ? 'store_departments' : 'edit_types');
        
        $stmt = $pdo->prepare("SELECT setting_value FROM `{$settTable}` WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $row = $stmt->fetch();
        
        $items = $row ? json_decode($row['setting_value'], true) : [];
        if (in_array($value, $items)) {
            jsonResponse(['success' => false, 'message' => 'Item already exists'], 400);
        }
        
        $items[] = $value;
        $updateStmt = $pdo->prepare("INSERT INTO `{$settTable}` (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $updateStmt->execute([$settingKey, json_encode($items), json_encode($items)]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DELETE SETTING ====================
if ($action === 'deleteSetting') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $type = $data['type'] ?? '';
    $value = trim($data['value'] ?? '');
    
    try {
        $pdo = db();
        $settTable = getTableName($pdo, 'settings');
        $settingKey = ($type === 'VoucherType') ? 'voucher_types' : (($type === 'StoreDepartment') ? 'store_departments' : 'edit_types');
        
        $stmt = $pdo->prepare("SELECT setting_value FROM `{$settTable}` WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $row = $stmt->fetch();
        
        $items = $row ? json_decode($row['setting_value'], true) : [];
        $items = array_values(array_filter($items, function($item) use ($value) { return $item !== $value; }));
        
        $updateStmt = $pdo->prepare("UPDATE `{$settTable}` SET setting_value = ? WHERE setting_key = ?");
        $updateStmt->execute([json_encode($items), $settingKey]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== ADD DEPARTMENT ====================
if ($action === 'addDepartment') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $name = trim($data['name'] ?? '');
    $telegramId = trim($data['telegramId'] ?? '');
    
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Department name required'], 400);
    }
    
    try {
        $pdo = db();
        $deptTable = getTableName($pdo, 'departments');
        $stmt = $pdo->prepare("INSERT INTO `{$deptTable}` (dept_name, telegram_chat_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE telegram_chat_id = VALUES(telegram_chat_id)");
        $stmt->execute([$name, $telegramId]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DELETE DEPARTMENT ====================
if ($action === 'deleteDepartment') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $name = trim($data['name'] ?? '');
    
    try {
        $pdo = db();
        $deptTable = getTableName($pdo, 'departments');
        $stmt = $pdo->prepare("DELETE FROM `{$deptTable}` WHERE dept_name = ?");
        $stmt->execute([$name]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== ADD BRANCH ====================
if ($action === 'addBranch') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $name = trim($data['name'] ?? $data['branch'] ?? '');
    $code = trim($data['code'] ?? '');
    if (empty($code)) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8));
        if (empty($code)) $code = 'BR' . rand(100, 999);
    }
    
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Branch name required'], 400);
    }
    
    try {
        $pdo = db();
        $branchTable = getTableName($pdo, 'branches');
        if (columnExists($pdo, $branchTable, 'branch_code')) {
            $stmt = $pdo->prepare("INSERT INTO `{$branchTable}` (branch_code, branch_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE branch_name = VALUES(branch_name)");
            $stmt->execute([$code, $name]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$branchTable}` (branch_name) VALUES (?)");
            $stmt->execute([$name]);
        }
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DELETE BRANCH ====================
if ($action === 'deleteBranch') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $branch = trim($data['branch'] ?? '');
    
    if (empty($branch)) {
        jsonResponse(['success' => false, 'message' => 'Branch name required'], 400);
    }
    
    try {
        $pdo = db();
        $branchTable = getTableName($pdo, 'branches');
        if (columnExists($pdo, $branchTable, 'branch_code')) {
            $stmt = $pdo->prepare("DELETE FROM `{$branchTable}` WHERE branch_name = ? OR branch_code = ?");
            $stmt->execute([$branch, $branch]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM `{$branchTable}` WHERE branch_name = ?");
            $stmt->execute([$branch]);
        }
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== ADD USER ====================
if ($action === 'addUser') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $role = trim($data['role'] ?? '');
    $name = trim($data['name'] ?? '');
    $password = $data['password'] ?? '';
    $chatId = trim($data['chatId'] ?? '');
    
    if (empty($role) || empty($name) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Role, name, and password required'], 400);
    }
    
    try {
        $pdo = db();
        $userTable = getTableName($pdo, 'users');
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO `{$userTable}` (role, full_name, password, chat_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password = VALUES(password), chat_id = VALUES(chat_id)");
        $stmt->execute([$role, $name, $hashedPassword, $chatId]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== SEND REPORT ====================
if ($action === 'sendReport') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $data = getRequestData();
    $type = $data['type'] ?? 'Weekly';
    
    try {
        $pdo = db();
        $reqTable = getTableName($pdo, 'requests');
        
        $interval = ($type === 'Weekly') ? 7 : 30;
        
        // Fetch detailed request records for the period
        $stmt = $pdo->prepare("SELECT * FROM `{$reqTable}` WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY timestamp DESC");
        $stmt->execute([$interval]);
        $rows = $stmt->fetchAll();
        
        $total = count($rows);
        $approved = 0;
        $pending = 0;
        $rejected = 0;
        $done = 0;
        $actionTotals = ['Edit' => 0, 'Void' => 0, 'On Hand Delete' => 0];
        $resolutionTimes = [];
        
        foreach ($rows as $row) {
            $status = $row['status'];
            if ($status === 'Approved') {
                $approved++;
            } elseif ($status === 'Pending') {
                $pending++;
            } elseif ($status === 'Rejected') {
                $rejected++;
            } elseif ($status === 'Done') {
                $done++;
                $resolutionTimes[] = 24; // Standard resolution hours representation
            }
            
            $act = $row['action_type'];
            if (isset($actionTotals[$act])) {
                $actionTotals[$act]++;
            }
        }
        
        $approvalRate = $total > 0 ? round((($approved + $done) / $total) * 100) : 0;
        $avgRes = '0h';
        if (count($resolutionTimes) > 0) {
            $avgHours = array_sum($resolutionTimes) / count($resolutionTimes);
            $avgRes = round($avgHours) . 'h';
        }

        // Calculate Branch Rankings (All branches with >0 requests) & Top Edit Types
        $branchCounts = [];
        $editTypeCounts = [];
        foreach ($rows as $row) {
            $b = trim((string)($row['branch'] ?? 'Unknown'));
            if ($b === '') $b = 'Unknown';
            $branchCounts[$b] = ($branchCounts[$b] ?? 0) + 1;
            
            if ($row['action_type'] === 'Edit' && !empty($row['edit_type'])) {
                $et = $row['edit_type'];
                $editTypeCounts[$et] = ($editTypeCounts[$et] ?? 0) + 1;
            }
        }
        
        arsort($branchCounts);
        arsort($editTypeCounts);
        
        // List ALL branches ranked by request count (omitting 0-request branches)
        $rankedBranchesList = [];
        $rankIdx = 1;
        foreach ($branchCounts as $bName => $bCount) {
            if ($bCount <= 0) continue; // Exclude branches with 0 requests
            $medal = ($rankIdx === 1) ? '🥇 ' : (($rankIdx === 2) ? '🥈 ' : (($rankIdx === 3) ? '🥉 ' : "{$rankIdx}. "));
            $rankedBranchesList[] = "{$medal}<b>" . telegramHtmlEscape($bName) . "</b>: {$bCount} req";
            $rankIdx++;
        }
        $rankedBranchesStr = !empty($rankedBranchesList) ? implode("\n", $rankedBranchesList) : "None";
        
        $topEditTypes = array_slice($editTypeCounts, 0, 5, true);
        $topEditTypesStr = '';
        if (!empty($topEditTypes)) {
            $tetItems = [];
            foreach ($topEditTypes as $etName => $etCount) {
                $tetItems[] = telegramHtmlEscape($etName) . " (" . $etCount . ")";
            }
            $topEditTypesStr = implode(", ", $tetItems);
        } else {
            $topEditTypesStr = "None";
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url = $protocol . $host . '/edit_request/index.php';
        
        // Generate detailed CSV file
        $tempDir = sys_get_temp_dir();
        $csvFile = $tempDir . DIRECTORY_SEPARATOR . $type . '_Report_' . date('Ymd_His') . '.csv';
        $fp = fopen($csvFile, 'w');
        if ($fp) {
            // Write UTF-8 BOM
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($fp, ["ID", "Timestamp", "Branch", "User Name", "Store User", "Action Type", "Voucher Code", "Voucher Type", "Target Dept", "Status", "Approver", "Reason"]);
            
            foreach ($rows as $r) {
                fputcsv($fp, [
                    $r['request_id'],
                    $r['timestamp'],
                    $r['branch'],
                    $r['user_name'],
                    $r['store_user'] ?? '',
                    $r['action_type'],
                    $r['voucher_code'] ?? '',
                    $r['voucher_type'] ?? '',
                    $r['target_dept'],
                    $r['status'],
                    $r['approver'] ?? '',
                    $r['reason'] ?? ''
                ]);
            }
            fclose($fp);
        }

        $message = "<b>" . telegramHtmlEscape(strtoupper($type)) . " DETAILED REPORT</b>\n\n" .
            "<b>Period:</b> Last " . telegramHtmlEscape($type === 'Weekly' ? '7 days' : '30 days') . "\n" .
            "<b>Total Requests:</b> " . (int)$total . "\n" .
            "<b>Avg. Resolution:</b> " . telegramHtmlEscape($avgRes) . "\n" .
            "<b>Approval Rate:</b> " . (int)$approvalRate . "%\n\n" .
            "<b>Approved:</b> " . (int)$approved . "\n" .
            "<b>Pending:</b> " . (int)$pending . "\n" .
            "<b>Rejected:</b> " . (int)$rejected . "\n" .
            "<b>Done:</b> " . (int)$done . "\n\n" .
            "🏆 <b>Branch Rankings (Active Branches):</b>\n" . $rankedBranchesStr . "\n\n" .
            "<b>Top Edit Types:</b>\n" . $topEditTypesStr . "\n\n" .
            "<b>Action Mix:</b> Edit " . (int)$actionTotals['Edit'] . " | Void " . (int)$actionTotals['Void'] . " | On Hand Delete " . (int)$actionTotals['On Hand Delete'] . "\n\n" .
            "<a href=\"" . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">View Dashboard</a>";

        if (file_exists($csvFile)) {
            sendTelegramDocument(TELEGRAM_CHAT_ID_ADMIN, $csvFile, $message);
            unlink($csvFile); // Clean up the temp file
        } else {
            sendTelegram(TELEGRAM_CHAT_ID_ADMIN, $message, 'HTML');
        }
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ==================== DEFAULT RESPONSE ====================
jsonResponse(['error' => 'Invalid action: ' . $action], 400);
