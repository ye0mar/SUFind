<?php
// =========================================================
// DATABASE CONFIGURATION
// Adjust these values to match your XAMPP/MySQL setup.
// =========================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'lost_and_found_db1');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

// Base URL of the application (adjust if you use a non-default port)
// Example with port 8080: define('BASE_URL', 'http://localhost:8080/lost_and_found');
define('BASE_URL', 'http://localhost:8080/lost_and_found');

// Base path on disk
define('BASE_PATH', dirname(__DIR__));

// Upload directory (must be writable)
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Backup storage (NOT web-accessible)
define('BACKUP_DIR', BASE_PATH . '/storage/backups/');

// Create directories if missing
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0755, true);

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Generic error — never expose credentials
    http_response_code(500);
    die('Database connection failed. Please check your configuration.');
}