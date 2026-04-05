<?php
/**
 * My Bookings Page
 * 
 * Displays all bookings made by the logged-in user.
 * Allows viewing details, cancelling, and permanently deleting bookings.
 * 
 * @route: my-bookings.php
 * @method: GET
 * @requires: includes/auth.php, includes/config.php, includes/public-header.php
 * 
 * @db-query: SELECT b.*, m.poster_url, m.title as movie_title FROM bookings b LEFT JOIN movies m ON b.movie_id = m.id WHERE b.user_id = ?
 * @displays: Table of user bookings with movie, date, time, theater, seats, price, status
 * @query-params-handled: cancelled=1, deleted=1, error=1 (status messages)
 * @actions:
 *   - View → confirmation.php?booking_id=...
 *   - Cancel → cancel-booking.php (POST)
 *   - Delete → delete-booking.php (POST)
 * 
 * @see confirmation.php (booking details view)
 * @see cancel-booking.php (cancellation handler)
 * @see delete-booking.php (permanent deletion handler)
 */

require_once 'includes/auth.php';
require_user();
require_once 'includes/config.php';

// Get current user ID from session
$user_id = $_SESSION['user_id'];

// Fetch all bookings for this user, ordered by creation date (newest first)
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

// Flag for empty state display
$noBookings = empty($bookings);

// Page metadata
$pageTitle = 'My Bookings - Movie Booking';
$activePage = 'my-bookings';
include 'includes/public-header.php';
?>

<?php if (isset($_GET['cancelled'])): ?>
        <div class="max-w-7xl mx-auto px-4 md:px-8 pt-24 mb-4">
            <div class="bg-emerald-900/50 border border-emerald-700 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-emerald-300">Booking cancelled successfully!</p>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="max-w-7xl mx-auto px-4 md:px-8 pt-24 mb-4 success-notification">
            <div class="bg-red-900/50 border border-red-700 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <p class="text-red-300">Booking permanently deleted!</p>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="max-w-7xl mx-auto px-4 md:px-8 pt-24 mb-4">
            <div class="bg-red-900/50 border border-red-700 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-300">Failed to cancel or delete booking. Please try again.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 pt-12">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white mb-1">My Bookings</h1>
            <p class="text-gray-400">View and manage your upcoming tickets</p>
        </div>

        <?php if ($noBookings): ?>
        <div class="bg-neutral-800 rounded-xl p-12 text-center border border-neutral-700">
            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <h2 class="text-xl font-bold text-white mb-2">No Bookings Yet</h2>
            <p class="text-gray-400 mb-6">Book your first movie ticket to see it here.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="index.php" class="bg-netflix-red hover:bg-red-700 text-white px-6 py-2 rounded font-semibold transition-all duration-200">Browse Movies</a>
                <a href="booking.php" class="bg-neutral-700 hover:bg-neutral-600 text-white px-6 py-2 rounded font-semibold transition-all duration-200">New Booking</a>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-neutral-800 rounded-xl border border-neutral-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Movie</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Theater</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Seats</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-700">
                        <?php foreach ($bookings as $booking): 
                        ?>
                        <tr class="hover:bg-neutral-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm bg-neutral-700 px-2 py-1 rounded text-gray-300">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($booking['poster_url']): ?>
                                <div class="flex items-center gap-2">
                                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="" class="w-10 h-14 object-cover rounded">
                                    <span class="text-white font-medium"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-white font-medium"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-300">
                                <div><?php echo date('M j, Y', strtotime($booking['date'])); ?></div>
                                <div class="text-gray-400"><?php echo date('g:i A', strtotime($booking['time'])); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-900/50 text-blue-300 border border-blue-700"><?php echo htmlspecialchars($booking['theater']); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                        $seats = array_filter(explode(',', $booking['seats'] ?? ''));
                                        foreach ($seats as $seat):
                                            $seat = trim($seat);
                                    ?>
                                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-900/50 text-blue-300 border border-blue-700">
                                            <?php echo htmlspecialchars($seat); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-white">₱<?php echo number_format($booking['price'], 2); ?></td>
                            <td class="px-4 py-3 text-sm">
                                <a href="confirmation.php?booking_id=<?php echo (int)$booking['id']; ?>" class="text-gray-300 hover:text-white font-medium mr-3">View</a>
                                
                                <?php if ($booking['status'] !== 'Cancelled'): ?>
                                <form method="POST" action="cancel-booking.php" style="display:inline;" onsubmit="return confirm('Cancel booking #<?php echo $booking['id']; ?>?');">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 font-medium mr-2 bg-transparent border-none cursor-pointer">Cancel</button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="delete-booking.php" style="display:inline;" onsubmit="return confirm('Permanently delete booking #<?php echo $booking['id']; ?>? This action cannot be undone.');">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-400 p-1 rounded hover:bg-red-900/30 transition-colors bg-transparent border-none cursor-pointer" title="Permanently Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
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

<?php include 'includes/public-footer.php'; ?>
