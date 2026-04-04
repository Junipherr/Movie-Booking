<?php
session_start();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/'); // Clear session cookie
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
