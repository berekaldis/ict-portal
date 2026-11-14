<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action == 'get_users') {
        // Get all users (don't return password hashes)
        $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM hr_users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'users' => $users]);
        
    } elseif ($action == 'add_user') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'User';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit;
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM hr_users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            exit;
        }
        
        // Add new user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO hr_users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $passwordHash, $role]);
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
        
    } elseif ($action == 'change_password') {
        $userId = $data['user_id'] ?? 0;
        $newPassword = $data['new_password'] ?? '';
        
        if (empty($userId) || empty($newPassword)) {
            echo json_encode(['success' => false, 'error' => 'User ID and password are required']);
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit;
        }
        
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE hr_users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        
    } elseif ($action == 'delete_user') {
        $userId = $data['user_id'] ?? 0;
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            exit;
        }
        
        // Don't allow deleting if only one user exists
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM hr_users");
        $count = $stmt->fetchColumn();
        if ($count <= 1) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete the last user']);
            exit;
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM hr_users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>