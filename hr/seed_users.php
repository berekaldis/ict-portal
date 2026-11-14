<?php
// seed_users.php — run once to insert default users with hashed passwords
require_once __DIR__ . '/config/database.php';

$users = [
    ['username' => 'user', 'password' => 'user123', 'role' => 'User'],
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'Admin'],
    ['username' => 'superadmin', 'password' => 'super123', 'role' => 'Superadmin'],
];

foreach ($users as $u) {
    $stmt = $pdo->prepare('SELECT id FROM hr_users WHERE username = ? LIMIT 1');
    $stmt->execute([$u['username']]);
    if ($stmt->fetch()) {
        echo "User {$u['username']} already exists.\n";
        continue;
    }
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO hr_users (username, password_hash, role) VALUES (?, ?, ?)');
    $ins->execute([$u['username'], $hash, $u['role']]);
    echo "Inserted user: {$u['username']}\n";
}

echo "Done.\n";