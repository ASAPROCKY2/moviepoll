<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'profile.php'):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Bar</title>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">

    <!-- ✅ INLINE CSS: Add this directly inside the <head> -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            transition: 0.3s ease-in-out;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .navbar-brand:hover {
            transform: scale(1.1);
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .navbar-nav li {
            margin: 0 15px;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            transition: color 0.3s ease-in-out;
        }

        .navbar-nav a:hover {
            color: #ff4500;
        }

        .navbar-toggler {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .back-link {
            color: #ffa502;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #eccc68;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                right: 20px;
                background: rgba(30, 30, 30, 0.9);
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            .navbar-nav.active {
                display: flex;
            }

            .navbar-toggler {
                display: block;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">MyWebsite</a>

        <button class="navbar-toggler" onclick="toggleNav()">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="navbar-nav" id="navbarNav">
            <?php if (!in_array($current_page, ['index.php', 'login.php'])): ?>
                <li><a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Back</a></li>
            <?php endif; ?>
            <li><a href="dashboard.php">🏠 Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if ($is_logged_in): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="signin.php">Login</a></li>
                <li><a href="available_polls.php">Available Polls</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <script>
        function toggleNav() {
            const navbarNav = document.getElementById('navbarNav');
            navbarNav.classList.toggle('active');
        }
    </script>
<?php endif; ?>
