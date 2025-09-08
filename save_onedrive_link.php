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

try {
    // Store the OneDrive URL and embed code in the database
    // We'll use video_path for the URL and add embed_code to a new field if needed
    $stmt = $conn->prepare("UPDATE videos SET video_path = ?, file_size = 0 WHERE id = ?");
    $stmt->bind_param("si", $oneDriveUrl, $videoId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'OneDrive link saved successfully.']);
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
