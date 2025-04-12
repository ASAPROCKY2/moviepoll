<?php
require 'db_connection.php';

try {
    // Test query
    $stmt = $conn->query("SELECT 1");
    echo "✅ Database connection successful!";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}