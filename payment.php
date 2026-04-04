<?php 
require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking = null;
$error = '';
$success = '';

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

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $booking_id > 0) {
    $user_id = $_SESSION['user_id'];
    if ($_POST['action'] === 'pay') {
        // Mock payment validation
        $card_number = trim($_POST['card_number'] ?? '');
        $expiry = trim($_POST['expiry'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');
        
        if (strlen($card_number) === 16 && strlen($cvv) === 3 && preg_match('/\d{2}\/\d{2}/', $expiry)) {
            $stmt = $conn->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->bind_param("ii", $booking_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                header("Location: confirmation.php?booking_id=" . $booking_id);
                exit;
            } else {
                $error = 'Payment failed. Please try again.';
            }
        } else {
            $error = 'Invalid payment details.';
        }
    } elseif ($_POST['action'] === 'cancel') {
        // Release seats before deleting booking
        releaseSeats($conn, $booking_id);
        
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ? AND status = 'Pending'");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Movie Booking</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-blue-50 to-emerald-50 min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Navbar -->
        <nav class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl mb-8 p-4 border border-white/50">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">MovieBooking</a>
                <div class="flex items-center space-x-4">
                    <a href="my-bookings.php" class="text-gray-700 hover:text-primary font-medium">My Bookings</a>
                    <a href="logout.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold shadow-md hover:shadow-lg transition-all">Logout</a>
                </div>
            </div>
        </nav>

        <?php if ($error): ?>
        <div class="bg-red-50 border-2 border-red-200 rounded-3xl p-8 text-center mb-8">
            <svg class="w-20 h-20 text-red-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-3xl font-bold text-red-900 mb-4"><?php echo htmlspecialchars($error); ?></h2>
            <a href="index.php" class="bg-primary text-white px-8 py-4 rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl transition-all">Back to Movies</a>
        </div>
        <?php elseif ($booking): ?>
        <div class="grid lg:grid-cols-2 gap-8 items-start">
            <!-- Booking Summary -->
            <div class="lg:row-span-2">
                <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/50">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-8 h-8 mr-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Booking #<?php echo (int)$booking['id']; ?>
                    </h2>
                    <?php if ($booking['poster_url']): ?>
                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="w-full h-64 object-cover rounded-2xl shadow-xl mb-6">
                    <?php endif; ?>
                    <div class="space-y-4 text-lg">
                        <div><strong>Movie:</strong> <?php echo htmlspecialchars($booking['movie_title']); ?></div>
                        <div><strong>Date:</strong> <?php echo date('M j, Y', strtotime($booking['date'])); ?></div>
                        <div><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['time'])); ?></div>
                        <div><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater']); ?></div>
                        <div><strong>Seats:</strong> <?php echo htmlspecialchars($booking['seats']); ?> (<?php echo count(array_filter(explode(',', $booking['seats']))); ?> seats)</div>
                        <div class="text-3xl font-bold text-primary bg-gradient-to-r from-primary/10 p-4 rounded-2xl">
                            Total: ₱<?php echo number_format($booking['price'], 2); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div>
                <div class="bg-gradient-to-br from-emerald-50 to-blue-50/50 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border border-emerald-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center flex items-center justify-center">
                        <svg class="w-8 h-8 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Secure Payment
                    </h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="pay">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Card Number</label>
                            <input type="text" name="card_number" maxlength="16" placeholder="1234 5678 9012 3456" required class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all text-lg tracking-wider" autocomplete="cc-number">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">Expiry Date</label>
                                <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all" autocomplete="cc-exp">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">CVV</label>
                                <input type="text" name="cvv" maxlength="3" placeholder="123" required class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all" autocomplete="cc-csc">
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-8 rounded-2xl shadow-2xl hover:shadow-3xl transform hover:-translate-y-1 transition-all text-lg flex items-center justify-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12"></path>
                                </svg>
                                Pay Now ₱<?php echo number_format($booking['price'], 2); ?>
                            </button>
                            <form method="POST" style="display: contents;" onsubmit="return confirm('Cancel booking #<?php echo $booking_id; ?>?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                <button type="submit" class="flex-1 bg-gradient-to-r from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600 text-white font-bold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transition-all text-lg">Cancel Booking</button>
                            </form>
                        </div>
                    </form>
                    <p class="text-xs text-gray-500 mt-6 text-center">Demo mode • No real charge • Secure checkout</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
