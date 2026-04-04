-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 02:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `movie_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `movie_title` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `theater` varchar(50) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `price` decimal(10,2) DEFAULT 12.99,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `movie_id`, `movie_title`, `date`, `time`, `theater`, `status`, `price`, `created_at`) VALUES
(3, 2, 3, 'Mission Impossible: Dead Reckoning', '2026-03-26', '10:00:00', 'Screen 1', 'Pending', 12.99, '2026-03-26 11:22:21'),
(4, 2, 8, 'Avengers: Endgame', '2026-03-27', '10:00:00', 'Screen 1', 'Pending', 12.99, '2026-03-26 12:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `poster_url` varchar(500) DEFAULT NULL,
  `genre` varchar(50) NOT NULL,
  `duration` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `poster_url`, `genre`, `duration`, `created_at`) VALUES
(4, 'Barbie', 'Barbie leaves the ideal world of Barbieland to find true happiness.', 'uploads/poster_69c51f6944db51.73069753.jpg', 'Comedy', '114 min', '2026-03-26 05:44:28'),
(5, 'Super Mario Bros Movie', 'Mario embarks on a mission to rescue Luigi from Bowser.', 'uploads/poster_69c51ff1107d22.03935025.jpg', 'Comedy', '92 min', '2026-03-26 05:44:28'),
(8, 'Avengers: Endgame', 'The Avengers assemble once more to reverse Thanos actions.', 'uploads/poster_69c52333312d36.36155337.jpg', 'Sci-Fi', '181 min', '2026-03-26 05:56:26'),
(9, 'John Wick 4', 'John Wick uncovers a path to defeat the High Table.', 'uploads/poster_69c51dd0f0fbe2.25608604.jpg', 'Action', '169 min', '2026-03-26 05:56:26'),
(10, 'Mission Impossible: Dead Reckoning', 'Ethan Hunt races against time to stop a rogue AI.', 'uploads/poster_69c51e4e40e431.08010799.jpg', 'Action', '163 min', '2026-03-26 05:56:26'),
(13, 'Oppenheimer', 'The story of American scientist J. Robert Oppenheimer.', 'uploads/poster_69c520f4dba4e8.53064966.jpg', 'Drama', '180 min', '2026-03-26 05:56:26'),
(14, 'One Piece Live-Action', 'The series begins with the East Blue Saga, introducing Luffy, a young pirate with the ability to stretch his body after eating a Devil Fruit. Luffy sets out to become the King of the Pirates and gather a crew, the Straw Hat Pirates, each with their own dreams and skills.', 'uploads/poster_69c521edb72d10.10537901.jpg', 'Comedy', '206 min', '2026-03-26 05:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `theater` varchar(50) NOT NULL,
  `seats_available` int(11) DEFAULT 100,
  `price` decimal(10,2) DEFAULT 12.99,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$RjZr9rCE3WEtEluYQ7QqPuDlDVPUoFMKQEBtwPLtZ9QE7ZlQ8RZ/W', 'admin', '2026-03-26 04:52:52'),
(2, 'Junipher', 'Junipherjunipher@gmail.com', '$2y$10$rYBW4Kb/HodnZdyWOlWiIeKQI.jRZ4B9Fq4jthwsYweA04zbckchm', 'user', '2026-03-26 04:53:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_movie` (`movie_id`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movie_date` (`movie_id`,`date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
