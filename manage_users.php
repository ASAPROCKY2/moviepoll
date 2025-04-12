<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

require_admin_login();

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "CSRF token validation failed";
        header("Location: manage_users.php");
        exit();
    }

    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_message'] = "User deleted successfully!";
        header("Location: manage_users.php");
        exit();
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error deleting user. Please try again.";
        header("Location: manage_users.php");
        exit();
    }
}

// Pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Search & filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = :role";
    $params[':role'] = $role_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get users
$query = "SELECT * FROM users $where_sql ORDER BY id DESC LIMIT :offset, :limit";
$stmt = $conn->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$count_query = "SELECT COUNT(*) FROM users $where_sql";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_results = $count_stmt->fetchColumn();
$total_pages = ceil($total_results / $results_per_page);

// Get stats using prepared statements
$active = $conn->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$admins = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$inactive = $conn->prepare("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();

$stats = [
    'total' => $total_results,
    'active' => $active,
    'admins' => $admins,
    'inactive' => $inactive
];

// Get roles for filter
$roles_stmt = $conn->query("SELECT DISTINCT role FROM users");
$roles = $roles_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$page_title = 'User Management | Admin Panel';
include 'header.php';

// Show messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success mb-4">' . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger mb-4">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="content-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users-cog mr-2"></i>User Management</h1>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <?php foreach ([
            ['total', 'primary', 'users', 'Total Users'],
            ['active', 'success', 'user-check', 'Active Users'],
            ['admins', 'warning', 'user-shield', 'Admin Users'],
            ['inactive', 'danger', 'user-clock', 'Inactive Users']
        ] as $stat): ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-<?= htmlspecialchars($stat[1], ENT_QUOTES, 'UTF-8') ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= htmlspecialchars($stat[1], ENT_QUOTES, 'UTF-8') ?> text-uppercase mb-1">
                                <?= htmlspecialchars($stat[3], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats[$stat[0]], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-<?= htmlspecialchars($stat[2], ENT_QUOTES, 'UTF-8') ?> fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- User Table Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">User Records</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="export_users.php?format=csv&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                        <i class="fas fa-file-csv mr-2"></i>CSV
                    </a>
                    <a class="dropdown-item" href="export_users.php?format=excel&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                    </a>
                    <a class="dropdown-item" href="export_users.php?format=pdf&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search users..." 
                               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="roleFilter">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $role_filter === $role ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($role, ENT_QUOTES, 'UTF-8')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-block" id="applyFilters">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                                $avatarName = !empty($user['first_name']) ? 
                                                    $user['first_name'] . '+' . $user['last_name'] : 
                                                    $user['username'];
                                            ?>
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($avatarName) ?>&background=4e73df&color=fff&size=40" 
                                                 class="rounded-circle mr-3" width="40" height="40" alt="User Avatar">
                                            <div>
                                                <div class="font-weight-bold">
                                                    <?= !empty($user['first_name']) ? 
                                                        htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') : 
                                                        htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="text-muted small">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small">
                                            <?= !empty($user['phone']) ? htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') : 'No phone' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                            <?= ucfirst(htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst(htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($user['last_login']) && $user['last_login'] ? htmlspecialchars(date('M d, Y H:i', strtotime($user['last_login'])), ENT_QUOTES, 'UTF-8') : 'Never' ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-user" data-id="<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning status-user" data-id="<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>" title="Status">
                                            <i class="fas fa-user-cog"></i>
                                        </button>
                                        <a href="manage_users.php?delete_id=<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                           class="btn btn-sm btn-danger delete-user" 
                                           title="Delete"
                                           onclick="return confirmDelete();">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
                                        &laquo;
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
                                        &raquo;
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editUserForm" action="edit_user.php" method="post">
                <input type="hidden" name="id" id="editUserId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-user-cog mr-2"></i>Change Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="statusForm" action="update_status.php" method="post">
                <input type="hidden" name="id" id="statusUserId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" name="status" id="statusSelect" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statusReason">Reason (Optional)</label>
                        <textarea class="form-control" name="reason" id="statusReason" rows="2" maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function confirmDelete() {
    return confirm('Are you sure you want to delete this user? This action cannot be undone.');
}

$(document).ready(function() {
    // Apply filters
    $('#applyFilters').click(function() {
        const search = $('#searchInput').val();
        const role = $('#roleFilter').val();
        window.location.href = `?search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`;
    });

    // Enter key in search
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            $('#applyFilters').click();
        }
    });

    // Edit user button
    $('.edit-user').click(function() {
        const userId = $(this).data('id');
        $('#editUserId').val(userId);
        
        $.ajax({
            url: 'get_user.php?id=' + userId + '&csrf_token=<?= $_SESSION['csrf_token'] ?>',
            beforeSend: function() {
                $('#editUserModal').modal('show');
                $('#editUserForm .modal-body').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading user data...</p>
                    </div>
                `);
            },
            success: function(response) {
                $('#editUserForm .modal-body').html(response);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                $('#editUserForm .modal-body').html(`
                    <div class="alert alert-danger">
                        Failed to load user data. Please try again.
                    </div>
                `);
            }
        });
    });

    // Status button
    $('.status-user').click(function() {
        const userId = $(this).data('id');
        $('#statusUserId').val(userId);
        
        // Pre-fetch current status if needed
        $.ajax({
            url: 'get_user_status.php?id=' + userId,
            success: function(response) {
                if(response.status) {
                    $('#statusSelect').val(response.status);
                }
                $('#statusModal').modal('show');
            },
            error: function() {
                $('#statusModal').modal('show');
            }
        });
    });

    // Form submissions
    $('#editUserForm, #statusForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Processing...
        `);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        Swal.fire({
                            title: 'Success',
                            text: result.message || 'Operation completed successfully',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: result.message || 'Operation failed',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Invalid server response',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to connect to server',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>