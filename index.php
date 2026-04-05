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

    <!-- Netflix Hero Section -->
    <?php if ($featured): ?>
    <section class="relative h-[85vh] min-h-[500px] overflow-hidden mt-[-4rem]">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0">
            <img src="<?php echo htmlspecialchars($featured['poster_url']); ?>" alt="<?php echo htmlspecialchars($featured['title']); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/60 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-neutral-950/80 via-transparent to-transparent"></div>
        </div>
        
        <!-- Hero Content -->
        <div class="relative h-full flex items-center">
            <div class="max-w-7xl mx-auto px-4 md:px-8 w-full pt-32">
                <div class="max-w-xl">
                    <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 drop-shadow-lg"><?php echo htmlspecialchars($featured['title']); ?></h2>
                    <p class="text-lg text-gray-200 mb-6 line-clamp-3 drop-shadow-md"><?php echo htmlspecialchars(substr($featured['description'], 0, 150)); ?>...</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="movie-details.php?id=<?php echo $featured['id']; ?>" class="flex items-center gap-2 bg-netflix-red hover:bg-red-700 text-white font-semibold px-6 py-3 rounded transition-all duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                            Play
                        </a>
                        <a href="movie-details.php?id=<?php echo $featured['id']; ?>" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold px-6 py-3 rounded transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            More Info
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Movies by Genre - Netflix Style -->
    <section class="relative z-10 pb-16 -mt-8">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            
            <?php if (empty($genres)): ?>
                <div class="text-center py-20">
                    <p class="text-xl text-gray-500">No movies available yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <?php foreach ($genres as $genre_key => $genre_movies): ?>
                <div class="mb-10">
                    <h3 class="text-xl font-semibold text-white mb-4 capitalize"><?php echo ucfirst($genre_key); ?></h3>
                    <div class="flex gap-4 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4">
                        <?php foreach ($genre_movies as $movie): ?>
                        <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="relative group flex-shrink-0 w-40 md:w-52 block">
                            <div class="relative rounded-lg overflow-hidden shadow-lg transition-all duration-300 ease-in-out group-hover:scale-105 group-hover:shadow-2xl group-hover:shadow-red-900/50">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-60 md:h-72 object-cover">
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-center p-4">
                                    <h5 class="text-white font-semibold text-center mb-2 line-clamp-2"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                    <span class="text-gray-300 text-sm"><?php echo htmlspecialchars($movie['duration']); ?></span>
                                    <span class="mt-2 bg-netflix-red text-white text-xs font-semibold px-3 py-1 rounded transition-colors">View Details</span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
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
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

<?php include 'includes/public-footer.php'; ?>
