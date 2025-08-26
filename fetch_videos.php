<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$sql = "SELECT id, video_path, name FROM videos WHERE name != 'V2' AND name != 'V3' ORDER BY id ASC";
$result = $conn->query($sql);

$videos = [];
while ($row = $result->fetch_assoc()) {
    $videos[] = $row;
}

echo json_encode($videos);
$conn->close();
?>