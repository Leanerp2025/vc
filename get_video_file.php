<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$video_id = $_GET['video_id'] ?? null;
error_log('get_video_file.php: video_id = ' . $video_id);

if (!$video_id) {
    error_log('get_video_file.php: video_id is required');
    http_response_code(400);
    exit('Bad Request: video_id is required');
}

// Build absolute path
$uploadDir = __DIR__ . '/uploads_secure/';
$videoFile = null;

// Try to get video info from database if possible
try {
    if (isset($_SESSION['loggedin'])) {
        $conn = require 'db.php';
        
        // Get video information including the video_path
        $stmt = $conn->prepare("SELECT name, file_size, video_path FROM videos WHERE id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $video = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($video && !empty($video['video_path'])) {
            error_log("get_video_file.php: DB video_path found: '{$video['video_path']}'");
            
            if (strpos($video['video_path'], 'http') === 0) {
                // This is a OneDrive URL, redirect to it
                error_log("get_video_file.php: OneDrive URL detected, redirecting");
                
                $oneDriveUrl = $video['video_path'];
                
                // Convert SharePoint/Stream link to direct download URL
                if (strpos($oneDriveUrl, 'stream.aspx') !== false || strpos($oneDriveUrl, 'sharepoint.com') !== false) {
                    $urlParts = parse_url($oneDriveUrl);
                    parse_str($urlParts['query'] ?? '', $queryParams);
                    
                    if (isset($queryParams['id'])) {
                        // Decode the id parameter which is URL encoded
                        $decodedId = urldecode($queryParams['id']);
                        error_log("get_video_file.php: SharePoint id parameter: " . $decodedId);
                        
                        // Build the direct download URL for SharePoint
                        $directUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . '/_layouts/15/download.aspx?SourceUrl=' . urlencode($decodedId);
                        error_log("get_video_file.php: Redirecting to SharePoint download URL: " . $directUrl);
                        
                        header('Location: ' . $directUrl);
                        exit;
                    }
                } else if (strpos($oneDriveUrl, '1drv.ms') !== false || strpos($oneDriveUrl, 'onedrive') !== false) {
                    // Regular OneDrive link
                    $directUrl = $oneDriveUrl . (strpos($oneDriveUrl, '?') ? '&download=1' : '?download=1');
                    error_log("get_video_file.php: Redirecting to OneDrive download URL: " . $directUrl);
                    header('Location: ' . $directUrl);
                    exit;
                }
                
                // Fallback: redirect to original URL
                error_log("get_video_file.php: Using fallback redirect to: " . $oneDriveUrl);
                header('Location: ' . $oneDriveUrl);
                exit;
            } else {
                // This is a local file path
                $candidate = $uploadDir . $video['video_path'];
                error_log("get_video_file.php: Local file path. Checking candidate path: '$candidate'");
                if (file_exists($candidate)) {
                    $videoFile = $candidate;
                    error_log("get_video_file.php: Candidate path exists. Using file: $videoFile");
                } else {
                    error_log("get_video_file.php: Candidate path does NOT exist.");
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("get_video_file.php: Database error, falling back to glob: " . $e->getMessage());
}

// Fallback to glob pattern if DB path missing, invalid, or DB unavailable
if ($videoFile === null) {
    error_log("get_video_file.php: videoFile is null, attempting glob fallback.");
    $glob_pattern = $uploadDir . 'video_' . intval($video_id) . '_*';
    error_log("get_video_file.php: Glob pattern: '$glob_pattern'");
    $matches = glob($glob_pattern);
    if (!empty($matches)) {
        error_log("get_video_file.php: Glob found matches: " . print_r($matches, true));
        // Choose the most recent file by modification time
        usort($matches, function($a, $b) { return filemtime($b) - filemtime($a); });
        $videoFile = $matches[0];
        error_log("get_video_file.php: Using glob fallback file: $videoFile");
    } else {
        error_log("get_video_file.php: Glob found no matches.");
    }
}

if ($videoFile === null) {
    error_log('get_video_file.php: No video file found for video_id ' . $video_id);
    http_response_code(404);
    exit('Video file not found on disk');
}

// Check if file exists and is readable
if (!file_exists($videoFile) || !is_readable($videoFile)) {
    error_log('get_video_file.php: Video file not accessible: ' . $videoFile);
    http_response_code(404);
    exit('Video file not accessible');
}

// Get file info
$fileInfo = pathinfo($videoFile);
$fileSize = filesize($videoFile);
$extension = strtolower($fileInfo['extension'] ?? '');

// Determine MIME type based on file extension
$mimeType = 'video/mp4'; // Default fallback
switch ($extension) {
    case 'mp4':
        $mimeType = 'video/mp4';
        break;
    case 'webm':
        $mimeType = 'video/webm';
        break;
    case 'ogg':
    case 'ogv':
        $mimeType = 'video/ogg';
        break;
    case 'avi':
        $mimeType = 'video/x-msvideo';
        break;
    case 'mov':
        $mimeType = 'video/quicktime';
        break;
    case 'wmv':
        $mimeType = 'video/x-ms-wmv';
        break;
    case 'flv':
        $mimeType = 'video/x-flv';
        break;
    case 'mkv':
        $mimeType = 'video/x-matroska';
        break;
    case '3gp':
        $mimeType = 'video/3gpp';
        break;
    case 'm4v':
        $mimeType = 'video/x-m4v';
        break;
    default:
        // Try to detect MIME type using PHP function
        if (function_exists('mime_content_type')) {
            $detectedMime = mime_content_type($videoFile);
            if ($detectedMime && strpos($detectedMime, 'video/') === 0) {
                $mimeType = $detectedMime;
            }
        }
        // If still no valid video MIME type, try finfo
        if ($mimeType === 'video/mp4' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $videoFile);
            finfo_close($finfo);
            if ($detectedMime && strpos($detectedMime, 'video/') === 0) {
                $mimeType = $detectedMime;
            }
        }
        // If no MIME detection available, default to MP4 for unknown extensions
        break;
}

error_log("get_video_file.php: File extension: $extension, MIME type: $mimeType");

// Set appropriate headers for video streaming
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');

// Check if this is a range request (for seeking in video)
$range = $_SERVER['HTTP_RANGE'] ?? null;
if ($range) {
    $ranges = array_map('trim', explode('=', $range));
    if ($ranges[0] === 'bytes') {
        $range = array_map('trim', explode('-', $ranges[1]));
        $start = $range[0];
        $end = $range[1] ?: $fileSize - 1;
        
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        header('Content-Length: ' . ($end - $start + 1));
        
        // Output the requested range
        $handle = fopen($videoFile, 'rb');
        fseek($handle, $start);
        $buffer = 1024 * 8;
        while (!feof($handle) && ftell($handle) <= $end) {
            $remaining = $end - ftell($handle) + 1;
            $readSize = min($buffer, $remaining);
            echo fread($handle, $readSize);
        }
        fclose($handle);
        exit;
    }
}

// Output the entire file
readfile($videoFile);
exit;
?>
