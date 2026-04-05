<?php
/**
 * Showtimes API Endpoint
 * 
 * Returns available dates and times for a specific movie and theater.
 * Called by booking.js via AJAX when user selects theater/date.
 * 
 * @route: get-showtimes.php?movie_id={id}&theater={name}[&date={date}]
 * @method: GET
 * @requires: includes/config.php
 * 
 * @query-params: 
 *   - movie_id (int): ID of the movie (required)
 *   - theater (string): Theater name to get dates/times for (required)
 *   - date (string): Specific date to get times for (optional)
 * 
 * @returns: JSON object with success, dates array, and/or times array
 * 
 * @used-by: booking.js (seat selection page)
 * @see booking.php (booking page with seat selection)
 */

// Include database configuration
require_once 'includes/config.php';

// Get parameters from URL
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theater = isset($_GET['theater']) ? trim($_GET['theater']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

// Set content type to JSON
header('Content-Type: application/json');

// Validate required parameters
if ($movie_id <= 0 || empty($theater)) {
    echo json_encode(['success' => false, 'dates' => [], 'times' => []]);
    exit;
}

// If date is provided, return times for that specific date
if (!empty($date)) {
    $stmt = $conn->prepare("
        SELECT DISTINCT time 
        FROM showtimes 
        WHERE movie_id = ? AND theater = ? AND date = ?
        ORDER BY time ASC
    ");
    $stmt->bind_param('iss', $movie_id, $theater, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $times = [];
    while ($row = $result->fetch_assoc()) {
        $times[] = $row['time'];
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'dates' => [], 'times' => $times]);
    exit;
}

// Otherwise, return all available dates for the theater
$stmt = $conn->prepare("
    SELECT DISTINCT date 
    FROM showtimes 
    WHERE movie_id = ? AND theater = ?
    ORDER BY date ASC
");
$stmt->bind_param('is', $movie_id, $theater);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date'];
}

$stmt->close();
$conn->close();

// Return JSON object matching what booking.js expects
echo json_encode(['success' => true, 'dates' => $dates, 'times' => []]);
?>