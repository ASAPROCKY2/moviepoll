<?php
session_start(); // Start a session for authentication

// Database connection details
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $option_id = $_POST['option_id'];

        // Insert the vote into the database
        $stmt = $conn->prepare("INSERT INTO votes (option_id) VALUES (?)");
        $stmt->execute([$option_id]);

        // Redirect to available_polls.php with a success message
        header("Location: available_polls.php?message=Vote submitted successfully.");
        exit();
    } else {
        // Redirect to available_polls.php with an error message
        header("Location: available_polls.php?error=Invalid request.");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>