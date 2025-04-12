<?php
$db = new mysqli("localhost", "root", "", "movie-poll-db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Function to log security events
function logSecurityEvent($db, $user_id, $action) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO security_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>
