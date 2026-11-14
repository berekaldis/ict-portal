<?php
// includes/auth.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============ SESSION FUNCTIONS ============

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

function isSupportTeam() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Support Team';
}

function isFinanceTeam() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Finance Team';
}

function getUserRole() {
    return $_SESSION['role'] ?? 'Guest';
}

function getCurrentUser() {
    return $_SESSION['username'] ?? 'Unknown';
}

// ============ LOGIN FUNCTION ============

function login($username, $password) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sales_users WHERE username = ? AND password = MD5(?)");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Check if first login
            $isFirstLogin = (bool)($user['first_login'] ?? true);
            
            // Update last activity
            $updateStmt = $db->prepare("UPDATE sales_users SET last_activity = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // If first login, update first_login flag
            if ($isFirstLogin) {
                $updateStmt = $db->prepare("UPDATE sales_users SET first_login = 0 WHERE id = ?");
                $updateStmt->execute([$user['id']]);
            }
            
            // Log action
            logAction($username, 'Login', "User $username logged in");
            
            return [
                'success' => true, 
                'role' => $user['role'],
                'isFirstLogin' => $isFirstLogin
            ];
        }
        return ['success' => false, 'error' => 'Invalid username or password'];
        
    } catch(PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}

// ============ LOGOUT FUNCTION ============

function logout() {
    if (isset($_SESSION['username'])) {
        logAction($_SESSION['username'], 'Logout', "User logged out");
    }
    session_destroy();
    header('Location: login.php');
    exit();
}

// ============ ACCESS CONTROL FUNCTIONS ============

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function requireSupportOrAdmin() {
    requireLogin();
    if (!isAdmin() && !isSupportTeam()) {
        header('Location: cases.php');
        exit();
    }
}

function requireFinanceOrAdmin() {
    requireLogin();
    if (!isAdmin() && !isFinanceTeam()) {
        header('Location: cases.php');
        exit();
    }
}

// ============ PERMISSION CHECKS ============

function canEditCase($case) {
    // Admin can edit any case
    if (isAdmin()) return true;
    
    // Support Team can edit cases assigned to them
    if (isSupportTeam()) {
        return isset($case['technical_name']) && $case['technical_name'] === $_SESSION['username'];
    }
    
    // Finance Team can view but not edit
    return false;
}

function canViewCase($case) {
    // Admin can view any case
    if (isAdmin()) return true;
    
    // Support Team can view cases assigned to them
    if (isSupportTeam()) {
        return isset($case['technical_name']) && $case['technical_name'] === $_SESSION['username'];
    }
    
    // Finance Team can view all cases
    if (isFinanceTeam()) return true;
    
    return false;
}

function canApproveCase() {
    return isAdmin();
}
