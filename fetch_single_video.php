<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'video' => null];

$video_id = $_GET['video_id'] ?? null;

// --- ADDED LOGGING ---
error_log("fetch_single_video.php: Received video_id = " . var_export($video_id, true));
// --- END ADDED LOGGING ---

if (!$video_id) {
    $response['error'] = 'Video ID is required.';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT id, video_path, name FROM videos WHERE id = ?");

if ($stmt === false) {
    $response['error'] = 'Prepare failed: ' . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

// --- ADDED LOGGING ---
error_log("fetch_single_video.php: Query executed for video_id = " . $video_id . ". num_rows = " . $result->num_rows);
// --- END ADDED LOGGING ---

if ($result->num_rows > 0) {
    $response['video'] = $result->fetch_assoc();
    $response['success'] = true;
} else {
    $response['error'] = 'Video not found.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>