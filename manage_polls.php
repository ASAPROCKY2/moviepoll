<?php
// manage_polls.php - Complete Fixed Version
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Check if admin is logged in
require_admin_login();

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete':
            if (isset($_GET['poll_id'])) {
                $poll_id = intval($_GET['poll_id']);
                if (deletePoll($poll_id)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Poll deleted successfully!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to delete poll.'];
                }
                header("Location: manage_polls.php");
                exit();
            }
            break;
            
        case 'toggle_status':
            if (isset($_GET['poll_id'])) {
                $poll_id = intval($_GET['poll_id']);
                if (togglePollStatus($poll_id)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Poll status updated!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update poll status.'];
                }
                header("Location: manage_polls.php");
                exit();
            }
            break;
            
        case 'clear_all_votes':
            if (isset($_GET['poll_id'])) {
                $poll_id = intval($_GET['poll_id']);
                if (clearPollVotes($poll_id)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'All votes cleared for this poll!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to clear votes.'];
                }
                header("Location: manage_polls.php");
                exit();
            }
            break;
    }
}

// Get all polls with proper vote counting
$all_polls = getPollsWithVoteCounts(false) ?? []; // Use the new function
$active_polls = [];
$inactive_polls = [];

foreach ($all_polls as $poll) {
    $status = isset($poll['active']) ? ($poll['active'] ? 'active' : 'inactive') : 'inactive';
    
    if ($status === 'active') {
        $active_polls[] = $poll;
    } else {
        $inactive_polls[] = $poll;
    }
}

