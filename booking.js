/**
 * Movie Booking System - Seat Selection JavaScript
 * 
 * Handles interactive seat selection for movie bookings.
 * Manages show selection (theater, date, time), seat map generation,
 * seat selection, and booking form submission with server-side verification.
 * 
 * @requires: booking.php (HTML page with seat selection form)
 * @requires: get-showtimes.php (API for fetching available dates/times)
 * @requires: get-seats-fixed.php (API for fetching occupied seats)
 * @requires: verify-seats-fixed.php (API for verifying seat availability on submit)
 * @requires: bookings.php (form submission target)
 * 
 * @global: movieId - Movie ID from URL parameter
 * @global: selectedShow - Current show selection (theater, date, time)
 * @global: selectedSeats - Array of selected seat IDs
 * 
 * @constants:
 *   - BASE_PRICE: Price per seat (PHP 250)
 *   - MAX_SEATS: Maximum seats per booking (10)
 * 
 * @functions:
 *   - initBooking(): Main initialization function
 *   - populateShowSelectors(): Sets up theater dropdown
 *   - setupCascadingSelectors(): Event handlers for dropdown cascading
 *   - populateDatesForTheater(): AJAX fetch available dates
 *   - populateTimesForTheaterAndDate(): AJAX fetch available times
 *   - generateSeatMap(): Renders interactive seat grid
 *   - handleSeatClick(): Seat selection toggle
 *   - updateSelection(): Updates price and form inputs
 *   - verifySeatsAvailable(): Server-side validation before submit
 *   - fetchOccupiedSeats(): Gets occupied seats from API
 * 
 * @see booking.php:219 (script include)
 * @see get-showtimes.php (AJAX date/time API)
 * @see get-seats-fixed.php (AJAX occupied seats API)
 * @see verify-seats-fixed.php (AJAX seat verification API)
 */

// Get URL parameters (movie_id, date, time, theater from query string)
const urlParams = new URLSearchParams(window.location.search);

/**
 * Safely parse JSON string with fallback
 * @param {any} value - Value to parse
 * @param {any} fallback - Fallback if parsing fails
 * @returns {any} Parsed value or fallback
 */
