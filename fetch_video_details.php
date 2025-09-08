<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'details' => []];

$video_id = $_GET['video_id'] ?? null;

if (!$video_id) {
    $response['error'] = 'Video ID is required.';
    echo json_encode($response);
    exit;
}

// Try to connect to database, but handle gracefully if not available
$conn = null;
$useFileStorage = false;

try {
    $conn = require 'db.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    error_log("fetch_video_details.php: Database connection error: " . $e->getMessage());
    $useFileStorage = true;
}

if ($useFileStorage) {
    // Use file-based storage when database is not available
    $storageDir = __DIR__ . '/storage/';
    $filename = $storageDir . 'video_details_' . $video_id . '.json';
    
    if (file_exists($filename)) {
        $fileContent = file_get_contents($filename);
        $details = json_decode($fileContent, true);
        
        if ($details && is_array($details)) {
            $response['details'] = $details;
            $response['success'] = true;
        } else {
            $response['error'] = 'Invalid file format';
        }
    } else {
        // No details found for this video
        $response['success'] = true;
        $response['details'] = [];
    }
} else {
    // Use database storage
    // Check if activity_type column exists
    $check_column = $conn->query("SHOW COLUMNS FROM video_details LIKE 'activity_type'");
    if ($check_column->num_rows == 0) {
        // Add the column if it doesn't exist
        $conn->query("ALTER TABLE video_details ADD COLUMN activity_type VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER va_nva_enva");
    }

    $stmt = $conn->prepare("SELECT id, id_fe, operator, description, va_nva_enva, activity_type, start_time, end_time FROM video_details WHERE video_id = ? ORDER BY id ASC");

    if ($stmt === false) {
        $response['error'] = 'Prepare failed: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Ensure activity_type has a default value if null
            if (empty($row['activity_type'])) {
                $row['activity_type'] = 'manual';
            }
            $response['details'][] = $row;
        }
    }

    $response['success'] = true;

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
?>