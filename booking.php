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
<style>
    * { font-family: 'Inter', sans-serif; }
</style>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-24">
        <?php if (!$movie): ?>
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Movie Not Found</h1>
            <a href="index.php" class="text-primary hover:underline">Back to Movies</a>
        </div>
        <?php else: ?>
        <div class="grid lg:grid-cols-2 gap-12">
            <!-- Movie Summary -->
            <div class="lg:row-span-2">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl p-8 border border-white/50">
                    <div class="flex items-start space-x-6 mb-6">
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-24 h-36 object-cover rounded-xl shadow-lg flex-shrink-0">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($movie['title']); ?></h1>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($movie['genre']); ?> • <?php echo htmlspecialchars($movie['duration']); ?> min</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Booking Details</h3>
                        <div id="bookingDetails" class="space-y-2 text-sm text-gray-700"></div>
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
                    <div id="showSelectionControls" class="mb-6 bg-slate-50 border border-slate-200 rounded-3xl p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Select show details</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Theater</span>
                                <select name="theater" id="showTheaterSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required>
                                    <option value="">Select theater</option>
                                    <?php foreach ($theaters as $theater): ?>
                                        <option value="<?php echo htmlspecialchars($theater); ?>"><?php echo htmlspecialchars($theater); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Date</span>
                                <select name="date" id="showDateSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required>
                                    <option value="">Select date</option>
                                    <?php foreach ($dates as $date): ?>
                                        <option value="<?php echo htmlspecialchars($date); ?>"><?php echo date('D, M j, Y', strtotime($date)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Time</span>
                                <select name="time" id="showTimeSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required>
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
<button type="button" id="loadSeatsBtn" class="mt-4 w-full bg-primary hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 opacity-50" disabled>Load Seats (Select Theater First)</button>
<p id="showSelectionMessage" class="mt-3 text-sm text-gray-600 font-semibold">🎯 Select theater → Click "Load Seats" → Pick seats!</p>
                    </div>
                    
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl p-8 border border-white/50">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Select Your Seats</h2>
                        
                        <!-- Seat Legend -->
                        <div class="flex flex-col sm:flex-row gap-4 mb-8 p-4 bg-gray-50 rounded-2xl">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="w-6 h-6 bg-green-500 rounded border-2 border-green-600 flex items-center justify-center font-bold text-white text-xs">A1</span>
                                <span>Available</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="w-6 h-6 bg-blue-500 rounded border-2 border-blue-600 flex items-center justify-center font-bold text-white text-xs">B3</span>
                                <span>Selected</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="w-6 h-6 bg-red-500 rounded border-2 border-red-600 flex items-center justify-center font-bold text-white text-xs">X</span>
                                <span>Occupied</span>
                            </div>
                        </div>

                        <!-- Screen -->
                        <div class="text-center mb-6">
                            <div class="inline-block bg-gradient-to-b from-gray-300 to-gray-400 px-6 sm:px-8 py-2 rounded-2xl shadow-lg border-4 border-gray-200 transform rotate-2 font-bold text-xl sm:text-2xl tracking-wider">
                                🎬 SCREEN
                            </div>
                        </div>

                        <!-- Seat Map -->
                        <div id="seatMap" class="mb-8 p-4 sm:p-6 md:p-8 bg-gradient-to-br from-gray-50/50 to-white/70 rounded-3xl border-2 border-gray-200/50 shadow-inner w-full max-w-4xl sm:max-w-5xl lg:max-w-6xl mx-auto overflow-x-auto pb-4">
                        <?php
                        // PHP fallback seat map: 10 rows (A-J), 12 seats per row
                        $rows = range('A', 'J');
                        $seatsPerRow = 12;
                        echo '<div class="text-gray-400 text-xs mb-2">(Fallback seat map: for best experience, enable JavaScript)</div>';
                        foreach ($rows as $row) {
                            echo '<div class="flex items-center justify-center mb-3 sm:mb-4 gap-0.5 sm:gap-1">';
                            echo '<span class="w-9 sm:w-10 md:w-12 text-right font-bold text-sm sm:text-base md:text-lg mr-1 sm:mr-2 md:mr-4 whitespace-nowrap flex-shrink-0">' . $row . '</span>';
                            for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                                $seatId = $row . $seat;
                                echo '<div class="seat w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 lg:w-12 lg:h-12 min-w-[2.5rem] min-h-[2.5rem] rounded-lg border-4 flex items-center justify-center font-bold text-xs sm:text-sm shadow-md bg-green-200 border-green-300 text-gray-400 cursor-not-allowed opacity-60" title="Enable JavaScript to select seats">' . $seat . '</div>';
                            }
                            echo '</div>';
                        }
                        ?>
                        </div>
                        <!-- Seats will be generated by JS if enabled -->
                    </div>

                        <!-- Selection Summary -->
                        <div class="bg-gradient-to-r from-emerald-50 to-blue-50 p-6 rounded-2xl border-2 border-emerald-200 mb-6">
                            <div class="flex flex-wrap gap-2 mb-4 text-sm font-medium">
                                <span>Selected: </span>
                                <span id="selectedSeatsList" class="bg-blue-100 text-blue-800 px-2 py-1 rounded-lg font-mono">0 seats</span>
                            </div>
                            <div class="flex justify-between items-center text-xl font-bold">
                                <span>Total:</span>
                                <span id="totalPrice" class="text-primary">₱0.00</span>
                            </div>
                        </div>

                        <!-- Error Alert (if any) -->
                        <div id="bookingError" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-start space-x-3">
                                <span class="text-red-600 font-bold text-lg">!</span>
                                <div>
                                    <p class="font-semibold text-red-800" id="errorMessage"></p>
                                    <p class="text-sm text-red-700 mt-1">Please refresh the page and select different seats.</p>
                                </div>
                            </div>
                        </div>

<button type="submit" id="confirmBookingBtn" class="w-full bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-2xl shadow-2xl hover:shadow-3xl transform hover:-translate-y-1 transition-all duration-300 text-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Proceed to Payment
                        </button>

                        <p class="text-xs text-gray-500 text-center mt-4">
                            Max 10 seats • Seats linked to your booking • Fully responsive
                        </p>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="booking.js"></script>
<?php include 'includes/public-footer.php'; ?>
</body>
</html>
<?php include 'includes/public-footer.php'; ?>
</body>
</html>
