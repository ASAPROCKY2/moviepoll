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

    // Fetch all polls (active and inactive)
    $stmt = $conn->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) as total_votes,
               (CASE 
                   WHEN p.end_date IS NULL THEN 1
                   WHEN p.end_date > NOW() THEN 1
                   ELSE 0
                END) as is_active
        FROM polls p
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $all_polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active polls for sidebar
    $stmt = $conn->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) as total_votes
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
    <title>Available Polls | The Flixx</title>
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

        .polls-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .polls-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            letter-spacing: 2px;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .polls-header p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        .polls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .poll-card {
            background: rgba(34, 31, 31, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            padding: 20px;
        }

        .poll-card-title {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: var(--text-light);
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .poll-card-question {
            color: var(--gray);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .poll-card-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .poll-card-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #4cc9f0;
        }

        .status-ended {
            background: rgba(220, 53, 69, 0.2);
            color: #f72585;
        }

        .poll-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-vote {
            flex: 1;
            padding: 10px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn-vote:hover {
            background: var(--secondary);
        }

        .btn-share {
            flex: 1;
            padding: 10px;
            background: rgba(34, 31, 31, 0.8);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn-share:hover {
            background: rgba(229, 9, 20, 0.2);
            border-color: var(--primary);
        }

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
        }

        .no-polls p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .btn-create {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-create:hover {
            background: var(--secondary);
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
            .polls-grid {
                grid-template-columns: 1fr;
            }
            
            .polls-header h1 {
                font-size: 2.2rem;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-film"></i> The Flixx Polls</h2>
            </div>
            
            <div class="active-polls">
                <h3>Current Polls</h3>
                <?php if (!empty($active_polls)): ?>
                    <ul class="poll-list">
                        <?php foreach ($active_polls as $active_poll): ?>
                            <li>
                                <a href="vote.php?poll_id=<?php echo $active_poll['id']; ?>">
                                    <div class="poll-title">
                                        <?php echo !empty($active_poll['title']) ? htmlspecialchars($active_poll['title']) : 'Movie Poll #' . $active_poll['id']; ?>
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
            <div class="polls-header fade-in">
                <h1>Available Polls</h1>
                <p>Browse through all the movie polls created by our community. Vote for your favorites and share them with friends!</p>
            </div>

            <?php if (!empty($all_polls)): ?>
                <div class="polls-grid">
                    <?php foreach ($all_polls as $poll): ?>
                        <div class="poll-card fade-in">
                            <?php if (!empty($poll['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($poll['image_url']); ?>" alt="Poll image" class="poll-card-image">
                            <?php else: ?>
                                <div class="poll-card-image" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-film" style="font-size: 3rem; color: rgba(255,255,255,0.2);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="poll-card-body">
                                <h3 class="poll-card-title">
                                    <?php echo !empty($poll['title']) ? htmlspecialchars($poll['title']) : 'Movie Poll #' . $poll['id']; ?>
                                </h3>
                                
                                <?php if (!empty($poll['question'])): ?>
                                    <p class="poll-card-question"><?php echo htmlspecialchars($poll['question']); ?></p>
                                <?php endif; ?>
                                
                                <div class="poll-card-meta">
                                    <span><i class="fas fa-vote-yea"></i> <?php echo $poll['total_votes'] ?? 0; ?> votes</span>
                                    <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                                </div>
                                
                                <div class="poll-card-status <?php echo $poll['is_active'] ? 'status-active' : 'status-ended'; ?>">
                                    <?php echo $poll['is_active'] ? 'Active' : 'Ended'; ?>
                                    <?php if ($poll['is_active'] && !empty($poll['end_date'])): ?>
                                        (ends <?php echo date('M j', strtotime($poll['end_date'])); ?>)
                                    <?php endif; ?>
                                </div>
                                
                                <div class="poll-card-actions">
                                    <a href="vote.php?poll_id=<?php echo $poll['id']; ?>" class="btn-vote">
                                        <i class="fas fa-vote-yea"></i> Vote
                                    </a>
                                    <div class="poll-card-actions">
    <a href="vote.php?poll_id=<?php echo $poll['id']; ?>" class="btn-vote">
        <i class="fas fa-vote-yea"></i> Vote
    </a>
    <button class="btn-share" onclick="sharePoll(<?php echo $poll['id']; ?>, '<?php echo isset($poll['title']) ? htmlspecialchars($poll['title'], ENT_QUOTES) : 'Movie Poll #' . $poll['id']; ?>')">
        <i class="fas fa-share-alt"></i> Share
    </button>
</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-polls fade-in">
                    <i class="fas fa-film"></i>
                    <h3>No Polls Available</h3>
                    <p>There are currently no polls to display. Be the first to create one!</p>
                    <a href="create_poll.php" class="btn-create">
                        <i class="fas fa-plus-circle"></i> Create Poll
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
// Share poll function
function sharePoll(pollId, pollTitle) {
    // Ensure pollTitle is never null or undefined
    const safeTitle = pollTitle || `Movie Poll #${pollId}`;
    const pollUrl = `${window.location.origin}/vote.php?poll_id=${pollId}`;
    const shareText = `Vote on this movie poll: "${safeTitle}"`;
    
    if (navigator.share) {
        // Use native share API if available
        navigator.share({
            title: safeTitle,
            text: shareText,
            url: pollUrl
        }).catch(err => {
            console.log('Error sharing:', err);
            fallbackShare(pollUrl, shareText);
        });
    } else {
        // Fallback for browsers without share API
        fallbackShare(pollUrl, shareText);
    }
}
        
        function fallbackShare(url, text) {
            // Create a temporary share dialog
            const shareDialog = document.createElement('div');
            shareDialog.style.position = 'fixed';
            shareDialog.style.top = '0';
            shareDialog.style.left = '0';
            shareDialog.style.width = '100%';
            shareDialog.style.height = '100%';
            shareDialog.style.backgroundColor = 'rgba(0,0,0,0.8)';
            shareDialog.style.display = 'flex';
            shareDialog.style.flexDirection = 'column';
            shareDialog.style.alignItems = 'center';
            shareDialog.style.justifyContent = 'center';
            shareDialog.style.zIndex = '1000';
            
            shareDialog.innerHTML = `
                <div style="background: #221f1f; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
                    <h3 style="color: #f5c518; font-family: 'Bebas Neue', sans-serif; margin-bottom: 20px;">Share This Poll</h3>
                    <div style="margin-bottom: 20px;">
                        <input type="text" id="shareUrl" value="${url}" readonly style="width: 100%; padding: 10px; background: #343a40; border: none; color: white; border-radius: 5px;">
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" 
                           target="_blank" style="flex: 1; padding: 10px; background: #3b5998; color: white; text-align: center; border-radius: 5px; text-decoration: none;">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(text + ' ' + url)}" 
                           target="_blank" style="flex: 1; padding: 10px; background: #1da1f2; color: white; text-align: center; border-radius: 5px; text-decoration: none;">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="copyToClipboard('${url}')" style="flex: 1; padding: 10px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-copy"></i> Copy Link
                        </button>
                        <button onclick="document.body.removeChild(this.parentNode.parentNode.parentNode)" style="flex: 1; padding: 10px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(shareDialog);
        }
        
        function copyToClipboard(text) {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            
            alert('Link copied to clipboard!');
        }
        
        // Add animation to cards as they come into view
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>