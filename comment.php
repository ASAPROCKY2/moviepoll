<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
        $comment = trim($_POST['comment']);
        $user_id = $_SESSION['user_id'];

        if (!empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, comment, created_at) VALUES (:user_id, :comment, NOW())");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':comment', $comment);
            $stmt->execute();
            header("Location: index.php");
            exit();
        } else {
            $error = "Comment cannot be empty!";
        }
    }

    // Fetch comments
    $stmt = $conn->query("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id ORDER BY created_at DESC");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat & Comment Section</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <style>
        body {
    background-color: #333;
    font-family: Arial, sans-serif;
    margin: 0;
    color: #333;
}

#content, .comment-section {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 10px #4a90e2;
    border-radius: 10px;
}

h1 { color: #4a90e2; text-align: center; }
p { line-height: 1.6; }
img { max-width: 100%; border-radius: 10px; margin-bottom: 20px; }

#chat-icon {
    position: fixed; bottom: 20px; right: 20px;
    background: #4a90e2; width: 60px; height: 60px;
    border-radius: 50%; display: flex; justify-content: center;
    align-items: center; cursor: pointer; color: #fff; font-size: 30px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

#chat-container {
    display: none; flex-direction: column;
    width: 90%; max-width: 400px; height: 75%;
    position: fixed; bottom: 54%; right: 50%;
    transform: translate(50%, 50%);
    background: #fff; border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

#chat-header {
    background: #4a90e2; color: #fff;
    padding: 10px; display: flex; justify-content: space-between;
}

#chat-box { flex-direction: column; height: calc(100% - 70px); overflow: auto; }
#user-input-container { display: flex; background: #fff; padding: 5px; }
#user-input { flex-grow: 1; padding: 10px; border: 1px solid #ccc; }
#send-button { padding: 10px; background: #4a90e2; color: #fff; cursor: pointer; }
</style>
    <?php include 'navbar.php'; ?>

    <!-- Chat Bot Section -->
    <div id="content">
        <h1>About Our Chat Bot</h1>
        <p>Welcome! Our chat bot is here to help you with questions.</p>
        <div class="images-container">
            <img src="https://via.placeholder.com/350x200" alt="Image 1">
            <img src="https://via.placeholder.com/350x200" alt="Image 2">
        </div>
        <p>Ask "Hello", "How are you?", or "What are you doing?"</p>
    </div>

    <div id="chat-icon">💬</div>
    <div id="chat-container">
        <div id="chat-header">
            <div class="close-chat">←</div>
            <span>Chat Bot Assistant</span>
            <div class="reset-chat"><img src="reload.png" style="height: 30px;"></div>
        </div>
        <div id="chat-box"><div id="messages"></div></div>
        <div id="user-input-container">
            <input type="text" id="user-input" placeholder="Type a message...">
            <button id="send-button">Send</button>
        </div>
    </div>

    <!-- Comment Section -->
    <div class="comment-section">
        <h1>💬 Comment Section</h1>
        <form class="comment-form" method="POST" action="">
            <textarea name="comment" placeholder="Write your comment here..." required></textarea>
            <button type="submit">Submit</button>
        </form>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="comment-list">
            <?php if (empty($comments)): ?>
                <p>No comments yet. Be the first!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="username"><?php echo htmlspecialchars($comment['username']); ?></div>
                        <div class="timestamp"><?php echo htmlspecialchars($comment['created_at']); ?></div>
                        <div class="content"><?php echo htmlspecialchars($comment['comment']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="main.js"></script>
</body>
</html>
