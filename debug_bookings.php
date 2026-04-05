<?php
// TEMP DEBUG: Test bookings.php flow
require_once 'includes/config.php';
echo '<pre>';
print_r($_POST ?? 'NO POST');
if (isset($_POST['selectedSeats'])) {
  $seats = trim($_POST['selectedSeats']);
  echo \"\\nSeats received: '$seats' (length: \" . strlen($seats) . \")\";
  $arr = array_filter(array_map('trim', explode(',', $seats)));
  echo \"\\nParsed: \" . json_encode($arr) . \" (count: \" . count($arr) . \")\";
}
echo '</pre>';
?>

