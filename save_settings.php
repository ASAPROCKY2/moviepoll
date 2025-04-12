<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $maintenance_mode = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] == '1' ? 1 : 0;
    $default_role = $_POST['default_role'] ?? 'user';

    try {
        // Assume settings row has ID = 1 (only one settings row exists)
        $stmt = $conn->prepare("UPDATE settings SET site_name = ?, maintenance_mode = ?, default_role = ? WHERE id = 1");
        $stmt->execute([$site_name, $maintenance_mode, $default_role]);

        // Redirect with success message
        header("Location: system_settings.php?success=1");
        exit;
    } catch (PDOException $e) {
        echo "Error saving settings: " . $e->getMessage();
    }
}
?>
