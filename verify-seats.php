<?php
// verify-seats.php - API to verify specific seats available for showtime
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$movie_id = (int)$_POST['movie_id'] ?? 0;
$theater = trim($_POST['theater'] ?? '');
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$seats_str = trim($_POST['seats'] ?? '');

if ($movie_id <= 0 || empty($theater) || empty($date) || empty($time) || empty($seats_str)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $seats_array = array_filter(array_map('trim', explode(',', $seats_str)));
    
    // 1. Verify showtime exists
    $stmt = $conn->prepare('SELECT id FROM showtimes WHERE movie_id = ? AND theater = ? AND date = ? AND time = ?');
    $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        echo json_encode(['success' => false, 'message' => 'Showtime not found']);
        exit;
    }
    $stmt->close();
    
    // 2. Check all seats available
    $unavailable = [];
    $stmt = $conn->prepare('
        SELECT seat_number FROM seats 
        WHERE movie_id = ? AND theater = ? AND date = ? AND time = ? AND occupied = 1
    ');
    $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (in_array($row['seat_number'], $seats_array)) {
            $unavailable[] = $row['seat_number'];
        }
    }
    $stmt->close();
    
    if (!empty($unavailable)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Seats unavailable: ' . implode(', ', $unavailable),
            'unavailable' => $unavailable
        ]);
    } else {
        echo json_encode(['success' => true, 'available' => true, 'message' => 'All seats available']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>

