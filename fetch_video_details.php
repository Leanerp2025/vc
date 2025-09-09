<?php
// MySQL-only fetch_video_details.php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$response = ['success' => false, 'error' => '', 'details' => []];

try {
    $video_id = $_GET['video_id'] ?? null;

    if (!$video_id) {
        $response['error'] = 'Video ID is required.';
        throw new Exception('Video ID is required');
    }

    // Database connection - REQUIRED
    $conn = require 'db.php';
    if (!$conn) {
        $response['error'] = 'Database connection failed. MySQL is required.';
        throw new Exception('Database connection failed');
    }

    // Check if activity_type column exists
    $check_column = $conn->query("SHOW COLUMNS FROM video_details LIKE 'activity_type'");
    if ($check_column->num_rows == 0) {
        // Add the column if it doesn't exist
        $conn->query("ALTER TABLE video_details ADD COLUMN activity_type VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER va_nva_enva");
    }

    // Check if id_fe column exists
    $check_id_fe = $conn->query("SHOW COLUMNS FROM video_details LIKE 'id_fe'");
    if ($check_id_fe->num_rows == 0) {
        // Add the column if it doesn't exist
        $conn->query("ALTER TABLE video_details ADD COLUMN id_fe INT(6) UNSIGNED AFTER id");
    }

    $stmt = $conn->prepare("SELECT id, id_fe, operator, description, va_nva_enva, activity_type, start_time, end_time FROM video_details WHERE video_id = ? ORDER BY id ASC");

    if ($stmt === false) {
        $response['error'] = 'Prepare failed: ' . $conn->error;
        throw new Exception('Prepare failed');
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

} catch (Exception $e) {
    error_log("fetch_video_details.php: Error: " . $e->getMessage());
    if ($response['success'] === false && empty($response['error'])) {
        $response['error'] = $e->getMessage();
    }
}

// Clean any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Output JSON response
$json = json_encode($response, JSON_UNESCAPED_UNICODE);
if ($json === false) {
    $json = '{"success":false,"error":"JSON encoding failed","details":[]}';
}

echo $json;
exit;
?>