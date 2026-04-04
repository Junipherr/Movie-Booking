<?php
require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT b.*, m.poster_url, m.title as movie_title  
    FROM bookings b 
    LEFT JOIN movies m ON b.movie_id = m.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$noBookings = empty($bookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Movie Booking</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%232563eb'/><text y='.9em' font-size='90'>🎬</text></svg>" type="image/svg+xml">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
<?php
$pageTitle = 'My Bookings - Movie Booking';
$activePage = 'my-bookings';
include 'includes/public-header.php';
?>
<div class="pt-24">

<?php if (isset($_GET['cancelled'])): ?>
        <div class="mb-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-emerald-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <div>
                    <p class="text-emerald-800 font-semibold">Booking cancelled successfully!</p>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-6 success-notification">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                </svg>
                <div>
                    <p class="text-red-800 font-semibold">Booking permanently deleted!</p>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-red-800 font-semibold">Failed to cancel or delete booking. Please try again.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">My Bookings</h1>
            <p class="text-xl text-gray-600">View and manage your upcoming tickets</p>
        </div>

        <?php if ($noBookings): ?>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl p-16 text-center border border-white/50">
            <svg class="w-20 h-20 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">No Bookings Yet</h2>
            <p class="text-lg text-gray-600 mb-8">Book your first movie ticket to see it here.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="index.php" class="bg-primary hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">Browse Movies</a>
                <a href="bookings.php" class="bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">New Booking</a>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl border border-white/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Ticket</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Movie</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Theater</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Seats</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($bookings as $booking): 
                            $statusClass = match($booking['status']) {
                                'Confirmed' => 'bg-emerald-100 text-emerald-800',
                                'Cancelled' => 'bg-red-100 text-red-800',
                                default => 'bg-yellow-100 text-yellow-800'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono font-semibold text-sm bg-gray-100 px-3 py-1 rounded-full">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($booking['poster_url']): ?>
                                <div class="flex items-center space-x-3">
                                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="" class="w-12 h-16 object-cover rounded-lg">
                                    <div>
                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div><?php echo date('M j, Y', strtotime($booking['date'])); ?></div>
                                <div class="font-semibold"><?php echo date('g:i A', strtotime($booking['time'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars($booking['theater']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                        $seats = array_filter(explode(',', $booking['seats'] ?? ''));
                                        foreach ($seats as $seat):
                                            $seat = trim($seat);
                                    ?>
                                        <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-800 border border-blue-300">
                                            <?php echo htmlspecialchars($seat); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">₱<?php echo number_format($booking['price'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="confirmation.php?booking_id=<?php echo (int)$booking['id']; ?>" class="text-primary hover:text-blue-700 font-medium mr-4">View</a>
                                
                                <?php if ($booking['status'] !== 'Cancelled'): ?>
                                <form method="POST" action="cancel-booking.php" style="display:inline;" class="inline-flex items-center text-red-600 hover:text-red-700 font-medium mr-3" onsubmit="return confirm('Cancel booking #<?php echo $booking['id']; ?>?');">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="bg-transparent border-none p-0 cursor-pointer">Cancel</button>
                                </form>
                                <?php endif; ?>
                                
                                <!-- Permanent Delete - always available -->
                                <form method="POST" action="delete-booking.php" style="display:inline;" onsubmit="return confirm('Permanently delete booking #<?php echo $booking['id']; ?>? This action cannot be undone.');">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium ml-2 p-1 rounded hover:bg-red-50 transition-colors bg-transparent border-none cursor-pointer focus:outline-none" title="Permanently Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        <span class="sr-only">Delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide success message after 3 seconds
        const successMsg = document.querySelector('.success-notification');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 3000);
        }
    </script>

<?php include 'includes/public-footer.php'; ?>
