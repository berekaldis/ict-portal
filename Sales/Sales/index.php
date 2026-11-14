<?php
// index.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect to login or dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>