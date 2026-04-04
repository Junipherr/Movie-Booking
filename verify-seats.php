<?php
// API endpoint to verify seats are still available before booking
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$theater = isset($_POST['theater']) ? trim($_POST['theater']) : '';
$seats = isset($_POST['seats']) ? $_POST['seats'] : [];

if (!is_array($seats)) {
    $seats = array_filter(array_map('trim', explode(',', $seats)));
}

if ($movie_id <= 0 || empty($theater) || empty($seats)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Check if seats are available (not occupied)
    $placeholders = implode(',', array_fill(0, count($seats), '?'));
    $query = "
        SELECT seat_number, occupied 
        FROM seats 
        WHERE movie_id = ? AND theater = ? AND seat_number IN ($placeholders)
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    // Build parameter types and values
    $types = 'is' . str_repeat('s', count($seats));
    $params = array_merge([$movie_id, $theater], $seats);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $seat_status = [];
    $unavailable_seats = [];
    
    while ($row = $result->fetch_assoc()) {
        $seat_status[$row['seat_number']] = $row['occupied'];
        if ($row['occupied'] == 1) {
            $unavailable_seats[] = $row['seat_number'];
        }
    }
    
    $stmt->close();
    
    if (!empty($unavailable_seats)) {
        echo json_encode([
            'success' => false,
            'available' => false,
            'unavailable_seats' => $unavailable_seats,
            'message' => 'Some seats are no longer available: ' . implode(', ', $unavailable_seats)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'available' => true,
            'message' => 'All seats are available'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
