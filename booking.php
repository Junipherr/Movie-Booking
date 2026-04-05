<?php 
require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$movie = null;
$theaters = $dates = $times = [];

if ($movie_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get all showtimes for this movie
    $showtimes = [];
    $stmt = $conn->prepare("SELECT DISTINCT theater, date, time FROM showtimes WHERE movie_id = ? ORDER BY theater, date, time");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $showtimes[] = $row;
        if (!in_array($row['theater'], $theaters)) $theaters[] = $row['theater'];
        if (!in_array($row['date'], $dates)) $dates[] = $row['date'];
        if (!in_array($row['time'], $times)) $times[] = $row['time'];
    }
    $stmt->close();
}
$conn->close();
?>
<?php 
$pageTitle = 'Booking - Movie Booking';
$activePage = 'bookings';
include 'includes/public-header.php';
?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 pt-24">
        <?php if (!$movie): ?>
        <div class="text-center py-20">
            <h1 class="text-3xl font-bold text-white mb-4">Movie Not Found</h1>
            <a href="index.php" class="text-netflix-red hover:underline">Back to Movies</a>
        </div>
        <?php else: ?>
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Movie Summary -->
            <div class="lg:row-span-2">
                <div class="bg-neutral-800 rounded-xl p-6 shadow-lg border border-neutral-700 sticky top-24">
                    <div class="flex items-start gap-4 mb-4">
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-20 h-28 object-cover rounded-lg shadow-lg flex-shrink-0">
                        <div>
                            <h1 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($movie['title']); ?></h1>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($movie['genre']); ?> • <?php echo htmlspecialchars($movie['duration']); ?> min</p>
                        </div>
                    </div>
                    <div class="border-t border-neutral-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-300 mb-2">Booking Details</h3>
                        <div id="bookingDetails" class="space-y-1 text-sm text-gray-400"></div>
                    </div>
                </div>
            </div>

            <!-- Seat Selection -->
            <div>
                <form id="bookingForm" method="POST" action="bookings.php">

                    <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                    <input type="hidden" name="date" id="dateInput">
                    <input type="hidden" name="time" id="timeInput">
                    <input type="hidden" name="theater" id="theaterInput">
                    <input type="hidden" name="selectedSeats" id="selectedSeatsInput">
                    <div id="showSelectionControls" class="mb-6 bg-neutral-800 rounded-xl p-6 shadow-lg border border-neutral-700">
                        <h3 class="text-lg font-semibold text-white mb-4">Select show details</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="block">
                                <span class="text-sm font-medium text-gray-400">Theater</span>
                                <select name="theater" id="showTheaterSelect" class="mt-1 w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-md text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red" required>
                                    <option value="">Select theater</option>
                                    <?php foreach ($theaters as $theater): ?>
                                        <option value="<?php echo htmlspecialchars($theater); ?>"><?php echo htmlspecialchars($theater); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-400">Date</span>
                                <select name="date" id="showDateSelect" class="mt-1 w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-md text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red" required>
                                    <option value="">Select date</option>
                                    <?php foreach ($dates as $date): ?>
                                        <option value="<?php echo htmlspecialchars($date); ?>"><?php echo date('D, M j, Y', strtotime($date)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-400">Time</span>
                                <select name="time" id="showTimeSelect" class="mt-1 w-full px-3 py-2 bg-neutral-900 border border-neutral-600 rounded-md text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red" required>
                                    <option value="">Select time</option>
                                    <?php foreach ($times as $time): ?>
                                        <option value="<?php echo htmlspecialchars($time); ?>">
                                            <?php 
                                            $h = (int)substr($time,0,2);
                                            if ($h === 10) echo '10:00 AM';
                                            elseif ($h === 13) echo '1:00 PM';
                                            elseif ($h === 16) echo '4:00 PM';
                                            elseif ($h === 19) echo '7:00 PM';
                                            elseif ($h === 22) echo '10:00 PM';
                                            else echo htmlspecialchars($time);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <button type="button" id="loadSeatsBtn" class="mt-4 w-full bg-netflix-red hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Load Seats</button>
                        <p id="showSelectionMessage" class="mt-2 text-xs text-gray-400">Select theater → Click "Load Seats" → Pick seats!</p>
                    </div>
                    
                    <div class="bg-neutral-800 rounded-xl p-6 shadow-lg border border-neutral-700">
                        <h2 class="text-xl font-bold text-white mb-4">Select Your Seats</h2>
                        
                        <!-- Seat Legend -->
                        <div class="flex flex-wrap gap-4 mb-4 p-3 bg-neutral-900 rounded-lg">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-6 h-6 bg-green-200 border-2 border-green-400 rounded flex items-center justify-center font-bold text-green-700 text-xs hover:scale-110 transition-transform">1</span>
                                <span class="text-gray-400">Available</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-6 h-6 bg-blue-500 border-2 border-blue-600 rounded flex items-center justify-center font-bold text-white text-xs hover:scale-110 transition-transform">3</span>
                                <span class="text-gray-400">Selected</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-6 h-6 bg-red-500 border-2 border-red-600 rounded flex items-center justify-center font-bold text-white text-xs">X</span>
                                <span class="text-gray-400">Occupied</span>
                            </div>
                        </div>

                        <!-- Screen -->
                        <div class="text-center mb-6">
                            <div class="relative inline-block">
                                <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-gray-400 to-transparent opacity-30 transform -translate-y-1 scale-y-150"></div>
                                <div class="bg-gradient-to-b from-gray-500 to-gray-700 px-12 py-2 rounded-t-[3rem] rounded-b-lg shadow-lg border-2 border-t-gray-300 border-x-gray-400 border-b-gray-600 font-bold text-sm tracking-[0.3em] text-gray-200">
                                    SCREEN
                                </div>
                                <div class="absolute inset-x-0 top-full h-3 bg-gradient-to-b from-gray-600/30 to-transparent"></div>
                            </div>
                        </div>

                        <!-- Seat Map -->
                        <div id="seatMap" class="mb-4 p-3 bg-neutral-900 rounded-lg overflow-x-auto">
                        <?php
                        $rows = range('A', 'J');
                        $seatsPerRow = 12;
                        echo '<div class="text-gray-500 text-xs mb-2">(Enable JavaScript for interactive seats)</div>';
                        foreach ($rows as $rowIndex => $row) {
                            $curveOffset = 0;
                            if ($row === 'A') $curveOffset = 4;
                            elseif ($row === 'B') $curveOffset = 3;
                            elseif ($row === 'C') $curveOffset = 2;
                            elseif ($row === 'D') $curveOffset = 1;
                            elseif ($row >= 'E' && $row <= 'G') $curveOffset = 0;
                            elseif ($row === 'H') $curveOffset = -1;
                            elseif ($row === 'I') $curveOffset = -2;
                            elseif ($row === 'J') $curveOffset = -3;
                            echo '<div class="flex items-center justify-center mb-1 gap-2">';
                            echo '<span class="w-6 text-right font-bold text-sm text-gray-400 mr-1 flex-shrink-0">' . $row . '</span>';
                            for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                                $seatId = $row . $seat;
                                $seatOffset = 0;
                                $centerStart = ceil($seatsPerRow / 2) - 1;
                                $centerEnd = floor($seatsPerRow / 2);
                                if ($seat >= $centerStart && $seat <= $centerEnd) {
                                    $seatOffset = $curveOffset;
                                } elseif ($seat === $centerStart - 1 || $seat === $centerEnd + 1) {
                                    $seatOffset = max(0, $curveOffset - 1);
                                } elseif ($seat <= 2 || $seat >= $seatsPerRow - 1) {
                                    $seatOffset = max(0, $curveOffset - 2);
                                }
                                $translateClass = $seatOffset > 0 ? 'translate-y-' . $seatOffset : ($seatOffset < 0 ? 'translate-y-[' . $seatOffset . ']' : '');
                                echo '<div class="seat w-8 h-8 rounded border-2 flex items-center justify-center font-bold text-xs bg-green-200 border-green-300 text-gray-400 cursor-not-allowed opacity-60 hover:scale-110 transition-transform duration-200 ' . $translateClass . '" data-seat="' . $seatId . '" title="Enable JavaScript to select seats">' . $seat . '</div>';
                            }
                            echo '</div>';
                        }
                        ?>
                        </div>
                    </div>

                        <!-- Selection Summary -->
                        <div class="bg-neutral-900 p-4 rounded-lg border border-neutral-700 mt-4">
                            <div class="flex flex-wrap gap-2 mb-2 text-xs font-medium">
                                <span class="text-gray-400">Selected: </span>
                                <span id="selectedSeatsList" class="bg-blue-600 text-white px-2 py-0.5 rounded font-mono text-xs">0 seats</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold">
                                <span class="text-gray-300">Total:</span>
                                <span id="totalPrice" class="text-netflix-red">₱0.00</span>
                            </div>
                        </div>

                        <!-- Error Alert -->
                        <div id="bookingError" class="hidden mt-4 p-3 bg-red-900/50 border border-red-700 rounded-lg">
                            <p class="text-red-300 text-sm" id="errorMessage"></p>
                            <p class="text-red-400 text-xs mt-1">Please refresh and select different seats.</p>
                        </div>

                        <button type="submit" id="confirmBookingBtn" class="w-full bg-netflix-red hover:bg-red-700 text-white font-semibold py-3 px-4 rounded mt-4 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Proceed to Payment
                        </button>

                        <p class="text-xs text-gray-500 text-center mt-3">
                            Max 10 seats per booking
                        </p>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="booking.js"></script>
<?php include 'includes/public-footer.php'; ?>
