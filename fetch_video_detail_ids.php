<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    // Get the current video ID from session or request
    $video_id = isset($_GET['video_id']) ? $_GET['video_id'] : null;
    
    if ($video_id) {
        // Fetch video detail IDs for the specific video
        $stmt = $conn->prepare("SELECT id, description FROM video_details WHERE video_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $video_id);
    } else {
        // Fetch all video detail IDs if no specific video
        $stmt = $conn->prepare("SELECT id, description FROM video_details ORDER BY id ASC");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $video_details = [];
    while ($row = $result->fetch_assoc()) {
        $video_details[] = [
            'id' => $row['id'],
            'description' => $row['description']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'video_details' => $video_details
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
