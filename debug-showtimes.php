<?php
require 'includes/config.php';

echo "=== DEBUGGING SHOWTIMES API ===\n\n";

// Test movie 21
$movie_id = 21;

echo "Testing movie_id: $movie_id\n";

// Check if movie exists
$stmt = $conn->prepare('SELECT id, title FROM movies WHERE id=?');
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    echo "✓ Movie exists: " . $row['title'] . "\n";
} else {
    echo "✗ Movie not found\n";
    exit;
}
$stmt->close();

// Check showtimes count
$stmt = $conn->prepare('SELECT COUNT(*) FROM showtimes WHERE movie_id=?');
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
echo "✓ Showtimes count: $count\n";
$stmt->close();

// Get actual showtimes data
$stmt = $conn->prepare('SELECT DISTINCT date, time, theater FROM showtimes WHERE movie_id=? ORDER BY date ASC, time ASC');
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
$times = [];
$theaters = [];

while ($row = $result->fetch_assoc()) {
    if (!in_array($row['date'], $dates)) {
        $dates[] = $row['date'];
    }
    if (!in_array($row['time'], $times)) {
        $times[] = $row['time'];
    }
    if (!in_array($row['theater'], $theaters)) {
        $theaters[] = $row['theater'];
    }
}
$stmt->close();

echo "\n=== API RESPONSE SIMULATION ===\n";
echo json_encode([
    'success' => true,
    'movie_id' => $movie_id,
    'dates' => $dates,
    'times' => $times,
    'theaters' => $theaters
], JSON_PRETTY_PRINT);

$conn->close();
?>