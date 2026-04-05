<?php 
require_once 'includes/auth.php';
require_user(); 
require_once 'includes/config.php';

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$movie = null;

if ($movie_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Simple Booking</title>
</head>
<body>
<?php if ($movie): ?>
<h1>Book <?php echo htmlspecialchars($movie['title']); ?></h1>
<form method="POST" action="bookings-fixed.php">
    <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
    <p><label>Theater: <select name="theater">
        <option>Screen 1</option>
    </select></label></p>
    <p><label>Date: <select name="date">
        <option>2026-04-05</option>
    </select></label></p>
    <p><label>Time: <select name="time">
        <option>16:00:00</option>
    </select></label></p>
    <p><label>Seats: <input name="selectedSeats" value="A1,A2,B3" size="30"></label></p>
    <p><button type="submit">Proceed to Payment</button></p>
</form>
<p><a href="index.php">Back</a></p>
<?php endif; ?>
</body>
</html>
