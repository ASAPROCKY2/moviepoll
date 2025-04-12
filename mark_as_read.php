<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Remove duplicate session_start() since it's already called in auth.php
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false]));
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['id'] ?? null;

if (!$notificationId) {
    die(json_encode(['success' => false]));
}

$db = $conn;

try {
    $stmt = $db->prepare("
        UPDATE user_notifications 
        SET status = 'read' 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->bindParam(':id', $notificationId);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => $stmt->rowCount() > 0]);
    
} catch (PDOException $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    echo json_encode(['success' => false]);
}
?>