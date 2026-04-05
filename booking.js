const urlParams = new URLSearchParams(window.location.search);

function safeParseJson(value, fallback) {
    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

// Clear cached seats on fresh page load/refresh
function clearCachedSeats() {
    sessionStorage.removeItem('selectedSeats');
    sessionStorage.removeItem('selectedShow');
}

const selectedShow = {
    date: urlParams.get('date') || '',
    time: urlParams.get('time') || '',
    theater: urlParams.get('theater') || ''
};

const movieId = urlParams.get('movie_id');
let showDateSelect, showTimeSelect, showTheaterSelect, loadSeatsBtn, showSelectionMessage, bookingDetailsDiv;
let selectedSeats = [];
const BASE_PRICE = 250.00;
const MAX_SEATS = 10;
let occupiedSeats = [];

function populateShowSelectors() {
    if (!movieId) {
        return;
    }

    if (showTheaterSelect) {
        showTheaterSelect.innerHTML = `
            <option value="">Select theater</option>
            <option value="Screen 1">Screen 1</option>
            <option value="Screen 2">Screen 2</option>
            <option value="IMAX">IMAX</option>
            <option value="VIP">VIP</option>
        `;
        showTheaterSelect.disabled = false;
    }

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

function setupCascadingSelectors() {
    if (showTheaterSelect) {
        showTheaterSelect.addEventListener('change', function() {
            const selectedTheater = this.value;
            selectedShow.theater = selectedTheater;
            document.getElementById('theaterInput').value = selectedTheater;
            
            if (selectedTheater) {
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

    if (showDateSelect) {
        showDateSelect.addEventListener('change', function() {
            const selectedDate = this.value;
            const selectedTheater = showTheaterSelect ? showTheaterSelect.value : '';
            
            if (selectedDate && selectedTheater) {
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

    if (showTimeSelect) {
        showTimeSelect.addEventListener('change', function() {
            const selectedTime = this.value;
            selectedShow.time = selectedTime;
            
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

function populateDatesForTheater(theater, preserveDate = false) {
    if (!movieId || !theater) {
        return Promise.resolve();
    }

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

function populateTimesForTheaterAndDate(theater, date, preserveTime = false) {
    if (!movieId || !theater || !date) return Promise.resolve();

    if (showTimeSelect) {
        showTimeSelect.innerHTML = '<option>Loading times...</option>';
        showTimeSelect.disabled = true;
    }

    selectedShow.date = date;
    if (!preserveTime) {
        selectedShow.time = '';
    }

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

            if (showTimeSelect && data.times && data.times.length > 0) {
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

async function fetchOccupiedSeats() {
    try {
        if (!movieId || !selectedShow.theater || !selectedShow.date || !selectedShow.time) {
            return;
        }

        const apiUrl = `get-seats-fixed.php?movie_id=${movieId}&theater=${encodeURIComponent(selectedShow.theater)}&date=${encodeURIComponent(selectedShow.date)}&time=${encodeURIComponent(selectedShow.time)}`;
        
        const response = await fetch(apiUrl);
        const data = await response.json();

        if (data.success && data.occupied_seats) {
            occupiedSeats = data.occupied_seats;
        }
        
        generateSeatMap();
    } catch (error) {
        generateSeatMap();
    }
}

const getSeatConfig = () => {
    const width = window.innerWidth;
    if (width < 640) return { seatsPerSide: 5, totalSeats: 10, aisleAfter: 5 };
    if (width < 1024) return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };
    return { seatsPerSide: 6, totalSeats: 12, aisleAfter: 6 };
};

function generateSeatMap() {
    const seatMap = document.getElementById('seatMap');
    if (!seatMap) {
        return;
    }

    const rows = 'ABCDEFGHIJ';
    const config = getSeatConfig();
    let html = '';
    
    for (let rowIndex = 0; rowIndex < rows.length; rowIndex++) {
        const row = rows[rowIndex];
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
        for (let seat = 1; seat <= totalSeats; seat++) {
            const seatId = `${row}${seat}`;
            const isOccupied = occupiedSeats.includes(seatId);
            const status = isOccupied ? 'occupied' : 'available';
            
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
            
            const seatClasses = status === 'occupied' 
                ? 'bg-red-500 border-red-600 text-white cursor-not-allowed'
                : 'bg-green-500 border-green-600 text-white shadow-lg cursor-pointer transition-all duration-200 hover:scale-110 active:scale-95 touch-manipulation';
            
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
    
    seatMap.innerHTML = html;
    
    const availableSeats = document.querySelectorAll('.seat.available');
    availableSeats.forEach(seat => {
        seat.addEventListener('click', handleSeatClick);
        seat.setAttribute('tabindex', '0');
        seat.setAttribute('role', 'button');
        seat.style.cursor = 'pointer';
    });
    
    updateSelection();
}

function handleSeatClick(e) {
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

window.addEventListener('resize', debounce(generateSeatMap, 250));

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

function hideError() {
    document.getElementById('bookingError').classList.add('hidden');
}

function initBooking() {
    // Clear cache on page load to prevent stale seats
    clearCachedSeats();
    
    showDateSelect = document.getElementById('showDateSelect');
    showTimeSelect = document.getElementById('showTimeSelect');
    showTheaterSelect = document.getElementById('showTheaterSelect');
    loadSeatsBtn = document.getElementById('loadSeatsBtn');
    showSelectionMessage = document.getElementById('showSelectionMessage');
    bookingDetailsDiv = document.getElementById('bookingDetails');

    populateShowSelectors();
    
    applySelectedShow();

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
                    fetchOccupiedSeats();
                }
            });
    }

    loadSeatsBtn?.addEventListener('click', async function() {
        const theater = showTheaterSelect?.value;
        const date = showDateSelect?.value;
        const time = showTimeSelect?.value;
        
        if (!theater || !date || !time) {
            showError('Please select theater, date AND time first');
            return;
        }
        
        loadSeatsBtn.disabled = true;
        loadSeatsBtn.textContent = 'Loading seats...';
        
        await fetchOccupiedSeats();
        
        loadSeatsBtn.disabled = false;
        loadSeatsBtn.textContent = 'Refresh Seats';
        showSelectionMessage.textContent = `Seats loaded for ${theater} (${date} ${time}). Select your seats!`;
    });
}

document.getElementById('bookingForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('SUBMIT DEBUG:', {selectedSeats, length: selectedSeats.length});
    
    if (selectedSeats.length === 0) {
        showError('Please select at least one seat');
        return;
    }

    const confirmBtn = document.getElementById('confirmBookingBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Verifying seats...';

    const seatsAvailable = await verifySeatsAvailable(selectedSeats);


    if (seatsAvailable) {
        hideError();
        this.submit();
    } else {
        confirmBtn.disabled = false;
        confirmBtn.textContent = `Proceed to Payment (${selectedSeats.length} seats)`;
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBooking);
} else {
    initBooking();
}
