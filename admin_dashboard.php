<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Check if admin is logged in
require_admin_login();

try {
    // Get dashboard stats
    $stats = getDashboardStats();
    $last_login = $_SESSION['admin_last_login'] ?? null;
    $user_count = $stats['total_users'] ?? 0;
    $poll_count = $stats['active_polls'] ?? 0;
    $vote_count = $stats['total_votes'] ?? 0;
    
    // Include header
    $page_title = 'Admin Dashboard | The Flixx';
    include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --accent-color: #fd79a8;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #d63031;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            transition: background-color var(--transition-speed);
        }

        body.dark-mode {
            background-color: #1a1a2e;
            color: #f0f0f0;
        }

        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .logo i {
            font-size: 2rem;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-links li a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-links i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        /* Main Container */
        .main-container {
            display: flex;
            min-height: calc(100vh - 70px);
            margin-top: 70px;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            z-index: 900;
            height: calc(100vh - 70px);
            position: fixed;
            overflow-y: auto;
        }

        body.dark-mode .sidebar {
            background: #16213e;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        body.dark-mode .sidebar-header {
            border-bottom: 1px solid #333;
        }

        .sidebar-header h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .sidebar-menu li a {
            color: #ddd;
        }

        .sidebar-menu li a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background-color: var(--primary-color);
            transform: translateX(-100%);
            transition: transform 0.3s;
        }

        .sidebar-menu li a:hover {
            background-color: rgba(108, 92, 231, 0.1);
            color: var(--primary-color);
        }

        .sidebar-menu li a:hover::before {
            transform: translateX(0);
        }

        .sidebar-menu li a.active {
            background-color: rgba(108, 92, 231, 0.2);
            color: var(--primary-color);
            font-weight: 500;
        }

        .sidebar-menu li a.active::before {
            transform: translateX(0);
        }

        .sidebar-menu i {
            font-size: 1.2rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }

        /* Dashboard Content */
        .dashboard-content {
            flex: 1;
            padding: 30px;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed);
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(108, 92, 231, 0.3);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            transform: rotate(30deg);
        }

        .welcome-message h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .welcome-message p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .last-login {
            font-size: 0.9rem;
            opacity: 0.8;
            font-style: italic;
        }

        /* Security Warning */
        .security-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(253, 203, 110, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(253, 203, 110, 0); }
            100% { box-shadow: 0 0 0 0 rgba(253, 203, 110, 0); }
        }

        .security-warning i {
            font-size: 1.5rem;
            margin-right: 15px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .stat-card {
            background: #16213e;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 1rem;
            color: #777;
        }

        body.dark-mode .stat-label {
            color: #aaa;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--dark-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        body.dark-mode .action-btn {
            background: #16213e;
            color: #f0f0f0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .action-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .action-btn span {
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
        }

        /* Admin Cards */
        .admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border-left: 4px solid var(--primary-color);
        }

        body.dark-mode .admin-card {
            background: #16213e;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .admin-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .admin-card h3 i {
            margin-right: 10px;
        }

        .admin-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        body.dark-mode .admin-card p {
            color: #ccc;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
            z-index: 1000;
            transition: all 0.3s;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }

        .dark-mode-toggle i {
            font-size: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 70px;
                left: 0;
                bottom: 0;
                z-index: 900;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .dashboard-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px;
            }

            .logo span {
                display: none;
            }

            .welcome-message h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }

            .admin-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-section, .security-warning, .stats-grid, .quick-actions, .admin-cards {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .stats-grid > * {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .stats-grid > *:nth-child(1) { animation-delay: 0.1s; }
        .stats-grid > *:nth-child(2) { animation-delay: 0.2s; }
        .stats-grid > *:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">
            <i class='bx bxs-movie'></i>
            <span>The Flixx Admin</span>
        </div>
        <button class="mobile-menu-btn">
            <i class='bx bx-menu'></i>
        </button>
        <ul class="nav-links">
            <li><a href="logout.php"><i class='bx bx-log-out'></i> <span class="nav-text">Logout</span></a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Menu</h2>
            </div>
            
            <ul class="sidebar-menu">
    <li><a href="admin_dashboard.php" class="active"><i class='bx bx-home'></i> <span>Admin Dashboard</span></a></li>
    <li><a href="dashboard.php"><i class='bx bx-user-circle'></i> <span>User Dashboard</span></a></li> <!-- ✅ New Link -->
    <li><a href="manage_polls.php"><i class='bx bx-bar-chart'></i> <span>Manage Polls</span></a></li>
    <li><a href="manage_users.php"><i class='bx bx-user'></i> <span>Manage Users</span></a></li>
    <li><a href="system_settings.php"><i class='bx bx-cog'></i> <span>System Settings</span></a></li>
    <li><a href="security_Logs.php"><i class='bx bx-shield'></i> <span>Security Logs</span></a></li>
    <li><a href="create_poll.php"><i class='bx bx-plus-circle'></i> <span>Create Poll</span></a></li>
    <li><a href="notification.php"><i class='bx bx-bell'></i> <span>Notifications</span></a></li>
</ul>

        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-message">
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
                    <p class="last-login">Last login: <?php echo $last_login ? date('F j, Y g:i a', strtotime($last_login)) : 'First login'; ?></p>
                    <p>You have full administrative access to The Flixx platform.</p>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="security-warning">
                <i class='bx bx-shield-alt'></i>
                <span>Restricted Area: All actions are logged and monitored. Please use your admin privileges responsibly.</span>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-user'></i></div>
                    <div class="stat-value"><?php echo number_format($user_count); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-bar-chart'></i></div>
                    <div class="stat-value"><?php echo number_format($poll_count); ?></div>
                    <div class="stat-label">Active Polls</div>
                </div>
                
            </div>

            <!-- Quick Actions -->
            <h2 style="margin-bottom: 20px;">Quick Actions</h2>
            <div class="quick-actions">
                <a href="create_poll.php" class="action-btn">
                    <i class='bx bx-plus'></i>
                    <span>Create New Poll</span>
                </a>
                <a href="manage_users.php" class="action-btn">
                    <i class='bx bx-user-plus'></i>
                    <span>Manage Users </span>
                </a>
                <a href="system_settings.php" class="action-btn">
                    <i class='bx bx-cog'></i>
                    <span>System Settings</span>
                </a>
                <a href="security_logs.php" class="action-btn">
                    <i class='bx bx-shield-alt'></i>
                    <span>View Security Logs</span>
                </a>
            </div>

            <!-- Admin Cards -->
            <h2 style="margin-bottom: 20px;">Administrative Controls</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3><i class='bx bx-cog'></i> System Settings</h3>
                    <p>Configure core system parameters, maintenance mode, and global settings. Update site-wide configurations with just a few clicks.</p>
                    <a href="system_settings.php" class="dashboard-btn">Access Settings</a>
                </div>
                
                <div class="admin-card">
                    <h3><i class='bx bx-user'></i> User Management</h3>
                    <p>Manage all user accounts, permissions, and access levels. Create, modify, or suspend user accounts as needed.</p>
                    <a href="manage_users.php" class="dashboard-btn">Manage Users</a>
                </div>
                
                <div class="admin-card">
                    <h3><i class='bx bx-shield-alt'></i> Security Logs</h3>
                    <p>View system access logs and monitor suspicious activities. Track all admin actions and system events in real-time.</p>
                    <a href="security_logs.php" class="dashboard-btn">View Logs</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <i class='bx bx-moon'></i>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });

        // Dark mode toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            
            // Change icon
            const icon = document.querySelector('.dark-mode-toggle i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('bx-moon');
                icon.classList.add('bx-sun');
            } else {
                icon.classList.remove('bx-sun');
                icon.classList.add('bx-moon');
            }
        }

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            document.querySelector('.dark-mode-toggle i').classList.remove('bx-moon');
            document.querySelector('.dark-mode-toggle i').classList.add('bx-sun');
        }

        // Real-time stats update (simulated)
        function updateStats() {
            fetch('api/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Animate the stat changes
                    animateValue('.stat-card:nth-child(1) .stat-value', <?php echo $user_count; ?>, data.users, 1000);
                    animateValue('.stat-card:nth-child(2) .stat-value', <?php echo $poll_count; ?>, data.polls, 1000);
                    animateValue('.stat-card:nth-child(3) .stat-value', <?php echo $vote_count; ?>, data.votes, 1000);
                })
                .catch(error => console.error('Error fetching stats:', error));
        }

        // Animate value changes
        function animateValue(selector, start, end, duration) {
            const element = document.querySelector(selector);
            if (!element) return;
            
            const range = end - start;
            let current = start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.abs(Math.floor(duration / range));
            const timer = setInterval(() => {
                current += increment;
                element.textContent = numberFormat(current);
                if (current == end) {
                    clearInterval(timer);
                }
            }, stepTime);
        }

        // Number formatting
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Update stats every 30 seconds
        setInterval(updateStats, 30000);

        // Initialize tooltips
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = `${rect.left + rect.width/2 - tooltip.offsetWidth/2}px`;
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
                
                this.addEventListener('mouseleave', () => {
                    tooltip.remove();
                });
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
<?php
} catch (PDOException $e) {
    die("Error loading dashboard: " . $e->getMessage());
}
?>