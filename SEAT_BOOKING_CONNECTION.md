# 🎫 Seat-Booking Connection System - User Guide

## Overview

The seat-booking connection system ensures that:
- **Seats are correctly linked to bookings** in the database
- **Seat availability is verified** before booking confirmation
- **Selected seats are displayed** in My Bookings
- **Seats are automatically locked** during checkout process
- **Seats are released** when bookings are cancelled

---

## System Flow

### 1. ✅ Seat Selection Phase

```
User selects movie
       ↓
booking.php loads
       ↓
JavaScript fetches real seats from database
  - Green seats = Available (occupied = 0)
  - Red seats = Already booked (occupied = 1)
       ↓
User selects up to 10 seats
       ↓
Selected seats shown in blue
       ↓
Total price calculated: ₱12.99 per seat
```

### 2. 🔐 Verification Phase (NEW)

```
User clicks "Proceed to Payment"
       ↓
JavaScript calls verify-seats.php
       ↓
Server checks: Are selected seats still available?
       ↓
If ANY seats booked by another user:
  → Show error message
  → Allow user to reselect
       ↓
If ALL seats available:
  → Allow booking to proceed
```

### 3. 📝 Booking Confirmation Phase

```
Booking form submitted to bookings.php
       ↓
Server verifies seats again (double-check)
       ↓
Creates booking record in database
       ↓
Calls markSeatsOccupied():
  - Updates each seat: occupied = 1, booking_id = [booking_id]
       ↓
Redirects to payment.php
```

### 4. 💳 Payment Phase

```
User on payment page
       ↓
Seats remain locked (occupied = 1)
       ↓
If user completes payment:
  → Booking confirmed
  → Seats locked permanently
       ↓
If user cancels payment:
  → Seats released (occupied = 0)
  → User can book different seats
```

### 5. ✚ My Bookings Display

```
User goes to My Bookings
       ↓
System displays for each booking:
  - Movie title
  - Date & Time
  - Theater
  - BOOKED SEATS (new column!)
  - Price
  - Status
       ↓
Each seat shown as colored badge
Example: [A1] [B3] [B4]
```

---

## New Files & Changes

### ✅ New File: `verify-seats.php`
- **Purpose:** API endpoint to verify seat availability
- **When called:** Before booking submission
- **Returns:** JSON with availability status
- **Usage:** JavaScript POST request

**Example Request:**
```javascript
POST /verify-seats.php
{
  movie_id: 1,
  theater: "Screen 1",
  seats: "A1,A2,B5"
}
```

**Example Response:**
```json
{
  "success": true,
  "available": true,
  "message": "All seats are available"
}
```

**If seats unavailable:**
```json
{
  "success": false,
  "available": false,
  "unavailable_seats": ["A1", "B5"],
  "message": "Some seats are no longer available: A1, B5"
}
```

### ✅ Enhanced: `booking.php`
- Added client-side seat verification before submission
- Added error display for seat conflicts
- Shows "Verifying seats..." during check
- Prevents booking if seats become unavailable

**New Features:**
```javascript
// Verify seats before form submission
verifySeatsAvailable(selectedSeats)
  → Calls verify-seats.php
  → Shows error if any seats unavailable
  → Only submits if all checks pass

// Error handling
showError() → Display error message
hideError() → Clear error message
```

### ✅ Enhanced: `bookings.php`
- Added double-check seat verification before insertion
- Rollback booking if seat marking fails
- Better error messages for users

**New Validation:**
```php
// Before creating booking:
1. Check all seats are still available
2. If any occupied: abort with error
3. If all available: create booking
4. Mark seats as occupied
5. If marking fails: delete booking
```

### ✅ Enhanced: `my-bookings.php`
- Added "Seats" column to bookings table
- Displays each booked seat as colored badge
- Shows seat layout clearly

**Display Example:**
```
Ticket  Movie              Date      Theater    Seats         Price  Status
#0042   Avengers: Endgame  Apr 5, 26 Screen 1  [A1] [B3] [B4] ₱38.97 Pending
```

---

## Database Changes

### Seats Table Relationships

```
bookings table
     ↓
     seats.booking_id (Foreign Key)
     ↓
seats table (new column values)

When booking created:
  INSERT INTO bookings (...)              → booking_id = 5
  UPDATE seats SET occupied=1, booking_id=5 WHERE seat_number IN (...)

When booking cancelled:
  UPDATE seats SET occupied=0, booking_id=NULL WHERE booking_id = 5
```

### Seat Status in Database

| Status | occupied | booking_id | Meaning |
|--------|----------|-----------|---------|
| Available | 0 | NULL | Can be booked |
| Locked (Pending) | 1 | [ID] | User checking out |
| Locked (Confirmed) | 1 | [ID] | User completed payment |
| Released | 0 | NULL | Booking cancelled |