function safeParseJson(value, fallback) {
    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

// Clear cached seats on fresh page load/refresh to prevent stale data
function clearCachedSeats() {
    sessionStorage.removeItem('selectedSeats');
    sessionStorage.removeItem('selectedShow');
}

// Store selected show details from URL parameters (for page refresh persistence)
const selectedShow = {
    date: urlParams.get('date') || '',
    time: urlParams.get('time') || '',
    theater: urlParams.get('theater') || ''
};

// Extract movie ID from URL - required for all API calls
const movieId = urlParams.get('movie_id');

// DOM element references - initialized in initBooking()
let showDateSelect, showTimeSelect, showTheaterSelect, loadSeatsBtn, showSelectionMessage, bookingDetailsDiv;

// Selected seats array - stores seat IDs like ['A1', 'A2', 'B5']
let selectedSeats = [];

// Price configuration
const BASE_PRICE = 250.00;    // Price per seat in PHP
const MAX_SEATS = 10;         // Maximum seats allowed per booking
let occupiedSeats = [];       // Array of occupied seat IDs from database

/**
 * Initialize theater dropdown with available theaters
 * Called by initBooking() on page load
 * 
 * @see booking.php:78-83 (theater select element)
 */
function populateShowSelectors() {
    if (!movieId) {
        return;
    }

    if (showTheaterSelect) {
        // Populate with available theaters from showtimes
        showTheaterSelect.innerHTML = `
            <option value="">Select theater</option>
            <option value="Screen 1">Screen 1</option>
            <option value="Screen 2">Screen 2</option>
            <option value="IMAX">IMAX</option>
            <option value="VIP">VIP</option>
        `;
        showTheaterSelect.disabled = false;
    }

    // Initialize dependent dropdowns as disabled
    if (showDateSelect) {
        showDateSelect.innerHTML = '<option value="">Select theater first</option>';
        showDateSelect.disabled = true;
    }
    if (showTimeSelect) {
        showTimeSelect.innerHTML = '<option value="">Select date first</option>';
        showTimeSelect.disabled = true;
    }

    setupCascadingSelectors();
}

/**
 * Set up event listeners for cascading dropdown selection
 * When theater is selected → load dates
 * When date is selected → load times
 * When time is selected → enable seat loading
 * 
 * @calls: populateDatesForTheater()
 * @calls: populateTimesForTheaterAndDate()
 * @calls: updateShowSelectionMessage()
 */
function setupCascadingSelectors() {
    // Theater selection triggers date loading
    if (showTheaterSelect) {
        showTheaterSelect.addEventListener('change', function() {
            const selectedTheater = this.value;
            selectedShow.theater = selectedTheater;
            document.getElementById('theaterInput').value = selectedTheater;
            
            if (selectedTheater) {
                // Fetch available dates for selected theater
                populateDatesForTheater(selectedTheater);
                showSelectionMessage.textContent = `Theater "${selectedTheater}" selected. Choose date/time to load seats.`;
                loadSeatsBtn.disabled = true;
                loadSeatsBtn.classList.add('opacity-50');
            } else {
                showSelectionMessage.textContent = 'Select a theater first.';
                loadSeatsBtn.disabled = true;
                loadSeatsBtn.classList.add('opacity-50');
            }
        });
    }

    // Date selection triggers time loading
    if (showDateSelect) {
        showDateSelect.addEventListener('change', function() {
            const selectedDate = this.value;
            const selectedTheater = showTheaterSelect ? showTheaterSelect.value : '';
            
            if (selectedDate && selectedTheater) {
                // Fetch available times for selected theater and date
                populateTimesForTheaterAndDate(selectedTheater, selectedDate);
                loadSeatsBtn.disabled = true;
                loadSeatsBtn.classList.add('opacity-50');
            } else {
                if (showTimeSelect) {
                    showTimeSelect.innerHTML = '<option value="">Select date first</option>';
                    showTimeSelect.disabled = true;
                }
                selectedShow.date = '';
                selectedShow.time = '';
                loadSeatsBtn.disabled = true;
                loadSeatsBtn.classList.add('opacity-50');
                updateShowSelectionMessage();
            }
        });
    }

    // Time selection enables seat loading button
    if (showTimeSelect) {
        showTimeSelect.addEventListener('change', function() {
            const selectedTime = this.value;
            selectedShow.time = selectedTime;
            
            // All three required for loading seats
            if (selectedShow.theater && selectedShow.date && selectedTime) {
                loadSeatsBtn.disabled = false;
                loadSeatsBtn.classList.remove('opacity-50');
                updateShowSelectionMessage();
            } else {
                loadSeatsBtn.disabled = true;
                loadSeatsBtn.classList.add('opacity-50');
            }
        });
    }
}

/**
 * Fetch available dates for a specific theater via AJAX
 * Calls get-showtimes.php API endpoint
 * 
 * @param {string} theater - Theater name
 * @param {boolean} preserveDate - Keep existing date selection on repopulation
 * @returns {Promise} Promise chain for async operation
 * 
 * @calls: get-showtimes.php?movie_id={id}&theater={name}
 * @see get-showtimes.php (API endpoint)
 */
function populateDatesForTheater(theater, preserveDate = false) {
    if (!movieId || !theater) {
        return Promise.resolve();
    }

    // Show loading state
    if (showDateSelect) {
        showDateSelect.innerHTML = '<option>Loading dates...</option>';
        showDateSelect.disabled = true;
    }

    if (showTimeSelect) {
        showTimeSelect.innerHTML = '<option value="">Select date first</option>';
        showTimeSelect.disabled = true;
    }

    selectedShow.theater = theater;
    if (!preserveDate) {
        selectedShow.date = '';
        selectedShow.time = '';
    }

    // API call to get-showtimes.php (returns dates when no date param)
    const apiUrl = `get-showtimes.php?movie_id=${movieId}&theater=${encodeURIComponent(theater)}`;

    return fetch(apiUrl)
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
            }
            return r.json();
        })
        .then(data => {
            if (!data.success) {
                return;
            }

            // Populate date dropdown with returned dates
            if (showDateSelect && data.dates && data.dates.length > 0) {
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
            } else {
                showDateSelect.innerHTML = '<option value="">No dates available</option>';
                showDateSelect.disabled = true;
            }
        })
        .catch(err => {
            if (showDateSelect) {
                showDateSelect.innerHTML = '<option value="">Error loading dates</option>';
                showDateSelect.disabled = true;
            }
        });
}

