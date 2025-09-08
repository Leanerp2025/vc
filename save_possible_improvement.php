<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log("save_possible_improvement.php: Received input data: " . print_r($input, true));

if (!isset($input['improvements']) || !is_array($input['improvements'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data format']);
    exit;
}

$success = true;
$errors = [];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    foreach ($input['improvements'] as $improvement) {
        $id = isset($improvement['id']) && $improvement['id'] !== 'null' && $improvement['id'] !== '' ? $improvement['id'] : null;
        $video_detail_id = $improvement['video_detail_id'] ?? '';
        $cycle_number = $improvement['cycle_number'] ?? '';
        $improvement_text = $improvement['improvement'] ?? '';
        $type_of_benefits = $improvement['type_of_benefits'] ?? '';
        $video_id = $improvement['video_id'] ?? null;
        
        // Log the received data for debugging
        error_log("save_possible_improvement.php: Processing improvement - id: $id, video_detail_id: $video_detail_id, cycle_number: $cycle_number, improvement: $improvement_text, type_of_benefits: $type_of_benefits");
        
        // Validate required fields
        if (empty($video_detail_id) || empty($cycle_number) || empty($improvement_text) || empty($type_of_benefits)) {
            $errors[] = 'Missing required fields';
            $success = false;
            continue;
        }
        
        if ($id) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE possible_improvements SET video_detail_id = ?, cycle_number = ?, improvement = ?, type_of_benefits = ? WHERE id = ? AND video_id = ?");
            
            if (!$stmt) {
                $errors[] = 'Database prepare error (UPDATE): ' . $conn->error;
                $success = false;
                continue;
            }
            
            $stmt->bind_param("isssii", $video_detail_id, $cycle_number, $improvement_text, $type_of_benefits, $id, $video_id);
            error_log("save_possible_improvement.php: Updating existing record with id: $id");
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO possible_improvements (video_detail_id, cycle_number, improvement, type_of_benefits, video_id) VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                $errors[] = 'Database prepare error (INSERT): ' . $conn->error;
                $success = false;
                continue;
            }
            
            $stmt->bind_param("isssi", $video_detail_id, $cycle_number, $improvement_text, $type_of_benefits, $video_id);
            error_log("save_possible_improvement.php: Inserting new record");
        }
        
        if (!$stmt->execute()) {
            $errors[] = 'Database execute error: ' . $stmt->error;
            $success = false;
            continue;
        }
        
        $stmt->close();
    }
    
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Improvements saved successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
}

$conn->close();
?>