<?php
session_start();
require 'db_connection.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = $_POST['theme'];

    // Update the user's theme preference in the database
    $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);

    // Store theme in session
    $_SESSION['theme'] = $theme;

    // Redirect back
    header("Location: profile.php");
    exit();
}
?>
