<?php
require_once 'config.php';
require_once 'functions.php';

session_start();

// Redirect if not logged in
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

    // Fetch user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user not found, log them out
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Initialize default values
    $vote_history = [];
    $total_votes = 0;
    $polls_won = 0;
    $total_hours = 0;
    $accuracy = 0;
    $favorite_genre = 'Unknown';

    // Fetch user's voting history (only if votes table exists)
    try {
        $votes_stmt = $conn->prepare("
            SELECT p.title as question, v.voted_at as vote_date, p.id as poll_id
            FROM votes v
            JOIN polls p ON v.poll_id = p.id
            WHERE v.user_id = :user_id
            ORDER BY v.voted_at DESC
            LIMIT 5
        ");
        $votes_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $votes_stmt->execute();
        $vote_history = $votes_stmt->fetchAll();
        
        // Count total votes
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total_votes FROM votes WHERE user_id = :user_id");
        $count_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $count_stmt->execute();
        $total_votes = $count_stmt->fetch(PDO::FETCH_ASSOC)['total_votes'] ?? 0;
    } catch (PDOException $e) {
        error_log("Votes query error: " . $e->getMessage());
    }

    // Fetch total polls won (only if polls table has winner_id column)
    try {
        $polls_won_stmt = $conn->prepare("SELECT COUNT(*) as polls_won FROM polls WHERE winner_id = :user_id");
        $polls_won_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $polls_won_stmt->execute();
        $polls_won = $polls_won_stmt->fetch(PDO::FETCH_ASSOC)['polls_won'] ?? 0;
    } catch (PDOException $e) {
        error_log("Polls won query error: " . $e->getMessage());
    }

    // Fetch total hours engaged (only if user_activity table exists)
    try {
        $hours_stmt = $conn->prepare("SELECT SUM(hours_spent) as total_hours FROM user_activity WHERE user_id = :user_id");
        $hours_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $hours_stmt->execute();
        $total_hours = $hours_stmt->fetch(PDO::FETCH_ASSOC)['total_hours'] ?? 0;
    } catch (PDOException $e) {
        error_log("Hours engaged query error: " . $e->getMessage());
    }

    // Fetch accuracy (only if user_stats table exists)
    try {
        $accuracy_stmt = $conn->prepare("SELECT (correct_answers / total_answers) * 100 as accuracy FROM user_stats WHERE user_id = :user_id");
        $accuracy_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $accuracy_stmt->execute();
        $accuracy_result = $accuracy_stmt->fetch(PDO::FETCH_ASSOC);
        $accuracy = $accuracy_result ? round($accuracy_result['accuracy'], 0) : 0;
    } catch (PDOException $e) {
        error_log("Accuracy query error: " . $e->getMessage());
    }

    // Fetch favorite movie genre (only if categories table exists)
    try {
        $genre_stmt = $conn->prepare("
            SELECT c.name as genre, COUNT(*) as count 
            FROM votes v
            JOIN polls p ON v.poll_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE v.user_id = :user_id
            GROUP BY c.name
            ORDER BY count DESC
            LIMIT 1
        ");
        $genre_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $genre_stmt->execute();
        $genre_result = $genre_stmt->fetch(PDO::FETCH_ASSOC);
        $favorite_genre = $genre_result['genre'] ?? 'Unknown';
    } catch (PDOException $e) {
        error_log("Favorite genre query error: " . $e->getMessage());
    }

} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database error. Please try again later.");
}

 ?>












