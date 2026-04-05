<?php
/**
 * Admin Dashboard Header Component
 * 
 * Common HTML header with sidebar navigation for admin pages.
 * Includes Tailwind CSS setup and admin-specific UI components.
 * 
 * Required before use:
 * - $pageTitle: Page title for browser tab
 * - $pageActiveNav: Active navigation item for highlighting
 * - $pageH1: Main page heading text
 * 
 * @requires: includes/auth.php (session check)
 * @used-by: admin-dashboard.php, admin-movies.php, admin-bookings.php
 * 
 * @see includes/public-header.php (public pages header)
 * @see includes/admin-footer.php (complementary footer)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$adminLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Panel'); ?></title>
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
    <script src="data.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%232563eb'/><text y='.9em' font-size='90'>🎬</text></svg>" type="image/svg+xml">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-50 min-h-screen">
    <!-- Admin Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-slate-950 shadow-2xl shadow-gray-950/50 border-r border-gray-700/50">
        <div class="flex items-center justify-between p-6 border-b border-gray-700/50">
            <h2 class="text-xl font-bold text-white">Admin Panel</h2>
        </div>
        <nav class="mt-8 px-4">
            <a href="admin-dashboard.php" class="flex items-center px-4 py-3 text-gray-200 hover:bg-gray-700/50 <?php echo ($pageActiveNav ?? '') === 'dashboard' ? 'bg-primary/10 text-primary' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                </svg>
                Dashboard
            </a>
            <a href="admin-movies.php" class="flex items-center px-4 py-3 text-gray-200 hover:bg-gray-700/50 <?php echo ($pageActiveNav ?? '') === 'movies' ? 'bg-primary/10 text-primary' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-9 0V1m10 3V1m0 3l1 1v16a2 2 0 01-2 2H6a2 2 0 01-2-2V5l1-1z"></path>
                </svg>
                Movies
            </a>
            <a href="admin-bookings.php" class="flex items-center px-4 py-3 text-gray-200 hover:bg-gray-700/50 <?php echo ($pageActiveNav ?? '') === 'bookings' ? 'bg-primary/10 text-primary' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Bookings
            </a>
            <hr class="my-4 border-gray-700/50">
            <a href="logout.php" class="flex items-center px-4 py-3 text-red-300 hover:bg-red-900/30">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Logout
            </a>
        </nav>
    </aside>

    <!-- Mobile menu overlay -->
    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="mobileOverlay"></div>

    <!-- Main Content Wrapper -->
    <div class="lg:pl-64">
        <!-- Topbar -->
        <header class="bg-gray-900/90 backdrop-blur-md shadow-lg border-b border-gray-700/50 relative z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg text-gray-200 hover:bg-gray-700/50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($pageH1 ?? 'Dashboard'); ?> <span class="text-gray-200"><?php echo htmlspecialchars($pageSubtitle ?? 'Admin'); ?></span></h1>
                    <div class="flex items-center space-x-4 relative" id="adminDropdown">
                        <button id="adminProfileBtn" class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
                            <span class="text-white text-sm hidden md:inline"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="adminDropdownMenu" class="absolute right-0 top-full mt-2 w-56 bg-gray-800 border border-gray-600 rounded-lg shadow-lg hidden z-50">
                            <div class="p-3 border-b border-gray-600">
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>
                            <a href="admin-dashboard.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors">Dashboard</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700 transition-colors">Logout</a>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const adminProfileBtn = document.getElementById('adminProfileBtn');
                            const adminDropdownMenu = document.getElementById('adminDropdownMenu');
                            if (adminProfileBtn && adminDropdownMenu) {
                                adminProfileBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    adminDropdownMenu.classList.toggle('hidden');
                                });
                                document.addEventListener('click', function(e) {
                                    if (!adminProfileBtn.contains(e.target) && !adminDropdownMenu.contains(e.target)) {
                                        adminDropdownMenu.classList.add('hidden');
                                    }
                                });
                            }
                        });
                    </script>
                </div>
            </div>
        </header>

        <!-- Page Content Starts Here -->
        <main class="p-8">
