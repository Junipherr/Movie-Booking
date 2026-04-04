<?php
require 'includes/config.php';

echo "=== ADDING MISSING SHOWTIMES ===\n\n";

// Find movies without showtimes
$result = $conn->query("
    SELECT m.id, m.title
    FROM movies m
    LEFT JOIN showtimes s ON m.id = s.movie_id
    WHERE s.movie_id IS NULL
");

$movies_without_showtimes = [];
while ($row = $result->fetch_assoc()) {
    $movies_without_showtimes[] = $row;
}

echo "Movies without showtimes: " . count($movies_without_showtimes) . "\n\n";

if (empty($movies_without_showtimes)) {
    echo "All movies have showtimes!\n";
    exit;
}

// Add showtimes for each movie
$theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];
$times = ['10:00:00', '13:00:00', '16:00:00', '19:00:00', '22:00:00'];
$stmt = $conn->prepare('INSERT INTO showtimes (movie_id, date, time, theater) VALUES (?, ?, ?, ?)');

$total_added = 0;
foreach ($movies_without_showtimes as $movie) {
    echo "Adding showtimes for: " . $movie['title'] . "\n";

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
                $stmt->bind_param('isss', $movie['id'], $date, $time, $theater);
                if ($stmt->execute()) {
                    $added++;
                }
            }
        }
    }

    echo "  ✓ Added $added showtimes\n";
    $total_added += $added;
}

$stmt->close();
$conn->close();

echo "\nTotal showtimes added: $total_added\n";
echo "All movies now have showtimes!\n";
?>