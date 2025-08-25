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
        $video_detail_id = $improvement['video_detail_id'] ?? '';
        $cycle = $improvement['cycle'] ?? '';
        $improvement_text = $improvement['improvement'] ?? '';
        $benefit = $improvement['benefit'] ?? '';
        $video_id = $improvement['video_id'] ?? null;
        
        // Validate required fields
        if (empty($video_detail_id) || empty($cycle) || empty($improvement_text) || empty($benefit)) {
            $errors[] = 'Missing required fields';
            $success = false;
            continue;
        }
        
        // Insert into possible_improvements table
        $stmt = $conn->prepare("INSERT INTO possible_improvements (video_detail_id, cycle_number, improvement, type_of_benefits, video_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            $errors[] = 'Database prepare error: ' . $conn->error;
            $success = false;
            continue;
        }
        
        $stmt->bind_param("isssi", $video_detail_id, $cycle, $improvement_text, $benefit, $video_id);
        
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