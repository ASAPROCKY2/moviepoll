<?php
// Database configuration
$host = "localhost";
$root_user = "root";
$root_pass = "";

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$host", $root_user, $root_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database with explicit InnoDB default
    $conn->exec("CREATE DATABASE IF NOT EXISTS `movie-poll-db` 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Set default storage engine
    $conn->exec("SET GLOBAL default_storage_engine = 'InnoDB'");

    // Create admin user
    $conn->exec("CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin123'");
    $conn->exec("GRANT ALL PRIVILEGES ON `movie-poll-db`.* TO 'admin'@'localhost'");
    $conn->exec("FLUSH PRIVILEGES");

    // Switch to database
    $conn->exec("USE `movie-poll-db`");

    // Create tables with explicit InnoDB engine
    $conn->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `last_login` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create default admin
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO `admins` (username, password) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $stmt->execute(['admin', $hashed_password]);

    echo "Database setup completed successfully with InnoDB engine!";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . 
        "\nTry running: mysqlcheck --repair --all-databases --use-frm -u root -p");
}
?>