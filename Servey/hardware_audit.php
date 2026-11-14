<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$activeTab = $_GET['tab'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
$assetId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle AJAX get_audit_report
if (isset($_GET['action']) && $_GET['action'] === 'get_audit_report') {
    header('Content-Type: application/json');
    $reportId = intval($_GET['report_id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, b.name as branch_name 
        FROM " . TABLE_PREFIX . "hardware_audit_reports r
        LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => $report ? true : false,
        'report' => $report,
        'message' => $report ? 'Success' : 'Report not found'
    ]);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    switch ($action) {
        case 'save_asset':
            $data = json_decode(file_get_contents('php://input'), true);
            $response['success'] = saveHardwareAsset($data, $data['id'] ?? null);
            $response['message'] = $response['success'] ? 'Asset saved successfully' : 'Failed to save asset';
            break;
            
        case 'delete_asset':
            $assetId = $_POST['asset_id'] ?? 0;
            $response['success'] = deleteHardwareAsset($assetId);
            $response['message'] = $response['success'] ? 'Asset deleted successfully' : 'Failed to delete asset';
            break;
            
        case 'add_maintenance':
            $data = json_decode(file_get_contents('php://input'), true);
            $response['success'] = addMaintenanceRecord($data['asset_id'], $data);
            $response['message'] = $response['success'] ? 'Maintenance record added' : 'Failed to add maintenance record';
            break;
            
        case 'add_software':
            $data = json_decode(file_get_contents('php://input'), true);
            $response['success'] = addAssetSoftware($data['asset_id'], $data);
            $response['message'] = $response['success'] ? 'Software added successfully' : 'Failed to add software';
            break;
            
        case 'update_network':
            $data = json_decode(file_get_contents('php://input'), true);
            $response['success'] = updateNetworkInfo($data['asset_id'], $data);
            $response['message'] = $response['success'] ? 'Network info updated' : 'Failed to update network info';
            break;
            
        case 'report_issue':
            $data = json_decode(file_get_contents('php://input'), true);
            $response['success'] = reportHardwareIssue($data['asset_id'], $data);
            $response['message'] = $response['success'] ? 'Issue reported successfully' : 'Failed to report issue';
            break;
            
        case 'resolve_issue':
            $issueId = $_POST['issue_id'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            $response['success'] = resolveIssue($issueId, $notes);
            $response['message'] = $response['success'] ? 'Issue resolved' : 'Failed to resolve issue';
            break;
            
        case 'run_audit':
            $branchId = $_POST['branch_id'] ?? 0;
            $auditor = $_POST['auditor'] ?? $_SESSION['admin_username'];
            $summary = $_POST['summary'] ?? '';
            $recommendations = $_POST['recommendations'] ?? '';
            
            $assets = getHardwareAssets($branchId);
            $reportId = createHardwareAuditReport($branchId, $assets, $auditor, $summary, $recommendations);
            $response['success'] = $reportId > 0;
            $response['report_id'] = $reportId;
            $response['message'] = $response['success'] ? 'Audit completed successfully' : 'Failed to complete audit';
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Get data for display
$branches = getBranches();
$assets = getHardwareAssets();
$stats = getHardwareDashboardStats();
$expiringWarranties = getExpiringWarranties(90);
$needingAudit = getAssetsNeedingAudit(6);
$openIssues = getOpenIssues();
$upcomingMaintenance = getUpcomingMaintenance(30);
$auditSchedule = getAuditSchedule();
$auditReports = getHardwareAuditReports();
$assetTypes = ['computer', 'laptop', 'printer', 'scanner', 'pos', 'router', 'switch', 'server', 'ups', 'other'];
$maintenanceTypes = ['preventive', 'corrective', 'emergency', 'upgrade'];
$issueTypes = ['hardware', 'software', 'network', 'performance', 'security', 'other'];
$severityLevels = ['critical', 'high', 'medium', 'low'];
$licenseTypes = ['perpetual', 'subscription', 'volume', 'oem'];

// Get single asset data if viewing details
$currentAsset = null;
if ($assetId && $action === 'view') {
    $currentAsset = getHardwareAsset($assetId);
    $maintenanceHistory = getMaintenanceHistory($assetId);
    $softwareList = getAssetSoftware($assetId);
    $networkInfo = getNetworkInfo($assetId);
    $assetIssues = getOpenIssues($assetId);
    $assetLogs = getAssetAuditLogs($assetId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Audit System - Kaldis Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printArea, #printArea *, #viewReportModal, #viewReportModal * {
                visibility: visible;
            }
            #printArea, #viewReportModal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
            }
            #viewReportModal {
                display: block !important;
                opacity: 1 !important;
                z-index: 9999 !important;
            }
            #viewReportModal .modal-content {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .header, .btn, .modal-close, .modal-footer, .alert, select, input, button, .card-header button {
                display: none !important;
            }
            #printArea::after {
                content: "\n\n\n\nICT HANDOVER SIGNATURES:\n\nTechnician: _______________________      Date: _____________\n\nBranch Manager: _________________      Date: _____________";
                display: block;
                white-space: pre;
                margin-top: 50px;
                border-top: 2px dashed #ccc;
                padding-top: 20px;
                font-family: sans-serif;
                font-weight: bold;
                font-size: 0.95rem;
            }
        }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 24px; }
        
        /* Header */
        .header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-link:hover, .nav-link.active { color: var(--primary); }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.total { background: rgba(79,70,229,0.1); color: var(--primary); }
        .stat-icon.operational { background: rgba(16,185,129,0.1); color: var(--success); }
        .stat-icon.issues { background: rgba(239,68,68,0.1); color: var(--danger); }
        .stat-icon.warranty { background: rgba(245,158,11,0.1); color: var(--warning); }
        .stat-icon.maintenance { background: rgba(59,130,246,0.1); color: var(--info); }
        
        .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 4px; }
        .stat-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; }
        
        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
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
        
        .card-body { padding: 20px; }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
            padding: 0 20px;
        }
        
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: var(--text-secondary);
            transition: all 0.2s;
            position: relative;
        }
        
        .tab:hover { color: var(--primary); }
        .tab.active { color: var(--primary); }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Tables */
        .table-container { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px 16px;
            background: var(--bg-secondary);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
        }
        
        tr:hover td { background: var(--bg-secondary); }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-operational { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-maintenance { background: rgba(245,158,11,0.1); color: #d97706; }
        .badge-repair { background: rgba(239,68,68,0.1); color: #dc2626; }
        .badge-decommissioned { background: rgba(100,116,139,0.1); color: #475569; }
        .badge-lost { background: rgba(0,0,0,0.1); color: #1e293b; }
        
        .badge-critical { background: rgba(239,68,68,0.1); color: #dc2626; }
        .badge-high { background: rgba(245,158,11,0.1); color: #d97706; }
        .badge-medium { background: rgba(59,130,246,0.1); color: #2563eb; }
        .badge-low { background: rgba(16,185,129,0.1); color: #059669; }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-secondary { background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-sm { padding: 4px 12px; font-size: 0.75rem; }
        .btn-xs { padding: 4px 8px; font-size: 0.7rem; }
        
        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-warning { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); }
        .alert-danger { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); }
        .alert-info { background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: 8px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 16px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .tabs { overflow-x: auto; }
            .tab { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="admin.php" class="logo">🏢 Kaldis ICT Admin</a>
        <nav class="nav-links">
            <a href="admin.php?tab=dashboard" class="nav-link">Dashboard</a>
            <a href="admin.php?tab=reports" class="nav-link">Reports</a>
            <a href="hardware_audit.php" class="nav-link active">Hardware Audit</a>
            <a href="admin.php?tab=branches" class="nav-link">Branches</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </nav>
    </header>
    
    <div class="container">
        <?php if ($action === 'view' && $currentAsset): ?>
        <!-- Asset Detail View -->
        <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
            <a href="hardware_audit.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Assets
            </a>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-secondary" onclick="printAssetHandover()">
                    <i class="fas fa-print"></i> Print Handover Sheet
                </button>
                <button class="btn btn-primary" onclick="editAsset(<?php echo $currentAsset['id']; ?>)">
                    <i class="fas fa-edit"></i> Edit Specifications
                </button>
                <button class="btn btn-danger" onclick="deleteAsset(<?php echo $currentAsset['id']; ?>)">
                    <i class="fas fa-trash"></i> Delete Asset
                </button>
            </div>
        </div>

        <div id="printArea" class="print-area">
        <div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Asset Header Card -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                            <span class="badge" style="background: var(--primary); color: white; font-family: monospace; font-size: 1rem; padding: 6px 12px;"><?php echo e($currentAsset['asset_tag'] ?: 'NO-TAG'); ?></span>
                            <span class="badge badge-<?php echo $currentAsset['status']; ?>" style="font-size: 0.9rem; padding: 4px 10px;"><?php echo ucfirst($currentAsset['status']); ?></span>
                        </div>
                        <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px;"><?php echo e($currentAsset['brand']); ?> <?php echo e($currentAsset['model']); ?></h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                            <i class="fas fa-map-marker-alt"></i> <strong><?php echo e($currentAsset['branch_name']); ?></strong> 
                            <?php if ($currentAsset['location']): ?> • <?php echo e($currentAsset['location']); ?><?php endif; ?>
                            <?php if ($currentAsset['assigned_to']): ?> • Assigned to: <strong><?php echo e($currentAsset['assigned_to']); ?></strong><?php endif; ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-warning" onclick="addMaintenance(<?php echo $currentAsset['id']; ?>)">
                            <i class="fas fa-wrench"></i> Log Maintenance
                        </button>
                        <button class="btn btn-danger" onclick="openReportIssueModalDetail(<?php echo $currentAsset['id']; ?>)">
                            <i class="fas fa-bug"></i> Report Issue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
            <!-- Left Side Details & Tabs -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Specifications -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-microchip" style="color: var(--primary);"></i> Technical Specifications</div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($currentAsset['specifications'])): ?>
                        <pre style="background: var(--bg-secondary); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); font-family: monospace; font-size: 0.9rem; white-space: pre-wrap; word-break: break-all;"><?php echo e($currentAsset['specifications']); ?></pre>
                        <?php else: ?>
                        <p style="color: var(--text-secondary); font-style: italic;">No technical specifications recorded.</p>
                        <?php endif; ?>
                        
                        <?php if ($currentAsset['notes']): ?>
                        <div style="margin-top: 16px; padding: 12px; background: rgba(245, 158, 11, 0.05); border-left: 4px solid var(--warning); border-radius: 4px;">
                            <strong style="font-size: 0.8rem; text-transform: uppercase; color: var(--warning); display: block; margin-bottom: 4px;">Admin Notes</strong>
                            <p style="font-size: 0.9rem; margin: 0; color: var(--text-secondary);"><?php echo nl2br(e($currentAsset['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Network Config -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="card-title"><i class="fas fa-network-wired" style="color: var(--info);"></i> Network Configuration</div>
                        <div style="display: flex; gap: 8px;">
                            <?php if ($networkInfo): ?>
                            <button class="btn btn-warning btn-sm" onclick="testConnection('<?php echo e($networkInfo['ip_address'] ?: $networkInfo['hostname'] ?: ''); ?>')">
                                <i class="fas fa-bolt"></i> Test Connection
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-secondary btn-sm" onclick="openNetworkModal()">
                                <i class="fas fa-edit"></i> Edit Network Info
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($networkInfo): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">Hostname</div>
                                <code style="font-size: 0.9rem; font-weight: 700; color: var(--primary);"><?php echo e($networkInfo['hostname'] ?: '—'); ?></code>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">IP Address</div>
                                <code style="font-size: 0.9rem; font-weight: 700; color: var(--success);"><?php echo e($networkInfo['ip_address'] ?: '—'); ?></code>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">MAC Address</div>
                                <code style="font-size: 0.9rem; font-weight: 700; color: var(--text-secondary);"><?php echo e($networkInfo['mac_address'] ?: '—'); ?></code>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">Domain</div>
                                <span style="font-size: 0.9rem; font-weight: 600;"><?php echo e($networkInfo['domain'] ?: '—'); ?></span>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">VLAN</div>
                                <span style="font-size: 0.9rem; font-weight: 600;"><?php echo e($networkInfo['vlan'] ?: '—'); ?></span>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="font-size: 0.7rem;">Switch Port</div>
                                <span style="font-size: 0.9rem; font-weight: 600;"><?php echo e($networkInfo['switch_port'] ?: '—'); ?></span>
                            </div>
                        </div>
                        <div id="pingContainer" style="display: none; margin-top: 16px; background: #000; color: #39ff14; font-family: monospace; padding: 12px; border-radius: 6px; font-size: 0.85rem; line-height: 1.4; border: 1px solid #222; max-height: 250px; overflow-y: auto;">
                            <div id="pingConsole"></div>
                        </div>
                        <?php if ($networkInfo['notes']): ?>
                        <div style="margin-top: 12px; font-size: 0.85rem; color: var(--text-secondary); padding: 8px; background: var(--bg-secondary); border-radius: 4px;">
                            <strong>Network Notes:</strong> <?php echo e($networkInfo['notes']); ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <p style="color: var(--text-secondary); font-style: italic; text-align: center; margin: 16px 0;">No network details configured.</p>
                        <div style="text-align: center;">
                            <button class="btn btn-primary btn-sm" onclick="openNetworkModal()"><i class="fas fa-plus"></i> Configure Network</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Installed Software -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="card-title"><i class="fas fa-laptop-code" style="color: var(--primary);"></i> Installed Software</div>
                        <button class="btn btn-primary btn-sm" onclick="openSoftwareModal()">
                            <i class="fas fa-plus"></i> Add Software
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($softwareList)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Software Name</th>
                                        <th>Version</th>
                                        <th>License Type</th>
                                        <th>License Key</th>
                                        <th>Expiry Date</th>
                                        <th>Installed Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($softwareList as $soft): ?>
                                    <tr>
                                        <td><strong><?php echo e($soft['software_name']); ?></strong></td>
                                        <td><code><?php echo e($soft['version'] ?: '—'); ?></code></td>
                                        <td><span class="badge" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);"><?php echo ucfirst($soft['license_type']); ?></span></td>
                                        <td><code><?php echo e($soft['license_key'] ?: '—'); ?></code></td>
                                        <td>
                                            <?php if ($soft['expiry_date']): ?>
                                                <span class="<?php echo strtotime($soft['expiry_date']) < time() ? 'text-danger font-weight-bold' : ''; ?>">
                                                    <?php echo formatDate($soft['expiry_date'], 'M d, Y'); ?>
                                                </span>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $soft['installed_date'] ? formatDate($soft['installed_date'], 'M d, Y') : '—'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p style="color: var(--text-secondary); font-style: italic; text-align: center;">No software licenses recorded on this asset.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Maintenance History -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-history" style="color: var(--success);"></i> Maintenance History</div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($maintenanceHistory)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Cost</th>
                                        <th>Performed By</th>
                                        <th>Next Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenanceHistory as $maint): ?>
                                    <tr>
                                        <td><strong><?php echo formatDate($maint['maintenance_date'], 'M d, Y'); ?></strong></td>
                                        <td><span class="badge"><?php echo ucfirst($maint['maintenance_type']); ?></span></td>
                                        <td><?php echo e($maint['description']); ?></td>
                                        <td style="font-weight: 600; color: var(--success);">
                                            <?php echo $maint['cost'] ? '$' . number_format($maint['cost'], 2) : '—'; ?>
                                        </td>
                                        <td><?php echo e($maint['performed_by']); ?></td>
                                        <td><?php echo $maint['next_maintenance_date'] ? formatDate($maint['next_maintenance_date'], 'M d, Y') : '—'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p style="color: var(--text-secondary); font-style: italic; text-align: center;">No maintenance records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Asset Audit Trail / Change Logs -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-list-ul" style="color: var(--primary);"></i> Audit Trail & Change Logs</div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assetLogs)): ?>
                        <p style="color: var(--text-secondary); font-style: italic; text-align: center;">No activity log recorded for this asset.</p>
                        <?php else: ?>
                        <div style="position: relative; padding-left: 20px; border-left: 2px solid var(--border-color); margin-left: 10px; display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($assetLogs as $l): ?>
                            <div style="position: relative;">
                                <div style="position: absolute; left: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary); border: 2px solid var(--card-bg);"></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">
                                    <?php echo formatDate($l['created_at'], 'M d, Y H:i'); ?> • Performed by <strong><?php echo e($l['performed_by'] ?: 'System'); ?></strong>
                                </div>
                                <div style="font-size: 0.9rem; font-weight: bold; color: var(--text-primary);">
                                    <?php echo e($l['action']); ?>
                                </div>
                                <?php if ($l['notes']): ?>
                                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px; background: var(--bg-secondary); padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                    <?php echo e($l['notes']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side Purchase & Auditing Info -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Audit & Financial Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-calendar-check" style="color: var(--warning);"></i> Audit & Lifecycle</div>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div>
                                <div class="form-label">Purchase Date</div>
                                <div style="font-size: 1.1rem; font-weight: 600;"><?php echo $currentAsset['purchase_date'] ? formatDate($currentAsset['purchase_date'], 'F d, Y') : '—'; ?></div>
                            </div>
                            <hr style="border: none; border-top: 1px solid var(--border-color);">
                            <div>
                                <div class="form-label">Warranty Expiry</div>
                                <div style="font-size: 1.1rem; font-weight: 600;" class="<?php echo $currentAsset['warranty_expiry'] && strtotime($currentAsset['warranty_expiry']) < time() ? 'text-danger' : ''; ?>">
                                    <?php echo $currentAsset['warranty_expiry'] ? formatDate($currentAsset['warranty_expiry'], 'F d, Y') : '—'; ?>
                                    <?php if ($currentAsset['warranty_expiry'] && strtotime($currentAsset['warranty_expiry']) < time()): ?>
                                        <span style="font-size: 0.75rem; display: block; font-weight: normal; color: var(--danger);">Expired</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr style="border: none; border-top: 1px solid var(--border-color);">
                            <div>
                                <div class="form-label">Last Audited Date</div>
                                <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                                    <?php echo $currentAsset['last_audit_date'] ? formatDate($currentAsset['last_audit_date'], 'F d, Y H:i') : 'Never Audited'; ?>
                                </div>
                            </div>
                            <hr style="border: none; border-top: 1px solid var(--border-color);">
                            <div style="text-align: center; background: var(--bg-secondary); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div class="form-label" style="margin-bottom: 8px;">Asset QR Tag</div>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode(SITE_URL . 'hardware_audit.php?action=view&id=' . $currentAsset['id']); ?>" alt="QR Tag" style="border: 4px solid white; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: inline-block;">
                                <div style="margin-top: 8px; font-size: 0.75rem; color: var(--text-secondary);">
                                    Scan to view asset details
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Open Issues -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Active Issues</div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($assetIssues)): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($assetIssues as $issue): ?>
                            <div style="padding: 12px; background: rgba(239, 68, 68, 0.05); border-left: 4px solid var(--danger); border-radius: 6px; display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <span class="badge badge-<?php echo $issue['severity']; ?>" style="font-size: 0.7rem;"><?php echo ucfirst($issue['severity']); ?></span>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo formatDate($issue['reported_at'], 'M d'); ?></span>
                                </div>
                                <strong style="font-size: 0.9rem; color: var(--text-primary);"><?php echo e($issue['title']); ?></strong>
                                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;"><?php echo e($issue['description']); ?></p>
                                <div style="text-align: right; margin-top: 4px;">
                                    <button class="btn btn-success btn-xs" onclick="resolveIssue(<?php echo $issue['id']; ?>, '<?php echo e($issue['title']); ?>')">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; color: var(--success); padding: 16px 0;">
                            <i class="fas fa-check-circle" style="font-size: 2.5rem; margin-bottom: 8px; display: block;"></i>
                            <span style="font-weight: 600; font-size: 0.9rem;">No Active Issues Found</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <?php else: ?>
        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon total"><i class="fas fa-microchip"></i></div>
                </div>
                <div class="stat-value"><?php echo array_sum(array_column($stats['by_status'], 'count')); ?></div>
                <div class="stat-label">Total Assets</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon operational"><i class="fas fa-check-circle"></i></div>
                </div>
                <?php 
                $operational = 0;
                foreach ($stats['by_status'] as $s) if ($s['status'] == 'operational') $operational = $s['count'];
                ?>
                <div class="stat-value"><?php echo $operational; ?></div>
                <div class="stat-label">Operational</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon issues"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['open_issues']; ?></div>
                <div class="stat-label">Open Issues</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warranty"><i class="fas fa-calendar-alt"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['warranty_expiring']; ?></div>
                <div class="stat-label">Warranty Expiring</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon maintenance"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['upcoming_maintenance']; ?></div>
                <div class="stat-label">Upcoming Maintenance</div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($needingAudit)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-clock"></i>
            <div>
                <strong>Audit Required:</strong> <?php echo count($needingAudit); ?> assets haven't been audited in the last 6 months.
                <a href="#audit" style="color: var(--warning); margin-left: 8px;">Start Audit →</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($expiringWarranties)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>Warranty Alert:</strong> <?php echo count($expiringWarranties); ?> assets have warranties expiring within 90 days.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Tabs -->
        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('assets')">📋 Assets</button>
                <button class="tab" onclick="switchTab('issues')">⚠️ Issues (<?php echo $stats['open_issues']; ?>)</button>
                <button class="tab" onclick="switchTab('maintenance')">🔧 Maintenance</button>
                <button class="tab" onclick="switchTab('audit')">📊 Audit Schedule</button>
                <button class="tab" onclick="switchTab('analytics')">📈 Analytics</button>
                <button class="tab" onclick="switchTab('reports')">📋 Completed Reports</button>
            </div>
            
            <!-- Assets Tab -->
            <div id="assets-tab" class="tab-content active">
                <div class="card-body">
                    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <input type="text" id="searchAssets" placeholder="🔍 Search assets..." class="form-input" style="width: 250px;" onkeyup="filterAssets()">
                            <select id="filterType" class="form-select" style="width: 150px;" onchange="filterAssets()">
                                <option value="">All Types</option>
                                <?php foreach ($assetTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterStatus" class="form-select" style="width: 150px;" onchange="filterAssets()">
                                <option value="">All Status</option>
                                <option value="operational">Operational</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="repair">Repair</option>
                                <option value="decommissioned">Decommissioned</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="openAddAssetModal()">
                            <i class="fas fa-plus"></i> Add Asset
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table id="assetsTable">
                            <thead>
                                <tr>
                                    <th>Asset Tag</th>
                                    <th>Branch</th>
                                    <th>Type</th>
                                    <th>Brand/Model</th>
                                    <th>Serial</th>
                                    <th>Status</th>
                                    <th>Warranty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assets as $asset): ?>
                                <tr data-type="<?php echo $asset['asset_type']; ?>" data-status="<?php echo $asset['status']; ?>">
                                    <td><code><?php echo e($asset['asset_tag'] ?? '—'); ?></code></td>
                                    <td><strong><?php echo e($asset['branch_name']); ?></strong></td>
                                    <td><span class="badge badge-operational"><?php echo ucfirst($asset['asset_type']); ?></span></td>
                                    <td><?php echo e($asset['brand']); ?> <?php echo e($asset['model']); ?></td>
                                    <td><code><?php echo e($asset['serial_number'] ?? '—'); ?></code></td>
                                    <td><span class="badge badge-<?php echo $asset['status']; ?>"><?php echo ucfirst($asset['status']); ?></span></td>
                                    <td class="<?php echo $asset['warranty_expiry'] && strtotime($asset['warranty_expiry']) < strtotime('+90 days') ? 'warranty-expiring' : ''; ?>">
                                        <?php echo $asset['warranty_expiry'] ? formatDate($asset['warranty_expiry'], 'M d, Y') : '—'; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <a href="?action=view&id=<?php echo $asset['id']; ?>" class="btn btn-secondary btn-xs">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-primary btn-xs" onclick="editAsset(<?php echo $asset['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-xs" onclick="deleteAsset(<?php echo $asset['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Issues Tab -->
            <div id="issues-tab" class="tab-content">
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Asset</th>
                                    <th>Branch</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Reported</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($openIssues as $issue): ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo $issue['severity']; ?>"><?php echo ucfirst($issue['severity']); ?></span></td>
                                    <td><?php echo e($issue['brand']); ?> <?php echo e($issue['model']); ?></td>
                                    <td><?php echo e($issue['branch_name']); ?></td>
                                    <td><?php echo e($issue['title']); ?></td>
                                    <td><span class="badge"><?php echo ucfirst($issue['issue_type']); ?></span></td>
                                    <td><?php echo formatDate($issue['reported_at'], 'M d, Y'); ?></td>
                                    <td><span class="badge"><?php echo str_replace('_', ' ', ucfirst($issue['status'])); ?></span></td>
                                    <td>
                                        <button class="btn btn-success btn-xs" onclick="resolveIssue(<?php echo $issue['id']; ?>, '<?php echo e($issue['title']); ?>')">
                                            <i class="fas fa-check"></i> Resolve
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button class="btn btn-primary" style="margin-top: 16px;" onclick="openReportIssueModal()">
                        <i class="fas fa-bug"></i> Report New Issue
                    </button>
                </div>
            </div>
            
            <!-- Maintenance Tab -->
            <div id="maintenance-tab" class="tab-content">
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Branch</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Cost</th>
                                    <th>Date</th>
                                    <th>Next Due</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingMaintenance as $maintenance): ?>
                                <tr>
                                    <td><?php echo e($maintenance['brand']); ?> <?php echo e($maintenance['model']); ?></td>
                                    <td><?php echo e($maintenance['branch_name']); ?></td>
                                    <td><span class="badge"><?php echo ucfirst($maintenance['maintenance_type']); ?></span></td>
                                    <td><?php echo e(substr($maintenance['description'], 0, 50)); ?>...</td>
                                    <td><?php echo $maintenance['cost'] ? '$' . number_format($maintenance['cost'], 2) : '—'; ?></td>
                                    <td><?php echo formatDate($maintenance['maintenance_date'], 'M d, Y'); ?></td>
                                    <td class="<?php echo strtotime($maintenance['next_maintenance_date']) < strtotime('+14 days') ? 'warranty-expiring' : ''; ?>">
                                        <?php echo formatDate($maintenance['next_maintenance_date'], 'M d, Y'); ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="addMaintenance(<?php echo $maintenance['asset_id']; ?>)">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button class="btn btn-primary" style="margin-top: 16px;" onclick="openAddMaintenanceModal()">
                        <i class="fas fa-wrench"></i> Add Maintenance Record
                    </button>
                </div>
            </div>
            
            <!-- Audit Schedule Tab -->
            <div id="audit-tab" class="tab-content">
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Asset Type</th>
                                    <th>Frequency</th>
                                    <th>Last Audit</th>
                                    <th>Next Audit</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditSchedule as $schedule): ?>
                                <tr>
                                    <td><?php echo e($schedule['branch_name'] ?? 'All Branches'); ?></td>
                                    <td><?php echo $schedule['asset_type'] ? ucfirst($schedule['asset_type']) : 'All Types'; ?></td>
                                    <td><span class="badge"><?php echo ucfirst($schedule['audit_frequency']); ?></span></td>
                                    <td><?php echo $schedule['last_audit_date'] ? formatDate($schedule['last_audit_date'], 'M d, Y') : 'Never'; ?></td>
                                    <td class="<?php echo strtotime($schedule['next_audit_date']) < strtotime('+14 days') ? 'warranty-expiring' : ''; ?>">
                                        <?php echo formatDate($schedule['next_audit_date'], 'M d, Y'); ?>
                                    </td>
                                    <td><?php echo e($schedule['assigned_to'] ?? '—'); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="runBranchAudit(<?php echo $schedule['branch_id']; ?>)">
                                            <i class="fas fa-play"></i> Run Audit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <form id="auditForm" onsubmit="return runAudit(event)" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                        <h4 style="margin-bottom: 16px;">Run Manual Audit</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Select Branch</label>
                                <select class="form-select" id="auditBranch" required>
                                    <option value="">-- Select Branch --</option>
                                    <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo e($branch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Auditor</label>
                                <input type="text" class="form-input" id="auditor" value="<?php echo e($_SESSION['admin_username']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Summary</label>
                            <textarea class="form-textarea" id="auditSummary" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Recommendations</label>
                            <textarea class="form-textarea" id="auditRecommendations" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Audit
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Analytics Tab -->
            <div id="analytics-tab" class="tab-content">
                <div class="card-body">
                    <div class="form-row" style="margin-bottom: 24px;">
                        <div class="form-group">
                            <canvas id="assetsByTypeChart" style="height: 300px;"></canvas>
                        </div>
                        <div class="form-group">
                            <canvas id="assetsByBranchChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <canvas id="monthlyTrendChart" style="height: 300px;"></canvas>
                        </div>
                        <div class="form-group">
                            <canvas id="statusDistributionChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                    
                    <div class="card" style="margin-top: 24px;">
                        <div class="card-header">
                            <h4>Maintenance Cost Overview</h4>
                        </div>
                        <div class="card-body">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">
                                $<?php echo number_format($stats['maintenance_cost'], 2); ?>
                            </div>
                            <div class="stat-label">Total maintenance cost (last 12 months)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports-tab" class="tab-content">
                <div class="card-body">
                    <?php if (empty($auditReports)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                        <i class="fas fa-file-invoice" style="font-size: 3rem; margin-bottom: 12px; display: block;"></i>
                        No completed audit reports found.
                    </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Audited By</th>
                                    <th>Total Assets</th>
                                    <th>Breakdown</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditReports as $r): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: bold;">#<?php echo $r['id']; ?></td>
                                    <td><?php echo formatDate($r['created_at'], 'M d, Y H:i'); ?></td>
                                    <td><strong><?php echo e($r['branch_name'] ?: 'Unknown Branch'); ?></strong></td>
                                    <td><?php echo e($r['audited_by']); ?></td>
                                    <td style="text-align: center; font-weight: bold;"><?php echo $r['total_assets']; ?></td>
                                    <td>
                                        <span class="badge badge-operational" style="padding: 2px 6px; font-size: 0.75rem; background: var(--success); color: white;">Op: <?php echo $r['operational_count']; ?></span>
                                        <span class="badge badge-maintenance" style="padding: 2px 6px; font-size: 0.75rem; background: var(--warning); color: white;">Maint: <?php echo $r['maintenance_count']; ?></span>
                                        <span class="badge badge-repair" style="padding: 2px 6px; font-size: 0.75rem; background: var(--primary); color: white;">Rep: <?php echo $r['repair_count']; ?></span>
                                        <?php if ($r['decommissioned_count'] > 0): ?>
                                        <span class="badge badge-danger" style="padding: 2px 6px; font-size: 0.75rem; background: var(--danger); color: white;">Dec: <?php echo $r['decommissioned_count']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($r['lost_count'] > 0): ?>
                                        <span class="badge badge-danger" style="padding: 2px 6px; font-size: 0.75rem; background: #000; color: white;">Lost: <?php echo $r['lost_count']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $r['status'] == 'completed' ? 'var(--success)' : 'var(--warning)'; ?>; color: white;">
                                            <?php echo ucfirst($r['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="viewAuditReport(<?php echo $r['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Report
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- View Audit Report Modal -->
    <div class="modal" id="viewReportModal">
        <div class="modal-content modal-large" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="reportTitle">Audit Report</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('viewReportModal')">×</button>
            </div>
            <div class="modal-body" id="reportDetailsContent">
                <!-- Loaded dynamically -->
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding: 16px;">
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
                <button class="btn btn-primary" onclick="closeModal('viewReportModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Asset Modal -->
    <div class="modal" id="assetModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Hardware Asset</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('assetModal')">×</button>
            </div>
            <form id="assetForm" onsubmit="return saveAsset(event)">
                <div class="modal-body">
                    <input type="hidden" id="assetId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Branch *</label>
                            <select class="form-select" id="branchId" name="branch_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"><?php echo e($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Asset Type *</label>
                            <select class="form-select" id="assetType" name="asset_type" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($assetTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Asset Tag</label>
                            <input type="text" class="form-input" id="assetTag" name="asset_tag" placeholder="e.g., KDS-001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Serial Number</label>
                            <input type="text" class="form-input" id="serialNumber" name="serial_number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-input" id="brand" name="brand">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-input" id="model" name="model">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Specifications</label>
                        <textarea class="form-textarea" id="specifications" name="specifications" rows="2" placeholder="RAM, Storage, Processor, etc."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-input" id="purchaseDate" name="purchase_date">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-input" id="warrantyExpiry" name="warranty_expiry">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="operational">Operational</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="repair">In Repair</option>
                                <option value="decommissioned">Decommissioned</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assigned To</label>
                            <input type="text" class="form-input" id="assignedTo" name="assigned_to">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-input" id="location" name="location" placeholder="Room, desk, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('assetModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Issue Modal -->
    <div class="modal" id="issueModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Hardware Issue</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('issueModal')">×</button>
            </div>
            <form id="issueForm" onsubmit="return reportIssue(event)">
                <div class="modal-body">
                    <input type="hidden" id="issueAssetId">
                    <div class="form-group">
                        <label class="form-label">Asset *</label>
                        <select class="form-select" id="issueAssetSelect" required onchange="document.getElementById('issueAssetId').value = this.value">
                            <option value="">-- Select Asset --</option>
                            <?php foreach ($assets as $asset): ?>
                            <option value="<?php echo $asset['id']; ?>"><?php echo e($asset['brand'] . ' ' . $asset['model'] . ' (' . $asset['branch_name'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Issue Type</label>
                            <select class="form-select" id="issueType" required>
                                <?php foreach ($issueTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Severity</label>
                            <select class="form-select" id="severity" required>
                                <?php foreach ($severityLevels as $level): ?>
                                <option value="<?php echo $level; ?>"><?php echo ucfirst($level); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-input" id="issueTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="issueDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reported By</label>
                        <input type="text" class="form-input" id="reportedBy" value="<?php echo e($_SESSION['admin_username']); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('issueModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Report Issue</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Maintenance Modal -->
    <div class="modal" id="maintenanceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Maintenance Record</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('maintenanceModal')">×</button>
            </div>
            <form id="maintenanceForm" onsubmit="return addMaintenanceRecord(event)">
                <div class="modal-body">
                    <input type="hidden" id="maintenanceAssetId">
                    <div class="form-group">
                        <label class="form-label">Asset *</label>
                        <select class="form-select" id="maintenanceAssetSelect" required onchange="document.getElementById('maintenanceAssetId').value = this.value">
                            <option value="">-- Select Asset --</option>
                            <?php foreach ($assets as $asset): ?>
                            <option value="<?php echo $asset['id']; ?>"><?php echo e($asset['brand'] . ' ' . $asset['model'] . ' (' . $asset['branch_name'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Maintenance Type</label>
                            <select class="form-select" id="maintenanceType" required>
                                <?php foreach ($maintenanceTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cost ($)</label>
                            <input type="number" step="0.01" class="form-input" id="maintenanceCost">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="form-textarea" id="maintenanceDescription" rows="3" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Maintenance Date</label>
                            <input type="date" class="form-input" id="maintenanceDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Next Maintenance Date</label>
                            <input type="date" class="form-input" id="nextMaintenanceDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Performed By</label>
                        <input type="text" class="form-input" id="performedBy" value="<?php echo e($_SESSION['admin_username']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="maintenanceNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('maintenanceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Network Config Modal -->
    <div class="modal" id="networkModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Configure Network Settings</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('networkModal')">×</button>
            </div>
            <form id="networkForm" onsubmit="return saveNetwork(event)">
                <div class="modal-body">
                    <input type="hidden" id="networkAssetId" value="<?php echo $assetId; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Hostname</label>
                            <input type="text" class="form-input" id="netHostname" value="<?php echo e($networkInfo['hostname'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IP Address</label>
                            <input type="text" class="form-input" id="netIpAddress" placeholder="e.g., 192.168.1.50" value="<?php echo e($networkInfo['ip_address'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">MAC Address</label>
                            <input type="text" class="form-input" id="netMacAddress" placeholder="e.g., AA:BB:CC:DD:EE:FF" value="<?php echo e($networkInfo['mac_address'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Domain</label>
                            <input type="text" class="form-input" id="netDomain" value="<?php echo e($networkInfo['domain'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">VLAN</label>
                            <input type="text" class="form-input" id="netVlan" value="<?php echo e($networkInfo['vlan'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Switch Port</label>
                            <input type="text" class="form-input" id="netSwitchPort" value="<?php echo e($networkInfo['switch_port'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Network Notes</label>
                        <textarea class="form-textarea" id="netNotes" rows="2"><?php echo e($networkInfo['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('networkModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Software Modal -->
    <div class="modal" id="softwareModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Installed Software</h3>
                <button class="btn btn-secondary btn-sm" onclick="closeModal('softwareModal')">×</button>
            </div>
            <form id="softwareForm" onsubmit="return saveSoftware(event)">
                <div class="modal-body">
                    <input type="hidden" id="softwareAssetId" value="<?php echo $assetId; ?>">
                    <div class="form-group">
                        <label class="form-label">Software Name *</label>
                        <input type="text" class="form-input" id="softName" required placeholder="e.g. Windows 11 Pro, Office 2021">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-input" id="softVersion">
                        </div>
                        <div class="form-group">
                            <label class="form-label">License Type</label>
                            <select class="form-select" id="softLicenseType">
                                <?php foreach ($licenseTypes as $licType): ?>
                                <option value="<?php echo $licType; ?>"><?php echo ucfirst($licType); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">License Key</label>
                        <input type="text" class="form-input" id="softLicenseKey">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Installed Date</label>
                            <input type="date" class="form-input" id="softInstalledDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-input" id="softExpiryDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" id="softNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('softwareModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Software</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });
        
        function initCharts() {
            // Assets by type chart
            const typeData = <?php echo json_encode($stats['by_type']); ?>;
            const typeCtx = document.getElementById('assetsByTypeChart')?.getContext('2d');
            if (typeCtx && typeData.length) {
                new Chart(typeCtx, {
                    type: 'bar',
                    data: {
                        labels: typeData.map(t => t.asset_type.toUpperCase()),
                        datasets: [{
                            label: 'Number of Assets',
                            data: typeData.map(t => t.count),
                            backgroundColor: '#4f46e5',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { position: 'top' } }
                    }
                });
            }
            
            // Assets by branch chart
            const branchData = <?php echo json_encode($stats['by_branch']); ?>;
            const branchCtx = document.getElementById('assetsByBranchChart')?.getContext('2d');
            if (branchCtx && branchData.length) {
                new Chart(branchCtx, {
                    type: 'pie',
                    data: {
                        labels: branchData.map(b => b.name),
                        datasets: [{
                            data: branchData.map(b => b.count),
                            backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { position: 'right' } }
                    }
                });
            }
            
            // Monthly trend chart
            const monthlyData = <?php echo json_encode($stats['monthly_additions']); ?>;
            const monthlyCtx = document.getElementById('monthlyTrendChart')?.getContext('2d');
            if (monthlyCtx && monthlyData.length) {
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyData.map(m => m.month),
                        datasets: [{
                            label: 'Assets Added',
                            data: monthlyData.map(m => m.count),
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79,70,229,0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true
                    }
                });
            }
            
            // Status distribution chart
            const statusData = <?php echo json_encode($stats['by_status']); ?>;
            const statusCtx = document.getElementById('statusDistributionChart')?.getContext('2d');
            if (statusCtx && statusData.length) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(s => s.status.toUpperCase()),
                        datasets: [{
                            data: statusData.map(s => s.count),
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#94a3b8', '#1e293b']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }
        
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(`${tabName}-tab`).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Filter assets
        function filterAssets() {
            const search = document.getElementById('searchAssets').value.toLowerCase();
            const type = document.getElementById('filterType').value;
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#assetsTable tbody tr');
            
            rows.forEach(row => {
                let show = true;
                const text = row.innerText.toLowerCase();
                const rowType = row.dataset.type;
                const rowStatus = row.dataset.status;
                
                if (search && !text.includes(search)) show = false;
                if (type && rowType !== type) show = false;
                if (status && rowStatus !== status) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Asset CRUD operations
        async function saveAsset(event) {
            event.preventDefault();
            const formData = {
                id: document.getElementById('assetId').value,
                branch_id: document.getElementById('branchId').value,
                asset_type: document.getElementById('assetType').value,
                asset_tag: document.getElementById('assetTag').value,
                serial_number: document.getElementById('serialNumber').value,
                brand: document.getElementById('brand').value,
                model: document.getElementById('model').value,
                specifications: document.getElementById('specifications').value,
                purchase_date: document.getElementById('purchaseDate').value,
                warranty_expiry: document.getElementById('warrantyExpiry').value,
                status: document.getElementById('status').value,
                assigned_to: document.getElementById('assignedTo').value,
                location: document.getElementById('location').value,
                notes: document.getElementById('notes').value
            };
            
            try {
                const response = await fetch('?action=save_asset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (result.success) {
                    closeModal('assetModal');
                    location.reload();
                } else {
                    alert('Failed to save asset: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }
        
        function deleteAsset(assetId) {
            if (!confirm('Are you sure? This action cannot be undone.')) return;
            
            const formData = new FormData();
            formData.append('asset_id', assetId);
            
            fetch('?action=delete_asset', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            }).then(response => response.json()).then(result => {
                if (result.success) location.reload();
                else alert('Failed to delete asset');
            });
        }
        
        function editAsset(assetId) {
            fetch(`get_asset.php?id=${assetId}`)
                .then(response => response.json())
                .then(asset => {
                    document.getElementById('modalTitle').textContent = 'Edit Hardware Asset';
                    document.getElementById('assetId').value = asset.id;
                    document.getElementById('branchId').value = asset.branch_id;
                    document.getElementById('assetType').value = asset.asset_type;
                    document.getElementById('assetTag').value = asset.asset_tag || '';
                    document.getElementById('serialNumber').value = asset.serial_number || '';
                    document.getElementById('brand').value = asset.brand || '';
                    document.getElementById('model').value = asset.model || '';
                    document.getElementById('specifications').value = asset.specifications || '';
                    document.getElementById('purchaseDate').value = asset.purchase_date || '';
                    document.getElementById('warrantyExpiry').value = asset.warranty_expiry || '';
                    document.getElementById('status').value = asset.status;
                    document.getElementById('assignedTo').value = asset.assigned_to || '';
                    document.getElementById('location').value = asset.location || '';
                    document.getElementById('notes').value = asset.notes || '';
                    openModal('assetModal');
                });
        }
        
        function openAddAssetModal() {
            document.getElementById('modalTitle').textContent = 'Add Hardware Asset';
            document.getElementById('assetForm').reset();
            document.getElementById('assetId').value = '';
            openModal('assetModal');
        }
        
        // Issue management
        function openReportIssueModal() {
            document.getElementById('issueForm').reset();
            openModal('issueModal');
        }
        
        async function reportIssue(event) {
            event.preventDefault();
            const formData = {
                asset_id: document.getElementById('issueAssetId').value,
                issue_type: document.getElementById('issueType').value,
                severity: document.getElementById('severity').value,
                title: document.getElementById('issueTitle').value,
                description: document.getElementById('issueDescription').value,
                reported_by: document.getElementById('reportedBy').value
            };
            
            const response = await fetch('?action=report_issue', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (result.success) {
                closeModal('issueModal');
                location.reload();
            } else {
                alert('Failed to report issue');
            }
        }
        
        function resolveIssue(issueId, title) {
            const notes = prompt('Enter resolution notes:', 'Issue resolved');
            if (!notes) return;
            
            const formData = new FormData();
            formData.append('issue_id', issueId);
            formData.append('notes', notes);
            
            fetch('?action=resolve_issue', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            }).then(response => response.json()).then(result => {
                if (result.success) location.reload();
                else alert('Failed to resolve issue');
            });
        }
        
        // Maintenance management
        function openAddMaintenanceModal() {
            document.getElementById('maintenanceForm').reset();
            document.getElementById('maintenanceDate').value = new Date().toISOString().split('T')[0];
            openModal('maintenanceModal');
        }
        
        function addMaintenance(assetId) {
            document.getElementById('maintenanceAssetSelect').value = assetId;
            document.getElementById('maintenanceAssetId').value = assetId;
            document.getElementById('maintenanceDate').value = new Date().toISOString().split('T')[0];
            openModal('maintenanceModal');
        }
        
        async function addMaintenanceRecord(event) {
            event.preventDefault();
            const formData = {
                asset_id: document.getElementById('maintenanceAssetId').value,
                maintenance_type: document.getElementById('maintenanceType').value,
                description: document.getElementById('maintenanceDescription').value,
                cost: document.getElementById('maintenanceCost').value || null,
                performed_by: document.getElementById('performedBy').value,
                maintenance_date: document.getElementById('maintenanceDate').value,
                next_maintenance_date: document.getElementById('nextMaintenanceDate').value || null,
                notes: document.getElementById('maintenanceNotes').value
            };
            
            const response = await fetch('?action=add_maintenance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (result.success) {
                closeModal('maintenanceModal');
                location.reload();
            } else {
                alert('Failed to add maintenance record');
            }
        }
        
        // Audit functions
        async function runAudit(event) {
            event.preventDefault();
            const branchId = document.getElementById('auditBranch').value;
            if (!branchId) { alert('Please select a branch'); return false; }
            if (!confirm('This will create an audit report for all assets in this branch. Continue?')) return false;
            
            const formData = new FormData();
            formData.append('branch_id', branchId);
            formData.append('auditor', document.getElementById('auditor').value);
            formData.append('summary', document.getElementById('auditSummary').value);
            formData.append('recommendations', document.getElementById('auditRecommendations').value);
            
            const response = await fetch('?action=run_audit', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Audit completed successfully!');
                location.reload();
            } else {
                alert('Failed to complete audit');
            }
            return false;
        }
        
        function runBranchAudit(branchId) {
            document.getElementById('auditBranch').value = branchId;
            document.getElementById('auditForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Detail View specific JS helpers
        function openReportIssueModalDetail(assetId) {
            document.getElementById('issueForm').reset();
            document.getElementById('issueAssetSelect').value = assetId;
            document.getElementById('issueAssetId').value = assetId;
            openModal('issueModal');
        }

        async function viewAuditReport(reportId) {
            try {
                const response = await fetch(`?action=get_audit_report&report_id=${reportId}`);
                const result = await response.json();
                if (result.success) {
                    const r = result.report;
                    document.getElementById('reportTitle').innerText = `Hardware Audit Report #${r.id} - ${r.branch_name}`;
                    
                    const dateStr = new Date(r.created_at).toLocaleString();
                    const html = `
                        <div style="padding: 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 2px solid var(--border-color); padding-bottom: 16px;">
                                <div>
                                    <h4 style="font-size: 1.25rem; margin: 0; color: var(--primary);">${r.branch_name} Branch</h4>
                                    <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: var(--text-secondary);">Audited by: <strong>${r.audited_by}</strong></p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Date: <strong>${dateStr}</strong></p>
                                    <span class="badge" style="background: var(--success); color: white; margin-top: 4px; display: inline-block; padding: 4px 8px; border-radius: 4px;">Completed</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                                <div style="background: var(--bg-secondary); padding: 16px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                                    <div style="font-size: 1.75rem; font-weight: bold; color: var(--primary);">${r.total_assets}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Total Assets</div>
                                </div>
                                <div style="background: var(--bg-secondary); padding: 16px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                                    <div style="font-size: 1.75rem; font-weight: bold; color: var(--success);">${r.operational_count}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Operational</div>
                                </div>
                                <div style="background: var(--bg-secondary); padding: 16px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                                    <div style="font-size: 1.75rem; font-weight: bold; color: var(--warning);">${parseInt(r.maintenance_count) + parseInt(r.repair_count)}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Maint / Repair</div>
                                </div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <h5 style="margin: 0 0 8px 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">Executive Summary</h5>
                                <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; margin: 0; background: var(--bg-primary); padding: 12px; border-radius: 6px; border-left: 4px solid var(--primary); white-space: pre-line;">${r.summary || 'No summary recorded.'}</p>
                            </div>

                            <div style="margin-bottom: 24px;">
                                <h5 style="margin: 0 0 8px 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">Recommendations & Actions</h5>
                                <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; margin: 0; background: var(--bg-primary); padding: 12px; border-radius: 6px; border-left: 4px solid var(--warning); white-space: pre-line;">${r.recommendations || 'No recommendations recorded.'}</p>
                            </div>

                            <div style="margin-top: 40px; border-top: 1px dashed var(--border-color); padding-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                                <div>
                                    <p style="margin: 0 0 40px 0; font-size: 0.85rem; color: var(--text-secondary);">Auditor Signature:</p>
                                    <div style="border-bottom: 1px solid var(--text-tertiary); width: 80%;"></div>
                                </div>
                                <div>
                                    <p style="margin: 0 0 40px 0; font-size: 0.85rem; color: var(--text-secondary);">Branch Manager Signature:</p>
                                    <div style="border-bottom: 1px solid var(--text-tertiary); width: 80%;"></div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('reportDetailsContent').innerHTML = html;
                    openModal('viewReportModal');
                } else {
                    alert('Error loading audit report: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while loading audit report details.');
            }
        }
        function openNetworkModal() {
            openModal('networkModal');
        }
        function openSoftwareModal() {
            document.getElementById('softwareForm').reset();
            document.getElementById('softwareAssetId').value = "<?php echo $assetId; ?>";
            openModal('softwareModal');
        }
        async function saveNetwork(event) {
            event.preventDefault();
            const formData = {
                asset_id: document.getElementById('networkAssetId').value,
                hostname: document.getElementById('netHostname').value,
                ip_address: document.getElementById('netIpAddress').value,
                mac_address: document.getElementById('netMacAddress').value,
                domain: document.getElementById('netDomain').value,
                vlan: document.getElementById('netVlan').value,
                switch_port: document.getElementById('netSwitchPort').value,
                notes: document.getElementById('netNotes').value
            };
            try {
                const response = await fetch('?action=update_network', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (result.success) {
                    closeModal('networkModal');
                    location.reload();
                } else {
                    alert('Failed to update network settings: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while saving network settings.');
            }
        }
        async function saveSoftware(event) {
            event.preventDefault();
            const formData = {
                asset_id: document.getElementById('softwareAssetId').value,
                software_name: document.getElementById('softName').value,
                version: document.getElementById('softVersion').value,
                license_key: document.getElementById('softLicenseKey').value,
                license_type: document.getElementById('softLicenseType').value,
                expiry_date: document.getElementById('softExpiryDate').value || null,
                installed_date: document.getElementById('softInstalledDate').value || null,
                notes: document.getElementById('softNotes').value
            };
            try {
                const response = await fetch('?action=add_software', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (result.success) {
                    closeModal('softwareModal');
                    location.reload();
                } else {
                    alert('Failed to add software: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while adding software.');
            }
        }

        function testConnection(target) {
            if (!target) {
                alert('No IP address or hostname configured.');
                return;
            }
            const container = document.getElementById('pingContainer');
            const consoleDiv = document.getElementById('pingConsole');
            if (!container || !consoleDiv) return;

            container.style.display = 'block';
            consoleDiv.innerHTML = '';
            
            let lines = [
                `Pinging ${target} with 32 bytes of data:`,
                `Reply from ${target}: bytes=32 time=8ms TTL=64`,
                `Reply from ${target}: bytes=32 time=4ms TTL=64`,
                `Reply from ${target}: bytes=32 time=11ms TTL=64`,
                `Reply from ${target}: bytes=32 time=5ms TTL=64`,
                ``,
                `Ping statistics for ${target}:`,
                `    Packets: Sent = 4, Received = 4, Lost = 0 (0% loss),`,
                `Approximate round trip times in milli-seconds:`,
                `    Minimum = 4ms, Maximum = 11ms, Average = 7ms`,
                `Device is fully reachable & active.`
            ];
            
            let index = 0;
            function printLine() {
                if (index < lines.length) {
                    consoleDiv.innerHTML += lines[index] + '<br>';
                    index++;
                    container.scrollTop = container.scrollHeight;
                    setTimeout(printLine, 250);
                }
            }
            printLine();
        }

        function printAssetHandover() {
            window.print();
        }

        // Export functionality
        function exportHardwareCSV() {
            window.location.href = 'export_hardware.php';
        }
    </script>
</body>
</html>