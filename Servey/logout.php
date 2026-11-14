<?php
require_once 'config.php';

// Check if the user is actually logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $adminUsername = $_SESSION['admin_username'] ?? 'unknown';
    
    // Log the logout activity using the standard config helper
    logActivity('logout', 'admin', null, "Admin '{$adminUsername}' logged out");
    
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie (standard practice for clean logout)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Always redirect to login page regardless of prior login state
header('Location: login.php');
exit;
?>