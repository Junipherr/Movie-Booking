<?php
require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    if ($booking_id > 0) {
        // Check ownership (any status allowed for permanent delete)
        $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $booking_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($check_result) {
            // Release seats before deleting booking
            releaseSeats($conn, $booking_id);
            
            // Permanent delete
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $booking_id, $user_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                header('Location: my-bookings.php?deleted=1');
                exit;
            } else {
                $stmt->close();
            }
        }
    }
    $conn->close();
}

header('Location: my-bookings.php?error=1');
exit;
?>

