<?php
require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ✅ FIXED INPUT HANDLING
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$theater = trim($_POST['theater'] ?? '');
$selected_seats = trim($_POST['selectedSeats'] ?? '');

$error = '';

// DEBUG
error_log("BOOKINGS DEBUG: seats='$selected_seats'");

// ✅ VALIDATION
if ($movie_id <= 0 || !$date || !$time || !$theater || !$selected_seats) {
    $error = 'Missing required fields.';
} else {

    // ✅ CHECK SHOWTIME
    $stmt = $conn->prepare('SELECT id FROM showtimes WHERE movie_id=? AND date=? AND time=? AND theater=?');
    $stmt->bind_param('isss', $movie_id, $date, $time, $theater);
    $stmt->execute();

    if (!$stmt->get_result()->num_rows) {
        $error = 'Invalid showtime.';
        $stmt->close();
    } else {
        $stmt->close();

        // ✅ PARSE SEATS
        $seats_array = array_unique(array_filter(array_map('trim', explode(',', $selected_seats))));

        if (count($seats_array) === 0 || count($seats_array) > 10) {
            $error = 'Invalid seat selection.';
        } else {

            // ✅ GET OCCUPIED SEATS
            $occupied_seats = [];

            $stmt = $conn->prepare('
                SELECT seats FROM bookings 
                WHERE movie_id=? AND theater=? AND date=? AND time=? AND status!="Cancelled"
            ');
            $stmt->bind_param('isss', $movie_id, $theater, $date, $time);
            $stmt->execute();

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $occupied_seats = array_merge(
                    $occupied_seats,
                    explode(',', $row['seats'])
                );
            }
            $stmt->close();

            // ✅ CHECK AVAILABILITY
            foreach ($seats_array as $seat) {
                if (in_array($seat, $occupied_seats)) {
                    $error = 'Seat already taken: ' . $seat;
                    break;
                }
            }

if (!$error) {
                $total_price = count($seats_array) * 250;
                $seats_str = implode(',', $seats_array);

                $stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
                $stmt->bind_param("i", $movie_id);
                $stmt->execute();
                $movie = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $_SESSION['pending_booking'] = [
                    'movie_id' => $movie_id,
                    'movie_title' => $movie['title'] ?? 'Unknown Movie',
                    'date' => $date,
                    'time' => $time,
                    'theater' => $theater,
                    'seats' => $seats_str,
                    'total_price' => $total_price
                ];

                header("Location: payment.php");
                exit;
            }
        }
    }
}

// ❌ FINAL FAIL
$_SESSION['booking_error'] = $error;
header('Location: booking.php?movie_id=' . $movie_id);
exit;
?>