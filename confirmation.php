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
    $stmt = $conn->prepare("SELECT b.*, m.poster_url FROM bookings b LEFT JOIN movies m ON b.movie_id = m.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();

$pageTitle = 'Booking Confirmed - Movie Booking';
$activePage = '';
include 'includes/public-header.php';
?>

    <!-- Clear booking cache after successful confirmation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
      sessionStorage.removeItem('selectedSeats');
      sessionStorage.removeItem('selectedShow');

      async function downloadTicket() {
        const ticketContent = document.querySelector('.bg-neutral-800.rounded-xl.shadow-lg');
        if (!ticketContent) return;

        try {
          const { jsPDF } = window.jspdf;
          const canvas = await html2canvas(ticketContent, {
            backgroundColor: '#1a1a1a',
            scale: 2,
            useCORS: true
          });

          const imgData = canvas.toDataURL('image/png');
          const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: [80, 150]
          });

          const imgWidth = 70;
          const imgHeight = (canvas.height * imgWidth) / canvas.width;
          pdf.addImage(imgData, 'PNG', 5, 10, imgWidth, imgHeight);
          pdf.save('ticket-<?php echo (int)$booking['id']; ?>.pdf');
        } catch (err) {
          alert('Could not download ticket. Please try again.');
          console.error(err);
        }
      }
    </script>

    <div class="min-h-screen flex items-center justify-center p-4 pt-24">
    <?php if ($error || !$booking): ?>
        <div class="w-full max-w-2xl">
            <div class="bg-neutral-800 rounded-xl p-12 text-center border border-neutral-700">
                <svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($error ?: 'No Recent Booking'); ?></h2>
                <p class="text-gray-400 mb-6">Check your bookings or make a new one.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="my-bookings.php" class="bg-netflix-red hover:bg-red-700 text-white font-semibold px-6 py-2 rounded transition-all duration-200">View My Bookings</a>
                    <a href="index.php" class="bg-neutral-700 hover:bg-neutral-600 text-white font-semibold px-6 py-2 rounded transition-all duration-200">Browse Movies</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="w-full max-w-md">
            <!-- Success Card -->
            <div class="bg-neutral-800 rounded-xl shadow-lg p-8 text-center border border-neutral-700">
                <!-- Success Icon -->
                <div class="w-16 h-16 bg-netflix-red rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-white mb-2">Booking Confirmed!</h1>
                <p class="text-gray-400 mb-6">Your ticket #<?php echo (int)$booking['id']; ?> has been secured.</p>

                <!-- Ticket Summary -->
                <div class="bg-neutral-900 rounded-lg p-4 mb-6 text-left">
                    <?php if (!empty($booking['poster_url'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="w-20 h-28 object-cover rounded-lg mx-auto">
                    </div>
                    <?php endif; ?>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Movie</span>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Date</span>
                            <span class="text-white"><?php echo date('M j, Y', strtotime($booking['date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Time</span>
                            <span class="text-white"><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Theater</span>
                            <span class="text-white"><?php echo htmlspecialchars($booking['theater']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Seats</span>
                            <?php 
                            $seats_list = trim($booking['seats'] ?? '');
                            $seats_count = $seats_list ? count(array_filter(explode(',', $seats_list))) : 0;
                            ?>
                            <span class="text-white"><?php echo htmlspecialchars($seats_list) ?: 'None'; ?> (<?php echo $seats_count; ?>)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Price</span>
                            <span class="text-netflix-red font-bold">₱<?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                        <div class="flex justify-between bg-neutral-800 p-2 rounded mt-2">
                            <span class="text-gray-400 text-xs">Ticket ID</span>
                            <span class="text-white font-mono text-xs">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="my-bookings.php" class="bg-netflix-red hover:bg-red-700 text-white font-semibold px-6 py-2 rounded transition-all duration-200">My Bookings</a>
                    <a href="index.php" class="bg-neutral-700 hover:bg-neutral-600 text-white font-semibold px-6 py-2 rounded transition-all duration-200">Browse Movies</a>
                </div>

                <p class="text-xs text-gray-500 mt-4 flex items-center justify-center gap-2">
                    <span>Ticket ID #<?php echo (int)$booking['id']; ?></span>
                    <button onclick="downloadTicket()" class="text-gray-400 hover:text-green-500 transition-colors" title="Download Ticket">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </button>
                </p>
        </div>
    <?php endif; ?>
    </div>
