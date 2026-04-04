<?php
// API endpoint to get occupied seats for a movie and theater
require_once 'includes/config.php';

header('Content-Type: application/json');

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theater = isset($_GET['theater']) ? trim($_GET['theater']) : '';

if ($movie_id <= 0 || empty($theater)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Fetch occupied seats for this movie and theater
    $stmt = $conn->prepare('
        SELECT seat_number 
        FROM seats 
        WHERE movie_id = ? AND theater = ? AND occupied = 1
    ');
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('is', $movie_id, $theater);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $occupied_seats = [];
    while ($row = $result->fetch_assoc()) {
        $occupied_seats[] = $row['seat_number'];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'movie_id' => $movie_id,
        'theater' => $theater,
        'occupied_seats' => $occupied_seats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