/**
 * Fetch available times for a specific theater and date via AJAX
 * Calls get-showtimes.php API endpoint with date parameter
 * 
 * @param {string} theater - Theater name
 * @param {string} date - Selected date YYYY-MM-DD
 * @param {boolean} preserveTime - Keep existing time selection on repopulation
 * @returns {Promise} Promise chain for async operation
 * 
 * @calls: get-showtimes.php?movie_id={id}&theater={name}&date={date}
 * @see get-showtimes.php (API endpoint)
 */
function populateTimesForTheaterAndDate(theater, date, preserveTime = false) {
    if (!movieId || !theater || !date) return Promise.resolve();

    // Show loading state
    if (showTimeSelect) {
        showTimeSelect.innerHTML = '<option>Loading times...</option>';
        showTimeSelect.disabled = true;
    }

    selectedShow.date = date;
    if (!preserveTime) {
        selectedShow.time = '';
    }

    // API call to get-showtimes.php (returns times when date param provided)
    return fetch(`get-showtimes.php?movie_id=${movieId}&theater=${encodeURIComponent(theater)}&date=${encodeURIComponent(date)}`)
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
            }
            return r.json();
        })
        .then(data => {
            if (!data.success) {
                return;
            }

            // Populate time dropdown with returned times, formatted to AM/PM
            if (showTimeSelect && data.times && data.times.length > 0) {
                showTimeSelect.innerHTML = '<option value="">Select time</option>';
                data.times.forEach(timeStr => {
                    const [hours, mins] = timeStr.split(':');
                    const hour = parseInt(hours);
                    let label;
                    // Convert 24h format to AM/PM
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
            } else {
                showTimeSelect.innerHTML = '<option value="">No times available</option>';
                showTimeSelect.disabled = true;
            }
        })
        .catch(err => {
            if (showTimeSelect) {
                showTimeSelect.innerHTML = '<option value="">Error loading times</option>';
                showTimeSelect.disabled = true;
            }
        });
}

/**
 * Update status message based on current selection state
 * Displays user guidance for completing show selection
 */
function updateShowSelectionMessage() {
    if (!showSelectionMessage) return;
    if (selectedShow.date && selectedShow.time && selectedShow.theater) {
        showSelectionMessage.textContent = 'Show details selected. Click Load seats to display available seats.';
    } else {
        showSelectionMessage.textContent = 'Choose theater, date, and time, then click Load seats.';
    }
}

/**
 * Apply URL parameters to dropdowns on page load
 * Restores previous selection if page was refreshed with params
 */
function applySelectedShow() {
    if (showDateSelect) showDateSelect.value = selectedShow.date || '';
    if (showTimeSelect) showTimeSelect.value = selectedShow.time || '';
    if (showTheaterSelect) showTheaterSelect.value = selectedShow.theater || '';
    
    // Update booking details display in sidebar
    if (bookingDetailsDiv && selectedShow.date && selectedShow.time && selectedShow.theater) {
        bookingDetailsDiv.innerHTML = `
            <div><strong>📅 Date:</strong> ${new Date(selectedShow.date).toLocaleDateString()}</div>
            <div><strong>🕒 Time:</strong> ${selectedShow.time}</div>
            <div><strong>🎬 Theater:</strong> ${selectedShow.theater}</div>
        `;
    }

    // Update hidden form inputs for submission
    document.getElementById('dateInput').value = selectedShow.date || '';
    document.getElementById('timeInput').value = selectedShow.time || '';
    document.getElementById('theaterInput').value = selectedShow.theater || '';
}

/**
 * Fetch occupied seats for current showtime via AJAX
 * Calls get-seats-fixed.php to get already-booked seats
 * 
 * @async
 * @calls: get-seats-fixed.php?movie_id={id}&theater={name}&date={date}&time={time}
 * @see get-seats-fixed.php (API endpoint)
 * @calls: generateSeatMap() - After fetching, render the seat grid
 */
async function fetchOccupiedSeats() {
    try {
        if (!movieId || !selectedShow.theater || !selectedShow.date || !selectedShow.time) {
            return;
        }

        const apiUrl = `get-seats-fixed.php?movie_id=${movieId}&theater=${encodeURIComponent(selectedShow.theater)}&date=${encodeURIComponent(selectedShow.date)}&time=${encodeURIComponent(selectedShow.time)}`;
        
        const response = await fetch(apiUrl);
        const data = await response.json();

        // Store occupied seats for seat map generation
        if (data.success && data.occupied_seats) {
            occupiedSeats = data.occupied_seats;
        }
        
        // Generate seat map with occupied seats marked
        generateSeatMap();
    } catch (error) {
        // Generate empty map if API fails (all seats available)
        generateSeatMap();
    }
}

/**
 * Get seat configuration based on viewport width
 * Responsive: fewer seats on mobile, more on desktop
 * 
 * @returns {Object} Configuration with totalSeats, seatsPerSide, aisleAfter
 */
const getSeatConfig = () => {
    const width = window.innerWidth;
    if (width < 640) return { seatsPerSide: 5, totalSeats: 10, aisleAfter: 5 };      // Mobile
    if (width < 1024) return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };   // Tablet
    return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };                      // Desktop
};

