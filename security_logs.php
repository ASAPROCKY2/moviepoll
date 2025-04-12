<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

require_admin_login();

// Get database connection
$db = $conn;

// Pagination setup
$results_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Search and filter functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_filter = isset($_GET['user']) ? trim($_GET['user']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';

// Base query with parameterized statements for security
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM security_logs WHERE 1=1";
$params = [];
$types = [];

if (!empty($search)) {
    $query .= " AND (action LIKE :search OR ip_address LIKE :search)";
    $params[':search'] = '%'.$search.'%';
    $types[':search'] = PDO::PARAM_STR;
}

if (!empty($user_filter)) {
    $query .= " AND username = :user";
    $params[':user'] = $user_filter;
    $types[':user'] = PDO::PARAM_STR;
}

if (!empty($type_filter)) {
    $query .= " AND log_type = :type";
    $params[':type'] = $type_filter;
    $types[':type'] = PDO::PARAM_STR;
}

$query .= " ORDER BY timestamp DESC LIMIT :offset, :limit";
$params[':offset'] = $offset;
$types[':offset'] = PDO::PARAM_INT;
$params[':limit'] = $results_per_page;
$types[':limit'] = PDO::PARAM_INT;

// Prepare and execute the query
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
}

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total results count
$total_results = $db->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = ceil($total_results / $results_per_page);

