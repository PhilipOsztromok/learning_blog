<?php
// ============================================================
// ANIME VAULT - Database Configuration
// File: /var/www/html/anime/includes/db.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'anime_vault');
define('DB_USER', 'philip');       // Change to your DB username
define('DB_PASS', 'AsT@1sAd3mon');    // Change to your DB password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    return $pdo;
}
