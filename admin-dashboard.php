<?php 
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_admin(); 

// Fetch dashboard statistics
$totalMovies = (int)$conn->query("SELECT COUNT(*) FROM movies")->fetch_row()[0];
$totalBookings = (int)$conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$revenueToday = $conn->query("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];

// Fetch recent bookings
$recentBookings = [];
$result = $conn->query("
    SELECT b.*, u.name as user_name 
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recentBookings[] = $row;
}

$pageTitle = 'Admin Dashboard - Movie Booking';
$pageActiveNav = 'dashboard';
$pageH1 = 'Dashboard';
?>
<?php include 'includes/admin-header.php'; ?>

<!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-primary/10 rounded-xl">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Movies</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $totalMovies; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl">
                    <div class="flex items-center">
                        <div class="p-3 bg-emerald-100 rounded-xl">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 4h3m-3 4h3m-2-4l.707.707a1 1 0 001.414 0l.707-.707a1 1 0 00-1.414 0l-.707.707zm0 2a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 001.414 0l.707-.707a1 1 0 001.414 0l.707-.707z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $totalBookings; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $totalUsers; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Revenue Today</p>
                            <p class="text-3xl font-bold text-gray-900">₱<?php echo number_format($revenueToday, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (Demo) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Recent Bookings</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-4 font-semibold text-gray-900">Movie</th>
                                    <th class="text-left py-4 font-semibold text-gray-900">User</th>
                                    <th class="text-left py-4 font-semibold text-gray-900">Time</th>
                                    <th class="text-left py-4 font-semibold text-gray-900">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="py-4 font-medium text-gray-900"><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                        <td class="py-4 text-gray-700"><?php echo htmlspecialchars($booking['user_name'] ?: 'Unknown'); ?></td>
                                        <td class="py-4 text-gray-700"><?php echo date('M j, g:i A', strtotime($booking['created_at'])); ?></td>
                                        <td><span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $booking['status'] === 'Confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentBookings)): ?>
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-500">
                                            No recent bookings found. Book some tickets to see activity here.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 shadow-lg border border-white/50">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="admin-movies.php" class="block w-full bg-primary/10 border-2 border-dashed border-primary text-primary px-6 py-4 rounded-xl text-center font-semibold hover:bg-primary/20 transition-all">+ Add New Movie</a>
                        <a href="admin-bookings.php" class="block w-full bg-emerald-50 border-2 border-dashed border-emerald-300 text-emerald-700 px-6 py-4 rounded-xl text-center font-semibold hover:bg-emerald-100 transition-all">View All Bookings</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle (centralized)
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.style.display = sidebar.classList.contains('-translate-x-full') ? 'none' : 'block';
        });

        mobileOverlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.style.display = 'none';
        });
    </script>

<?php $conn->close(); ?>
<?php include 'includes/admin-footer.php'; ?>
