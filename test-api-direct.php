<?php
echo "=== TESTING get-showtimes.php API DIRECTLY ===\n\n";

// Simulate the API call
$_GET['movie_id'] = 21;
include 'get-showtimes.php';
echo "\n=== END TEST ===\n";
?>