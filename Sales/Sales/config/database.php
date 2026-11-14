<?php
// config/database.php

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'sales_');
}

class Database {
    private static $instance = null;
    private $conn;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $prefix = DB_PREFIX;
    
    private function __construct() {
        $defaultDbName = getenv('DB_NAME') ?: (getenv('kaldisbp_ict') ?: 'kaldisbp_ict');
        $defaultUser = getenv('DB_USER') ?: (getenv('kaldisbp_it') ?: 'kaldisbp_it');
        $defaultPassword = getenv('DB_PASS') ?: (getenv('@IT2026!') ?: '@IT2026!');

        $configs = [
            [
                'host' => getenv('DB_HOST') ?: (getenv('localhost') ?: 'localhost'),
                'dbname' => $defaultDbName,
                'username' => $defaultUser,
                'password' => $defaultPassword,
            ],
            [
                'host' => '127.0.0.1',
                'dbname' => $defaultDbName,
                'username' => $defaultUser,
                'password' => $defaultPassword,
            ],
            [
                'host' => 'localhost',
                'dbname' => $defaultDbName,
                'username' => 'kaldisbp_it',
                'password' => '@IT2026!',
            ],
        ];

        $lastError = null;

        foreach ($configs as $config) {
            try {
                $this->host = $config['host'];
                $this->dbname = $config['dbname'];
                $this->username = $config['username'];
                $this->password = $config['password'];

                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return;
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
            }
        }

        die("Connection failed: " . $lastError);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }

    public function getPrefix() {
        return $this->prefix;
    }
}
?>