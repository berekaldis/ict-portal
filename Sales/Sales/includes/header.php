<?php
// includes/header.php - Updated with logo display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaldis Coffee - <?= $page_title ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <?php 
                $logoUrl = getSetting('logo_url', '');
                $logoFile = getSetting('logo_file', '');
                $logo = !empty($logoFile) ? $logoFile : $logoUrl;
                $logoSize = getSetting('logo_size', 'medium');
                ?>
                <?php if ($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" 
                         alt="Logo" 
                         class="logo-navbar <?= $logoSize ?>"
                         style="height: <?= $logoSize === 'small' ? '30px' : ($logoSize === 'medium' ? '40px' : '50px') ?>; 
                                object-fit: contain;
                                margin-right: 8px;">
                <?php else: ?>
                    <i class="fas fa-coffee"></i>
                <?php endif; ?>
                <span>Kaldis Coffee</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
                <div class="sidebar-user-role">
                    <span class="badge bg-<?= ($_SESSION['role'] ?? '') === 'Admin' ? 'danger' : (($_SESSION['role'] ?? '') === 'Support Team' ? 'info' : 'primary') ?>">
                        <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
                    </span>
                </div>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <!-- Dashboard - All roles -->
            <li class="sidebar-menu-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Cases - All roles -->
            <li class="sidebar-menu-item <?= basename($_SERVER['PHP_SELF']) == 'cases.php' || basename($_SERVER['PHP_SELF']) == 'add_case.php' || basename($_SERVER['PHP_SELF']) == 'edit_case.php' ? 'active' : '' ?>">
                <a href="cases.php">
                    <i class="fas fa-tasks"></i>
                    <span>Cases</span>
                </a>
            </li>
            
            <!-- Completed Cases - All roles -->
            <li class="sidebar-menu-item <?= basename($_SERVER['PHP_SELF']) == 'completed_cases.php' ? 'active' : '' ?>">
                <a href="completed_cases.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Completed</span>
                </a>
            </li>
            
            <!-- Admin only -->
            <?php if (isAdmin()): ?>
            <li class="sidebar-menu-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="sidebar-menu-divider"></li>
            <li class="sidebar-menu-item">
                <a href="logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="sidebar-footer-text">
                <small>&copy; <?= date('Y') ?> Kaldis Coffee</small>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header Bar -->
        <header class="top-header">
            <div class="top-header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" 
                     alt="Logo" 
                     class="logo-header <?= $logoSize ?>"
                     style="height: <?= $logoSize === 'small' ? '25px' : ($logoSize === 'medium' ? '35px' : '45px') ?>; 
                            object-fit: contain;
                            margin-right: 10px;">
                <?php endif; ?>
                <h1 class="page-title"><?= $page_title ?? 'Dashboard' ?></h1>
            </div>
            <div class="top-header-right">
                <div class="top-header-user">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    <span class="badge bg-<?= ($_SESSION['role'] ?? '') === 'Admin' ? 'danger' : (($_SESSION['role'] ?? '') === 'Support Team' ? 'info' : 'primary') ?> ms-2">
                        <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
                    </span>
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
    <?php endif; ?>
