<?php 
require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$movie = null;

if ($movie_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
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
                <div id="bookingDebug" class="mb-6 p-4 bg-blue-50 rounded-3xl border border-blue-200 text-sm text-blue-900 max-h-96 overflow-y-auto"></div>
                <form id="bookingForm" action="bookings.php" method="POST">
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
                                <select id="showTheaterSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required>
                                    <option value="">Select theater</option>
                                    <option value="Screen 1">Screen 1</option>
                                    <option value="Screen 2">Screen 2</option>
                                    <option value="IMAX">IMAX</option>
                                    <option value="VIP">VIP</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Date</span>
                                <select id="showDateSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required disabled>
                                    <option value="">Select theater first</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Time</span>
                                <select id="showTimeSelect" class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white" required disabled>
                                    <option value="">Select date first</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="13:00">1:00 PM</option>
                                    <option value="16:00">4:00 PM</option>
                                    <option value="19:00">7:00 PM</option>
                                    <option value="22:00">10:00 PM</option>
                                </select>
                            </label>
                        </div>
                        <button type="button" id="loadSeatsBtn" class="mt-4 w-full bg-primary hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300">Load seats</button>
                        <p id="showSelectionMessage" class="mt-3 text-sm text-gray-600">Choose theater, date, and time, then click Load seats.</p>
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
                        <div id="seatMap" class="mb-8 p-4 sm:p-6 md:p-8 bg-gradient-to-br from-gray-50/50 to-white/70 rounded-3xl border-2 border-gray-200/50 shadow-inner w-full max-w-4xl sm:max-w-5xl lg:max-w-6xl mx-auto overflow-x-auto pb-4"></div>
                        <!-- Seats will be generated by JS -->
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

    <script>
        console.log('Booking page JavaScript loaded - v1.1');
        const urlParams = new URLSearchParams(window.location.search);
        function safeParseJson(value, fallback) {
            try {
                return JSON.parse(value);
            } catch (error) {
                console.warn('safeParseJson failed', error, value);
                return fallback;
            }
        }

        const storedSelectedShow = safeParseJson(sessionStorage.getItem('selectedShow') || '{}', {});
        const selectedShow = (storedSelectedShow && storedSelectedShow.date && storedSelectedShow.time && storedSelectedShow.theater) ? storedSelectedShow : {
            date: urlParams.get('date') || '',
            time: urlParams.get('time') || '',
            theater: urlParams.get('theater') || ''
        };
        let isVerifying = false;

        // Get movie_id from URL
        const movieId = urlParams.get('movie_id');

        let showDateSelect;
        let showTimeSelect;
        let showTheaterSelect;
        let loadSeatsBtn;
        let showSelectionMessage;
        let bookingDetailsDiv;

        function populateShowSelectors() {
            if (!movieId) {
                showDebug('ERROR: No movieId available');
                return;
            }

            showDebug(`Initializing show selectors for movie ${movieId}...`);

            // Initialize theater selector with static options
            if (showTheaterSelect) {
                showTheaterSelect.innerHTML = `
                    <option value="">Select theater</option>
                    <option value="Screen 1">Screen 1</option>
                    <option value="Screen 2">Screen 2</option>
                    <option value="IMAX">IMAX</option>
                    <option value="VIP">VIP</option>
                `;
                showTheaterSelect.disabled = false;
                showDebug('✓ Theaters initialized');
            }

            // Disable date and time selectors initially
            if (showDateSelect) {
                showDateSelect.innerHTML = '<option value="">Select theater first</option>';
                showDateSelect.disabled = true;
            }
            if (showTimeSelect) {
                showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                showTimeSelect.disabled = true;
            }

            // Set up event listeners for cascading selection
            setupCascadingSelectors();
        }

        function setupCascadingSelectors() {
            showDebug('Setting up cascading selectors...');

            // Theater change handler
            if (showTheaterSelect) {
                showDebug('Adding theater change listener');
                showTheaterSelect.addEventListener('change', function() {
                    const selectedTheater = this.value;
                    showDebug(`Theater change event fired: ${selectedTheater}`);

                    if (selectedTheater) {
                        if (showDateSelect) {
                            showDateSelect.innerHTML = '<option>Loading dates...</option>';
                            showDateSelect.disabled = false;
                            showDateSelect.removeAttribute('disabled');
                        }
                        if (showTimeSelect) {
                            showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                            showTimeSelect.disabled = true;
                        }
                        showDebug(`Calling populateDatesForTheater with: ${selectedTheater}`);
                        populateDatesForTheater(selectedTheater);
                    } else {
                        // Reset date and time selectors
                        if (showDateSelect) {
                            showDateSelect.innerHTML = '<option value="">Select theater first</option>';
                            showDateSelect.disabled = true;
                        }
                        if (showTimeSelect) {
                            showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                            showTimeSelect.disabled = true;
                        }
                        selectedShow.theater = '';
                        selectedShow.date = '';
                        selectedShow.time = '';
                        updateShowSelectionMessage();
                    }
                });
            } else {
                showDebug('ERROR: showTheaterSelect not found for event listener');
            }

            // Date change handler
            if (showDateSelect) {
                showDateSelect.addEventListener('change', function() {
                    const selectedDate = this.value;
                    const selectedTheater = showTheaterSelect ? showTheaterSelect.value : '';

                    showDebug(`Date selected: ${selectedDate} for theater: ${selectedTheater}`);

                    if (selectedDate && selectedTheater) {
                        populateTimesForTheaterAndDate(selectedTheater, selectedDate);
                    } else {
                        // Reset time selector
                        if (showTimeSelect) {
                            showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                            showTimeSelect.disabled = true;
                        }
                        selectedShow.date = '';
                        selectedShow.time = '';
                        updateShowSelectionMessage();
                    }
                });
            }

            // Time change handler
            if (showTimeSelect) {
                showTimeSelect.addEventListener('change', function() {
                    const selectedTime = this.value;
                    showDebug(`Time selected: ${selectedTime}`);
                    selectedShow.time = selectedTime;
                    updateShowSelectionMessage();
                });
            }
        }

        function populateDatesForTheater(theater, preserveDate = false) {
            showDebug(`populateDatesForTheater called with theater: ${theater}`);

            if (!movieId || !theater) {
                showDebug('ERROR: Missing movieId or theater');
                return Promise.resolve();
            }

            showDebug(`Fetching dates for theater: ${theater}...`);

            // Loading state for date selector
            if (showDateSelect) {
                showDateSelect.innerHTML = '<option>Loading dates...</option>';
                showDateSelect.disabled = true;
            }

            // Reset time selector
            if (showTimeSelect) {
                showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                showTimeSelect.disabled = true;
            }

            selectedShow.theater = theater;
            if (!preserveDate) {
                selectedShow.date = '';
                selectedShow.time = '';
            }

            // Fetch dates for this theater
            const apiUrl = `get-showtimes.php?movie_id=${movieId}&theater=${encodeURIComponent(theater)}`;
            showDebug(`Making API call to: ${apiUrl}`);

            return fetch(apiUrl)
                .then(r => {
                    showDebug(`Dates API response status: ${r.status}`);
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    showDebug(`Dates API data received: ${JSON.stringify(data)}`);

                    if (!data.success) {
                        showDebug('ERROR: API returned success=false: ' + (data.error || 'Unknown error'));
                        return;
                    }

                    // Populate dates
                    if (showDateSelect && data.dates && data.dates.length > 0) {
                        showDebug(`Populating ${data.dates.length} dates for theater ${theater}...`);
                        showDateSelect.innerHTML = '<option value="">Select date</option>';
                        data.dates.forEach(dateStr => {
                            const date = new Date(dateStr + 'T00:00:00');
                            const label = date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                            const option = document.createElement('option');
                            option.value = dateStr;
                            option.textContent = label;
                            showDateSelect.appendChild(option);
                        });
                        showDateSelect.disabled = false;
                        showDateSelect.removeAttribute('disabled');
                        showDebug('✓ Dates populated successfully');
                    } else {
                        showDebug(`No dates available for theater ${theater}`);
                        showDateSelect.innerHTML = '<option value="">No dates available</option>';
                        showDateSelect.disabled = true;
                    }
                })
                .catch(err => {
                    showDebug('ERROR: Dates fetch failed - ' + err.message);
                    console.error('Dates fetch error:', err);
                    if (showDateSelect) {
                        showDateSelect.innerHTML = '<option value="">Error loading dates</option>';
                        showDateSelect.disabled = true;
                    }
                });
        }

        function populateTimesForTheaterAndDate(theater, date, preserveTime = false) {
            if (!movieId || !theater || !date) return Promise.resolve();

            showDebug(`Fetching times for theater: ${theater}, date: ${date}...`);

            // Loading state for time selector
            if (showTimeSelect) {
                showTimeSelect.innerHTML = '<option>Loading times...</option>';
                showTimeSelect.disabled = true;
            }

            selectedShow.date = date;
            if (!preserveTime) {
                selectedShow.time = '';
            }

            // Fetch times for this theater and date
            return fetch(`get-showtimes.php?movie_id=${movieId}&theater=${encodeURIComponent(theater)}&date=${encodeURIComponent(date)}`)
                .then(r => {
                    showDebug(`Times API response status: ${r.status}`);
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    showDebug(`Times API data received: success=${data.success}, times=${data.times?.length || 0}`);

                    if (!data.success) {
                        showDebug('ERROR: API returned success=false: ' + (data.error || 'Unknown error'));
                        return;
                    }

                    // Populate times
                    if (showTimeSelect && data.times && data.times.length > 0) {
                        showDebug(`Populating ${data.times.length} times for theater ${theater} on ${date}...`);
                        showTimeSelect.innerHTML = '<option value="">Select time</option>';
                        data.times.forEach(timeStr => {
                            const [hours, mins] = timeStr.split(':');
                            const hour = parseInt(hours);
                            let label;
                            if (hour === 10) label = '10:00 AM';
                            else if (hour === 13) label = '1:00 PM';
                            else if (hour === 16) label = '4:00 PM';
                            else if (hour === 19) label = '7:00 PM';
                            else if (hour === 22) label = '10:00 PM';
                            else label = timeStr;

                            const option = document.createElement('option');
                            option.value = timeStr;
                            option.textContent = label;
                            showTimeSelect.appendChild(option);
                        });
                        showTimeSelect.disabled = false;
                        showTimeSelect.removeAttribute('disabled');
                        showDebug('✓ Times populated successfully');
                    } else {
                        showDebug(`No times available for theater ${theater} on ${date}`);
                        showTimeSelect.innerHTML = '<option value="">No times available</option>';
                        showTimeSelect.disabled = true;
                    }
                })
                .catch(err => {
                    showDebug('ERROR: Times fetch failed - ' + err.message);
                    console.error('Times fetch error:', err);
                    if (showTimeSelect) {
                        showTimeSelect.innerHTML = '<option value="">Error loading times</option>';
                        showTimeSelect.disabled = true;
                    }
                });
        }

        function updateShowSelectionMessage() {
            if (!showSelectionMessage) return;
            if (selectedShow.date && selectedShow.time && selectedShow.theater) {
                showSelectionMessage.textContent = 'Show details selected. Click Load seats to display available seats.';
            } else {
                showSelectionMessage.textContent = 'Choose theater, date, and time, then click Load seats.';
            }
        }

        function applySelectedShow() {
            if (showDateSelect) showDateSelect.value = selectedShow.date || '';
            if (showTimeSelect) showTimeSelect.value = selectedShow.time || '';
            if (showTheaterSelect) showTheaterSelect.value = selectedShow.theater || '';
            if (bookingDetailsDiv && selectedShow.date && selectedShow.time && selectedShow.theater) {
                bookingDetailsDiv.innerHTML = `
                    <div><strong>📅 Date:</strong> ${new Date(selectedShow.date).toLocaleDateString()}</div>
                    <div><strong>🕒 Time:</strong> ${selectedShow.time}</div>
                    <div><strong>🎬 Theater:</strong> ${selectedShow.theater}</div>
                `;
            }

            document.getElementById('dateInput').value = selectedShow.date || '';
            document.getElementById('timeInput').value = selectedShow.time || '';
            document.getElementById('theaterInput').value = selectedShow.theater || '';
        }

        async function loadSelectedShow() {
            if (!selectedShow.date || !selectedShow.time || !selectedShow.theater) {
                showError('Please select date, time, and theater before loading seats.');
                return;
            }

            sessionStorage.setItem('selectedShow', JSON.stringify(selectedShow));
            applySelectedShow();
            updateShowSelectionMessage();
            hideError();
            await fetchOccupiedSeats();
        }

        function initBooking() {
            console.log('initBooking called');
            alert('initBooking called for movie ' + movieId);
            showDebug('initBooking started for movieId: ' + movieId);
            showDateSelect = document.getElementById('showDateSelect');
            showTimeSelect = document.getElementById('showTimeSelect');
            showTheaterSelect = document.getElementById('showTheaterSelect');
            loadSeatsBtn = document.getElementById('loadSeatsBtn');
            showSelectionMessage = document.getElementById('showSelectionMessage');
            bookingDetailsDiv = document.getElementById('bookingDetails');

            // Debug: show which elements were found
            showDebug('Initializing booking: ' + 
                (showDateSelect ? '✓ Date' : '✗ Date') + ' ' +
                (showTimeSelect ? '✓ Time' : '✗ Time') + ' ' +
                (showTheaterSelect ? '✓ Theater' : '✗ Theater') + ' ' +
                (loadSeatsBtn ? '✓ LoadBtn' : '✗ LoadBtn'));

            populateShowSelectors();
            
            // Debug: show how many options in each select
            if (showDateSelect) showDebug('Date select now has ' + showDateSelect.length + ' options');
            if (showTimeSelect) showDebug('Time select now has ' + showTimeSelect.length + ' options');
            if (showTheaterSelect) showDebug('Theater select now has ' + showTheaterSelect.length + ' options');
            
            applySelectedShow();
            showDebug(`movie_id=${movieId}, selectedShow=${JSON.stringify(selectedShow)}`);

            if (selectedShow.theater) {
                populateDatesForTheater(selectedShow.theater, true)
                    .then(() => {
                        if (selectedShow.date && showDateSelect) {
                            showDateSelect.value = selectedShow.date;
                            return populateTimesForTheaterAndDate(selectedShow.theater, selectedShow.date, true);
                        }
                    })
                    .then(() => {
                        if (selectedShow.time && showTimeSelect) {
                            showTimeSelect.value = selectedShow.time;
                        }
                        if (selectedShow.date && selectedShow.time && selectedShow.theater) {
                            loadSelectedShow();
                        }
                    });
            } else if (selectedShow.date && selectedShow.time) {
                updateShowSelectionMessage();
            }

            loadSeatsBtn?.addEventListener('click', function() {
                const date = showDateSelect?.value || '';
                const time = showTimeSelect?.value || '';
                const theater = showTheaterSelect?.value || '';

                selectedShow.date = date;
                selectedShow.time = time;
                selectedShow.theater = theater;

                loadSelectedShow();
            });
        }

        function showDebug(message) {
            const debug = document.getElementById('bookingDebug');
            if (!debug) return;
            debug.classList.remove('hidden');
            debug.innerHTML = `<strong>Debug:</strong> ${message}`;
        }

        let selectedSeats = safeParseJson(sessionStorage.getItem('selectedSeats') || '[]', []);
        const BASE_PRICE = 12.99;
        const MAX_SEATS = 10;
        let occupiedSeats = [];

        // Fetch occupied seats from server
        async function fetchOccupiedSeats() {
            try {
                if (!movieId || !selectedShow.theater) {
                    showDebug('Missing movieId or theater, cannot fetch seats');
                    return;
                }

                const response = await fetch('get-seats.php?movie_id=' + movieId + '&theater=' + encodeURIComponent(selectedShow.theater));
                const data = await response.json();
                
                if (data.success && data.occupied_seats) {
                    occupiedSeats = data.occupied_seats;
                    showDebug(`movie_id=${movieId}, theater=${selectedShow.theater}, seatsLoaded=${occupiedSeats.length}`);
                } else {
                    showDebug(`Failed to fetch seats: ${data.error || JSON.stringify(data)}`);
                }
                
                generateSeatMap();
            } catch (error) {
                console.error('Error fetching seats:', error);
                showDebug('Error fetching seats: ' + error.message);
                generateSeatMap(); // Generate anyway with empty occupied list
            }
        }

        // Responsive seat config
        const getSeatConfig = () => {
            const width = window.innerWidth;
            if (width < 640) return { seatsPerSide: 5, totalSeats: 10, aisleAfter: 5 };
            if (width < 1024) return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };
            return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };
        };

        // Generate seat map
        function generateSeatMap() {
            const seatMap = document.getElementById('seatMap');
            if (!seatMap) {
                console.error('seatMap element not found!');
                return;
            }

            const rows = 'ABCDEFGHIJ';
            const config = getSeatConfig();
            let html = '';
            
            for (let row of rows) {
                html += `<div class="flex items-center justify-center mb-3 sm:mb-4 gap-0.5 sm:gap-1">\n`;
                html += `<span class="w-9 sm:w-10 md:w-12 text-right font-bold text-sm sm:text-base md:text-lg mr-1 sm:mr-2 md:mr-4 whitespace-nowrap flex-shrink-0">${row}</span>\n`;
                
                for (let seat = 1; seat <= config.totalSeats; seat++) {
                    const seatId = `${row}${seat}`;
                    const isOccupied = occupiedSeats.includes(seatId);
                    const status = isOccupied ? 'occupied' : 'available';
                    
                    html += `<div class="seat w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 lg:w-12 lg:h-12 min-w-[2.5rem] min-h-[2.5rem] rounded-lg border-4 flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-105 active:scale-95 touch-manipulation font-bold text-xs sm:text-sm shadow-md ${status === 'occupied' ? 'bg-red-500 border-red-600 text-white cursor-not-allowed' : 'bg-green-500 border-green-600 text-white shadow-lg'} seat-${seatId}" data-seat="${seatId}" data-status="${status}" aria-label="Seat ${seatId} ${status}">${isOccupied ? 'X' : seat}</div>\n`;
                }
                
                html += `</div>\n`;
            }
            
            seatMap.innerHTML = html;
            console.log('Seat map generated with', occupiedSeats.length, 'occupied seats');
            
            // Add event listeners
            document.querySelectorAll('.seat.available').forEach(seat => {
                seat.addEventListener('click', handleSeatClick, { passive: true });
                seat.setAttribute('tabindex', '0');
                seat.setAttribute('role', 'button');
            });
            
            updateSelection();
        }

        function handleSeatClick(e) {
            e.preventDefault();
            const seat = e.currentTarget;
            const seatId = seat.dataset.seat;
            
            if (selectedSeats.includes(seatId)) {
                selectedSeats = selectedSeats.filter(s => s !== seatId);
                seat.className = seat.className.replace('selected', 'available').replace('bg-blue-500', 'bg-green-500').replace('border-blue-600', 'border-green-600');
                seat.dataset.status = 'available';
                seat.setAttribute('aria-label', `Seat ${seatId} available`);
            } else if (selectedSeats.length < MAX_SEATS && seat.dataset.status !== 'occupied') {
                selectedSeats.push(seatId);
                seat.className = seat.className.replace('available', 'selected').replace('bg-green-500', 'bg-blue-500').replace('border-green-600', 'border-blue-600') + ' shadow-lg ring-2 ring-blue-300';
                seat.dataset.status = 'selected';
                seat.setAttribute('aria-label', `Seat ${seatId} selected`);
            }
            
            updateSelection();
        }

        function updateSelection() {
            const list = document.getElementById('selectedSeatsList');
            const totalPrice = document.getElementById('totalPrice');
            const confirmBtn = document.getElementById('confirmBookingBtn');
            const selectedSeatsInput = document.getElementById('selectedSeatsInput');
            
            selectedSeatsInput.value = selectedSeats.join(',');
            
            if (selectedSeats.length > 0) {
                list.innerHTML = `<span class="font-mono">${selectedSeats.join(', ')}</span> <span class="text-gray-600">(${selectedSeats.length} seats)</span>`;
                totalPrice.textContent = `₱${(selectedSeats.length * BASE_PRICE).toFixed(2)}`;
                confirmBtn.disabled = false;
                confirmBtn.textContent = `Proceed to Payment (${selectedSeats.length} seats)`;
            } else {
                list.textContent = '0 seats';
                totalPrice.textContent = '₱0.00';
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Proceed to Payment';
            }
            
            sessionStorage.setItem('selectedSeats', JSON.stringify(selectedSeats));
        }

        // Resize listener for re-generating responsive layout
        window.addEventListener('resize', debounce(generateSeatMap, 250));

        // Debounce utility
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Verify seats are still available before booking
        async function verifySeatsAvailable(seats) {
            if (!seats || seats.length === 0) {
                showError('No seats selected');
                return false;
            }

            try {
                const formData = new FormData();
                formData.append('movie_id', movieId);
                formData.append('theater', selectedShow.theater);
                formData.append('seats', seats.join(','));

                const response = await fetch('verify-seats.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success || !data.available) {
                    showError(data.message || 'Some seats are no longer available');
                    return false;
                }

                return true;
            } catch (error) {
                console.error('Seat verification error:', error);
                showError('Failed to verify seats. Please try again.');
                return false;
            }
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('bookingError');
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorDiv.classList.remove('hidden');
            window.scrollTo(0, 0);
        }

        // Hide error message
        function hideError() {
            document.getElementById('bookingError').classList.add('hidden');
        }

        // Handle form submission
        document.getElementById('bookingForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (selectedSeats.length === 0) {
                showError('Please select at least one seat');
                return;
            }

            const confirmBtn = document.getElementById('confirmBookingBtn');
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Verifying seats...';

            // Verify seats before submitting
            const seatsAvailable = await verifySeatsAvailable(selectedSeats);

            if (seatsAvailable) {
                hideError();
                // Submit the form
                this.submit();
            } else {
                confirmBtn.disabled = false;
                confirmBtn.textContent = `Proceed to Payment (${selectedSeats.length} seats)`;
            }
        });

        // Navbar handlers
        document.getElementById('mobileMenuBtn')?.onclick = () => alert('Mobile menu');
        document.getElementById('profileBtn')?.onclick = () => alert('Profile: My Bookings, Settings, Logout');

        // Display any booking error from session
        if (urlParams.has('error')) {
            showError('Booking failed. Some selected seats may have been booked by another user. Please try again.');
        }

        // Initialize booking when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initBooking);
        } else {
            initBooking();
        }
    </script>
<?php include 'includes/public-footer.php'; ?>
</body>
</html>
