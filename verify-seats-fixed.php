<?php
// verify-seats.php - FIXED: Verify specific seats available for showtime
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$movie_id = (int)$_POST['movie_id'] ?? 0;
$theater = trim($_POST['theater'] ?? '');
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$seats_str = trim($_POST['seats'] ?? '');

if ($movie_id <= 0 || empty($theater) || empty($date) || empty($time) || empty($seats_str)) {
    echo json_encode(['success' => false, 'error' => 'Missing: movie_id, theater, date, time, seats']);
    exit;
}

$selected_seats = array_filter(array_map('trim', explode(',', $seats_str)));

try {
    // **FIXED**: Get ALL occupied seats for this showtime via bookings+showtimes
    $stmt = $conn->prepare('
        SELECT b.seats 
        FROM bookings b
        INNER JOIN showtimes st ON b.movie_id = st.movie_id 
            AND b.date = st.date AND b.time = st.time AND b.theater = st.theater
        WHERE b.movie_id = ? AND b.theater = ? AND st.date = ? AND st.time = ? 
        AND b.status != "Cancelled"
    ');
    $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $occupied_seats = [];
    while ($row = $result->fetch_assoc()) {
        $booking_seats = array_filter(array_map('trim', explode(',', $row['seats'] ?? '')));
        $occupied_seats = array_merge($occupied_seats, $booking_seats);
    }
    $stmt->close();
    
    // Check for conflicts
    $unavailable = array_intersect($selected_seats, $occupied_seats);
    
    if (empty($unavailable)) {
        echo json_encode([
            'success' => true, 
            'available' => true, 
            'message' => 'All seats available',
            'selected_count' => count($selected_seats),
            'occupied_count' => count($occupied_seats)
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'available' => false,
            'message' => count($unavailable) . ' seat(s) unavailable: ' . implode(', ', $unavailable),
            'unavailable' => array_values($unavailable)
        ]);
    }
    
} catch (Exception $e) {
    error_log('verify-seats.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
</xai:function_call}



<xai:function_call name="read_file">
<parameter name="path">c:/xampp/htdocs/Movie Booking/test-movie-21.php
