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
error_log("Delete video request: " . json_encode($input));

if (!isset($input['video_id']) || empty($input['video_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Video ID is required']);
    exit;
}

$videoId = $input['video_id'];
$userId = $_SESSION['id'];

error_log("Attempting to delete video ID: $videoId for user ID: $userId");

try {
    // Database connection using mysqli (same as other files)
    $conn = new mysqli('localhost', 'root', '', 'videocapture');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // First, get the video file path to delete the physical file
    $stmt = $conn->prepare("SELECT video_path, name FROM videos WHERE id = ?");
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
    
    // Delete the physical video file if it exists
    if ($video['video_path']) {
        $uploadDir = __DIR__ . '/uploads_secure/';
        $fullPath = $uploadDir . $video['video_path'];
        
        if (file_exists($fullPath)) {
            error_log("Deleting physical file: " . $fullPath);
            if (unlink($fullPath)) {
                error_log("Physical file deleted successfully");
            } else {
                error_log("Failed to delete physical file: " . $fullPath);
            }
        } else {
            error_log("Physical file doesn't exist: " . $fullPath);
        }
        
        // Also clean up any old files with the same video_id pattern
        $oldFiles = glob($uploadDir . 'video_' . $videoId . '_*');
        foreach ($oldFiles as $oldFile) {
            if (file_exists($oldFile)) {
                error_log("Cleaning up old file: " . $oldFile);
                unlink($oldFile);
            }
        }
    } else {
        error_log("No video_path to delete for video ID: $videoId");
    }
    
    // Only delete the video record, keep video details and possible improvements
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    $videoDeleted = $stmt->affected_rows;
    error_log("Deleted $videoDeleted video record (keeping video details and improvements)");
    
    if ($videoDeleted > 0) {
        error_log("Video deletion successful");
        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
    } else {
        error_log("No video record was deleted");
        echo json_encode(['success' => false, 'message' => 'Video not found or already deleted']);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error in delete_video.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the video: ' . $e->getMessage()]);
}
?>
