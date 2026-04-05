<?php
// DEBUG: Show LAST booking details exactly matching user's feedback
require_once 'includes/config.php';

echo "<h2>Latest Bookings (last 5)</h2>";
$result = $conn->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 5");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "Movie: {$row['movie_title']}\n";
    echo "Date: {$row['date']}\n";
    echo "Time: {$row['time']}\n";
    echo "Theater: {$row['theater']}\n";
    echo "Seats: '{$row['seats']}' (length: " . strlen($row['seats']) . ")\n";
    echo "Price: {$row['price']}\n";
    echo "Status: {$row['status']}\n";
    echo "---\n";
}
echo "</pre>";

echo "<h2>Showtimes matching user's test</h2>";
$stmt = $conn->prepare("SELECT * FROM showtimes WHERE theater = 'Screen 1' AND date = '2026-04-05' AND time = '16:00:00'");
$stmt->execute();
$result = $stmt->get_result();
echo "<pre>Showtimes count: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}
echo "</pre>";

echo "<h2>Test booking.php POST simulation</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = trim($_POST['selectedSeats'] ?? '');
    echo "POST selectedSeats: '$selected_seats'\n";
    $arr = array_filter(array_map('trim', explode(',', $selected_seats)));
    echo "Parsed: " . json_encode($arr) . " (count: " . count($arr) . ")\n";
} else {
    echo '<form method="POST">';
    echo '<input type="hidden" name="movie_id" value="21">';
    echo '<input type="hidden" name="date" value="2026-04-05">';
    echo '<input type="hidden" name="time" value="16:00:00">';
    echo '<input type="hidden" name="theater" value="Screen 1">';
    echo '<input type="text" name="selectedSeats" placeholder="A1,A2" value="A1,A2">';
    echo '<button type="submit">Simulate POST</button>';
    echo '</form>';
}

$conn->close();
?>

