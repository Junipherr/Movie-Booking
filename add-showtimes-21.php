<?php
require 'includes/config.php';

echo "=== ADDING SHOWTIMES FOR MOVIE 21 ===\n\n";

// Check if movie 21 exists
$stmt = $conn->prepare('SELECT id, title FROM movies WHERE id=21');
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    echo "✓ Movie 21 exists: " . $row['title'] . "\n";
    $movie_id = $row['id'];
} else {
    echo "✗ Movie 21 not found\n";
    exit;
}
$stmt->close();

// Check existing showtimes
$stmt = $conn->prepare('SELECT COUNT(*) FROM showtimes WHERE movie_id=21');
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
echo "Current showtimes for movie 21: " . $count . "\n";
$stmt->close();

if ($count > 0) {
    echo "Showtimes already exist for movie 21\n";
    exit;
}

// Add showtimes for movie 21
$theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];
$times = ['10:00:00', '13:00:00', '16:00:00', '19:00:00', '22:00:00'];

$stmt = $conn->prepare('INSERT INTO showtimes (movie_id, date, time, theater) VALUES (?, ?, ?, ?)');

$added = 0;
for ($day = 0; $day < 7; $day++) {
    $date = date('Y-m-d', strtotime("+$day days"));

    // Add 2-3 random times per theater per day
    $selectedTimes = array_rand(array_flip($times), rand(2, 3));
    if (!is_array($selectedTimes)) {
        $selectedTimes = [$selectedTimes];
    }

    foreach ($theaters as $theater) {
        foreach ($selectedTimes as $time) {
            $stmt->bind_param('isss', $movie_id, $date, $time, $theater);
            if ($stmt->execute()) {
                $added++;
            }
        }
    }
}

$stmt->close();
$conn->close();

echo "✓ Added $added showtimes for movie 21\n";
echo "Now try: http://localhost/Movie%20Booking/booking.php?movie_id=21\n";
?>