<?php 
require_once 'includes/auth.php';
require_admin(); 
require_once 'includes/config.php';
require_once 'includes/seat-management.php';

// AJAX details endpoint
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("
        SELECT b.*, u.name as user_name, u.email 
        FROM bookings b LEFT JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    echo json_encode([
        'success' => $booking !== null,
        'booking' => $booking ?: []
    ]);
    exit;
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = trim($_POST['status']);
    
    if ($booking_id > 0 && in_array($status, ['Pending', 'Confirmed', 'Cancelled'])) {
        // If cancelling, fetch current status to check if seats need to be released
        $fetch_stmt = $conn->prepare("SELECT status FROM bookings WHERE id = ?");
        $fetch_stmt->bind_param("i", $booking_id);
        $fetch_stmt->execute();
        $current = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();
        
        // Release seats if status is changing to Cancelled
        if ($status === 'Cancelled' && $current && $current['status'] !== 'Cancelled') {
            releaseSeats($conn, $booking_id);
        }
        
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Booking #$booking_id status updated to '" . ucfirst($status) . "' successfully!";
            } else {
                $error = "No changes made. Booking #$booking_id not found or status unchanged.";
            }
        } else {
            $error = "Update failed: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Invalid booking ID or status.";
    }
}

$pageTitle = 'Bookings - Admin';
$pageActiveNav = 'bookings';
$pageH1 = 'Bookings Management';
?>
<?php include 'includes/admin-header.php'; ?>

