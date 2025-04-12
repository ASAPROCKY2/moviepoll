<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session securely
session_start();
session_regenerate_id(true);

// Database configuration
$db_host = 'localhost';
$db_name = 'movie-poll-db';
$db_user = 'admin';
$db_pass = 'admin123';

$error = '';

try {
    // Attempt database connection
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p style='color: green;'>Database connection successful!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test a simple query to check if 'users' table exists
try {
    $stmt = $conn->query("SELECT 1 FROM users LIMIT 1");
    echo "<p style='color: green;'>Users table exists.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking users table: " . $e->getMessage() . "</p>";
}

// Test retrieving admin details
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $admin_count = $stmt->fetchColumn();
    
    if ($admin_count > 0) {
        echo "<p style='color: green;'>Admin account exists.</p>";
    } else {
        echo "<p style='color: red;'>No admin account found!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error retrieving admin account: " . $e->getMessage() . "</p>";
}
?>
