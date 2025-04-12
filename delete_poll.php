<?php
session_start();

// Verify if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = "localhost";
$dbname = "movie-poll-db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle poll deletion
if (isset($_GET['delete_poll'])) {
    $poll_id = $_GET['delete_poll'];
    try {
        $stmt = $pdo->prepare("DELETE FROM polls WHERE id = :poll_id");
        $stmt->execute(['poll_id' => $poll_id]);

        $_SESSION['message'] = "Poll deleted successfully!";
        $_SESSION['alert_class'] = "alert-success";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting poll: " . $e->getMessage();
        $_SESSION['alert_class'] = "alert-danger";
    }
    header("Location: manage_polls.php");
    exit();
}

// Fetch all polls
try {
    $stmt = $pdo->query("SELECT id, question, created_at FROM polls ORDER BY created_at DESC");
    $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching polls: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Polls</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<style>


/* General styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

/* Main container */
.container {
    max-width: 800px;
    margin: 50px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Page title */
h2 {
    text-align: center;
    color: #333;
}

/* Success and error messages */
.alert-success {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #333;
    color: white;
}

tr:hover {
    background-color: #f1f1f1;
}

/* Delete button */
.delete-button {
    background-color: #dc3545;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
    transition: background 0.3s ease;
}

.delete-button:hover {
    background-color: #c82333;
}

/* Create poll button */
.btn {
    display: block;
    width: fit-content;
    padding: 10px 15px;
    margin: 20px auto;
    background-color: #28a745;
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.btn:hover {
    background-color: #218838;
}

</style>





    <div class="container">
        <h2>Manage Polls</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?= $_SESSION['alert_class'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['alert_class']); ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($polls as $poll): ?>
                    <tr>
                        <td><?= htmlspecialchars($poll['id']) ?></td>
                        <td><?= htmlspecialchars($poll['question']) ?></td>
                        <td><?= date('M j, Y', strtotime($poll['created_at'])) ?></td>
                        <td>
                            <a href="manage_polls.php?delete_poll=<?= $poll['id'] ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this poll?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="create_poll.php" class="btn">Create New Poll</a>
    </div>
</body>
</html>
