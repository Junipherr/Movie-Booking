<?php
/**
 * Authentication Helper Functions
 * 
 * Provides session-based authentication middleware for the Movie Booking system.
 * Handles login checks and role-based access control for users and admins.
 * 
 * @provides: require_login(), require_user(), require_admin()
 * @requires: $_SESSION variable to be set upon successful login
 * 
 * @used-by: login.php (post-login), register.php, index.php, booking.php, 
 *           my-bookings.php, confirmation.php, admin-dashboard.php, 
 *           admin-movies.php, admin-bookings.php, bookings.php, payment.php
 * 
 * @see login.php
 * @see logout.php
 * @see admin-dashboard.php
 */

session_start();

/**
 * Checks if user is logged in; redirects to login if not authenticated.
 * 
 * @global array $_SESSION - must contain 'user_id' key for authenticated users
 * @redirects: login.php if session user_id is not set
 * @see login.php:24-33 (session set after successful login)
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Ensures user is logged in and has regular user role (not admin).
 * Redirects to admin dashboard if user has admin role.
 * 
 * @global array $_SESSION - must contain 'user_id' and 'user_role' keys
 * @calls: require_login()
 * @redirects: admin-dashboard.php if role is 'admin'
 * @see admin-dashboard.php:4 (requires admin role)
 */
function require_user() {
    require_login();
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role !== 'user') {
        header('Location: admin-dashboard.php');
        exit;
    }
}

/**
 * Ensures user is logged in and has admin role.
 * Redirects to homepage if user is not admin.
 * 
 * @global array $_SESSION - must contain 'user_id' and 'user_role' keys
 * @calls: require_login()
 * @redirects: index.php if role is not 'admin'
 * @see admin-dashboard.php:4 (requires admin)
 * @see admin-movies.php:293 (requires admin)
 * @see admin-bookings.php:3 (requires admin)
 */
function require_admin() {
    require_login();
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        header('Location: index.php');
        exit;
    }
}
?>

