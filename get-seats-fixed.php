<?php
// get-seats.php - FIXED: Get occupied seats for specific showtime via bookings + showtimes JOIN
require_once 'includes/config.php';

header('Content-Type: application/json');

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theater = isset($_GET['theater']) ? trim($_GET['theater']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$time = isset($_GET['time']) ? trim($_GET['time']) : '';

if ($movie_id <= 0 || empty($theater) || empty($date) || empty($time)) {
    echo json_encode(['success' => false, 'error' => 'Missing required params: movie_id, theater, date, time']);
    exit;
}

try {
    // **FIXED QUERY**: Get occupied seat_numbers from bookings for this EXACT showtime
    $stmt = $conn->prepare('
        SELECT DISTINCT b.seats 
        FROM bookings b
        INNER JOIN showtimes st ON b.movie_id = st.movie_id 
            AND b.date = st.date 
            AND b.time = st.time
            AND b.theater = st.theater
        WHERE b.movie_id = ? AND b.theater = ? AND st.date = ? AND st.time = ? 
        AND b.status != "Cancelled"
    ');
    $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
    
    if (!$stmt->execute()) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $result = $stmt->get_result();
    $occupied_seats = [];
    
    while ($row = $result->fetch_assoc()) {
        // Parse comma-separated seats from booking (e.g. "A1,B2,C3")
        $booking_seats = array_filter(array_map('trim', explode(',', $row['seats'] ?? '')));
        $occupied_seats = array_merge($occupied_seats, $booking_seats);
    }
    
    $stmt->close();
    
    // Remove duplicates and filter valid seats (A1-J12 format)
    $occupied_seats = array_unique(array_filter($occupied_seats, function($seat) {
        return preg_match('/^[A-J][1-9]$|^[A-J]1[0-2]$/', $seat);
    }));
    
    echo json_encode([
        'success' => true,
        'movie_id' => $movie_id,
        'theater' => $theater,
        'date' => $date,
        'time' => $time,
        'total_occupied' => count($occupied_seats),
        'occupied_seats' => $occupied_seats,
        'debug_showtimes' => $result->num_rows // Number of bookings for this show
    ]);
    
} catch (Exception $e) {
    error_log('get-seats.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>

