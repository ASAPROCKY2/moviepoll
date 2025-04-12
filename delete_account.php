<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get password from request
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';

    if (empty($password)) {
        throw new Exception('Password is required');
    }

    // Verify password
    $stmt = $conn->prepare("SELECT password, avatar_url FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($password, $user['password'])) {
        throw new Exception('Incorrect password');
    }

    // Delete user's avatar if it exists and is not the default
    if ($user['avatar_url'] && !str_contains($user['avatar_url'], 'ui-avatars.com')) {
        @unlink($user['avatar_url']);
    }

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Delete user's votes
        $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // Delete user's activity
        $stmt = $conn->prepare("DELETE FROM user_activity WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // Delete user's stats
        $stmt = $conn->prepare("DELETE FROM user_stats WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Destroy session
        session_destroy();

        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}