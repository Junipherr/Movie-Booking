<?php
/**
 * Delete Booking Handler
 * 
 * Permanently removes a booking from the database.
 * Differs from cancel-booking.php - this is permanent deletion, not status change.
 * Releases seats before removing the booking record.
 * 
 * @route: delete-booking.php (POST only)
 * @method: POST
 * @requires: includes/auth.php, includes/config.php, includes/seat-management.php
 * 
 * @post-fields: booking_id
 * @db-ops: DELETE FROM bookings WHERE id=? AND user_id=?
 * @seat-ops: releaseSeats() - marks seats as available before deletion
 * @redirects: my-bookings.php?deleted=1 (success) or my-bookings.php?error=1 (failure)
 * @warning: This action cannot be undone - booking is permanently removed
 * 
 * @see my-bookings.php (UI with delete button)
 * @see cancel-booking.php (soft delete - changes status to Cancelled)
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
        // Verify ownership (any status allowed - cancelled bookings can also be deleted)
        $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $booking_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($check_result) {
            // Release seats first
            releaseSeats($conn, $booking_id);
            
            // Permanently delete the booking record
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $booking_id, $user_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                // Success redirect with status message
                header('Location: my-bookings.php?deleted=1');
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

