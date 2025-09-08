<?php
// Fixed version of save_video_detail.php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'Invalid request'];

// Try to connect to database, but handle gracefully if not available
$conn = null;
$useFileStorage = false;

try {
    $conn = require 'db.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    error_log("save_video_detail.php: Database connection error: " . $e->getMessage());
    $useFileStorage = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw input data
    $rawInput = file_get_contents('php://input');
    
    // Try to decode JSON data
    $data = json_decode($rawInput, true);
    
    // If JSON decode failed or no data, try alternative methods
    if ($data === null || empty($data)) {
        // Try $_POST as fallback
        if (!empty($_POST)) {
            $data = $_POST;
        }
        // Try $_REQUEST as another fallback
        elseif (!empty($_REQUEST)) {
            $data = $_REQUEST;
        }
        // Try to get from raw input stream
        elseif (!empty($rawInput)) {
            // Try to decode as form data
            parse_str($rawInput, $data);
        }
    }
    
    // Check if we have valid data
    if (!$data) {
        $response['error'] = 'No data received';
        $response['debug'] = [
            'raw_input_length' => strlen($rawInput),
            'raw_input_preview' => substr($rawInput, 0, 100),
            'post_data' => $_POST,
            'request_data' => $_REQUEST,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not set'
        ];
    } elseif (isset($data['details']) && is_array($data['details'])) {
        $details = $data['details'];
        error_log("save_video_detail.php: Received details data: " . print_r($details, true));
        $all_success = true;

        if ($useFileStorage) {
            // Use file-based storage when database is not available
            $all_success = saveDetailsToFile($details);
            if ($all_success) {
                $response['success'] = true;
                $response['message'] = 'Video details saved successfully! (Using file storage)';
                $response['warning'] = 'Database unavailable - using file storage';
            } else {
                $response['error'] = 'Failed to save details to file';
            }
        } else {
            // Use database storage
            $all_success = saveDetailsToDatabase($conn, $details);
            if ($all_success) {
                $response['success'] = true;
                $response['message'] = 'Video details saved successfully! (Database storage)';
            } else {
                $response['error'] = 'Failed to save details to database';
            }
        }
    } else {
        $response['error'] = 'Invalid data format.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

// Function to save details to database
function saveDetailsToDatabase($conn, $details) {
    try {
        $all_success = true;
        
        foreach ($details as $detail) {
            // Validate required fields
            if (!isset($detail['video_id']) || !isset($detail['operator']) || !isset($detail['description']) || 
                !isset($detail['va_nva_enva']) || !isset($detail['start_time']) || !isset($detail['end_time'])) {
                error_log("saveDetailsToDatabase: Missing required fields in detail: " . print_r($detail, true));
                return false;
            }
            
            $video_id = intval($detail['video_id']);
            $operator = trim($detail['operator']);
            $description = trim($detail['description']);
            $va_nva_enva = trim($detail['va_nva_enva']);
            $activity_type = isset($detail['activity_type']) && !empty($detail['activity_type']) ? trim($detail['activity_type']) : 'manual';
            $start_time = trim($detail['start_time']);
            $end_time = trim($detail['end_time']);
            $id_fe = isset($detail['id_fe']) && !empty($detail['id_fe']) ? intval($detail['id_fe']) : null;
            
            // Check if this is an update (has ID) or insert (no ID)
            if (isset($detail['id']) && $detail['id'] !== 'null' && $detail['id'] !== '' && $detail['id'] !== null) {
                // Update existing record
                $id = intval($detail['id']);
                $stmt = $conn->prepare("UPDATE video_details SET id_fe = ?, operator = ?, description = ?, va_nva_enva = ?, activity_type = ?, start_time = ?, end_time = ? WHERE id = ? AND video_id = ?");
                $stmt->bind_param("isssssii", $id_fe, $operator, $description, $va_nva_enva, $activity_type, $start_time, $end_time, $id, $video_id);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO video_details (id_fe, video_id, operator, description, va_nva_enva, activity_type, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissssss", $id_fe, $video_id, $operator, $description, $va_nva_enva, $activity_type, $start_time, $end_time);
            }
            
            if (!$stmt->execute()) {
                error_log("saveDetailsToDatabase: Failed to execute statement: " . $stmt->error);
                error_log("saveDetailsToDatabase: SQL Error: " . $stmt->error);
                error_log("saveDetailsToDatabase: Detail data: " . print_r($detail, true));
                $all_success = false;
                $stmt->close();
                break;
            }
            
            $stmt->close();
        }
        
        return $all_success;
    } catch (Exception $e) {
        error_log("saveDetailsToDatabase: Exception: " . $e->getMessage());
        return false;
    }
}

// Function to save details to file when database is not available
function saveDetailsToFile($details) {
    try {
        $storageDir = __DIR__ . '/storage/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $videoId = null;
        $allDetails = [];
        
        foreach ($details as $detail) {
            // Validate required fields
            if (!isset($detail['video_id']) || !isset($detail['operator']) || !isset($detail['description']) || 
                !isset($detail['va_nva_enva']) || !isset($detail['start_time']) || !isset($detail['end_time'])) {
                error_log("saveDetailsToFile: Missing required fields in detail: " . print_r($detail, true));
                return false;
            }
            
            $videoId = intval($detail['video_id']);
            $id = isset($detail['id']) && $detail['id'] !== 'null' && $detail['id'] !== '' ? $detail['id'] : null;
            
            $detailData = [
                'id' => $id ?: uniqid('detail_', true),
                'video_id' => $videoId,
                'operator' => trim($detail['operator']),
                'description' => trim($detail['description']),
                'va_nva_enva' => trim($detail['va_nva_enva']),
                'activity_type' => isset($detail['activity_type']) && !empty($detail['activity_type']) ? trim($detail['activity_type']) : 'manual',
                'start_time' => trim($detail['start_time']),
                'end_time' => trim($detail['end_time']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $allDetails[] = $detailData;
        }
        
        if ($videoId) {
            $filename = $storageDir . 'video_details_' . $videoId . '.json';
            $success = file_put_contents($filename, json_encode($allDetails, JSON_PRETTY_PRINT));
            
            if ($success === false) {
                error_log("saveDetailsToFile: Failed to write to file: $filename");
                return false;
            }
            
            error_log("saveDetailsToFile: Successfully saved details to: $filename");
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("saveDetailsToFile: Exception: " . $e->getMessage());
        return false;
    }
}

ob_clean();
$json_output = json_encode($response);
error_log("save_video_detail.php: Final JSON output: " . $json_output);
echo $json_output;
?>