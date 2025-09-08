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

// Prepare paths and keep existing data. Do NOT delete old video until new upload succeeds.

// 1. Get current video name (for response convenience)
$video_name_from_db = null;
$stmt_get_old = $conn->prepare("SELECT name FROM videos WHERE id = ?");
$stmt_get_old->bind_param("i", $video_id);
$stmt_get_old->execute();
$result_old = $stmt_get_old->get_result();
if ($row_old = $result_old->fetch_assoc()) {
    $video_name_from_db = $row_old['name'];
}
$stmt_get_old->close();

$uploadDir = __DIR__ . '/uploads_secure/';


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

$fileName = 'video_' . $video_id . '_' . uniqid() . '_' . basename($_FILES['video']['name']);
$fileSize = isset($_FILES['video']['size']) ? (int)$_FILES['video']['size'] : null;
$uploadFile = $uploadDir . $fileName;

error_log('Attempting to move uploaded file from: ' . $_FILES['video']['tmp_name'] . ' to: ' . $uploadFile);

if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadFile)) {
    error_log('File moved successfully.');
    // First update DB to point to new file
    $stmt = $conn->prepare("UPDATE videos SET file_size = ?, video_path = ? WHERE id = ?");
    $stmt->bind_param("isi", $fileSize, $fileName, $video_id);

    if ($stmt->execute()) {
        error_log('Database updated successfully for video_id = ' . $video_id);

        // After DB update succeeds, delete any old files matching the old pattern to reclaim space
        $oldFiles = glob($uploadDir . 'video_' . $video_id . '_*');
        foreach ($oldFiles as $old) {
            // Keep the newly uploaded file; delete others
            if (basename($old) !== $fileName && file_exists($old)) {
                @unlink($old);
            }
        }

        // Return success
        $response['success'] = true;
        $response['message'] = 'Video uploaded successfully.';
        $response['video_name'] = $video_name_from_db;
        $response['video_id'] = $video_id;

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
