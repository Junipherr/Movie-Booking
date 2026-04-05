<?php
/**
 * Admin Movies Management Page
 * 
 * Provides CRUD operations for movies in the Movie Booking system.
 * Supports AJAX-based add, edit, delete operations without page reload.
 * Automatically creates seats and showtimes when a new movie is added.
 * 
 * @route: admin-movies.php (GET display, POST AJAX operations)
 * @method: GET (display), POST (AJAX CRUD actions)
 * @requires: includes/auth.php (require_admin), includes/config.php, includes/admin-header.php
 * 
 * @ajax-actions:
 *   - list: Returns all movies as JSON
 *   - add: Creates new movie with auto-generated seats/showtimes
 *   - update: Updates existing movie details
 *   - delete: Removes movie and associated seats/showtimes
 * 
 * @auto-generated: 
 *   - Seats: 4 theaters × 10 rows × 12 seats = 480 seats per movie
 *   - Showtimes: 7 days × 4 theaters × 2-3 random time slots per day
 * 
 * @db-tables: movies, seats, showtimes
 * @form-fields: title, genre, duration, description, poster_url, poster_file (upload)
 * 
 * @see admin-dashboard.php (main dashboard with link to this page)
 * @see admin-bookings.php (manage bookings)
 */

require_once 'includes/auth.php';
require_once 'includes/config.php';

/**
 * JSON error response helper for AJAX operations
 * 
 * @param string $message Error message to return
 * @param int $code HTTP response code
 */
function respondJsonError($message, $code = 401) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

/**
 * Handles poster image upload - accepts file upload or URL string
 * 
 * @return string|null Relative path to uploaded image or original URL, null if neither provided
 * @used-by: AJAX add/update actions
 */
function handlePosterUpload() {
    $posterUrl = trim($_POST['poster_url'] ?? '');

    // Handle file upload
    if (!empty($_FILES['poster_file']['name'])) {
        if ($_FILES['poster_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Poster upload failed with error code: ' . $_FILES['poster_file']['error']);
        }

        // Validate image file types
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $info = pathinfo($_FILES['poster_file']['name']);
        $ext = strtolower($info['extension'] ?? '');
        if (!in_array($ext, $allowed)) {
            throw new Exception('Poster image must be JPG, PNG, GIF or WEBP.');
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename and move uploaded file
        $filename = uniqid('poster_', true) . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['poster_file']['tmp_name'], $target)) {
            throw new Exception('Failed to save poster image file.');
        }

        return 'uploads/' . $filename;
    }

    // Fall back to URL if no file uploaded
    if (!empty($posterUrl)) {
        return $posterUrl;
    }

    return null; // Allow movies without poster
}

/**
 * Automatically creates seats for a new movie in all theaters
 * Creates 480 seats: 4 theaters × 10 rows (A-J) × 12 seats per row
 * 
 * @param mysqli $conn Database connection
 * @param int $movie_id ID of the movie to create seats for
 * @throws Exception If seat creation fails
 * 
 * @called-by: AJAX add action after successful movie insertion
 */
