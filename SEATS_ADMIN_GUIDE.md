# 🎬 Automatic Seat Management - Quick Start Guide

## What's New? 
Seats are **automatically created** when you add a movie. Users can now **select real seats** from the database when booking.

## ⚡ Quick Setup (5 Minutes)

### Step 1: Initialize Database (Do This Once)
1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Click on your `movie_booking` database
3. Click the **SQL** tab at the top
4. Copy and paste the SQL below:

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
  UNIQUE KEY `idx_seat_unique` (`movie_id`,`theater`,`seat_number`),
  KEY `idx_movie_theater` (`movie_id`,`theater`),
  KEY `idx_booking` (`booking_id`),
  FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. Click **Go** (bottom right)
6. You should see: ✅ No errors

### Step 2: Create Seats for Existing Movies (If You Have Any)
1. Still in SQL tab, paste and run this:

```sql
INSERT IGNORE INTO seats (movie_id, theater, seat_number)
SELECT m.id, t.theater, CONCAT(r.row, s.seat)
FROM movies m
CROSS JOIN (SELECT 'Screen 1' as theater UNION ALL SELECT 'Screen 2' UNION ALL SELECT 'IMAX' UNION ALL SELECT 'VIP') t
CROSS JOIN (SELECT 'A' as row UNION ALL SELECT 'B' UNION ALL SELECT 'C' UNION ALL SELECT 'D' UNION ALL SELECT 'E' UNION ALL SELECT 'F' UNION ALL SELECT 'G' UNION ALL SELECT 'H' UNION ALL SELECT 'I' UNION ALL SELECT 'J') r
CROSS JOIN (SELECT 1 as seat UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12) s;
```

2. Click **Go**
3. You should see a success message

### Step 3: Verify It's Working
```sql
SELECT COUNT(*) as total_seats FROM seats;
```

Should show a number like 480, 960, 1440 (depending on movies you have)

**✅ Done! Your seat system is ready.**

---

## 📝 How It Works (For Admins)

### When You Add a Movie:
1. Go to **Admin Panel** → **Manage Movies** → **+ Add Movie**
2. Fill in the details (title, genre, duration, poster, description)
3. Click **Save**
4. **System automatically creates:**
   - 480 seats (all initially available)
   - 4 theaters: Screen 1, Screen 2, IMAX, VIP
   - Each theater has: 10 rows × 12 seats

### When You Delete a Movie:
- All 480 seats for that movie are **automatically deleted**
- No manual cleanup needed!

### Managing User Bookings:
- Go to **Admin Panel** → **Bookings**
- View all bookings and their details
- Can change booking status: Pending → Confirmed → Cancelled
- If you cancel a booking: **Seats are automatically released**

---

## 🎫 How It Works (For Users)

### Normal Booking Flow:
1. User selects a movie
2. Chooses date, time, theater
3. **Seat selection page loads with REAL seats from database**
4. **Red seats** = Already booked (occupied)
5. **Green seats** = Available for booking
6. User selects seats (max 10)
7. Clicks "Proceed to Payment"
8. System marks those seats as **OCCUPIED**
9. User completes payment
10. Booking confirmed ✅

### If User Cancels Booking:
- Selected seats **automatically released** back to available
- Other users can now book those seats

---

## 🧮 Seat Configuration

### Per Theater:
- **Rows:** A through J (10 rows)
- **Seats:** 1 through 12 (12 seats per row)
- **Total:** 120 seats per theater
- **Total per Movie:** 480 seats (4 theaters)

### Theater Names:
- Screen 1 (Standard)
- Screen 2 (Standard)
- IMAX (Premium)
- VIP (Premium)

---

## ✅ Testing Checklist

After setup, test the system:

- [ ] Log in to Admin Panel
- [ ] Add a new movie
- [ ] Go to phpmyadmin and check: SELECT COUNT(*) FROM seats WHERE movie_id = [new movie id];
- [ ] Should show 480 seats exactly
- [ ] Log in as a regular user
- [ ] Go to movie booking page
- [ ] Verify seats load from database (should see mix of red and green)
- [ ] Select some seats and complete booking
- [ ] In phpmyadmin check: SELECT COUNT(*) FROM seats WHERE movie_id = [movie id] AND occupied = 1;
- [ ] Should match the number of seats you booked
- [ ] Go back to "My Bookings" and cancel the booking
- [ ] Check the database again - occupied seats should decrease
- [ ] ✅ System working perfectly!

---

## 🆘 Troubleshooting

### Problem: "Seats table doesn't exist"
**Solution:** Run the CREATE TABLE SQL from Step 1 above

### Problem: Movies added but no seats created
**Solution:** Restart your web server or wait a few seconds

### Problem: All seats show as available (no red occupied seats)
**Solution:** This is normal for new movies! Just try booking some and they'll turn red.

### Problem: Can't book movie / "Invalid theater"
**Solution:** Make sure you selected a theater before clicking seats

### Problem: Booked seats not showing as occupied
**Solution:** Check your browser console (F12) for errors, may need to refresh page

---

## 📋 Database Tables

### Seats Table Structure:
| Column | Type | Notes |
|--------|------|-------|
| id | int | Unique seat ID |
| movie_id | int | Which movie |
| theater | varchar | Screen 1, 2, IMAX, or VIP |
| seat_number | varchar | A1, A2, B5, J12, etc |
| occupied | tinyint | 0=available, 1=booked |
| booking_id | int | Links to bookings table |

---

## 🎯 Key Features

✅ **Automatic** - Seats created automatically when movie added
✅ **Real-time** - Seat availability updates instantly
✅ **Persistent** - Seats stored in database (not random demo)
✅ **Linked** - Seats connected to bookings for tracking
✅ **Clean** - Seats released when bookings cancelled
✅ **Scalable** - Works with any number of movies

---

## 📞 Need Help?

Check these files for more details:
- `SEAT_SYSTEM_SETUP.md` - Detailed setup guide
- `SEAT_IMPLEMENTATION_SUMMARY.md` - Technical details
- `setup_seats_migration.sql` - SQL migrations

Or check the database directly in phpMyAdmin!
