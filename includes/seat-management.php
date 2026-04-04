<?php
// Helper function to mark selected seats as occupied for a booking
require_once 'includes/config.php';

function markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $selected_seats) {
    if (empty($selected_seats)) {
        return false;
    }
    
    // Parse seat numbers
    $seats_array = array_filter(array_map('trim', explode(',', $selected_seats)));
    if (empty($seats_array)) {
        return false;
    }
    
    try {
        // Update each seat to mark as occupied
        $stmt = $conn->prepare('
            UPDATE seats 
            SET occupied = 1, booking_id = ? 
            WHERE movie_id = ? AND theater = ? AND seat_number = ?
        ');
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        foreach ($seats_array as $seat_number) {
            $stmt->bind_param('iiss', $booking_id, $movie_id, $theater, $seat_number);
            if (!$stmt->execute()) {
                throw new Exception('Failed to mark seat ' . $seat_number . ' as occupied');
            }
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log('Seat marking error: ' . $e->getMessage());
        return false;
    }
}

// Function to release seats when a booking is cancelled
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
