<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Check if admin is logged in
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab values from form
    $site_name = $_POST['site_name'] ?? 'The Flixx';
    $maintenance_mode = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] == '1';
    $default_role = $_POST['default_role'] ?? 'user';

    try {
    $stmt = $conn->query("SELECT site_name, maintenance_mode, default_role FROM settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // fallback
        $settings = [
            'site_name' => 'The Flixx',
            'maintenance_mode' => false,
            'default_role' => 'user'
        ];
    }
} catch (PDOException $e) {
    echo "Error loading settings: " . $e->getMessage();
}


    // Optional: show confirmation
    echo "<div style='color: green; text-align: center;'>✅ Settings saved successfully.</div>";
}


// Sample settings fetch (replace with real DB logic)
$settings = [
    'site_name' => 'The Flixx',
    'maintenance_mode' => false,
    'default_role' => 'user'
];

$page_title = 'System Settings | The Flixx';
include 'header.php';
?>

<!-- Styles -->
<style>
.settings-container {
    max-width: 800px;
    margin: auto;
    padding: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.6s ease-in-out;
}
.settings-container h2 {
    font-size: 24px;
    margin-bottom: 1rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    font-weight: 600;
    display: block;
    margin-bottom: 0.5rem;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.btn-save {
    background: #6c5ce7;
    color: #fff;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.btn-save:hover {
    background: #5a4bd2;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="settings-container">
    <h2><i class='bx bx-cog'></i> System Settings</h2>

    <?php if (isset($_GET['success'])): ?>
        <p style="color: green; font-weight: bold; margin-bottom: 1rem;">
            ✅ Settings saved successfully!
        </p>
    <?php endif; ?>

    <form action="save_settings.php" method="POST">
        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>">
        </div>

        <div class="form-group">
            <label for="maintenance_mode">Maintenance Mode</label>
            <select id="maintenance_mode" name="maintenance_mode">
                <option value="0" <?= !$settings['maintenance_mode'] ? 'selected' : '' ?>>Disabled</option>
                <option value="1" <?= $settings['maintenance_mode'] ? 'selected' : '' ?>>Enabled</option>
            </select>
        </div>
        <div class="form-group">
            <label for="default_role">Default Role</label>
            <select id="default_role" name="default_role">
                <option value="user" <?= $settings['default_role'] == 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $settings['default_role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn-save">Save Settings</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    form.addEventListener("submit", (e) => {
        const btn = form.querySelector(".btn-save");
        btn.disabled = true;
        btn.textContent = "Saving...";
    });
});
</script>

<?php include 'footer.php'; ?>
