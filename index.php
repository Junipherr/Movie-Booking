<?php 
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Fetch all movies
$stmt = $conn->prepare("SELECT * FROM movies ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
$stmt->close();
$conn->close();

// Group movies by genre (lowercase key for consistency)
$genres = [];
foreach ($movies as $movie) {
    $genre_key = strtolower($movie['genre']);
    if (!isset($genres[$genre_key])) {
        $genres[$genre_key] = [];
    }
    $genres[$genre_key][] = $movie;
}

// Featured movie for hero (first one)
$featured = !empty($movies) ? $movies[0] : null;
?>
<?php 
$pageTitle = 'Movie Booking';
$activePage = 'home';
include 'includes/public-header.php';
?>

    <!-- Hero Section -->
    <?php if ($featured): ?>
    <section class="pt-24 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/50">
                <div class="lg:flex">
                    <div class="lg:w-1/2 lg:p-12 p-8">
                        <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($featured['title']); ?></h2>
                        <p class="text-xl text-gray-600 mb-8 leading-relaxed"><?php echo htmlspecialchars(substr($featured['description'], 0, 150)); ?>...</p>
                        <a href="movie-details.php?id=<?php echo $featured['id']; ?>" class="inline-flex items-center px-8 py-4 bg-primary hover:bg-blue-700 text-white font-semibold text-lg rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
                            Book Now
                            <svg class="ml-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                    <div class="lg:w-1/2">
                        <img src="<?php echo htmlspecialchars($featured['poster_url']); ?>" alt="<?php echo htmlspecialchars($featured['title']); ?>" class="w-full h-96 lg:h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Movies by Genre -->
    <section class="px-4 sm:px-6 lg:px-8 pb-20">
        <div class="max-w-6xl mx-auto">
            <h3 class="text-3xl font-bold text-gray-900 mb-12 text-center">Now Showing
            
            <?php if (empty($genres)): ?>
                <p class="text-center text-xl text-gray-500 py-20">No movies available yet. Check back soon!</p>
            <?php else: ?>
            <div class="space-y-12">
                <?php foreach ($genres as $genre_key => $genre_movies): ?>
                <div>
                    <h4 class="text-2xl font-semibold text-gray-900 mb-8 capitalize"><?php echo ucfirst($genre_key); ?></h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($genre_movies as $movie): ?>
                        <div class="group cursor-pointer bg-white/70 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-white/50 overflow-hidden">
                            <div class="h-64 bg-gradient-to-br from-gray-100 to-gray-200">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <div class="p-6">
                                <h5 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($movie['description'], 0, 100)); ?>...</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($movie['duration']); ?></span>
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-md hover:shadow-lg transition-all duration-200">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

<?php include 'includes/public-footer.php'; ?>
