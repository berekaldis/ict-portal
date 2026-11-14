<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$adminUsername = $_SESSION['admin_username'] ?? 'admin';
$db = getDB();
$activeTab = $_GET['tab'] ?? 'dashboard';
$toast = null;

if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
}

function setToast($message, $type = 'success') {
    $_SESSION['toast'] = ['message' => $message, 'type' => $type];
}

function redirect($url, $toast = null) {
    if ($toast) setToast($toast['message'], $toast['type']);
    header("Location: $url");
    exit;
}

// Branch Management
if (isset($_POST['add_branch'])) {
    $name = clean($_POST['branch_name']);
    $address = clean($_POST['branch_address'] ?? '');
    $phone = clean($_POST['branch_phone'] ?? '');
    $manager = clean($_POST['branch_manager'] ?? '');
    
    if ($name) {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "branches (name, address, phone, manager_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $address, $phone, $manager);
        if ($stmt->execute()) {
            logActivity('branch_added', 'branch', $stmt->insert_id, "Added branch: $name");
            redirect("admin.php?tab=branches", ['message' => 'Branch added!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=branches", ['message' => 'Failed to add branch', 'type' => 'error']);
}

if (isset($_POST['edit_branch'])) {
    $id = intval($_POST['branch_id']);
    $name = clean($_POST['edit_branch_name']);
    $address = clean($_POST['edit_branch_address'] ?? '');
    $phone = clean($_POST['edit_branch_phone'] ?? '');
    $manager = clean($_POST['edit_branch_manager'] ?? '');
    $active = isset($_POST['edit_branch_active']) ? 1 : 0;
    
    if ($name && $id) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "branches SET name=?, address=?, phone=?, manager_name=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssii", $name, $address, $phone, $manager, $active, $id);
        if ($stmt->execute()) {
            logActivity('branch_updated', 'branch', $id, "Updated branch: $name");
            redirect("admin.php?tab=branches", ['message' => 'Branch updated!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=branches", ['message' => 'Failed to update branch', 'type' => 'error']);
}

if (isset($_GET['del_branch'])) {
    $id = intval($_GET['del_branch']);
    $branch = $db->query("SELECT name FROM " . TABLE_PREFIX . "branches WHERE id = $id")->fetch_assoc();
    $db->query("DELETE FROM " . TABLE_PREFIX . "branches WHERE id = $id");
    logActivity('branch_deleted', 'branch', $id, "Deleted: " . ($branch['name'] ?? $id));
    redirect("admin.php?tab=branches", ['message' => 'Branch deleted', 'type' => 'warning']);
}

// Technician Management
if (isset($_POST['add_tech'])) {
    $name = clean($_POST['tech_name']);
    $phone = clean($_POST['tech_phone']);
    $email = clean($_POST['tech_email'] ?? '');
    $type = $_POST['tech_type'];
    
    if ($name) {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "technicians (name, phone, email, type, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $name, $phone, $email, $type);
        if ($stmt->execute()) {
            logActivity('technician_added', 'technician', $stmt->insert_id, "Added: $name");
            redirect("admin.php?tab=technicians", ['message' => 'Technician added!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=technicians", ['message' => 'Failed to add technician', 'type' => 'error']);
}

if (isset($_POST['edit_tech'])) {
    $id = intval($_POST['tech_id']);
    $name = clean($_POST['edit_tech_name']);
    $phone = clean($_POST['edit_tech_phone']);
    $email = clean($_POST['edit_tech_email'] ?? '');
    $type = $_POST['edit_tech_type'];
    $active = isset($_POST['edit_tech_active']) ? 1 : 0;
    
    if ($name && $id) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "technicians SET name=?, phone=?, email=?, type=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssii", $name, $phone, $email, $type, $active, $id);
        if ($stmt->execute()) {
            logActivity('technician_updated', 'technician', $id, "Updated: $name");
            redirect("admin.php?tab=technicians", ['message' => 'Technician updated!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=technicians", ['message' => 'Failed to update technician', 'type' => 'error']);
}

if (isset($_GET['del_tech'])) {
    $id = intval($_GET['del_tech']);
    $tech = $db->query("SELECT name FROM " . TABLE_PREFIX . "technicians WHERE id = $id")->fetch_assoc();
    $db->query("DELETE FROM " . TABLE_PREFIX . "technicians WHERE id = $id");
    logActivity('technician_deleted', 'technician', $id, "Deleted: " . ($tech['name'] ?? $id));
    redirect("admin.php?tab=technicians", ['message' => 'Technician deleted', 'type' => 'warning']);
}

if (isset($_GET['set_default_tech'])) {
    $id = intval($_GET['set_default_tech']);
    $type = $_GET['type'];
    $db->query("UPDATE " . TABLE_PREFIX . "technicians SET is_default = 0 WHERE type = '" . $db->real_escape_string($type) . "'");
    $db->query("UPDATE " . TABLE_PREFIX . "technicians SET is_default = 1 WHERE id = $id");
    logActivity('technician_set_default', 'technician', $id, "Set default $type technician");
    redirect("admin.php?tab=technicians", ['message' => 'Default technician updated!', 'type' => 'success']);
}

// Category Management
if (isset($_POST['add_category'])) {
    $name = clean($_POST['cat_name']);
    $icon = clean($_POST['cat_icon']);
    $type = $_POST['cat_type'];
    $has_photo = isset($_POST['cat_has_photo']) ? 1 : 0;
    $description = clean($_POST['cat_description'] ?? '');
    
    if ($name) {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "categories (name, icon, type, has_photo, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $icon, $type, $has_photo, $description);
        if ($stmt->execute()) {
            logActivity('category_added', 'category', $stmt->insert_id, "Added: $name");
            redirect("admin.php?tab=checklist", ['message' => 'Category added!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=checklist", ['message' => 'Failed to add category', 'type' => 'error']);
}

if (isset($_POST['edit_category'])) {
    $id = intval($_POST['cat_id']);
    $name = clean($_POST['edit_cat_name']);
    $icon = clean($_POST['edit_cat_icon']);
    $type = $_POST['edit_cat_type'];
    $has_photo = isset($_POST['edit_cat_has_photo']) ? 1 : 0;
    $description = clean($_POST['edit_cat_description'] ?? '');
    $active = isset($_POST['edit_cat_active']) ? 1 : 0;
    
    if ($name && $id) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "categories SET name=?, icon=?, type=?, has_photo=?, description=?, is_active=? WHERE id=?");
        $stmt->bind_param("sssssii", $name, $icon, $type, $has_photo, $description, $active, $id);
        if ($stmt->execute()) {
            logActivity('category_updated', 'category', $id, "Updated: $name");
            redirect("admin.php?tab=checklist", ['message' => 'Category updated!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=checklist", ['message' => 'Failed to update category', 'type' => 'error']);
}

if (isset($_GET['del_cat'])) {
    $id = intval($_GET['del_cat']);
    $cat = $db->query("SELECT name FROM " . TABLE_PREFIX . "categories WHERE id = $id")->fetch_assoc();
    // First delete related items
    $db->query("DELETE FROM " . TABLE_PREFIX . "checklist_items WHERE category_id = $id");
    $db->query("DELETE FROM " . TABLE_PREFIX . "categories WHERE id = $id");
    logActivity('category_deleted', 'category', $id, "Deleted: " . ($cat['name'] ?? $id));
    redirect("admin.php?tab=checklist", ['message' => 'Category deleted', 'type' => 'warning']);
}

// Checklist Item Management
if (isset($_POST['add_item'])) {
    $cat_id = intval($_POST['category_id']);
    $question = clean($_POST['question']);
    $help_text = clean($_POST['help_text'] ?? '');
    
    if ($question && $cat_id) {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "checklist_items (category_id, question, help_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $cat_id, $question, $help_text);
        if ($stmt->execute()) {
            logActivity('checklist_item_added', 'checklist_item', $stmt->insert_id, "Added: $question");
            redirect("admin.php?tab=checklist", ['message' => 'Checklist item added!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=checklist", ['message' => 'Failed to add item', 'type' => 'error']);
}

if (isset($_POST['edit_item'])) {
    $id = intval($_POST['item_id']);
    $cat_id = intval($_POST['edit_category_id']);
    $question = clean($_POST['edit_question']);
    $help_text = clean($_POST['edit_help_text'] ?? '');
    $active = isset($_POST['edit_item_active']) ? 1 : 0;
    
    if ($question && $cat_id && $id) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "checklist_items SET category_id=?, question=?, help_text=?, is_active=? WHERE id=?");
        $stmt->bind_param("issii", $cat_id, $question, $help_text, $active, $id);
        if ($stmt->execute()) {
            logActivity('checklist_item_updated', 'checklist_item', $id, "Updated: $question");
            redirect("admin.php?tab=checklist", ['message' => 'Item updated!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=checklist", ['message' => 'Failed to update item', 'type' => 'error']);
}

if (isset($_GET['del_item'])) {
    $id = intval($_GET['del_item']);
    $item = $db->query("SELECT question FROM " . TABLE_PREFIX . "checklist_items WHERE id = $id")->fetch_assoc();
    $db->query("DELETE FROM " . TABLE_PREFIX . "checklist_items WHERE id = $id");
    logActivity('checklist_item_deleted', 'checklist_item', $id, "Deleted: " . ($item['question'] ?? $id));
    redirect("admin.php?tab=checklist", ['message' => 'Item deleted', 'type' => 'warning']);
}

// Hardware Asset Management
if (isset($_POST['add_hardware'])) {
    $branch_id = intval($_POST['asset_branch_id']);
    $asset_type = clean($_POST['asset_type']);
    $asset_tag = clean($_POST['asset_tag'] ?? '');
    $serial_number = clean($_POST['serial_number'] ?? '');
    $brand = clean($_POST['brand'] ?? '');
    $model = clean($_POST['model'] ?? '');
    $specifications = clean($_POST['specifications'] ?? '');
    $purchase_date = $_POST['purchase_date'] ?? null;
    $warranty_expiry = $_POST['warranty_expiry'] ?? null;
    $status = clean($_POST['status']);
    $assigned_to = clean($_POST['assigned_to'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $notes = clean($_POST['notes'] ?? '');
    
    if ($branch_id && $asset_type) {
        $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "hardware_assets 
            (branch_id, asset_type, asset_tag, serial_number, brand, model, specifications, 
             purchase_date, warranty_expiry, status, assigned_to, location, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssssss", 
            $branch_id, $asset_type, $asset_tag, $serial_number, $brand, $model, $specifications,
            $purchase_date, $warranty_expiry, $status, $assigned_to, $location, $notes);
        if ($stmt->execute()) {
            logActivity('hardware_added', 'hardware', $stmt->insert_id, "Added hardware: $brand $model");
            redirect("admin.php?tab=hardware", ['message' => 'Hardware asset added!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=hardware", ['message' => 'Failed to add hardware', 'type' => 'error']);
}

if (isset($_POST['edit_hardware'])) {
    $id = intval($_POST['asset_id']);
    $branch_id = intval($_POST['edit_asset_branch_id']);
    $asset_type = clean($_POST['edit_asset_type']);
    $asset_tag = clean($_POST['edit_asset_tag'] ?? '');
    $serial_number = clean($_POST['edit_serial_number'] ?? '');
    $brand = clean($_POST['edit_brand'] ?? '');
    $model = clean($_POST['edit_model'] ?? '');
    $specifications = clean($_POST['edit_specifications'] ?? '');
    $purchase_date = $_POST['edit_purchase_date'] ?? null;
    $warranty_expiry = $_POST['edit_warranty_expiry'] ?? null;
    $status = clean($_POST['edit_status']);
    $assigned_to = clean($_POST['edit_assigned_to'] ?? '');
    $location = clean($_POST['edit_location'] ?? '');
    $notes = clean($_POST['edit_notes'] ?? '');
    
    if ($id && $branch_id && $asset_type) {
        $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "hardware_assets SET 
            branch_id=?, asset_type=?, asset_tag=?, serial_number=?, brand=?, model=?, 
            specifications=?, purchase_date=?, warranty_expiry=?, status=?, assigned_to=?, 
            location=?, notes=? WHERE id=?");
        $stmt->bind_param("issssssssssssi", 
            $branch_id, $asset_type, $asset_tag, $serial_number, $brand, $model, $specifications,
            $purchase_date, $warranty_expiry, $status, $assigned_to, $location, $notes, $id);
        if ($stmt->execute()) {
            logActivity('hardware_updated', 'hardware', $id, "Updated hardware: $brand $model");
            redirect("admin.php?tab=hardware", ['message' => 'Hardware asset updated!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=hardware", ['message' => 'Failed to update hardware', 'type' => 'error']);
}

if (isset($_GET['del_hardware'])) {
    $id = intval($_GET['del_hardware']);
    $asset = $db->query("SELECT brand, model FROM " . TABLE_PREFIX . "hardware_assets WHERE id = $id")->fetch_assoc();
    $db->query("DELETE FROM " . TABLE_PREFIX . "hardware_assets WHERE id = $id");
    logActivity('hardware_deleted', 'hardware', $id, "Deleted: " . ($asset['brand'] . ' ' . $asset['model'] ?? $id));
    redirect("admin.php?tab=hardware", ['message' => 'Hardware asset deleted', 'type' => 'warning']);
}

// Hardware Audit Action
if (isset($_POST['run_hardware_audit'])) {
    $branch_id = intval($_POST['audit_branch_id']);
    $auditor = clean($_POST['auditor']);
    $summary = clean($_POST['audit_summary'] ?? '');
    $recommendations = clean($_POST['audit_recommendations'] ?? '');
    
    if ($branch_id) {
        $assets = getHardwareAssets($branch_id);
        $reportId = createHardwareAuditReport($branch_id, $assets, $auditor, $summary, $recommendations);
        if ($reportId) {
            logActivity('hardware_audit_completed', 'hardware_audit', $reportId, "Completed audit for branch ID: $branch_id");
            redirect("admin.php?tab=hardware", ['message' => 'Hardware audit completed successfully!', 'type' => 'success']);
        }
    }
    redirect("admin.php?tab=hardware", ['message' => 'Failed to complete audit', 'type' => 'error']);
}

// Telegram Configuration
if (isset($_POST['save_telegram'])) {
    $token = clean($_POST['bot_token']);
    $chat_id = clean($_POST['admin_chat_id']);
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    $db->query("DELETE FROM " . TABLE_PREFIX . "telegram_config");
    $stmt = $db->prepare("INSERT INTO " . TABLE_PREFIX . "telegram_config (bot_token, admin_chat_id, enabled) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $token, $chat_id, $enabled);
    $stmt->execute();
    logActivity('telegram_config_updated', 'config', null, "Telegram configuration updated");
    redirect("admin.php?tab=telegram", ['message' => 'Telegram settings saved!', 'type' => 'success']);
}

if (isset($_POST['test_telegram'])) {
    $result = sendTelegram("✅ Test message from Kaldis ICT System - " . date('Y-m-d H:i:s'));
    redirect("admin.php?tab=telegram", ['message' => $result ? 'Test message sent successfully!' : 'Failed to send test message', 'type' => $result ? 'success' : 'error']);
}

// Password Change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    $stmt = $db->prepare("SELECT password FROM " . TABLE_PREFIX . "admin WHERE username = ?");
    $stmt->bind_param("s", $adminUsername);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    
    if (!$admin || !password_verify($current, $admin['password'])) {
        redirect("admin.php?tab=settings", ['message' => 'Current password is incorrect', 'type' => 'error']);
    }
    if (strlen($new) < 6) {
        redirect("admin.php?tab=settings", ['message' => 'Password must be at least 6 characters', 'type' => 'error']);
    }
    if ($new !== $confirm) {
        redirect("admin.php?tab=settings", ['message' => 'New passwords do not match', 'type' => 'error']);
    }
    
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE " . TABLE_PREFIX . "admin SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hash, $adminUsername);
    $stmt->execute();
    logActivity('password_changed', 'admin', null, "Password changed for $adminUsername");
    redirect("admin.php?tab=settings", ['message' => 'Password changed successfully!', 'type' => 'success']);
}

// Delete Report
if (isset($_GET['del_report'])) {
    $id = intval($_GET['del_report']);
    $db->query("DELETE FROM " . TABLE_PREFIX . "reports WHERE id = $id");
    logActivity('report_deleted', 'report', $id, "Deleted report #$id");
    redirect("admin.php?tab=reports", ['message' => 'Report deleted', 'type' => 'warning']);
}

// Clear Activity Log
if (isset($_GET['clear_log'])) {
    $days = intval($_GET['clear_log']);
    if ($days == 0) {
        $db->query("DELETE FROM " . TABLE_PREFIX . "activity_log");
    } else {
        $db->query("DELETE FROM " . TABLE_PREFIX . "activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
    }
    redirect("admin.php?tab=activity", ['message' => 'Activity log cleared', 'type' => 'success']);
}

// Export Backup
if (isset($_GET['export_backup'])) {
    $tables = ['branches', 'technicians', 'categories', 'checklist_items', 'reports', 'telegram_config', 'activity_log', 'hardware_assets', 'hardware_audit_reports'];
    $backup = [];
    foreach ($tables as $table) {
        $result = $db->query("SELECT * FROM " . TABLE_PREFIX . $table);
        if ($result) $backup[$table] = $result->fetch_all(MYSQLI_ASSOC);
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="kaldis_backup_' . date('Y-m-d_His') . '.json"');
    echo json_encode($backup, JSON_PRETTY_PRINT);
    exit;
}

// Fetch data for display
$branches = $db->query("SELECT * FROM " . TABLE_PREFIX . "branches ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$technicians = $db->query("SELECT * FROM " . TABLE_PREFIX . "technicians ORDER BY type, name")->fetch_all(MYSQLI_ASSOC);
$hardwareCats = $db->query("SELECT * FROM " . TABLE_PREFIX . "categories WHERE type='hardware' ORDER BY display_order")->fetch_all(MYSQLI_ASSOC);
$softwareCats = $db->query("SELECT * FROM " . TABLE_PREFIX . "categories WHERE type='software' ORDER BY display_order")->fetch_all(MYSQLI_ASSOC);

// Hardware Assets Data
$hardwareAssets = getHardwareAssets();
$hardwareStats = getHardwareStats();
$totalAssets = array_sum(array_column($hardwareStats, 'total'));
$assetsOperational = array_sum(array_column($hardwareStats, 'operational'));
$assetsIssues = array_sum(array_column($hardwareStats, 'maintenance')) + array_sum(array_column($hardwareStats, 'repair'));
$expiringWarranties = getExpiringWarranties(90);
$needingAudit = getAssetsNeedingAudit(6);
$assetTypes = ['computer', 'laptop', 'printer', 'scanner', 'pos', 'router', 'switch', 'server', 'ups', 'other'];

// Reports filtering
$reportPage = max(1, intval($_GET['report_page'] ?? 1));
$reportsPerPage = 25;
$reportSearch = $_GET['report_search'] ?? '';
$reportType = $_GET['report_type'] ?? '';
$reportBranch = intval($_GET['report_branch'] ?? 0);
$reportDateFrom = $_GET['date_from'] ?? '';
$reportDateTo = $_GET['date_to'] ?? '';

$where = ["1=1"];
$params = [];
$types = "";

if ($reportSearch) {
    $where[] = "(r.branch_manager LIKE ? OR b.name LIKE ? OR t.name LIKE ?)";
    $s = "%$reportSearch%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= "sss";
}
if ($reportType) { 
    $where[] = "r.type = ?"; 
    $params[] = $reportType; 
    $types .= "s"; 
}
if ($reportBranch) { 
    $where[] = "r.branch_id = ?"; 
    $params[] = $reportBranch; 
    $types .= "i"; 
}
if ($reportDateFrom) { 
    $where[] = "r.submitted_at >= ?"; 
    $params[] = $reportDateFrom . " 00:00:00"; 
    $types .= "s"; 
}
if ($reportDateTo) { 
    $where[] = "r.submitted_at <= ?"; 
    $params[] = $reportDateTo . " 23:59:59"; 
    $types .= "s"; 
}

$whereClause = implode(" AND ", $where);

// Count total reports
$countSql = "SELECT COUNT(*) as total FROM " . TABLE_PREFIX . "reports r 
             LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id 
             LEFT JOIN " . TABLE_PREFIX . "technicians t ON r.technician_id = t.id 
             WHERE $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalReports = $countStmt->get_result()->fetch_assoc()['total'];
$pagination = paginate($totalReports, $reportsPerPage, $reportPage);

// Fetch reports
$reportSql = "SELECT r.*, b.name as branch_name, t.name as tech_name 
              FROM " . TABLE_PREFIX . "reports r 
              LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id 
              LEFT JOIN " . TABLE_PREFIX . "technicians t ON r.technician_id = t.id 
              WHERE $whereClause 
              ORDER BY r.id DESC LIMIT ? OFFSET ?";
$allParams = array_merge($params, [$reportsPerPage, $pagination['offset']]);
$allTypes = $types . "ii";

$reportStmt = $db->prepare($reportSql);
$reportStmt->bind_param($allTypes, ...$allParams);
$reportStmt->execute();
$reports = $reportStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Telegram config
$tg = $db->query("SELECT * FROM " . TABLE_PREFIX . "telegram_config LIMIT 1")->fetch_assoc();

// Statistics
$stats = [
    'reports' => (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports")->fetch_assoc()['c'],
    'technicians' => (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "technicians WHERE is_active=1")->fetch_assoc()['c'],
    'branches' => (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "branches WHERE is_active=1")->fetch_assoc()['c'],
    'avg_score' => (int)round($db->query("SELECT AVG(health_score) as avg FROM " . TABLE_PREFIX . "reports")->fetch_assoc()['avg'] ?? 0),
    'today' => (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports WHERE DATE(submitted_at) = CURDATE()")->fetch_assoc()['c'],
    'week' => (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'],
    'hardware_assets' => $totalAssets,
    'hardware_issues' => $assetsIssues
];

$branchStats = $db->query("SELECT b.name, COUNT(r.id) as total, ROUND(AVG(r.health_score)) as avg_score 
                          FROM " . TABLE_PREFIX . "branches b 
                          LEFT JOIN " . TABLE_PREFIX . "reports r ON b.id = r.branch_id 
                          GROUP BY b.id ORDER BY total DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

$monthlyTrend = $db->query("SELECT DATE_FORMAT(submitted_at, '%Y-%m') as month, COUNT(*) as total, ROUND(AVG(health_score)) as avg_score 
                           FROM " . TABLE_PREFIX . "reports 
                           WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                           GROUP BY month ORDER BY month")->fetch_all(MYSQLI_ASSOC);

$typeStats = $db->query("SELECT type, COUNT(*) as total, ROUND(AVG(health_score)) as avg_score 
                        FROM " . TABLE_PREFIX . "reports GROUP BY type")->fetch_all(MYSQLI_ASSOC);

$recentIssues = $db->query("SELECT r.*, b.name as branch_name 
                           FROM " . TABLE_PREFIX . "reports r 
                           LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id 
                           WHERE r.health_score < 75 ORDER BY r.submitted_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Activity Log
$activityPage = max(1, intval($_GET['activity_page'] ?? 1));
$activityPerPage = 50;
$activityCount = (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "activity_log")->fetch_assoc()['c'];
$activityPagination = paginate($activityCount, $activityPerPage, $activityPage);
$activityLog = $db->query("SELECT * FROM " . TABLE_PREFIX . "activity_log ORDER BY id DESC LIMIT " . $activityPagination['per_page'] . " OFFSET " . $activityPagination['offset'])->fetch_all(MYSQLI_ASSOC);

// Editing data
$editingCategory = isset($_GET['edit_cat']) ? $db->query("SELECT * FROM " . TABLE_PREFIX . "categories WHERE id = " . intval($_GET['edit_cat']))->fetch_assoc() : null;
$editingItem = isset($_GET['edit_item']) ? $db->query("SELECT * FROM " . TABLE_PREFIX . "checklist_items WHERE id = " . intval($_GET['edit_item']))->fetch_assoc() : null;
$editingHardware = isset($_GET['edit_hardware']) ? getHardwareAsset(intval($_GET['edit_hardware'])) : null;

// JSON data for JavaScript
$branchesJson = json_encode($branches, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$techniciansJson = json_encode($technicians, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$typeStatsJson = json_encode($typeStats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$monthlyTrendJson = json_encode($monthlyTrend, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

function buildPagUrl($page) { 
    $p = $_GET; 
    $p['report_page'] = $page; 
    return '?' . http_build_query($p); 
}

function buildActPagUrl($page) { 
    $p = $_GET; 
    $p['activity_page'] = $page; 
    return '?' . http_build_query($p); 
}

// Calculate total checklist items
$totalChecklistItems = (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "checklist_items ci JOIN " . TABLE_PREFIX . "categories c ON ci.category_id=c.id WHERE ci.is_active=1")->fetch_assoc()['c'];

// Healthy reports count
$healthyReports = (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports WHERE health_score>=90")->fetch_assoc()['c'];
$warningReports = (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports WHERE health_score>=75 AND health_score<90")->fetch_assoc()['c'];
$criticalReports = (int)$db->query("SELECT COUNT(*) as c FROM " . TABLE_PREFIX . "reports WHERE health_score<75")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kaldis Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --primary: #4f46e5;
  --primary-dark: #4338ca;
  --primary-light: #818cf8;
  --secondary: #0ea5e9;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #3b82f6;
  
  --bg-primary: #f8fafc;
  --bg-secondary: #f1f5f9;
  --bg-tertiary: #e2e8f0;
  
  --text-primary: #1e293b;
  --text-secondary: #475569;
  --text-tertiary: #64748b;
  
  --border-color: #e2e8f0;
  --border-focus: #cbd5e1;
  
  --card-bg: #ffffff;
  --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
  --card-hover-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
  
  --sidebar-width: 260px;
  --sidebar-collapsed-width: 70px;
  --header-height: 64px;
  
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background-color: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.6;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: var(--bg-secondary);
  border-radius: var(--radius-sm);
}

::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: var(--radius-sm);
}

::-webkit-scrollbar-thumb:hover {
  background: var(--text-tertiary);
}

/* App Layout */
.app {
  display: flex;
  min-height: 100vh;
}

/* Enhanced Sidebar */
.sidebar {
  width: var(--sidebar-width);
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  height: 100vh;
  position: fixed;
  z-index: 100;
  display: flex;
  flex-direction: column;
  transition: width var(--transition-normal);
  box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
}

.sidebar.collapsed {
  width: var(--sidebar-collapsed-width);
}

.sidebar.collapsed .sidebar-label,
.sidebar.collapsed .sidebar-text,
.sidebar.collapsed .sidebar-badge,
.sidebar.collapsed .sidebar-footer-links,
.sidebar.collapsed .sidebar-brand-sub,
.sidebar.collapsed .sidebar-brand-name {
  display: none;
}

.sidebar.collapsed .sidebar-link {
  justify-content: center;
  padding: 14px;
}

.sidebar.collapsed .sidebar-link-icon {
  margin: 0;
}

.sidebar.collapsed .sidebar-logo {
  width: 36px;
  height: 36px;
}

.sidebar-toggle {
  position: absolute;
  top: 20px;
  right: -12px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  transition: all var(--transition-fast);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.sidebar-toggle:hover {
  background: rgba(79, 70, 229, 0.2);
  transform: scale(1.1);
}

.sidebar-toggle i {
  color: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
}

.sidebar-header {
  background: rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.sidebar-logo-wrapper {
  position: relative;
}

.sidebar-logo {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.sidebar-logo:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

.sidebar-brand {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-brand-name {
  font-weight: 700;
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.95);
  letter-spacing: 0.5px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.sidebar-brand-sub {
  font-size: 0.625rem;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-top: 2px;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 16px 0;
}

.sidebar-label {
  padding: 0 24px 8px;
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.5);
  font-weight: 700;
  margin-bottom: 4px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.sidebar-link {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 24px;
  margin: 2px 12px;
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-link::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
  transform: translateX(-100%);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  transform: translateX(4px);
}

.sidebar-link.active {
  background: rgba(79, 70, 229, 0.2);
  color: #fff;
  font-weight: 600;
  box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
}

.sidebar-link.active::before {
  transform: translateX(0);
}

.sidebar-link-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  transition: all 0.3s ease;
}

.sidebar-link:hover .sidebar-link-icon {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.sidebar-link.active .sidebar-link-icon {
  background: rgba(79, 70, 229, 0.3);
}

.sidebar-icon {
  color: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
}

.sidebar-badge {
  margin-left: auto;
  min-width: 24px;
  height: 24px;
  padding: 0 8px;
  border-radius: 9999px;
  background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
  }
}

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  margin-bottom: 16px;
}

.sidebar-footer-links {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sidebar-link.logout {
  color: rgba(239, 68, 68, 0.8);
}

.sidebar-link.logout:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fff;
}

.sidebar-link.logout .sidebar-link-icon {
  background: rgba(239, 68, 68, 0.2);
}

.sidebar-link.logout:hover .sidebar-link-icon {
  background: rgba(239, 68, 68, 0.3);
}

/* Mobile Sidebar Overlay */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 99;
  opacity: 0;
  transition: opacity var(--transition-normal);
}

.sidebar-overlay.active {
  display: block;
  opacity: 1;
}

/* Main Content */
.main {
  flex: 1;
  margin-left: var(--sidebar-width);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: var(--bg-primary);
  transition: margin-left var(--transition-normal);
}

.sidebar.collapsed ~ .main {
  margin-left: var(--sidebar-collapsed-width);
}

/* Header */
.header {
  height: var(--header-height);
  background-color: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.mobile-toggle {
  display: none;
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  background-color: var(--card-bg);
  cursor: pointer;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.mobile-toggle:hover {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
}

.breadcrumb a {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  transition: color var(--transition-fast);
}

.breadcrumb a:hover {
  color: var(--primary);
}

.breadcrumb-separator {
  color: var(--text-tertiary);
  font-size: 0.75rem;
}

.breadcrumb-current {
  color: var(--text-primary);
  font-weight: 600;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
}

.search-container {
  position: relative;
}

.search-input {
  width: 240px;
  padding: 8px 16px 8px 40px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background-color: var(--card-bg);
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.search-input:focus {
  width: 320px;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
  outline: none;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-tertiary);
  cursor: pointer;
}

.header-button {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  background-color: var(--card-bg);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
  position: relative;
}

.header-button:hover {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-focus);
}

.header-button .notification-dot {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--danger);
  border: 2px solid var(--card-bg);
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 12px;
  border-radius: 9999px;
  border: 1px solid var(--border-color);
  background-color: var(--card-bg);
  cursor: pointer;
  transition: all var(--transition-fast);
  position: relative;
}

.user-menu:hover {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  font-size: 0.625rem;
  color: var(--text-tertiary);
  font-weight: 500;
}

.user-menu-arrow {
  font-size: 0.75rem;
  color: var(--text-tertiary);
  transition: transform var(--transition-fast);
}

.user-menu:hover .user-menu-arrow {
  transform: rotate(180deg);
}

.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--card-shadow);
  min-width: 200px;
  z-index: 60;
}

.dropdown-item {
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-primary);
  text-decoration: none;
  transition: background var(--transition-fast);
}

.dropdown-item:hover {
  background-color: var(--bg-secondary);
}

/* Content Area */
.content {
  padding: 24px;
  flex: 1;
}

/* Greeting Section */
.greeting {
  margin-bottom: 32px;
}

.greeting h1 {
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1.25;
  margin-bottom: 4px;
  letter-spacing: -0.025em;
}

.greeting p {
  font-size: 1rem;
  color: var(--text-secondary);
  font-weight: 400;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: all var(--transition-fast);
  box-shadow: var(--card-shadow);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--card-hover-shadow);
}

.stat-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-icon-primary {
  background-color: rgba(79, 70, 229, 0.1);
  color: var(--primary);
}

.stat-icon-secondary {
  background-color: rgba(14, 165, 233, 0.1);
  color: var(--secondary);
}

.stat-icon-success {
  background-color: rgba(16, 185, 129, 0.1);
  color: var(--success);
}

.stat-icon-warning {
  background-color: rgba(245, 158, 11, 0.1);
  color: var(--warning);
}

.stat-icon-danger {
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--danger);
}

.stat-badge {
  font-size: 0.625rem;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 9999px;
  background-color: rgba(79, 70, 229, 0.1);
  color: var(--primary);
  letter-spacing: 0.05em;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
  letter-spacing: -0.025em;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-tertiary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Quick Stats */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.quick-stat {
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all var(--transition-fast);
  box-shadow: var(--card-shadow);
}

.quick-stat:hover {
  border-color: var(--primary);
  box-shadow: var(--card-hover-shadow);
}

.quick-stat-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.quick-stat-indicator-success {
  background-color: var(--success);
}

.quick-stat-indicator-warning {
  background-color: var(--warning);
}

.quick-stat-indicator-danger {
  background-color: var(--danger);
}

.quick-stat-content {
  flex: 1;
}

.quick-stat-label {
  font-size: 0.75rem;
  color: var(--text-tertiary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.quick-stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.025em;
}

/* Cards */
.card {
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all var(--transition-fast);
  box-shadow: var(--card-shadow);
  margin-bottom: 20px;
}

.card:hover {
  box-shadow: var(--card-hover-shadow);
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.card-body {
  padding: 20px;
}

/* Tables */
.table-container {
  overflow-x: auto;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  box-shadow: var(--card-shadow);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background-color: var(--bg-secondary);
  font-weight: 700;
  color: var(--text-tertiary);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-align: left;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
}

td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.875rem;
  color: var(--text-primary);
}

tr:last-child td {
  border-bottom: none;
}

tr:hover td {
  background-color: var(--bg-secondary);
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.badge-primary {
  background-color: rgba(79, 70, 229, 0.1);
  color: var(--primary);
}

.badge-secondary {
  background-color: rgba(14, 165, 233, 0.1);
  color: var(--secondary);
}

.badge-success {
  background-color: rgba(16, 185, 129, 0.1);
  color: var(--success);
}

.badge-warning {
  background-color: rgba(245, 158, 11, 0.1);
  color: var(--warning);
}

.badge-danger {
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--danger);
}

.badge-operational { background: rgba(16, 185, 129, 0.1); color: #059669; }
.badge-maintenance { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.badge-repair { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.badge-decommissioned { background: rgba(100, 116, 139, 0.1); color: #475569; }
.badge-lost { background: rgba(0, 0, 0, 0.1); color: #1e293b; }

/* Health Score */
.health-score {
  display: flex;
  align-items: center;
  gap: 8px;
}

.health-score-value {
  font-weight: 700;
  font-size: 0.875rem;
  min-width: 40px;
}

.health-score-bar {
  flex: 1;
  height: 6px;
  background-color: var(--border-color);
  border-radius: 9999px;
  overflow: hidden;
}

.health-score-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.health-fill-excellent {
  background-color: var(--success);
}

.health-fill-good {
  background-color: var(--warning);
}

.health-fill-fair {
  background-color: var(--warning);
}

.health-fill-poor {
  background-color: var(--danger);
}

/* Forms */
.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary);
  background-color: var(--card-bg);
  transition: all var(--transition-fast);
}

.form-input:hover,
.form-select:hover,
.form-textarea:hover {
  border-color: var(--border-focus);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}

.form-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.form-row .form-group {
  flex: 1;
  min-width: 200px;
}

.form-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
}

.form-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  accent-color: var(--primary);
  cursor: pointer;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--radius-md);
  border: none;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--transition-fast);
  white-space: nowrap;
  line-height: 1.5;
}

.btn:active {
  transform: scale(0.98);
}

.btn-primary {
  background-color: var(--primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
}

.btn-secondary {
  background-color: var(--secondary);
  color: white;
}

.btn-secondary:hover {
  background-color: #0284c7;
  box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2);
}

.btn-success {
  background-color: var(--success);
  color: white;
}

.btn-success:hover {
  background-color: #059669;
  box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
}

.btn-warning {
  background-color: var(--warning);
  color: white;
}

.btn-warning:hover {
  background-color: #d97706;
  box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
}

.btn-danger {
  background-color: var(--danger);
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
  box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
}

.btn-ghost {
  background-color: transparent;
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
}

.btn-ghost:hover {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-focus);
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
}

.btn-xs {
  padding: 4px 8px;
  font-size: 0.75rem;
}

.btn-icon {
  width: 36px;
  height: 36px;
  padding: 0;
  justify-content: center;
  border-radius: var(--radius-sm);
}

/* Alerts */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  margin-bottom: 12px;
  border: 1px solid;
}

.alert-warning {
  background-color: rgba(245, 158, 11, 0.1);
  border-color: rgba(245, 158, 11, 0.2);
}

.alert-danger {
  background-color: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.2);
}

.alert-info {
  background-color: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.2);
}

.alert-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-weight: 700;
  font-size: 0.875rem;
  color: var(--text-primary);
}

.alert-description {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 2px;
}

/* Tabs */
.tab-content {
  display: none;
  animation: fadeIn 0.3s ease;
}

.tab-content.active {
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Empty States */
.empty-state {
  text-align: center;
  padding: 48px 20px;
  color: var(--text-tertiary);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 12px;
  opacity: 0.3;
  display: block;
}

.empty-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.empty-description {
  font-size: 0.875rem;
  color: var(--text-tertiary);
}

/* Checklist */
.checklist-category {
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 16px;
  margin-bottom: 12px;
  transition: all var(--transition-fast);
}

.checklist-category:hover {
  border-color: var(--primary);
}

.checklist-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}

.checklist-title {
  font-size: 1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.checklist-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  margin: 2px 0;
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.checklist-item:hover {
  border-color: var(--primary);
}

.checklist-item-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.checklist-item:hover .checklist-item-actions {
  opacity: 1;
}

/* Activity Log */
.activity-item {
  display: flex;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid var(--border-color);
  transition: all var(--transition-fast);
}

.activity-item:hover {
  background-color: var(--bg-secondary);
  margin: 0 -20px;
  padding-left: 20px;
  padding-right: 20px;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
}

.activity-text strong {
  color: var(--text-primary);
  font-weight: 700;
}

.activity-time {
  font-size: 0.75rem;
  color: var(--text-tertiary);
  margin-top: 4px;
  font-weight: 500;
}

/* Modals */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.modal.active {
  display: flex;
}

.modal-content {
  background-color: var(--card-bg);
  border-radius: var(--radius-xl);
  width: 100%;
  max-height: 85vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid var(--border-color);
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-small {
  max-width: 420px;
}

.modal-medium {
  max-width: 560px;
}

.modal-large {
  max-width: 760px;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border-color);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 700;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  border: none;
  background-color: var(--bg-secondary);
  cursor: pointer;
  font-size: 1.125rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-tertiary);
  transition: all var(--transition-fast);
}

.modal-close:hover {
  background-color: var(--danger);
  color: white;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  background-color: var(--bg-secondary);
}

/* Hardware Asset specific */
.asset-type-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
  margin-right: 8px;
}

.warranty-expiring {
  background-color: rgba(245, 158, 11, 0.1);
  border-left: 3px solid var(--warning);
}

/* Toast Notifications */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
}

.toast {
  pointer-events: auto;
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 280px;
  max-width: 380px;
  animation: toastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast.removing {
  animation: toastOut 0.2s ease forwards;
}

.toast-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.toast-message {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.toast-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: var(--text-tertiary);
  padding: 2px;
  line-height: 1;
}

.toast-success {
  border-left: 4px solid var(--success);
}

.toast-error {
  border-left: 4px solid var(--danger);
}

.toast-warning {
  border-left: 4px solid var(--warning);
}

.toast-info {
  border-left: 4px solid var(--info);
}

@keyframes toastIn {
  from {
    opacity: 0;
    transform: translateX(60px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toastOut {
  to {
    opacity: 0;
    transform: translateX(60px);
  }
}

/* Footer */
.footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: var(--text-tertiary);
  font-weight: 600;
  background-color: var(--card-bg);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .stats-grid,
  .quick-stats {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
  }
  
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
  
  .main {
    margin-left: 0;
  }
  
  .mobile-toggle {
    display: flex;
  }
  
  .sidebar.collapsed ~ .main {
    margin-left: 0;
  }
}

@media (max-width: 768px) {
  .content {
    padding: 16px;
  }
  
  .header {
    padding: 0 16px;
  }
  
  .stats-grid,
  .quick-stats {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .stat-card,
  .quick-stat {
    padding: 14px;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .form-row {
    flex-direction: column;
  }
  
  .form-row .form-group {
    min-width: 100%;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .table-container {
    border-radius: 0;
  }
  
  th, td {
    padding: 10px 12px;
    font-size: 0.75rem;
  }
  
  .footer {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }
  
  .user-menu .user-info {
    display: none;
  }
  
  .search-input {
    width: 160px;
  }
  
  .search-input:focus {
    width: 200px;
  }
}
</style>
</head>
<body>
<div class="toast-container" id="toasts"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<div class="app">
<aside class="sidebar" id="sidebar">
<button class="sidebar-toggle" onclick="toggleSidebar()">
<i class="fas fa-chevron-left"></i>
</button>
<div class="sidebar-header">
<div class="sidebar-logo-wrapper">
<img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" class="sidebar-logo" alt="K">
</div>
<div class="sidebar-brand">
<div class="sidebar-brand-name">Kaldis Coffee</div>
<div class="sidebar-brand-sub">ICT Admin</div>
</div>
</div>
<nav class="sidebar-nav">
<div class="sidebar-label">Overview</div>
<a href="?tab=dashboard" class="sidebar-link <?php if($activeTab=='dashboard') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-chart-line"></i>
</div>
<span class="sidebar-text">Dashboard</span>
<?php if($stats['today']>0): ?>
<span class="sidebar-badge"><?php echo $stats['today']; ?></span>
<?php endif; ?>
</a>
<a href="?tab=reports" class="sidebar-link <?php if($activeTab=='reports') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-file-alt"></i>
</div>
<span class="sidebar-text">Reports</span>
<?php if($stats['today']>0): ?>
<span class="sidebar-badge"><?php echo $stats['today']; ?></span>
<?php endif; ?>
</a>
<div class="sidebar-label">Management</div>
<a href="?tab=branches" class="sidebar-link <?php if($activeTab=='branches') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-building"></i>
</div>
<span class="sidebar-text">Branches</span>
</a>
<a href="?tab=technicians" class="sidebar-link <?php if($activeTab=='technicians') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-tools"></i>
</div>
<span class="sidebar-text">Technicians</span>
</a>
<a href="?tab=checklist" class="sidebar-link <?php if($activeTab=='checklist') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-tasks"></i>
</div>
<span class="sidebar-text">Checklist</span>
</a>
<a href="?tab=hardware" class="sidebar-link <?php if($activeTab=='hardware') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-microchip"></i>
</div>
<span class="sidebar-text">Hardware Audit</span>
<?php if($assetsIssues > 0): ?>
<span class="sidebar-badge"><?php echo $assetsIssues; ?></span>
<?php endif; ?>
</a>
<div class="sidebar-label">System</div>
<a href="?tab=telegram" class="sidebar-link <?php if($activeTab=='telegram') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-robot"></i>
</div>
<span class="sidebar-text">Telegram</span>
</a>
<a href="?tab=activity" class="sidebar-link <?php if($activeTab=='activity') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-history"></i>
</div>
<span class="sidebar-text">Activity Log</span>
</a>
<a href="?tab=settings" class="sidebar-link <?php if($activeTab=='settings') echo 'active'; ?>">
<div class="sidebar-link-icon">
<i class="fas fa-cog"></i>
</div>
<span class="sidebar-text">Settings</span>
</a>
</nav>
<div class="sidebar-footer">
<div class="sidebar-divider"></div>
<div class="sidebar-footer-links">
<a href="index.php" class="sidebar-link">
<div class="sidebar-link-icon">
<i class="fas fa-arrow-left"></i>
</div>
<span class="sidebar-text">Survey</span>
</a>
<a href="logout.php" class="sidebar-link logout">
<div class="sidebar-link-icon">
<i class="fas fa-sign-out-alt"></i>
</div>
<span class="sidebar-text">Sign Out</span>
</a>
</div>
</div>
</aside>

<div class="main">
<header class="header">
<div class="header-left">
<button class="mobile-toggle" onclick="toggleSidebar()">
<i class="fas fa-bars"></i>
</button>
<nav class="breadcrumb">
<?php
$bc = [
    'dashboard' => [['Dashboard']],
    'reports' => [['Dashboard', '?tab=dashboard'], ['Reports']],
    'branches' => [['Dashboard', '?tab=dashboard'], ['Branches']],
    'technicians' => [['Dashboard', '?tab=dashboard'], ['Technicians']],
    'checklist' => [['Dashboard', '?tab=dashboard'], ['Checklist']],
    'hardware' => [['Dashboard', '?tab=dashboard'], ['Hardware Audit']],
    'telegram' => [['Dashboard', '?tab=dashboard'], ['Telegram']],
    'activity' => [['Dashboard', '?tab=dashboard'], ['Activity Log']],
    'settings' => [['Dashboard', '?tab=dashboard'], ['Settings']]
];
foreach($bc[$activeTab] ?? [] as $i=>$cr):
if($i>0):?><span class="breadcrumb-separator">/</span><?php endif;
if(isset($cr[1])):?><a href="<?php echo $cr[1]; ?>"><?php echo $cr[0]; ?></a><?php else:?><span class="breadcrumb-current"><?php echo $cr[0]; ?></span><?php endif;
endforeach;
?>
</nav>
</div>
<div class="header-right">
<div class="search-container">
<i class="fas fa-search search-icon"></i>
<input type="text" class="search-input" placeholder="Search reports..." id="searchInput" onkeyup="handleSearch(event)">
</div>
<button class="header-button" onclick="showNotification()" title="Notifications">
<i class="fas fa-bell"></i>
<span class="notification-dot"></span>
</button>
<button class="header-button" id="darkModeBtn" title="Toggle Dark Mode">
<i class="fas fa-moon"></i>
</button>
<div class="user-menu">
<div class="user-avatar"><?php echo strtoupper(substr($adminUsername,0,1)); ?></div>
<div class="user-info">
<div class="user-name"><?php echo e($adminUsername); ?></div>
<div class="user-role">Administrator</div>
</div>
<i class="fas fa-chevron-down user-menu-arrow"></i>
</div>
<div class="user-dropdown" id="userDropdown" style="display: none;">
<a href="?tab=settings" class="dropdown-item">
<i class="fas fa-cog"></i> Settings
</a>
<a href="?tab=activity" class="dropdown-item">
<i class="fas fa-history"></i> Activity Log
</a>
<div style="height: 1px; background: var(--border-color); margin: 4px 0;"></div>
<a href="logout.php" class="dropdown-item">
<i class="fas fa-sign-out-alt"></i> Sign Out
</a>
</div>
</div>
</header>

<main class="content">
<!-- Dashboard Tab -->
<div id="dashboard-tab" class="tab-content <?php if($activeTab=='dashboard') echo 'active'; ?>">
<div class="greeting">
<h1><?php echo $greeting; ?>, <?php echo e($adminUsername); ?></h1>
<p>Here's what's happening across your ICT infrastructure today.</p>
</div>

<div class="stats-grid">
<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-primary">
<i class="fas fa-file-alt"></i>
</div>
<?php if($stats['today']>0): ?>
<span class="stat-badge">+<?php echo $stats['today']; ?> today</span>
<?php endif; ?>
</div>
<div class="stat-value" data-count="<?php echo $stats['reports']; ?>">0</div>
<div class="stat-label">Total Reports</div>
</div>

<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-secondary">
<i class="fas fa-chart-line"></i>
</div>
</div>
<div class="stat-value" data-count="<?php echo $stats['avg_score']; ?>">0</div>
<div class="stat-label">Avg Health</div>
</div>

<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-success">
<i class="fas fa-building"></i>
</div>
</div>
<div class="stat-value" data-count="<?php echo $stats['branches']; ?>">0</div>
<div class="stat-label">Branches</div>
</div>

<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-warning">
<i class="fas fa-tools"></i>
</div>
</div>
<div class="stat-value" data-count="<?php echo $stats['technicians']; ?>">0</div>
<div class="stat-label">Technicians</div>
</div>

<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-danger">
<i class="fas fa-calendar-week"></i>
</div>
</div>
<div class="stat-value" data-count="<?php echo $stats['week']; ?>">0</div>
<div class="stat-label">This Week</div>
</div>

<div class="stat-card">
<div class="stat-card-header">
<div class="stat-icon stat-icon-primary">
<i class="fas fa-microchip"></i>
</div>
</div>
<div class="stat-value" data-count="<?php echo $stats['hardware_assets']; ?>">0</div>
<div class="stat-label">Hardware Assets</div>
</div>
</div>

<div class="quick-stats">
<div class="quick-stat">
<div class="quick-stat-indicator quick-stat-indicator-success"></div>
<div class="quick-stat-content">
<div class="quick-stat-label">Healthy (90%+)</div>
<div class="quick-stat-value"><?php echo $healthyReports; ?></div>
</div>
</div>

<div class="quick-stat">
<div class="quick-stat-indicator quick-stat-indicator-warning"></div>
<div class="quick-stat-content">
<div class="quick-stat-label">Warning (75-89%)</div>
<div class="quick-stat-value"><?php echo $warningReports; ?></div>
</div>
</div>

<div class="quick-stat">
<div class="quick-stat-indicator quick-stat-indicator-danger"></div>
<div class="quick-stat-content">
<div class="quick-stat-label">Critical (<75%)</div>
<div class="quick-stat-value"><?php echo $criticalReports; ?></div>
</div>
</div>

<div class="quick-stat">
<div class="quick-stat-indicator quick-stat-indicator-warning"></div>
<div class="quick-stat-content">
<div class="quick-stat-label">Assets with Issues</div>
<div class="quick-stat-value"><?php echo $stats['hardware_issues']; ?></div>
</div>
</div>
</div>

<?php if(!empty($recentIssues)): ?>
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
Critical Alerts
</h3>
<span class="badge badge-danger"><?php echo count($recentIssues); ?></span>
</div>
<div class="card-body">
<?php foreach($recentIssues as $ri): ?>
<div class="alert <?php echo $ri['health_score']<50 ? 'alert-danger' : 'alert-warning'; ?>">
<i class="fas <?php echo $ri['health_score']<50 ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'; ?> alert-icon"></i>
<div class="alert-content">
<div class="alert-title"><?php echo e($ri['branch_name']); ?> — <span style="color:<?php echo $ri['health_score']<50 ? 'var(--danger)' : 'var(--warning)'; ?>;font-weight:900;"><?php echo $ri['health_score']; ?>%</span></div>
<div class="alert-description"><?php echo formatDate($ri['submitted_at'], 'M d, Y H:i'); ?> · <?php echo ucfirst($ri['type']); ?></div>
</div>
<button class="btn btn-primary btn-sm" onclick="viewReport(<?php echo $ri['id']; ?>)">View</button>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php if(!empty($expiringWarranties)): ?>
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-shield-alt" style="color: var(--warning);"></i>
Warranty Expiring Soon
</h3>
<span class="badge badge-warning"><?php echo count($expiringWarranties); ?></span>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Asset</th>
<th>Branch</th>
<th>Serial</th>
<th>Warranty Expiry</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach(array_slice($expiringWarranties,0,5) as $warranty): ?>
<tr>
<td><strong><?php echo e($warranty['brand'] . ' ' . $warranty['model']); ?></strong></td>
<td><?php echo e($warranty['branch_name']); ?></td>
<td><code><?php echo e($warranty['serial_number'] ?? '—'); ?></code></td>
<td class="warranty-expiring"><?php echo formatDate($warranty['warranty_expiry'], 'M d, Y'); ?></td>
<td><span class="badge badge-<?php echo $warranty['status']; ?>"><?php echo ucfirst($warranty['status']); ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-chart-line"></i>
Trend Analysis
</h3>
</div>
<div class="card-body">
<div style="height: 300px;">
<canvas id="trendChart"></canvas>
</div>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-chart-pie"></i>
Report Types
</h3>
</div>
<div class="card-body">
<div style="height: 300px;">
<canvas id="typeChart"></canvas>
</div>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-building"></i>
Branch Performance
</h3>
<a href="?tab=reports" class="btn btn-ghost btn-sm">View All →</a>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Branch</th>
<th style="text-align: center">Reports</th>
<th style="text-align: center">Avg</th>
<th>Health</th>
</tr>
</thead>
<tbody>
<?php foreach($branchStats as $bs): ?>
<tr>
<td><strong><?php echo e($bs['name']); ?></strong></td>
<td style="text-align: center">
<span class="badge badge-primary"><?php echo $bs['total']; ?></span>
</td>
<td style="text-align: center; font-weight: 800">
<?php echo $bs['avg_score'] ? $bs['avg_score'] . '%' : '—'; ?>
</td>
<td>
<div class="health-score">
<div class="health-score-bar">
<div class="health-score-fill <?php echo healthBarClass($bs['avg_score']??0); ?>" style="width:<?php echo $bs['avg_score']??0; ?>%"></div>
</div>
<span class="health-score-value" style="min-width: auto"><?php echo $bs['avg_score']??0; ?>%</span>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-file-alt"></i>
Recent Reports
</h3>
<a href="?tab=reports" class="btn btn-ghost btn-sm">View All →</a>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Date</th>
<th>Branch</th>
<th>Technician</th>
<th>Type</th>
<th>Score</th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach(array_slice($reports,0,8) as $r): ?>
<tr style="cursor: pointer;" onclick="viewReport(<?php echo $r['id']; ?>)">
<td><?php echo formatDate($r['submitted_at'], 'M d, Y'); ?></td>
<td><strong><?php echo e($r['branch_name']); ?></strong></td>
<td><?php echo e($r['tech_name']); ?></td>
<td>
<span class="badge <?php echo ($r['type'] ?? '') == 'hardware' ? 'badge-primary' : 'badge-secondary'; ?>">
<?php echo ucfirst($r['type'] ?? 'Unknown'); ?>
</span>
</td>
<td>
<div class="health-score">
<span class="health-score-value"><?php echo $r['health_score']; ?>%</span>
<div class="health-score-bar">
<div class="health-score-fill <?php echo healthBarClass($r['health_score']); ?>" style="width:<?php echo $r['health_score']; ?>%"></div>
</div>
</div>
</td>
<td>
<button class="btn btn-ghost btn-xs btn-icon" onclick="event.stopPropagation(); viewReport(<?php echo $r['id']; ?>)">
<i class="fas fa-eye"></i>
</button>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($reports)): ?>
<tr>
<td colspan="6">
<div class="empty-state">
<i class="fas fa-file-alt empty-icon"></i>
<div class="empty-title">No reports yet</div>
</div>
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<!-- Reports Tab -->
<div id="reports-tab" class="tab-content <?php if($activeTab=='reports') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-file-alt"></i>
Reports
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo $totalReports; ?></span>
</h3>
<div class="card-actions">
<button class="btn btn-secondary btn-sm" onclick="exportCSV()">
<i class="fas fa-download"></i> CSV
</button>
<button class="btn btn-ghost btn-sm" onclick="window.print()">
<i class="fas fa-print"></i> Print
</button>
</div>
</div>
<div class="card-body">
<div class="card" style="margin-bottom: 16px;">
<div class="card-body" style="padding: 16px;">
<div class="form-row" style="margin-bottom: 0;">
<div class="form-group" style="flex: 2;">
<input type="text" class="form-input" id="reportSearch" placeholder="🔍 Search..." value="<?php echo e($reportSearch); ?>" onkeyup="handleSearch(event)">
</div>
<div class="form-group">
<select class="form-select" id="reportType" onchange="applyFilters()">
<option value="">All Types</option>
<option value="hardware" <?php if($reportType=='hardware') echo 'selected'; ?>>Hardware</option>
<option value="software" <?php if($reportType=='software') echo 'selected'; ?>>Software</option>
</select>
</div>
<div class="form-group">
<select class="form-select" id="reportBranch" onchange="applyFilters()">
<option value="">All Branches</option>
<?php foreach($branches as $b): ?>
<option value="<?php echo $b['id']; ?>" <?php if($reportBranch==$b['id']) echo 'selected'; ?>><?php echo e($b['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<input type="date" class="form-input" id="dateFrom" value="<?php echo $reportDateFrom; ?>" onchange="applyFilters()" style="max-width: 150px;">
</div>
<div class="form-group">
<input type="date" class="form-input" id="dateTo" value="<?php echo $reportDateTo; ?>" onchange="applyFilters()" style="max-width: 150px;">
</div>
<div class="form-group" style="display: flex; align-items: flex-end;">
<button class="btn btn-ghost btn-sm" onclick="clearFilters()">
<i class="fas fa-times"></i> Clear
</button>
</div>
</div>
</div>
</div>

<div style="margin-bottom: 12px; font-size: 0.875rem; color: var(--text-tertiary); font-weight: 600;">
Showing <?php echo $totalReports ? ($pagination['offset']+1) : 0; ?>–<?php echo min($pagination['offset']+$reportsPerPage, $totalReports); ?> of <?php echo $totalReports; ?>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th>
<th>Date</th>
<th>Branch</th>
<th>Technician</th>
<th>Manager</th>
<th>Type</th>
<th>Score</th>
<th>Status</th>
<th style="text-align: center">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($reports as $r): ?>
<tr style="cursor: pointer;" onclick="viewReport(<?php echo $r['id']; ?>)">
<td style="color: var(--text-tertiary); font-weight: 700; font-size: 0.875rem;">#<?php echo $r['id']; ?></td>
<td><?php echo formatDate($r['submitted_at'], 'M d, Y H:i'); ?></td>
<td><strong><?php echo e($r['branch_name']); ?></strong></td>
<td><?php echo e($r['tech_name']); ?></td>
<td><?php echo e($r['branch_manager']); ?></td>
<td>
<span class="badge <?php echo ($r['type'] ?? '') == 'hardware' ? 'badge-primary' : 'badge-secondary'; ?>">
<?php echo ucfirst($r['type'] ?? 'Unknown'); ?>
</span>
</td>
<td>
<div class="health-score">
<span class="health-score-value"><?php echo $r['health_score']; ?>%</span>
<div class="health-score-bar">
<div class="health-score-fill <?php echo healthBarClass($r['health_score']); ?>" style="width:<?php echo $r['health_score']; ?>%"></div>
</div>
</div>
</td>
<td><?php echo getHealthBadge($r['health_score']); ?></td>
<td style="text-align: center" onclick="event.stopPropagation()">
<div style="display: flex; gap: 4px; justify-content: center;">
<button class="btn btn-primary btn-xs btn-icon" onclick="viewReport(<?php echo $r['id']; ?>)">
<i class="fas fa-eye"></i>
</button>
<a href="?del_report=<?php echo $r['id']; ?>&tab=reports" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($reports)): ?>
<tr>
<td colspan="9">
<div class="empty-state">
<i class="fas fa-file-alt empty-icon"></i>
<div class="empty-title">No reports found</div>
<div class="empty-description">Adjust your filters</div>
</div>
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if($pagination['total_pages']>1): ?>
<div style="display: flex; justify-content: center; margin-top: 20px; gap: 4px;">
<?php if($pagination['has_prev']): ?>
<a href="<?php echo buildPagUrl($reportPage-1); ?>" class="btn btn-ghost btn-sm">
<i class="fas fa-chevron-left"></i>
</a>
<?php endif; ?>
<?php for($i=max(1,$reportPage-2); $i<=min($pagination['total_pages'], $reportPage+2); $i++): ?>
<a href="<?php echo buildPagUrl($i); ?>" class="btn <?php echo $i==$reportPage ? 'btn-primary' : 'btn-ghost'; ?> btn-sm">
<?php echo $i; ?>
</a>
<?php endfor; ?>
<?php if($pagination['has_next']): ?>
<a href="<?php echo buildPagUrl($reportPage+1); ?>" class="btn btn-ghost btn-sm">
<i class="fas fa-chevron-right"></i>
</a>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- Branches Tab -->
<div id="branches-tab" class="tab-content <?php if($activeTab=='branches') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-plus-circle"></i>
Add Branch
</h3>
</div>
<div class="card-body">
<form method="POST" class="form-row">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="branch_name" class="form-input" required placeholder="Branch name">
</div>
<div class="form-group">
<label class="form-label">Address</label>
<input type="text" name="branch_address" class="form-input" placeholder="Address">
</div>
<div class="form-group">
<label class="form-label">Phone</label>
<input type="text" name="branch_phone" class="form-input" placeholder="+251...">
</div>
<div class="form-group">
<label class="form-label">Manager</label>
<input type="text" name="branch_manager" class="form-input" placeholder="Manager name">
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: flex-end;">
<button type="submit" name="add_branch" class="btn btn-primary">
<i class="fas fa-plus"></i> Add
</button>
</div>
</form>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-building"></i>
Branches
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo count($branches); ?></span>
</h3>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Name</th>
<th>Address</th>
<th>Phone</th>
<th>Manager</th>
<th>Status</th>
<th style="text-align: center">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($branches as $b): ?>
<tr>
<td><strong><?php echo e($b['name']); ?></strong></td>
<td style="white-space: normal; max-width: 180px;"><?php echo e($b['address'] ?? '—'); ?></td>
<td><?php echo e($b['phone'] ?? '—'); ?></td>
<td><?php echo e($b['manager_name'] ?? '—'); ?></td>
<td>
<?php if(($b['is_active'] ?? 1)): ?>
<span class="badge badge-success">Active</span>
<?php else: ?>
<span class="badge badge-danger">Inactive</span>
<?php endif; ?>
</td>
<td style="text-align: center">
<div style="display: flex; gap: 4px; justify-content: center;">
<button class="btn btn-secondary btn-xs btn-icon edit-branch" 
    data-id="<?php echo $b['id']; ?>" 
    data-name="<?php echo e($b['name']); ?>" 
    data-addr="<?php echo e($b['address'] ?? ''); ?>" 
    data-phone="<?php echo e($b['phone'] ?? ''); ?>" 
    data-mgr="<?php echo e($b['manager_name'] ?? ''); ?>" 
    data-active="<?php echo ($b['is_active'] ?? 1) ? 1 : 0; ?>">
<i class="fas fa-edit"></i>
</button>
<a href="?del_branch=<?php echo $b['id']; ?>&tab=branches" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<!-- Technicians Tab -->
<div id="technicians-tab" class="tab-content <?php if($activeTab=='technicians') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-plus-circle"></i>
Add Technician
</h3>
</div>
<div class="card-body">
<form method="POST" class="form-row">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="tech_name" class="form-input" required>
</div>
<div class="form-group">
<label class="form-label">Phone</label>
<input type="text" name="tech_phone" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Email</label>
<input type="email" name="tech_email" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Type</label>
<select name="tech_type" class="form-select">
<option value="hardware">Hardware</option>
<option value="software">Software</option>
</select>
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: flex-end;">
<button type="submit" name="add_tech" class="btn btn-primary">
<i class="fas fa-plus"></i> Add
</button>
</div>
</form>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-tools"></i>
Technicians
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo count($technicians); ?></span>
</h3>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Name</th>
<th>Phone</th>
<th>Email</th>
<th>Type</th>
<th>Default</th>
<th style="text-align: center">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($technicians as $t): ?>
<tr>
<td><strong><?php echo e($t['name']); ?></strong></td>
<td><?php echo e($t['phone'] ?? '—'); ?></td>
<td><?php echo e($t['email'] ?? '—'); ?></td>
<td>
<span class="badge <?php echo ($t['type'] ?? '') == 'hardware' ? 'badge-primary' : 'badge-secondary'; ?>">
<?php echo ucfirst($t['type'] ?? 'Unknown'); ?>
</span>
</td>
<td>
<?php if($t['is_default']): ?>
<span class="badge badge-warning">
<i class="fas fa-star"></i> Default
</span>
<?php else: ?>
<span style="color: var(--text-tertiary)">—</span>
<?php endif; ?>
</td>
<td style="text-align: center">
<div style="display: flex; gap: 4px; justify-content: center;">
<?php if(!$t['is_default']): ?>
<a href="?set_default_tech=<?php echo $t['id']; ?>&type=<?php echo $t['type']; ?>&tab=technicians" class="btn btn-warning btn-xs btn-icon" title="Set default">
<i class="fas fa-star"></i>
</a>
<?php endif; ?>
<button class="btn btn-secondary btn-xs btn-icon edit-tech" 
    data-id="<?php echo $t['id']; ?>" 
    data-name="<?php echo e($t['name']); ?>" 
    data-phone="<?php echo e($t['phone'] ?? ''); ?>" 
    data-email="<?php echo e($t['email'] ?? ''); ?>" 
    data-type="<?php echo $t['type']; ?>" 
    data-active="<?php echo ($t['is_active'] ?? 1) ? 1 : 0; ?>">
<i class="fas fa-edit"></i>
</button>
<a href="?del_tech=<?php echo $t['id']; ?>&tab=technicians" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<!-- Checklist Tab -->
<div id="checklist-tab" class="tab-content <?php if($activeTab=='checklist') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-plus-circle"></i>
Add Category
</h3>
</div>
<div class="card-body">
<form method="POST" class="form-row">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="cat_name" class="form-input" required>
</div>
<div class="form-group" style="flex: 0;">
<label class="form-label">Icon</label>
<input type="text" name="cat_icon" id="cat_icon" class="form-input" value="🔧" style="max-width: 70px;">
</div>
<div class="form-group" style="flex: 0;">
<label class="form-label">Type</label>
<select name="cat_type" id="cat_type" class="form-select">
<option value="hardware">Hardware</option>
<option value="software">Software</option>
</select>
</div>
<div class="form-group">
<label class="form-label">Description</label>
<input type="text" name="cat_description" class="form-input">
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: center;">
<label class="form-checkbox">
<input type="checkbox" name="cat_has_photo">
<i class="fas fa-camera"></i> Photo
</label>
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: flex-end;">
<button type="submit" name="add_category" class="btn btn-primary">
<i class="fas fa-plus"></i> Add
</button>
</div>
</form>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-plus-circle"></i>
Add Item
</h3>
</div>
<div class="card-body">
<form method="POST" class="form-row">
<div class="form-group">
<label class="form-label">Category *</label>
<select name="category_id" class="form-select" required>
<option value="">— Select —</option>
<optgroup label="Hardware">
<?php foreach($hardwareCats as $c): ?>
<option value="<?php echo $c['id']; ?>">
<?php echo $c['icon']; ?> <?php echo e($c['name']); ?>
</option>
<?php endforeach; ?>
</optgroup>
<optgroup label="Software">
<?php foreach($softwareCats as $c): ?>
<option value="<?php echo $c['id']; ?>">
<?php echo $c['icon']; ?> <?php echo e($c['name']); ?>
</option>
<?php endforeach; ?>
</optgroup>
</select>
</div>
<div class="form-group" style="flex: 2;">
<label class="form-label">Question *</label>
<input type="text" name="question" class="form-input" required>
</div>
<div class="form-group">
<label class="form-label">Help Text</label>
<input type="text" name="help_text" class="form-input">
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: flex-end;">
<button type="submit" name="add_item" class="btn btn-primary">
<i class="fas fa-plus"></i> Add
</button>
</div>
</form>
</div>
</div>

<div class="tabs" style="padding: 0; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 4px;">
    <button type="button" class="tab active" onclick="filterChecklistType('all')">📋 All Checklists</button>
    <button type="button" class="tab" onclick="filterChecklistType('hardware')">🔧 Hardware Checklist</button>
    <button type="button" class="tab" onclick="filterChecklistType('software')">💻 Software Checklist</button>
</div>

<?php foreach([['Hardware','🔧',$hardwareCats],['Software','💻',$softwareCats]] as $cg):
list($lb, $ic, $cats) = $cg; ?>
<div class="checklist-type-card" data-type="<?php echo strtolower($lb); ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<?php echo $ic; ?> <?php echo $lb; ?>
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo count($cats); ?></span>
</h3>
</div>
<div class="card-body">
<?php if(empty($cats)): ?>
<div class="empty-state">
<i class="<?php echo $lb=='Hardware' ? 'fas fa-tools' : 'fas fa-laptop-code'; ?> empty-icon"></i>
<div class="empty-title">No <?php echo strtolower($lb); ?> categories</div>
</div>
<?php endif; ?>
<?php foreach($cats as $c): ?>
<div class="checklist-category">
<div class="checklist-header">
<h4 class="checklist-title">
<?php echo $c['icon']; ?> <?php echo e($c['name']); ?>
<?php if($c['has_photo']): ?>
<span class="badge badge-secondary">
<i class="fas fa-camera"></i> Photo
</span>
<?php endif; ?>
<?php if(!($c['is_active'] ?? 1)): ?>
<span class="badge badge-danger">Off</span>
<?php endif; ?>
</h4>
<div style="display: flex; gap: 4px;">
<a href="?edit_cat=<?php echo $c['id']; ?>&tab=checklist" class="btn btn-secondary btn-xs btn-icon">
<i class="fas fa-edit"></i>
</a>
<a href="?del_cat=<?php echo $c['id']; ?>&tab=checklist" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-trash"></i>
</a>
</div>
</div>
<?php 
$itemsStmt = $db->prepare("SELECT * FROM " . TABLE_PREFIX . "checklist_items WHERE category_id = ? ORDER BY display_order");
$itemsStmt->bind_param("i", $c['id']);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php foreach($items as $i): ?>
<div class="checklist-item" <?php if(!($i['is_active'] ?? 1)) echo 'style="opacity: 0.6;"'; ?>>
<span>
<?php echo e($i['question']); ?>
<?php if($i['help_text']): ?>
<br>
<small style="color: var(--text-tertiary); font-size: 0.875rem;"><?php echo e($i['help_text']); ?></small>
<?php endif; ?>
</span>
<div class="checklist-item-actions">
<a href="?edit_item=<?php echo $i['id']; ?>&tab=checklist" class="btn btn-secondary btn-xs btn-icon">
<i class="fas fa-edit"></i>
</a>
<a href="?del_item=<?php echo $i['id']; ?>&tab=checklist" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-times"></i>
</a>
</div>
</div>
<?php endforeach; ?>
<?php if(empty($items)): ?>
<div style="text-align: center; padding: 8px; color: var(--text-tertiary); font-size: 0.875rem;">
No items
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Hardware Audit Tab -->
<div id="hardware-tab" class="tab-content <?php if($activeTab=='hardware') echo 'active'; ?>">
<!-- Add Hardware Asset Form -->
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-plus-circle"></i>
Add Hardware Asset
</h3>
</div>
<div class="card-body">
<form method="POST" class="form-row">
<div class="form-group">
<label class="form-label">Branch *</label>
<select name="asset_branch_id" class="form-select" required>
<option value="">-- Select Branch --</option>
<?php foreach($branches as $b): ?>
<option value="<?php echo $b['id']; ?>"><?php echo e($b['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label class="form-label">Asset Type *</label>
<select name="asset_type" class="form-select" required>
<option value="">-- Select --</option>
<?php foreach($assetTypes as $type): ?>
<option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label class="form-label">Asset Tag</label>
<input type="text" name="asset_tag" class="form-input" placeholder="e.g., KDS-001">
</div>
<div class="form-group">
<label class="form-label">Serial Number</label>
<input type="text" name="serial_number" class="form-input" placeholder="Serial #">
</div>
<div class="form-group">
<label class="form-label">Brand</label>
<input type="text" name="brand" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Model</label>
<input type="text" name="model" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="operational">Operational</option>
<option value="maintenance">Under Maintenance</option>
<option value="repair">In Repair</option>
<option value="decommissioned">Decommissioned</option>
<option value="lost">Lost</option>
</select>
</div>
<div class="form-group" style="flex: 0; display: flex; align-items: flex-end;">
<button type="submit" name="add_hardware" class="btn btn-primary">
<i class="fas fa-plus"></i> Add Asset
</button>
</div>
</form>
</div>
</div>

<!-- Hardware Assets Table -->
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-microchip"></i>
Hardware Assets Inventory
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo $totalAssets; ?></span>
</h3>
<div class="card-actions">
<button class="btn btn-secondary btn-sm" onclick="exportHardwareCSV()">
<i class="fas fa-download"></i> Export CSV
</button>
</div>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Asset Tag</th>
<th>Branch</th>
<th>Type</th>
<th>Brand/Model</th>
<th>Serial Number</th>
<th>Status</th>
<th>Warranty</th>
<th>Last Audit</th>
<th style="text-align: center">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($hardwareAssets as $asset): ?>
<tr>
<td><code><?php echo e($asset['asset_tag'] ?? '—'); ?></code></td>
<td><strong><?php echo e($asset['branch_name']); ?></strong></td>
<td>
<span class="badge badge-primary">
<?php echo ucfirst($asset['asset_type']); ?>
</span>
</td>
<td><?php echo e($asset['brand']); ?> <?php echo e($asset['model']); ?></td>
<td><code><?php echo e($asset['serial_number'] ?? '—'); ?></code></td>
<td>
<span class="badge badge-<?php echo $asset['status']; ?>">
<?php echo ucfirst($asset['status']); ?>
</span>
</td>
<td class="<?php echo $asset['warranty_expiry'] && strtotime($asset['warranty_expiry']) < strtotime('+90 days') ? 'warranty-expiring' : ''; ?>">
<?php echo $asset['warranty_expiry'] ? formatDate($asset['warranty_expiry'], 'M d, Y') : '—'; ?>
</td>
<td><?php echo $asset['last_audit_date'] ? formatDate($asset['last_audit_date'], 'M d, Y') : 'Never'; ?></td>
<td style="text-align: center">
<div style="display: flex; gap: 4px; justify-content: center;">
<a href="?edit_hardware=<?php echo $asset['id']; ?>&tab=hardware" class="btn btn-secondary btn-xs btn-icon">
<i class="fas fa-edit"></i>
</a>
<a href="?del_hardware=<?php echo $asset['id']; ?>&tab=hardware" class="btn btn-danger btn-xs btn-icon delete-btn">
<i class="fas fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($hardwareAssets)): ?>
<tr>
<td colspan="9" style="text-align: center; padding: 40px;">
<i class="fas fa-microchip" style="font-size: 2rem; opacity: 0.3;"></i>
<p>No hardware assets found. Click "Add Asset" to get started.</p>
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Run Hardware Audit -->
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-clipboard-list"></i>
Run Hardware Audit
</h3>
</div>
<div class="card-body">
<form method="POST">
<div class="form-row">
<div class="form-group">
<label class="form-label">Select Branch *</label>
<select name="audit_branch_id" class="form-select" required>
<option value="">-- Select Branch --</option>
<?php foreach($branches as $b): ?>
<option value="<?php echo $b['id']; ?>"><?php echo e($b['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label class="form-label">Auditor Name</label>
<input type="text" name="auditor" class="form-input" value="<?php echo e($adminUsername); ?>" required>
</div>
</div>
<div class="form-group">
<label class="form-label">Audit Summary</label>
<textarea name="audit_summary" class="form-textarea" rows="3" placeholder="Overall summary of the audit..."></textarea>
</div>
<div class="form-group">
<label class="form-label">Recommendations</label>
<textarea name="audit_recommendations" class="form-textarea" rows="3" placeholder="Recommendations for improvement..."></textarea>
</div>
<button type="submit" name="run_hardware_audit" class="btn btn-primary">
<i class="fas fa-play"></i> Start Audit
</button>
</form>
</div>
</div>

<?php if(!empty($needingAudit)): ?>
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-clock"></i>
Assets Needing Audit
<span class="badge badge-warning"><?php echo count($needingAudit); ?></span>
</h3>
</div>
<div class="card-body">
<div class="table-container">
<table>
<thead>
<tr>
<th>Asset</th>
<th>Branch</th>
<th>Type</th>
<th>Last Audit</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($needingAudit as $asset): ?>
<tr>
<td><strong><?php echo e($asset['brand'] . ' ' . $asset['model']); ?></strong></td>
<td><?php echo e($asset['branch_name']); ?></td>
<td><?php echo ucfirst($asset['asset_type']); ?></td>
<td><?php echo $asset['last_audit_date'] ? formatDate($asset['last_audit_date'], 'M d, Y') : 'Never'; ?></td>
<td><span class="badge badge-<?php echo $asset['status']; ?>"><?php echo ucfirst($asset['status']); ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>
</div>

<!-- Edit Hardware Modal -->
<?php if($editingHardware): ?>
<div class="modal active" id="editHardwareModal">
<div class="modal-content modal-medium">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-edit"></i> Edit Hardware Asset
</h3>
<button class="modal-close" onclick="closeEditModal()">
<i class="fas fa-times"></i>
</button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="asset_id" value="<?php echo $editingHardware['id']; ?>">
<div class="form-group">
<label class="form-label">Branch *</label>
<select name="edit_asset_branch_id" class="form-select" required>
<?php foreach($branches as $b): ?>
<option value="<?php echo $b['id']; ?>" <?php echo $b['id'] == $editingHardware['branch_id'] ? 'selected' : ''; ?>><?php echo e($b['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label class="form-label">Asset Type *</label>
<select name="edit_asset_type" class="form-select" required>
<?php foreach($assetTypes as $type): ?>
<option value="<?php echo $type; ?>" <?php echo $type == $editingHardware['asset_type'] ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Asset Tag</label>
<input type="text" name="edit_asset_tag" class="form-input" value="<?php echo e($editingHardware['asset_tag'] ?? ''); ?>">
</div>
<div class="form-group">
<label class="form-label">Serial Number</label>
<input type="text" name="edit_serial_number" class="form-input" value="<?php echo e($editingHardware['serial_number'] ?? ''); ?>">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Brand</label>
<input type="text" name="edit_brand" class="form-input" value="<?php echo e($editingHardware['brand'] ?? ''); ?>">
</div>
<div class="form-group">
<label class="form-label">Model</label>
<input type="text" name="edit_model" class="form-input" value="<?php echo e($editingHardware['model'] ?? ''); ?>">
</div>
</div>
<div class="form-group">
<label class="form-label">Specifications</label>
<textarea name="edit_specifications" class="form-textarea" rows="2"><?php echo e($editingHardware['specifications'] ?? ''); ?></textarea>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Purchase Date</label>
<input type="date" name="edit_purchase_date" class="form-input" value="<?php echo $editingHardware['purchase_date']; ?>">
</div>
<div class="form-group">
<label class="form-label">Warranty Expiry</label>
<input type="date" name="edit_warranty_expiry" class="form-input" value="<?php echo $editingHardware['warranty_expiry']; ?>">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Status</label>
<select name="edit_status" class="form-select">
<option value="operational" <?php echo $editingHardware['status'] == 'operational' ? 'selected' : ''; ?>>Operational</option>
<option value="maintenance" <?php echo $editingHardware['status'] == 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
<option value="repair" <?php echo $editingHardware['status'] == 'repair' ? 'selected' : ''; ?>>In Repair</option>
<option value="decommissioned" <?php echo $editingHardware['status'] == 'decommissioned' ? 'selected' : ''; ?>>Decommissioned</option>
<option value="lost" <?php echo $editingHardware['status'] == 'lost' ? 'selected' : ''; ?>>Lost</option>
</select>
</div>
<div class="form-group">
<label class="form-label">Assigned To</label>
<input type="text" name="edit_assigned_to" class="form-input" value="<?php echo e($editingHardware['assigned_to'] ?? ''); ?>">
</div>
</div>
<div class="form-group">
<label class="form-label">Location</label>
<input type="text" name="edit_location" class="form-input" value="<?php echo e($editingHardware['location'] ?? ''); ?>">
</div>
<div class="form-group">
<label class="form-label">Notes</label>
<textarea name="edit_notes" class="form-textarea" rows="2"><?php echo e($editingHardware['notes'] ?? ''); ?></textarea>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
<button type="submit" name="edit_hardware" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>
<script>
function closeEditModal() {
    window.location.href = '?tab=hardware';
}
</script>
<?php endif; ?>

<!-- Telegram Tab -->
<div id="telegram-tab" class="tab-content <?php if($activeTab=='telegram') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-robot"></i>
Telegram Bot
</h3>
<?php if(($tg['enabled'] ?? 0)): ?>
<span class="badge badge-success">Connected</span>
<?php else: ?>
<span class="badge badge-danger">Off</span>
<?php endif; ?>
</div>
<div class="card-body" style="max-width: 440px;">
<form method="POST">
<div class="form-group">
<label class="form-label">Bot Token *</label>
<input type="text" name="bot_token" class="form-input" value="<?php echo e($tg['bot_token'] ?? ''); ?>">
<div style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px;">
From @BotFather
</div>
</div>
<div class="form-group">
<label class="form-label">Admin Chat ID *</label>
<input type="text" name="admin_chat_id" class="form-input" value="<?php echo e($tg['admin_chat_id'] ?? ''); ?>">
<div style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px;">
From @userinfobot
</div>
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="enabled" <?php if(($tg['enabled'] ?? 0)) echo 'checked'; ?>>
<i class="fas fa-bell"></i> Enable notifications
</label>
</div>
<div style="display: flex; gap: 8px; margin-top: 16px;">
<button type="submit" name="save_telegram" class="btn btn-primary">
<i class="fas fa-save"></i> Save
</button>
<button type="submit" name="test_telegram" class="btn btn-secondary">
<i class="fas fa-flask"></i> Test
</button>
</div>
</form>
</div>
</div>
</div>

<!-- Activity Tab -->
<div id="activity-tab" class="tab-content <?php if($activeTab=='activity') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-history"></i>
Activity
<span style="color: var(--text-tertiary); font-weight: 600; font-size: 0.875rem;"><?php echo $activityCount; ?></span>
</h3>
<div class="card-actions">
<a href="?clear_log=30&tab=activity" class="btn btn-warning btn-sm">
<i class="fas fa-trash-alt"></i> Clear 30d
</a>
<a href="?clear_log=0&tab=activity" class="btn btn-danger btn-sm">
<i class="fas fa-trash"></i> Clear All
</a>
</div>
</div>
<div class="card-body">
<?php if(!empty($activityLog)): ?>
<?php foreach($activityLog as $log): 
    // Decode the action JSON
    $actionData = json_decode($log['action'], true);
    $actionType = $actionData['action'] ?? $log['action'];
    $details = $actionData['details'] ?? '';
    $entityType = $actionData['entity_type'] ?? '';
    $entityId = $actionData['entity_id'] ?? '';
    $adminName = $actionData['admin'] ?? $log['admin_id'] ?? 'System';
?>
<div class="activity-item">
<div class="activity-icon" style="background: <?php echo getActivityColor($actionType); ?>; color: white;">
<i class="fas <?php echo getActivityIcon($actionType); ?>"></i>
</div>
<div class="activity-content">
<div class="activity-text">
<strong><?php echo e($adminName); ?></strong> — <?php echo formatActivityAction($actionType); ?>
<?php if(!empty($details)): ?>
<br>
<small style="color: var(--text-tertiary);"><?php echo e($details); ?></small>
<?php endif; ?>
</div>
<div class="activity-time">
<?php echo formatDate($log['created_at'], 'M d, Y H:i:s'); ?>
<?php if(!empty($log['ip_address'])): ?>
· <?php echo e($log['ip_address']); ?>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
<?php if($activityPagination['total_pages']>1): ?>
<div style="display: flex; justify-content: center; margin-top: 20px; gap: 4px;">
<?php if($activityPagination['has_prev']): ?>
<a href="<?php echo buildActPagUrl($activityPage-1); ?>" class="btn btn-ghost btn-sm">
<i class="fas fa-chevron-left"></i>
</a>
<?php endif; ?>
<?php for($i=max(1,$activityPage-2); $i<=min($activityPagination['total_pages'], $activityPage+2); $i++): ?>
<a href="<?php echo buildActPagUrl($i); ?>" class="btn <?php echo $i==$activityPage ? 'btn-primary' : 'btn-ghost'; ?> btn-sm">
<?php echo $i; ?>
</a>
<?php endfor; ?>
<?php if($activityPagination['has_next']): ?>
<a href="<?php echo buildActPagUrl($activityPage+1); ?>" class="btn btn-ghost btn-sm">
<i class="fas fa-chevron-right"></i>
</a>
<?php endif; ?>
</div>
<?php endif; ?>
<?php else: ?>
<div class="empty-state">
<i class="fas fa-history empty-icon"></i>
<div class="empty-title">No activity</div>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- Settings Tab -->
<div id="settings-tab" class="tab-content <?php if($activeTab=='settings') echo 'active'; ?>">
<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-lock"></i>
Change Password
</h3>
</div>
<div class="card-body" style="max-width: 440px;">
<form method="POST">
<div class="form-group">
<label class="form-label">Current *</label>
<input type="password" name="current_password" class="form-input" required>
</div>
<div class="form-group">
<label class="form-label">New * <small style="font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--text-tertiary);">(min 6)</small></label>
<input type="password" name="new_password" class="form-input" required minlength="6">
</div>
<div class="form-group">
<label class="form-label">Confirm *</label>
<input type="password" name="confirm_password" class="form-input" required>
</div>
<button type="submit" name="change_password" class="btn btn-primary" style="margin-top: 4px;">
<i class="fas fa-key"></i> Change
</button>
</form>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-database"></i>
Data
</h3>
</div>
<div class="card-body">
<a href="?export_backup=1" class="btn btn-secondary">
<i class="fas fa-download"></i> Export Backup (JSON)
</a>
<p style="font-size: 0.875rem; color: var(--text-tertiary); margin-top: 8px;">
Branches, technicians, categories, items, reports, hardware assets, telegram config.
</p>
</div>
</div>

<div class="card">
<div class="card-header">
<h3 class="card-title">
<i class="fas fa-info-circle"></i>
System
</h3>
</div>
<div class="card-body">
<table style="width: 100%; font-size: 0.875rem;">
<tr>
<td style="width: 160px; font-weight: 600; color: var(--text-secondary);">PHP</td>
<td><code style="background: var(--bg-secondary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.875rem; border: 1px solid var(--border-color);"><?php echo PHP_VERSION; ?></code></td>
</tr>
<tr>
<td style="font-weight: 600; color: var(--text-secondary);">MySQL</td>
<td><code style="background: var(--bg-secondary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.875rem; border: 1px solid var(--border-color);"><?php echo $db->server_info; ?></code></td>
</tr>
<tr>
<td style="font-weight: 600; color: var(--text-secondary);">Database</td>
<td><code style="background: var(--bg-secondary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.875rem; border: 1px solid var(--border-color);"><?php echo DB_NAME; ?></code></td>
</tr>
<tr>
<td style="font-weight: 600; color: var(--text-secondary);">Prefix</td>
<td><code style="background: var(--bg-secondary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.875rem; border: 1px solid var(--border-color);"><?php echo TABLE_PREFIX; ?></code></td>
</tr>
<tr>
<td style="font-weight: 600; color: var(--text-secondary);">URL</td>
<td><a href="<?php echo SITE_URL; ?>" target="_blank" style="color: var(--primary); font-weight: 600; font-size: 0.875rem;"><?php echo SITE_URL; ?></a></td>
</tr>
<tr>
<td style="font-weight: 600; color: var(--text-secondary);">Time</td>
<td><?php echo date('Y-m-d H:i:s'); ?></td>
</tr>
</table>
</div>
</div>
</div>
</main>

<footer class="footer">
<span>© <?php echo date('Y'); ?> Kaldis Coffee — ICT</span>
<span><?php echo count($branches); ?> branches · <?php echo count($technicians); ?> techs · <?php echo $stats['reports']; ?> reports · <?php echo $totalAssets; ?> assets</span>
</footer>
</div>

<!-- Modals -->
<!-- Modals -->
<!-- Edit Category Modal -->
<?php if($editingCategory): ?>
<div class="modal active" id="editCategoryModal">
<div class="modal-content modal-medium">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-edit"></i> Edit Category
</h3>
<button class="modal-close" onclick="closeEditCatModal()">
<i class="fas fa-times"></i>
</button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="cat_id" value="<?php echo $editingCategory['id']; ?>">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="edit_cat_name" class="form-input" value="<?php echo e($editingCategory['name']); ?>" required>
</div>
<div class="form-group">
<label class="form-label">Icon</label>
<input type="text" name="edit_cat_icon" class="form-input" value="<?php echo e($editingCategory['icon']); ?>">
</div>
<div class="form-group">
<label class="form-label">Type</label>
<select name="edit_cat_type" class="form-select">
<option value="hardware" <?php echo $editingCategory['type'] == 'hardware' ? 'selected' : ''; ?>>Hardware</option>
<option value="software" <?php echo $editingCategory['type'] == 'software' ? 'selected' : ''; ?>>Software</option>
</select>
</div>
<div class="form-group">
<label class="form-label">Description</label>
<input type="text" name="edit_cat_description" class="form-input" value="<?php echo e($editingCategory['description'] ?? ''); ?>">
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="edit_cat_has_photo" <?php echo $editingCategory['has_photo'] ? 'checked' : ''; ?>>
<i class="fas fa-camera"></i> Photo Required
</label>
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="edit_cat_active" <?php echo $editingCategory['is_active'] ? 'checked' : ''; ?>>
Active
</label>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeEditCatModal()">Cancel</button>
<button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>
<script>
function closeEditCatModal() {
    window.location.href = '?tab=checklist';
}
</script>
<?php endif; ?>

<!-- Edit Checklist Item Modal -->
<?php if($editingItem): ?>
<div class="modal active" id="editItemModal">
<div class="modal-content modal-medium">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-edit"></i> Edit Checklist Question
</h3>
<button class="modal-close" onclick="closeEditItemModal()">
<i class="fas fa-times"></i>
</button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="item_id" value="<?php echo $editingItem['id']; ?>">
<div class="form-group">
<label class="form-label">Category *</label>
<select name="edit_category_id" class="form-select" required>
<optgroup label="Hardware">
<?php foreach($hardwareCats as $c): ?>
<option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $editingItem['category_id'] ? 'selected' : ''; ?>>
<?php echo $c['icon']; ?> <?php echo e($c['name']); ?>
</option>
<?php endforeach; ?>
</optgroup>
<optgroup label="Software">
<?php foreach($softwareCats as $c): ?>
<option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $editingItem['category_id'] ? 'selected' : ''; ?>>
<?php echo $c['icon']; ?> <?php echo e($c['name']); ?>
</option>
<?php endforeach; ?>
</optgroup>
</select>
</div>
<div class="form-group">
<label class="form-label">Question *</label>
<input type="text" name="edit_question" class="form-input" value="<?php echo e($editingItem['question']); ?>" required>
</div>
<div class="form-group">
<label class="form-label">Help Text</label>
<input type="text" name="edit_help_text" class="form-input" value="<?php echo e($editingItem['help_text'] ?? ''); ?>">
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="edit_item_active" <?php echo $editingItem['is_active'] ? 'checked' : ''; ?>>
Active
</label>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeEditItemModal()">Cancel</button>
<button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>
<script>
function closeEditItemModal() {
    window.location.href = '?tab=checklist';
}
</script>
<?php endif; ?>

<div class="modal" id="reportModal">
<div class="modal-content modal-large">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-file-alt"></i> Report
</h3>
<button class="modal-close" onclick="closeModal('reportModal')">
<i class="fas fa-times"></i>
</button>
</div>
<div class="modal-body" id="reportModalBody">
<div class="empty-state">
<i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
<div class="empty-title">Loading...</div>
</div>
</div>
</div>
</div>

<div class="modal" id="branchModal">
<div class="modal-content modal-medium">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-edit"></i> Edit Branch
</h3>
<button class="modal-close" onclick="closeModal('branchModal')">
<i class="fas fa-times"></i>
</button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="branch_id" id="editBranchId">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="edit_branch_name" id="editBranchName" class="form-input" required>
</div>
<div class="form-group">
<label class="form-label">Address</label>
<input type="text" name="edit_branch_address" id="editBranchAddress" class="form-input">
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Phone</label>
<input type="text" name="edit_branch_phone" id="editBranchPhone" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Manager</label>
<input type="text" name="edit_branch_manager" id="editBranchManager" class="form-input">
</div>
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="edit_branch_active" id="editBranchActive" checked>
<i class="fas fa-check-circle"></i> Active
</label>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeModal('branchModal')">Cancel</button>
<button type="submit" name="edit_branch" class="btn btn-primary">
<i class="fas fa-save"></i> Save
</button>
</div>
</form>
</div>
</div>

<div class="modal" id="technicianModal">
<div class="modal-content modal-medium">
<div class="modal-header">
<h3 class="modal-title">
<i class="fas fa-edit"></i> Edit Technician
</h3>
<button class="modal-close" onclick="closeModal('technicianModal')">
<i class="fas fa-times"></i>
</button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="tech_id" id="editTechId">
<div class="form-group">
<label class="form-label">Name *</label>
<input type="text" name="edit_tech_name" id="editTechName" class="form-input" required>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Phone</label>
<input type="text" name="edit_tech_phone" id="editTechPhone" class="form-input">
</div>
<div class="form-group">
<label class="form-label">Email</label>
<input type="email" name="edit_tech_email" id="editTechEmail" class="form-input">
</div>
</div>
<div class="form-group">
<label class="form-label">Type</label>
<select name="edit_tech_type" id="editTechType" class="form-select">
<option value="hardware">Hardware</option>
<option value="software">Software</option>
</select>
</div>
<div class="form-group">
<label class="form-checkbox">
<input type="checkbox" name="edit_tech_active" id="editTechActive" checked>
<i class="fas fa-check-circle"></i> Active
</label>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeModal('technicianModal')">Cancel</button>
<button type="submit" name="edit_tech" class="btn btn-primary">
<i class="fas fa-save"></i> Save
</button>
</div>
</form>
</div>
</div>

<div class="modal" id="confirmModal">
<div class="modal-content modal-small">
<div class="modal-body" style="text-align: center; padding: 32px 20px;">
<i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning); margin-bottom: 16px;"></i>
<div style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px;">Delete this item?</div>
<div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 24px;">This cannot be undone.</div>
<div style="display: flex; gap: 12px; justify-content: center;">
<button class="btn btn-ghost" onclick="closeModal('confirmModal')" style="min-width: 100px;">Cancel</button>
<button class="btn btn-danger" id="confirmDeleteBtn" style="min-width: 100px;">Delete</button>
</div>
</div>
</div>
</div>
</div>

<script>
// Global variables
let deleteCallback = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  // Initialize counters
  animateCounters();
  
  // Initialize charts if on dashboard
  if (document.getElementById('trendChart')) {
    initCharts();
  }
  
  // Handle delete confirmation
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const href = this.getAttribute('href');
      if (href) {
        confirmDelete(() => {
          window.location.href = href;
        });
      }
    });
  });
  
  // Handle edit branch
  document.querySelectorAll('.edit-branch').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('editBranchId').value = this.dataset.id;
      document.getElementById('editBranchName').value = this.dataset.name;
      document.getElementById('editBranchAddress').value = this.dataset.addr;
      document.getElementById('editBranchPhone').value = this.dataset.phone;
      document.getElementById('editBranchManager').value = this.dataset.mgr;
      document.getElementById('editBranchActive').checked = this.dataset.active === '1';
      openModal('branchModal');
    });
  });
  
  // Handle edit technician
  document.querySelectorAll('.edit-tech').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('editTechId').value = this.dataset.id;
      document.getElementById('editTechName').value = this.dataset.name;
      document.getElementById('editTechPhone').value = this.dataset.phone;
      document.getElementById('editTechEmail').value = this.dataset.email;
      document.getElementById('editTechType').value = this.dataset.type;
      document.getElementById('editTechActive').checked = this.dataset.active === '1';
      openModal('technicianModal');
    });
  });
  
  // Handle user dropdown
  const userMenu = document.querySelector('.user-menu');
  const userDropdown = document.getElementById('userDropdown');
  
  if (userMenu && userDropdown) {
    userMenu.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const isVisible = userDropdown.style.display === 'block';
      userDropdown.style.display = isVisible ? 'none' : 'block';
    });
    
    document.addEventListener('click', function(e) {
      if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.style.display = 'none';
      }
    });
  }
  
  // Handle dark mode button
  const darkModeBtn = document.getElementById('darkModeBtn');
  if (darkModeBtn) {
    darkModeBtn.addEventListener('click', toggleDarkMode);
  }
  
  // Show toast if exists
  <?php if($toast): ?>
  showToast('<?php echo addslashes($toast['message']); ?>', '<?php echo $toast['type']; ?>');
  <?php endif; ?>
});

// Sidebar toggle
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  if (window.innerWidth <= 1024) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  } else {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
  }
}

// Initialize sidebar state
if (localStorage.getItem('sidebarCollapsed') === 'true') {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) sidebar.classList.add('collapsed');
}

// Modal functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove('active');
}

// Delete confirmation
function confirmDelete(callback) {
  deleteCallback = callback;
  openModal('confirmModal');
}

const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
if (confirmDeleteBtn) {
  confirmDeleteBtn.addEventListener('click', function() {
    if (deleteCallback) {
      deleteCallback();
      deleteCallback = null;
    }
    closeModal('confirmModal');
  });
}

// View report
function viewReport(id) {
  openModal('reportModal');
  const modalBody = document.getElementById('reportModalBody');
  if (modalBody) {
    modalBody.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><div class="empty-title">Loading...</div></div>';
  }
  
  fetch('get_report.php?id=' + id)
    .then(response => response.json())
    .then(data => {
      if (!modalBody) return;
      
      if (data.error) {
        modalBody.innerHTML = `
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle alert-icon"></i>
            <div class="alert-content">
              <div class="alert-title">Error</div>
              <div class="alert-description">${data.error}</div>
            </div>
          </div>
        `;
        return;
      }
      
      const scoreColor = data.score >= 90 ? 'var(--success)' : 
                        data.score >= 75 ? 'var(--warning)' : 
                        data.score >= 50 ? 'var(--warning)' : 'var(--danger)';
      const scoreClass = data.score >= 90 ? 'health-fill-excellent' : 
                        data.score >= 75 ? 'health-fill-good' : 
                        data.score >= 50 ? 'health-fill-fair' : 'health-fill-poor';
      
      let html = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
          <div style="background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div class="form-label">Branch</div>
            <p style="font-weight: 600; font-size: 1rem; margin-top: 4px;">${escapeHtml(data.branch) || '—'}</p>
          </div>
          <div style="background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div class="form-label">Technician</div>
            <p style="font-weight: 600; font-size: 1rem; margin-top: 4px;">${escapeHtml(data.technician) || '—'}</p>
          </div>
          <div style="background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div class="form-label">Type</div>
            <span class="badge ${data.type === 'hardware' ? 'badge-primary' : 'badge-secondary'}" style="margin-top: 4px; display: inline-flex;">${data.type ? data.type.toUpperCase() : '—'}</span>
          </div>
          <div style="background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div class="form-label">Submitted</div>
            <p style="font-weight: 600; font-size: 1rem; margin-top: 4px;">${escapeHtml(data.submitted_at) || '—'}</p>
          </div>
          <div style="background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div class="form-label">Health Score</div>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
              <span style="font-size: 2rem; font-weight: 700; color: ${scoreColor}; letter-spacing: -0.8px;">${data.score}%</span>
              <div class="health-score-bar" style="flex: 1; height: 8px;">
                <div class="health-score-fill ${scoreClass}" style="width: ${data.score}%"></div>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Display statistics summary
      if (data.statistics) {
        html += `
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px;">
            <div style="background: var(--bg-secondary); padding: 12px; border-radius: var(--radius-md); text-align: center;">
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">${data.statistics.ok_items}</div>
              <div style="font-size: 0.75rem; color: var(--text-tertiary);">OK Items</div>
            </div>
            <div style="background: var(--bg-secondary); padding: 12px; border-radius: var(--radius-md); text-align: center;">
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);">${data.statistics.issue_items}</div>
              <div style="font-size: 0.75rem; color: var(--text-tertiary);">Issues</div>
            </div>
            <div style="background: var(--bg-secondary); padding: 12px; border-radius: var(--radius-md); text-align: center;">
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-tertiary);">${data.statistics.skipped_items}</div>
              <div style="font-size: 0.75rem; color: var(--text-tertiary);">Skipped</div>
            </div>
            <div style="background: var(--bg-secondary); padding: 12px; border-radius: var(--radius-md); text-align: center;">
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${data.statistics.completion_rate}%</div>
              <div style="font-size: 0.75rem; color: var(--text-tertiary);">Completion</div>
            </div>
          </div>
        `;
      }
      
      // Display checklist responses by category
      if (data.responses && data.responses.length > 0) {
        html += '<div style="margin-bottom: 24px;"><div class="form-label" style="margin-bottom: 12px;">📋 Checklist Responses</div>';
        
        let currentCategory = '';
        
        data.responses.forEach(response => {
          if (response.category_name !== currentCategory) {
            if (currentCategory) html += '</div>';
            currentCategory = response.category_name;
            html += `<div class="checklist-category" style="margin-bottom: 16px;">
              <div class="checklist-header">
                <h4 style="font-size: 1rem; margin: 0 0 12px 0;">${escapeHtml(response.category_icon) || '📋'} ${escapeHtml(response.category_name)}</h4>
              </div>`;
          }
          
          const responseClass = response.response_class === 'success' ? 'badge-success' : 
                               response.response_class === 'danger' ? 'badge-danger' : 'badge-secondary';
          
          html += `
            <div class="checklist-item" style="margin-bottom: 8px;">
              <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 4px;">${escapeHtml(response.question)}</div>
                ${response.help_text ? `<div style="font-size: 0.75rem; color: var(--text-tertiary); margin-bottom: 4px;">💡 ${escapeHtml(response.help_text)}</div>` : ''}
                ${response.notes ? `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; padding: 4px 8px; background: var(--bg-secondary); border-radius: var(--radius-sm);">📝 Note: ${escapeHtml(response.notes)}</div>` : ''}
              </div>
              <div style="margin-left: 12px;">
                <span class="badge ${responseClass}" style="font-size: 0.875rem; padding: 6px 12px;">${escapeHtml(response.response_text)}</span>
              </div>
            </div>
          `;
        });
        
        if (currentCategory) html += '</div>';
        html += '</div>';
      }
      
      // Display issues, actions, recommendations
      const sections = [
        ['🚨 Issues Found', data.issues, 'alert-danger'],
        ['🔧 Actions Taken', data.actions, 'alert-info'],
        ['💡 Recommendations', data.recommendations, 'alert-warning']
      ];
      
      let hasContent = false;
      sections.forEach(section => {
        if (section[1] && section[1].trim()) {
          if (!hasContent) {
            html += '<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">';
            hasContent = true;
          }
          html += `
            <div class="alert ${section[2]}" style="margin-bottom: 16px;">
              <div class="alert-content">
                <div class="alert-title" style="margin-bottom: 8px;">${section[0]}</div>
                <div class="alert-description" style="white-space: pre-line;">${escapeHtml(section[1]).replace(/\n/g, '<br>')}</div>
              </div>
            </div>
          `;
        }
      });
      
      // Display photos
      if (data.photos && data.photos.length > 0) {
        html += `
          <div style="margin-top: 24px;">
            <div class="form-label" style="margin-bottom: 12px;">📷 Photos (${data.photos.length})</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
        `;
        
        data.photos.forEach((photo, index) => {
          html += `
            <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-secondary);">
              <img src="${escapeHtml(photo.path)}" alt="Photo ${index + 1}" 
                   style="width: 100%; height: 180px; object-fit: cover; display: block; cursor: pointer;" 
                   onclick="window.open(this.src, '_blank')"
                   onerror="this.style.display='none'; this.parentElement.querySelector('.photo-error').style.display='flex';">
              <div class="photo-error" style="display: none; height: 180px; align-items: center; justify-content: center; color: var(--text-tertiary);">
                <i class="fas fa-image" style="font-size: 2rem;"></i>
                <span style="margin-left: 8px;">Image not available</span>
              </div>
              ${photo.caption ? `<div style="padding: 8px; font-size: 0.75rem; color: var(--text-secondary); border-top: 1px solid var(--border-color);">${escapeHtml(photo.caption)}</div>` : ''}
            </div>
          `;
        });
        
        html += '</div></div>';
      }
      
      modalBody.innerHTML = html;
    })
    .catch(error => {
      console.error('Error:', error);
      if (modalBody) {
        modalBody.innerHTML = `
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle alert-icon"></i>
            <div class="alert-content">
              <div class="alert-title">Network Error</div>
              <div class="alert-description">Failed to load report. Please try again.</div>
            </div>
          </div>
        `;
      }
    });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Filter functions
function applyFilters() {
  const params = new URLSearchParams();
  params.set('tab', 'reports');
  
  const search = document.getElementById('reportSearch')?.value;
  if (search) params.set('report_search', search);
  
  const type = document.getElementById('reportType')?.value;
  if (type) params.set('report_type', type);
  
  const branch = document.getElementById('reportBranch')?.value;
  if (branch) params.set('report_branch', branch);
  
  const dateFrom = document.getElementById('dateFrom')?.value;
  if (dateFrom) params.set('date_from', dateFrom);
  
  const dateTo = document.getElementById('dateTo')?.value;
  if (dateTo) params.set('date_to', dateTo);
  
  window.location.href = '?' + params.toString();
}

function clearFilters() {
  window.location.href = '?tab=reports';
}

function exportCSV() {
  const params = new URLSearchParams();
  const search = document.getElementById('reportSearch')?.value;
  const type = document.getElementById('reportType')?.value;
  const branch = document.getElementById('reportBranch')?.value;
  const dateFrom = document.getElementById('dateFrom')?.value;
  const dateTo = document.getElementById('dateTo')?.value;
  
  if (search) params.set('search', search);
  if (type) params.set('type', type);
  if (branch) params.set('branch', branch);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);
  params.set('export', 'csv');
  
  window.location.href = 'export_reports.php?' + params.toString();
}

function exportHardwareCSV() {
  window.location.href = 'export_hardware.php';
}

// Search handling
function handleSearch(event) {
  if (event.key === 'Enter') {
    applyFilters();
  }
}

// Toast notifications
function showToast(message, type = 'success') {
  const toastContainer = document.getElementById('toasts');
  if (!toastContainer) return;
  
  type = type || 'success';
  const icons = {
    success: 'fa-check-circle',
    error: 'fa-exclamation-circle',
    warning: 'fa-exclamation-triangle',
    info: 'fa-info-circle'
  };
  
  const colors = {
    success: 'var(--success)',
    error: 'var(--danger)',
    warning: 'var(--warning)',
    info: 'var(--info)'
  };
  
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="fas ${icons[type]} toast-icon" style="color: ${colors[type]}"></i>
    <span class="toast-message">${escapeHtml(message)}</span>
    <button class="toast-close" onclick="this.parentElement.remove()">
      <i class="fas fa-times"></i>
    </button>
  `;
  
  toastContainer.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 200);
  }, 4000);
}

// Animate counters
function animateCounters() {
  document.querySelectorAll('.stat-value[data-count]').forEach(element => {
    const target = parseInt(element.dataset.count);
    if (isNaN(target)) {
      element.textContent = '0';
      return;
    }
    
    let current = 0;
    const increment = Math.ceil(target / 50);
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      element.textContent = current.toLocaleString();
    }, 20);
  });
}

// Initialize charts
function initCharts() {
  // Get chart data from PHP
  const monthlyTrend = <?php echo json_encode($monthlyTrend); ?>;
  const typeStats = <?php echo json_encode($typeStats); ?>;
  
  // Trend chart
  const trendCtx = document.getElementById('trendChart')?.getContext('2d');
  if (trendCtx && monthlyTrend && monthlyTrend.length > 0) {
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: monthlyTrend.map(item => item.month),
        datasets: [{
          label: 'Reports',
          data: monthlyTrend.map(item => item.total),
          borderColor: 'var(--primary)',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 5,
          pointBackgroundColor: 'var(--primary)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }, {
          label: 'Avg Score %',
          data: monthlyTrend.map(item => item.avg_score || 0),
          borderColor: 'var(--secondary)',
          backgroundColor: 'transparent',
          borderDash: [5, 5],
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: 'var(--secondary)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              font: {
                size: 12,
                weight: '600'
              },
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: 'var(--card-bg)',
            titleColor: 'var(--text-primary)',
            bodyColor: 'var(--text-primary)',
            borderColor: 'var(--border-color)',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            titleFont: {
              weight: '700'
            }
          }
        },
        scales: {
          x: {
            grid: {
              color: 'var(--border-color)',
              drawBorder: false
            },
            ticks: {
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            position: 'left',
            grid: {
              color: 'var(--border-color)',
              drawBorder: false
            },
            ticks: {
              font: {
                size: 11
              }
            },
            title: {
              display: true,
              text: 'Reports',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y1: {
            position: 'right',
            min: 0,
            max: 100,
            grid: {
              display: false
            },
            ticks: {
              font: {
                size: 11
              },
              callback: function(value) {
                return value + '%';
              }
            },
            title: {
              display: true,
              text: 'Score',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }
  
  // Type chart
  const typeCtx = document.getElementById('typeChart')?.getContext('2d');
  if (typeCtx && typeStats && typeStats.length > 0) {
    new Chart(typeCtx, {
      type: 'doughnut',
      data: {
        labels: typeStats.map(item => item.type ? item.type.toUpperCase() : 'Unknown'),
        datasets: [{
          data: typeStats.map(item => item.total),
          backgroundColor: [
            'var(--primary)',
            'var(--secondary)'
          ],
          borderWidth: 0,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                size: 12,
                weight: '600'
              },
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: 'var(--card-bg)',
            titleColor: 'var(--text-primary)',
            bodyColor: 'var(--text-primary)',
            borderColor: 'var(--border-color)',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            callbacks: {
              label: function(context) {
                const index = context.dataIndex;
                return context.label + ': ' + context.raw + ' (Avg: ' + (typeStats[index]?.avg_score || 0) + '%)';
              }
            }
          }
        }
      }
    });
  }
}

// Notification function
function showNotification() {
  showToast('No new notifications', 'info');
}

// Dark mode toggle
function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  const isDark = document.body.classList.contains('dark-mode');
  localStorage.setItem('darkMode', isDark);
  const darkModeBtn = document.getElementById('darkModeBtn');
  if (darkModeBtn) {
    darkModeBtn.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
  }
}

// Initialize dark mode
if (localStorage.getItem('darkMode') === 'true') {
  document.body.classList.add('dark-mode');
  const darkModeBtn = document.getElementById('darkModeBtn');
  if (darkModeBtn) {
    darkModeBtn.innerHTML = '<i class="fas fa-sun"></i>';
  }
}

// Add dark mode styles if not already present
if (!document.querySelector('#darkModeStyles')) {
  const style = document.createElement('style');
  style.id = 'darkModeStyles';
  style.textContent = `
    .dark-mode {
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --bg-tertiary: #334155;
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --text-tertiary: #94a3b8;
      --border-color: #334155;
      --border-focus: #475569;
      --card-bg: #1e293b;
      --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2);
      --card-hover-shadow: 0 4px 6px rgba(0, 0, 0, 0.3), 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .dark-mode .sidebar,
    .dark-mode .header,
    .dark-mode .card,
    .dark-mode .modal-content,
    .dark-mode .toast,
    .dark-mode .footer {
      background-color: var(--card-bg);
    }
    
    .dark-mode .form-input,
    .dark-mode .form-select,
    .dark-mode .form-textarea {
      background-color: var(--bg-secondary);
      color: var(--text-primary);
    }
    
    .dark-mode .table-container {
      background-color: var(--card-bg);
    }
    
    .dark-mode th {
      background-color: var(--bg-tertiary);
    }
  `;
  document.head.appendChild(style);
}

// Checklist Filtering and Auto-Icon Changer JS
function filterChecklistType(type) {
  const cards = document.querySelectorAll('.checklist-type-card');
  const tabs = event.currentTarget.parentElement.querySelectorAll('.tab');
  tabs.forEach(t => t.classList.remove('active'));
  event.currentTarget.classList.add('active');

  cards.forEach(c => {
    if (type === 'all' || c.dataset.type === type) {
      c.style.display = 'block';
    } else {
      c.style.display = 'none';
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('cat_type')?.addEventListener('change', function() {
    const iconInput = document.getElementById('cat_icon');
    if (iconInput) {
      iconInput.value = this.value === 'software' ? '💻' : '🔧';
    }
  });
});
</script>
</body>
</html>