<?php
// Ultra-bulletproof save_video_detail.php - guarantees valid JSON output
// Disable ALL output and errors
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ini_set('output_buffering', 0);
ini_set('html_errors', 0);

// Start output buffering immediately - this is critical
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Initialize response
$response = ['success' => false, 'error' => 'Unknown error'];

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response = ['success' => false, 'error' => 'Only POST requests allowed'];
        throw new Exception('Only POST requests allowed');
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        $response = ['success' => false, 'error' => 'No input data received'];
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response = ['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()];
        throw new Exception('Invalid JSON input');
    }

    if (!isset($data['details']) || !is_array($data['details'])) {
        $response = ['success' => false, 'error' => 'Invalid data structure'];
        throw new Exception('Invalid data structure');
    }

    // Database connection - REQUIRED
    $conn = require 'db.php';
    if (!$conn) {
        $response = ['success' => false, 'error' => 'Database connection failed. MySQL is required.'];
        throw new Exception('Database connection failed');
    }

    // Save to database
    $response = saveToDatabase($conn, $data['details']);

} catch (Exception $e) {
    // Error already set in response
    if ($response['success'] === false && empty($response['error'])) {
        $response['error'] = $e->getMessage();
    }
} finally {
    // CRITICAL: Clean any output buffer and ensure only JSON is output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Encode response as JSON
    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"success":false,"error":"JSON encoding failed"}';
    }
    
    // Output ONLY the JSON response
    echo $json;
    exit;
}

// Function to save to database
function saveToDatabase($conn, $details) {
    try {
        $conn->begin_transaction();
        
        foreach ($details as $detail) {
            if (!isset($detail['video_id'])) {
                throw new Exception('Missing video_id in detail');
            }
            
            $video_id = intval($detail['video_id']);
            $id = isset($detail['id']) && $detail['id'] !== 'null' && $detail['id'] !== '' ? intval($detail['id']) : null;
            $id_fe = isset($detail['id_fe']) ? intval($detail['id_fe']) : null;
            $operator = trim($detail['operator'] ?? '');
            $description = trim($detail['description'] ?? '');
            $va_nva_enva = trim($detail['va_nva_enva'] ?? '');
            $activity_type = trim($detail['activity_type'] ?? 'manual');
            $start_time = trim($detail['start_time'] ?? '');
            $end_time = trim($detail['end_time'] ?? '');
            
            if ($id) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE video_details SET id_fe = ?, operator = ?, description = ?, va_nva_enva = ?, activity_type = ?, start_time = ?, end_time = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $stmt->bind_param("issssssi", $id_fe, $operator, $description, $va_nva_enva, $activity_type, $start_time, $end_time, $id);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO video_details (video_id, id_fe, operator, description, va_nva_enva, activity_type, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $stmt->bind_param("iissssss", $video_id, $id_fe, $operator, $description, $va_nva_enva, $activity_type, $start_time, $end_time);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $stmt->close();
        }
        
        $conn->commit();
        return ['success' => true, 'error' => '', 'message' => 'Video details saved successfully!'];
        
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
        }
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

?>