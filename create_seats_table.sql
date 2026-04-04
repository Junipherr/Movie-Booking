-- Create seats table to track individual seats for each movie/theater combination
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
