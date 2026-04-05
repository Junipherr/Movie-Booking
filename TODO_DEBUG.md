# Movie Booking DEBUG - Remaining Issues

## Issue 1: "Failed to verify seats" (Collation Mismatch)
**Root cause**: `verify-seats-fixed.php` JOIN fails:
```
Illegal mix of collations (utf8mb4_unicode_ci vs utf8mb4_general_ci)
```

**Fix**:
```sql
ALTER TABLE showtimes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
**Status**: [ ] Run SQL

## Issue 2: SessionStorage Cache Persists
**Current**: `sessionStorage.selectedSeats` caches seats across refreshes
**Problem**: User selects same cached seats → "already occupied" loop

**Fix**:
- Clear `sessionStorage` on payment success (`confirmation.php`)
- **No server-side cache** - already DB-driven
**Status**: [ ] Add to confirmation.php

## Issue 3: Seat Marking (DB vs Cache)
**Current**: Seats marked in DB (`occupied=1`)
**Display**: JS fetches fresh from `get-seats-fixed.php` (bookings JOIN)
**No cache marking** - already real-time DB

## Test After Fixes
```
1. Run collation SQL
2. booking.php?movie_id=21 → NEW seats → Payment ✅
3. Refresh → RED "X" occupied seats ✅
4. Admin → bookings table → see Confirmed booking
```

**Priority**: Collation fix → test → complete!
