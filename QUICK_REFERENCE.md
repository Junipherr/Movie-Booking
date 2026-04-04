# 🎬 Automatic Seat Management System - Quick Reference Card

## 🚀 Installation (1 Command)

```bash
# Import this SQL file in phpMyAdmin:
setup_seats_migration.sql
```

---

## 📊 System Architecture at a Glance

```
┌─────────────────────────────────────────────────────┐
│              MOVIE BOOKING SYSTEM                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Admin Panel ──► Add Movie ──► Auto-Create Seats   │
│                                 (480 per movie)     │
│                                 │                   │
│                                 ▼                   │
│                          Database Seeds            │
│                          ┌─────────────────┐       │
│                          │ 4 Theaters      │       │
│                          │ 120 seats each  │       │
│                          │ All available   │       │
│                          └─────────────────┘       │
│                                 │                   │
│          ┌──────────────────────┼──────────────────┐
│          ▼                      ▼                   ▼
│    User Booking         API Query            Admin View
│         │               /get-seats.php           │
│         ▼               │                        ▼
│    See Available        │                  Manage Bookings
│    Seats (Green)        │                  - View all
│         │               │                  - Change status
│         ▼               │                  - Cancel (release)
│    Select & Book        │                       
│    Mark Occupied◄───────┴──────────────────────┐
│         │                                       │
│         ▼                                       │
│    Cancel (Release)──────► Seats Available Again
│                                       
└─────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Tables

### `seats` Table
```
Column          | Type      | Purpose
────────────────┼───────────┼──────────────────
id              | INT PK    | Unique seat ID
movie_id        | INT FK    | Which movie
theater         | VARCHAR   | Screen 1/2/IMAX/VIP
seat_number     | VARCHAR   | A1-J12
occupied        | TINYINT   | 0=free, 1=booked
booking_id      | INT FK    | Links to booking
created_at      | TIMESTAMP | Creation time
```

**Key:** (movie_id, theater, seat_number) = Unique

---

## 📱 Seat Layout

```
        A1  A2  A3  A4  A5  A6 | A7  A8  A9  A10 A11 A12
        B1  B2  B3  B4  B5  B6 | B7  B8  B9  B10 B11 B12
        ... (continues) ...
        J1  J2  J3  J4  J5  J6 | J7  J8  J9  J10 J11 J12

Per Theater: 10 rows × 12 seats = 120 seats
Total Movie: 4 theaters × 120 = 480 seats
```

---

## 🔄 Key Functions

### Admin Side
```php
createSeatsForMovie($conn, $movie_id)
  → Creates 480 seats for a new movie
  → Called: When movie added
  → Creates: 4 theaters × 10 rows × 12 seats

// When movie deleted:
DELETE FROM seats WHERE movie_id = ?
```

### User Booking Side
```php
markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $seats)
  → Marks selected seats as occupied
  → Links to booking_id for tracking

releaseSeats($conn, $booking_id)
  → Marks seats as available
  → Clears booking_id
  → Called on: cancel/delete/payment-cancel
```

### API Side
```php
GET /get-seats.php?movie_id=1&theater=Screen%201
  → Response: {"occupied_seats": ["A1", "B5", ...]}
```

---

## 🎫 Seat Status Codes

| Color | Status | Clickable | Meaning |
|-------|--------|-----------|---------|
| 🟢 Green | available | YES | User can book |
| 🔵 Blue | selected | YES | User has chosen |
| 🔴 Red | occupied | NO | Already booked |

---

## 📋 Workflow Summary

### Adding a Movie (Admin)
```
1. Admin Panel → Manage Movies → + Add Movie
2. Fill details (title, genre, etc.)
3. Click Save
4. AUTOMATIC: 480 seats created, all available

DB Result: 480 new rows in seats table
```

### Booking a Seat (User)
```
1. User selects movie & show time
2. Booking page loads
   - JavaScript fetches /get-seats.php
   - Shows occupied seats in RED
   - Shows available seats in GREEN
3. User selects up to 10 seats
4. Clicks "Proceed to Payment"
5. AUTOMATIC: Selected seats marked occupied

DB Result: occupied=1 for selected seats
           booking_id = current booking
```

### Cancelling Booking (User/Admin)
```
1. User goes to My Bookings → Cancel
   OR
   Admin goes to Bookings → Change to Cancel
2. AUTOMATIC: All seats released

DB Result: occupied=0, booking_id=NULL for those seats
           Seats immediately available for others
```

---

## 💾 SQL Quick Reference

### Check All Seats
```sql
SELECT COUNT(*) FROM seats;
-- Shows: total seats in system
```

### Check Seats per Movie
```sql
SELECT movie_id, COUNT(*) FROM seats GROUP BY movie_id;
-- Shows: 480 seats per movie (if configured)
```

### Find Occupied Seats for Theater
```sql
SELECT seat_number FROM seats 
WHERE movie_id = 1 AND theater = 'Screen 1' AND occupied = 1;
-- Shows: All booked seats for that theater
```

### Check Booking Links
```sql
SELECT booking_id, COUNT(*) FROM seats 
WHERE occupied = 1 
GROUP BY booking_id;
-- Shows: Seats reserved per booking
```

### Release Seats for a Booking
```sql
UPDATE seats SET occupied = 0, booking_id = NULL 
WHERE booking_id = 5;
-- Releases: All seats tied to booking #5
```

---

## 🆘 Common Issues & Fixes

| Issue | Check | Fix |
|-------|-------|-----|
| No seats appear | seats table exists? | Run migration SQL |
| All seats available | After booking, still green? | Refresh page, check DB |
| Can't see occupied | Page not loading data? | Check browser console |
| Admin can't cancel | Status not changing? | Verify admin role |
| Seats not releasing | After cancel, still red? | Check if releaseSeats() called |

---

## 📞 Support Files

| File | Purpose |
|------|---------|
| `SEATS_ADMIN_GUIDE.md` | Admin setup & usage |
| `SEAT_SYSTEM_SETUP.md` | Detailed technical setup |
| `SEAT_IMPLEMENTATION_SUMMARY.md` | Implementation details |
| `IMPLEMENTATION_VERIFICATION.md` | Verification checklist |
| `setup_seats_migration.sql` | Migration script (RUN THIS) |

---

## 🎯 Key Files Changed

```
admin-movies.php      ← Auto-creates seats on add
booking.php           ← Fetches real seats from DB
bookings.php          ← Marks seats occupied on book
cancel-booking.php    ← Releases seats on cancel
get-seats.php         ← API for seat queries
includes/seat-management.php ← Core functions
```

---

## ✅ Pre-Launch Testing

```
□ Run migration SQL
□ Add a test movie
□ Verify 480 seats created
□ Book some seats
□ Verify seats marked occupied
□ Cancel booking
□ Verify seats marked available
□ Try booking again - same seats available
✅ System Ready!
```

---

## 🚀 Go Live Checklist

- [ ] Run `setup_seats_migration.sql`
- [ ] Test complete booking flow
- [ ] Verify seat occupancy in database
- [ ] Test cancellation releases seats
- [ ] Monitor database for errors
- [ ] Train admins on system
- [ ] Announce to users

**System is production-ready! ✅**
