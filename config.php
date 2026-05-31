<?php
// ─── Database configuration ───────────────────────────────────────────────
// Edit these values to match your MySQL setup.

define('DB_HOST', 'localhost');
define('DB_NAME', 'learning_blog');
define('DB_USER', 'philip');
define('DB_PASS', 'AsT@1sAd3mon');
define('DB_CHARSET', 'utf8mb4');


// ─── Site configuration ───────────────────────────────────────────────────
define('SITE_TITLE', 'Philip\'s Learning Blog');
define('SITE_BASE_URL', 'http://localhost'); // no trailing slash

// ─── Database connection (singleton) ──────────────────────────────────────
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production you'd log this rather than display it
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
