<?php
session_start();
require_once 'db.php'; // Include your database connection

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id']) && isset($_FILES['new_video_file'])) {
    $video_id = $_POST['video_id'];
    $new_video_file = $_FILES['new_video_file'];

    // Validate uploaded file
    if ($new_video_file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error: ' . $new_video_file['error'];
        echo json_encode($response);
        exit;
    }

    // Get old video path from database
    $stmt = $conn->prepare("SELECT video_path FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    $stmt->close();

    if ($video && $video['video_path']) {
        $old_video_path = 'uploads_secure/' . basename($video['video_path']); // Ensure path is secure

        // Check if the old file exists and delete it
        if (file_exists($old_video_path)) {
            if (!unlink($old_video_path)) {
                $response['message'] = 'Failed to delete old video file.';
                echo json_encode($response);
                exit;
            }
        }

        // Move the new file to the same location with the same name
        $target_file = $old_video_path; // Overwrite with the same name

        if (move_uploaded_file($new_video_file['tmp_name'], $target_file)) {
            $response['success'] = true;
            $response['message'] = 'Video reuploaded successfully.';
        } else {
            $response['message'] = 'Failed to move uploaded file.';
        }
    } else {
        $response['message'] = 'Video not found or no existing path.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>