/**
 * Generate interactive seat map grid
 * Creates DOM elements for 10 rows (A-J) with clickable seats
 * Applies curved theater effect with offset positioning
 * 
 * @requires: occupiedSeats array from fetchOccupiedSeats()
 * @requires: getSeatConfig() for responsive seat count
 * @calls: handleSeatClick() - Attached to each available seat
 * @calls: updateSelection() - Updates UI after seat changes
 * 
 * @see booking.php:149-184 (seat map container)
 */
function generateSeatMap() {
    const seatMap = document.getElementById('seatMap');
    if (!seatMap) {
        return;
    }

    const rows = 'ABCDEFGHIJ';  // 10 rows of seats
    const config = getSeatConfig();
    let html = '';
    
    // Generate each row
    for (let rowIndex = 0; rowIndex < rows.length; rowIndex++) {
        const row = rows[rowIndex];
        
        // Calculate curved offset for theater effect (front rows curve more)
        let curveOffset = 0;
        if (row === 'A') curveOffset = 4;
        else if (row === 'B') curveOffset = 3;
        else if (row === 'C') curveOffset = 2;
        else if (row === 'D') curveOffset = 1;
        else if (row >= 'E' && row <= 'G') curveOffset = 0;
        else if (row === 'H') curveOffset = -1;
        else if (row === 'I') curveOffset = -2;
        else if (row === 'J') curveOffset = -3;
        
        html += `<div class="flex items-center justify-center mb-2 sm:mb-3 gap-1 sm:gap-2">`;
        html += `<span class="w-9 sm:w-10 md:w-12 text-right font-bold text-sm sm:text-base md:text-lg mr-1 sm:mr-2 md:mr-4 whitespace-nowrap flex-shrink-0">${row}</span>`;
        
        const totalSeats = config.totalSeats;
        // Generate each seat in the row
        for (let seat = 1; seat <= totalSeats; seat++) {
            const seatId = `${row}${seat}`;
            const isOccupied = occupiedSeats.includes(seatId);
            const status = isOccupied ? 'occupied' : 'available';
            
            // Calculate seat position offset for curved effect
            let seatOffset = 0;
            const centerStart = Math.ceil(totalSeats / 2) - 1;
            const centerEnd = Math.floor(totalSeats / 2);
            if (seat >= centerStart && seat <= centerEnd) {
                seatOffset = curveOffset;
            } else if (seat === centerStart - 1 || seat === centerEnd + 1) {
                seatOffset = Math.max(0, curveOffset - 1);
            } else if (seat <= 2 || seat >= totalSeats - 1) {
                seatOffset = Math.max(0, curveOffset - 2);
            }
            const translateClass = seatOffset !== 0 ? `translate-y-[${seatOffset}px]` : '';
            
            // Apply different styles for occupied vs available seats
            const seatClasses = status === 'occupied' 
                ? 'bg-red-500 border-red-600 text-white cursor-not-allowed'
                : 'bg-green-500 border-green-600 text-white shadow-lg cursor-pointer transition-all duration-200 hover:scale-110 active:scale-95 touch-manipulation';
            
            // Build seat element with accessibility attributes
            html += `<div class="seat ${status} relative w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 lg:w-12 lg:h-12 min-w-[2.5rem] min-h-[2.5rem] rounded-lg border-4 flex items-center justify-center font-bold text-xs sm:text-sm shadow-md ${seatClasses} ${translateClass} seat-${seatId}" data-seat="${seatId}" data-status="${status}" aria-label="Seat ${seatId} ${status}">`;
            if (isOccupied) {
                html += `<span class="text-sm font-bold">X</span>`;
                html += `<span class="absolute -top-2 -right-2 bg-red-700 text-white text-[10px] px-1 rounded">Occupied</span>`;
            } else {
                html += `${seat}`;
            }
            html += `</div>`;
        }
        
        html += `</div>`;
    }
    
    // Inject HTML into seat map container
    seatMap.innerHTML = html;
    
    // Attach click handlers to available seats
    const availableSeats = document.querySelectorAll('.seat.available');
    availableSeats.forEach(seat => {
        seat.addEventListener('click', handleSeatClick);
        seat.setAttribute('tabindex', '0');
        seat.setAttribute('role', 'button');
        seat.style.cursor = 'pointer';
    });
    
    // Update selection UI
    updateSelection();
}