<?php if ($message): ?>
    <div class="mb-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-emerald-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <div>
                <p class="text-emerald-800 font-semibold"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-6">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-red-800 font-semibold"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

            <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-lg border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">User</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Movie</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Theater</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            require_once 'includes/config.php';
                            $stmt = $conn->prepare("
                                SELECT b.*, u.name as user_name, u.email 
                                FROM bookings b 
                                LEFT JOIN users u ON b.user_id = u.id 
                                ORDER BY b.created_at DESC
                            ");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $hasBookings = false;
                            while ($booking = $result->fetch_assoc()): 
                                $hasBookings = true;
                                $statusClass = $booking['status'] === 'Confirmed' ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : ($booking['status'] === 'Cancelled' ? 'bg-red-100 text-red-800 ring-red-200' : 'bg-yellow-100 text-yellow-800 ring-yellow-200');
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-sm font-bold text-gray-900">#<?php echo str_pad($booking['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($booking['user_name'] ?: $booking['email']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700"><?php echo date('M j, Y', strtotime($booking['date'])); ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700"><?php echo date('g:i A', strtotime($booking['time'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800 ring-1 ring-blue-200 shadow-sm"><?php echo htmlspecialchars($booking['theater']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-4 py-2 rounded-full text-sm font-bold ring-2 ring-inset <?php echo $statusClass; ?> shadow-lg"><?php echo ucfirst($booking['status']); ?></span>
                                </td>
                                <td class="px-6 py-4 font-bold text-xl text-gray-900">₱<?php echo number_format($booking['price'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button onclick="viewDetails(<?php echo (int)$booking['id']; ?>)" 
                                                class="px-6 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all text-sm whitespace-nowrap">
                                            View
                                        </button>
                                        <form method="POST" class="flex-1 sm:w-auto" style="min-width: 140px;" onsubmit="return confirmStatusChange(this)">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <select name="status" class="w-full text-sm border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all" onchange="this.form.submit()">
                                                <option value="Pending" <?php echo $booking['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Confirmed" <?php echo $booking['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="Cancelled" <?php echo $booking['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; 
                            $stmt->close();
                            ?>
                            <?php if (!$hasBookings): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <div class="space-y-4">
                                        <svg class="w-20 h-20 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <h3 class="text-2xl font-bold text-gray-900">No Bookings Yet</h3>
                                        <p class="text-lg text-gray-600 max-w-md mx-auto">Book some test tickets as a regular user to populate this table and test the admin actions.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid md:grid-cols-4 gap-8 mt-12">
                <?php
                $stats = [
                    'confirmed' => (int)$conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'Confirmed' AND DATE(created_at) = CURDATE()")->fetch_row()[0],
                    'pending' => (int)$conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetch_row()[0],
                    'revenue' => $conn->query("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetch_row()[0],
                    'total' => (int)$conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0]
                ];
                ?>
                <div class="group bg-gradient-to-br from-emerald-400/20 to-emerald-500/20 border-2 border-emerald-200/50 backdrop-blur-xl rounded-3xl p-8 text-center hover:shadow-2xl transition-all hover:-translate-y-1">
                    <div class="text-4xl font-black text-emerald-700 mb-2 group-hover:scale-110 transition-transform"><?php echo $stats['confirmed']; ?></div>
                    <div class="text-emerald-800 font-bold">Confirmed Today</div>
                </div>
                <div class="group bg-gradient-to-br from-yellow-400/20 to-yellow-500/20 border-2 border-yellow-200/50 backdrop-blur-xl rounded-3xl p-8 text-center hover:shadow-2xl transition-all hover:-translate-y-1">
                    <div class="text-4xl font-black text-yellow-700 mb-2 group-hover:scale-110 transition-transform"><?php echo $stats['pending']; ?></div>
                    <div class="text-yellow-800 font-bold">Pending Review</div>
                </div>
                <div class="group bg-gradient-to-br from-gray-400/20 to-gray-500/20 border-2 border-gray-200/50 backdrop-blur-xl rounded-3xl p-8 text-center hover:shadow-2xl transition-all hover:-translate-y-1">
                    <div class="text-4xl font-black text-gray-900 mb-2 group-hover:scale-110 transition-transform">₱<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="text-gray-800 font-bold">Today's Revenue</div>
                </div>
                <div class="group bg-gradient-to-br from-blue-400/20 to-blue-500/20 border-2 border-blue-200/50 backdrop-blur-xl rounded-3xl p-8 text-center hover:shadow-2xl transition-all hover:-translate-y-1">
                    <div class="text-4xl font-black text-blue-700 mb-2 group-hover:scale-110 transition-transform"><?php echo $stats['total']; ?></div>
                    <div class="text-blue-800 font-bold">Total Bookings</div>
                </div>
            </div>

        </main>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 z-50 hidden bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="relative bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl max-w-2xl md:max-w-3xl lg:max-w-4xl w-full max-h-[90vh] m-4 border border-gray-200/60 overflow-hidden transform transition-transform duration-300 ease-out hover:scale-[1.005]">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 bg-indigo-50/80">
                <h2 id="modalTitle" class="text-2xl font-bold text-slate-900">Loading...</h2>
                <button type="button" onclick="document.getElementById('bookingModal').classList.add('hidden')" class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-white shadow-sm border border-gray-200 text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close modal">✕</button>
            </div>
            <div id="modalContent" class="p-4 md:p-6 space-y-4 md:space-y-5 bg-white overflow-y-auto max-h-[70vh]">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <script>
        async function viewDetails(id) {
            const modal = document.getElementById('bookingModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="flex items-center justify-center p-12 text-gray-500"><svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Loading details...</div>';

            try {
                const response = await fetch(`admin-bookings.php?action=details&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const booking = data.booking;
                    const statusColor = booking.status === 'Confirmed' ? 'emerald' : booking.status === 'Cancelled' ? 'red' : 'yellow';
                    
                    document.getElementById('modalTitle').textContent = `Booking #${booking.id}`;
                    content.innerHTML = `\n                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">\n                            <div>\n                                <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl inline-block shadow-lg">🎬 Movie Details</h3>\n                                <div class="bg-gradient-to-br from-blue-50 p-4 md:p-6 lg:p-8 rounded-2xl md:rounded-3xl border-2 border-blue-200 shadow-lg md:shadow-xl">\n                                    <div class="text-xl md:text-2xl lg:text-3xl font-black text-gray-900 mb-3 md:mb-4">${booking.movie_title}</div>\n                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 text-base md:text-lg mb-4 md:mb-6">\n                                        <div class="text-left"><strong>Date:</strong> ${new Date(booking.date).toLocaleDateString()}</div>\n                                        <div class="text-left md:text-right"><strong>Time:</strong> ${booking.time}</div>\n                                        <div class="text-left md:col-span-2"><strong>Theater:</strong> ${booking.theater}</div>\n                                    </div>\n                                </div>\n                            </div>\n                            <div>\n                                <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl inline-block shadow-lg">👤 Customer</h3>\n                                <div class="bg-gradient-to-br from-emerald-50 p-4 md:p-6 lg:p-8 rounded-2xl md:rounded-3xl border-2 border-emerald-200 shadow-lg md:shadow-xl">\n                                    <div class="text-lg md:text-2xl font-bold text-gray-900 mb-3 md:mb-4">${booking.user_name || booking.email}</div>\n                                    <div class="text-base md:text-lg text-gray-700 mb-2">User ID: <span class="font-mono font-bold">${booking.user_id}</span></div>\n                                </div>\n                            </div>\n                        </div>\n                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 md:gap-8">\n                            <div>\n                                <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4 bg-gradient-to-r from-${statusColor}-500 to-${statusColor}-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl inline-block shadow-lg">📊 Status & Price</h3>\n                                <div class="bg-gradient-to-br from-${statusColor}-50 p-4 md:p-6 lg:p-8 rounded-2xl md:rounded-3xl border-2 border-${statusColor}-200 shadow-lg md:shadow-xl">\n                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:mb-4 md:mb-6">\n                                        <span class="px-3 md:px-4 md:px-6 py-2 md:py-3 rounded-xl md:rounded-2xl text-base md:text-lg font-black ring-2 md:ring-4 ring-inset ring-${statusColor}-200/50 bg-${statusColor}-100 shadow-lg md:shadow-2xl">${booking.status}</span>\n                                    </div>\n                                    <div class="text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-800 text-transparent bg-clip-text">\n                                        ₱${parseFloat(booking.price).toFixed(2)}\n                                    </div>\n                                </div>\n                            </div>\n                            <div>\n                                <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4 bg-gradient-to-r from-purple-500 to-pink-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl inline-block shadow-lg">⚡ Actions</h3>\n                                <div class="space-y-2 md:space-y-3">\n                                    <a href="confirmation.php?booking_id=${booking.id}" target="_blank" class="block w-full bg-gradient-to-r from-primary to-blue-600 hover:from-blue-600 hover:to-primary text-white py-2 md:py-3 px-4 md:px-6 lg:px-8 rounded-xl md:rounded-2xl font-bold text-sm md:text-base lg:text-lg shadow-xl md:shadow-2xl hover:shadow-2xl md:hover:shadow-3xl hover:-translate-y-1 transition-all text-center">\n                                        📄 View Full Invoice\n                                    </a>\n                                </div>\n                            </div>\n                        </div>\n                        <div class="mt-6 md:mt-8 pt-6 md:pt-8 border-t border-gray-200 p-4 md:p-6 bg-gray-50 rounded-xl md:rounded-2xl">\n                            <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4">📝 Booking Created</h3>\n                            <p class="text-base md:text-lg text-gray-700">${new Date(booking.created_at).toLocaleString()}</p>\n                        </div>\n                    `;
                } else {
                    content.innerHTML = '<div class="text-center p-16"><div class="w-24 h-24 mx-auto mb-4 rounded-2xl bg-red-100 flex items-center justify-center"><svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h3 class="text-2xl font-bold text-red-800 mb-2">Booking Not Found</h3><p class="text-gray-600">This booking may have been deleted.</p></div>';
                }
            } catch (e) {
                content.innerHTML = '<div class="text-center p-16"><div class="w-24 h-24 mx-auto mb-4 rounded-2xl bg-yellow-100 flex items-center justify-center"><svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h3 class="text-2xl font-bold text-yellow-800 mb-2">Connection Error</h3><p class="text-gray-600">Unable to load booking details. Please try again.</p></div>';
            }
        }

        function printInvoice(id) {
            window.open('confirmation.php?booking_id=' + id, '_blank').focus();
        }

        function confirmStatusChange(form) {
            const status = form.querySelector('select[name="status"]').value;
            if (status === 'Cancelled') {
                return confirm('Are you sure you want to cancel this booking?\n\nThis action cannot be undone.');
            }
            return true;
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.add('-translate-x-full');
            document.getElementById('mobileOverlay').style.display = 'none';
        }

        // Allow closing modal with Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const modal = document.getElementById('bookingModal');
                if (!modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('select[name="status"]').forEach(select => {
                select.onchange = () => select.form.submit();
            });
        });
    </script>

<?php $conn->close(); ?>
<?php include 'includes/admin-footer.php'; ?>