<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | The Flixx</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #6C5CE7;
            --secondary: #A29BFE;
            --accent: #FD79A8;
            --light: #f8f9fa;
            --dark: #2D3436;
            --success: #00B894;
            --info: #0984E3;
            --warning: #FDCB6E;
            --danger: #D63031;
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
            --card-shadow: 0 10px 30px -15px rgba(0,0,0,0.2);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6fa;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Floating particles background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(108, 92, 231, 0.3);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Main container */
        .profile-container {
            max-width: 1300px;
            margin: 80px auto 40px;
            padding: 20px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            position: relative;
        }

        /* Profile sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            height: fit-content;
            position: sticky;
            top: 100px;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .profile-sidebar:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px -15px rgba(108, 92, 231, 0.4);
        }

        .profile-avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: var(--transition);
        }

        .profile-avatar-container:hover .profile-avatar {
            transform: scale(1.05);
        }

        .avatar-edit-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
        }

        .avatar-edit-overlay i {
            color: white;
            font-size: 1.5rem;
        }

        .profile-avatar-container:hover .avatar-edit-overlay {
            opacity: 1;
        }

        .profile-name {
            text-align: center;
            font-size: 1.6rem;
            margin-bottom: 5px;
            color: var(--primary);
            font-weight: 600;
        }

        .profile-email {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .profile-bio {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--light);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .profile-btn i {
            font-size: 1.1rem;
        }

        .edit-profile-btn {
            background: var(--primary);
            color: white;
        }

        .edit-profile-btn:hover {
            background: #5a4bcf;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
        }

        .logout-btn {
            background: #f8f9fa;
            color: var(--dark);
            border: 1px solid #eee;
        }

        .logout-btn:hover {
            background: #f1f3f5;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        /* Profile main content */
        .profile-main {
            display: grid;
            gap: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px -15px rgba(108, 92, 231, 0.3);
        }

        .section-title {
            font-size: 1.4rem;
            margin-bottom: 25px;
            color: var(--primary);
            position: relative;
            padding-bottom: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 1.6rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient);
            border-radius: 3px;
        }

        /* Vote history */
        .vote-history {
            display: grid;
            gap: 15px;
        }

        .vote-item {
            padding: 18px;
            border-radius: 12px;
            background: var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .vote-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            background: white;
        }

        .vote-question {
            font-weight: 500;
            flex: 1;
        }

        .vote-question a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .vote-question a:hover {
            color: var(--primary);
        }

        .vote-date {
            color: #666;
            font-size: 0.85rem;
            min-width: 150px;
            text-align: right;
        }

        .vote-item i {
            color: var(--primary);
            margin-left: 15px;
            font-size: 1.2rem;
        }

        /* Stats card */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .stat-card-label {
            font-size: 0.85rem;
            color: #666;
        }

        /* Favorite genre card */
        .favorite-genre {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(108, 92, 231, 0.1);
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }

        .favorite-genre-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .favorite-genre-text h4 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .favorite-genre-text p {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state p {
            font-size: 1rem;
        }

        /* Settings section */
        .settings-section {
            display: grid;
            gap: 20px;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-item h4 {
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark);
        }

        .setting-item p {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        .setting-action {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-sm {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #5a4bcf;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: var(--dark);
        }

        .btn-outline:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        /* Switch toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal h2 {
            margin-bottom: 25px;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
        }

        .save-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            font-size: 1rem;
        }

        .save-btn:hover {
            background: #5a4bcf;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #121212;
            color: #f5f5f5;
        }

        body.dark-mode .profile-sidebar,
        body.dark-mode .profile-card,
        body.dark-mode .stat-card,
        body.dark-mode .vote-item,
        body.dark-mode .modal-content {
            background-color: #2d2d2d;
            color: #f5f5f5;
            border-color: #3d3d3d;
        }

        body.dark-mode .profile-email,
        body.dark-mode .stat-label,
        body.dark-mode .vote-date,
        body.dark-mode .empty-state,
        body.dark-mode .form-group label,
        body.dark-mode .favorite-genre-text h4 {
            color: #aaa;
        }

        body.dark-mode .profile-sidebar,
        body.dark-mode .profile-card,
        body.dark-mode .stat-card {
            box-shadow: 0 10px 30px -15px rgba(0,0,0,0.3);
        }

        body.dark-mode .vote-item {
            background: #3d3d3d;
        }

        body.dark-mode .vote-item:hover {
            background: #4d4d4d;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea,
        body.dark-mode .form-group select {
            background: #3d3d3d;
            border-color: #4d4d4d;
            color: #f5f5f5;
        }

        body.dark-mode .logout-btn {
            background: #3d3d3d;
            border-color: #4d4d4d;
            color: #f5f5f5;
        }

        body.dark-mode .logout-btn:hover {
            background: #4d4d4d;
        }

        body.dark-mode .btn-outline {
            border-color: #4d4d4d;
            color: #f5f5f5;
        }

        body.dark-mode .btn-outline:hover {
            background: #3d3d3d;
        }

        body.dark-mode .setting-item {
            border-bottom-color: #3d3d3d;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                margin-top: 60px;
                padding: 15px;
            }
            
            .profile-sidebar, .profile-card {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .vote-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .vote-date {
                text-align: left;
                min-width: auto;
            }
        }

        /* Animation classes */
        .animate-pop {
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(108, 92, 231, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(108, 92, 231, 0); }
            100% { box-shadow: 0 0 0 0 rgba(108, 92, 231, 0); }
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast i {
            font-size: 1.2rem;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <!-- Floating particles background -->
    <div class="particles" id="particles"></div>
    
    <!-- Include your navbar here -->
    
    <div class="profile-container animate__animated animate__fadeIn">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar animate-pop">
            <div class="profile-avatar-container">
                <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name='.urlencode($user['username']).'&background=6C5CE7&color=fff'; ?>" 
                     alt="Profile Picture" class="profile-avatar" id="profileAvatar">
                <div class="avatar-edit-overlay" id="avatarEditBtn">
                    <i class='bx bx-camera'></i>
                </div>
            </div>
            
            <h2 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <p class="profile-bio"><?php echo htmlspecialchars($user['bio'] ?? 'Movie enthusiast and poll participant'); ?></p>
            
            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-value" id="totalVotes"><?php echo $total_votes; ?></div>
                    <div class="stat-label">Total Votes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="memberSince">
                        <?php 
                            $date = new DateTime($user['created_at']);
                            echo $date->format('M Y'); 
                        ?>
                    </div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>

            <?php if ($favorite_genre !== 'Unknown'): ?>
                <div class="favorite-genre">
                    <div class="favorite-genre-icon">
                        <i class='bx bx-movie'></i>
                    </div>
                    <div class="favorite-genre-text">
                        <h4>Favorite Genre</h4>
                        <p><?php echo htmlspecialchars($favorite_genre); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="profile-actions">
                <button class="profile-btn edit-profile-btn" id="editProfileBtn">
                    <i class='bx bx-edit'></i> Edit Profile
                </button>
                <button class="profile-btn logout-btn" id="logoutBtn">
                    <i class='bx bx-log-out'></i> Logout
                </button>
            </div>
        </div>
        
        <!-- Profile Main Content -->
        <div class="profile-main">
            <!-- Stats Card -->
            <div class="profile-card">
                <h3 class="section-title"><i class='bx bx-stats'></i> Your Activity</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: var(--primary);">
                            <i class='bx bx-check-circle'></i>
                        </div>
                        <div class="stat-card-value"><?php echo $total_votes; ?></div>
                        <div class="stat-card-label">Total Votes</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: var(--success);">
                            <i class='bx bx-trophy'></i>
                        </div>
                        <div class="stat-card-value"><?php echo $polls_won; ?></div>
                        <div class="stat-card-label">Polls Won</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: var(--info);">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <div class="stat-card-value"><?php echo $total_hours; ?></div>
                        <div class="stat-card-label">Hours Engaged</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: var(--accent);">
                            <i class='bx bx-star'></i>
                        </div>
                        <div class="stat-card-value"><?php echo $accuracy; ?>%</div>
                        <div class="stat-card-label">Accuracy</div>
                    </div>
                </div>
            </div>
            
            <!-- Voting History Card -->
            <div class="profile-card">
                <h3 class="section-title"><i class='bx bx-history'></i> Recent Voting Activity</h3>
                
                <div class="vote-history">
                    <?php if (!empty($vote_history)): ?>
                        <?php foreach ($vote_history as $vote): ?>
                            <div class="vote-item animate__animated animate__fadeIn">
                                <span class="vote-question">
                                    <a href="poll.php?id=<?php echo $vote['poll_id']; ?>">
                                        <?php echo htmlspecialchars($vote['question']); ?>
                                    </a>
                                </span>
                                <span class="vote-date">
                                    <?php 
                                        $voteDate = new DateTime($vote['vote_date']);
                                        echo $voteDate->format('M j, Y g:i A');
                                    ?>
                                </span>
                                <i class='bx bx-right-arrow-alt'></i>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-movie'></i>
                            <p>No voting history yet. Participate in polls to see your activity here!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Settings Card -->
            <div class="profile-card">
                <h3 class="section-title"><i class='bx bx-cog'></i> Account Settings</h3>
                
                <div class="settings-section">
                    <div class="setting-item">
                        <div>
                            <h4>Change Password</h4>
                            <p>Update your account password</p>
                        </div>
                        <div class="setting-action">
                            <button class="btn-sm btn-primary" id="changePasswordBtn">
                                <i class='bx bx-lock'></i> Change
                            </button>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div>
                            <h4>Email Notifications</h4>
                            <p>Receive email updates about new polls</p>
                        </div>
                        <div class="setting-action">
                            <label class="switch">
                                <input type="checkbox" id="emailNotifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div>
                            <h4>Dark Mode</h4>
                            <p>Toggle between light and dark theme</p>
                        </div>
                        <div class="setting-action">
                            <label class="switch">
                                <input type="checkbox" id="darkModeToggle">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div>
                            <h4>Delete Account</h4>
                            <p>Permanently delete your account and all data</p>
                        </div>
                        <div class="setting-action">
                            <button class="btn-sm btn-outline" id="deleteAccountBtn">
                                <i class='bx bx-trash'></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Edit Profile</h2>
            <form id="profileForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="avatar">Profile Picture</label>
                    <input type="file" id="avatarUpload" name="avatar" accept="image/*">
                    <small>Current image:</small>
                    <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name='.urlencode($user['username']).'&background=6C5CE7&color=fff'; ?>" 
                         class="avatar-preview" id="avatarPreview">
                </div>
                <button type="submit" class="save-btn" id="saveProfileBtn">
                    <span id="saveProfileText">Save Changes</span>
                    <span id="saveProfileSpinner" class="spinner" style="display: none;"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Change Password</h2>
            <form id="passwordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <small class="password-strength" style="display: none;"></small>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                <button type="submit" class="save-btn" id="savePasswordBtn">
                    <span id="savePasswordText">Update Password</span>
                    <span id="savePasswordSpinner" class="spinner" style="display: none;"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteAccountModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Delete Your Account</h2>
            <div class="form-group">
                <p>Are you sure you want to delete your account? This action cannot be undone. All your data will be permanently removed.</p>
                <p>To confirm, please enter your password:</p>
                <input type="password" id="deleteAccountPassword" placeholder="Enter your password" style="margin-top: 15px;">
            </div>
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button class="btn-sm btn-outline" style="flex: 1;" id="cancelDeleteBtn">Cancel</button>
                <button class="btn-sm" style="flex: 1; background: var(--danger); color: white;" id="confirmDeleteBtn">Delete Account</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class='bx bx-check-circle'></i>
        <span id="toastMessage">Changes saved successfully!</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/dist/index.min.js"></script>
    <canvas id="confetti-canvas" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; pointer-events: none;"></canvas>

    <script>
        // DOM Elements
        const editProfileBtn = document.getElementById('editProfileBtn');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const deleteAccountModal = document.getElementById('deleteAccountModal');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const profileForm = document.getElementById('profileForm');
        const passwordForm = document.getElementById('passwordForm');
        const profileAvatar = document.getElementById('profileAvatar');
        const avatarEditBtn = document.getElementById('avatarEditBtn');
        const avatarUpload = document.getElementById('avatarUpload');
        const darkModeToggle = document.getElementById('darkModeToggle');
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        // Floating particles effect
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size between 5 and 15px
                const size = Math.random() * 10 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.5 + 0.1;
                
                // Random animation
                const duration = Math.random() * 20 + 10;
                particle.style.animation = `float ${duration}s linear infinite`;
                
                // Add to container
                particlesContainer.appendChild(particle);
            }
        }

        // CSS for floating animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0% { transform: translate(0, 0) rotate(0deg); }
                25% { transform: translate(10px, 10px) rotate(5deg); }
                50% { transform: translate(20px, 0) rotate(0deg); }
                75% { transform: translate(10px, -10px) rotate(-5deg); }
                100% { transform: translate(0, 0) rotate(0deg); }
            }
        `;
        document.head.appendChild(style);

        // Create particles on load
        window.addEventListener('load', createParticles);

        // Open Edit Profile Modal
        editProfileBtn.addEventListener('click', () => {
            editProfileModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        // Open Change Password Modal
        changePasswordBtn.addEventListener('click', () => {
            changePasswordModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        // Avatar edit button
        avatarEditBtn.addEventListener('click', () => {
            editProfileModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        // Delete account button
        deleteAccountBtn.addEventListener('click', () => {
            deleteAccountModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        // Cancel delete
        cancelDeleteBtn.addEventListener('click', () => {
            deleteAccountModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        // Logout button
        logoutBtn.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        // Close Modals
        closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                editProfileModal.style.display = 'none';
                changePasswordModal.style.display = 'none';
                deleteAccountModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === editProfileModal) {
                editProfileModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (e.target === changePasswordModal) {
                changePasswordModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (e.target === deleteAccountModal) {
                deleteAccountModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Show toast notification
        function showToast(message, type = 'success') {
            toastMessage.textContent = message;
            toast.classList.add('show');
            
            // Change color based on type
            if (type === 'error') {
                toast.style.background = 'var(--danger)';
            } else if (type === 'warning') {
                toast.style.background = 'var(--warning)';
            } else {
                toast.style.background = 'var(--success)';
            }
            
            // Hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }





      
           // Handle Profile Form Submission with file upload
profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const saveProfileText = document.getElementById('saveProfileText');
    const saveProfileSpinner = document.getElementById('saveProfileSpinner');
    
    // Show loading state
    saveProfileText.textContent = 'Saving...';
    saveProfileSpinner.style.display = 'inline-block';
    saveProfileBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('username', document.getElementById('username').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('bio', document.getElementById('bio').value);
    
    const avatarFile = document.getElementById('avatarUpload').files[0];
    if (avatarFile) {
        formData.append('avatar', avatarFile);
    }

    try {
        const response = await fetch('update_profile.php', {
            method: 'POST',
            body: formData,
            // Don't set Content-Type header when using FormData
            // The browser will set it automatically with the correct boundary
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            showToast('Profile updated successfully!');
            // Update the avatar preview if new avatar was uploaded
            if (result.avatar_url) {
                document.getElementById('avatarPreview').src = result.avatar_url;
                document.getElementById('profileAvatar').src = result.avatar_url;
            }
            // Update the username in the sidebar
            document.querySelector('.profile-name').textContent = result.username;
            // Close the modal
            editProfileModal.style.display = 'none';
        } else {
            showToast(result.message || 'Error updating profile', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred while updating your profile', 'error');
    } finally {
        // Reset button state
        saveProfileText.textContent = 'Save Changes';
        saveProfileSpinner.style.display = 'none';
        saveProfileBtn.disabled = false;
        document.body.style.overflow = 'auto';
    }
});














        // Password strength indicator
        const newPasswordInput = document.getElementById('newPassword');
        const passwordStrength = document.querySelector('.password-strength');
        
        newPasswordInput.addEventListener('input', () => {
            const password = newPasswordInput.value;
            if (password.length === 0) {
                passwordStrength.style.display = 'none';
                return;
            }
            
            passwordStrength.style.display = 'block';
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            // Update display
            let message = '';
            let color = '';
            
            switch(strength) {
                case 0:
                case 1:
                    message = 'Very Weak';
                    color = 'var(--danger)';
                    break;
                case 2:
                    message = 'Weak';
                    color = '#ff6b6b';
                    break;
                case 3:
                    message = 'Moderate';
                    color = 'var(--warning)';
                    break;
                case 4:
                    message = 'Strong';
                    color = '#51cf66';
                    break;
                case 5:
                    message = 'Very Strong';
                    color = 'var(--success)';
                    break;
            }
            
            passwordStrength.textContent = message;
            passwordStrength.style.color = color;
        });

        // Handle Password Form Submission
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const savePasswordBtn = document.getElementById('savePasswordBtn');
            const savePasswordText = document.getElementById('savePasswordText');
            const savePasswordSpinner = document.getElementById('savePasswordSpinner');
            
            // Show loading state
            savePasswordText.textContent = 'Updating...';
            savePasswordSpinner.style.display = 'inline-block';
            savePasswordBtn.disabled = true;
            
            const formData = {
                currentPassword: document.getElementById('currentPassword').value,
                newPassword: document.getElementById('newPassword').value,
                confirmPassword: document.getElementById('confirmPassword').value
            };

            if (formData.newPassword !== formData.confirmPassword) {
                showToast('New passwords do not match!', 'error');
                savePasswordText.textContent = 'Update Password';
                savePasswordSpinner.style.display = 'none';
                savePasswordBtn.disabled = false;
                return;
            }

            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Password changed successfully!');
                    changePasswordModal.style.display = 'none';
                    passwordForm.reset();
                    passwordStrength.style.display = 'none';
                } else {
                    showToast(result.message || 'Error changing password', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while changing your password', 'error');
            } finally {
                savePasswordText.textContent = 'Update Password';
                savePasswordSpinner.style.display = 'none';
                savePasswordBtn.disabled = false;
                document.body.style.overflow = 'auto';
            }
        });

        // Handle account deletion
        confirmDeleteBtn.addEventListener('click', async () => {
            const password = document.getElementById('deleteAccountPassword').value;
            
            if (!password) {
                showToast('Please enter your password', 'error');
                return;
            }
            
            confirmDeleteBtn.textContent = 'Deleting...';
            confirmDeleteBtn.disabled = true;
            
            try {
                const response = await fetch('delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password })
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Account deleted successfully. Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 2000);
                } else {
                    showToast(result.message || 'Error deleting account', 'error');
                    confirmDeleteBtn.textContent = 'Delete Account';
                    confirmDeleteBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while deleting your account', 'error');
                confirmDeleteBtn.textContent = 'Delete Account';
                confirmDeleteBtn.disabled = false;
            }
        });

        // Avatar upload preview
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').src = event.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Dark mode toggle
        darkModeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }

        // Add animation to vote history items
        const voteItems = document.querySelectorAll('.vote-item');
        voteItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });

        // Add hover effect to stat cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px)';
                card.style.boxShadow = '0 15px 30px -10px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.05)';
            });
        });

        // Confetti effect for fun
        function showConfetti() {
            const confettiSettings = {
                target: 'confetti-canvas',
                max: 150,
                size: 1.5,
                animate: true,
                props: ['circle', 'square', 'triangle', 'line'],
                colors: [[108, 92, 231], [162, 155, 254], [253, 121, 168], [0, 184, 148]],
                clock: 25,
                rotate: true,
                start_from_edge: true,
                respawn: true
            };
            
            const confetti = new ConfettiGenerator(confettiSettings);
            confetti.render();
            
            setTimeout(() => {
                confetti.clear();
            }, 3000);
        }

        // Add confetti to some actions (just for fun)
        editProfileBtn.addEventListener('click', showConfetti);
    </script>
</body>
</html>