/**
 * Handle seat click - toggle selection on/off
 * Adds/removes seat from selectedSeats array
 * Updates visual appearance (green → blue when selected)
 * 
 * @param {Event} e - Click event
 * @calls: updateSelection() - Update price and form after change
 * @see selectedSeats (global array)
 */
function handleSeatClick(e) {
    const seat = e.currentTarget;
    const seatId = seat.dataset.seat;
    
    // If already selected, deselect it
    if (selectedSeats.includes(seatId)) {
        selectedSeats = selectedSeats.filter(s => s !== seatId);
        seat.className = seat.className.replace('selected', 'available').replace('bg-blue-500', 'bg-green-500').replace('border-blue-600', 'border-green-600');
        seat.dataset.status = 'available';
        seat.setAttribute('aria-label', `Seat ${seatId} available`);
    } 
    // If not selected and under limit, select it
    else if (selectedSeats.length < MAX_SEATS && seat.dataset.status !== 'occupied') {
        selectedSeats.push(seatId);
        seat.className = seat.className.replace('available', 'selected').replace('bg-green-500', 'bg-blue-500').replace('border-green-600', 'border-blue-600') + ' shadow-lg ring-2 ring-blue-300';
        seat.dataset.status = 'selected';
        seat.setAttribute('aria-label', `Seat ${seatId} selected`);
    }
    
    updateSelection();
}

/**
 * Update selection display and form inputs
 * Shows selected seats count, calculates total price,
 * enables/disables submit button, updates hidden input
 * Also persists selection to sessionStorage
 * 
 * @requires: selectedSeats array
 * @requires: BASE_PRICE constant
 * @requires: MAX_SEATS constant
 * @updates: #selectedSeatsList, #totalPrice, #confirmBookingBtn, #selectedSeatsInput
 */
function updateSelection() {
    const list = document.getElementById('selectedSeatsList');
    const totalPrice = document.getElementById('totalPrice');
    const confirmBtn = document.getElementById('confirmBookingBtn');
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    
    // Update hidden input for form submission
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
    
    // Persist to sessionStorage for page refresh recovery
    sessionStorage.setItem('selectedSeats', JSON.stringify(selectedSeats));
}

// Debounce seat map regeneration on window resize
window.addEventListener('resize', debounce(generateSeatMap, 250));

/**
 * Debounce utility - limit function execution rate
 * @param {Function} func - Function to debounce
 * @param {number} wait - Milliseconds to wait
 * @returns {Function} Debounced function
 */
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

/**
 * Verify seat availability with server before form submission
 * Calls verify-seats-fixed.php to prevent double-booking race conditions
 * 
 * @async
 * @param {string[]} seats - Array of seat IDs to verify
 * @returns {boolean} True if all seats available, false otherwise
 * 
 * @calls: verify-seats-fixed.php (POST verification API)
 * @see verify-seats-fixed.php (API endpoint)
 * @calls: showError() - If seats not available
 */
