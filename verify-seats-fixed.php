<?php
/**
 * Verify Seats Availability API Endpoint
 * 
 * Validates that selected seats are still available before booking submission.
 * Called by booking.js on form submit to prevent double-booking.
 * 
 * @route: verify-seats-fixed.php
 * @method: POST
 * @requires: includes/config.php
 * 
 * @post-fields:
 *   - movie_id (int): ID of the movie
 *   - theater (string): Theater name
 *   - date (string): Show date YYYY-MM-DD
 *   - time (string): Show time HH:MM:SS
 *   - seats (string): Comma-separated seat numbers
 * 
 * @returns: JSON object with success status and availability boolean
 * 
 * @used-by: booking.js:431 (verifySeatsAvailable function on form submit)
 * @see bookings.php (server-side validation after this passes)
 */

require_once 'includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters from POST
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$theater = isset($_POST['theater']) ? trim($_POST['theater']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time = isset($_POST['time']) ? trim($_POST['time']) : '';
$seats = isset($_POST['seats']) ? trim($_POST['seats']) : '';

// Validate required parameters
if ($movie_id <= 0 || empty($theater) || empty($date) || empty($time) || empty($seats)) {
    echo json_encode(['success' => false, 'available' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Parse the seat numbers
$seats_array = array_filter(array_map('trim', explode(',', $seats)));

if (empty($seats_array)) {
    echo json_encode(['success' => false, 'available' => false, 'message' => 'No seats provided']);
    exit;
}

try {
    // Get all occupied seats for this showtime
    $stmt = $conn->prepare('
        SELECT seats 
        FROM bookings 
        WHERE movie_id = ? AND theater = ? AND date = ? AND time = ?
        AND status != "Cancelled"
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

    // Check each selected seat against occupied seats
    $taken_seats = [];
    foreach ($seats_array as $seat) {
        if (in_array($seat, $occupied_seats)) {
            $taken_seats[] = $seat;
        }
    }

    if (!empty($taken_seats)) {
        echo json_encode([
            'success' => true,
            'available' => false,
            'message' => 'These seats are already taken: ' . implode(', ', $taken_seats),
            'taken_seats' => $taken_seats
        ]);
        exit;
    }

    // All seats are available
    echo json_encode([
        'success' => true,
        'available' => true,
        'message' => 'All seats available'
    ]);

} catch (Exception $e) {
    error_log('verify-seats.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'available' => false, 'message' => 'Error verifying seats']);
}

$conn->close();
?>