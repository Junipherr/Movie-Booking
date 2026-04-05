<?php
require 'includes/config.php';

echo "=== ADDING SHOWTIMES FOR MOVIE 22 ===\n\n";

// Check if movie 22 exists
$stmt = $conn->prepare('SELECT id, title FROM movies WHERE id=22');
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    echo "✓ Movie 22 exists: " . $row['title'] . "\n";
    $movie_id = $row['id'];
} else {
    echo "✗ Movie 22 not found\n";
    exit;
}
$stmt->close();

// Add showtimes
$showtimes = [
    ['2026-04-07', '10:00:00', 'Screen 1'],
    ['2026-04-07', '13:00:00', 'Screen 1'],
    ['2026-04-07', '16:00:00', 'Screen 1'],
    ['2026-04-07', '19:00:00', 'Screen 1'],
    ['2026-04-07', '22:00:00', 'Screen 1'],
    ['2026-04-08', '10:00:00', 'Screen 1'],
];

$stmt = $conn->prepare('INSERT IGNORE INTO showtimes (movie_id, date, time, theater) VALUES (?, ?, ?, ?)');
$count = 0;
foreach ($showtimes as $show) {
    $stmt->bind_param('isss', $movie_id, $show[0], $show[1], $show[2]);
    if ($stmt->execute()) $count++;
}
$stmt->close();

echo "✓ Added $count showtimes for movie 22\n";
echo "Now test: http://localhost/Movie Booking/booking.php?movie_id=22\n";

$conn->close();
?>

