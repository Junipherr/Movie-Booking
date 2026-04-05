<?php 
/**
 * Payment Page
 * 
 * Displays booking summary and processes payment for confirmed bookings.
 * Supports both new booking (from session) and existing pending bookings.
 * 
 * @route: payment.php or payment.php?booking_id={id}
 * @method: GET (display), POST (process payment)
 * @requires: includes/auth.php, includes/config.php, includes/seat-management.php
 * 
 * @query-param: booking_id (int, optional) - For existing pending bookings
 * @session-reads: pending_booking (new booking data from bookings.php)
 * @post-fields: action (pay|cancel), card_number, expiry, cvv
 * @db-ops: 
 *   - INSERT into bookings table (new booking)
 *   - UPDATE bookings set status='Paid' (existing pending)
 * @redirects: 
 *   - Payment success → confirmation.php?booking_id=...
 *   - Payment cancel → index.php
 * 
 * @note: Demo mode - any card number works, no real payment processing
 * 
 * @see bookings.php (creates pending_booking in session)
 * @see confirmation.php (displays after successful payment)
 * @see cancel-booking.php (user cancellation handler)
 */

require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

// Get booking ID from URL if provided
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking = null;
$error = '';
$success = '';

// For existing booking (pending payment), fetch from database
if ($booking_id > 0) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT b.*, m.poster_url FROM bookings b LEFT JOIN movies m ON b.movie_id = m.id WHERE b.id = ? AND b.user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        $error = 'Booking not found, expired, or already processed.';
    }
} 
// For new booking from session (created by bookings.php)
elseif (isset($_SESSION['pending_booking'])) {
    $pending = $_SESSION['pending_booking'];
    
    // Get movie details for display
    $stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
    $stmt->bind_param("i", $pending['movie_id']);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($movie) {
        // Build booking display data from session
        $booking = [
            'id' => 0,
            'movie_id' => $pending['movie_id'],
            'movie_title' => $movie['title'],
            'date' => $pending['date'],
            'time' => $pending['time'],
            'theater' => $pending['theater'],
            'seats' => $pending['seats'],
            'price' => $pending['total_price'],
            'user_id' => $_SESSION['user_id'],
            'pending_data' => $pending
        ];
    } else {
        $error = 'Movie not found.';
    }
} 

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    
    // Process payment - mark existing pending booking as Paid
    if ($_POST['action'] === 'pay') {
        $pending = $_SESSION['pending_booking'] ?? null;
        
        if ($booking_id > 0) {
            // Update existing pending booking
            $stmt = $conn->prepare("UPDATE bookings SET status = 'Paid' WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->bind_param("ii", $booking_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                header("Location: confirmation.php?booking_id=" . $booking_id);
                exit;
            } else {
                $error = 'Payment failed. Please try again.';
            }
        } elseif ($pending) {
            // Insert new booking record with Paid status
            $stmt = $conn->prepare('
                INSERT INTO bookings 
                (user_id, movie_id, movie_title, date, time, theater, seats, price, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Paid", NOW())
            ');
            $stmt->bind_param(
                'iisssssd',
                $user_id,
                $pending['movie_id'],
                $pending['movie_title'],
                $pending['date'],
                $pending['time'],
                $pending['theater'],
                $pending['seats'],
                $pending['total_price']
            );
            
            if ($stmt->execute()) {
                $new_booking_id = $conn->insert_id;
                $stmt->close();
                
                // Mark seats as occupied in database
                markSeatsOccupied($conn, $new_booking_id, $pending['movie_id'], $pending['theater'], $pending['seats'], $pending['date'], $pending['time']);
                
                // Clear pending booking from session
                unset($_SESSION['pending_booking']);
                
                // Redirect to confirmation page
                header("Location: confirmation.php?booking_id=" . $new_booking_id);
                exit;
            } else {
                $error = 'Booking creation failed. Please try again.';
            }
        } else {
            $error = 'No pending booking found.';
        }
    } 
    // Cancel payment - release seats and remove pending booking
    elseif ($_POST['action'] === 'cancel') {
        if ($booking_id > 0) {
            releaseSeats($conn, $booking_id);
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->bind_param("ii", $booking_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['pending_booking']);
        header("Location: index.php");
        exit;
    }
}

// Set page metadata and include header
$pageTitle = 'Payment - Movie Booking';
$activePage = '';
include 'includes/public-header.php';
?>

    <div class="max-w-4xl mx-auto px-4 py-8 pt-24">
        <?php if ($error): ?>
        <div class="bg-neutral-800 border border-red-700 rounded-xl p-8 text-center">
            <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($error); ?></h2>
            <a href="index.php" class="inline-block bg-netflix-red hover:bg-red-700 text-white px-6 py-2 rounded font-semibold transition-all duration-200">Back to Movies</a>
        </div>
        <?php elseif ($booking): ?>
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Booking Summary -->
            <div class="lg:row-span-2">
                <div class="bg-neutral-800 rounded-xl p-6 shadow-lg border border-neutral-700 sticky top-24">
                    <h2 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-netflix-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo $booking_id > 0 ? 'Booking #' . (int)$booking['id'] : 'Confirm Booking'; ?>
                    </h2>
                    <?php 
                    $poster_url = $booking['poster_url'] ?? '';
                    if (empty($poster_url) && isset($booking['movie_id'])) {
                        $stmt = $conn->prepare("SELECT poster_url FROM movies WHERE id = ?");
                        $stmt->bind_param("i", $booking['movie_id']);
                        $stmt->execute();
                        $movie_data = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        $poster_url = $movie_data['poster_url'] ?? '';
                    }
                    if ($poster_url): ?>
                    <img src="<?php echo htmlspecialchars($poster_url); ?>" alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="w-full h-48 object-cover rounded-lg shadow-lg mb-4">
                    <?php endif; ?>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-gray-400">Movie:</span> <span class="text-white"><?php echo htmlspecialchars($booking['movie_title']); ?></span></div>
                        <div><span class="text-gray-400">Date:</span> <span class="text-white"><?php echo date('M j, Y', strtotime($booking['date'])); ?></span></div>
                        <div><span class="text-gray-400">Time:</span> <span class="text-white"><?php echo date('g:i A', strtotime($booking['time'])); ?></span></div>
                        <div><span class="text-gray-400">Theater:</span> <span class="text-white"><?php echo htmlspecialchars($booking['theater']); ?></span></div>
                        <div><span class="text-gray-400">Seats:</span> <span class="text-white"><?php echo htmlspecialchars($booking['seats']); ?> (<?php echo count(array_filter(explode(',', $booking['seats']))); ?>)</span></div>
                        <div class="mt-4 p-3 bg-neutral-900 rounded-lg text-center">
                            <span class="text-gray-400">Total:</span>
                            <span class="text-2xl font-bold text-netflix-red ml-2">₱<?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div>
                <div class="bg-neutral-800 rounded-xl p-6 shadow-lg border border-neutral-700">
                    <h3 class="text-lg font-bold text-white mb-4 text-center flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Secure Payment
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="pay">
                        <?php if ($booking_id > 0): ?>
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Card Number</label>
                            <input type="text" name="card_number" placeholder="Any card number works" class="w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all" autocomplete="cc-number">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Expiry Date</label>
                                <input type="text" name="expiry" placeholder="Any date works" class="w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all" autocomplete="cc-exp">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">CVV</label>
                                <input type="text" name="cvv" placeholder="Any CVV works" class="w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all" autocomplete="cc-csc">
                            </div>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-netflix-red hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12"></path>
                                </svg>
                                Pay ₱<?php echo number_format($booking['price'], 2); ?>
                            </button>
                        </div>
                        <div class="pt-2">
                            <button type="submit" name="action" value="cancel" class="w-full bg-neutral-700 hover:bg-neutral-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">Cancel Booking</button>
                        </div>
                    </form>
                    <p class="text-xs text-gray-500 mt-3 text-center">Demo mode • No real charge</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php $conn->close(); ?>
