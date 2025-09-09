<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Log the input for debugging
error_log("Delete video file request: " . json_encode($input));

if (!isset($input['video_id']) || empty($input['video_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Video ID is required']);
    exit;
}

$videoId = $input['video_id'];
$userId = $_SESSION['id'];

error_log("Attempting to delete video file for video ID: $videoId for user ID: $userId");

try {
    // Database connection using mysqli (same as other files)
    $conn = new mysqli('localhost', 'root', '', 'videocapture');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // First, get the video file path to delete the physical file
    $stmt = $conn->prepare("SELECT video_path, name, file_size FROM videos WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    
    error_log("Video found: " . json_encode($video));
    
    if (!$video) {
        error_log("Video not found for ID: $videoId");
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit;
    }
    
    $fileDeleted = false;
    $fileSize = $video['file_size'];
    
    // Delete the physical video file if it exists
    if ($video['video_path']) {
        $uploadDir = __DIR__ . '/uploads_secure/';
        $fullPath = $uploadDir . $video['video_path'];
        
        if (file_exists($fullPath)) {
            error_log("Deleting physical file: " . $fullPath);
            if (unlink($fullPath)) {
                error_log("Physical file deleted successfully");
                $fileDeleted = true;
            } else {
                error_log("Failed to delete physical file: " . $fullPath);
            }
        } else {
            error_log("Physical file doesn't exist: " . $fullPath);
            $fileDeleted = true; // Consider it deleted if it doesn't exist
        }
        
        // Also clean up any old files with the same video_id pattern
        $oldFiles = glob($uploadDir . 'video_' . $videoId . '_*');
        foreach ($oldFiles as $oldFile) {
            if (file_exists($oldFile)) {
                error_log("Cleaning up old file: " . $oldFile);
                unlink($oldFile);
                $fileDeleted = true;
            }
        }
    } else {
        error_log("No video_path to delete for video ID: $videoId");
        $fileDeleted = true; // No file to delete, consider it successful
    }
    
    // Update the video record to remove file information but keep the video record
    $stmt = $conn->prepare("UPDATE videos SET video_path = NULL, file_size = NULL WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    $videoUpdated = $stmt->affected_rows;
    error_log("Updated $videoUpdated video record to remove file information for video ID: $videoId");
    
    if ($videoUpdated > 0) {
        error_log("Video file deletion successful - file removed, data preserved");
        echo json_encode([
            'success' => true, 
            'message' => 'Video file deleted successfully. Video details and improvements have been preserved.',
            'details' => [
                'video_id' => $videoId,
                'file_deleted' => $fileDeleted,
                'file_size_removed' => $fileSize,
                'video_record_updated' => $videoUpdated
            ]
        ]);
    } else {
        error_log("No video record was updated");
        echo json_encode(['success' => false, 'message' => 'Video not found or already has no file']);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error in delete_video_file.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the video file: ' . $e->getMessage()]);
}
?>
