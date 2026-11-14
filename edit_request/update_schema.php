<?php
// ===================================================
// Kaldis Voucher System - Database Schema Updater
// ===================================================

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Kaldis Voucher System - Schema Updater</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; color: #333; padding: 20px; line-height: 1.6; }
        .card { background: white; border-radius: 12px; padding: 25px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #4B3621; border-bottom: 2px solid #D4A373; padding-bottom: 10px; }
        .log-entry { margin: 8px 0; padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 13px; }
        .log-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .log-info { background: #e0f2fe; color: #075985; border-left: 4px solid #0284c7; }
        .log-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .log-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .btn { display: inline-block; background: #4B3621; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; }
        .btn:hover { background: #6F4E37; }
    </style>
</head>
<body>
<div class='card'>
    <h1>🏦 Kaldis Voucher System - Schema Updater</h1>
";

function logMsg($msg, $type = 'info') {
    $time = date('H:i:s');
    echo "<div class='log-entry log-{$type}'>[{$time}] {$msg}</div>";
}

try {
    $pdo = db();
    logMsg("Connected to MySQL database: <strong>" . DB_NAME . "</strong> on <strong>" . DB_HOST . "</strong>", "success");

    // Helper functions for schema update
    function ensureColumn(PDO $db, $table, $column, $definition, $afterColumn = '') {
        $check = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $check->execute([$table, $column]);
        $exists = (int)$check->fetchColumn() > 0;

        if (!$exists) {
            $afterSql = $afterColumn ? " AFTER `{$afterColumn}`" : "";
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}{$afterSql}");
            logMsg("Added missing column <strong>{$column}</strong> to table <strong>{$table}</strong>", "success");
        } else {
            // Modify column definition to ensure proper type
            try {
                $db->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition}");
                logMsg("Verified column <strong>{$column}</strong> in table <strong>{$table}</strong>", "info");
            } catch (Exception $e) {
                logMsg("Column <strong>{$column}</strong> in table <strong>{$table}</strong> verified: " . $e->getMessage(), "warning");
            }
        }
    }

    // Determine target tables (checking both edit_* and non-prefixed)
    $tablesToUpdate = ['requests', 'departments', 'users', 'branches', 'settings', 'system_logs', 'usernames'];
    
    foreach ($tablesToUpdate as $base) {
        $targetTable = getTableName($pdo, $base);
        logMsg("Target table for {$base}: <strong>{$targetTable}</strong>", "info");

        if ($base === 'requests') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(50) NOT NULL UNIQUE,
                timestamp DATETIME NOT NULL,
                branch VARCHAR(200) NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                voucher_code VARCHAR(100) DEFAULT NULL,
                voucher_type VARCHAR(100) DEFAULT NULL,
                action_type VARCHAR(100) NOT NULL DEFAULT 'Edit',
                edit_type VARCHAR(100) DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                target_dept VARCHAR(200) NOT NULL DEFAULT 'F&B',
                incorrect_date VARCHAR(50) DEFAULT NULL,
                correct_date VARCHAR(50) DEFAULT NULL,
                shift VARCHAR(50) DEFAULT NULL,
                status ENUM('Pending', 'Approved', 'Rejected', 'Done') DEFAULT 'Pending',
                approver VARCHAR(200) DEFAULT NULL,
                telegram_msg_id VARCHAR(100) DEFAULT NULL,
                INDEX idx_request_id (request_id),
                INDEX idx_status (status),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Ensure all columns exist
            ensureColumn($pdo, $targetTable, 'store_user', 'VARCHAR(150) DEFAULT NULL', 'user_name');
            ensureColumn($pdo, $targetTable, 'voucher_code', 'VARCHAR(100) DEFAULT NULL', 'store_user');
            ensureColumn($pdo, $targetTable, 'voucher_type', 'VARCHAR(100) DEFAULT NULL', 'voucher_code');
            ensureColumn($pdo, $targetTable, 'action_type', 'VARCHAR(100) NOT NULL DEFAULT "Edit"', 'voucher_type');
            ensureColumn($pdo, $targetTable, 'edit_type', 'VARCHAR(100) DEFAULT NULL', 'action_type');
            ensureColumn($pdo, $targetTable, 'reason', 'TEXT DEFAULT NULL', 'edit_type');
            ensureColumn($pdo, $targetTable, 'target_dept', 'VARCHAR(200) NOT NULL DEFAULT "F&B"', 'reason');
            ensureColumn($pdo, $targetTable, 'incorrect_date', 'VARCHAR(50) DEFAULT NULL', 'target_dept');
            ensureColumn($pdo, $targetTable, 'correct_date', 'VARCHAR(50) DEFAULT NULL', 'incorrect_date');
            ensureColumn($pdo, $targetTable, 'shift', 'VARCHAR(50) DEFAULT NULL', 'correct_date');
            ensureColumn($pdo, $targetTable, 'status', "ENUM('Pending', 'Approved', 'Rejected', 'Done') DEFAULT 'Pending'", 'shift');
            ensureColumn($pdo, $targetTable, 'approver', 'VARCHAR(200) DEFAULT NULL', 'status');
            ensureColumn($pdo, $targetTable, 'telegram_msg_id', 'VARCHAR(100) DEFAULT NULL', 'approver');

        } elseif ($base === 'departments') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                dept_name VARCHAR(100) NOT NULL UNIQUE,
                telegram_chat_id VARCHAR(50) DEFAULT NULL,
                INDEX idx_dept_name (dept_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            ensureColumn($pdo, $targetTable, 'telegram_chat_id', 'VARCHAR(50) DEFAULT NULL', 'dept_name');

            // Seed default departments if missing
            $pdo->exec("INSERT INTO `{$targetTable}` (dept_name, telegram_chat_id) VALUES 
                ('Finance', '-1003609053132'),
                ('F&B', '-1003821366530')
                ON DUPLICATE KEY UPDATE telegram_chat_id = VALUES(telegram_chat_id)");
            $pdo->exec("DELETE FROM `{$targetTable}` WHERE dept_name = 'IT'");
            logMsg("Seeded default departments (Finance & F&B) and removed IT from <strong>{$targetTable}</strong>", "success");

        } elseif ($base === 'users') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) DEFAULT NULL,
                role VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(150) DEFAULT NULL,
                full_name VARCHAR(100) NOT NULL,
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

            ensureColumn($pdo, $targetTable, 'username', 'VARCHAR(100) DEFAULT NULL', 'id');
            ensureColumn($pdo, $targetTable, 'name', 'VARCHAR(150) DEFAULT NULL', 'role');
            ensureColumn($pdo, $targetTable, 'full_name', 'VARCHAR(150) DEFAULT NULL', 'name');
            ensureColumn($pdo, $targetTable, 'department', 'VARCHAR(100) DEFAULT NULL', 'full_name');
            ensureColumn($pdo, $targetTable, 'email', 'VARCHAR(150) DEFAULT NULL', 'department');
            ensureColumn($pdo, $targetTable, 'must_change_password', 'TINYINT(1) DEFAULT 1', 'password');
            ensureColumn($pdo, $targetTable, 'chat_id', 'VARCHAR(50) DEFAULT NULL', 'must_change_password');
            ensureColumn($pdo, $targetTable, 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'chat_id');

            // Seed default users with default password 'admin123'
            $admin123Hash = password_hash('admin123', PASSWORD_DEFAULT);

            $stmtUser = $pdo->prepare("INSERT INTO `{$targetTable}` 
                (role, username, name, full_name, department, password, must_change_password, chat_id) VALUES 
                ('Admin', 'admin', 'Kaldi\'s ICT Admin', 'Kaldi\'s ICT Admin', 'ICT', ?, 1, '814523272'),
                ('Finance', 'finance_trainer', 'Finance Department Manager', 'Finance Department Manager', 'Finance', ?, 1, '-1003609053132'),
                ('F&B', 'fb_mgr', 'F&B Department Manager', 'F&B Department Manager', 'F&B', ?, 1, '-1003821366530'),
                ('IT', 'it_trainer', 'IT Department Trainer', 'IT Department Trainer', 'IT', ?, 1, '814523272'),
                ('Coordinator', 'coordinator', 'Training Coordinator Team', 'Training Coordinator Team', 'HR / Training', ?, 1, '')
                ON DUPLICATE KEY UPDATE 
                    username = VALUES(username),
                    name = VALUES(name),
                    full_name = VALUES(full_name),
                    department = VALUES(department),
                    password = VALUES(password),
                    must_change_password = 1,
                    chat_id = VALUES(chat_id)");
            $stmtUser->execute([$admin123Hash, $admin123Hash, $admin123Hash, $admin123Hash, $admin123Hash]);
            logMsg("Seeded/Updated 5 system users (Admin, Finance, F&B, IT, Coordinator) with default password 'admin123' into <strong>{$targetTable}</strong>", "success");

        } elseif ($base === 'branches') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                branch_code VARCHAR(10) NOT NULL UNIQUE,
                branch_name VARCHAR(100) NOT NULL,
                INDEX idx_branch_code (branch_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

            $stmtBranch = $pdo->prepare("INSERT INTO `{$targetTable}` (branch_code, branch_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE branch_name = VALUES(branch_name)");
            foreach ($branches as $branch) {
                $stmtBranch->execute([$branch[0], $branch[1]]);
            }
            logMsg("Seeded 31 default branches into <strong>{$targetTable}</strong>", "success");

        } elseif ($base === 'settings') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

            $pdo->prepare("INSERT INTO `{$targetTable}` (setting_key, setting_value) VALUES ('voucher_types', ?), ('edit_types', ?), ('store_departments', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$voucherTypes, $editTypes, $storeDepts]);
            logMsg("Seeded default settings into <strong>{$targetTable}</strong>", "success");

        } elseif ($base === 'system_logs') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                action VARCHAR(100) NOT NULL,
                actor VARCHAR(100) NOT NULL,
                request_id VARCHAR(50) DEFAULT NULL,
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            logMsg("Verified system logs table <strong>{$targetTable}</strong>", "success");

        } elseif ($base === 'usernames') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$targetTable}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_name VARCHAR(150) NOT NULL UNIQUE,
                branch VARCHAR(200) DEFAULT NULL,
                request_count INT UNSIGNED DEFAULT 1,
                first_seen DATETIME NOT NULL,
                last_seen DATETIME NOT NULL,
                INDEX idx_username (user_name),
                INDEX idx_count (request_count)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Backfill existing user records from requests table if usernames table is empty
            $uCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$targetTable}`")->fetchColumn();
            if ($uCount === 0) {
                $reqTab = getTableName($pdo, 'requests');
                if (tableExists($pdo, $reqTab)) {
                    $bStmt = $pdo->query("SELECT user_name, branch, COUNT(*) as cnt, MIN(timestamp) as min_ts, MAX(timestamp) as max_ts FROM `{$reqTab}` WHERE user_name IS NOT NULL AND user_name != '' GROUP BY user_name");
                    $insU = $pdo->prepare("INSERT INTO `{$targetTable}` (user_name, branch, request_count, first_seen, last_seen) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE request_count = VALUES(request_count), last_seen = VALUES(last_seen)");
                    while ($rU = $bStmt->fetch()) {
                        $insU->execute([$rU['user_name'], $rU['branch'], $rU['cnt'], $rU['min_ts'], $rU['max_ts']]);
                    }
                    logMsg("Backfilled user request statistics into <strong>{$targetTable}</strong>", "success");
                }
            } else {
                logMsg("Verified usernames tracking table <strong>{$targetTable}</strong>", "success");
            }
        }
    }

    // Migrate existing On Hand Delete requests to prefix F&B
    $reqTable = getTableName($pdo, 'requests');
    $migratedCount = $pdo->exec("UPDATE `{$reqTable}` SET target_dept = CONCAT('F&B (', target_dept, ')') WHERE action_type = 'On Hand Delete' AND target_dept NOT LIKE 'F&B%'");
    if ($migratedCount > 0) {
        logMsg("Migrated <strong>{$migratedCount}</strong> existing On Hand Delete requests to prefix target_dept with F&B.", "success");
    }

    logMsg("<strong>ALL DATABASE SCHEMAS & TABLES UPDATED SUCCESSFULLY! 🎉</strong>", "success");

} catch (Exception $e) {
    logMsg("Schema update failed: " . $e->getMessage(), "error");
}

echo "
    <br>
    <a href='index.php' class='btn'>Return to Dashboard &rarr;</a>
</div>
</body>
</html>";
