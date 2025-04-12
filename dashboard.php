<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'movie-poll-db';
$db_username = 'root';
$db_password = '';

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Load helper functions (AFTER connection is ready)
require_once 'functions.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';

// Fetch notifications only if logged in
$notifications = [];
if ($is_logged_in) {
    $notifications = getUserNotifications($conn, $_SESSION['user_id']);
}

// Check if admin is logged in
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// Handle logout if requested
if (isset($_GET['logout'])) {
    if ($is_admin) {
        // Admin logout
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        header("Location: admin_login.php");
        exit();
    } else {
        // User logout
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

// Fetch all polls with correct total votes
try {
    $stmt = $conn->query("
        SELECT p.id, p.question, p.image, p.end_date, 
               (SELECT COUNT(*) FROM votes WHERE votes.poll_id = p.id) AS total_votes
        FROM polls p
        ORDER BY p.end_date DESC
    ");
    $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Flixx - Movie Poll Platform</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        




 /* Modern Color Palette */
        :root {
            --primary: #4a00e0;
            --secondary: #8e2de2;
            --accent: #ff6b6b;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
            --text-gradient: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            transition: all 0.3s ease;
            overflow-x: hidden;
        }



.poll-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

@media (max-width: 768px) {
    .poll-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}



.loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top: 4px solid var(--primary);
    animation: spin 1s linear infinite;
    margin: 20px auto;
    grid-column: 1 / -1;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}








/* Enhanced Movie Card Styles */
.movie-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

body.dark-mode .movie-card {
    background: #2d2d2d;
}

.movie-card-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.movie-poster-container {
    position: relative;
    overflow: hidden;
    aspect-ratio: 2/3;
}

.movie-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.movie-card:hover .movie-overlay {
    opacity: 1;
}

.quick-vote-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.quick-vote-btn.voted {
    background: var(--success);
}

.quick-vote-btn:hover {
    transform: scale(1.05);
}

.movie-info {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.movie-title {
    font-size: 1rem;
    margin-bottom: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.movie-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 10px;
}

body.dark-mode .movie-meta {
    color: #aaa;
}

.user-rating {
    margin: 10px 0;
}

.star-rating {
    color: var(--warning);
    font-size: 1.2rem;
}

.star-rating i {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.star-rating i:hover {
    transform: scale(1.2);
}

.view-details-btn {
    margin-top: auto;
    background: rgba(74, 0, 224, 0.1);
    color: var(--primary);
    border: none;
    padding: 8px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-details-btn:hover {
    background: rgba(74, 0, 224, 0.2);
}

/* Movie Detail Modal Styles */
.movie-detail-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1003;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

.movie-detail-content {
    background: white;
    border-radius: 15px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

body.dark-mode .movie-detail-content {
    background: #2d2d2d;
    color: white;
}

.close-detail-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 1.5rem;
    cursor: pointer;
    z-index: 1;
}

.movie-detail-header {
    position: relative;
}

.movie-backdrop {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 15px 15px 0 0;
}

.movie-header-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
}

.movie-header-info h2 {
    margin: 0;
    font-size: 1.8rem;
}

.movie-detail-body {
    padding: 20px;
}

.movie-overview {
    margin-bottom: 20px;
}

.movie-overview h3 {
    margin-top: 0;
}

.movie-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.stat-box {
    background: rgba(74, 0, 224, 0.1);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

body.dark-mode .stat-box {
    background: rgba(255, 255, 255, 0.1);
}

.stat-box i {
    font-size: 1.5rem;
    color: var(--primary);
    margin-bottom: 5px;
}

.stat-box span {
    display: block;
    font-size: 1.2rem;
    font-weight: bold;
}

.stat-box small {
    font-size: 0.8rem;
    color: #666;
}

body.dark-mode .stat-box small {
    color: #aaa;
}

.detail-vote-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}

.detail-vote-btn.voted {
    background: var(--success);
}

.detail-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.create-poll-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

body.dark-mode .create-poll-section {
    border-top-color: #444;
}

.create-poll-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.create-poll-btn:hover {
    background: var(--secondary);
}








/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1002;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.8);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    width: 80%;
    max-width: 900px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}

body.dark-mode .modal-content {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

.close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: var(--accent);
}


.movies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.movie-card {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.movie-card:hover {
    transform: translateY(-5px);
}

.movie-poster {
    width: 100%;
    border-radius: 8px;
    aspect-ratio: 2/3;
    object-fit: cover;
}

.movie-title {
    margin-top: 8px;
    font-size: 0.9rem;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}





        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #121212;
            color: #f5f5f5;
        }
        
        body.dark-mode .navbar {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        body.dark-mode .main-content {
            background-color: #121212;
            color: #f5f5f5;
        }
        
        body.dark-mode .poll-container {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        
        body.dark-mode .poll-card {
            background-color: #2d2d2d;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        body.dark-mode .sidebar {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }
        
        body.dark-mode #chat-container {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        
        body.dark-mode #user-input {
            background-color: #2d2d2d;
            color: #f5f5f5;
            border-color: #444;
        }
        
        body.dark-mode .footer {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }
        
        body.dark-mode .search-box input {
            background-color: #2d2d2d;
            color: #f5f5f5;
            border-color: #444;
        }

        /* Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

        body.dark-mode ::-webkit-scrollbar-track {
            background: #2d2d2d;
        }

        body.dark-mode ::-webkit-scrollbar-thumb {
            background: var(--secondary);
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 10px 5%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .logo h4 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(to right, #fff, #ddd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        .navbar .logo i {
            font-size: 2rem;
            color: white;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        .navbar-nav li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-nav li a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .navbar-nav li a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Search Box */
        .search-box {
            position: relative;
            margin-left: auto;
            margin-right: 20px;
        }

        .search-box input {
            padding: 10px 15px 10px 40px;
            border-radius: 30px;
            border: 1px solid #ddd;
            width: 250px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.2);
            width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        /* App Container */
        .app-container {
            display: flex;
            min-height: 100vh;
            padding-top: 70px;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            min-height: calc(100vh - 70px);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px 20px;
            position: fixed;
            top: 70px;
            left: 0;
            z-index: 900;
            transition: all 0.3s ease;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 80px;
            padding: 30px 10px;
        }

        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-menu li a span {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu li a {
            justify-content: center;
            padding: 15px 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed .toggle-sidebar {
            transform: rotate(180deg);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu li a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar-menu li a.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }

        .sidebar-menu li a i {
            font-size: 1.2rem;
            min-width: 24px;
            text-align: center;
        }

        /* User Profile in Sidebar */
        .sidebar-profile {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .sidebar-profile-info {
            flex: 1;
        }

        .sidebar-profile-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .sidebar-profile-info p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar.collapsed .sidebar-profile {
            justify-content: center;
        }

        .sidebar.collapsed .sidebar-profile-info {
            display: none;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 280px;
            background-color: white;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 90vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://source.unsplash.com/random/1920x1080/?movie,theater') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 1s ease;
        }

        .hero-content h1 {
            font-size: 4rem;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, #ddd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 500;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: none;
            cursor: pointer;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .cta-button.secondary {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .cta-button.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Polls Section */
        .section-title {
            font-size: 2rem;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .poll-container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .poll-container:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .poll-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .poll-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .poll-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .poll-image-container {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .poll-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .poll-card:hover .poll-image {
            transform: scale(1.1);
        }

        .poll-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
        }

        .poll-info {
            padding: 20px;
        }

        .poll-info h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .poll-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }

        .poll-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vote-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .vote-button:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
        }

        /* Featured Poll */
        .featured-poll {
            grid-column: span 2;
            display: flex;
            background: linear-gradient(135deg, rgba(74, 0, 224, 0.1), rgba(142, 45, 226, 0.1));
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .featured-poll-image {
            flex: 1;
            min-height: 300px;
        }

        .featured-poll-info {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .featured-poll-info h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .featured-poll-info p {
            margin-bottom: 20px;
            color: #555;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1) rotate(30deg);
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            margin-left: 20px;
            cursor: pointer;
        }

        .notification-bell i {
            font-size: 1.5rem;
            color: white;
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Chatbox Styles */
        #chat-icon {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        #chat-icon:hover {
            transform: scale(1.1);
        }

        #chat-container {
            display: none;
            position: fixed;
            bottom: 100px;
            left: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            flex-direction: column;
            z-index: 1001;
            overflow: hidden;
            transform-origin: bottom left;
            animation: scaleIn 0.3s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        #chat-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #chat-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #chat-header .close-chat {
            cursor: pointer;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        #chat-header .close-chat:hover {
            transform: rotate(90deg);
        }

        #chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }

        body.dark-mode #chat-box {
            background-color: #1e1e1e;
        }

        .message {
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 18px;
            max-width: 80%;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease;
            position: relative;
            line-height: 1.4;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-message {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .bot-message {
            background: #f1f1f1;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }

        body.dark-mode .bot-message {
            background: #2d2d2d;
            color: #f5f5f5;
        }

        .message-time {
            display: block;
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        #user-input-container {
            display: flex;
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
        }

        body.dark-mode #user-input-container {
            background: #1e1e1e;
            border-top: 1px solid #333;
        }

        #user-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            outline: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        #user-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
        }

        #send-button {
            margin-left: 10px;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #send-button:hover {
            transform: scale(1.05);
        }

        .typing-indicator {
            display: flex;
            padding: 10px 15px;
            background: #f1f1f1;
            border-radius: 18px;
            margin-bottom: 15px;
            width: fit-content;
        }

        body.dark-mode .typing-indicator {
            background: #2d2d2d;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #666;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 60px 0 30px;
            margin-top: 60px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: inline-block;
            background: linear-gradient(to right, #fff, #ddd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        .footer-about p {
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: white;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .social-links a:hover {
            transform: translateY(-3px);
        }

        .footer-links h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-links h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: white;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links ul li {
            margin-bottom: 10px;
        }

        .footer-links ul li a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links ul li a:hover {
            opacity: 1;
            transform: translateX(5px);
        }

        .footer-newsletter input {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 30px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .footer-newsletter input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-newsletter button {
            width: 100%;
            padding: 12px;
            background: white;
            color: var(--primary);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .footer-newsletter button:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1002;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .search-box input {
                width: 200px;
            }
            
            .search-box input:focus {
                width: 250px;
            }
            
               .featured-poll-info {
        background: #222;
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: background 0.3s;
    }
    .featured-poll-info:hover {
        background: #333;
    }
    .poll-badge {
        background: red;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: bold;
    }
    .vote-button {
        background: #ff4500;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .vote-button:hover {
        background: #e63e00;
    }
    .poll-results {
        margin-top: 15px;
        display: none;
    }


            .featured-poll {
                grid-column: span 1;
                flex-direction: column;
            }
            
            .featured-poll-image {
                min-height: 200px;
            }
        }


        @media (max-width: 768px) {
            .navbar-nav {
                display: none;
            }
            
            .search-box {
                margin-left: 0;
                margin-right: 10px;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .cta-button {
                width: 100%;
            }
            
            #chat-container {
                width: 300px;
                height: 450px;
            }
        }

        @media (max-width: 576px) {
            .search-box {
                display: none;
            }
            
            .poll-grid {
                grid-template-columns: 1fr;
            }
            
            #chat-container {
                width: calc(100% - 60px);
                left: 30px;
                right: 30px;
                bottom: 90px;
            }
        }

        /* Animation Classes */
        .animate-pop {
            animation: popIn 0.5s ease;
        }

        @keyframes popIn {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1002;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        .toast.warning {
            background: var(--warning);
            color: var(--dark);
        }

        .toast.info {
            background: var(--info);
        }

        /* Pulse Animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
    </style>










</head>
<body>
    







<!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class='bx bxs-movie-play'></i>
            <h4>The Flixx</h4>
        </div>
        
        <div class="search-box">
            <i class='bx bx-search'></i>
            <input type="text" placeholder="Search polls...">
        </div>
        
        <button class="mobile-menu-btn">
            <i class='bx bx-menu'></i>
        </button>
        
        <ul class="navbar-nav">
            <li><a href="dashboard.php" class="active"><i class='bx bx-home'></i> Home</a></li>
            <li><a href="vote.php"><i class='bx bx-check-square'></i> Vote</a></li>
            <li><a href="results.php"><i class='bx bx-bar-chart'></i> Results</a></li>
            <li><a href="about.php"><i class='bx bx-info-circle'></i> About</a></li>
            <li><a href="contact.php"><i class='bx bx-envelope'></i> Contact</a></li>
            <?php if ($is_admin): ?>
                <li><a href="admin_dashboard.php"><i class='bx bx-shield'></i> Admin</a></li>
            <?php endif; ?>
            <!-- In your index.php or navbar.php -->
<li class="notification-item">
    <div id="notification-bell" onclick="toggleNotifications()">
        <i class='bx bx-bell'></i>
        <span id="unread-count">0</span>
    </div>
    <div id="notification-dropdown" style="display: none;">
        <div class="notification-header">
            <h4>Notifications</h4>
            <button onclick="markAllAsRead()" class="mark-all-read">Mark all as read</button>
        </div>
        <div id="notification-content">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>

</li>
        </ul>
    </nav>

    <!-- App Container -->
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Menu</h2>
                <button class="toggle-sidebar">
                    <i class='bx bx-chevron-left'></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
    <li><a href="dashboard.php" class="active"><i class='bx bx-home'></i> <span>Dashboard</span></a></li>
    <li><a href="profile.php"><i class='bx bx-user'></i> <span>Profile</span></a></li>
   
    <li><a href="available_polls.php"><i class='bx bx-list-ul'></i> <span>Available Polls</span></a></li> <!-- New link added -->
    <li><a href="results.php"><i class='bx bx-bar-chart'></i> <span>Results</span></a></li>
    <li><a href="signin.php"><i class='bx bx-cog'></i> <span>Logout</span></a></li>
    <li><a href="about.php"><i class='bx bx-info-circle'></i> <span>About</span></a></li>
</ul>



    <!-- User Auth Section -->
            <div class="sidebar-auth">
                <?php if ($is_logged_in || $is_admin): ?>
                    <div class="sidebar-profile">
                        <img src="https://th.bing.com/th/id/OIP.VZ9ba9AeIiJ7lPLR8C2fjwHaHa?w=216&h=216&c=7&r=0&o=5&pid=1.7" alt="User profile" class="sidebar-profile-img">
                        <div class="sidebar-profile-info">
                            <h4><?php echo htmlspecialchars($is_admin ? $_SESSION['admin_username'] : $username); ?></h4>
                            <p><?php echo $is_admin ? 'Administrator' : 'Member'; ?></p>
                        </div>
                    </div>
                   <a href="logout.php" class="logout-btn">

                        <i class='bx bx-log-out'></i> <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="signin.php" class="login-btn">
                        <i class='bx bx-log-in'></i> <span>Login</span>
                    </a>
                    <a href="register.php" class="register-btn">
                        <i class='bx bx-user-plus'></i> <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>



        <!-- Genre Movies Modal -->
        <div id="genre-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="modal-genre-title"></h2>
                <div class="movies-grid" id="movies-container"></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="animate__animated animate__fadeInDown">Discover & Vote on Your Favorite Movies</h1>
                    <p class="animate__animated animate__fadeInUp">Join thousands of movie lovers in deciding which films deserve the spotlight.</p>
                    <div class="cta-buttons">
                        <a href="#polls" class="cta-button animate__animated animate__fadeInLeft">
                            <i class='bx bx-star'></i> Explore Polls
                        </a>
                    </div>
                </div>
            </section>

            
<!-- Polls Section -->
<div class="poll-container" id="polls">
    <h2 class="section-title">Current Polls</h2>

    <?php if (empty($polls)): ?>
        <div class="no-polls">
            <i class='bx bx-movie'></i>
            <h3>No polls available at the moment</h3>
            <p>Check back later for updates.</p>
            <div class="cta-button disabled">
                <i class='bx bx-info-circle'></i> No Polls Available
            </div>
        </div>
    <?php else: ?>
        <div class="poll-grid">
            <?php foreach ($polls as $poll): ?>
                <div class="poll-card">
                    <div class="poll-image-container">
                        <img src="<?php 
                            echo !empty($poll['image']) 
                                ? htmlspecialchars($poll['image']) 
                                : 'https://www.adgully.com/img/800/202201/flix-logo.png.jpg'; 
                        ?>" 
                        alt="<?php echo htmlspecialchars($poll['question']); ?>" class="poll-image">
                        <span class="poll-badge">New</span>
                    </div>

                    <div class="poll-info">
                        <h3><?php echo htmlspecialchars($poll['question']); ?></h3>
                        <div class="poll-meta">
                            <span><i class='bx bx-user'></i> <?php echo (int)$poll['total_votes']; ?> votes</span>
                        </div>
                        <a href="vote.php?poll_id=<?php echo (int)$poll['id']; ?>" class="vote-button">
                            <i class='bx bx-check'></i> Vote Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

            <!-- Categories Section -->
            <div class="poll-container">
                <h2 class="section-title">Browse by Category</h2>
                <div class="poll-grid" id="categories-container">
                    <div class="loading-spinner"></div>
                </div>
            </div>

            <!-- Trailer Modal -->
            <div id="trailer-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <div class="modal-movie-info">
                        <h2 id="modal-movie-title"></h2>
                        <p id="modal-movie-overview"></p>
                        <p><strong>Rating:</strong> <span id="modal-movie-rating"></span></p>
                    </div>
                    <video id="trailer-video" controls width="100%"></video>
                    <h3>Related Movies</h3>
                    <div id="related-movies" class="related-movie-grid"></div>
                </div>
            </div>
        </div> <!-- End main-content -->
    </div> <





<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode initialization
    document.body.classList.add('dark-mode');
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    if (darkModeToggle) darkModeToggle.remove();
    localStorage.removeItem('darkMode');

    // TMDB API Configuration
    const API_KEY = '08b9b8265a5266a65138256d97f5797e';
    const BASE_URL = 'https://api.themoviedb.org/3';
    const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/w500';

    // Movie categories to display (25 total)
const categories = [
    { id: 28, name: 'Action' },
    { id: 12, name: 'Adventure' },
    { id: 16, name: 'Animation' },
    { id: 35, name: 'Comedy' },
    { id: 80, name: 'Crime' },
    { id: 99, name: 'Documentary' },
    { id: 18, name: 'Drama' },
    { id: 10751, name: 'Family' },
    { id: 14, name: 'Fantasy' },
    { id: 36, name: 'History' },
    { id: 27, name: 'Horror' },
    { id: 10402, name: 'Music' },
    { id: 9648, name: 'Mystery' },
    { id: 10749, name: 'Romance' },
    { id: 878, name: 'Sci-Fi' },
    { id: 10770, name: 'Supernatural' }, // Custom/creative category (you can swap or map to 'Horror' or similar)
    { id: 10752, name: 'War' },
    { id: 37, name: 'Western' },
    { id: 10759, name: 'Action & Adventure' }, // From TMDb TV categories, repurposed
    { id: 10762, name: 'Kids' },
    { id: 10763, name: 'News' }, // or skip if not relevant
    { id: 10764, name: 'Reality' }, // or rename as 'Reality Shows'
    { id: 10765, name: 'Sci-Fi & Fantasy' }, // blended genre
    { id: 10766, name: 'Soap' },
    { id: 53, name: 'Thriller' }
];

    // State management
    let currentGenre = null;
    let currentMovies = [];
    let userVotes = JSON.parse(localStorage.getItem('userVotes')) || {};
    let userRatings = JSON.parse(localStorage.getItem('userRatings')) || {};

    // Initialize
    displayCategories();
    setupEventListeners();

    // Voting system
    function handleVoteClick(e) {
        if (e.target.classList.contains('vote-btn')) {
            const card = e.target.closest('.movie-card');
            if (!card) return;
            
            const movieId = card.getAttribute('data-movie-id');
            const pollId = card.getAttribute('data-poll-id');
            
            if (!movieId || !pollId) {
                console.error('Missing movie or poll ID');
                return;
            }

            fetch('record_vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `movie_id=${encodeURIComponent(movieId)}&poll_id=${encodeURIComponent(pollId)}`
            })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showToast("Vote recorded! 🎉", 'success');
                } else {
                    showToast("Error: " + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast("Failed to record vote", 'error');
            });
        }
    }

    // Setup all event listeners
    function setupEventListeners() {
        // Voting
        document.addEventListener('click', handleVoteClick);

        // Notification bell
        const notificationBell = document.getElementById('notification-bell');
        if (notificationBell) {
            notificationBell.addEventListener('click', function() {
                const dropdown = document.getElementById('notification-dropdown');
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.classList.toggle('scrolled', window.scrollY > 50);
            }
        });

        // Sidebar toggle
        const toggleSidebar = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');

        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        }

        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992 && sidebar && !sidebar.contains(event.target)) {
                if (mobileMenuBtn && event.target !== mobileMenuBtn && !mobileMenuBtn.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const genreModal = document.getElementById('genre-modal');
            if (genreModal && event.target === genreModal) {
                genreModal.style.display = 'none';
            }
            
            const trailerModal = document.getElementById('trailer-modal');
            if (trailerModal && event.target === trailerModal) {
                trailerModal.style.display = 'none';
            }
        });

        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.poll-card').forEach(card => {
                    const title = card.querySelector('h3')?.textContent.toLowerCase();
                    card.style.display = title?.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }
    }

    // Fetch movies by genre
    async function fetchMoviesByGenre(genreId, genreName) {
        try {
            const response = await fetch(`${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${genreId}&sort_by=popularity.desc`);
            if (!response.ok) throw new Error('Failed to fetch');
            const data = await response.json();
            return data.results?.length > 0 ? { 
                genre: genreName, 
                movies: data.results.slice(0, 5) 
            } : null;
        } catch (error) {
            console.error(`Error fetching ${genreName} movies:`, error);
            return null;
        }
    }

    // Create category card
    function createCategoryCard(categoryData) {
        const card = document.createElement('div');
        card.className = 'poll-card';
        card.dataset.genre = categoryData.genre.toLowerCase();
        
        const movie = categoryData.movies[0];
        const posterPath = movie.poster_path ? 
            `${IMAGE_BASE_URL}${movie.poster_path}` : 
            'https://via.placeholder.com/300x200?text=No+Poster';
        
        card.innerHTML = `
            <div class="poll-image-container">
                <img src="${posterPath}" alt="${categoryData.genre} Movies" class="poll-image">
            </div>
            <div class="poll-info">
                <h3>${categoryData.genre} Movies</h3>
                <button class="vote-button" data-genre="${categoryData.genre}" data-genre-id="${movie.genre_ids[0]}">
                    <i class='bx bx-chevron-right'></i> Explore & Vote
                </button>
            </div>
        `;
        
        // Add event listener to the button
        const button = card.querySelector('.vote-button');
        button.addEventListener('click', () => {
            showGenreModal(categoryData.genre, movie.genre_ids[0]);
        });
        
        return card;
    }

    // Display all categories
    async function displayCategories() {
        const categoriesContainer = document.getElementById('categories-container');
        if (!categoriesContainer) return;
        
        categoriesContainer.innerHTML = '<div class="loading-spinner"></div>';
        
        try {
            const categoriesData = await Promise.all(
                categories.map(cat => fetchMoviesByGenre(cat.id, cat.name))
            );
            
            categoriesContainer.innerHTML = '';
            categoriesData
                .filter(cat => cat !== null)
                .forEach(category => {
                    categoriesContainer.appendChild(createCategoryCard(category));
                });
        } catch (error) {
            console.error('Error loading categories:', error);
            categoriesContainer.innerHTML = '<p>Failed to load categories. Please try again later.</p>';
        }
    }

    // Show genre modal with movies
    async function showGenreModal(genreName, genreId) {
        const genreModal = document.getElementById('genre-modal');
        const modalGenreTitle = document.getElementById('modal-genre-title');
        const moviesContainer = document.getElementById('movies-container');
        const closeModal = document.querySelector('.close-modal');

        if (!genreModal || !modalGenreTitle || !moviesContainer) return;
        
        currentGenre = genreName;
        modalGenreTitle.textContent = `Loading ${genreName} movies...`;
        moviesContainer.innerHTML = '<div class="loading-spinner"></div>';
        genreModal.style.display = 'block';
        
        try {
            const response = await fetch(`${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${genreId}&sort_by=popularity.desc`);
            if (!response.ok) throw new Error('Failed to fetch');
            const data = await response.json();
            currentMovies = data.results.slice(0, 12);
            
            modalGenreTitle.textContent = `Popular ${genreName} Movies`;
            moviesContainer.innerHTML = '';
            
            currentMovies.forEach((movie, index) => {
                moviesContainer.appendChild(createMovieCard(movie, index));
            });
            
            // Add close event listener
            if (closeModal) {
                closeModal.onclick = () => genreModal.style.display = 'none';
            }
        } catch (error) {
            console.error(`Error loading ${genreName} movies:`, error);
            moviesContainer.innerHTML = `<p>Failed to load ${genreName} movies. Please try again later.</p>`;
        }
    }

    // Create movie card with interactive elements
    function createMovieCard(movie, index) {
        const movieCard = document.createElement('div');
        movieCard.className = 'movie-card';
        movieCard.dataset.movieId = movie.id;
        
        const posterPath = movie.poster_path ? 
            `${IMAGE_BASE_URL}${movie.poster_path}` : 
            'https://via.placeholder.com/150x225?text=No+Poster';
        
        const hasVoted = userVotes[movie.id] !== undefined;
        const userRating = userRatings[movie.id] || 0;
        
        movieCard.innerHTML = `
            <div class="movie-card-inner">
                <div class="movie-poster-container">
                    <img src="${posterPath}" alt="${movie.title}" class="movie-poster">
                    <div class="movie-overlay">
                        <button class="quick-vote-btn ${hasVoted ? 'voted' : ''}">
                            <i class='bx ${hasVoted ? 'bx-check' : 'bx-upvote'}'></i>
                            ${hasVoted ? 'Voted!' : 'Vote'}
                        </button>
                    </div>
                </div>
                <div class="movie-info">
                    <h3 class="movie-title">${movie.title}</h3>
                    <div class="movie-meta">
                        <span class="release-year">${movie.release_date?.substring(0, 4) || 'N/A'}</span>
                        <span class="vote-average">⭐ ${movie.vote_average?.toFixed(1) || 'N/A'}</span>
                    </div>
                    <div class="user-rating">
                        <div class="star-rating" data-movie-id="${movie.id}">
                            ${createStarRating(userRating, movie.id)}
                        </div>
                    </div>
                    <button class="view-details-btn">
                        <i class='bx bx-info-circle'></i> Details
                    </button>
                </div>
            </div>
        `;
        
        // Add event listeners
        const voteBtn = movieCard.querySelector('.quick-vote-btn');
        voteBtn.addEventListener('click', (e) => handleQuickVote(e, movie.id));
        
        const detailsBtn = movieCard.querySelector('.view-details-btn');
        detailsBtn.addEventListener('click', () => showMovieDetails(movie.id));
        
        // Add star rating event listeners
        const stars = movieCard.querySelectorAll('.star-rating i');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                rateMovie(movie.id, rating);
            });
        });
        
        return movieCard;
    }

    // Create star rating HTML
    function createStarRating(rating, movieId) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `
                <i class='bx ${i <= rating ? 'bxs-star' : 'bx-star'}' 
                   data-rating="${i}"></i>
            `;
        }
        return stars;
    }

    // Handle quick voting
    function handleQuickVote(event, movieId) {
        event.stopPropagation();
        const voteBtn = event.currentTarget;
        
        if (userVotes[movieId] === undefined) {
            userVotes[movieId] = true;
            voteBtn.innerHTML = '<i class="bx bx-check"></i> Voted!';
            voteBtn.classList.add('voted');
            showToast('Vote recorded!', 'success');
        } else {
            delete userVotes[movieId];
            voteBtn.innerHTML = '<i class="bx bx-upvote"></i> Vote';
            voteBtn.classList.remove('voted');
            showToast('Vote removed', 'info');
        }
        
        localStorage.setItem('userVotes', JSON.stringify(userVotes));
    }

    // Rate a movie (1-5 stars)
    function rateMovie(movieId, rating) {
        userRatings[movieId] = rating;
        localStorage.setItem('userRatings', JSON.stringify(userRatings));
        
        const starContainer = document.querySelector(`.star-rating[data-movie-id="${movieId}"]`);
        if (starContainer) {
            starContainer.innerHTML = createStarRating(rating, movieId);
            
            // Reattach event listeners to new stars
            const stars = starContainer.querySelectorAll('i');
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const newRating = parseInt(star.getAttribute('data-rating'));
                    rateMovie(movieId, newRating);
                });
            });
        }
        
        showToast(`Rated ${rating} star${rating > 1 ? 's' : ''}!`, 'success');
    }

    // Show detailed movie view
    function showMovieDetails(movieId) {
        const movie = currentMovies.find(m => m.id === movieId);
        if (!movie) return;
        
        const detailModal = document.createElement('div');
        detailModal.className = 'movie-detail-modal';
        detailModal.innerHTML = `
            <div class="movie-detail-content">
                <span class="close-detail-modal">&times;</span>
                <div class="movie-detail-header">
                    <img src="${movie.backdrop_path ? IMAGE_BASE_URL + movie.backdrop_path : 'https://via.placeholder.com/800x450?text=No+Backdrop'}" 
                         alt="${movie.title}" class="movie-backdrop">
                    <div class="movie-header-info">
                        <h2>${movie.title}</h2>
                        <div class="movie-meta">
                            <span>${movie.release_date?.substring(0, 4) || 'N/A'}</span>
                            <span>⭐ ${movie.vote_average?.toFixed(1) || 'N/A'}</span>
                            <span>${Math.floor(movie.runtime / 60)}h ${movie.runtime % 60}m</span>
                        </div>
                        <div class="movie-actions">
                            <button class="detail-vote-btn ${userVotes[movie.id] ? 'voted' : ''}">
                                <i class='bx ${userVotes[movie.id] ? 'bx-check' : 'bx-upvote'}'></i>
                                ${userVotes[movie.id] ? 'Voted' : 'Vote'}
                            </button>
                            <div class="detail-rating">
                                <span>Your Rating:</span>
                                <div class="star-rating">
                                    ${createStarRating(userRatings[movie.id] || 0, movie.id)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="movie-detail-body">
                    <div class="movie-overview">
                        <h3>Overview</h3>
                        <p>${movie.overview || 'No overview available.'}</p>
                    </div>
                    <div class="movie-stats">
                        <div class="stat-box">
                            <i class='bx bx-trending-up'></i>
                            <span>${movie.popularity?.toFixed(0) || 'N/A'}</span>
                            <small>Popularity</small>
                        </div>
                        <div class="stat-box">
                            <i class='bx bx-group'></i>
                            <span>${movie.vote_count || '0'}</span>
                            <small>Votes</small>
                        </div>
                        <div class="stat-box">
                            <i class='bx bx-star'></i>
                            <span>${movie.vote_average?.toFixed(1) || 'N/A'}</span>
                            <small>Rating</small>
                        </div>
                    </div>
                    <div class="create-poll-section">
                        <h3>Create Poll for This Movie</h3>
                        <p>Start a discussion about this movie with the community!</p>
                        <button class="create-poll-btn">
                            <i class='bx bx-plus'></i> Create Poll
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add event listeners
        const closeBtn = detailModal.querySelector('.close-detail-modal');
        closeBtn.addEventListener('click', () => detailModal.remove());
        
        const voteBtn = detailModal.querySelector('.detail-vote-btn');
        voteBtn.addEventListener('click', (e) => {
            handleQuickVote(e, movie.id);
            // Update the button state
            if (userVotes[movie.id]) {
                voteBtn.innerHTML = '<i class="bx bx-check"></i> Voted';
                voteBtn.classList.add('voted');
            } else {
                voteBtn.innerHTML = '<i class="bx bx-upvote"></i> Vote';
                voteBtn.classList.remove('voted');
            }
        });
        
        const stars = detailModal.querySelectorAll('.star-rating i');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                rateMovie(movie.id, rating);
            });
        });
        
        const createPollBtn = detailModal.querySelector('.create-poll-btn');
        createPollBtn.addEventListener('click', () => createPollForMovie(movie.id));
        
        document.body.appendChild(detailModal);
    }

    // Create poll for a specific movie
    function createPollForMovie(movieId) {
        const movie = currentMovies.find(m => m.id === movieId);
        if (!movie) return;
        
        const question = prompt(`Enter your poll question about ${movie.title}`, 
                              `Is ${movie.title} the best ${currentGenre} movie of ${movie.release_date?.substring(0, 4) || 'the year'}?`);
        
        if (question) {
            showToast(`Poll created: "${question}"`, 'success');
        }
    }

  

    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class='bx ${type === 'success' ? 'bx-check-circle' : 
                           type === 'error' ? 'bx-error' : 
                           type === 'warning' ? 'bx-error-circle' : 'bx-info-circle'}'></i>
            ${message}
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }




// Function to load notifications
function loadNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notification-content');
            
            if (data.length === 0) {
                container.innerHTML = '<div class="notification-message">No new notifications</div>';
                return;
            }
            
            let html = '';
            data.forEach(notification => {
                const notificationData = JSON.parse(notification.data);
                html += `
                    <div class="notification-message" data-id="${notification.id}">
                        <strong>${notificationData.subject}</strong><br>
                        ${notificationData.message}<br>
                        <small>${new Date(notification.created_at).toLocaleString()}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            document.getElementById('unread-count').textContent = data.length;
            
            // Mark as read when clicked
            document.querySelectorAll('.notification-message').forEach(item => {
                item.addEventListener('click', function() {
                    markAsRead(this.dataset.id);
                });
            });
        });
}

// Function to mark notification as read
function markAsRead(notificationId) {
    fetch('mark_as_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update unread count
            const unreadCount = document.getElementById('unread-count');
            unreadCount.textContent = parseInt(unreadCount.textContent) - 1;
        }
    });
}

// Check for new notifications periodically
function checkNewNotifications() {
    fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('unread-count').textContent = data.count;
        });
}

// Run every 5 minutes
setInterval(checkNewNotifications, 300000);





    // Trailer modal functionality
    function setupTrailerModal() {
        const trailerModal = document.getElementById("trailer-modal");
        if (!trailerModal) return;

        const closeModalBtn = trailerModal.querySelector(".close-modal");
        const trailerVideo = document.getElementById("trailer-video");

        document.querySelectorAll(".trailer-btn").forEach(button => {
            button.addEventListener("click", async () => {
                const trailerPath = button.dataset.trailer;
                const movieTitle = button.closest(".poll-option-card")?.querySelector("span")?.textContent.trim();

                if (trailerVideo) trailerVideo.src = trailerPath;
                trailerModal.style.display = "block";

                try {
                    const response = await fetch(`fetch_movie_details.php?title=${encodeURIComponent(movieTitle)}`);
                    if (!response.ok) throw new Error('Failed to fetch');
                    const data = await response.json();

                    const titleEl = document.getElementById("modal-movie-title");
                    const overviewEl = document.getElementById("modal-movie-overview");
                    const ratingEl = document.getElementById("modal-movie-rating");
                    const relatedContainer = document.getElementById("related-movies");

                    if (titleEl) titleEl.textContent = data.title || movieTitle;
                    if (overviewEl) overviewEl.textContent = data.overview || "No description available.";
                    if (ratingEl) ratingEl.textContent = data.vote_average || "N/A";

                    if (relatedContainer && data.related) {
                        relatedContainer.innerHTML = data.related.map(movie => `
                            <div class="related-movie-card">
                                <img src="${movie.poster}" alt="${movie.title}">
                                <p>${movie.title}</p>
                            </div>
                        `).join('');
                    }
                } catch (err) {
                    console.error("Error fetching movie details:", err);
                }
            });
        });

        if (closeModalBtn && trailerVideo) {
            closeModalBtn.addEventListener("click", () => {
                trailerVideo.pause();
                trailerVideo.currentTime = 0;
                trailerModal.style.display = "none";
            });
        }
    }

    // Initialize trailer modal
    setupTrailerModal();
});


function toggleNotifications() {
    const dropdown = document.getElementById("notification-dropdown");
    dropdown.style.display = dropdown.style.display === "none" ? "block" : "none";

    if (dropdown.style.display === "block") {
        fetch('fetch_notifications.php')
            .then(res => res.json())
            .then(data => {
                const content = document.getElementById("notification-content");
                content.innerHTML = "";

                if (data.length === 0 || data.error) {
                    content.innerHTML = "<p>No notifications found.</p>";
                    return;
                }

                let unreadCount = 0;

                data.forEach(n => {
                    if (n.is_read == 0) unreadCount++;

                    const item = document.createElement("div");
                    item.classList.add("notification");

                    item.innerHTML = `
                        <strong>${n.title}</strong><br>
                        <small>${new Date(n.created_at).toLocaleString()}</small>
                        <p>${n.message}</p>
                        <hr>
                    `;

                    content.appendChild(item);
                });

                document.getElementById("unread-count").textContent = unreadCount;
            });
    }
}

function markAllAsRead() {
    fetch('mark_notifications_read.php')
        .then(() => {
            toggleNotifications(); // refresh
            document.getElementById("unread-count").textContent = 0;
        });
}


</script>





</body>
</html>