function createSeatsForMovie($conn, $movie_id) {
    $theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];
    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    
    // Prepare seat data array
    $seats_data = [];
    
    foreach ($theaters as $theater) {
        foreach ($rows as $row) {
            for ($seat = 1; $seat <= 12; $seat++) {
                $seat_number = $row . $seat;
                $seats_data[] = [
                    'movie_id' => $movie_id,
                    'theater' => $theater,
                    'seat_number' => $seat_number
                ];
            }
        }
    }
    
    // Batch insert all seats
    try {
        $stmt = $conn->prepare('INSERT INTO seats (movie_id, theater, seat_number) VALUES (?, ?, ?)');
        if (!$stmt) {
            throw new Exception('Database error preparing seats: ' . $conn->error);
        }
        
        foreach ($seats_data as $seat) {
            $stmt->bind_param('iss', $seat['movie_id'], $seat['theater'], $seat['seat_number']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert seat: ' . $stmt->error);
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        throw new Exception('Seats creation failed: ' . $e->getMessage());
    }
}

/**
 * Automatically creates showtimes for a new movie (next 7 days)
 * Generates 2-3 random time slots per theater per day for variety
 * 
 * @param mysqli $conn Database connection
 * @param int $movie_id ID of the movie to create showtimes for
 * @throws Exception If showtime creation fails
 * 
 * @called-by: AJAX add action after successful movie and seat creation
 */
function createShowtimesForMovie($conn, $movie_id) {
    $theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];
    $times = ['10:00:00', '13:00:00', '16:00:00', '19:00:00', '22:00:00'];
    
    try {
        $stmt = $conn->prepare('INSERT INTO showtimes (movie_id, date, time, theater) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            throw new Exception('Database error preparing showtimes: ' . $conn->error);
        }
        
        // Create showtimes for next 7 days
        for ($day = 0; $day < 7; $day++) {
            $date = date('Y-m-d', strtotime("+$day days"));
            
            // Add 2-3 random times per theater per day for variety
            $selectedTimes = array_rand(array_flip($times), rand(2, 3));
            if (!is_array($selectedTimes)) {
                $selectedTimes = [$selectedTimes];
            }
            
            foreach ($theaters as $theater) {
                foreach ($selectedTimes as $time) {
                    $stmt->bind_param('isss', $movie_id, $date, $time, $theater);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to insert showtime: ' . $stmt->error);
                    }
                }
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        throw new Exception('Showtimes creation failed: ' . $e->getMessage());
    }
}

// AJAX CRUD endpoints - process POST requests with action parameter
if (!empty($_REQUEST['action'])) {
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    if (!$isAdmin) {
        respondJsonError('Unauthorized: admin login required', 401);
    }
    header('Content-Type: application/json');
    $action = $_REQUEST['action'];

    try {
        switch ($action) {
            // Return list of all movies as JSON
            case 'list':
                $result = $conn->query('SELECT * FROM movies ORDER BY id DESC');
                $movies = [];
                while ($row = $result->fetch_assoc()) {
                    $movies[] = $row;
                }
                echo json_encode(['success' => true, 'movies' => $movies]);
                break;

            // Add new movie with auto-generated seats and showtimes
            case 'add':
                $title = trim($_POST['title'] ?? '');
                $genre = trim($_POST['genre'] ?? '');
                $duration = trim($_POST['duration'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if ($title === '' || $genre === '' || $duration === '' || $description === '') {
                    throw new Exception('All fields are required.');
                }

                $poster_url = handlePosterUpload();

                // Insert movie into database
                $stmt = $conn->prepare('INSERT INTO movies (title, description, poster_url, genre, duration) VALUES (?, ?, ?, ?, ?)');
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param('sssss', $title, $description, $poster_url, $genre, $duration);
                if (!$stmt->execute()) {
                    throw new Exception('Insert failed: ' . $stmt->error);
                }
                $newId = $stmt->insert_id;
                $stmt->close();

                // Automatically create seats for all theaters
                createSeatsForMovie($conn, $newId);
                
                // Automatically create showtimes for next 7 days
                try {
                    createShowtimesForMovie($conn, $newId);
                    error_log("Showtimes created successfully for movie $newId");
                } catch (Exception $e) {
                    error_log("Failed to create showtimes for movie $newId: " . $e->getMessage());
                    throw $e; // Re-throw to show error to user
                }

                $movie = [
                    'id' => $newId,
                    'title' => $title,
                    'description' => $description,
                    'poster_url' => $poster_url,
                    'genre' => $genre,
                    'duration' => $duration,
                ];
                echo json_encode(['success' => true, 'movie' => $movie]);
                break;

            // Update existing movie details
            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $genre = trim($_POST['genre'] ?? '');
                $duration = trim($_POST['duration'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if ($id <= 0 || $title === '' || $genre === '' || $duration === '' || $description === '') {
                    throw new Exception('All fields are required for update.');
                }

                $poster_url = handlePosterUpload();
                if ($poster_url === null) {
                    // Keep existing poster URL if no new URL or file was provided during edit
                    $stmt = $conn->prepare('SELECT poster_url FROM movies WHERE id = ?');
                    if (!$stmt) {
                        throw new Exception('Database error: ' . $conn->error);
                    }
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingMovie = $result->fetch_assoc();
                    $stmt->close();
                    $poster_url = $existingMovie['poster_url'] ?? null;
                }

                $stmt = $conn->prepare('UPDATE movies SET title = ?, description = ?, poster_url = ?, genre = ?, duration = ? WHERE id = ?');
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param('sssssi', $title, $description, $poster_url, $genre, $duration, $id);
                if (!$stmt->execute()) {
                    throw new Exception('Update failed: ' . $stmt->error);
                }
                $stmt->close();

                $movie = [
                    'id' => $id,
                    'title' => $title,
                    'description' => $description,
                    'poster_url' => $poster_url,
                    'genre' => $genre,
                    'duration' => $duration,
                ];

                echo json_encode(['success' => true, 'movie' => $movie]);
                break;

            // Delete movie and all associated data (seats, showtimes)
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid id.');
                }
                
                // Delete seats associated with this movie (cascading)
                $stmt = $conn->prepare('DELETE FROM seats WHERE movie_id = ?');
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to delete seats: ' . $stmt->error);
                }
                $stmt->close();
                
                // Delete showtimes associated with this movie
                $stmt = $conn->prepare('DELETE FROM showtimes WHERE movie_id = ?');
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to delete showtimes: ' . $stmt->error);
                }
                $stmt->close();
                
                // Delete the movie
                $stmt = $conn->prepare('DELETE FROM movies WHERE id = ?');
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) {
                    throw new Exception('Delete failed: ' . $stmt->error);
                }
                $stmt->close();

                echo json_encode(['success' => true]);
                break;

            default:
                throw new Exception('Unknown action.');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $conn->close();
    exit;
}

// Page requires admin authentication
require_admin();

// Set page metadata for admin header
$pageTitle = 'Manage Movies - Admin';
$pageActiveNav = 'movies';
$pageH1 = 'Manage Movies';
?>
<?php include 'includes/admin-header.php'; ?>

            <!-- Add Movie Button Section -->
            <div class="mb-8 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-gray-900">All Movies</h3>
                <button id="addMovieBtn" class="bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">+ Add Movie</button>
            </div>

            <!-- Movies Table -->
            <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Poster</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Genre</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-5 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="moviesTableBody" class="divide-y divide-gray-200">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Movie Modal -->
    <div id="addMovieModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl p-8 w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="flex justify-between items-center mb-8">
                <h2 id="modalHeading" class="text-2xl font-bold text-gray-900">Add New Movie</h2>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form id="addMovieForm" class="space-y-6">
                <input id="movieId" name="id" type="hidden" value="">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input id="movieTitle" name="title" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Genre</label>
                        <select id="movieGenre" name="genre" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                            <option value="">Select Genre</option>
                            <option>Action</option>
                            <option>Comedy</option>
                            <option>Drama</option>
                            <option>Thriller</option>
                            <option>Sci-Fi</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                    <input id="movieDuration" name="duration" type="text" required placeholder="2h 30m" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="movieDescription" name="description" required rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary resize-vertical"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poster Image</label>
                    <input id="moviePosterFile" name="poster_file" type="file" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-700" />
                    <p class="text-xs text-gray-500 mt-1">Upload an image file or keep using URL below.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poster URL (fallback)</label>
                    <input id="moviePoster" name="poster_url" type="url" placeholder="https://example.com/poster.jpg" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button id="saveMovieBtn" type="submit" class="flex-1 bg-primary hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all">Add Movie</button>
                    <button type="button" id="cancelBtn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-all">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Client-side state
        let allMovies = [];

        async function loadMovies() {
            try {
                const res = await fetch('admin-movies.php?action=list', { method: 'GET' });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Unable to load movies.');
                }
                allMovies = data.movies;
                renderMoviesTable();
            } catch (error) {
                alert('Error loading movies: ' + error.message);
            }
        }

        function renderMoviesTable() {
            const tbody = document.getElementById('moviesTableBody');
            if (!allMovies.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500">No movies available.</td></tr>';
                return;
            }

            tbody.innerHTML = allMovies.map(movie => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-5">
                        <img src="${movie.poster_url}" alt="${movie.title}" class="w-16 h-24 object-cover rounded-lg shadow">
                    </td>
                    <td class="px-6 py-5 font-medium text-gray-900">${movie.title}</td>
                    <td class="px-6 py-5">
                        <span class="px-3 py-1 bg-blue-100 text-primary text-sm font-semibold rounded-full">${movie.genre}</span>
                    </td>
                    <td class="px-6 py-5 text-gray-700">${movie.duration}</td>
                    <td class="px-6 py-5">
                        <div class="flex items-center space-x-2">
                            <button onclick="editMovie(${movie.id})" class="text-emerald-600 hover:text-emerald-800 font-medium text-sm">Edit</button>
                            <button onclick="deleteMovie(${movie.id})" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Modal controls
        const modal = document.getElementById('addMovieModal');
        const addBtn = document.getElementById('addMovieBtn');
        const closeBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const form = document.getElementById('addMovieForm');
        const modalHeading = document.getElementById('modalHeading');
        const saveMovieBtn = document.getElementById('saveMovieBtn');
        let editingMovieId = null;

        function resetMovieModal() {
            editingMovieId = null;
            modalHeading.textContent = 'Add New Movie';
            saveMovieBtn.textContent = 'Add Movie';
            document.getElementById('movieId').value = '';
            form.reset();
        }

        function showMovieModal(movie = null) {
            if (movie) {
                editingMovieId = movie.id;
                modalHeading.textContent = 'Edit Movie';
                saveMovieBtn.textContent = 'Save Changes';
                document.getElementById('movieId').value = movie.id;
                document.getElementById('movieTitle').value = movie.title;
                document.getElementById('movieGenre').value = movie.genre;
                document.getElementById('movieDuration').value = movie.duration;
                document.getElementById('movieDescription').value = movie.description;
                document.getElementById('moviePoster').value = movie.poster_url;
            } else {
                resetMovieModal();
            }
            modal.classList.remove('hidden');
        }

        addBtn.addEventListener('click', () => showMovieModal());
        closeBtn.addEventListener('click', () => { modal.classList.add('hidden'); resetMovieModal(); });
        cancelBtn.addEventListener('click', () => { modal.classList.add('hidden'); resetMovieModal(); });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const title = document.getElementById('movieTitle').value.trim();
            const genre = document.getElementById('movieGenre').value.trim();
            const duration = document.getElementById('movieDuration').value.trim();
            const description = document.getElementById('movieDescription').value.trim();
            const poster_url = document.getElementById('moviePoster').value.trim();
            const poster_file = document.getElementById('moviePosterFile').files[0];

            if (!title || !genre || !duration || !description) {
                alert('Please fill in all required fields (title, genre, duration, description). Poster optional.');
                return;
            }

            const formData = new FormData(form);
            formData.set('action', editingMovieId ? 'update' : 'add');
            if (editingMovieId) {
                formData.set('id', editingMovieId);
            }
            if (poster_file) {
                formData.set('poster_file', poster_file);
            }

            try {
                const res = await fetch('admin-movies.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Failed to save movie.');
                }

                modal.classList.add('hidden');
                resetMovieModal();
                await loadMovies();
                alert(editingMovieId ? 'Movie updated successfully.' : 'Movie added successfully.');
            } catch (error) {
                alert('Error saving movie: ' + error.message);
            }
        });

        // Demo actions
        function editMovie(id) {
            const movie = allMovies.find(m => Number(m.id) === Number(id));
            if (!movie) {
                alert('Movie not found for editing.');
                return;
            }
            showMovieModal(movie);
        }

        async function deleteMovie(id) {
            if (!confirm('Delete this movie?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const res = await fetch('admin-movies.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Failed to delete movie.');
                }
                await loadMovies();
                alert('Movie deleted successfully.');
            } catch (error) {
                alert('Error deleting movie: ' + error.message);
            }
        }

        // Init
        loadMovies();
    </script>
</body>
</html>
