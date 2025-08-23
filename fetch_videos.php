<?php
require_once 'db.php';

header('Content-Type: application/json');

$sql = "SELECT id, video_path, name, folder_id FROM videos WHERE name != 'V2' AND name != 'V3' ORDER BY id ASC";
$result = $conn->query($sql);

$videos = [];

if ($result === FALSE) {
    // Query failed, output error
    echo json_encode(['success' => false, 'error' => 'SQL Error: ' . $conn->error]);
    $conn->close();
    exit;
}

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}

echo json_encode($videos);

$conn->close();
?>