$page_title = 'Manage Polls | The Flixx Admin';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --primary-dark: #5649c0;
            --secondary-color: #a29bfe;
            --accent-color: #fd79a8;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #d63031;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
            --border-radius: 12px;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--dark-color);
            transition: all var(--transition-speed);
            line-height: 1.6;
        }

        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 2rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-color);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color var(--transition-speed);
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-text {
            display: inline-block;
        }

        .dashboard-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h1 {
            font-size: 2rem;
            color: var(--dark-color);
            font-weight: 600;
        }

        .btn-create {
            background-color: var(--primary-color);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all var(--transition-speed);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.2);
        }

        .btn-create:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 92, 231, 0.3);
        }

        .flash-message {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
            cursor: pointer;
            box-shadow: var(--card-shadow);
        }

        .flash-message.success {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .flash-message.error {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .section-title {
            font-size: 1.5rem;
            margin: 2rem 0 1.5rem;
            color: var(--dark-color);
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }

        .poll-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            border-left: 4px solid transparent;
        }

        .poll-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .poll-card.active {
            border-left-color: var(--success-color);
        }

        .poll-card.inactive {
            border-left-color: var(--warning-color);
        }

        .poll-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .poll-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.3rem;
        }

        .poll-date {
            font-size: 0.9rem;
            color: #666;
        }

        .poll-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background-color: rgba(253, 203, 110, 0.1);
            color: #e17055;
        }

        .poll-stats {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #666;
        }

        .stat-item i {
            font-size: 1.1rem;
        }

        .poll-options {
            margin-bottom: 1.5rem;
        }

        .option-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .option-text {
            font-weight: 500;
            color: var(--dark-color);
        }

        .option-votes {
            color: #666;
            font-weight: 500;
        }

        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #f1f1f1;
            border-radius: 4px;
            margin-bottom: 1.2rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .poll-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
            border: none;
        }

        .btn-edit {
            background-color: rgba(108, 92, 231, 0.1);
            color: var(--primary-color);
        }

        .btn-edit:hover {
            background-color: rgba(108, 92, 231, 0.2);
        }

        .btn-toggle {
            background-color: rgba(253, 203, 110, 0.1);
            color: #e17055;
        }

        .btn-toggle:hover {
            background-color: rgba(253, 203, 110, 0.2);
        }

        .btn-clear {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
        }

        .btn-clear:hover {
            background-color: rgba(214, 48, 49, 0.2);
        }

        .btn-delete {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: rgba(214, 48, 49, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-speed);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transform: translateY(20px);
            transition: all var(--transition-speed);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
        }

        .modal-body {
            padding: 1.5rem;
            color: #666;
        }

        .modal-actions {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid #eee;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav-links .nav-text {
                display: none;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .poll-header {
                flex-direction: column;
                gap: 1rem;
            }

            .poll-status {
                align-self: flex-start;
            }

            .poll-actions {
                gap: 0.5rem;
            }

            .action-btn span {
                display: none;
            }

            .action-btn i {
                margin-right: 0;
            }
        }

        /* Dark Mode Toggle (Optional) */
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 99;
            border: none;
            font-size: 1.2rem;
        }

        body.dark-mode {
            background-color: #1a1a1a;
            color: #f0f0f0;
        }

        body.dark-mode .navbar,
        body.dark-mode .poll-card,
        body.dark-mode .empty-state,
        body.dark-mode .modal-content {
            background-color: #2d2d2d;
            color: #f0f0f0;
        }

        body.dark-mode .poll-title,
        body.dark-mode .option-text,
        body.dark-mode .modal-header {
            color: #f0f0f0;
        }

        body.dark-mode .poll-date,
        body.dark-mode .stat-item,
        body.dark-mode .option-votes,
        body.dark-mode .modal-body,
        body.dark-mode .empty-state p {
            color: #b0b0b0;
        }

        body.dark-mode .progress-container {
            background-color: #3d3d3d;
        }

        body.dark-mode .nav-links a {
            color: #f0f0f0;
        }

        body.dark-mode .section-title {
            color: #f0f0f0;
        }
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

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="section-header">
            <h1>Manage Polls</h1>
            <a href="create_poll.php" class="btn-create">
                <i class='bx bx-plus'></i> Create New Poll
            </a>
        </div>

        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo htmlspecialchars($_SESSION['flash_message']['type']); ?>">
                <i class='bx <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <span><?php echo htmlspecialchars($_SESSION['flash_message']['message']); ?></span>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- Active Polls -->
        <h2 class="section-title">Active Polls</h2>
        <?php if (!empty($active_polls)): ?>
            <?php foreach ($active_polls as $poll): ?>
                <div class="poll-card active">
                    <div class="poll-header">
                        <div>
                            <h3 class="poll-title"><?php echo htmlspecialchars($poll['title'] ?? 'Untitled Poll'); ?></h3>
                            <p class="poll-date">Created: <?php echo isset($poll['created_at']) ? date('F j, Y g:i a', strtotime($poll['created_at'])) : 'Unknown date'; ?></p>
                        </div>
                        <span class="poll-status status-active">Active</span>
                    </div>
                    
                    <div class="poll-stats">
                        <div class="stat-item">
                            <i class='bx bx-user'></i>
                            <span><?php echo isset($poll['total_votes']) ? (int)$poll['total_votes'] : 0; ?> votes</span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-show'></i>
                            <span><?php echo isset($poll['views']) ? (int)$poll['views'] : 0; ?> views</span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-time-five'></i>
                            <span><?php echo isset($poll['duration_days']) ? (int)$poll['duration_days'] : 7; ?> days</span>
                        </div>
                    </div>
                    
                    <div class="poll-options">
                        <?php if (!empty($poll['options'])): ?>
                            <?php foreach ($poll['options'] as $option): ?>
                                <div class="option-item">
                                    <span class="option-text"><?php echo htmlspecialchars($option['text'] ?? ''); ?></span>
                                    <span class="option-votes">
                                        <?php 
                                        $votes = isset($option['votes']) ? (int)$option['votes'] : 0;
                                        $total = isset($poll['total_votes']) ? (int)$poll['total_votes'] : 1;
                                        $percentage = $total > 0 ? round(($votes / $total) * 100, 1) : 0;
                                        echo "$votes ($percentage%)";
                                        ?>
                                    </span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No options available for this poll</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="poll-actions">
                        <?php if (isset($poll['id'])): ?>
                            <a href="edit_poll.php?poll_id=<?php echo (int)$poll['id']; ?>" class="action-btn btn-edit">
                                <i class='bx bx-edit'></i> <span>Edit</span>
                            </a>
                            <button class="action-btn btn-toggle" onclick="togglePollStatus(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-power-off'></i> <span>Deactivate</span>
                            </button>
                            <button class="action-btn btn-clear" onclick="confirmClearVotes(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-trash'></i> <span>Clear Votes</span>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDelete(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-trash'></i> <span>Delete</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-bar-chart-alt-2'></i>
                <h3>No Active Polls</h3>
                <p>There are currently no active polls. Create a new poll to get started!</p>
                <a href="create_poll.php" class="action-btn btn-edit">
                    <i class='bx bx-plus'></i> Create Poll
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Inactive Polls -->
        <h2 class="section-title">Inactive Polls</h2>
        <?php if (!empty($inactive_polls)): ?>
            <?php foreach ($inactive_polls as $poll): ?>
                <div class="poll-card inactive">
                    <div class="poll-header">
                        <div>
                            <h3 class="poll-title"><?php echo htmlspecialchars($poll['title'] ?? 'Untitled Poll'); ?></h3>
                            <p class="poll-date">Created: <?php echo isset($poll['created_at']) ? date('F j, Y g:i a', strtotime($poll['created_at'])) : 'Unknown date'; ?></p>
                        </div>
                        <span class="poll-status status-inactive">Inactive</span>
                    </div>
                    
                    <div class="poll-stats">
                        <div class="stat-item">
                            <i class='bx bx-user'></i>
                            <span><?php echo isset($poll['total_votes']) ? (int)$poll['total_votes'] : 0; ?> votes</span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-show'></i>
                            <span><?php echo isset($poll['views']) ? (int)$poll['views'] : 0; ?> views</span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-time-five'></i>
                            <span><?php echo isset($poll['duration_days']) ? (int)$poll['duration_days'] : 7; ?> days</span>
                        </div>
                    </div>
                    
                    <div class="poll-options">
                        <?php if (!empty($poll['options'])): ?>
                            <?php foreach ($poll['options'] as $option): ?>
                                <div class="option-item">
                                    <span class="option-text"><?php echo htmlspecialchars($option['text'] ?? ''); ?></span>
                                    <span class="option-votes">
                                        <?php 
                                        $votes = isset($option['votes']) ? (int)$option['votes'] : 0;
                                        $total = isset($poll['total_votes']) ? (int)$poll['total_votes'] : 1;
                                        $percentage = $total > 0 ? round(($votes / $total) * 100, 1) : 0;
                                        echo "$votes ($percentage%)";
                                        ?>
                                    </span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No options available for this poll</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="poll-actions">
                        <?php if (isset($poll['id'])): ?>
                            <a href="edit_poll.php?poll_id=<?php echo (int)$poll['id']; ?>" class="action-btn btn-edit">
                                <i class='bx bx-edit'></i> <span>Edit</span>
                            </a>
                            <button class="action-btn btn-toggle" onclick="togglePollStatus(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-power-off'></i> <span>Activate</span>
                            </button>
                            <button class="action-btn btn-clear" onclick="confirmClearVotes(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-trash'></i> <span>Clear Votes</span>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDelete(<?php echo (int)$poll['id']; ?>)">
                                <i class='bx bx-trash'></i> <span>Delete</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-bar-chart-alt-2'></i>
                <h3>No Inactive Polls</h3>
                <p>All your polls are currently active or you haven't created any polls yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Confirm Action</div>
            <div class="modal-body" id="modalBody">
                Are you sure you want to perform this action?
            </div>
            <div class="modal-actions">
                <button class="action-btn btn-edit" onclick="closeModal()">Cancel</button>
                <button class="action-btn btn-delete" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle (Optional) -->
    <button class="dark-mode-toggle" id="darkModeToggle">
        <i class='bx bx-moon'></i>
    </button>

    <script>
        // Toggle sidebar on mobile
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });

        // Modal functions
        let currentActionUrl = '';
        
        function showModal(title, message, actionUrl) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalBody').textContent = message;
            currentActionUrl = actionUrl;
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            currentActionUrl = '';
        }
        
        document.getElementById('confirmActionBtn').addEventListener('click', function() {
            if (currentActionUrl) {
                window.location.href = currentActionUrl;
            }
        });
        
        // Poll management functions
        function confirmDelete(pollId) {
            showModal(
                'Delete Poll', 
                'Are you sure you want to delete this poll? This action cannot be undone.', 
                `manage_polls.php?action=delete&poll_id=${pollId}`
            );
        }
        
        function togglePollStatus(pollId) {
            window.location.href = `manage_polls.php?action=toggle_status&poll_id=${pollId}`;
        }
        
        function confirmClearVotes(pollId) {
            showModal(
                'Clear All Votes', 
                'Are you sure you want to clear all votes for this poll? This action cannot be undone.', 
                `manage_polls.php?action=clear_all_votes&poll_id=${pollId}`
            );
        }
        
        // Animate progress bars on scroll
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }
        
        // Initialize animations when page loads
        window.addEventListener('load', animateProgressBars);
        
        // Close flash message when clicked
        const flashMessage = document.querySelector('.flash-message');
        if (flashMessage) {
            flashMessage.addEventListener('click', () => {
                flashMessage.style.display = 'none';
            });
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                if (flashMessage) {
                    flashMessage.style.display = 'none';
                }
            }, 5000);
        }
        
        // Add hover effects to poll cards
        document.querySelectorAll('.poll-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.1)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                card.style.boxShadow = '';
            });
        });

        // Dark mode toggle functionality
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        
        // Check for saved user preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="bx bx-sun"></i>';
        }
        
        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.innerHTML = '<i class="bx bx-sun"></i>';
            } else {
                localStorage.setItem('darkMode', 'disabled');
                darkModeToggle.innerHTML = '<i class="bx bx-moon"></i>';
            }
        });

        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Tooltip for buttons (optional)
        tippy('.action-btn', {
            content: (reference) => reference.querySelector('span').textContent,
            placement: 'top',
            theme: 'light',
            animation: 'scale'
        });
    </script>
    
    <!-- Optional: Include Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
</body>
</html>