<?php
// change_password.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Only allow if first login
if (!isset($_SESSION['first_login']) || $_SESSION['first_login'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['temp_username'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE sales_users SET password = MD5(?), first_login = FALSE WHERE username = ?");
            $stmt->execute([$newPassword, $username]);
            
            $_SESSION['first_login'] = false;
            unset($_SESSION['temp_username']);
            
            $success = 'Password changed successfully! Redirecting...';
            header('Refresh: 2; URL=dashboard.php');
        } catch (PDOException $e) {
            $error = 'Failed to update password: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Kaldis Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <i class="fas fa-key fa-3x mb-2"></i>
                        <h4 class="mb-0">Change Password</h4>
                        <p class="mb-0 small">Welcome <?= htmlspecialchars($username) ?>, please change your password</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!$success): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Change Password</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
