# 🧪 Seat-Booking Connection - Testing Checklist

## Pre-Test Setup

- [ ] Database migration completed (`setup_seats_migration.sql` imported)
- [ ] At least one movie exists in database
- [ ] At least 480 seats created for test movie
- [ ] Web server running (XAMPP Apache on)
- [ ] Two browser windows/tabs open (for conflict testing)

---

## Test 1: Seat Display on Booking Page

**Objective:** Verify seats load from database with real occupancy data

**Steps:**
1. [ ] Login as regular user
2. [ ] Go to home page (index.php)
3. [ ] Select a movie
4. [ ] Click to book movie
5. [ ] On booking page, select show details (date, time, theater)

**Expected Results:**
- [ ] Seat map displays with 120 seats (10 rows × 12 seats)
- [ ] Some seats appear red (occupied) - real data from DB
- [ ] Some seats appear green (available)
- [ ] If first booking for new movie: all green (no occupied seats yet)

**Database Verification:**
```sql
SELECT COUNT(*) as occupied FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Cross-reference with red seats shown
```

---

## Test 2: Select Seats & Verify UI

**Objective:** Test seat selection UI and price calculation

**Steps:**
1. [ ] Click 3 different available seats (e.g., A1, B5, C3)
2. [ ] Observe seats change to blue (selected)
3. [ ] Observe total price updates: 3 × ₱12.99 = ₱38.97
4. [ ] Verify selected seats listed: [A1] [B5] [C3]

**Expected Results:**
- [ ] Selected seats highlight in blue
- [ ] Price updates: ₱12.99 per seat
- [ ] Selection summary shows correctly
- [ ] Can select up to 10 seats
- [ ] Cannot select occupied (red) seats

**Edge Cases to Test:**
- [ ] Try clicking occupied seat - should not select
- [ ] Select 10 seats - button should still work
- [ ] Try selecting 11th seat - should not add

---

## Test 3: Seat Verification (Pre-Booking)

**Objective:** Test verification endpoint prevents seat conflicts

**Steps:**
1. [ ] Select seats: A1, B2, C3
2. [ ] Click "Proceed to Payment"
3. [ ] Observe: "Verifying seats..." message
4. [ ] Wait for verification complete

**Expected Results:**
- [ ] Verification runs (button shows loading)
- [ ] If seats available: Redirect to payment.php
- [ ] If seats unavailable: Show error message

**Browser Console:**
- [ ] No JavaScript errors
- [ ] fetch to verify-seats.php succeeds

**Network Check (Browser DevTools):**
1. [ ] Open DevTools → Network tab
2. [ ] Click "Proceed to Payment"
3. [ ] Verify POST request to verify-seats.php
4. [ ] Response shows: `"available": true`

---

## Test 4: Booking Creation & Seat Locking

**Objective:** Verify seats locked in database when booking created

**Pre-Test Database State:**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Note this number (e.g., 5)
```

**Steps:**
1. [ ] Select seats: A1, B2, C3
2. [ ] Click "Proceed to Payment"
3. [ ] Complete booking (form submits)
4. [ ] Redirected to payment.php

**Expected Results:**
- [ ] Successfully redirected to payment.php
- [ ] URL shows: `payment.php?booking_id=X`

**Database Verification (IMPORTANT):**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Should be: [previous count] + 3 = [new count]

SELECT * FROM seats 
WHERE movie_id = [ID] AND seat_number IN ('A1', 'B2', 'C3');
-- Should show:
-- occupied = 1
-- booking_id = [payment booking_id]
```

---

## Test 5: My Bookings - Seat Display

**Objective:** Verify booked seats display in My Bookings

