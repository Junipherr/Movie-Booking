<?php
// API endpoint to get available showtimes for a movie
require_once 'includes/config.php';

header('Content-Type: application/json');

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theater_filter = isset($_GET['theater']) ? $_GET['theater'] : null;
$date_filter = isset($_GET['date']) ? $_GET['date'] : null;

if ($movie_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid movie_id']);
    exit;
}

try {
    // Build query based on filters
    $where_conditions = ['movie_id = ?'];
    $params = [$movie_id];
    $types = 'i';
    
    if ($theater_filter) {
        $where_conditions[] = 'theater = ?';
        $params[] = $theater_filter;
        $types .= 's';
    }
    
    if ($date_filter) {
        $where_conditions[] = 'date = ?';
        $params[] = $date_filter;
        $types .= 's';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Fetch distinct dates and times for this movie with filters
    $stmt = $conn->prepare("
        SELECT DISTINCT date, time, theater
        FROM showtimes
        WHERE $where_clause
        ORDER BY date ASC, time ASC
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    $times = [];
    $theaters = [];
    
    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['date'], $dates)) {
            $dates[] = $row['date'];
        }
        if (!in_array($row['time'], $times)) {
            $times[] = $row['time'];
        }
        if (!in_array($row['theater'], $theaters)) {
            $theaters[] = $row['theater'];
        }
    }
    
    $stmt->close();
    
    // If no showtimes found, generate default ones (only if no filters applied)
    if (empty($dates) && !$theater_filter && !$date_filter) {
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days"));
        }
        $times = ['10:00:00', '13:00:00', '16:00:00', '19:00:00', '22:00:00'];
        $theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];
    }
    
    echo json_encode([
        'success' => true,
        'movie_id' => $movie_id,
        'dates' => $dates,
        'times' => $times,
        'theaters' => $theaters
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
