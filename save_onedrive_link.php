<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$videoId = $input['video_id'] ?? null;
$oneDriveUrl = $input['onedrive_url'] ?? null;
$embedCode = $input['embed_code'] ?? null;

if (!$videoId || !$oneDriveUrl) {
    echo json_encode(['success' => false, 'error' => 'Missing video ID or OneDrive URL.']);
    exit;
}

// Try to derive a human-friendly file name from the OneDrive URL
function deriveFileNameFromUrl($url) {
    // Prefer filename from query param id which often contains the full path (/.../filename.mp4)
    $label = '';
    try {
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $params);
            if (!empty($params['id'])) {
                $idPath = urldecode($params['id']); // e.g. /personal/.../Documents/.../20250818_160510A.mp4
                $baseFromId = basename($idPath);
                if ($baseFromId) {
                    $label = $baseFromId;
                }
            }
            // Sometimes filename may be present in other params (e.g., filename or download)
            if (!$label && !empty($params['filename'])) {
                $label = urldecode($params['filename']);
            }
        }
        // Fallback to URL path basename if query didn't help
        if (!$label) {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $label = urldecode(basename($path));
            }
        }
        // Strip extension if present (e.g., .mp4)
        if ($label && str_contains($label, '.')) {
            $label = preg_replace('/\.[^.]+$/', '', $label);
        }
        // Final fallback
        if (!$label || $label === '/' || $label === '.' || $label === '..') {
            $label = 'OneDrive Video';
        }
    } catch (Exception $e) {
        $label = 'OneDrive Video';
    }
    return $label;
}

try {
    // Compute file name to store in videos.name
    $derivedName = deriveFileNameFromUrl($oneDriveUrl);

    // Update: store URL into video_path, zero file_size, and set name to derived file name
    $stmt = $conn->prepare("UPDATE videos SET video_path = ?, file_size = 0, name = ? WHERE id = ?");
    $stmt->bind_param("ssi", $oneDriveUrl, $derivedName, $videoId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'OneDrive link saved successfully.', 'video_name' => $derivedName]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
