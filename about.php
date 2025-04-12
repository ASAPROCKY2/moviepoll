<?php
session_start(); // Start a session for authentication

// Check if admin is logged in
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - The Flixx</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            width: 100%;
            min-height: 100vh;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            text-align: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #1c1c1c, #3a3a3a);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .navbar .container {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: space-between;
        }

        .navbar-header h4 {
            font-size: 22px;
            color: #fff;
            font-weight: bold;
        }

        .navbar-header h4 span {
            color: #ffcc00;
        }

        .navbar-nav {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .navbar-nav a {
            color: #fff;
            text-decoration: none;
            font-size: 18px;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s ease-in-out;
        }

        .navbar-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* About Section Styles */
        .about-container {
            max-width: 900px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.2);
        }

        .about-container h2 {
            color: #ffcc00;
        }

        .about-container p {
            font-size: 18px;
            line-height: 1.6;
            color: #ddd;
        }

        .contact-links a {
            display: inline-block;
            color: #ffcc00;
            font-size: 18px;
            margin: 10px;
            text-decoration: none;
            transition: color 0.3s ease-in-out;
        }

        .contact-links a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-header">
                <h4>The <span>Flixx</span></h4>
            </div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class='bx bx-home'></i> Home</a></li>
                <li><a href="about.php"><i class='bx bx-info-circle'></i> About</a></li>
                <li><a href="contact.php"><i class='bx bx-envelope'></i> Contact</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class='bx bx-log-in'></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- About Section -->
    <div class="about-container">
        <h2>About The Flixx 🎬</h2>
        <p><strong>Vote, Discuss, and Celebrate Movies Like Never Before!</strong></p>

        <h2>🔍 Problem Statement</h2>
        <p>With so many movies releasing every year, it’s hard to keep up with public opinion. <strong>The Flixx</strong> solves this by providing a <strong>real-time voting platform</strong> where movie lovers can cast their votes, see trending movies, and engage in exciting discussions.</p>

        <h2>👥 Target Audience</h2>
        <p><strong>The Flixx</strong> is designed for:</p>
        <ul style="list-style: none; padding: 0;">
            <li>🎥 Movie Enthusiasts – Find out what’s hot and what’s not.</li>
            <li>📊 Film Critics – Gauge audience reception in real time.</li>
            <li>🎬 Streaming & Cinema Fans – Stay updated with top-rated content.</li>
            <li>🏆 Awards Predictors – Use poll data to anticipate award winners.</li>
        </ul>

        <h2>🚀 Future Plans</h2>
        <p>We’re just getting started! Here’s what’s coming soon:</p>
        <ul style="list-style: none; padding: 0;">
            <li>✨ User Profiles – Save your votes and track favorite movies.</li>
            <li>📊 Advanced Analytics – Deep dive into poll trends.</li>
            <li>🎟️ Exclusive Movie Giveaways – Win tickets and prizes!</li>
            <li>📱 Mobile App – Take the polls anywhere!</li>
        </ul>

        <h2>💻 Development Stack</h2>
        <p><strong>The Flixx</strong> is built using:</p>
        <ul style="list-style: none; padding: 0;">
            <li>🖥️ Frontend – HTML, CSS, JavaScript</li>
            <li>⚙️ Backend – PHP, MySQL</li>
            <li>🔗 Frameworks – Bootstrap, jQuery</li>
            <li>🔒 Security – PHP Sessions & Authentication</li>
        </ul>

        <!-- Admin Message (Only for Admins) -->
        <?php if ($is_admin): ?>
            <p style="color: lightgreen; font-weight: bold;">Admin Access: You can create, delete, and manage polls and users.</p>
        <?php endif; ?>

        <h2>📩 Contact & Links</h2>
        <p>We'd love to hear from you! Connect with us:</p>
        <div class="contact-links">
            <a href="mailto:support@theflixx.com"><i class='bx bx-envelope'></i> Email</a>
            <a href="https://twitter.com/theflixx"><i class='bx bxl-twitter'></i> Twitter</a>
            <a href="https://github.com/theflixx"><i class='bx bxl-github'></i> GitHub</a>
        </div>
    </div>
</body>
</html>