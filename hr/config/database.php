<?php
// config/database.php
$host = 'localhost';
$dbname = 'kaldisbp_ict';
$username = 'kaldisbp_it';
$password = '@IT2026!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Don't start session here if not needed
// session_start();
?>