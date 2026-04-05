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
<style>
    * { font-family: 'Inter', sans-serif; }
</style>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Movie Header -->
        <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/50">
            <div class="lg:flex">
                <div class="lg:w-1/2">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-96 lg:h-[500px] object-cover rounded-t-3xl lg:rounded-l-3xl">
                </div>
                <div class="lg:w-1/2 lg:p-12 p-8 flex flex-col justify-center">
                    <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <p class="text-2xl text-gray-600 mb-8"><?php echo htmlspecialchars($movie['genre']); ?> • <?php echo htmlspecialchars($movie['duration']); ?></p>
                    <div class="flex space-x-4 mb-8">
                        <button onclick="addToFavorites(<?php echo $movie['id']; ?>)" class="flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-semibold transition-all">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                            </svg>
                            Add to Favorites
                        </button>
                        <?php if ($isLoggedIn): ?>
                        <a href="booking.php?movie_id=<?php echo $movie['id']; ?>" class="flex-1 flex items-center justify-center px-6 py-3 bg-primary hover:bg-blue-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Book Now
                        </a>
                        <?php else: ?>
                        <a href="login.php?return=movie-details.php?id=<?php echo urlencode($movie_id); ?>" class="flex-1 flex items-center justify-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                            Login to Book
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info & Description Only -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl p-8 border border-white/50 mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($movie['title']); ?></h2>
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-8">
                <span class="bg-blue-100 text-primary px-3 py-1 rounded-full"><?php echo htmlspecialchars($movie['genre']); ?></span>
                <span><?php echo htmlspecialchars($movie['duration']); ?></span>
            </div>
            <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($movie['description']); ?></p>
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

