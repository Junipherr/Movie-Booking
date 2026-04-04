<?php
require 'includes/config.php';

// Test movie 21
echo "=== TESTING MOVIE 21 ===\n\n";

$stmt = $conn->prepare('SELECT id, title FROM movies WHERE id=21');
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    echo "✓ Movie 21 exists: " . $row['title'] . "\n";
} else {
    echo "✗ Movie 21 not found\n";
}
$stmt->close();

// Test showtimes for movie 21
$stmt = $conn->prepare('SELECT COUNT(*) FROM showtimes WHERE movie_id=21');
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
echo "✓ Showtimes for movie 21: " . $count . "\n";
$stmt->close();

// Test the API directly
echo "\n=== TESTING API ===\n";
$_GET['movie_id'] = 21;
include 'get-showtimes.php';
echo "\n";

$conn->close();
?>