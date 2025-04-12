<?php
session_start();
require_once 'db_connection.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Optional: Check if admin still exists in database
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
$stmt->execute([$_SESSION['admin_username']]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
?>