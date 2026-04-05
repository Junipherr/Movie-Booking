<?php
/**
 * Migration: Add date and time columns to seats table
 * This fixes the seat booking system to track availability per showtime
 */

require_once 'includes/config.php';

echo "=== Seat System Migration ===\n\n";

try {
    // Step 1: Add columns if they don't exist
    echo "Step 1: Adding date and time columns to seats table...\n";
    
    $queries = [
        "ALTER TABLE seats ADD COLUMN date DATE NOT NULL DEFAULT '2026-04-04' AFTER theater",
        "ALTER TABLE seats ADD COLUMN time TIME NOT NULL DEFAULT '00:00:00' AFTER date"
    ];
    
    foreach ($queries as $query) {
        try {
            $conn->query($query);
            echo "  ✓ Column added successfully\n";
        } catch (Exception $e) {
            // Column might already exist
            if (strpos($conn->error, 'Duplicate column') === false) {
                echo "  ⚠ Note: " . $conn->error . "\n";
            }
        }
    }
    
    // Step 2: Populate seats with dates and times from showtimes
    echo "\nStep 2: Populating seats with showtime data...\n";
    
    // Get all unique movie+theater+date+time combinations from showtimes
    $stmt = $conn->prepare("
        SELECT DISTINCT movie_id, theater, date, time 
        FROM showtimes 
        ORDER BY movie_id, theater, date, time
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $showtime_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $movie_id = $row['movie_id'];
        $theater = $row['theater'];
        $date = $row['date'];
        $time = $row['time'];
        
        // Update seats for this showtime
        $update = $conn->prepare("
            UPDATE seats 
            SET date = ?, time = ? 
            WHERE movie_id = ? AND theater = ?
            LIMIT 120
        ");
        
        if (!$update) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $update->bind_param('ssis', $date, $time, $movie_id, $theater);
        $update->execute();
        $affected = $conn->affected_rows;
        
        if ($affected > 0) {
            echo "  ✓ Updated $affected seats for movie $movie_id, $theater, $date $time\n";
            $showtime_count++;
        }
        
        $update->close();
    }
    
    $stmt->close();
    
    // Step 3: Create indexes for better performance
    echo "\nStep 3: Creating indexes...\n";
    
    $index_queries = [
        "ALTER TABLE seats ADD INDEX idx_movie_theater_datetime (movie_id, theater, date, time)",
        "ALTER TABLE seats ADD INDEX idx_date_time (date, time)"
    ];
    
    foreach ($index_queries as $query) {
        try {
            $conn->query($query);
            echo "  ✓ Index created successfully\n";
        } catch (Exception $e) {
            if (strpos($conn->error, 'Duplicate key') === false) {
                echo "  ⚠ Note: " . $conn->error . "\n";
            }
        }
    }
    
    // Step 4: Verify the migration
    echo "\nStep 4: Verifying migration...\n";
    
    $verify_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT CONCAT(movie_id, '-', theater, '-', date, '-', time)) as unique_showtimes
        FROM seats
        WHERE date > '2000-01-01' AND time <> '00:00:00'
    ");
    
    if (!$verify_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_row = $verify_result->fetch_assoc();
    
    echo "  ✓ Found " . $verify_row['unique_showtimes'] . " unique showtimes with seats\n";
    
    $verify_stmt->close();
    
    echo "\n=== Migration Complete ===\n";
    echo "✓ Seats system is now properly configured per showtime!\n";
    echo "\nYou can now select seats by theater, date, and time.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
