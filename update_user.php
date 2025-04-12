<?php
session_start();
require 'db.php'; // Include database connection

// Log raw input data for debugging
$rawData = file_get_contents("php://input");
file_put_contents("debug_log.txt", "RAW DATA: " . $rawData . "\n", FILE_APPEND);
file_put_contents("debug_log.txt", "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// If JSON, decode it
$data = json_decode($rawData, true);
if ($data === null) {
    $data = $_POST; // Use form data if JSON not found
}

if (!empty($data)) {
    if (isset($data['id'], $data['first_name'], $data['last_name'], $data['email'], $data['role'])) {

        $id = intval($data['id']);
        $first_name = trim($data['first_name']);
        $last_name = trim($data['last_name']);
        $email = trim($data['email']);
        $role = trim($data['role']);

        if (empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
            echo json_encode(["status" => "error", "message" => "All fields are required"]);
            exit();
        }

        $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $role, $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update user"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No data received"]);
}
?>
