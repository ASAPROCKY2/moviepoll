<?php
// create_admin.php
require_once 'config.php';

// Admin credentials (change these!)
$adminUsername = 'admin';
$adminPassword = 'admin123'; // Storing as plain text

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = :username");
    $stmt->bindParam(':username', $adminUsername);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        die("Admin user already exists!");
    }

    // Insert admin with plain text password
    $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (:username, :password)");
    $stmt->bindParam(':username', $adminUsername);
    $stmt->bindParam(':password', $adminPassword); // Storing plain text
    $stmt->execute();
    
    echo "Admin created successfully!<br>";
    echo "Username: ".htmlspecialchars($adminUsername)."<br>";
    echo "Password: ".htmlspecialchars($adminPassword)."<br>";
    echo "<strong>Warning:</strong> Passwords are stored as plain text!";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>