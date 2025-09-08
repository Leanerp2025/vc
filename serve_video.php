<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin'])) {
    error_log('serve_video.php: Not logged in');
    http_response_code(403);
    exit('Forbidden');
}

$conn = require 'db.php';

$video_id = $_GET['video_id'] ?? null;
error_log('serve_video.php: video_id = ' . $video_id);

if (!$video_id) {
    error_log('serve_video.php: video_id is required');
    http_response_code(400);
    exit('Bad Request: video_id is required');
}

$stmt = $conn->prepare("SELECT name FROM videos WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();
$video = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$video) {
    error_log('serve_video.php: Video not found in database for id ' . $video_id);
    http_response_code(404);
    exit('Video not found in database');
}

// Since we no longer store video_path, we cannot serve the actual video file
// This endpoint now returns video information instead
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'video_id' => $video_id,
    'video_name' => $video['name'],
    'message' => 'Video file serving is not available. This system only stores video metadata.'
]);
exit;