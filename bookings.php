<?php 
require_once 'includes/auth.php';
require_user(); 

// Handle booking submission (new PHP backend)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/config.php';
    require_once 'includes/seat-management.php';
    
    $movie_id = (int)$_POST['movie_id'] ?? 0;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $theater = trim($_POST['theater'] ?? '');
    $selectedSeats = trim($_POST['selectedSeats'] ?? '');
    $seatsArray = array_filter(explode(',', $selectedSeats));
    $seatsCount = count($seatsArray);
    $price = 12.99 * $seatsCount;
    $user_id = $_SESSION['user_id'];
    
    if ($movie_id > 0 && $date && $time && $theater && $user_id && $seatsCount >= 1 && $seatsCount <= 10 && !empty($selectedSeats)) {
        // VERIFY: Check all selected seats are still available (not booked by another user)
        $verify_stmt = $conn->prepare("
            SELECT COUNT(*) as occupied_count 
            FROM seats 
            WHERE movie_id = ? AND theater = ? AND seat_number IN (" . implode(',', array_fill(0, count($seatsArray), '?')) . ") AND occupied = 1
        ");
        
        // Build params for verification
        $types = 'is' . str_repeat('s', count($seatsArray));
        $verify_params = array_merge([$movie_id, $theater], $seatsArray);
        $verify_stmt->bind_param($types, ...$verify_params);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result()->fetch_assoc();
        $verify_stmt->close();
        
        // If any seats are occupied, abort
        if ($verify_result['occupied_count'] > 0) {
            $error = "Some of the seats you selected are no longer available. They may have been booked by another user. Please refresh and try different seats.";
        } else {
            // Fetch movie title for denormalization
            $stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
            $stmt->bind_param("i", $movie_id);
            $stmt->execute();
            $movie = $stmt->get_result()->fetch_assoc();
            $movie_title = $movie['title'] ?? 'Unknown Movie';
            $stmt->close();
            
            // Insert booking with selected seats
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, movie_id, movie_title, date, time, theater, seats, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("iisssssd", $user_id, $movie_id, $movie_title, $date, $time, $theater, $selectedSeats, $price);
            
            if ($stmt->execute()) {
                $booking_id = $conn->insert_id;
                $stmt->close();
                
                // Mark selected seats as occupied and link to this booking
                if (markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $selectedSeats)) {
                    // Seats marked successfully
                    $conn->close();
                    header("Location: payment.php?booking_id=" . $booking_id);
                    exit;
                } else {
                    // Failed to mark seats, rollback booking
                    $delete_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
                    $delete_stmt->bind_param("i", $booking_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    $error = "Failed to reserve seats. Please try again.";
                }
            } else {
                $error = "Booking failed: " . $conn->error;
            }
        }
    } else {
        $error = "Invalid booking data. Please select between 1-10 seats.";
    }
    
    if (isset($error)) {
        // Store error in session to display to user
        $_SESSION['booking_error'] = $error;
        // Redirect back to booking page
        if (isset($_POST['movie_id'])) {
            header("Location: booking.php?movie_id=" . $_POST['movie_id'] . "&error=1");
            exit;
        }
    }
}

// Load from session for display (fallback if direct access)
$movie = [];
$show = [];
$booking_error = $_SESSION['booking_error'] ?? '';
if (isset($_GET['error']) && $booking_error) {
    unset($_SESSION['booking_error']); // Clear after displaying
}

if (isset($_GET['movie_id'])) {
    require_once 'includes/config.php';
    $movie_id = (int)$_GET['movie_id'];
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Movie Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%232563eb'/><text y='.9em' font-size='90'>🎬</text></svg>" type="image/svg+xml">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md shadow-lg border-b border-gray-200/50 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">MovieBooking</a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Home</a>
                    <a href="bookings.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Bookings</a>
                    <div class="relative">
                        <button id="profileBtn" class="flex items-center space-x-2 text-gray-700 hover:text-primary">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            <span>Profile</span>
                        </button>
                    </div>
                    <a href="logout.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg">Logout</a>
                </div>
                <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <?php if (isset($error)): ?>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center max-w-2xl mx-auto">
            <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-2xl font-bold text-red-900 mb-4">Booking Error</h2>
            <p class="text-lg text-red-800 mb-8"><?php echo htmlspecialchars($error); ?></p>
            <a href="index.php" class="bg-primary hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">Back to Movies</a>
        </div>
    </div>
    <?php else: ?>
        <?php
        // Success redirect already handled above - this block should not execute
        header("Location: index.php");
        exit;
        ?>
    <?php endif; ?>
