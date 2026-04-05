<?php 
require_once 'includes/config.php';
require_once 'includes/auth.php';

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movie = null;

if ($movie_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $movie = $result->fetch_assoc();
    $stmt->close();
}

if (!$movie) {
    header("Location: index.php");
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<?php 
$pageTitle = $movie['title'] . ' - Movie Booking';
$activePage = 'home';
include 'includes/public-header.php';
?>

    <!-- Movie Detail Hero -->
    <div class="relative min-h-[60vh] -mt-16 overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0">
            <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/70 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-neutral-950/90 via-neutral-950/40 to-transparent"></div>
        </div>

        <!-- Content -->
        <div class="relative max-w-7xl mx-auto px-4 md:px-8 pt-32 pb-12">
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Poster -->
                <div class="flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-48 md:w-56 lg:w-64 rounded-lg shadow-2xl shadow-black/50">
                </div>
                
                <!-- Info -->
                <div class="flex-1 max-w-2xl">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <div class="flex flex-wrap items-center gap-3 mb-6 text-gray-300">
                        <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded text-sm font-medium"><?php echo htmlspecialchars($movie['genre']); ?></span>
                        <span><?php echo htmlspecialchars($movie['duration']); ?></span>
                    </div>
                    
                    <p class="text-lg text-gray-200 mb-8 leading-relaxed"><?php echo htmlspecialchars($movie['description']); ?></p>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <?php if ($isLoggedIn): ?>
                        <a href="booking.php?movie_id=<?php echo $movie['id']; ?>" class="flex items-center gap-2 bg-netflix-red hover:bg-red-700 text-white font-semibold px-6 py-3 rounded transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Book Now
                        </a>
                        <?php else: ?>
                        <a href="login.php?return=movie-details.php?id=<?php echo urlencode($movie_id); ?>" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold px-6 py-3 rounded transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                            Login to Book
                        </a>
                        <?php endif; ?>
                        <button onclick="addToFavorites(<?php echo $movie['id']; ?>)" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-semibold px-6 py-3 rounded transition-all duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                            </svg>
                            Add to Favorites
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Role check - only redirect if trying to book without login (handled by PHP/conditional button)
        if (!<?php echo json_encode($isLoggedIn); ?>) {
            console.log('Guest user - booking requires login');
        }

        // Live price preview removed since seats selected visually

        function addToFavorites(movieId) {
            alert('Added to favorites! (Demo)');
        }
    </script>

<?php include 'includes/public-footer.php'; ?>

