<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$sql = "
    SELECT n.id, n.title, n.message, n.type, n.created_at, un.is_read
    FROM notifications n
    JOIN user_notifications un ON n.id = un.notification_id
    WHERE un.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);