**Prep:**
- [ ] Have completed at least one booking (don't pay yet)
- [ ] Note booking ID and selected seats

**Steps:**
1. [ ] Click "My Bookings" in navbar
2. [ ] Find your pending booking
3. [ ] Look for "Seats" column

**Expected Results:**
- [ ] Table has "Seats" column (new!)
- [ ] Shows selected seats as badges: [A1] [B2] [C3]
- [ ] Seats correctly match what you selected
- [ ] Each seat shown separately in blue badge

**Visual Check:**
```
Row format should be:
| Ticket | Movie | Date & Time | Theater | Seats | Price | Status | Actions |
| #0001  | Movie | Date Time   | Theatre | [A1][B2][C3] | ₱38.97 | Pending | ... |
```

---

## Test 6: Booking Confirmation (Payment)

**Objective:** Complete booking and verify final state

**Steps:**
1. [ ] On payment.php to complete booking
2. [ ] Fill in test payment details:
   - Card: 1234567890123456
   - Expiry: 12/25
   - CVV: 123
3. [ ] Click "Pay Now"
4. [ ] Should redirect to confirmation.php

**Expected Results:**
- [ ] Redirect to confirmation.php
- [ ] Shows booking confirmed
- [ ] Seats still locked in database

**Database State:**
```sql
SELECT status FROM bookings WHERE id = [booking_id];
-- Should show: Confirmed

SELECT occupied, booking_id FROM seats 
WHERE booking_id = [booking_id];
-- All should show: occupied=1, booking_id=[booking_id]
```

---

## Test 7: Seat Conflict Detection (2-Window Test)

**IMPORTANT: Do this with 2 browser windows**

**Setup:**
- [ ] Open 2 separate windows (not tabs)
- [ ] Login in both windows
- [ ] Navigate both to booking page for SAME movie/show

**Steps in Window 1:**
1. [ ] Select seats: A1, B2
2. [ ] Note: Still on booking.php

**Steps in Window 2:**
1. [ ] Select seats: A1, B2 (same seats!)
2. [ ] Note: Still on booking.php

**Steps in Window 1:**
1. [ ] Click "Proceed to Payment"
2. [ ] Complete booking

**Database State After Window 1:**
```sql
SELECT occupied FROM seats WHERE seat_number IN ('A1', 'B2');
-- Should show: occupied=1
```

**Steps in Window 2:**
1. [ ] Click "Proceed to Payment"
2. [ ] Should see: "Seats A1, B2 are no longer available"
3. [ ] Error message displayed
4. [ ] NOT redirected to payment
5. [ ] Can select different seats

**Expected Result:**
- ✅ Window 2 prevented from booking same seats
- ✅ Error message shown
- ✅ Database integrity maintained

---

## Test 8: Booking Cancellation & Seat Release

**Objective:** Verify seats released when booking cancelled

**Prep - Get Database State:**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Note this (e.g., 20)
```

**Steps:**
1. [ ] Go to My Bookings
2. [ ] Find a Pending booking (not Confirmed!)
3. [ ] Click "Cancel"
4. [ ] Confirm cancellation

**Expected Results:**
- [ ] Booking status changed to "Cancelled"
- [ ] Back on My Bookings page
- [ ] Success message displayed

**Database Verification:**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Should be: [previous count] - [cancelled seats] = [new count]

SELECT * FROM seats 
WHERE movie_id = [ID] AND seat_number IN ('[A1]', '[B2]', '[C3]');
-- Should show:
-- occupied = 0
-- booking_id = NULL
```

---

## Test 9: Delete Booking & Seat Release

**Objective:** Verify seats released when booking permanently deleted

**Prep:**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Note: [count_before_delete]
```

**Steps:**
1. [ ] Go to My Bookings
2. [ ] Find a booking to delete (any status)
3. [ ] Click trash icon
4. [ ] Confirm permanent deletion

**Expected Results:**
- [ ] Booking deleted from list
- [ ] Success message displayed
- [ ] Seats released

**Database Check:**
```sql
SELECT COUNT(*) FROM seats 
WHERE movie_id = [ID] AND occupied = 1;
-- Should decrease by number of seats deleted
```

---

## Test 10: Admin Cancel Booking (Bonus)

**Objective:** Verify seats released when admin cancels booking

**Setup:**
- [ ] Have a Pending or Confirmed booking
- [ ] Login as ADMIN
- [ ] Go to Admin Panel → Bookings

**Steps:**
1. [ ] Find the booking
2. [ ] Change status to "Cancelled"
3. [ ] Save

**Expected Results:**
- [ ] Status updated to Cancelled
- [ ] Success message shown

**Database Verification:**
```sql
SELECT occupied, booking_id FROM seats 
WHERE booking_id = [booking_id];
-- Should show: occupied=0, booking_id=NULL
```

---

## Test 11: Error Handling

**Objective:** Test system handles errors gracefully

**Scenario 1: Network Error During Verification**
- [ ] Open DevTools
- [ ] Network tab → Disable network
- [ ] Try to book seats
- [ ] Should show: "Failed to verify seats"

**Scenario 2: Database Error**
```sql
-- Temporarily rename seats table to break queries
RENAME TABLE seats TO seats_backup;
-- Try to book
-- Should show error message (not crash)
-- UNDO: RENAME TABLE seats_backup TO seats;
```

**Scenario 3: Invalid Seats**
- [ ] Try to manually POST invalid seat numbers
- [ ] System should reject gracefully

---

## Test 12: Mobile Responsiveness

**Objective:** Verify seats display correctly on mobile

**Steps:**
1. [ ] Open booking page
2. [ ] Open DevTools (F12)
3. [ ] Toggle Device Toolbar (mobile view)
4. [ ] Select show and view seats

**Expected Results:**
- [ ] Seat map displays without horizontal scroll
- [ ] Seats resized appropriately
- [ ] Touch-friendly seat selection
- [ ] Prices and totals visible
- [ ] Error messages readable

---

## Final Verification Queries

Run these SQL queries to verify complete data integrity:

```sql
-- 1. Check all seats created for movies
SELECT movie_id, COUNT(*) as total_seats FROM seats GROUP BY movie_id;
-- Each movie should have 480 seats

-- 2. Check occupied seats
SELECT COUNT(*) FROM seats WHERE occupied = 1;
-- Should match total seats in bookings

-- 3. Check orphaned bookings (seats reference valid bookings)
SELECT s.* FROM seats s 
LEFT JOIN bookings b ON s.booking_id = b.id 
WHERE s.booking_id IS NOT NULL AND b.id IS NULL;
-- Should return 0 rows

-- 4. Check seat-booking links
SELECT b.id, COUNT(s.id) as seat_count 
FROM bookings b 
LEFT JOIN seats s ON s.booking_id = b.id 
WHERE b.status = 'Confirmed' 
GROUP BY b.id;
-- seat_count should match number of seats in booking

-- 5. Check cancelled bookings have no linked seats
SELECT COUNT(*) FROM seats 
WHERE booking_id IN (
  SELECT id FROM bookings WHERE status = 'Cancelled'
);
-- Should return 0 (cancelled bookings have no active seats)
```

---

## ✅ Sign-Off Checklist

After completing all tests, check off:

- [ ] All 12 tests passed
- [ ] No JavaScript errors in console
- [ ] No PHP errors in server logs
- [ ] Database queries return expected results
- [ ] Seat display matches database state
- [ ] Conflicts detected correctly
- [ ] Mobile view works properly
- [ ] Error handling works
- [ ] Cancellations work properly

---

## 🎉 Success!

If all tests pass, the seat-booking connection system is working perfectly!

**Next Steps:**
1. Deploy to production
2. Monitor for issues
3. Collect user feedback
4. Consider future enhancements

**Ready for launch!** ✅
