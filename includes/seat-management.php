<?php
/**
 * Seat Management Helper Functions
 * 
 * Provides functions to manage seat availability in the Movie Booking system.
 * Handles marking seats as occupied upon booking and releasing seats on cancellation.
 * 
 * @requires: includes/config.php (database connection)
 * 
 * @used-by: bookings.php, payment.php, cancel-booking.php, delete-booking.php
 * 
 * @see booking.php:5 (includes seat-management)
 * @see payment.php:5 (includes seat-management)
 * @see cancel-booking.php:5 (includes seat-management)
 * @see delete-booking.php:5 (includes seat-management)
 */

require_once 'includes/config.php';

/**
 * Marks selected seats as occupied for a confirmed booking.
 * Updates the seats table to indicate the booking_id that reserved them.
 * 
 * @param mysqli $conn Database connection
 * @param int $booking_id ID of the booking that reserved seats
 * @param int $movie_id ID of the movie for this showtime
 * @param string $theater Theater/screen name
 * @param string $selected_seats Comma-separated seat numbers (e.g., "A1,A2,A3")
 * @param string $date Show date
 * @param string $time Show time
 * @return bool True on success (or if seats table doesn't exist)
 * 
 * @called-by: payment.php:86 (after successful payment)
 * @called-by: bookings.php (indirectly via payment flow)
 */
function markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $selected_seats, $date, $time) {
    if (empty($selected_seats)) {
        return false;
    }
    
    // Parse comma-separated seat numbers into array
    $seats_array = array_filter(array_map('trim', explode(',', $selected_seats)));
    if (empty($seats_array)) {
        return false;
    }
    
    // Check if seats table exists in database
    $result = @$conn->query("SHOW TABLES LIKE 'seats'");
    if (!$result || $result->num_rows === 0) {
        // Seats table doesn't exist - skip seat marking, booking is still valid
        return true;
    }
    
    try {
        // Update each seat row to mark as occupied with booking reference
        $stmt = $conn->prepare('
            UPDATE seats 
            SET occupied = 1, booking_id = ? 
            WHERE movie_id = ? AND theater = ? AND seat_number = ?
        ');
        
        if (!$stmt) {
            return true; // Skip if prepare fails
        }
        
        foreach ($seats_array as $seat_number) {
            $stmt->bind_param('iiss', $booking_id, $movie_id, $theater, $seat_number);
            if (!$stmt->execute()) {
                error_log('Failed to mark seat ' . $seat_number . ' as occupied');
            }
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log('Seat marking error: ' . $e->getMessage());
        return true; // Return true to not block booking
    }
}

/**
 * Releases seats when a booking is cancelled or deleted.
 * Resets occupied status and clears booking_id reference.
 * 
 * @param mysqli $conn Database connection
 * @param int $booking_id ID of the cancelled/deleted booking
 * @return bool True on success
 * 
 * @called-by: cancel-booking.php:21 (on booking cancellation)
 * @called-by: delete-booking.php:21 (on booking deletion)
 * @called-by: admin-bookings.php:45 (admin cancels booking)
 */
function releaseSeats($conn, $booking_id) {
    try {
        $stmt = $conn->prepare('
            UPDATE seats 
            SET occupied = 0, booking_id = NULL 
            WHERE booking_id = ?
        ');
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param('i', $booking_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to release seats');
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log('Seat release error: ' . $e->getMessage());
        return false;
    }
}
?>
