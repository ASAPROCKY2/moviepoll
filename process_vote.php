<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["vote"])) {
    $vote = $_POST["vote"];

    // Store the vote in the database (example with MySQL)
    $conn = new mysqli("localhost", "root", "", "voting_system");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO votes (user_id, choice) VALUES (?, ?)");
    $stmt->bind_param("ss", $_SESSION['user_id'], $vote);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Thank you for voting!";
} else {
    echo "Invalid vote submission!";
}
?>
