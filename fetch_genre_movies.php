<?php
require_once 'config.php'; // your DB connection

header('Content-Type: application/json');

if (!isset($_GET['genre_id'])) {
    echo json_encode(['error' => 'Genre ID is missing']);
    exit;
}

$genre_id = intval($_GET['genre_id']);

try {
    $stmt = $conn->prepare("SELECT id, title, poster_url FROM movies WHERE genre_id = ?");
    $stmt->execute([$genre_id]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($movies);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch movies: ' . $e->getMessage()]);
}
