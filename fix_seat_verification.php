<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

// Test verification endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = $_POST['movie_id'] ?? 0;
    $theater = $_POST['theater'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $seats = $_POST['seats'] ?? '';

    echo "POST DATA:\n";
    echo json_encode([
        'movie_id' => $movie_id,
        'theater' => $theater,
        'date' => $date,
        'time' => $time,
        'seats' => $seats
    ], JSON_PRETTY_PRINT);

    // Test query
    $stmt = $conn->prepare('
        SELECT b.seats 
        FROM bookings b
        INNER JOIN showtimes st ON b.movie_id = st.movie_id AND b.date = st.date AND b.time = st.time AND b.theater = st.theater
        WHERE b.movie_id = ? AND b.theater = ? AND st.date = ? AND st.time = ? AND b.status != "Cancelled"
    ');
    $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $occupied = [];
    while ($row = $result->fetch_assoc()) {
        $occupied = array_merge($occupied, explode(',', $row['seats']));
    }
    
    echo "\nOCCUPIED SEATS FROM QUERY: " . json_encode($occupied) . "\n";
    $stmt->close();
} else {
    echo "POST to test verification (movie_id, theater, date, time, seats)";
}
?>

