<?php
// index.php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$currentUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALDIS COFFEE - HR System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            background-color: #6b3a3a;   
          
            overflow: show !important;
            transition: width 0.5s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar.closed {
            left: -250px !important;
        }

        .sidebar.open {
            left: 0 !important;
        }

        /* Minimized Sidebar */
        .sidebar.minimized {
            width: 80px;
            overflow: visible;
        }

        .sidebar.minimized .sidebar-header-wrapper {
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .sidebar.minimized .sidebar-header-text {
            display: none;
        }

        .sidebar.minimized a,
        .sidebar.minimized .dropdown-btn {
            padding: 12px 10px;
            font-size: 12px;
            text-align: center;
            width: 100%;
        }

        .sidebar.minimized a i,
        .sidebar.minimized .dropdown-btn i {
            display: block;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .sidebar.minimized a span,
        .sidebar.minimized .dropdown-btn span {
            display: none;
        }

        .sidebar.minimized .fa-caret-down {
            display: none;
        }

        .sidebar.minimized #sidebar-logo {
            width: 60px;
            height: 60px;
            margin: 10px auto;
            padding: 5px;
        }

        .sidebar.minimized .dropdown-container {
            display: none;
            position: fixed;
            left: 80px;
            background-color: #6b3a3a;
            min-width: 180px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            border-radius: 4px;
            z-index: 999;
            top: 150px;
        }

        .sidebar.minimized .dropdown-container.show {
            display: block;
        }

        .sidebar.minimized .dropdown-container a {
            padding: 10px 15px;
            font-size: 13px;
            text-align: left;
            display: block;
            white-space: nowrap;
        }

        .sidebar.minimized .dropdown-container a i {
            display: none;
        }

        .sidebar.minimized .dropdown-container a span {
            display: inline;
        }

        /* Hamburger Button */
        .hamburger-btn {
            background: none;
            border: none;
            color: #f1f1f1;
            font-size: 20px;
            cursor: pointer;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            margin-left: 10px;
        }

        .hamburger-btn:hover {
            color: #ffb74d;
        }

        .sidebar-header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
        }

        .sidebar.minimized .sidebar-header-wrapper {
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 5px 0;
        }

        .sidebar.minimized .hamburger-btn {
            margin-left: 0;
            margin-top: 5px;
        }

        .sidebar a, .dropdown-btn {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
            color: #f1f1f1;
            display: block;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            outline: none;
            transition: 0.3s;
        }

        .sidebar a:hover, .dropdown-btn:hover {
            background-color:#6b3a3a;
        }

        .sidebar a.active, .dropdown-btn.active {
            background-color: #6b3a3a;
            color: white;
            font-weight: bold;
            border-left: 4px solid #ff9800;
        }

        .dropdown-container {
            display: none;
            background-color: #6b3a3a;
            padding-left: 10px;
        }

        .dropdown-container.show {
            display: block;
        }

        .dropdown-container a {
            font-size: 13px;
            padding: 10px 20px 10px 30px;
        }

        .fa-caret-down {
            float: right;
            padding-right: 8px;
        }

        #sidebar-logo {
            text-align: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        /* Make sidebar logo sit on the same background and remove white box visually */
