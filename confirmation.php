<?php 
require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';

$booking = null;
$error = '';

if (isset($_GET['booking_id'])) {
    $booking_id = (int)$_GET['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT b.*, m.poster_url FROM bookings b LEFT JOIN movies m ON b.movie_id = m.id WHERE b.id = ? AND b.user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        $error = 'Booking not found or access denied.';
    }
} 

if (!$booking && isset($_SESSION['user_id'])) {
    // Fallback to latest booking
    $stmt = $conn->prepare("SELECT b.*, m.poster_url FROM bookings b LEFT JOIN movies m ON b.movie_id = m.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Movie Booking</title>
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
        .ticket-stub {
            position: relative;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #bbf7d0;
            border-radius: 16px;
            overflow: hidden;
        }
        .ticket-stub::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -10px;
            width: 20px;
            height: 20px;
            background: #f8fafc;
            border-radius: 50%;
            transform: translateY(-50%);
            border: 2px solid #bbf7d0;
        }
        .ticket-stub::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 20px;
            height: 20px;
            background: #f8fafc;
            border-radius: 50%;
            transform: translateY(-50%);
            border: 2px solid #bbf7d0;
        }
        .ticket-perforation {
            background-image: repeating-linear-gradient(90deg, transparent, transparent 8px, rgba(0,0,0,0.1) 8px, rgba(0,0,0,0.1) 9px);
            background-size: 17px 1px;
            background-position: 0 0;
            background-repeat: repeat-x;
            height: 1px;
            margin: 12px 0;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-green-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <?php if ($error || !$booking): ?>
    <div class="w-full max-w-2xl">
        <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-12 text-center">
            <svg class="w-20 h-20 text-yellow-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h2 class="text-3xl font-bold text-yellow-900 mb-4"><?php echo htmlspecialchars($error ?: 'No Recent Booking'); ?></h2>
            <p class="text-lg text-yellow-800 mb-8">Check your bookings or make a new one.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="my-bookings.php" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 text-lg">View My Bookings</a>
                <a href="index.php" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 text-lg">Book Another</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="w-full max-w-2xl">
        <!-- Success Card -->
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-12 text-center border border-white/50">
            <!-- Success Icon -->
            <div class="w-24 h-24 bg-gradient-to-r from-emerald-400 to-green-500 rounded-3xl mx-auto mb-8 flex items-center justify-center shadow-2xl">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent mb-4">Booking Confirmed!</h1>
            <p class="text-xl text-gray-600 mb-12 leading-relaxed">Your ticket #<?php echo (int)$booking['id']; ?> has been secured. Enjoy the show!</p>

            <!-- Real Ticket Summary -->
            <div class="ticket-stub p-8 mb-12">
                <!-- Movie Poster -->
                <?php if (!empty($booking['poster_url'])): ?>
                <div class="text-center mb-6">
                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="w-24 h-36 object-cover rounded-xl shadow-lg mx-auto border-4 border-white">
                </div>
                <div class="ticket-perforation"></div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div class="flex justify-between items-center pt-4 border-t border-emerald-200">
                        <span class="text-sm font-medium text-gray-700">Movie</span>
                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Date</span>
                        <span class="font-bold text-gray-900"><?php echo date('M j, Y', strtotime($booking['date'])); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Time</span>
                        <span class="font-bold text-gray-900"><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Theater</span>
                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($booking['theater']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Seats</span>
                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($booking['seats']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Price</span>
                        <span class="font-bold text-primary">₱<?php echo number_format($booking['price'], 2); ?></span>
                    </div>
                    <div class="flex justify-between items-center bg-emerald-100 p-4 rounded-xl">
                        <span class="text-sm font-semibold text-gray-800">Ticket ID</span>
                        <span class="font-bold text-emerald-700 tracking-wide">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="ticket-perforation"></div>
                    <div class="text-center pt-6">
                        <span class="text-4xl">🎫</span>
                    </div>
                    <div class="text-xs text-emerald-700 font-medium mt-2">Status: <?php echo ucfirst($booking['status']); ?></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="my-bookings.php" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 text-lg">View My Bookings</a>
                <a href="index.php" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 text-lg">Book Another</a>
            </div>

            <p class="text-sm text-gray-500 mt-8">
                Confirmation email sent. Ticket ID #<?php echo (int)$booking['id']; ?> • Check spam if not received.
            </p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
