<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

require_admin_login();

header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token missing']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_multiple') {
        // Delete multiple users
        if (empty($_POST['user_ids'])) {
            echo json_encode(['success' => false, 'message' => 'No users selected']);
            exit;
        }

        $user_ids = is_array($_POST['user_ids']) ? $_POST['user_ids'] : [$_POST['user_ids']];
        
        // Validate and sanitize user IDs
        $valid_ids = [];
        foreach ($user_ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $valid_ids[] = $id;
            }
        }

        if (empty($valid_ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user IDs']);
            exit;
        }

        // Prevent deleting yourself
        if (in_array($_SESSION['user_id'], $valid_ids)) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
            exit;
        }

        // Prepare the query
        $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
        $query = "DELETE FROM users WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        
        // Execute with validated user IDs
        $stmt->execute($valid_ids);
        
        $deletedCount = $stmt->rowCount();
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "Successfully deleted $deletedCount user(s)"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No users were deleted']);
        }
    } else {
        // Delete single user
        if (empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }

        $user_id = (int)$_POST['id'];
        
        // Validate user ID
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        // Prevent deleting yourself
        if ($user_id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
            exit;
        }
        
        // Prepare and execute deletion
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
        }
    }
} catch (PDOException $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}