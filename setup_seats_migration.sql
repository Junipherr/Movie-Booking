-- Movie Booking System - Automatic Seat Management Migration
-- Run this file in phpMyAdmin to set up the seats table

-- Create the seats table
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

-- Generate seats for all existing movies (run only once)
-- This creates 480 seats per movie (4 theaters × 10 rows × 12 seats)
INSERT IGNORE INTO seats (movie_id, theater, seat_number)
SELECT m.id, t.theater, CONCAT(r.row, s.seat)
FROM movies m
CROSS JOIN (
    SELECT 'Screen 1' as theater 
    UNION ALL SELECT 'Screen 2' 
    UNION ALL SELECT 'IMAX' 
    UNION ALL SELECT 'VIP'
) t
CROSS JOIN (
    SELECT 'A' as row UNION ALL SELECT 'B' UNION ALL SELECT 'C' UNION ALL SELECT 'D' 
    UNION ALL SELECT 'E' UNION ALL SELECT 'F' UNION ALL SELECT 'G' UNION ALL SELECT 'H' 
    UNION ALL SELECT 'I' UNION ALL SELECT 'J'
) r
CROSS JOIN (
    SELECT 1 as seat UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) s;

-- Verify: Check total seats created
SELECT movie_id, COUNT(*) as total_seats FROM seats GROUP BY movie_id;