#sidebar-logo {
    text-align: center;
    margin-bottom: 25px;
    padding: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    width: 70px;
    height: 70px;

    background: #f8f8f8;
    border-radius: 50%;

    margin-left: auto;
    margin-right: auto;
    margin-top: 30px; /* Move down */

    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}    #sidebar-logo img {
            max-width: 190%;
            max-height: 300%;
            object-fit: contain;
            display: block;
            mix-blend-mode: multiply; /* white pixels will blend into background */
        }

        .sidebar-header-text {
            padding: 0 10px 20px 10px;
            text-align: center;
            border-bottom: 1px solid #6b3a3a;
        }

        /* Main Content */
        #main {
            margin-left: 250px;
            padding: 18px 18px 18px;
            transition: margin-left 0.5s ease;
            min-height: 100vh;
        }

        #main.sidebar-minimized {
            margin-left: 80px;
        }

        .header {
            background: white;
            padding: 12px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
            color: #6b3a3a;
        }

        .lang-btn {
            background-color: #6b3a3a;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .lang-btn:hover {
            background-color: #6b3a3a;
        }

        .open-btn {
            display: none;
            background: none;
            color: #6b3a3a;
            font-size: 24px;
            border: none;
            cursor: pointer;
            margin-right: 15px;
        }

        /* Content Pages */
        .content-page {
            display: none;
            animation: fadeEffect 0.5s;
            background: white;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        @keyframes fadeEffect {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes popUpEffect {
            from { opacity: 0; transform: scale(0.95) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Cards */
        .card-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .card {
            flex: 1;
            min-width: 200px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 5px solid #6b3a3a;
        }

        .card h3 {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .card .number {
            font-size: 32px;
            font-weight: bold;
            color: #6b3a3a;
            margin-top: 5px;
        }

        .dashboard-filter-card {
            background: #fff;
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            padding: 14px 16px 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .dashboard-filter-card h3 {
            margin: 0 0 14px;
            font-size: 15px;
            color: #6b3a3a;
        }

        .dashboard-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            align-items: flex-end;
        }

        .dashboard-filter-grid label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .dashboard-report-card {
            background: white;
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .dashboard-report-card h3 {
            margin-top: 0;
            color: #6b3a3a;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .report-chart-wrapper {
            margin-top: 16px;
        }

        .report-chart-wrapper canvas {
            width: 100% !important;
            max-height: 260px;
        }

        .top-report-table td {
            padding: 8px;
        }

        .dashboard-filter-grid select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
        }

        /* Forms */
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 13px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .submit-btn {
            background-color: #6b3a3a;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #6b3a3a;
        }

        .cancel-btn {
            background-color: #f8f9fa;
            color: #333;
            padding: 12px 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            margin-bottom: 10px;
        }

        .cancel-btn:hover {
            background-color: #e2e6ea;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #6b3a3a;
            color: white;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .link-btn {
            text-decoration: none;
            color: #007bff;
            padding: 4px 8px;
            border: 1px solid #007bff;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
            margin: 2px;
        }

        .link-btn:hover {
            background: #007bff;
            color: white;
        }

        /* Settings Lists */
        .settings-lists-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .list-box {
            flex: 1;
            min-width: 250px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .item-list {
            flex: 1;
            overflow-y: auto;
            max-height: 200px;
            min-height: 100px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 10px;
            padding: 5px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .delete-list-item {
            color: #dc3545;
            cursor: pointer;
            font-size: 16px;
        }

        .delete-list-item:hover {
            color: #c82333;
        }

        .add-row {
            display: flex;
            gap: 5px;
        }

        .add-btn {
            background-color: #5D4037;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
            font-weight: bold;
        }

        .add-btn:hover {
            background-color: #3E2723;
        }

        /* Reference List */
        .voucher-list {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }

        .voucher-list h4 {
            background: #f9f9f9;
            margin: 0;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            color: #6b3a3a;
        }

        .ref-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .ref-table th {
            background-color: #6b3a3a;
            color: white;
            text-align: left;
            padding: 8px;
        }

        .ref-table td {
            border-bottom: 1px solid #eee;
            padding: 8px;
            vertical-align: middle;
        }

        .ref-table tr:hover {
            background-color: #f5f5f5;
        }

        .select-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }

        .select-btn:hover {
            background: #138496;
        }

        /* Form Wrapper */
        .form-active {
            animation: popUpEffect 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        /* Loading Overlay */
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            color: white;
            font-size: 18px;
        }

        /* Success Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        }

        .success-popup {
            background-color: #fff;
            width: 90%;
            max-width: 400px;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: scaleUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .checkmark-circle {
            width: 80px;
            height: 80px;
            background-color: #4CAF50;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.4);
        }

        .checkmark-icon {
            color: white;
            font-size: 40px;
            font-weight: bold;
        }

        .modal-title {
            font-size: 24px;
            color: #333;
            margin: 0 0 10px 0;
        }

        .modal-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-ok-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .modal-ok-btn:hover {
            background-color: #45a049;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleUp {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Telegram Section */
        .telegram-section {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #c8e6c9;
            margin-top: 20px;
        }

        .settings-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-top: 20px;
        }

        .logo-section {
            background: #eef;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ccd;
            margin-top: 20px;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 0;
                padding-top: 0;
            }
            #main {
                margin-left: 0;
            }
            .open-btn {
                display: block !important;
            }
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div id="loading"><i class="fa fa-spinner fa-spin"></i> Processing...</div>

<!-- Success Modal -->
<div id="successModal" class="modal-overlay">
    <div class="success-popup">
        <div class="checkmark-circle">
            <div class="checkmark-icon"><i class="fa fa-check"></i></div>
        </div>
        <h2 class="modal-title" id="modal-title">Success!</h2>
        <p class="modal-message" id="modal-message">Request saved successfully.</p>
        <button class="modal-ok-btn" id="modal-close-btn">OK</button>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar" id="mySidebar">
    <div id="sidebar-logo">
        <img id="img-logo" src="" alt="Logo">
    </div>

    <div class="sidebar-header-wrapper">
        <div class="sidebar-header-text">
            <h2 style="color: white; margin: 0; font-size: 18px;">KALDIS COFFEE</h2>
            <small style="color: #ccc;" id="txt-subtitle">HR Management</small>
        </div>
        <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebarMinimize()" title="Toggle Sidebar">
            <i class="fa fa-bars"></i>
        </button>
    </div>

    <a href="javascript:void(0)" onclick="openPage('Dashboard')" id="nav-Dashboard">
        <i class="fa fa-home"></i> <span id="txt-dashboard">Dashboard</span>
    </a>

    <button id="btn-hr-system" class="dropdown-btn">
        <i class="fa fa-file-text"></i> <span id="txt-hr-system">HR System</span> <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-container" id="container-HRSystem">
        <?php if ($currentRole !== 'User'): ?>
        <a href="javascript:void(0)" onclick="openPage('Form-Internship')" id="txt-nav-internship">Internship Request</a>
        <?php endif; ?>
        <a href="javascript:void(0)" onclick="openPage('Form-Qualification')" id="txt-nav-qual">Qualification</a>
        <?php if ($currentRole !== 'User'): ?>
        <a href="javascript:void(0)" onclick="openPage('Form-Recruitment')" id="txt-nav-rec">Recruitment</a>
        <?php endif; ?>
    </div>

    <button id="btn-view-list" class="dropdown-btn">
        <i class="fa fa-list"></i> <span id="txt-view-list">View List</span> <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-container" id="container-ViewList">
        <a href="javascript:void(0)" onclick="openPage('List-Internship')" id="txt-nav-list-internship">Internship List</a>
        <a href="javascript:void(0)" onclick="openPage('List-Qualification')" id="txt-nav-list-qual">Qualification List</a>
        <a href="javascript:void(0)" onclick="openPage('List-Recruitment')" id="txt-nav-list-rec">Recruitment List</a>
    </div>
    
    <?php if ($currentRole === 'Superadmin'): ?>
    <a href="javascript:void(0)" onclick="openPage('Settings')" id="txt-settings">
        <i class="fa fa-cog"></i> <span id="txt-settings-text">Settings</span>
    </a>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div id="main">
    <div class="header">
        <div style="display:flex; align-items:center;">
            <h2 id="pageTitle" style="margin-left: 0;">HR System</h2>
        </div>
        <div style="display:flex; align-items:center; gap:15px;">
            <div id="successMsg" style="color: green; font-weight: bold; display:none;"></div>
            <div style="color:#4a2c2a; font-size:13px;">
                <strong><?php echo htmlspecialchars($currentUser); ?></strong> (<?php echo htmlspecialchars($currentRole); ?>)
            </div>
            <button id="langBtn" class="lang-btn" onclick="toggleLanguage()">አማ</button>
            <a href="logout.php" class="lang-btn" style="background:#dc3545;">Logout</a>
        </div>
        
    </div>

    <!-- Dashboard Page -->
    <div id="page-Dashboard" class="content-page" style="display:none;">
        <div class="dashboard-filter-card">
            <div class="dashboard-filter-grid">
                <div>
                    <label for="dashboard-filter-to-branch">To Branch</label>
                    <select id="dashboard-filter-to-branch" onchange="applyDashboardFilters()">
                        <option value="all">All</option>
                    </select>
                </div>
                <div>
                    <label for="dashboard-filter-work-branch">Work Branch</label>
                    <select id="dashboard-filter-work-branch" onchange="applyDashboardFilters()">
                        <option value="all">All</option>
                    </select>
                </div>
                <div>
                    <label for="dashboard-filter-job">Job</label>
                    <select id="dashboard-filter-job" onchange="applyDashboardFilters()">
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-container">
            <div class="card" style="border-left-color: #ff9800;">
                <h3>Internship Requests</h3>
                <div class="number" id="stat-internship">0</div>
            </div>
            <div class="card" style="border-left-color: #2196F3;">
                <h3>Qualification Requests</h3>
                <div class="number" id="stat-qualification">0</div>
            </div>
            <div class="card" style="border-left-color: #4CAF50;">
                <h3>Recruitment Requests</h3>
                <div class="number" id="stat-recruitment">0</div>
            </div>
            <div class="card" style="border-left-color: #9c27b0;">
                <h3>All Requests</h3>
                <div class="number" id="stat-total">0</div>
            </div>
        </div>
        <div class="dashboard-report-grid">
            <div class="dashboard-report-card">
                <h3>Top Internship Requests</h3>
            
                <div class="report-chart-wrapper">
                    <canvas id="chart-internship"></canvas>
                </div>
            </div>
            <div class="dashboard-report-card">
                <h3>Top Qualification Requests</h3>
              
                <div class="report-chart-wrapper">
                    <canvas id="chart-qualification"></canvas>
                </div>
            </div>
            <div class="dashboard-report-card" style="grid-column: span 2;">
                <h3>Top Recruitment Requests</h3>
                
                <div class="report-chart-wrapper">
                    <canvas id="chart-recruitment"></canvas>
                </div>
            </div>
        </div>

        <!-- Activity Trend Section -->
        <div style="margin-top: 30px;">
            <div class="dashboard-report-card">
                <h3>Monthly Activity Trend</h3>
                <div class="report-chart-wrapper" style="height: 300px;">
                    <canvas id="chart-monthly"></canvas>
                </div>
            </div>
        </div>
    </div>
        
    <!-- Internship Form -->
    <div id="page-Form-Internship" class="content-page">
        <h3 id="txt-form-internship">Internship Request Form</h3>
        <form id="formInternship">
            <div class="form-row">
                <div class="form-group">
                    <label id="lbl-date">Date</label>
                    <input type="date" name="date" id="i_date" required>
                </div>
                <div class="form-group">
                    <label id="lbl-to-branch">To Branch</label>
                    <select name="to_branch" id="i_to_branch" required>
                        <option value="">Select...</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label id="lbl-name">Name</label>
                    <input type="text" name="name" id="i_name" required>
                </div>
                <div class="form-group">
                    <label id="lbl-gender">Gender</label>
                    <select name="gender" id="i_gender">
                        <option value="ወ/ሮ">ወ/ሮ</option>
                        <option value="ወ/ሪት">ወ/ሪት</option>
                        <option value="አቶ">አቶ</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label id="lbl-work-branch">Work Branch</label>
                    <select name="work_branch" id="i_work_branch" required>
                        <option value="">Select...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="lbl-job">Job Title</label>
                    <select name="job" id="i_job" required>
                        <option value="">Select...</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label id="lbl-start-date">Start Date</label>
                <input type="date" name="start_date" id="i_start_date" required>
            </div>
            <button type="submit" class="submit-btn" id="btn-submit">Submit Request</button>
        </form>
    </div>

    <!-- Qualification Form -->
    <div id="page-Form-Qualification" class="content-page">
        <h3 id="txt-form-qual">Qualification Form</h3>
        
        <div id="qualification-form-wrapper" style="display:none; border: 2px solid #4a2c2a; padding: 20px; margin-bottom: 30px; background:#fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <form id="formQualification">
                <input type="hidden" id="ref_intern_id" name="ref_intern_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-date-q">Date</label>
                        <input type="date" name="q_date" id="q_date" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-branch-q">Branch</label>
                        <select name="q_branch" id="q_branch" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-name-q">Name</label>
                        <input type="text" name="q_name" id="q_name" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-gender-q">Gender</label>
                        <select name="q_gender" id="q_gender">
                            <option value="ወ/ሮ">ወ/ሮ</option>
                            <option value="ወ/ሪት">ወ/ሪት</option>
                            <option value="አቶ">አቶ</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-job-q">Job Title</label>
                        <select name="q_job" id="q_job" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="lbl-intern-start">Internship Start</label>
                        <input type="date" name="q_intern_start" id="q_intern_start" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-prob-start">Probation Start</label>
                        <input type="date" name="q_probation_start" id="q_probation_start" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-destination">Destination</label>
                        <select name="q_destination" id="q_destination">
                            <option value="">None</option>
                        </select>
                    </div>
                </div>
                <hr>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-mgr-name">Manager Name</label>
                        <input type="text" name="q_manager_name" id="q_manager_name" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-mgr-rank">Manager Rank</label>
                        <select name="q_manager_rank" id="q_manager_rank" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>
                
                <div style="display:flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="cancel-btn" onclick="backToQualificationList()">Back to List</button>
                    <button type="submit" class="submit-btn" id="btn-submit-q">Submit Request</button>
                </div>
            </form>
        </div>

        <div class="voucher-list" id="qual-list-wrapper">
            <h4 id="txt-ref-internship">Select Internship Request (Reference)</h4>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                <input id="filter-intern-name" type="text" placeholder="Name" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-intern-to-branch" type="text" placeholder="To Branch" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-intern-work-branch" type="text" placeholder="Work Branch" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-intern-job" type="text" placeholder="Job" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-intern-start-date-from" type="date" placeholder="Start Date From" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-intern-start-date-to" type="date" placeholder="Start Date To" oninput="filterInternshipRefList()" style="flex:1; min-width:140px; padding:8px;" />
            </div>
            <div id="internship-list-container">Loading pending internships...</div>
        </div>
    </div>

    <!-- Recruitment Form -->
    <div id="page-Form-Recruitment" class="content-page">
        <h3 id="txt-form-rec">Recruitment Form</h3>

        <div id="recruitment-form-wrapper" style="display:none; border: 2px solid #4a2c2a; padding: 20px; margin-bottom: 30px; background:#fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <form id="formRecruitment">
                <input type="hidden" id="ref_qual_id" name="ref_qual_id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-date-r">Date</label>
                        <input type="date" name="r_date" id="r_date" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-branch-r">Branch</label>
                        <select name="r_branch" id="r_branch" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-name-r">Name</label>
                        <input type="text" name="r_name" id="r_name" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-gender-r">Gender</label>
                        <select name="r_gender" id="r_gender">
                            <option value="ወ/ሮ">ወ/ሮ</option>
                            <option value="ወ/ሪት">ወ/ሪት</option>
                            <option value="አቶ">አቶ</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-req-job">Requested Job</label>
                        <select name="r_req_job" id="r_req_job" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="lbl-assign-job">Assigned Job</label>
                        <select name="r_job" id="r_job" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label id="lbl-start-date-r">Start Date</label>
                        <input type="date" name="r_start_date" id="r_start_date" required>
                    </div>
                    <div class="form-group">
                        <label id="lbl-destination-r">Destination</label>
                        <select name="r_destination" id="r_destination">
                            <option value="">None</option>
                        </select>
                    </div>
                </div>
                
                <div style="display:flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="cancel-btn" onclick="backToRecruitmentList()">Back to List</button>
                    <button type="submit" class="submit-btn" id="btn-submit-r">Submit Request</button>
                </div>
            </form>
        </div>

        <div class="voucher-list" id="rec-list-wrapper">
            <h4 id="txt-ref-qual">Select Qualification (Reference)</h4>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                <input id="filter-qual-name" type="text" placeholder="Name" oninput="filterQualificationRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-qual-branch" type="text" placeholder="Branch" oninput="filterQualificationRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-qual-job" type="text" placeholder="Job" oninput="filterQualificationRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-qual-probation-from" type="date" placeholder="Probation From" oninput="filterQualificationRefList()" style="flex:1; min-width:140px; padding:8px;" />
                <input id="filter-qual-probation-to" type="date" placeholder="Probation To" oninput="filterQualificationRefList()" style="flex:1; min-width:140px; padding:8px;" />
            </div>
            <div id="qualification-list-container">Loading pending qualifications...</div>
        </div>
    </div>

    <!-- List Pages -->
    <div id="page-List-Internship" class="content-page">
        <h3 id="txt-list-internship">Internship List</h3>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            <input id="filter-list-intern-name" type="text" placeholder="Name" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-intern-to-branch" type="text" placeholder="To Branch" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-intern-work-branch" type="text" placeholder="Work Branch" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-intern-job" type="text" placeholder="Job" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-intern-start-date-from" type="date" placeholder="Start Date From" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-intern-start-date-to" type="date" placeholder="Start Date To" oninput="renderInternshipList()" style="flex:1; min-width:140px; padding:8px;" />
        </div>
        <div id="table-Internship">Loading data...</div>
    </div>
    
    <div id="page-List-Qualification" class="content-page">
        <h3 id="txt-list-qual">Qualification List</h3>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            <input id="filter-list-qual-name" type="text" placeholder="Name" oninput="renderQualificationList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-qual-branch" type="text" placeholder="Branch" oninput="renderQualificationList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-qual-job" type="text" placeholder="Job" oninput="renderQualificationList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-qual-probation-from" type="date" placeholder="Probation From" oninput="renderQualificationList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-qual-probation-to" type="date" placeholder="Probation To" oninput="renderQualificationList()" style="flex:1; min-width:140px; padding:8px;" />
        </div>
        <div id="table-Qualification">Loading data...</div>
    </div>
    
    <div id="page-List-Recruitment" class="content-page">
        <h3 id="txt-list-rec">Recruitment List</h3>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            <input id="filter-list-rec-name" type="text" placeholder="Name" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-rec-branch" type="text" placeholder="Branch" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-rec-requested-job" type="text" placeholder="Requested Job" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-rec-assigned-job" type="text" placeholder="Assigned Job" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-rec-start-date-from" type="date" placeholder="Start Date From" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
            <input id="filter-list-rec-start-date-to" type="date" placeholder="Start Date To" oninput="renderRecruitmentList()" style="flex:1; min-width:140px; padding:8px;" />
        </div>
        <div id="table-Recruitment">Loading data...</div>
    </div>

    <!-- Settings Page -->
    <div id="page-Settings" class="content-page">
        <h3 id="txt-set-title">System Settings</h3>
        
        <!-- Telegram Configuration -->
        <div class="telegram-section">
            <h4 style="margin-top:0; color:#2e7d32;">📱 Telegram Configuration</h4>
            <p style="font-size:13px; margin-bottom:15px;">
                1. Create a bot via <b>BotFather</b> and get the Token.<br>
                2. For each branch, add the Telegram Chat ID (e.g., -100123456789).
            </p>
            <div class="form-group">
                <label>Bot Token</label>
                <input type="text" id="input-bot-token" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" />
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="submit-btn" style="width: auto; background-color:#2e7d32;" onclick="saveBotToken()">Save Bot Token</button>
                <button class="submit-btn" style="width: auto; background-color:#0288d1;" onclick="testTelegram()">Test Connection</button>
            </div>
        </div>

        <!-- User Control -->
        <div class="user-management-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #4a2c2a;">
            <h4 style="margin-top:0; color:#333;">👥 User Control</h4>
            <p style="font-size:13px; margin-bottom:20px; color:#666;">Manage system users, add new users, and change passwords.</p>
            
            <!-- Add New User Form -->
            <div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <div style="font-weight: bold; color: #4a2c2a; margin-bottom: 15px;">Add New User</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">Username</label>
                        <input type="text" id="input-new-username" placeholder="Enter username" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">Password</label>
                        <input type="password" id="input-new-password" placeholder="Min 6 characters" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">Role</label>
                        <select id="input-new-role" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;">
                            <option value="User">User</option>
                            <option value="Admin">Admin</option>
                            <option value="Superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>
                <button class="add-btn" onclick="addNewUser()" style="width: auto;">Add User</button>
            </div>

            <!-- Users List -->
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                <div style="font-weight: bold; color: #4a2c2a; margin-bottom: 15px;">User List</div>
                <div id="users-list-container" style="min-height: 200px;">
                    <div style="text-align: center; color: #999; padding: 20px;">Loading users...</div>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="settings-section">
            <h4 style="margin-top:0; color:#333;">Data Management</h4>
            <p style="margin-bottom:20px;">Manage Branches, Jobs, Ranks, and Destinations.</p>
            
            <div class="settings-lists-container">
                <!-- Branches -->
                <div class="list-box">
                    <div style="font-weight:bold; color:#4a2c2a; margin-bottom:10px;">Branches & Chat IDs</div>
                    <div id="list-branches" class="item-list"></div>
                    <div class="add-row">
                        <input type="text" id="input-branch" placeholder="New Branch Name..." style="flex:1;" />
                        <button class="add-btn" onclick="addBranch()">Add Branch</button>
                    </div>
                </div>

                <!-- Jobs -->
                <div class="list-box">
                    <div style="font-weight:bold; color:#4a2c2a; margin-bottom:10px;">Job Titles</div>
                    <div id="list-jobs" class="item-list"></div>
                    <div class="add-row">
                        <input type="text" id="input-job" placeholder="New Job Title..." style="flex:1;" />
                        <button class="add-btn" onclick="addItem('jobs')">Add</button>
                    </div>
                </div>

                <!-- Ranks -->
                <div class="list-box">
                    <div style="font-weight:bold; color:#4a2c2a; margin-bottom:10px;">Manager Ranks</div>
                    <div id="list-ranks" class="item-list"></div>
                    <div class="add-row">
                        <input type="text" id="input-rank" placeholder="New Rank..." style="flex:1;" />
                        <button class="add-btn" onclick="addItem('ranks')">Add</button>
                    </div>
                </div>
                
                <!-- Destinations -->
                <div class="list-box">
                    <div style="font-weight:bold; color:#4a2c2a; margin-bottom:10px;">Destinations & Chat IDs</div>
                    <div id="list-destinations" class="item-list"></div>
                    <div class="add-row">
                        <input type="text" id="input-destination" placeholder="New Destination..." style="flex:1;" />
                        <button class="add-btn" onclick="addItem('destinations')">Add</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Logo Section -->
        <div class="logo-section">
            <h4 style="margin-top:0;">Logo Configuration</h4>
            <p>Paste your image URL below.</p>
            <div class="form-group">
                <label>Logo Image URL</label>
                <input type="text" id="input-logo-url" placeholder="https://..." />
            </div>
            <button class="submit-btn" style="width: auto;" onclick="saveLogoSettings()">Save Logo</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Global Variables
    var currentLang = 'en';
    var currentEditId = null;
    var currentLogoUrl = "";
    var CURRENT_ROLE = '<?php echo htmlspecialchars($currentRole ?? "User"); ?>';
    var settingsData = { branches: [], jobs: [], ranks: [], branchChatIds: {}, destinations: [], destChatIds: {} };
    var currentPage = 'Form-Internship';
    var dashboardAllRequests = [];
    var internshipChart = null;
    var qualificationChart = null;
    var recruitmentChart = null;
    var weeklyChart = null;
    var monthlyChart = null;
    var isUpdating = false;

    // Translations
    var translations = {
        en: { 
            'txt-subtitle': 'HR Management', 'txt-hr-system': 'HR System', 'txt-dashboard': 'Dashboard',
            'txt-view-list': 'View List', 'txt-settings-text': 'Settings', 'txt-nav-internship': 'Internship Request', 
            'txt-nav-qual': 'Qualification', 'txt-nav-rec': 'Recruitment', 'txt-nav-list-internship': 'Internship List', 
            'txt-nav-list-qual': 'Qualification List', 'txt-nav-list-rec': 'Recruitment List', 
            'txt-stat-interns': 'Internship Requests', 'txt-stat-quals': 'Qualification Requests', 
            'txt-welcome': 'Welcome to LIDESS COFFEE HR System.', 'lbl-date': 'Date', 
            'lbl-to-branch': 'To Branch', 'lbl-name': 'Name', 'lbl-gender': 'Gender', 
            'lbl-work-branch': 'Work Branch', 'lbl-job': 'Job Title', 'lbl-start-date': 'Start Date', 
            'lbl-branch-q': 'Branch', 'lbl-intern-start': 'Internship Start', 'lbl-prob-start': 'Probation Start', 
            'lbl-destination': 'Destination', 'lbl-destination-r': 'Destination',
            'lbl-mgr-name': 'Manager Name', 'lbl-mgr-rank': 'Manager Rank', 'lbl-req-job': 'Requested Job', 
            'lbl-assign-job': 'Assigned Job', 'btn-submit': 'Submit Request', 
            'txt-form-internship': 'Internship Request Form', 'txt-form-qual': 'Qualification Form', 
            'txt-form-rec': 'Recruitment Form', 'txt-list-internship': 'Internship List', 
            'txt-list-qual': 'Qualification List', 'txt-list-rec': 'Recruitment List', 
            'txt-set-title': 'System Settings', 'txt-ref-internship': 'Select Internship Request (Reference)', 
            'txt-ref-qual': 'Select Qualification (Reference)', 
            'pageTitle': 'Dashboard'
        },
        am: { 
            'txt-subtitle': 'የሰው ኃይል አስተዳደር', 'txt-hr-system': 'የሰው ኃይል ስርዓት',
            'txt-view-list': 'ዝርዝር መመልከት', 'txt-settings-text': 'ቅንብሮች', 'txt-nav-internship': 'የሥራ ልምምድ ጥያቄ', 'txt-dashboard': 'ዳሽቦርድ', 
            'txt-nav-qual': 'የብቃት መግለጫ', 'txt-nav-rec': 'የቅጥር ማሳወቂያ', 'txt-nav-list-internship': 'የልምምድ ዝርዝር', 
            'txt-nav-list-qual': 'የብቃት ዝርዝር', 'txt-nav-list-rec': 'የቅጥር ዝርዝር', 

            'txt-welcome': 'ወደ ልደስ ቡና የሰው ኃይል ስርዓት እንኳን በደህና መጡ።', 'lbl-date': 'ቀን', 
            'lbl-to-branch': 'ለቅርንጫፍ', 'lbl-name': 'ስም', 'lbl-gender': 'ፆታ', 
            'lbl-work-branch': 'የስራ ቅርንጫፍ', 'lbl-job': 'የስራ መደብ', 'lbl-start-date': 'የመጀመሪያ ቀን', 
            'lbl-branch-q': 'ቅርንጫፍ', 'lbl-intern-start': 'የልምምድ መጀመሪያ', 'lbl-prob-start': 'የሙከራ መጀመሪያ', 
            'lbl-destination': 'መድረሻ', 'lbl-destination-r': 'መድረሻ',
            'lbl-mgr-name': 'የኃላፊ ስም', 'lbl-mgr-rank': 'የኃላፊነት ደረጃ', 'lbl-req-job': 'የተጠየቀው ስራ', 
            'lbl-assign-job': 'የተመደበው ስራ', 'btn-submit': 'ጥያቄ አስገባ', 
            'txt-form-internship': 'የሥራ ልምምድ ቅጽ', 'txt-form-qual': 'የብቃት ቅጽ', 
            'txt-form-rec': 'የቅጥር ቅጽ', 'txt-list-internship': 'የልምምድ ዝርዝር', 
            'txt-list-qual': 'የብቃት ዝርዝር', 'txt-list-rec': 'የቅጥር ዝርዝር', 
            'txt-set-title': 'የስርዓት ቅንብሮች', 'txt-ref-internship': 'የልምምድ ጥያቄ ምረጥ (ማመሳከያ)', 
            'txt-ref-qual': 'የብቃት ማመሳከያ ምረጥ'
        }
    };

    // Utility Functions
    function toggleLanguage() {
        currentLang = currentLang === 'en' ? 'am' : 'en';
        document.getElementById('langBtn').innerText = currentLang === 'en' ? 'አማ' : 'En';
        for (var key in translations[currentLang]) {
            var el = document.getElementById(key);
            if (el) el.innerText = translations[currentLang][key];
        }
    }

    function showLoading(show) {
        document.getElementById("loading").style.display = show ? "flex" : "none";
    }
function showError(msg) {
    // Create a simple error alert or toast
    alert("Error: " + msg);
    // Alternative: create a temporary error message div
    var errorDiv = document.createElement('div');
    errorDiv.style.position = 'fixed';
    errorDiv.style.bottom = '20px';
    errorDiv.style.right = '20px';
    errorDiv.style.backgroundColor = '#f44336';
    errorDiv.style.color = 'white';
    errorDiv.style.padding = '12px 20px';
    errorDiv.style.borderRadius = '4px';
    errorDiv.style.zIndex = '9999';
    errorDiv.innerText = msg;
    document.body.appendChild(errorDiv);
    setTimeout(function() {
        errorDiv.remove();
    }, 4000);
}
    function showSuccessPopup(title, message) {
        var modal = document.getElementById('successModal');
        document.getElementById('modal-title').innerText = title;
        document.getElementById('modal-message').innerText = message;
        modal.style.display = 'flex';
        document.getElementById('modal-close-btn').onclick = function() {
            modal.style.display = 'none';
        };
    }

    function showSuccess(msg) {
        var el = document.getElementById("successMsg");
        el.innerText = msg;
        el.style.display = "block";
        setTimeout(function() { el.style.display = "none"; }, 4000);
    }

    function populateSelect(selectEl, options) {
        selectEl.innerHTML = '<option value="">Select...</option>';
        options.forEach(function(opt) {
            var option = document.createElement("option");
            option.value = opt;
            option.innerText = opt;
            selectEl.appendChild(option);
        });
    }

    // API Calls
    async function apiCall(url, data = null) {
        showLoading(true);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // Increased from 10s to 30s
        try {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                signal: controller.signal
            };
            if (data) {
                options.body = JSON.stringify(data);
            }
            const response = await fetch(url, options);
            clearTimeout(timeoutId);
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
                alert("Server returned invalid JSON:\n" + text);
                return null;
            }
        } catch (error) {
            console.error(error);
            if (error.name === 'AbortError') {
                alert('Request timed out. Please try again.');
            } else {
                alert("Error: " + error.message);
            }
            return null;
        } finally {
            clearTimeout(timeoutId);
            showLoading(false);
        }
    }

    var internshipRefData = [];
    var qualificationRefData = [];
    var viewListData = [];

    function normalizeFilter(value) {
        return String(value || '').toLowerCase().trim();
    }

    function matchesFilter(value, query) {
        if (!query) return true;
        return normalizeFilter(value).indexOf(normalizeFilter(query)) !== -1;
    }

    function matchesDateRange(value, from, to) {
        if (!from && !to) return true;
        if (!value) return false;
        var dateValue = new Date(value);
        if (isNaN(dateValue.getTime())) return false;
        if (from) {
            var fromDate = new Date(from);
            if (dateValue < fromDate) return false;
        }
        if (to) {
            var toDate = new Date(to);
            if (dateValue > toDate) return false;
        }
        return true;
    }

    // Dashboard: fetch summary counts
    async function loadDashboard() {
        resetDashboardStats();
        const data = await apiCall('api/get_data.php?type=all_requests');
        if (!Array.isArray(data)) {
            dashboardAllRequests = [];
            populateDashboardFilterOptions([]);
            return;
        }
        dashboardAllRequests = data;
        populateDashboardFilterOptions(data);
        applyDashboardFilters();
    }

    function resetDashboardStats() {
        document.getElementById('stat-internship').innerText = '0';
        document.getElementById('stat-qualification').innerText = '0';
        document.getElementById('stat-recruitment').innerText = '0';
        document.getElementById('stat-total').innerText = '0';
    }

    function populateDashboardFilterOptions(data) {
        var toBranchSet = new Set();
        var workBranchSet = new Set();
        var jobSet = new Set();

        data.forEach(function(item) {
            if (item.type === 'Internship') {
                if (item.to_branch) toBranchSet.add(item.to_branch);
                if (item.work_branch) workBranchSet.add(item.work_branch);
                if (item.job) jobSet.add(item.job);
            } else if (item.type === 'Qualification' || item.type === 'Recruitment') {
                if (item.branch) workBranchSet.add(item.branch);
                if (item.job) jobSet.add(item.job);
                if (item.req_job) jobSet.add(item.req_job);
                if (item.assign_job) jobSet.add(item.assign_job);
            }
        });

        var toBranchSelect = document.getElementById('dashboard-filter-to-branch');
        var workBranchSelect = document.getElementById('dashboard-filter-work-branch');
        var jobSelect = document.getElementById('dashboard-filter-job');

        if (toBranchSelect) {
            toBranchSelect.innerHTML = '<option value="all">All</option>' + Array.from(toBranchSet).sort().map(function(branch) {
                return '<option value="' + branch + '">' + branch + '</option>';
            }).join('');
        }
        if (workBranchSelect) {
            workBranchSelect.innerHTML = '<option value="all">All</option>' + Array.from(workBranchSet).sort().map(function(branch) {
                return '<option value="' + branch + '">' + branch + '</option>';
            }).join('');
        }
        if (jobSelect) {
            jobSelect.innerHTML = '<option value="all">All</option>' + Array.from(jobSet).sort().map(function(job) {
                return '<option value="' + job + '">' + job + '</option>';
            }).join('');
        }
    }

    function applyDashboardFilters() {
        if (!dashboardAllRequests.length) {
            resetDashboardStats();
            return;
        }

        var toBranch = document.getElementById('dashboard-filter-to-branch').value;
        var workBranch = document.getElementById('dashboard-filter-work-branch').value;
        var job = document.getElementById('dashboard-filter-job').value;

        var filtered = dashboardAllRequests.filter(function(item) {
            if (toBranch && toBranch !== 'all') {
                if (item.type !== 'Internship' || item.to_branch !== toBranch) return false;
            }
            if (workBranch && workBranch !== 'all') {
                if (item.type === 'Internship') {
                    if (item.work_branch !== workBranch) return false;
                } else if (item.type === 'Qualification' || item.type === 'Recruitment') {
                    if (item.branch !== workBranch) return false;
                }
            }
            if (job && job !== 'all') {
                if (item.type === 'Internship') {
                    if (item.job !== job) return false;
                } else if (item.type === 'Qualification') {
                    if (item.job !== job) return false;
                } else if (item.type === 'Recruitment') {
                    if (item.req_job !== job && item.assign_job !== job) return false;
                }
            }
            return true;
        });

        document.getElementById('stat-internship').innerText = filtered.filter(r => r.type === 'Internship').length;
        document.getElementById('stat-qualification').innerText = filtered.filter(r => r.type === 'Qualification').length;
        document.getElementById('stat-recruitment').innerText = filtered.filter(r => r.type === 'Recruitment').length;
        document.getElementById('stat-total').innerText = filtered.length;
        renderDashboardReports(filtered);
    }

    function renderDashboardReports(filtered) {
        var dashboardPage = document.getElementById('page-Dashboard');
        if (!dashboardPage || dashboardPage.style.display === 'none') return; // only render when Dashboard is visible

        renderTopInternshipReport(filtered.filter(r => r.type === 'Internship'));
        renderTopQualificationReport(filtered.filter(r => r.type === 'Qualification'));
        renderTopRecruitmentReport(filtered.filter(r => r.type === 'Recruitment'));
        renderActivityTrends(filtered);
    }

    function renderTopInternshipReport(items) {
        var rows = aggregateCounts(items, function(item) {
            return (item.to_branch || 'Unknown') + '||' + (item.job || 'Unknown');
        }).sort(function(a, b) {
            return b.count - a.count;
        }).slice(0, 8);

        internshipChart = renderDashboardPieChart(rows, 'chart-internship', internshipChart, function(row) {
            var parts = row.key.split('||');
            return parts[0] + ' | ' + parts[1];
        });
    }

    function renderTopQualificationReport(items) {
        var rows = aggregateCounts(items, function(item) {
            return (item.branch || 'Unknown') + '||' + (item.job || 'Unknown');
        }).sort(function(a, b) {
            return b.count - a.count;
        }).slice(0, 8);

        qualificationChart = renderDashboardPieChart(rows, 'chart-qualification', qualificationChart, function(row) {
            var parts = row.key.split('||');
            return parts[0] + ' | ' + parts[1];
        });
    }

    function renderTopRecruitmentReport(items) {
        // Group recruitment data by branch and job
        var branchJobMap = {}; // { branch: { job: count } }
        var jobSet = new Set();

        items.forEach(function(item) {
            var branch = item.branch || 'Unknown';
            var job = item.assign_job || item.req_job || 'Unknown';
            
            if (!branchJobMap[branch]) {
                branchJobMap[branch] = {};
            }
            if (!branchJobMap[branch][job]) {
                branchJobMap[branch][job] = 0;
            }
            branchJobMap[branch][job]++;
            jobSet.add(job);
        });

        recruitmentChart = renderRecruitmentBarChart(branchJobMap, Array.from(jobSet), 'chart-recruitment', recruitmentChart);
    }

    function renderRecruitmentBarChart(branchJobMap, jobs, canvasId, existingChart) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        var branches = Object.keys(branchJobMap).sort();
        var colors = getChartColors(jobs.length);

        var datasets = jobs.map(function(job, idx) {
            return {
                label: job,
                data: branches.map(function(branch) {
                    return branchJobMap[branch][job] || 0;
                }),
                backgroundColor: colors[idx],
                borderColor: colors[idx],
                borderWidth: 1
            };
        });

        var config = {
            type: 'bar',
            data: {
                labels: branches,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        stacked: false,
                        title: { display: true, text: 'Branch' }
                    },
                    y: { 
                        stacked: false,
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Jobs' }
                    }
                }
            }
        };

        if (existingChart) {
            existingChart.data.labels = branches;
            existingChart.data.datasets = datasets;
            existingChart.update();
            return existingChart;
        }

        return new Chart(ctx, config);
    }

    function renderDashboardPieChart(rows, canvasId, existingChart, labelFormatter) {
        var labels = rows.map(function(row) {
            return typeof labelFormatter === 'function' ? labelFormatter(row) : row.key;
        });
        var data = rows.map(function(row) { return row.count; });
        var ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        var colors = getChartColors(rows.length);
        var chartConfig = {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed || 0;
                                var total = context.chart._metasets[context.datasetIndex].total || 0;
                                var percentage = total ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        };

        if (existingChart) {
            existingChart.data.labels = labels;
            existingChart.data.datasets[0].data = data;
            existingChart.data.datasets[0].backgroundColor = colors;
            existingChart.update();
            return existingChart;
        }

        return new Chart(ctx, chartConfig);
    }

        // Activity trend aggregation and rendering
        function renderActivityTrends(filtered) {
            var types = ['Internship','Qualification','Recruitment'];
            var typeCounts = { 'Internship': 0, 'Qualification': 0, 'Recruitment': 0 };

            filtered.forEach(function(item) {
                var t = item.type || 'Unknown';
                if (typeCounts.hasOwnProperty(t)) {
                    typeCounts[t]++;
                }
            });

            var labels = types;
            var data = types.map(function(type) {
                return typeCounts[type];
            });

            var colorPalette = ['#FF6384', '#36A2EB', '#FFCE56'];

            var monthlyDatasets = [
                {
                    label: 'Monthly Activity Trend',
                    type: 'line',
                    data: data,
                    borderColor: '#FF6384',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#FF6384',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }
            ];

            monthlyChart = renderMonthlyActivityChart(labels, monthlyDatasets, 'chart-monthly', monthlyChart, 'Monthly Activity');
        }

        function getYearWeek(d) {
            var date = new Date(d.getTime());
            date.setHours(0,0,0,0);
            date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
            var week1 = new Date(date.getFullYear(), 0, 4);
            var weekNo = 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
            return date.getFullYear() + '-W' + String(weekNo).padStart(2, '0');
        }

        function renderMonthlyActivityChart(labels, datasets, canvasId, existingChart, titleText) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            var config = {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'bottom'
                        },
                        title: { display: false, text: titleText } 
                    },
                    scales: { 
                        x: { 
                            display: true,
                            title: { display: true, text: '' }
                        }, 
                        y: { 
                            beginAtZero: true,
                            title: { display: true, text: 'Count' }
                        } 
                    }
                }
            };

            if (existingChart) {
                existingChart.data.labels = labels;
                existingChart.data.datasets = datasets;
                existingChart.options.plugins.legend = config.options.plugins.legend;
                existingChart.update();
                return existingChart;
            }

            return new Chart(ctx, config);
        }

    

    function getChartColors(count) {
        var palette = [
            '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336', '#00ACC1', '#FFB300', '#8E24AA', '#43A047', '#FB8C00'
        ];
        return Array.from({ length: count }, function(_, idx) {
            return palette[idx % palette.length];
        });
    }

    function aggregateCounts(items, keyFn) {
        var counts = {};
        items.forEach(function(item) {
            var key = keyFn(item) || 'Unknown';
            counts[key] = (counts[key] || 0) + 1;
        });
        return Object.keys(counts).map(function(key) {
            return { key: key, count: counts[key] };
        });
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function(chr) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[chr];
        });
    }

    // Settings
    async function loadSettings() {
        const data = await apiCall('api/get_settings.php');
        if (data) {
            settingsData = data;
            renderSettingsLists();
            populateDropdowns(data);
            if (data.logo_url) {
                currentLogoUrl = data.logo_url;
                document.getElementById('img-logo').src = currentLogoUrl;
                document.getElementById('input-logo-url').value = currentLogoUrl;
            }
            
            // Load bot token
            const tokenData = await apiCall('api/get_bot_token.php');
            if (tokenData && tokenData.token) {
                document.getElementById('input-bot-token').value = tokenData.token;
            }
        }
    }

    function renderSettingsLists() {
        // Branches
        var branchHtml = "";
        for (var i = 0; i < settingsData.branches.length; i++) {
            var branchName = settingsData.branches[i];
            var chatId = settingsData.branchChatIds[branchName] || "";
            branchHtml += "<div class='list-item' style='display:block; padding-bottom:10px;'>";
            branchHtml += "  <div style='display:flex; justify-content:space-between; margin-bottom:5px;'>";
            branchHtml += "    <span style='font-weight:bold;'>" + branchName + "</span>";
            branchHtml += "    <i class='fa fa-times delete-list-item' onclick='deleteItem(\"branch\", \"" + branchName + "\")'></i>";
            branchHtml += "  </div>";
            branchHtml += "  <input type='text' value='" + chatId + "' placeholder='Telegram Chat ID' style='width:100%; font-size:12px;' onchange='updateBranchChat(\"" + branchName + "\", this.value)' />";
            branchHtml += "</div>";
        }
        document.getElementById('list-branches').innerHTML = branchHtml;

        // Jobs
        var jobHtml = "";
        settingsData.jobs.forEach(function(item) {
            jobHtml += "<div class='list-item'><span>" + item + "</span> <i class='fa fa-times delete-list-item' onclick='deleteItem(\"job\", \"" + item + "\")'></i></div>";
        });
        document.getElementById('list-jobs').innerHTML = jobHtml;

        // Ranks
        var rankHtml = "";
        settingsData.ranks.forEach(function(item) {
            rankHtml += "<div class='list-item'><span>" + item + "</span> <i class='fa fa-times delete-list-item' onclick='deleteItem(\"rank\", \"" + item + "\")'></i></div>";
        });
        document.getElementById('list-ranks').innerHTML = rankHtml;

        // Destinations
        var destHtml = "";
        for (var i = 0; i < settingsData.destinations.length; i++) {
            var destName = settingsData.destinations[i];
            var chatId = settingsData.destChatIds[destName] || "";
            destHtml += "<div class='list-item' style='display:block; padding-bottom:10px;'>";
            destHtml += "  <div style='display:flex; justify-content:space-between; margin-bottom:5px;'>";
            destHtml += "    <span style='font-weight:bold;'>" + destName + "</span>";
            destHtml += "    <i class='fa fa-times delete-list-item' onclick='deleteItem(\"destination\", \"" + destName + "\")'></i>";
            destHtml += "  </div>";
            destHtml += "  <input type='text' value='" + chatId + "' placeholder='Telegram Chat ID' style='width:100%; font-size:12px;' onchange='updateDestinationChat(\"" + destName + "\", this.value)' />";
            destHtml += "</div>";
        }
        document.getElementById('list-destinations').innerHTML = destHtml;
    }

    function populateDropdowns(data) {
        var branches = data.branches || [];
        var jobs = data.jobs || [];
        var ranks = data.ranks || [];
        var destinations = data.destinations || [];

        var branchSelects = document.querySelectorAll('#i_to_branch, #i_work_branch, #q_branch, #r_branch');
        var jobSelects = document.querySelectorAll('#i_job, #q_job, #r_req_job, #r_job');
        var rankSelects = document.querySelectorAll('#q_manager_rank');
        var destSelects = document.querySelectorAll('#q_destination, #r_destination');

        branchSelects.forEach(s => populateSelect(s, branches));
        jobSelects.forEach(s => populateSelect(s, jobs));
        rankSelects.forEach(s => populateSelect(s, ranks));
        destSelects.forEach(s => populateSelect(s, destinations));
    }

    async function addBranch() {
        var name = document.getElementById('input-branch').value;
        if (!name) return alert("Please enter a Branch Name.");
        const result = await apiCall('api/save_settings.php', { action: 'add', type: 'branch', value: name });
        if (result && result.success) {
            showSuccess(result.message);
            document.getElementById('input-branch').value = "";
            loadSettings();
        }
    }

    async function addItem(listType) {
        var inputId = listType === 'jobs' ? "input-job" : (listType === 'ranks' ? "input-rank" : "input-destination");
        var value = document.getElementById(inputId).value;
        if (!value) return alert("Please enter a value.");
        const result = await apiCall('api/save_settings.php', { action: 'add', type: listType.slice(0, -1), value: value });
        if (result && result.success) {
            showSuccess(result.message);
            document.getElementById(inputId).value = "";
            loadSettings();
        }
    }

    async function deleteItem(type, value) {
        if (!confirm("Delete " + value + "?")) return;
        const result = await apiCall('api/save_settings.php', { action: 'delete', type: type, value: value });
        if (result && result.success) {
            showSuccess(result.message);
            loadSettings();
        }
    }

    async function updateBranchChat(branchName, chatId) {
        await apiCall('api/save_settings.php', { action: 'update_chat', type: 'branch', value: branchName, chat_id: chatId });
    }

    async function updateDestinationChat(destName, chatId) {
        await apiCall('api/save_settings.php', { action: 'update_chat', type: 'destination', value: destName, chat_id: chatId });
    }

    async function saveLogoSettings() {
        var url = document.getElementById('input-logo-url').value;
        if (!url) return alert("Please enter a URL");
        const result = await apiCall('api/save_settings.php', { action: 'update_logo', type: 'logo', value: url });
        if (result && result.success) {
            showSuccess(result.message);
            currentLogoUrl = url;
            document.getElementById('img-logo').src = currentLogoUrl;
        }
    }

    async function saveBotToken() {
        var token = document.getElementById('input-bot-token').value;
        if (!token) return alert("Please enter a Bot Token.");
        const result = await apiCall('api/save_bot_token.php', { token: token });
        if (result && result.success) {
            showSuccess(result.message);
        }
    }

    async function testTelegram() {
        const result = await apiCall('api/test_telegram.php');
        if (result) {
            if (result.error) {
                alert(result.error);
            } else {
                alert(result.message);
            }
        }
    }

    // User Management Functions
    async function loadUsers() {
        const result = await apiCall('api/manage_users.php', { action: 'get_users' });
        if (result && result.success) {
            renderUsersList(result.users);
        }
    }

    function renderUsersList(users) {
        const container = document.getElementById('users-list-container');
        if (!users || users.length === 0) {
            container.innerHTML = "<div style='padding:15px; text-align:center; color:#888;'>No users found.</div>";
            return;
        }

        let html = "<table style='width:100%; border-collapse: collapse;'>";
        html += "<thead style='background: #f5f5f5;'>";
        html += "<tr style='border-bottom: 2px solid #ddd;'>";
        html += "<th style='padding: 10px; text-align: left; font-weight: bold;'>Username</th>";
        html += "<th style='padding: 10px; text-align: left; font-weight: bold;'>Role</th>";
        html += "<th style='padding: 10px; text-align: left; font-weight: bold;'>Created Date</th>";
        html += "<th style='padding: 10px; text-align: center; font-weight: bold;'>Actions</th>";
        html += "</tr></thead><tbody>";

        users.forEach(function(user) {
            const createdDate = new Date(user.created_at).toLocaleDateString();
            html += "<tr style='border-bottom: 1px solid #eee;'>";
            html += "<td style='padding: 10px;'><strong>" + escapeHtml(user.username) + "</strong></td>";
            html += "<td style='padding: 10px;'><span style='background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>" + escapeHtml(user.role) + "</span></td>";
            html += "<td style='padding: 10px;'>" + createdDate + "</td>";
            html += "<td style='padding: 10px; text-align: center;'>";
            html += "<button onclick='openChangePasswordModal(" + user.id + ", \"" + escapeHtml(user.username) + "\")' style='background: #0288d1; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px;'>Change Password</button>";
            if (users.length > 1) {
                html += "<button onclick='deleteUserConfirm(" + user.id + ", \"" + escapeHtml(user.username) + "\")' style='background: #d32f2f; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;'>Delete</button>";
            }
            html += "</td></tr>";
        });

        html += "</tbody></table>";
        container.innerHTML = html;
    }

    async function addNewUser() {
        const username = document.getElementById('input-new-username').value.trim();
        const password = document.getElementById('input-new-password').value;
        const role = document.getElementById('input-new-role').value;

        if (!username || !password) {
            alert('Please enter username and password.');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters long.');
            return;
        }

        const result = await apiCall('api/manage_users.php', {
            action: 'add_user',
            username: username,
            password: password,
            role: role
        });

        if (result && result.success) {
            showSuccess('User added successfully!');
            document.getElementById('input-new-username').value = '';
            document.getElementById('input-new-password').value = '';
            document.getElementById('input-new-role').value = 'User';
            loadUsers();
        } else if (result) {
            alert(result.error || 'Failed to add user');
        }
    }

    function openChangePasswordModal(userId, username) {
        const newPassword = prompt('Enter new password for user "' + username + '" (minimum 6 characters):');
        if (newPassword === null) return; // User cancelled

        if (!newPassword || newPassword.length < 6) {
            alert('Password must be at least 6 characters long.');
            return;
        }

        changeUserPassword(userId, newPassword);
    }

    async function changeUserPassword(userId, newPassword) {
        const result = await apiCall('api/manage_users.php', {
            action: 'change_password',
            user_id: userId,
            new_password: newPassword
        });

        if (result && result.success) {
            showSuccess('Password changed successfully!');
            loadUsers();
        } else if (result) {
            alert(result.error || 'Failed to change password');
        }
    }

    function deleteUserConfirm(userId, username) {
        if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
            deleteUser(userId);
        }
    }

    async function deleteUser(userId) {
        const result = await apiCall('api/manage_users.php', {
            action: 'delete_user',
            user_id: userId
        });

        if (result && result.success) {
            showSuccess('User deleted successfully!');
            loadUsers();
        } else if (result) {
            alert(result.error || 'Failed to delete user');
        }
    }

    // Form Submissions
    async function saveInternship(data, existingId = null) {
        if (existingId) data.existing_id = existingId;
        const result = await apiCall('api/save_internship.php', data);
        if (result && result.success) {
            var title = existingId ? "Success" : "Success";
            var message = existingId ? "Request Updated successfully." : "Request saved successfully.";
            showSuccessPopup(title, message + "\nID: " + result.id);
            if (result.pdf_url) {
                loadViewLists();
            }
            return true;
        }
        return false;
    }

    async function saveQualification(data, existingId = null) {
        if (existingId) data.existing_id = existingId;
        const result = await apiCall('api/save_qualification.php', data);
        if (result && result.success) {
            var title = existingId ? "Success" : "Success";
            var message = existingId ? "Request Updated successfully." : "Request saved successfully.";
            showSuccessPopup(title, message + "\nID: " + result.id);
            if (result.pdf_url) {
                loadViewLists();
            }
            return true;
        }
        return false;
    }

    async function saveRecruitment(data, existingId = null) {
        if (existingId) data.existing_id = existingId;
        const result = await apiCall('api/save_recruitment.php', data);
        if (result && result.success) {
            var title = existingId ? "Success" : "Success";
            var message = existingId ? "Request Updated successfully." : "Request saved successfully.";
            showSuccessPopup(title, message + "\nID: " + result.id);
            if (result.pdf_url) {
                loadViewLists();
            }
            return true;
        }
        return false;
    }

    // Reference Lists
    async function loadRefLists() {
        const internships = await apiCall('api/get_data.php?type=internships_for_form');
        if (internships) renderInternshipRefList(internships);
        
        const qualifications = await apiCall('api/get_data.php?type=qualifications_for_form');
        if (qualifications) renderQualificationRefList(qualifications);
    }

    function renderInternshipRefList(data) {
        internshipRefData = data || [];
        filterInternshipRefList();
    }

    function filterInternshipRefList() {
        var filtered = internshipRefData.filter(function(item) {
            return matchesFilter(item.name, document.getElementById('filter-intern-name').value)
                && matchesFilter(item.to_branch, document.getElementById('filter-intern-to-branch').value)
                && matchesFilter(item.branch, document.getElementById('filter-intern-work-branch').value)
                && matchesFilter(item.job, document.getElementById('filter-intern-job').value)
                && matchesDateRange(item.start, document.getElementById('filter-intern-start-date-from').value, document.getElementById('filter-intern-start-date-to').value);
        });
        var container = document.getElementById('internship-list-container');
        if (!filtered || filtered.length === 0) {
            container.innerHTML = "<div style='padding:15px; text-align:center; color:#888;'>No matching internships.</div>";
            return;
        }
        var html = "<table class='ref-table'><thead><tr><th>ID</th><th>Name</th><th>To Branch</th><th>Work Branch</th><th>Job</th><th>Start Date</th><th>Action</th></tr></thead><tbody>";
        filtered.forEach(function(item) {
            var itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
            html += "<tr><td>" + item.id + "</td><td>" + item.name + "</td><td>" + item.to_branch + "</td><td>" + item.branch + "</td><td>" + item.job + "</td><td>" + item.start + "</td><td><button class='select-btn' onclick='selectIntern(" + itemJson + ")'>Select</button></td></tr>";
        });
        html += "</tbody></table>";
        container.innerHTML = html;
    }

    function renderQualificationRefList(data) {
        qualificationRefData = data || [];
        filterQualificationRefList();
    }

    function filterQualificationRefList() {
        var filtered = qualificationRefData.filter(function(item) {
            return matchesFilter(item.name, document.getElementById('filter-qual-name').value)
                && matchesFilter(item.branch, document.getElementById('filter-qual-branch').value)
                && matchesFilter(item.job, document.getElementById('filter-qual-job').value)
                && matchesDateRange(item.probation_start, document.getElementById('filter-qual-probation-from').value, document.getElementById('filter-qual-probation-to').value);
        });
        var container = document.getElementById('qualification-list-container');
        if (!filtered || filtered.length === 0) {
            container.innerHTML = "<div style='padding:15px; text-align:center; color:#888;'>No matching qualifications.</div>";
            return;
        }
        var html = "<table class='ref-table'><thead><tr><th>ID</th><th>Name</th><th>Branch</th><th>Job</th><th>Probation Start</th><th>Action</th></tr></thead><tbody>";
        filtered.forEach(function(item) {
            var itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
            html += "<tr><td>" + item.id + "</td><td>" + item.name + "</td><td>" + item.branch + "</td><td>" + item.job + "</td><td>" + item.probation_start + "</td><td><button class='select-btn' onclick='selectQual(" + itemJson + ")'>Select</button></td></tr>";
        });
        html += "</tbody></table>";
        container.innerHTML = html;
    }

    function selectIntern(item) {
        document.getElementById('q_name').value = item.name;
        document.getElementById('q_gender').value = item.gender;
        document.getElementById('q_branch').value = item.to_branch;
        document.getElementById('q_job').value = item.job;
        if (item.start) document.getElementById('q_intern_start').value = item.start;
        document.getElementById('ref_intern_id').value = item.id;
        
        var wrapper = document.getElementById('qualification-form-wrapper');
        wrapper.style.display = 'block';
        wrapper.classList.remove('form-active');
        void wrapper.offsetWidth;
        wrapper.classList.add('form-active');
        document.getElementById('qual-list-wrapper').style.display = 'none';
    }

    function selectQual(item) {
        document.getElementById('r_name').value = item.name;
        document.getElementById('r_gender').value = item.gender;
        document.getElementById('r_branch').value = item.branch;
        document.getElementById('r_req_job').value = item.job;
        document.getElementById('r_job').value = item.job;
        document.getElementById('ref_qual_id').value = item.id;
        
        var wrapper = document.getElementById('recruitment-form-wrapper');
        wrapper.style.display = 'block';
        wrapper.classList.remove('form-active');
        void wrapper.offsetWidth;
        wrapper.classList.add('form-active');
        document.getElementById('rec-list-wrapper').style.display = 'none';
    }

    function backToQualificationList() {
        var wrapper = document.getElementById('qualification-form-wrapper');
        wrapper.style.display = 'none';
        wrapper.classList.remove('form-active');
        document.getElementById('qual-list-wrapper').style.display = 'block';
        document.getElementById('formQualification').reset();
        document.getElementById('ref_intern_id').value = "";
        loadRefLists();
    }

    function backToRecruitmentList() {
        var wrapper = document.getElementById('recruitment-form-wrapper');
        wrapper.style.display = 'none';
        wrapper.classList.remove('form-active');
        document.getElementById('rec-list-wrapper').style.display = 'block';
        document.getElementById('formRecruitment').reset();
        document.getElementById('ref_qual_id').value = "";
        loadRefLists();
    }

    // View Lists
    async function loadViewLists() {
        const data = await apiCall('api/get_data.php?type=all_requests');
        if (data) renderViewLists(data);
    }

    function renderViewLists(data) {
        viewListData = data || [];
        renderInternshipList();
        renderQualificationList();
        renderRecruitmentList();
    }

    function renderInternshipList() {
        var filtered = viewListData.filter(function(r) {
            return r.type === 'Internship'
                && matchesFilter(r.name, document.getElementById('filter-list-intern-name').value)
                && matchesFilter(r.to_branch, document.getElementById('filter-list-intern-to-branch').value)
                && matchesFilter(r.work_branch, document.getElementById('filter-list-intern-work-branch').value)
                && matchesFilter(r.job, document.getElementById('filter-list-intern-job').value)
                && matchesDateRange(r.start_date, document.getElementById('filter-list-intern-start-date-from').value, document.getElementById('filter-list-intern-start-date-to').value);
        });
        var html = "<table><thead><tr><th>ID</th><th>Name</th><th>To Branch</th><th>Work Branch</th><th>Job</th><th>Start Date</th><th>Actions</th></tr></thead><tbody>";
        if (!filtered.length) {
            html += "<tr><td colspan='7' style='padding:15px; text-align:center; color:#888;'>No matching internship records.</td></tr>";
        } else {
            filtered.forEach(function(r) {
                html += buildListRow(r, CURRENT_ROLE);
            });
        }
        html += "</tbody></table>";
        document.getElementById('table-Internship').innerHTML = html;
    }

    function renderQualificationList() {
        var filtered = viewListData.filter(function(r) {
            return r.type === 'Qualification'
                && matchesFilter(r.name, document.getElementById('filter-list-qual-name').value)
                && matchesFilter(r.branch, document.getElementById('filter-list-qual-branch').value)
                && matchesFilter(r.job, document.getElementById('filter-list-qual-job').value)
                && matchesDateRange(r.start_date, document.getElementById('filter-list-qual-probation-from').value, document.getElementById('filter-list-qual-probation-to').value);
        });
        var html = "<table><thead><tr><th>ID</th><th>Name</th><th>Branch</th><th>Job</th><th>Probation Start</th><th>Actions</th></tr></thead><tbody>";
        if (!filtered.length) {
            html += "<tr><td colspan='6' style='padding:15px; text-align:center; color:#888;'>No matching qualification records.</td></tr>";
        } else {
            filtered.forEach(function(r) {
                html += buildListRow(r, CURRENT_ROLE);
            });
        }
        html += "</tbody></table>";
        document.getElementById('table-Qualification').innerHTML = html;
    }

    function renderRecruitmentList() {
        var filtered = viewListData.filter(function(r) {
            return r.type === 'Recruitment'
                && matchesFilter(r.name, document.getElementById('filter-list-rec-name').value)
                && matchesFilter(r.branch, document.getElementById('filter-list-rec-branch').value)
                && matchesFilter(r.req_job, document.getElementById('filter-list-rec-requested-job').value)
                && matchesFilter(r.assign_job, document.getElementById('filter-list-rec-assigned-job').value)
                && matchesDateRange(r.start_date, document.getElementById('filter-list-rec-start-date-from').value, document.getElementById('filter-list-rec-start-date-to').value);
        });
        var html = "<table><thead><tr><th>ID</th><th>Name</th><th>Branch</th><th>Requested Job</th><th>Assigned Job</th><th>Start Date</th><th>Actions</th></tr></thead><tbody>";
        if (!filtered.length) {
            html += "<tr><td colspan='7' style='padding:15px; text-align:center; color:#888;'>No matching recruitment records.</td></tr>";
        } else {
            filtered.forEach(function(r) {
                html += buildListRow(r, CURRENT_ROLE);
            });
        }
        html += "</tbody></table>";
        document.getElementById('table-Recruitment').innerHTML = html;
    }

    function buildListRow(r, role) {
        if (!role) role = window.CURRENT_ROLE || 'User';
        
        var actionLinks = "";
        // All roles can view PDF
        if (r.pdf && r.pdf !== 'Processing...') {
            actionLinks += "<a href='" + r.pdf + "' target='_blank' class='link-btn' style='border-color:#007bff; color:#007bff; margin-right:4px;'>PDF</a> ";
        } else {
            actionLinks += "<span style='color:#999; font-size:11px; margin-right:8px;'>Processing...</span> ";
        }
        
        // Determine reject button color based on status
        var rejectButtonColor = (r.status === 'Rejected') ? '#28a745' : '#fd7e14';
        
        // Role-based action visibility
        if (r.type === 'Internship') {
            // User: PDF + Reject; Admin/Superadmin: PDF + Edit + Delete + Reject
            actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:" + rejectButtonColor + "; color:" + rejectButtonColor + "; margin-right:4px;' onclick='rejectRequest(\"" + r.type + "\", \"" + r.id + "\")'>Reject</a> ";
            if (role !== 'User') {
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#ffc107; color:#ffc107; margin-right:4px;' onclick='editRequest(\"" + r.type + "\", \"" + r.id + "\")'>Edit</a> ";
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#dc3545; color:#dc3545;' onclick='removeRequest(\"" + r.type + "\", \"" + r.id + "\")'>Delete</a>";
            }
            return "<tr><td>" + r.id + "</td><td>" + r.name + "</td><td>" + r.to_branch + "</td><td>" + r.work_branch + "</td><td>" + r.job + "</td><td>" + r.start_date + "</td><td>" + actionLinks + "</td></tr>";
        } else if (r.type === 'Qualification') {
            // User: PDF + Edit; Admin/Superadmin: PDF + Edit + Delete + Reject
            actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#ffc107; color:#ffc107; margin-right:4px;' onclick='editRequest(\"" + r.type + "\", \"" + r.id + "\")'>Edit</a> ";
            if (role !== 'User') {
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:" + rejectButtonColor + "; color:" + rejectButtonColor + "; margin-right:4px;' onclick='rejectRequest(\"" + r.type + "\", \"" + r.id + "\")'>Reject</a> ";
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#dc3545; color:#dc3545;' onclick='removeRequest(\"" + r.type + "\", \"" + r.id + "\")'>Delete</a>";
            }
            return "<tr><td>" + r.id + "</td><td>" + r.name + "</td><td>" + r.branch + "</td><td>" + r.job + "</td><td>" + r.start_date + "</td><td>" + actionLinks + "</td></tr>";
        } else if (r.type === 'Recruitment') {
            // User: PDF only; Admin/Superadmin: PDF + Edit + Delete + Reject
            if (role !== 'User') {
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#ffc107; color:#ffc107; margin-right:4px;' onclick='editRequest(\"" + r.type + "\", \"" + r.id + "\")'>Edit</a> ";
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:" + rejectButtonColor + "; color:" + rejectButtonColor + "; margin-right:4px;' onclick='rejectRequest(\"" + r.type + "\", \"" + r.id + "\")'>Reject</a> ";
                actionLinks += "<a href='javascript:void(0)' class='link-btn' style='border-color:#dc3545; color:#dc3545;' onclick='removeRequest(\"" + r.type + "\", \"" + r.id + "\")'>Delete</a>";
            }
            return "<tr><td>" + r.id + "</td><td>" + r.name + "</td><td>" + r.branch + "</td><td>" + (r.req_job || '') + "</td><td>" + (r.assign_job || '') + "</td><td>" + r.start_date + "</td><td>" + actionLinks + "</td></tr>";
        }
        return "";
    }

    async function editRequest(type, id) {
        const data = await apiCall(`api/get_data.php?type=edit_data&id=${id}&typeParam=${type}`);
        if (data && !data.error) {
            currentEditId = data.id;
            if (type === 'Internship') {
                openPage('Form-Internship');
                document.getElementById('i_name').value = data.name;
                document.getElementById('i_gender').value = data.gender;
                document.getElementById('i_to_branch').value = data.to_branch;
                document.getElementById('i_work_branch').value = data.work_branch;
                document.getElementById('i_job').value = data.job;
                document.getElementById('i_start_date').value = data.start_date;
                document.getElementById('btn-submit').innerText = "Update Request (" + data.id + ")";
            } else if (type === 'Qualification') {
                openPage('Form-Qualification');
                var wrapper = document.getElementById('qualification-form-wrapper');
                wrapper.style.display = 'block';
                wrapper.classList.add('form-active');
                document.getElementById('q_name').value = data.name;
                document.getElementById('q_gender').value = data.gender;
                document.getElementById('q_branch').value = data.branch;
                document.getElementById('q_job').value = data.job;
                document.getElementById('q_probation_start').value = data.probation_start;
                document.getElementById('btn-submit-q').innerText = "Update Request (" + data.id + ")";
                document.getElementById('qual-list-wrapper').style.display = 'none';
            } else if (type === 'Recruitment') {
                openPage('Form-Recruitment');
                var wrapper = document.getElementById('recruitment-form-wrapper');
                wrapper.style.display = 'block';
                wrapper.classList.add('form-active');
                document.getElementById('r_name').value = data.name;
                document.getElementById('r_gender').value = data.gender;
                document.getElementById('r_branch').value = data.branch;
                document.getElementById('r_req_job').value = data.req_job;
                document.getElementById('r_job').value = data.job;
                document.getElementById('r_start_date').value = data.start_date;
                document.getElementById('btn-submit-r').innerText = "Update Request (" + data.id + ")";
                document.getElementById('rec-list-wrapper').style.display = 'none';
            }
        }
    }

    async function removeRequest(type, id) {
        if (!confirm("Are you sure you want to delete request " + id + "?")) return;
        const result = await apiCall(`api/get_data.php?type=delete&id=${id}&typeParam=${type}`, { id: id, type: type });
        if (result && result.success) {
            showSuccess(result.message);
            loadViewLists();
            loadDashboard();
        }
    }

    async function rejectRequest(type, id) {
        if (!confirm("Are you sure you want to reject request " + id + "?")) return;
        const result = await apiCall(`api/get_data.php?type=reject&id=${id}&typeParam=${type}`, { id: id, type: type });
        if (result && result.success) {
            showSuccess(result.message);
            loadViewLists();
            loadDashboard();
        } else {
            showError(result?.message || "Failed to reject request");
        }
    }

    async function nextRequest(type, id) {
        if (!confirm("Are you sure you want to move request " + id + " to the next status?")) return;
        const result = await apiCall(`api/get_data.php?type=next&id=${id}&typeParam=${type}`, { id: id, type: type });
        if (result && result.success) {
            showSuccess(result.message);
            loadViewLists();
            loadDashboard();
        } else {
            showError(result?.message || "Failed to update request status");
        }
    }

    function clearDateFilters(pageName) {
        if (!pageName) return;
        if (pageName === 'List-Internship') {
            document.getElementById('filter-list-intern-start-date-from').value = '';
            document.getElementById('filter-list-intern-start-date-to').value = '';
        }
        if (pageName === 'List-Qualification') {
            document.getElementById('filter-list-qual-probation-from').value = '';
            document.getElementById('filter-list-qual-probation-to').value = '';
        }
        if (pageName === 'List-Recruitment') {
            document.getElementById('filter-list-rec-start-date-from').value = '';
            document.getElementById('filter-list-rec-start-date-to').value = '';
        }
        if (pageName === 'Form-Qualification') {
            document.getElementById('filter-intern-start-date-from').value = '';
            document.getElementById('filter-intern-start-date-to').value = '';
        }
        if (pageName === 'Form-Recruitment') {
            document.getElementById('filter-qual-probation-from').value = '';
            document.getElementById('filter-qual-probation-to').value = '';
        }
    }

    // Toggle Sidebar Minimize
    function toggleSidebarMinimize() {
        var sidebar = document.getElementById('mySidebar');
        var main = document.getElementById('main');
        
        sidebar.classList.toggle('minimized');
        main.classList.toggle('sidebar-minimized');
        
        // Save state to localStorage
        var isMinimized = sidebar.classList.contains('minimized');
        localStorage.setItem('sidebarMinimized', isMinimized);
    }

    // Restore sidebar state on page load
    function restoreSidebarState() {
        var isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
        if (isMinimized) {
            var sidebar = document.getElementById('mySidebar');
            var main = document.getElementById('main');
            sidebar.classList.add('minimized');
            main.classList.add('sidebar-minimized');
        }
    }

    // Close all dropdown menus
    function closeAllDropdowns() {
        var dropdowns = document.getElementsByClassName('dropdown-container');
        for (var i = 0; i < dropdowns.length; i++) {
            dropdowns[i].classList.remove('show');
            if (dropdowns[i].previousElementSibling) {
                dropdowns[i].previousElementSibling.classList.remove('active');
            }
        }
    }

    // Page Navigation
    function openPage(pageName) {
        // Close all dropdowns first
        
        
        destroyDashboardCharts();
        var pages = document.getElementsByClassName("content-page");
        for (var i = 0; i < pages.length; i++) pages[i].style.display = "none";
        
        var activePage = document.getElementById("page-" + pageName);
        if (activePage) activePage.style.display = "block";

        // Sidebar active states
        var allNavItems = document.querySelectorAll('.sidebar a, .dropdown-btn');
        allNavItems.forEach(item => item.classList.remove('active'));

        var activeLinkId = "";
        var activeDropdownId = "";

        if (pageName === 'Dashboard') {
            activeLinkId = "nav-Dashboard";
        } else if (pageName === 'Settings') {
            activeLinkId = "txt-settings";
        } else if (pageName === 'Form-Internship' || pageName === 'Form-Qualification' || pageName === 'Form-Recruitment') {
            activeDropdownId = "btn-hr-system";
            if (pageName === 'Form-Internship') activeLinkId = "txt-nav-internship";
            if (pageName === 'Form-Qualification') activeLinkId = "txt-nav-qual";
            if (pageName === 'Form-Recruitment') activeLinkId = "txt-nav-rec";
        } else if (pageName.startsWith('List-')) {
            activeDropdownId = "btn-view-list";
            if (pageName === 'List-Internship') activeLinkId = "txt-nav-list-internship";
            if (pageName === 'List-Qualification') activeLinkId = "txt-nav-list-qual";
            if (pageName === 'List-Recruitment') activeLinkId = "txt-nav-list-rec";
        }

        if (pageName !== currentPage) {
            clearDateFilters(currentPage);
        }

        // Only add active class to the link, NOT to the dropdown button
        // This keeps dropdown menus closed
        if (activeLinkId) {
            var link = document.getElementById(activeLinkId);
            if (link) link.classList.add('active');
        }



        // Set page title
        var titleKey = "txt-" + pageName.toLowerCase().replace("-", "-");
        if (pageName === "Form-Internship") titleKey = "txt-form-internship";
        if (pageName === "Form-Qualification") titleKey = "txt-form-qual";
        if (pageName === "Form-Recruitment") titleKey = "txt-form-rec";
        if (pageName === "List-Internship") titleKey = "txt-list-internship";
        if (pageName === "List-Qualification") titleKey = "txt-list-qual";
        if (pageName === "List-Recruitment") titleKey = "txt-list-rec";
        
        document.getElementById('pageTitle').innerText = translations[currentLang][titleKey] || pageName;
        
        // Load data based on page
        if (pageName === 'Dashboard') {
            loadDashboard();
        } else if (pageName === 'Settings') {
            loadSettings();
            loadUsers();
        } else if (pageName.startsWith('List-')) {
            loadViewLists();
        } else if (pageName === 'Form-Qualification' || pageName === 'Form-Recruitment') {
            if (pageName === 'Form-Qualification') {
                document.getElementById('qualification-form-wrapper').style.display = 'none';
                document.getElementById('qual-list-wrapper').style.display = 'block';
                document.getElementById('internship-list-container').innerHTML = '<div style="padding:20px; text-align:center;"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
            }
            if (pageName === 'Form-Recruitment') {
                document.getElementById('recruitment-form-wrapper').style.display = 'none';
                document.getElementById('rec-list-wrapper').style.display = 'block';
                document.getElementById('qualification-list-container').innerHTML = '<div style="padding:20px; text-align:center;"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
            }
            loadRefLists();
        }

        currentPage = pageName;
        if (window.innerWidth <= 768) {
            document.getElementById("mySidebar").style.width = "0";
            var mainEl = document.getElementById('main');
            if (mainEl) mainEl.style.marginLeft = '0';
        }
    }

    function destroyDashboardCharts() {
        try {
            if (internshipChart) { internshipChart.destroy(); internshipChart = null; }
            if (qualificationChart) { qualificationChart.destroy(); qualificationChart = null; }
            if (recruitmentChart) { recruitmentChart.destroy(); recruitmentChart = null; }
        } catch (e) {
            console.warn('Error destroying charts', e);
        }
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Restore sidebar state
        restoreSidebarState();
        
        // Dropdown buttons
        var dropdown = document.getElementsByClassName("dropdown-btn");
        for (var i = 0; i < dropdown.length; i++) {
            dropdown[i].addEventListener("click", function() {
                // Close all other dropdowns first
                var allDropdowns = document.getElementsByClassName('dropdown-container');
                for (var j = 0; j < allDropdowns.length; j++) {
                    if (allDropdowns[j] !== this.nextElementSibling) {
                        allDropdowns[j].classList.remove('show');
                        if (allDropdowns[j].previousElementSibling) {
                            allDropdowns[j].previousElementSibling.classList.remove('active');
                        }
                    }
                }
                
                // Toggle current dropdown
                this.classList.toggle("active");
                var dropdownContent = this.nextElementSibling;
                if (dropdownContent) {
                    dropdownContent.classList.toggle("show");
                    
                    // Position dropdown correctly when sidebar is minimized
                    if (document.getElementById('mySidebar').classList.contains('minimized') && dropdownContent.classList.contains('show')) {
                        var btnRect = this.getBoundingClientRect();
                        dropdownContent.style.top = btnRect.top + 'px';
                    }
                }
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown-btn') && !event.target.closest('.dropdown-container')) {
               
            }
        });

        // Internship Form Submit
        document.getElementById('formInternship').addEventListener('submit', async function(e) {
            e.preventDefault();
            var data = {
                date: document.getElementById('i_date').value,
                to_branch: document.getElementById('i_to_branch').value,
                name: document.getElementById('i_name').value,
                gender: document.getElementById('i_gender').value,
                work_branch: document.getElementById('i_work_branch').value,
                job: document.getElementById('i_job').value,
                start_date: document.getElementById('i_start_date').value
            };
            
            if (currentEditId) {
                await saveInternship(data, currentEditId);
                currentEditId = null;
                document.getElementById('btn-submit').innerText = "Submit Request";
            } else {
                await saveInternship(data);
            }
            document.getElementById('formInternship').reset();
        });

        // Qualification Form Submit
        document.getElementById('formQualification').addEventListener('submit', async function(e) {
            e.preventDefault();
            var data = {
                q_date: document.getElementById('q_date').value,
                q_branch: document.getElementById('q_branch').value,
                q_name: document.getElementById('q_name').value,
                q_gender: document.getElementById('q_gender').value,
                q_job: document.getElementById('q_job').value,
                q_intern_start: document.getElementById('q_intern_start').value,
                q_probation_start: document.getElementById('q_probation_start').value,
                q_manager_name: document.getElementById('q_manager_name').value,
                q_manager_rank: document.getElementById('q_manager_rank').value,
                q_destination: document.getElementById('q_destination').value,
                ref_intern_id: document.getElementById('ref_intern_id').value
            };
            
            if (currentEditId) {
                await saveQualification(data, currentEditId);
                currentEditId = null;
                document.getElementById('btn-submit-q').innerText = "Submit Request";
            } else {
                await saveQualification(data);
            }
            backToQualificationList();
        });

        // Recruitment Form Submit
        document.getElementById('formRecruitment').addEventListener('submit', async function(e) {
            e.preventDefault();
            var data = {
                r_date: document.getElementById('r_date').value,
                r_branch: document.getElementById('r_branch').value,
                r_name: document.getElementById('r_name').value,
                r_gender: document.getElementById('r_gender').value,
                r_req_job: document.getElementById('r_req_job').value,
                r_job: document.getElementById('r_job').value,
                r_start_date: document.getElementById('r_start_date').value,
                r_destination: document.getElementById('r_destination').value,
                ref_qual_id: document.getElementById('ref_qual_id').value
            };
            
            if (currentEditId) {
                await saveRecruitment(data, currentEditId);
                currentEditId = null;
                document.getElementById('btn-submit-r').innerText = "Submit Request";
            } else {
                await saveRecruitment(data);
            }
            backToRecruitmentList();
            loadDashboard();
        });

        // Load initial data
        loadSettings();
        loadUsers();
        loadViewLists();
        loadRefLists();
        
        // Open default page
        openPage('Form-Internship');
    });
</script>

</body>
</html>