async function verifySeatsAvailable(seats) {
    if (!seats || seats.length === 0) {
        showError('No seats selected');
        return false;
    }

    try {
        const formData = new FormData();
        formData.append('movie_id', movieId);
        formData.append('theater', selectedShow.theater);
        formData.append('date', selectedShow.date);
        formData.append('time', selectedShow.time);
        formData.append('seats', seats.join(','));

        const response = await fetch('verify-seats-fixed.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // If verification fails, show error
        if (!data.success || !data.available) {
            showError(data.message || 'Some seats are no longer available');
            return false;
        }

        return true;
    } catch (error) {
        showError('Failed to verify seats. Please try again.');
        return false;
    }
}

/**
 * Display error message to user
 * Shows error div with message and refresh button
 * 
 * @param {string} message - Error message to display
 * @updates: #bookingError div visibility and content
 */
function showError(message) {
    const errorDiv = document.getElementById('bookingError');
    const errorMessage = document.getElementById('errorMessage');
    errorMessage.innerHTML = `${message}<br><br>Please refresh the page and select different seats.` +
        `<div class="mt-2"><button id="refreshSeatsBtn" class="px-3 py-1 bg-blue-600 text-white rounded">Refresh</button></div>`;
    errorDiv.classList.remove('hidden');
    
    const refreshBtn = document.getElementById('refreshSeatsBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', () => location.reload());
    window.scrollTo(0, 0);
}

/**
 * Hide error message div
 */
function hideError() {
    document.getElementById('bookingError').classList.add('hidden');
}

/**
 * Main initialization function
 * Called when DOM is ready - sets up all event listeners and loads initial data
 * 
 * @calls: clearCachedSeats()
 * @calls: populateShowSelectors()
 * @calls: applySelectedShow()
 * @calls: populateDatesForTheater()
 * @calls: populateTimesForTheaterAndDate()
 * @calls: fetchOccupiedSeats()
 * @listens: loadSeatsBtn click
 * @listens: bookingForm submit
 */
function initBooking() {
    // Clear cache on page load to prevent stale seats
    clearCachedSeats();
    
    // Get DOM element references
    showDateSelect = document.getElementById('showDateSelect');
    showTimeSelect = document.getElementById('showTimeSelect');
    showTheaterSelect = document.getElementById('showTheaterSelect');
    loadSeatsBtn = document.getElementById('loadSeatsBtn');
    showSelectionMessage = document.getElementById('showSelectionMessage');
    bookingDetailsDiv = document.getElementById('bookingDetails');

    // Initialize theater dropdown
    populateShowSelectors();
    
    // Restore URL params to dropdowns if present
    applySelectedShow();

    // If theater was already selected (from URL), load dates
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
                // If all selected, load occupied seats immediately
                if (selectedShow.date && selectedShow.time && selectedShow.theater) {
                    fetchOccupiedSeats();
                }
            });
    }

    // Load seats button click handler
    loadSeatsBtn?.addEventListener('click', async function() {
        const theater = showTheaterSelect?.value;
        const date = showDateSelect?.value;
        const time = showTimeSelect?.value;
        
        // Validate all fields selected
        if (!theater || !date || !time) {
            showError('Please select theater, date AND time first');
            return;
        }
        
        loadSeatsBtn.disabled = true;
        loadSeatsBtn.textContent = 'Loading seats...';
        
        // Fetch and display seat map
        await fetchOccupiedSeats();
        
        loadSeatsBtn.disabled = false;
        loadSeatsBtn.textContent = 'Refresh Seats';
        showSelectionMessage.textContent = `Seats loaded for ${theater} (${date} ${time}). Select your seats!`;
    });
}

// Form submission handler - validates and submits booking
document.getElementById('bookingForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Check seats selected
    if (selectedSeats.length === 0) {
        showError('Please select at least one seat');
        return;
    }

    const confirmBtn = document.getElementById('confirmBookingBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Verifying seats...';

    // Server-side verification before final submission
    const seatsAvailable = await verifySeatsAvailable(selectedSeats);

    // If verified, allow form to submit to bookings.php
    if (seatsAvailable) {
        hideError();
        this.submit();
    } else {
        confirmBtn.disabled = false;
        confirmBtn.textContent = `Proceed to Payment (${selectedSeats.length} seats)`;
    }
});

// Initialize on DOM ready (supports both sync and async page loads)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBooking);
} else {
    initBooking();
}