// Get unique users and log types for filter dropdowns
$users = $db->query("SELECT DISTINCT username FROM security_logs ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);
$types = $db->query("SELECT DISTINCT log_type FROM security_logs ORDER BY log_type")->fetchAll(PDO::FETCH_COLUMN);

// Get stats for dashboard
$stats = [
    'total_logs' => $total_results,
    'login_attempts' => $db->query("SELECT COUNT(*) FROM security_logs WHERE log_type = 'login'")->fetchColumn(),
    'security_events' => $db->query("SELECT COUNT(*) FROM security_logs WHERE log_type = 'security'")->fetchColumn(),
    'admin_actions' => $db->query("SELECT COUNT(*) FROM security_logs WHERE log_type = 'admin'")->fetchColumn()
];

$page_title = 'Security Logs | Admin Panel';
include 'header.php';
?>

<style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #f8f9fc;
        --accent-color: #2e59d9;
        --text-color: #5a5c69;
        --danger-color: #e74a3b;
        --warning-color: #f6c23e;
        --success-color: #1cc88a;
    }
    
    .logs-container {
        max-width: 1200px;
        margin: 40px auto;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        animation: fadeIn 0.5s ease-in-out;
    }
    
    .logs-header {
        background: var(--primary-color);
        padding: 20px;
        color: white;
        font-size: 1.5rem;
        text-align: center;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .log-row {
        display: grid;
        grid-template-columns: 150px 120px 120px 1fr 120px;
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        transition: background 0.3s;
        font-size: 0.9rem;
    }
    
    .log-row:hover {
        background: #f9f9f9;
    }
    
    .log-row:last-child {
        border-bottom: none;
    }
    
    .log-header {
        background: #f1f1f1;
        font-weight: bold;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .log-timestamp {
        color: #666;
    }
    
    .log-user {
        font-weight: 600;
    }
    
    .log-type {
        border-radius: 4px;
        padding: 3px 8px;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
    }
    
    .log-type.login {
        background-color: rgba(28, 200, 138, 0.1);
        color: var(--success-color);
    }
    
    .log-type.security {
        background-color: rgba(231, 74, 59, 0.1);
        color: var(--danger-color);
    }
    
    .log-type.action {
        background-color: rgba(78, 115, 223, 0.1);
        color: var(--primary-color);
    }
    
    .log-type.system {
        background-color: rgba(246, 194, 62, 0.1);
        color: var(--warning-color);
    }
    
    .log-ip {
        font-family: monospace;
        color: #666;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box i {
        position: absolute;
        top: 12px;
        left: 12px;
        color: #d1d3e2;
    }
    
    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
        border: 1px solid #d1d3e2;
    }
    
    .filter-dropdown {
        border-radius: 20px;
        border: 1px solid #d1d3e2;
        padding: 0.375rem 0.75rem;
    }
    
    .stats-card {
        border-left: 0.25rem solid var(--primary-color);
        margin-bottom: 20px;
    }
    
    .stats-card .card-body {
        padding: 1rem;
    }
    
    .stats-card .text-primary {
        color: var(--primary-color) !important;
    }
    
    .stats-card .text-xs {
        font-size: 0.7rem;
    }
    
    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(20px);}
        to {opacity: 1; transform: translateY(0);}
    }
    
    .pagination .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .pagination .page-link {
        color: var(--primary-color);
    }
    
    .export-btn {
        background: white;
        border: none;
        color: var(--primary-color);
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .export-btn:hover {
        background: var(--primary-color);
        color: white;
    }
    
    .no-logs {
        text-align: center;
        padding: 40px;
        color: #666;
    }
</style>

<div class="content-wrapper">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-shield-alt mr-2"></i>Security Logs</h1>
        <div>
            <button class="btn btn-danger" id="clearLogsBtn">
                <i class="fas fa-trash mr-2"></i>Clear Logs
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Logs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['total_logs']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Login Attempts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['login_attempts']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Security Events</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['security_events']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Admin Actions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['admin_actions']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Container -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Security Events Log</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" 
                     aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv mr-2"></i>CSV</a>
                    <a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel mr-2"></i>Excel</a>
                    <a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf mr-2"></i>PDF</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Search and Filter Bar -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search logs..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control filter-dropdown" id="userFilter">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user) ?>" <?= $user_filter === $user ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control filter-dropdown" id="typeFilter">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($type)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block" id="applyFilters">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
            </div>
            
            <!-- Logs Table -->
            <div class="table-responsive">
                <div class="logs-container">
                    <div class="log-row log-header">
                        <div>Timestamp</div>
                        <div>User</div>
                        <div>Type</div>
                        <div>Action</div>
                        <div>IP Address</div>
                    </div>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="log-row">
                                <div class="log-timestamp"><?= htmlspecialchars($log['timestamp']) ?></div>
                                <div class="log-user"><?= htmlspecialchars($log['username']) ?></div>
                                <div class="log-type <?= strtolower($log['log_type']) ?>"><?= htmlspecialchars($log['log_type']) ?></div>
                                <div><?= htmlspecialchars($log['action']) ?></div>
                                <div class="log-ip"><?= htmlspecialchars($log['ip_address']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-logs">
                            <i class="fas fa-info-circle fa-3x mb-3" style="color: #ddd;"></i>
                            <h4>No security logs found</h4>
                            <p>There are no logs matching your criteria</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="text-muted">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $results_per_page, $total_results) ?> of <?= $total_results ?> entries
                    </div>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Page navigation" class="float-right">
                        <ul class="pagination">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&user=<?= urlencode($user_filter) ?>&type=<?= urlencode($type_filter) ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&user=<?= urlencode($user_filter) ?>&type=<?= urlencode($type_filter) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&user=<?= urlencode($user_filter) ?>&type=<?= urlencode($type_filter) ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Apply filters
    $('#applyFilters').click(function() {
        var search = $('#searchInput').val();
        var user = $('#userFilter').val();
        var type = $('#typeFilter').val();
        window.location.href = 'security_logs.php?search=' + encodeURIComponent(search) + 
                              '&user=' + encodeURIComponent(user) + 
                              '&type=' + encodeURIComponent(type);
    });
    
    // Enter key in search input
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            $('#applyFilters').click();
        }
    });
    
    // Clear logs confirmation
    $('#clearLogsBtn').click(function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete all security logs!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, clear all logs!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'clear_logs.php',
                    type: 'POST',
                    beforeSend: function() {
                        Swal.showLoading();
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire('Cleared!', 'All security logs have been deleted.', 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', result.message || 'Failed to clear logs', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'An unexpected error occurred', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to connect to server', 'error');
                    }
                });
            }
        });
    });
    
    // Export buttons
    $('#exportCSV').click(function(e) {
        e.preventDefault();
        var search = $('#searchInput').val();
        var user = $('#userFilter').val();
        var type = $('#typeFilter').val();
        window.location.href = 'export_logs.php?format=csv&search=' + encodeURIComponent(search) + 
                              '&user=' + encodeURIComponent(user) + 
                              '&type=' + encodeURIComponent(type);
    });
    
    $('#exportExcel').click(function(e) {
        e.preventDefault();
        var search = $('#searchInput').val();
        var user = $('#userFilter').val();
        var type = $('#typeFilter').val();
        window.location.href = 'export_logs.php?format=excel&search=' + encodeURIComponent(search) + 
                              '&user=' + encodeURIComponent(user) + 
                              '&type=' + encodeURIComponent(type);
    });
    
    $('#exportPDF').click(function(e) {
        e.preventDefault();
        var search = $('#searchInput').val();
        var user = $('#userFilter').val();
        var type = $('#typeFilter').val();
        window.location.href = 'export_logs.php?format=pdf&search=' + encodeURIComponent(search) + 
                              '&user=' + encodeURIComponent(user) + 
                              '&type=' + encodeURIComponent(type);
    });
    
    // Real-time updates (simulated)
    function checkForNewLogs() {
        setTimeout(function() {
            // In a real implementation, this would check the server for new logs
            if (Math.random() > 0.8) {
                Swal.fire({
                    title: 'New Security Event',
                    text: 'A new security event has been logged',
                    icon: 'info',
                    confirmButtonText: 'Refresh',
                    showCancelButton: true,
                    cancelButtonText: 'Dismiss'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            }
            checkForNewLogs();
        }, 30000); // Check every 30 seconds
    }
    
    // Start checking for new logs
    checkForNewLogs();
});
</script>