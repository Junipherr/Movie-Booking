# Automatic Seat Management System - Setup Guide

## Overview
This guide explains how to set up and use the automatic seat creation and management system for the Movie Booking application.

## What's New

### Features Added:
1. **Automatic Seat Creation** - When an admin adds a movie, 480 seats are automatically created (4 theaters × 10 rows × 12 seats)
2. **Real-time Seat Availability** - Seats are tracked in the database and updated in real-time
3. **Seat Occupancy Management** - When users book seats, they're marked as occupied; when bookings are cancelled, seats are released
4. **Database-Driven Seat Selection** - Booking page now fetches actual seat data instead of generating random demo data

## Database Setup

### Step 1: Create the Seats Table
Run the following SQL in phpMyAdmin or MySQL:

```sql
CREATE TABLE IF NOT EXISTS `seats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `theater` varchar(50) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `occupied` tinyint(1) DEFAULT 0,
  `booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_movie_theater` (`movie_id`,`theater`),
  KEY `idx_seat_unique` (`movie_id`,`theater`,`seat_number`),
  KEY `idx_booking` (`booking_id`),
  FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: Create Seats for Existing Movies (Optional)
If you already have movies in your database, run this SQL to create seats for them:

```sql
-- Generate seats for all existing movies
INSERT INTO seats (movie_id, theater, seat_number)
SELECT m.id, t.theater, CONCAT(r.row, s.seat)
FROM movies m
CROSS JOIN (SELECT 'Screen 1' as theater UNION SELECT 'Screen 2' UNION SELECT 'IMAX' UNION SELECT 'VIP') t
CROSS JOIN (SELECT 'A' as row UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D' UNION SELECT 'E' UNION SELECT 'F' UNION SELECT 'G' UNION SELECT 'H' UNION SELECT 'I' UNION SELECT 'J') r
CROSS JOIN (SELECT 1 as seat UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) s
WHERE NOT EXISTS (
    SELECT 1 FROM seats 
    WHERE seats.movie_id = m.id 
    AND seats.theater = t.theater
    AND seats.seat_number = CONCAT(r.row, s.seat)
);
```

## File Changes

### New Files Created:
1. **`get-seats.php`** - API endpoint to fetch occupied seats for a movie/theater
2. **`includes/seat-management.php`** - Functions to manage seat occupancy:
   - `markSeatsOccupied()` - Mark seats as occupied when booking confirmed
   - `releaseSeats()` - Release seats when booking cancelled
3. **`create_seats_table.sql`** - SQL migration file

### Modified Files:
1. **`admin-movies.php`**
   - Added `createSeatsForMovie()` function
   - Auto-creates 480 seats when a new movie is added
   - Deletes seats when a movie is deleted

2. **`bookings.php`**
   - Added seat marking when booking is created
   - Seats are now linked to bookings

3. **`booking.php`**
   - Fetches actual occupied seats from database
   - Real-time seat availability display
   - Removed random demo seat generation

4. **`cancel-booking.php`**
   - Releases seats when user cancels booking

5. **`delete-booking.php`**
   - Releases seats when user permanently deletes booking

6. **`admin-bookings.php`**
   - Releases seats when admin cancels a booking

## Seat Configuration

### Theater Types:
- Screen 1
- Screen 2
- IMAX
- VIP

### Seat Layout:
- **Rows:** A through J (10 rows)
- **Seats per row:** 1 through 12 (12 seats)
- **Total seats per theater:** 120
- **Total seats per movie:** 480 (4 theaters)

## How It Works

### Adding a Movie (Admin):
1. Admin clicks "Add Movie" button
2. Admin fills in movie details (title, genre, duration, description, poster)
3. Clicks "Save"
4. **System automatically:**
   - Inserts movie into database
   - Creates 480 seats for all 4 theaters
   - Each seat is marked as available (occupied = 0)

### Booking a Seat (User):
1. User selects a movie and show details
2. JavaScript fetches occupied seats via `get-seats.php` API
3. User can only select available seats
4. User clicks "Proceed to Payment"
5. **System:**
   - Creates booking record
   - Marks selected seats as occupied
   - Links seats to booking via booking_id

### Cancelling a Booking:
- When user or admin cancels booking:
  1. All seats linked to that booking are released
  2. Seats are marked as available (occupied = 0)

## Database Queries Reference

### Fetch occupied seats for a theater:
```sql
SELECT seat_number FROM seats 
WHERE movie_id = ? AND theater = ? AND occupied = 1
```

### Create new seats for a movie:
```sql
INSERT INTO seats (movie_id, theater, seat_number) 
VALUES (?, ?, ?)
```

### Mark seats as occupied during booking:
```sql
UPDATE seats 
SET occupied = 1, booking_id = ? 
WHERE movie_id = ? AND theater = ? AND seat_number = ?
```

### Release seats on cancellation:
```sql
UPDATE seats 
SET occupied = 0, booking_id = NULL 
WHERE booking_id = ?
```

## Testing

### Test the System:
1. **Add a Movie:**
   - Go to Admin Panel → Manage Movies
   - Click "+ Add Movie"
   - Fill in details and save
   - Verify 480 seats created in database: `SELECT COUNT(*) FROM seats WHERE movie_id = ?`

2. **Book Seats:**
   - Log in as regular user
   - Select a movie
   - Verify seats load from database (not random)
   - Select some seats and complete booking
   - Verify seats marked as occupied in database

3. **Cancel Booking:**
   - Go to My Bookings
   - Cancel a booking
   - Verify seats marked as available again in database

## Troubleshooting

### Issue: Seats not appearing
- **Check:** Are seats created in database?
- **Solution:** Run the SQL to create seats for existing movies

### Issue: All seats showing as available
- **Check:** Are seats being marked as occupied in database?
- **Solution:** Verify `markSeatsOccupied()` is being called in bookings.php

### Issue: Seats not releasing after cancellation
- **Check:** Is `releaseSeats()` being called in cancel/delete handlers?
- **Solution:** Verify seat-management.php is included in cancel-booking.php

## Future Enhancements

Potential improvements:
1. Add date/time filtering to seat availability
2. Multiple showtimes per day per theater
3. Advance seat selection with interactive theater map
4. Seat hold/reserve feature (e.g., 15 min timeout)
5. Seat pricing tiers (premium seats, etc.)
6. Integration with payment system for real confirmation

## Support

For issues or questions, check:
- Browser console for JavaScript errors
- PHP error logs for backend issues
- Database for seat record verification
