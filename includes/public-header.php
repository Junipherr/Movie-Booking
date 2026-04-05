<?php
/**
 * Public Page Header Component
 * 
 * Common HTML header with navigation bar for all public-facing pages.
 * Provides user authentication status and navigation menu.
 * 
 * Required before use:
 * - $pageTitle: Page title to display in browser tab
 * - $activePage: Current page identifier for menu highlighting (optional)
 * 
 * @requires: includes/config.php (database), includes/auth.php (session)
 * 
 * @used-by: index.php, login.php, register.php, movie-details.php, 
 *           booking.php, payment.php, confirmation.php, my-bookings.php
 * 
 * @see includes/public-footer.php (complementary footer)
 * @see includes/admin-header.php (admin dashboard header)
 */

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Movie Booking'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#e50914',
                        'netflix-red': '#e50914',
                        'netflix-dark': '#141414',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23e50914'/><text y='.9em' font-size='90'>🎬</text></svg>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-neutral-950 min-h-screen text-white flex flex-col">
    <main class="flex-grow">
    <!-- CineMovie Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-b from-black/90 to-transparent transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <div class="flex items-center gap-8">
                    <a href="index.php" class="text-netflix-red text-2xl md:text-3xl font-bold tracking-tight">CineMovie</a>
                    <div class="hidden md:flex items-center gap-6">
                        <a href="index.php" class="text-sm <?php echo ($activePage ?? '') === 'home' ? 'text-white font-semibold' : 'text-gray-300 hover:text-white'; ?> transition-colors">Home</a>
                        <?php if ($isLoggedIn): ?>
                        <a href="my-bookings.php" class="text-sm <?php echo ($activePage ?? '') === 'my-bookings' ? 'text-white font-semibold' : 'text-gray-300 hover:text-white'; ?> transition-colors">My Bookings</a>
                        <?php else: ?>
                        <a href="login.php" class="text-sm text-gray-300 hover:text-white transition-colors">Bookings</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($isLoggedIn): ?>
                    <div class="relative" id="userDropdown">
                        <button id="profileBtn" class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            <span class="hidden sm:inline text-sm"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="dropdownMenu" class="absolute right-0 mt-2 w-56 bg-neutral-800 border border-neutral-700 rounded-lg shadow-lg hidden">
                            <div class="p-3 border-b border-neutral-700">
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>
                            <a href="my-bookings.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-neutral-700 transition-colors">My Bookings</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-neutral-700 transition-colors">Logout</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="bg-netflix-red hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded transition-all duration-200">Login</a>
                    <?php endif; ?>
                    <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-300 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileBtn');
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (profileBtn && dropdownMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
