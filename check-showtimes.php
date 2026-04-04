<?php
require 'includes/config.php';

echo "=== SHOWTIME CHECK ===\n\n";

// Count showtimes total
$result = $conn->query("SELECT COUNT(*) FROM showtimes");
$total = $result->fetch_row()[0];
echo "Total showtimes in database: " . $total . "\n\n";

// Check for movie 18
$result = $conn->query("SELECT COUNT(*) FROM showtimes WHERE movie_id = 18");
$count = $result->fetch_row()[0];
echo "Showtimes for movie 18: " . $count . "\n";

if ($count > 0) {
    echo "\nSample showtimes:\n";
    $result = $conn->query("SELECT id, movie_id, date, time, theater FROM showtimes WHERE movie_id = 18 LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        echo "- Date: " . $row['date'] . ", Time: " . $row['time'] . ", Theater: " . $row['theater'] . "\n";
    }
} else {
    echo "\n⚠️ No showtimes found for movie 18!\n";
    echo "\nTo add test data, run this SQL:\n";
    echo "INSERT INTO showtimes (movie_id, date, time, theater) VALUES\n";
    echo "(18, '2026-04-05', '10:00:00', 'Screen 1'),\n";
    echo "(18, '2026-04-05', '13:00:00', 'Screen 1'),\n";
    echo "(18, '2026-04-05', '16:00:00', 'Screen 1'),\n";
    echo "(18, '2026-04-05', '19:00:00', 'Screen 1'),\n";
    echo "(18, '2026-04-05', '22:00:00', 'Screen 1');\n";
}

$conn->close();
?>
