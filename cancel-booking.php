<?php
/**
 * Cancel Booking Handler
 * 
 * Handles user-initiated booking cancellation.
 * Releases seats back to available pool and updates booking status.
 * 
 * @route: cancel-booking.php (POST only)
 * @method: POST
 * @requires: includes/auth.php, includes/config.php, includes/seat-management.php
 * 
 * @post-fields: booking_id
 * @db-ops: UPDATE bookings SET status='Cancelled' WHERE id=? AND user_id=?
 * @seat-ops: releaseSeats() - marks seats as available in seats table
 * @redirects: my-bookings.php?cancelled=1 (success) or my-bookings.php?error=1 (failure)
 * 
 * @see my-bookings.php (UI with cancel button)
 * @see delete-booking.php (permanent deletion, different from cancel)
 * @see admin-bookings.php (admin can also cancel bookings)
 */

require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

// Only process POST requests with booking_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    if ($booking_id > 0) {
        // Verify ownership and current status before cancelling
        $check_stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $booking_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        // Only cancel if booking exists and is not already cancelled
        if ($check_result && $check_result['status'] !== 'Cancelled') {
            // Release seats back to available pool
            releaseSeats($conn, $booking_id);
            
            // Update booking status to Cancelled
            $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $booking_id, $user_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                // Success redirect with status message
                header('Location: my-bookings.php?cancelled=1');
                exit;
            } else {
                $stmt->close();
            }
        }
    }
    $conn->close();
}

// Failure redirect if anything went wrong
header('Location: my-bookings.php?error=1');
exit;
?>

