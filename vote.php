<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

// Initialize variables
$poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;
$user_id = $_SESSION['user_id'] ?? null;
$already_voted = false;
$voted_successfully = isset($_GET['voted']) && $_GET['voted'] == 1;
$error = null;
$poll = [];
$options = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if user has voted
    if ($user_id && $poll_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND poll_id = ?");
        $stmt->execute([$user_id, $poll_id]);
        $already_voted = $stmt->fetchColumn() > 0;
    }

    // Fetch poll data
    if ($poll_id) {
        $stmt = $conn->prepare("SELECT * FROM polls WHERE id = ?");
        $stmt->execute([$poll_id]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($poll) {
            $stmt = $conn->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
            $stmt->execute([$poll_id]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Handle vote submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
        if (!isset($_POST['option_id'])) {
            $error = "Please select an option to vote";
        } elseif (!$user_id) {
            header("Location: login.php?redirect=vote.php?poll_id=" . $poll_id);
            exit();
        } elseif (!$already_voted) {
            $option_id = (int)$_POST['option_id'];
            $stmt = $conn->prepare("INSERT INTO votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$poll_id, $option_id, $user_id]);
            $already_voted = true;
            header("Location: vote.php?poll_id=$poll_id&voted=1");
            exit();
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<?php include 'navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote: <?php echo isset($poll['question']) ? htmlspecialchars($poll['question']) : 'Poll'; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-light: #a29bfe;
            --secondary: #00cec9;
            --accent: #fd79a8;
            --success: #00b894;
            --danger: #d63031;
            --warning: #fdcb6e;
            --light: #f8f9fa;
            --dark: #2d3436;
            --gray: #636e72;
            --light-gray: #dfe6e9;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --box-shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #dfe6e9 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 2.2rem;
            position: relative;
            padding-bottom: 1rem;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }
        
        .poll-question {
            font-size: 1.3rem;
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--light);
            border-radius: var(--border-radius-sm);
        }
        
        .poll-category {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .vote-form {
            margin-top: 2rem;
        }
        
        .options-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .option-item {
            margin-bottom: 1rem;
            padding: 1.5rem;
            border-radius: var(--border-radius-sm);
            background: white;
            border: 2px solid var(--light-gray);
            transition: var(--transition);
            position: relative;
        }
        
        .option-item:hover {
            border-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
        }
        
        .option-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
        }
        
        .option-radio {
            margin-right: 1rem;
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }
        
        .option-text {
            font-size: 1.1rem;
            flex-grow: 1;
        }
        
        .option-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: var(--border-radius-sm);
            display: block;
        }
        
        .option-emoji {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .btn {
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn i {
            font-size: 1.1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .btn-submit {
            background-color: var(--success);
            color: white;
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
        }
        
        .btn-submit:hover {
            background-color: #00a884;
        }
        
        .btn-back {
            background-color: var(--primary);
            color: white;
            margin-top: 1rem;
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-back:hover {
            background-color: #5a4bd1;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 5px solid transparent;
        }
        
        .message i {
            font-size: 1.2rem;
        }
        
        .success-message {
            background-color: rgba(0, 184, 148, 0.1);
            color: #007a63;
            border-left-color: var(--success);
        }
        
        .error-message {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }
        
        .already-voted {
            text-align: center;
            padding: 2rem;
        }
        
        .already-voted-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Trailer preview styles */
        .trailer-preview {
            margin-top: 1rem;
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: var(--border-radius-sm);
            display: none;
        }
        
        .trailer-preview iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .trailer-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            color: var(--primary);
            cursor: pointer;
            font-weight: 500;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }
        
        .trailer-toggle:hover {
            background-color: rgba(108, 92, 231, 0.1);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 2rem 1.5rem;
            }
            
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class='bx bx-poll'></i> Cast Your Vote</h1>
            <div class="poll-question"><?php echo isset($poll['question']) ? htmlspecialchars($poll['question']) : 'Poll question not available.'; ?></div>
            <?php if (isset($poll['category'])): ?>
                <div class="poll-category">Category: <?php echo htmlspecialchars($poll['category']); ?></div>
            <?php endif; ?>
        </header>
        
        <?php if ($voted_successfully): ?>
            <div class="message success-message">
                <i class='bx bx-check-circle'></i>
                <div>Your vote has been recorded successfully!</div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error-message">
                <i class='bx bx-error-circle'></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($already_voted): ?>
            <div class="already-voted">
                <div class="already-voted-icon">
                    <i class='bx bx-check-circle'></i>
                </div>
                <h2>You've already voted in this poll!</h2>
                <p>Thank you for participating. You can view the results or return to the polls.</p>
                <a href="results.php?poll_id=<?php echo $poll_id; ?>" class="btn btn-back">
                    <i class='bx bx-stats'></i> View Results
                </a>
                <a href="available_polls.php" class="btn btn-back">
                    <i class='bx bx-arrow-back'></i> Back to Polls
                </a>
            </div>
        <?php else: ?>
            <form action="vote.php?poll_id=<?php echo $poll_id; ?>" method="POST" class="vote-form">
                <ul class="options-list">
                    <?php foreach ($options as $option): ?>
                        <li class="option-item">
                            <label class="option-label">
                                <input type="radio" name="option_id" value="<?php echo $option['id']; ?>" class="option-radio" required>
                                <?php if (!empty($option['emoji'])): ?>
                                    <span class="option-emoji"><?php echo htmlspecialchars($option['emoji']); ?></span>
                                <?php endif; ?>
                                <span class="option-text"><?php echo htmlspecialchars($option['text']); ?></span>
                            </label>
                            <?php if (!empty($option['image'])): ?>
                                <img src="<?php echo htmlspecialchars($option['image']); ?>" alt="Option image" class="option-image">
                            <?php endif; ?>
                            
                            <?php if (!empty($option['trailer'])): ?>
                                <?php
                                // Convert YouTube URL to embed format if needed
                                $trailer_url = $option['trailer'];
                                // Handle youtu.be short URLs
                                if (strpos($trailer_url, 'youtu.be/') !== false) {
                                    $video_id = substr($trailer_url, strpos($trailer_url, 'youtu.be/') + 9);
                                    $trailer_url = 'https://www.youtube.com/embed/' . $video_id;
                                }
                                // Handle regular YouTube URLs
                                elseif (strpos($trailer_url, 'youtube.com/watch?v=') !== false) {
                                    $trailer_url = str_replace('youtube.com/watch?v=', 'youtube.com/embed/', $trailer_url);
                                    $trailer_url = preg_replace('/&.*$/', '', $trailer_url);
                                }
                                // Ensure URL is secure (https)
                                $trailer_url = str_replace('http://', 'https://', $trailer_url);
                                ?>
                                <div class="trailer-toggle" onclick="toggleTrailer(this, '<?php echo htmlspecialchars($trailer_url, ENT_QUOTES); ?>')">
                                    <i class='bx bx-play-circle'></i>
                                    <span>Show Trailer Preview</span>
                                </div>
                                <div class="trailer-preview" id="trailer-<?php echo $option['id']; ?>">
                                    <!-- Iframe will be inserted here by JavaScript -->
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <button type="submit" name="vote" class="btn btn-submit">
                    <i class='bx bx-check'></i> Submit Vote
                </button>
                
                <a href="available_polls.php" class="btn btn-back">
                    <i class='bx bx-arrow-back'></i> Back to Polls
                </a>
            </form>
            
            <script>
                function toggleTrailer(element, trailerUrl) {
                    const optionItem = element.closest('.option-item');
                    const trailerContainer = optionItem.querySelector('.trailer-preview');
                    const isHidden = window.getComputedStyle(trailerContainer).display === 'none';

                    if (isHidden) {
                        trailerContainer.innerHTML = `<iframe src="${trailerUrl}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                        trailerContainer.style.display = 'block';
                        element.querySelector('span').textContent = 'Hide Trailer Preview';
                        element.querySelector('i').className = 'bx bx-x-circle';
                    } else {
                        trailerContainer.innerHTML = '';
                        trailerContainer.style.display = 'none';
                        element.querySelector('span').textContent = 'Show Trailer Preview';
                        element.querySelector('i').className = 'bx bx-play-circle';
                    }
                }
            </script>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>