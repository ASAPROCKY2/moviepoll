<?php
require_once 'config.php'; // your PDO setup
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = $_POST['movie_id'];
    $pollId = $_POST['poll_id'];
    $userId = $_SESSION['user_id'] ?? null;

    if (!$movieId || !$pollId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    // Optional: prevent double-voting
    $stmt = $conn->prepare("SELECT * FROM votes WHERE user_id = ? AND poll_id = ?");
    $stmt->execute([$userId, $pollId]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already voted']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO votes (user_id, poll_id, movie_id) VALUES (?, ?, ?)");
    $success = $stmt->execute([$userId, $pollId, $movieId]);

    echo json_encode(['success' => $success]);
}
?>
