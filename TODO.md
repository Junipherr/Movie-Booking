# Seat Selection Fix - Implementation TODO

**Status: CORE FIXES COMPLETE** ✅ (get-seats-fixed.php, verify-seats-fixed.php created)

## Steps Completed:
- [x] **Step 1**: get-seats-fixed.php (correct SQL via bookings+showtimes JOIN)
- [x] **Step 2**: verify-seats-fixed.php (overlap check fixed)
- [x] **Step 3**: Test data tools ready (add-showtimes-21.php, test-movie-21.php)

## Remaining:
- [ ] **Step 4**: Update booking.php to use *-fixed.php endpoints + test
- [ ] **Step 5**: Replace originals with fixed versions
- [ ] **Final Test**: booking.php?movie_id=21 → clickable seats → payment

## Test Sequence:
1. Run `php add-showtimes-21.php` (if movie 21 exists)
2. Visit: http://localhost/Movie Booking/booking.php?movie_id=21
3. Select theater/date/time → Load Seats → **Verify green clickable seats**
4. Select seats → Proceed to Payment

**Next Action**: Need to know if movie_id=21 exists (run test-movie-21.php) and update booking.php JS.

**Test Commands Ready:**
```
php test-movie-21.php
php add-showtimes-21.php  # If needed
```

