<?php
/**
 * Database Configuration File
 * 
 * Establishes connection to MySQL database for the Movie Booking system.
 * Used by all PHP files that need database access.
 * 
 * @required-by: All pages requiring database connectivity
 * @connection-target: MySQL (XAMPP) - database: movie_booking
 * 
 * @see includes/auth.php
 * @see includes/seat-management.php
 */

// Database connection settings for XAMPP local server
define('DB_HOST', 'localhost');      // MySQL server hostname
define('DB_USER', 'root');           // XAMPP default MySQL username
define('DB_PASS', '');               // XAMPP default password (empty)
define('DB_NAME', 'movie_booking');  // Database name

// Create MySQLi connection instance
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verify database connection; terminate on failure
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding for proper UTF-8 support
$conn->set_charset("utf8mb4");
?>

