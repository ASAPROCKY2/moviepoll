<?php
// ===== Database Configuration =====
define('DB_HOST', '127.0.0.1'); // or 'localhost'
define('DB_USER', 'root');      // change if needed
define('DB_PASS', '');
define('DB_NAME', 'movie-poll-db');

// ===== Optional Site Configuration =====
define('SITE_NAME', 'Security Admin Panel');
define('LOG_TABLE', 'security_logs');
define('RESULTS_PER_PAGE', 15);

// ===== Create Database Connection =====
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Optional: test the connection
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed. Please check:<br>
        1. MySQL is running in XAMPP<br>
        2. Database '" . DB_NAME . "' exists in phpMyAdmin<br>
        3. Your credentials are correct<br>
        Error: " . $e->getMessage());
}
?>
