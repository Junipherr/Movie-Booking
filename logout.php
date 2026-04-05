<?php
/**
 * Logout Handler
 * 
 * Destroys user session and redirects to login page.
 * Clears all session data including user_id, user_name, user_email, user_role.
 * 
 * @route: logout.php
 * @method: GET (direct access triggers logout)
 * @session-destroys: All session variables
 * @redirects: login.php
 * 
 * @see login.php (login page)
 * @see auth.php:require_login() (checks session for protected pages)
 */

// Start session to access session variables
session_start();

// Destroy all session data
session_destroy();

// Clear session cookie
setcookie(session_name(), '', time() - 3600, '/');

// Redirect to login page
header('Location: login.php');
exit;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <meta http-equiv="refresh" content="0;url=login.php">
</head>
<body>
    <p>Logging out... Redirecting to login.</p>
</body>
</html>
