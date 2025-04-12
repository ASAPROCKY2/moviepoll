<?php
session_start(); // Start a session for authentication

// Database connection details
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if a poll ID is provided in the URL
    if (isset($_GET['poll_id'])) {
        $poll_id = $_GET['poll_id'];

        // Fetch the poll details from the database
        $stmt = $conn->prepare("SELECT * FROM polls WHERE id = ?");
        $stmt->execute([$poll_id]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$poll) {
            // Redirect to available_polls.php with an error message
            header("Location: available_polls.php?error=Poll not found.");
            exit();
        }

        // Generate the shareable link
        $poll_url = "http://yourwebsite.com/vote.php?poll_id=" . urlencode($poll['id']);
    } else {
        // Redirect to available_polls.php with an error message
        header("Location: available_polls.php?error=Poll ID not provided.");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Poll</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }

        .poll-question {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .share-section {
            margin-top: 30px;
        }

        .share-section h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }

        .share-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .share-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .share-button:hover {
            background-color: #0056b3;
        }

        #pollLink {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        #copy-message {
            color: #28a745;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
            display: none;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-size: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Share Poll</h1>

        <!-- Poll Question -->
        <div class="poll-question">
            <p><?php echo htmlspecialchars($poll['question']); ?></p>
        </div>

        <!-- Shareable Link Section -->
        <div class="share-section">
            <h2>Share this Poll:</h2>
            <input type="text" id="pollLink" value="<?php echo $poll_url; ?>" readonly>
            <div class="share-buttons">
                <button onclick="copyLink()" class="share-button">📋 Copy Link</button>
                <a href="https://wa.me/?text=Vote%20in%20this%20poll:%20<?php echo urlencode($poll_url); ?>" target="_blank" class="share-button">📱 WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($poll_url); ?>" target="_blank" class="share-button">📘 Facebook</a>
                <a href="https://twitter.com/intent/tweet?text=Vote%20now:%20<?php echo urlencode($poll_url); ?>" target="_blank" class="share-button">🐦 Twitter</a>
                <a href="mailto:?subject=Vote in this poll&body=Check out this poll: <?php echo $poll_url; ?>" class="share-button">✉️ Email</a>
            </div>
            <p id="copy-message">Link copied to clipboard!</p>
        </div>

        <!-- Back to Polls Link -->
        <a href="available_polls.php" class="back-link">← Back to Available Polls</a>
    </div>

    <script>
        function copyLink() {
            const pollLink = document.getElementById('pollLink');
            pollLink.select();
            document.execCommand('copy');

            const copyMessage = document.getElementById('copy-message');
            copyMessage.style.display = 'block';
            setTimeout(() => {
                copyMessage.style.display = 'none';
            }, 2000); // Hide the message after 2 seconds
        }
    </script>
</body>
</html>