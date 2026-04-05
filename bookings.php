<?php
/**
 * Booking Processing Handler
 * 
 * Processes seat selection from booking.php, validates seat availability,
 * and stores pending booking in session for payment processing.
 * 
 * @route: bookings.php (POST from booking.php form)
 * @method: POST
 * @requires: includes/auth.php, includes/config.php, includes/seat-management.php
 * 
 * @post-fields: movie_id, date, time, theater, selectedSeats
 * @session-sets: pending_booking (temporary booking data for payment)
 * @redirects: 
 *   - Success → payment.php
 *   - Failure → booking.php?movie_id=... (with error in session)
 * 
 * @db-checks: 
 *   - Verify showtime exists in showtimes table
 *   - Check selected seats against existing bookings (non-cancelled)
 * @validation: max 10 seats per booking, seats must be available
 * @price-calculation: PHP 250 per seat
 * 
 * @see booking.php (form source)
 * @see payment.php (redirect target on success)
 * @see cancel-booking.php (user cancellation)
 */

require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

// Only accept POST requests; redirect GET requests to homepage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Extract and sanitize form inputs
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$theater = trim($_POST['theater'] ?? '');
$selected_seats = trim($_POST['selectedSeats'] ?? '');

$error = '';

// Validate all required fields are present
if ($movie_id <= 0 || !$date || !$time || !$theater || !$selected_seats) {
    $error = 'Missing required fields.';
} else {

    // Verify showtime exists in database
    $stmt = $conn->prepare('SELECT id FROM showtimes WHERE movie_id=? AND date=? AND time=? AND theater=?');
    $stmt->bind_param('isss', $movie_id, $date, $time, $theater);
    $stmt->execute();

    if (!$stmt->get_result()->num_rows) {
        $error = 'Invalid showtime.';
        $stmt->close();
    } else {
        $stmt->close();

        // Parse and validate seat selection
        $seats_array = array_unique(array_filter(array_map('trim', explode(',', $selected_seats))));

        // Check seat count constraints
        if (count($seats_array) === 0 || count($seats_array) > 10) {
            $error = 'Invalid seat selection.';
        } else {

            // Get all currently occupied seats for this showtime
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

            // Check each selected seat against occupied seats
            foreach ($seats_array as $seat) {
                if (in_array($seat, $occupied_seats)) {
                    $error = 'Seat already taken: ' . $seat;
                    break;
                }
            }

            // If all seats available, calculate price and store in session
            if (!$error) {
                // Price: PHP 250 per seat
                $total_price = count($seats_array) * 250;
                $seats_str = implode(',', $seats_array);

                // Get movie title for display
                $stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
                $stmt->bind_param("i", $movie_id);
                $stmt->execute();
                $movie = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Store pending booking in session for payment page
                $_SESSION['pending_booking'] = [
                    'movie_id' => $movie_id,
                    'movie_title' => $movie['title'] ?? 'Unknown Movie',
                    'date' => $date,
                    'time' => $time,
                    'theater' => $theater,
                    'seats' => $seats_str,
                    'total_price' => $total_price
                ];

                // Redirect to payment page
                header("Location: payment.php");
                exit;
            }
        }
    }
}

// If validation failed, store error and redirect back to booking page
$_SESSION['booking_error'] = $error;
header('Location: booking.php?movie_id=' . $movie_id);
exit;
?>