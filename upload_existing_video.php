<?php
error_reporting(E_ALL); // Enable all error reporting
ini_set('display_errors', 1); // Display errors
header('Content-Type: application/json');

$conn = require 'db.php';

$response = ['success' => false, 'error' => ''];

$video_id = $_POST['video_id'] ?? null;

if (!$video_id) {
    $response['error'] = 'Video ID is required.';
    echo json_encode($response);
    exit;
}

// --- ADDED LOGGING ---
error_log("upload_existing_video.php: Received video_id = " . var_export($video_id, true));
// --- END ADDED LOGGING ---

// --- START: New logic to delete old video file and details ---

// 1. Get current video path and name
$old_video_path = null;
$video_name_from_db = null;
$stmt_get_old = $conn->prepare("SELECT video_path, name FROM videos WHERE id = ?");
$stmt_get_old->bind_param("i", $video_id);
$stmt_get_old->execute();
$result_old = $stmt_get_old->get_result();
if ($row_old = $result_old->fetch_assoc()) {
    $old_video_path = $row_old['video_path'];
    $video_name_from_db = $row_old['name']; // Keep the existing video name
}
$stmt_get_old->close();

$uploadDir = __DIR__ . '/uploads_secure/';

// 2. Delete old video file if it exists
if ($old_video_path && file_exists($uploadDir . $old_video_path)) {
    if (!unlink($uploadDir . $old_video_path)) {
        // Log error but don't stop process, as file might be in use or permissions issue
        error_log("Failed to delete old video file: " . $uploadDir . $old_video_path);
    }
}

// 3. Delete associated video_details
$stmt_delete_details = $conn->prepare("DELETE FROM video_details WHERE video_id = ?");
$stmt_delete_details->bind_param("i", $video_id);
if (!$stmt_delete_details->execute()) {
    // Log error but don't stop process
    error_log("Failed to delete old video details for video_id: " . $video_id . " Error: " . $stmt_delete_details->error);
}
$stmt_delete_details->close();

// --- END: New logic ---


// Handle new video file upload
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Video file upload failed.';
    if (isset($_FILES['video'])) {
        $error_message .= ' Error code: ' . $_FILES['video']['error'];
        error_log('$_FILES[\'video\'] content: ' . print_r($_FILES['video'], true));
    } else {
        $error_message .= ' $_FILES[\'video\'] not set.';
    }
    $response['error'] = $error_message;
    echo json_encode($response);
    exit;
}

// Ensure the upload directory exists and is writable
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
}
if (!is_writable($uploadDir)) {
    $response['error'] = 'Upload directory is not writable: ' . $uploadDir;
    error_log('Upload directory not writable: ' . $uploadDir);
    echo json_encode($response);
    exit;
}

$fileName = uniqid() . '_' . basename($_FILES['video']['name']);
$fileSize = isset($_FILES['video']['size']) ? (int)$_FILES['video']['size'] : null;
$uploadFile = $uploadDir . $fileName;

error_log('Attempting to move uploaded file from: ' . $_FILES['video']['tmp_name'] . ' to: ' . $uploadFile);

if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadFile)) {
    error_log('File moved successfully.');
    $stmt = $conn->prepare("UPDATE videos SET video_path = ?, file_size = ? WHERE id = ?");
    $stmt->bind_param("sii", $fileName, $fileSize, $video_id);

    if ($stmt->execute()) {
        error_log('Database updated successfully for video_id = ' . $video_id);
        // Return the video name that was already in the database, as it's not changed by this upload
        $response['success'] = true;
        $response['message'] = 'Video uploaded successfully.';
        $response['video_path'] = $fileName;
        $response['video_name'] = $video_name_from_db; // Use the name fetched earlier
        $response['video_id'] = $video_id; // --- ADD THIS LINE ---

    } else {
        error_log('Database update failed for video_id = ' . $video_id . ': ' . $stmt->error);
        // If DB update fails, try to delete the newly uploaded file
        if (file_exists($uploadFile)) {
            unlink($uploadFile);
        }
        $response['error'] = 'Database error: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $last_error = error_get_last();
    $response['error'] = 'Failed to move uploaded file. PHP Error: ' . ($last_error['message'] ?? 'Unknown error');
    error_log('Failed to move uploaded file. PHP Error: ' . print_r($last_error, true));
}

$conn->close();

echo json_encode($response);
?>
