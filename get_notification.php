<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    exit(json_encode([]));
}

try {
    // Fetch unread notifications for the current user
    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.data,
            un.created_at,
            un.status
        FROM user_notifications un
        JOIN notifications n ON un.notification_id = n.id
        WHERE un.user_id = :user_id 
        AND un.status = 'unread'
        ORDER BY un.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    
    // Process notifications
    $notifications = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Decode JSON data field if it exists
        if (isset($row['data']) && is_string($row['data'])) {
            $row['data'] = json_decode($row['data'], true) ?? [];
        }
        $notifications[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($notifications);
    exit;
    
} catch (PDOException $e) {
    error_log("Notification Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}