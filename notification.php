<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

require_admin_login();

$db = $conn;

// Get notification stats
$result = $db->query("SELECT COUNT(*) as total FROM notifications");
$total_notifications = $result->fetch(PDO::FETCH_ASSOC)['total'];

$result = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM user_notifications");
$total_delivered = $result->fetch(PDO::FETCH_ASSOC)['total'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $send_inapp = isset($_POST['send_inapp']);

    // Validate input
    $errors = [];
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message)) $errors[] = 'Message is required';
    if (!$send_inapp) $errors[] = 'In-app notification must be selected';

    if (empty($errors)) {
        // Get all active users
        $stmt = $db->query("SELECT id FROM users WHERE status = 'active'");
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Save notification to database
        $notification_data = [
            'type' => 'admin',
            'subject' => $subject,
            'message' => $message,
            'created_by' => $_SESSION['admin_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $stmt = $db->prepare("INSERT INTO notifications (type, data, created_at, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $notification_data['type'],
            json_encode($notification_data),
            $notification_data['created_at'],
            $notification_data['created_by']
        ]);
        $notification_id = $db->lastInsertId();

        // Create user notifications
        if (!empty($user_ids)) {
            $values = [];
            foreach ($user_ids as $user_id) {
                $values[] = "($user_id, $notification_id, 'unread', NOW())";
            }
            $query = "INSERT INTO user_notifications (user_id, notification_id, status, created_at) VALUES " . implode(',', $values);
            $db->query($query);
        }

        $_SESSION['notification_success'] = count($user_ids);
        header('Location: notification.php');
        exit;
    }
}

// HEADER
$page_title = 'Send Notifications | The Flixx';
include 'header.php';

if (isset($_SESSION['notification_success'])) {
    $success = $_SESSION['notification_success'];
    unset($_SESSION['notification_success']);
}
?>

<style>
.notification-container {
    max-width: 800px;
    margin: 40px auto;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.notification-header {
    background: #6c5ce7;
    padding: 20px;
    color: white;
    font-size: 1.5rem;
    text-align: center;
}

.notification-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #5a5c69;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.delivery-method {
    margin: 15px 0;
}

.btn {
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 1rem;
    background: #6c5ce7;
    color: white;
    border: none;
    cursor: pointer;
}

.stats-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    gap: 20px;
}

.stat-card {
    flex: 1;
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.stat-card i {
    font-size: 2.5rem;
    color: #6c5ce7;
    margin-bottom: 15px;
}

.stat-card h3 {
    font-size: 1.8rem;
    margin: 10px 0;
}

.success-message {
    background: rgba(0, 184, 148, 0.1);
    border-left: 4px solid #00b894;
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 4px;
}

.error-message {
    background: rgba(214, 48, 49, 0.1);
    border-left: 4px solid #d63031;
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 4px;
}

#message {
    min-height: 150px;
    resize: vertical;
}
</style>

<div class="notification-container">
    <div class="notification-header">
        <i class="fas fa-bell"></i> Send Admin Notifications
    </div>
    
    <div class="notification-form">
        <?php if (isset($success)): ?>
            <div class="success-message">
                <h3>Notification Sent Successfully!</h3>
                <p>Delivered to <?= $success ?> users in-app.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <h3>Error Sending Notification</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-bell"></i>
                <h3><?= number_format($total_notifications) ?></h3>
                <p>Notifications Sent</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?= number_format($total_delivered) ?></h3>
                <p>Notifications Delivered</p>
            </div>
        </div>

        <form method="POST" id="notificationForm">
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control" name="subject" required
                       placeholder="Notification subject">
            </div>
            
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" required
                          placeholder="Write your notification message here..."></textarea>
            </div>
            
            <div class="delivery-method">
                <input type="checkbox" id="send_inapp" name="send_inapp" checked>
                <label for="send_inapp">Send as in-app notification</label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('notificationForm');
    
    form.addEventListener('submit', function(e) {
        const subject = form.querySelector('[name="subject"]').value.trim();
        const message = form.querySelector('[name="message"]').value.trim();
        
        if (!subject || !message) {
            e.preventDefault();
            alert('Subject and message are required');
            return;
        }
        
        if (!confirm('Are you sure you want to send this notification to all users?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include 'footer.php'; ?>