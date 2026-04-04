<?php
require 'includes/config.php';

echo "=== DATABASE DEBUG ===\n\n";

// Check if movie 18 exists
$stmt = $conn->prepare("SELECT id, title FROM movies WHERE id = 18");
$stmt->execute();
$result = $stmt->get_result();
if ($movie = $result->fetch_assoc()) {
    echo "✓ Movie found: ID=" . $movie['id'] . ", Title=" . $movie['title'] . "\n";
} else {
    echo "✗ Movie 18 not found\n";
    $conn->close();
    exit;
}
$stmt->close();

// Check seats
$stmt = $conn->prepare("SELECT COUNT(*) FROM seats WHERE movie_id = 18");
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
echo "✓ Seats for Movie 18: " . $count . "\n";
$stmt->close();

// Check seat details
$stmt = $conn->prepare("SELECT seat_number, theater, occupied FROM seats WHERE movie_id = 18 LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
echo "\n=== First 5 Seats ===\n";
while ($row = $result->fetch_assoc()) {
    echo "Seat: " . $row['seat_number'] . ", Theater: " . $row['theater'] . ", Occupied: " . $row['occupied'] . "\n";
}
$stmt->close();

// Check occupied seats
$stmt = $conn->prepare("SELECT COUNT(*) FROM seats WHERE movie_id = 18 AND occupied = 1");
$stmt->execute();
$stmt->bind_result($occupied);
$stmt->fetch();
echo "\n✓ Occupied seats: " . $occupied . "\n";
$stmt->close();

$conn->close();
echo "\n=== END DEBUG ===\n";
?>
