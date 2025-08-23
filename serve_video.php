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

$stmt = $conn->prepare("SELECT video_path FROM videos WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();
$video = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$video || empty($video['video_path'])) {
    error_log('serve_video.php: Video not found in database for id ' . $video_id);
    http_response_code(404);
    exit('Not Found');
}

error_log('serve_video.php: video_path from db = ' . $video['video_path']);

$uploadDir = realpath(__DIR__ . '/uploads_secure');

if ($uploadDir === false) {
    error_log('serve_video.php: The uploads_secure directory does not exist or is not accessible.');
    http_response_code(500);
    exit('Server configuration error: Upload directory not found.');
}

$filePath = $uploadDir . DIRECTORY_SEPARATOR . basename($video['video_path']);

error_log('serve_video.php: Constructed file path = ' . $filePath);

if (!file_exists($filePath)) {
    error_log('serve_video.php: File not found on server at ' . $filePath);
    http_response_code(404);
    exit('File not found on server');
}

error_log('serve_video.php: File found. Serving...');

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;