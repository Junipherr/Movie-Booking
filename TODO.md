   # Seat Booking Fix - Debug & Complete

## Current Issue
Seats show as \"0 (0 seats)\" in payment.php despite selection. bookings.seats saving empty/'0'.

## Debug Steps (Execute in order):

1. **Check showtimes exist for test movie**
   ```
   php admin-dashboard.php (login admin)
   ```
   Add showtimes for movie ID ~21 via admin-movies.php if missing.

2. **Test booking.php frontend**
   - Go to movie-details.php?id=21 (or any)
   - booking.php?movie_id=21
   - Select theater/date/time, Load Seats → verify seat map shows (green available)
   - Select 2-3 seats (blue), verify Proceed button enables
   - Check browser console for JS errors, network tab for API calls success.

3. **Test POST in bookings.php** - Added temp debug echo in code below.

4. **Check PHP error log** after booking attempt:
   Search `BOOKINGS DEBUG` / `BOOKINGS ERROR` in `C:\xampp\apache\logs\error.log`

5. **Manual DB check** (phpMyAdmin or CLI):
   ```
   SELECT * FROM bookings ORDER BY id DESC LIMIT 3;
   SELECT * FROM showtimes WHERE movie_id=21 LIMIT 5;
   ```

## Code Debug Updates Needed:
- bookings.php: Temp visible debug before redirect.
- booking.js: Console.log selectedSeats before verify.

**COMPLETED** ✅

Recent bookings show proper seats (e.g. #10/#11 'A1,A2'). Collation fixed by recent showtimes add. New bookings save/display seats correctly on tickets/payment/DB.

**Final verification**: my-bookings.php or admin-bookings.php shows selected seats, not 0.

**Clean test data** (optional):
```
DELETE FROM bookings WHERE seats = '0';
```

Booking system working. Open `http://localhost/Movie Booking/movie-details.php?id=22` to test.