---

## How Users Experience It

### Scenario 1: Normal Booking (Happy Path)

```
1. User selects seats: A1, B3, B4
2. Clicks "Proceed to Payment"
3. System: "Verifying seats..."
4. System: ✓ All seats available
5. Redirects to payment
6. User completes payment
7. Booking confirmed!
8. In My Bookings: Shows [A1] [B3] [B4]
```

### Scenario 2: Seats Became Unavailable

```
1. User selects seats: A1, B3, B4
2. (Meanwhile, another user books seat B3)
3. User clicks "Proceed to Payment"
4. System: "Verifying seats..."
5. System: ✗ Seat B3 is now booked!
6. Shows error: "Seat B3 is no longer available"
7. User can retry with different seats
```

### Scenario 3: Cancelling Booking

```
1. User in My Bookings
2. Has booking with seats: [A1] [B3] [B4]
3. Clicks "Cancel"
4. Confirms cancellation
5. Booking status changed to "Cancelled"
6. Seats A1, B3, B4 marked as available
7. Other users can now book those seats
```

---

## Technical Implementation

### Backend Seat Management

```php
// Mark seats as occupied
markSeatsOccupied($conn, $booking_id, $movie_id, $theater, $seats_csv)
  → For each seat: UPDATE occupied=1, booking_id=$booking_id

// Release seats on cancellation
releaseSeats($conn, $booking_id)
  → For all seats with booking_id: UPDATE occupied=0, booking_id=NULL

// Verify seats available
SELECT occupied FROM seats WHERE seat_number IN (...)
  → If any occupied=1: abort
  → If all occupied=0: proceed
```

### Frontend Verification

```javascript
// Before form submission
async function verifySeatsAvailable(seats)
  → POST to verify-seats.php
  → Wait for response
  → If available: return true
  → If unavailable: show error, return false

// Form submission handler
bookingForm.addEventListener('submit', async (e) => {
  e.preventDefault()
  const ok = await verifySeatsAvailable(selectedSeats)
  if (ok) form.submit()
})
```

---

## Testing the Connection

### Test 1: Verify Seats Display in My Bookings
```
1. Create a booking with seats: A1, C5, D7
2. Go to My Bookings
3. Should see column: | Seats |
4. Should shows: [A1] [C5] [D7]
```

### Test 2: Verify Seat Locking Works
```
Database check:
SELECT occupied, booking_id FROM seats WHERE seat_number IN ('A1', 'C5', 'D7');
Result should show: occupied=1, booking_id=[booking_id]
```

### Test 3: Verify Seat Release Works
```
1. Cancel booking
2. Check My Bookings: status now "Cancelled"
3. Query database:
   SELECT * FROM seats WHERE seat_number IN ('A1', 'C5', 'D7');
4. Should show: occupied=0, booking_id=NULL
```

### Test 4: Verify Seat Conflict Detection
```
1. Open booking page in 2 browser windows
2. In window 1: Select seats A1, B2
3. In window 2: Select seats A1, B2 (same seats!)
4. Window 1: Complete payment (booking confirmed)
5. Window 2: Try to complete payment
6. System should reject with: "A1, B2 are no longer available"
```

---

## Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Some seats are no longer available" | Another user booked them | Try different seats |
| "Failed to verify seats" | Network error | Refresh page and try again |
| "Invalid booking data" | Form incomplete | Select 1-10 seats |
| "Seat verification error" | Database issue | Contact support |

---

## Benefits of This System

✅ **Real-time validation** - Prevents overbooking
✅ **Conflict prevention** - Handles simultaneous bookings
✅ **Transparency** - Users see exactly which seats they booked
✅ **Auditability** - Complete seat-booking relationship traceable
✅ **Cancellation support** - Seats properly released
✅ **Database integrity** - Foreign key relationships maintained

---

## Under the Hood

### When a seat is booked:
```
seats table gets updated:
occupied: 0 → 1
booking_id: NULL → [booking_id]
```

### When booking is displayed:
```
bookings table shows: seats = "A1,B3,B4"
My Bookings parses this and shows:
[A1] [B3] [B4]
```

### When booking is cancelled:
```
seats table updated:
occupied: 1 → 0
booking_id: [booking_id] → NULL
```

---

## Summary

The seat-booking connection ensures:

1. ✅ **Seats correctly linked** to bookings in database
2. ✅ **Availability verified** before booking confirmed
3. ✅ **Conflicts detected** between simultaneous bookings
4. ✅ **Clear display** of booked seats in My Bookings
5. ✅ **Proper cleanup** when bookings cancelled
6. ✅ **Complete audit trail** of seat status changes

**System is now production-ready!** ✅
