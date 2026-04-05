<?php
// bookings.php - Handle seat booking form POST, create pending booking
require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$movie_id = (int)$_POST['movie_id'] ?? 0;
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$theater = trim($_POST['theater'] ?? '');
$selected_seats = trim($_POST['selectedSeats'] ?? '');

$error = '';
$success = false;

// Validation
if ($movie_id <= 0 || empty($date) || empty($time) || empty($theater) || empty($selected_seats)) {
    $error = 'Missing required fields.';
} else {
    // 1. Verify showtime exists
    $stmt = $conn->prepare('SELECT id FROM showtimes WHERE movie_id = ? AND date = ? AND time = ? AND theater = ?');
    $stmt->bind_param('isss', $movie_id, $date, $time, $theater);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        $error = 'Selected showtime not available.';
        $stmt->close();
    } else {
        $stmt->close();
        
        // 2. Parse and validate seats
        $seats_array = array_filter(array_map('trim', explode(',', $selected_seats)));
        if (count($seats_array) === 0 || count($seats_array) > 10) {
            $error = 'Invalid seats selected (max 10).';
        } else {
            // 3. Double-check seats available (race condition protection)
            $available = true;
            $stmt = $conn->prepare('
                SELECT seat_number FROM seats 
                WHERE movie_id = ? AND theater = ? AND date = ? AND time = ? 
                AND seat_number = ? AND occupied = 0
            ');
            foreach ($seats_array as $seat) {
                $stmt->bind_param('issss', $movie_id, $theater, $date, $time, $seat);
                $stmt->execute();
                if (!$stmt->get_result()->num_rows) {
                    $available = false;
                    break;
                }
            }
            $stmt->close();
            
            if (!$available) {
                $error = 'One or more selected seats no longer available.';
            } else {
                // 4. Create pending booking
                $total_seats = count($seats_array);
                $price_per_seat = 250.00; // Standard price
                $total_price = $total_seats * $price_per_seat;
                $seats_str = implode(',', $seats_array);
                
                $stmt = $conn->prepare('
                    INSERT INTO bookings (user_id, movie_id, movie_title, date, time, theater, seats, price, status, created_at) 
                    VALUES (?, ?, (SELECT title FROM movies WHERE id=?), ?, ?, ?, ?, ?, "Pending", NOW())
                ');
                $stmt->bind_param('iissssds', $_SESSION['user_id'], $movie_id, $movie_id, $date, $time, $theater, $seats_str, $total_price);
                
                if ($stmt->execute()) {
                    $booking_id = $conn->insert_id;
                    $stmt->close();
                    
                    // 5. Mark seats occupied
                    if (markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $seats_str, $date, $time)) {
                        $_SESSION['booking_message'] = 'Booking created successfully!';
                        header("Location: payment.php?booking_id=" . $booking_id);
                        exit;
                    } else {
                        $error = 'Failed to reserve seats.';
                        // Rollback booking
                        $conn->query("DELETE FROM bookings WHERE id = $booking_id");
                    }
                } else {
                    $error = 'Failed to create booking.';
                }
            }
        }
    }
}

$_SESSION['booking_error'] = $error;
header('Location: booking.php?movie_id=' . $movie_id . '&error=booking_failed');
exit;
?>

