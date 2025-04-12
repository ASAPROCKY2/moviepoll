<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : null;

    if ($poll_id) {
        // Fetch single poll
        $stmt = $conn->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) AS total_votes,
                   (CASE 
                       WHEN p.end_date IS NULL THEN 1
                       WHEN p.end_date > NOW() THEN 1
                       ELSE 0
                   END) AS is_active
            FROM polls p
            WHERE p.id = ?
        ");
        $stmt->execute([$poll_id]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($poll) {
            // Fetch options + count votes per option
            $stmt = $conn->prepare("
                SELECT o.*, COUNT(v.id) AS vote_count
                FROM poll_options o
                LEFT JOIN votes v ON o.id = v.option_id
                WHERE o.poll_id = ?
                GROUP BY o.id
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$poll_id]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $poll['options'] = $options;
            $poll['total_votes'] = array_sum(array_column($options, 'vote_count'));
        }

    } else {
        // Fetch all polls
        $stmt = $conn->query("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) AS total_votes,
                   (CASE 
                       WHEN p.end_date IS NULL THEN 1
                       WHEN p.end_date > NOW() THEN 1
                       ELSE 0
                   END) AS is_active
            FROM polls p
            ORDER BY p.created_at DESC
        ");
        $all_polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_polls as &$poll) {
            // Fetch options + count votes per option
            $stmt = $conn->prepare("
                SELECT o.*, COUNT(v.id) AS vote_count
                FROM poll_options o
                LEFT JOIN votes v ON o.id = v.option_id
                WHERE o.poll_id = ?
                GROUP BY o.id
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$poll['id']]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $poll['options'] = $options;
            $poll['total_votes'] = array_sum(array_column($options, 'vote_count'));
        }
    }

    // Fetch active polls for sidebar
    $stmt = $conn->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) AS total_votes
        FROM polls p
        WHERE p.end_date > NOW() OR p.end_date IS NULL
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $active_polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

            
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($poll) ? htmlspecialchars($poll['title'] ?? $poll['question']) : 'Poll Results'; ?> | The Flixx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e50914;
            --secondary: #b81d24;
            --accent: #f5c518;
            --light: #f8f9fa;
            --dark: #221f1f;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray: #6c757d;
            --dark-gray: #343a40;
            --text-light: #f8f8f8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #000;
            color: var(--text-light);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://assets.nflxext.com/ffe/siteui/vlv3/9d3533b2-0e2b-40b2-95e0-ecd7979cc88b/a3873901-5b7c-46eb-b9fa-12fea5197bd3/IN-en-20240311-popsignuptwoweeks-perspective_alpha_website_large.jpg') no-repeat center center/cover;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header {
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2rem;
            letter-spacing: 1px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 i {
            color: var(--primary);
            font-size: 1.8rem;
        }

        .active-polls h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--accent);
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 1px;
        }

        .poll-list {
            list-style: none;
        }

        .poll-list li {
            margin-bottom: 10px;
        }

        .poll-list a {
            display: block;
            padding: 12px;
            background: rgba(34, 31, 31, 0.7);
            border-radius: 5px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .poll-list a:hover {
            background: rgba(229, 9, 20, 0.2);
            border-left: 3px solid var(--primary);
            transform: translateX(5px);
        }

        .poll-list .poll-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-light);
        }

        .poll-list .poll-meta {
            font-size: 0.8rem;
            opacity: 0.8;
            display: flex;
            justify-content: space-between;
            color: var(--gray);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.7);
        }

        .results-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .results-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            letter-spacing: 2px;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .results-header p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Results Navigation */
        .results-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 12px 20px;
            border-radius: 5px;
            background: rgba(34, 31, 31, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn.active, .nav-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Poll Results Container */
        .poll-results {
            margin-top: 2rem;
        }

        .poll-card {
            background: rgba(34, 31, 31, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .poll-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(229, 9, 20, 0.2);
        }

        .poll-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .poll-card-body {
            padding: 25px;
        }

        .poll-card-title {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: var(--text-light);
            font-weight: 600;
        }

        .poll-card-question {
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .poll-card-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .total-votes {
            background: rgba(229, 9, 20, 0.2);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Results Visualization */
        .results-visual {
            margin-top: 1.5rem;
        }

        .result-item {
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            padding: 15px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
        }

        .result-item:hover {
            background: rgba(229, 9, 20, 0.1);
            transform: translateX(5px);
        }

        .option-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }

        .option-text {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .option-emoji {
            font-size: 1.5rem;
        }

        .vote-count {
            font-weight: 600;
            color: var(--accent);
        }

        .progress-container {
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
            transition: width 0.8s ease-out;
            position: relative;
        }

        .progress-percent {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            text-shadow: 0 0 2px rgba(0,0,0,0.5);
        }

        /* Winner Styling */
        .winner {
            position: relative;
            border-left: 4px solid var(--accent);
        }

        .winner .progress-bar {
            background: linear-gradient(90deg, var(--accent), #f7b500);
        }

        .winner-badge {
            background: var(--accent);
            color: var(--dark);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Single Poll View */
        .single-poll {
            max-width: 800px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .back-link:hover {
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
        }

        /* No Polls Styling */
        .no-polls {
            text-align: center;
            padding: 50px;
            background: rgba(34, 31, 31, 0.5);
            border-radius: 10px;
            margin-top: 30px;
        }

        .no-polls i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .no-polls h3 {
            color: var(--accent);
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .no-polls p {
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .results-nav {
                flex-direction: column;
                align-items: center;
            }
            
            .results-header h1 {
                font-size: 2.2rem;
            }

            .poll-card-title {
                font-size: 1.3rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        /* Trophy Animation */
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .trophy-animate {
            animation: bounce 1.5s infinite;
            display: inline-block;
        }

        /* Live Pulse */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(229, 9, 20, 0); }
            100% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0); }
        }

        .live-badge {
            background: var(--primary);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-poll"></i> The Flixx Results</h2>
            </div>
            
           


 <div class="active-polls">
    <h3>Current Polls</h3>
    <?php if (!empty($active_polls)): ?>
        <ul class="poll-list">
            <?php foreach ($active_polls as $active_poll): ?>
                <li>
                    <a href="results.php?poll_id=<?php echo $active_poll['id']; ?>">
                        <div class="poll-title">
                            <?php echo !empty($active_poll['title']) ? htmlspecialchars($active_poll['title']) : 'Movie Poll #' . $active_poll['id']; ?>
                            <?php if (empty($active_poll['end_date']) || strtotime($active_poll['end_date']) > time()): ?>
                                <span class="live-badge">Live</span>
                            <?php endif; ?>
                        </div>
                        <div class="poll-meta">
                            <span><i class="fas fa-vote-yea"></i> <?php echo $active_poll['total_votes'] ?? 0; ?> votes</span>
                            <?php if (!empty($active_poll['end_date'])): ?>
                                <span><i class="far fa-clock"></i> <?php echo date('M j', strtotime($active_poll['end_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No active polls at the moment.</p>
    <?php endif; ?>
</div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="results-header fade-in">
        <h1>Poll Results Dashboard</h1>
        <p>See real-time voting statistics for all movie polls. Watch as votes come in and see which options are winning!</p>
    </div>

    <div class="results-nav fade-in">
        <a href="results.php" class="nav-btn <?php echo !isset($poll_id) ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> All Polls
        </a>
        <?php if (isset($all_polls)): ?>
            <?php foreach ($all_polls as $p): ?>
                <a href="results.php?poll_id=<?php echo $p['id']; ?>" class="nav-btn">
                    <?php echo !empty($p['title']) ? htmlspecialchars($p['title']) : 'Poll #' . $p['id']; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (isset($poll) && isset($options)): ?>
        <div class="single-poll fade-in">
            <div class="poll-card">
                <?php if (!empty($poll['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($poll['image_url']); ?>" alt="Poll image" class="poll-card-image">
                <?php else: ?>
                    <div class="poll-card-image" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-poll-h" style="font-size: 4rem; color: rgba(255,255,255,0.2);"></i>
                    </div>
                <?php endif; ?>

                <div class="poll-card-body">
                    <h2 class="poll-card-title">
                        <?php echo !empty($poll['title']) ? htmlspecialchars($poll['title']) : 'Movie Poll #' . $poll['id']; ?>
                        <?php if ($poll['is_active']): ?>
                            <span class="live-badge">Live Results</span>
                        <?php endif; ?>
                    </h2>

                    <p class="poll-card-question"><?php echo htmlspecialchars($poll['question']); ?></p>

                    <div class="poll-card-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                        <span class="total-votes">
                            <i class="fas fa-users"></i> <?php echo $poll['total_votes']; ?> total votes
                        </span>
                    </div>

                    <div class="results-visual">
                        <?php 
                        $max_votes = 0;
                        foreach ($options as $opt) {
                            if ($opt['vote_count'] > $max_votes) $max_votes = $opt['vote_count'];
                        }
                        ?>

                        <?php foreach ($options as $option): ?>
                            <div class="result-item <?php echo $option['vote_count'] == $max_votes && $max_votes > 0 ? 'winner' : ''; ?>">
                                <div class="option-header">
                                    <span class="option-text">
                                        <?php if (!empty($option['emoji'])): ?>
                                            <span class="option-emoji"><?php echo htmlspecialchars($option['emoji']); ?></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($option['text']); ?>
                                        <?php if ($option['vote_count'] == $max_votes && $max_votes > 0): ?>
                                            <span class="winner-badge">
                                                <i class="fas fa-trophy trophy-animate"></i> WINNER
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="vote-count">
                                        <?php echo $option['vote_count']; ?> votes
                                        (<?php echo $poll['total_votes'] > 0 ? round(($option['vote_count'] / $poll['total_votes']) * 100) : 0; ?>%)
                                    </span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?php echo $poll['total_votes'] > 0 ? ($option['vote_count'] / $poll['total_votes']) * 100 : 0; ?>%">
                                        <span class="progress-percent">
                                            <?php echo $poll['total_votes'] > 0 ? round(($option['vote_count'] / $poll['total_votes']) * 100) : 0; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="results.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to All Polls
                </a>
                <a href="vote.php?poll_id=<?php echo $poll['id']; ?>" class="back-link" style="background: var(--accent); color: var(--dark); margin-left: 15px;">
                    <i class="fas fa-vote-yea"></i> Cast Your Vote
                </a>
            </div>
        </div>

    <?php elseif (isset($all_polls)): ?>
        <div class="poll-results">
            <?php foreach ($all_polls as $poll): ?>
                <div class="poll-card fade-in">
                    <?php if (!empty($poll['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($poll['image_url']); ?>" alt="Poll image" class="poll-card-image">
                    <?php else: ?>
                        <div class="poll-card-image" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-poll-h" style="font-size: 4rem; color: rgba(255,255,255,0.2);"></i>
                        </div>
                    <?php endif; ?>

                    <div class="poll-card-body">
                        <h2 class="poll-card-title">
                            <?php echo !empty($poll['title']) ? htmlspecialchars($poll['title']) : 'Movie Poll #' . $poll['id']; ?>
                            <?php if ($poll['is_active']): ?>
                                <span class="live-badge">Live</span>
                            <?php endif; ?>
                        </h2>

                        <p class="poll-card-question"><?php echo htmlspecialchars($poll['question']); ?></p>

                        <div class="poll-card-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                            <span class="total-votes">
                                <i class="fas fa-users"></i> <?php echo $poll['total_votes']; ?> total votes
                            </span>
                        </div>

                        <div class="results-visual">
                            <?php 
                            $max_votes = 0;
                            foreach ($poll['options'] as $opt) {
                                if ($opt['vote_count'] > $max_votes) $max_votes = $opt['vote_count'];
                            }
                            ?>

                            <?php foreach ($poll['options'] as $option): ?>
                                <div class="result-item <?php echo $option['vote_count'] == $max_votes && $max_votes > 0 ? 'winner' : ''; ?>">
                                    <div class="option-header">
                                        <span class="option-text">
                                            <?php if (!empty($option['emoji'])): ?>
                                                <span class="option-emoji"><?php echo htmlspecialchars($option['emoji']); ?></span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($option['text']); ?>
                                            <?php if ($option['vote_count'] == $max_votes && $max_votes > 0): ?>
                                                <span class="winner-badge">
                                                    <i class="fas fa-trophy"></i> LEADING
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="vote-count">
                                            <?php echo $option['vote_count']; ?> votes
                                            (<?php echo $poll['total_votes'] > 0 ? round(($option['vote_count'] / $poll['total_votes']) * 100) : 0; ?>%)
                                        </span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo $poll['total_votes'] > 0 ? ($option['vote_count'] / $poll['total_votes']) * 100 : 0; ?>%">
                                            <span class="progress-percent">
                                                <?php echo $poll['total_votes'] > 0 ? round(($option['vote_count'] / $poll['total_votes']) * 100) : 0; ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 20px;">
                            <a href="results.php?poll_id=<?php echo $poll['id']; ?>" class="back-link" style="flex: 1; text-align: center;">
                                <i class="fas fa-chart-bar"></i> Detailed Results
                            </a>
                            <a href="vote.php?poll_id=<?php echo $poll['id']; ?>" class="back-link" style="flex: 1; text-align: center; background: var(--accent); color: var(--dark);">
                                <i class="fas fa-vote-yea"></i> Vote Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-polls fade-in">
            <i class="fas fa-poll-h"></i>
            <h3>No Polls Available</h3>
            <p>There are currently no polls with results to display.</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="vote.php" class="back-link">
                    <i class="fas fa-vote-yea"></i> Vote in Polls
                </a>
                <a href="create_poll.php" class="back-link" style="background: var(--accent); color: var(--dark);">
                    <i class="fas fa-plus-circle"></i> Create Poll
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

















    <script>
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
            
            // Add hover effects to result items
            const resultItems = document.querySelectorAll('.result-item');
            resultItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.boxShadow = '0 5px 15px rgba(229, 9, 20, 0.2)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
            
            // Add intersection observer for scroll animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.poll-card').forEach(card => {
                observer.observe(card);
            });
        });
        
        // Auto-refresh for live polls
        function checkForUpdates() {
            const liveBadges = document.querySelectorAll('.live-badge');
            if (liveBadges.length > 0) {
                // Only refresh if there are live polls on the page
                fetch(window.location.href, {
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const newDoc = parser.parseFromString(html, 'text/html');
                    
                    // Compare vote counts
                    const currentVoteElements = document.querySelectorAll('.vote-count');
                    const newVoteElements = newDoc.querySelectorAll('.vote-count');
                    
                    let needsUpdate = false;
                    
                    currentVoteElements.forEach((el, index) => {
                        if (el.textContent !== newVoteElements[index].textContent) {
                            needsUpdate = true;
                        }
                    });
                    
                    if (needsUpdate) {
                        // Add a visual notification before reloading
                        const notification = document.createElement('div');
                        notification.style.position = 'fixed';
                        notification.style.bottom = '20px';
                        notification.style.right = '20px';
                        notification.style.backgroundColor = 'var(--primary)';
                        notification.style.color = 'white';
                        notification.style.padding = '15px 25px';
                        notification.style.borderRadius = '5px';
                        notification.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
                        notification.style.zIndex = '1000';
                        notification.style.display = 'flex';
                        notification.style.alignItems = 'center';
                        notification.style.gap = '10px';
                        notification.innerHTML = `
                            <i class="fas fa-sync-alt fa-spin"></i>
                            <span>New votes detected! Updating results...</span>
                        `;
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            }
            
            // Check every 10 seconds
            setTimeout(checkForUpdates, 10000);
        }
        
        // Start the update checker
        setTimeout(checkForUpdates, 10000);
        
        // Share functionality
        function sharePoll(pollId, pollTitle) {
            const safeTitle = pollTitle || `Movie Poll #${pollId}`;
            const pollUrl = `${window.location.origin}/results.php?poll_id=${pollId}`;
            const shareText = `Check out the results for "${safeTitle}" movie poll!`;
            
            if (navigator.share) {
                navigator.share({ 
                    title: safeTitle,
                    text: shareText,
                    url: pollUrl
                }).catch(err => {
                    console.log('Error sharing:', err);
                    fallbackShare(pollUrl, shareText);
                });
            } else {
                fallbackShare(pollUrl, shareText);
            }
        }
        
        function fallbackShare(url, text) {
            const shareDialog = document.createElement('div');
            shareDialog.style.position = 'fixed';
            shareDialog.style.top = '0';
            shareDialog.style.left = '0';
            shareDialog.style.width = '100%';
            shareDialog.style.height = '100%';
            shareDialog.style.backgroundColor = 'rgba(0,0,0,0.9)';
            shareDialog.style.display = 'flex';
            shareDialog.style.flexDirection = 'column';
            shareDialog.style.alignItems = 'center';
            shareDialog.style.justifyContent = 'center';
            shareDialog.style.zIndex = '1000';
            
            shareDialog.innerHTML = `
                <div style="background: #221f1f; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; border: 1px solid var(--primary);">
                    <h3 style="color: var(--accent); font-family: 'Bebas Neue', sans-serif; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fas fa-share-alt"></i> Share These Results
                    </h3>
                    <div style="margin-bottom: 20px;">
                        <input type="text" id="shareUrl" value="${url}" readonly style="width: 100%; padding: 12px; background: #343a40; border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 5px; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                        <a href="https://www.facebook.com
                <?php include 'footer.php'; ?>