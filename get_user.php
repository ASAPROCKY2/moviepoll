<?php
require 'db.php';
$id = $_POST['id'];
$query = "SELECT * FROM users WHERE id = $id";
$result = $db->query($query);
echo json_encode($result->fetch_assoc());
?>
