<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// Verify CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['error' => 'CSRF token validation failed']));
}

$user_id = (int)$_GET['id'];
try {
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => $result['status'] ?? 'active'
    ]);
} catch (PDOException $e) {
    error_log("Get user status error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to get user status']);
}