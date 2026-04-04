<?php
// Common public header with navbar
// Usage: After requires/config/auth, set $pageTitle = '...'; $activePage = 'home|bookings|my-bookings'; then include
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
                        primary: '#2563eb',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%232563eb'/><text y='.9em' font-size='90'>🎬</text></svg>">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md shadow-lg border-b border-gray-200/50 fixed top-0 left-0 right-0 z-[100]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16">
    <div class="flex justify-between items-center h-[64px]">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">MovieBooking</a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo ($activePage ?? '') === 'home' ? 'text-primary font-semibold' : ''; ?>">Home</a>
                    <?php if ($isLoggedIn): ?>
                    <a href="my-bookings.php" class="text-gray-700 hover:text-primary font-medium transition-colors <?php echo ($activePage ?? '') === 'my-bookings' ? 'text-primary font-semibold bg-primary/10 px-3 py-1 rounded-lg' : ''; ?>">My Bookings</a>
                    <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Bookings</a>
                    <?php endif; ?>
                    <div class="relative">
                        <button id="profileBtn" class="flex items-center space-x-2 text-gray-700 hover:text-primary font-medium transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Account'); ?></span>
                        </button>
                    </div>
                    <?php if ($isLoggedIn): ?>
                    <a href="logout.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 shadow-md hover:shadow-lg">Logout</a>
                    <?php else: ?>
                    <a href="login.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 shadow-md hover:shadow-lg">Login</a>
                    <?php endif; ?>
                </div>
                <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
