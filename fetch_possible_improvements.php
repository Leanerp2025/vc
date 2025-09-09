<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'improvements' => []];

$video_id = $_GET['video_id'] ?? null;

if (!$video_id) {
    $response['error'] = 'Video ID is required.';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT id, video_detail_id, cycle_number, improvement, type_of_benefits FROM possible_improvements WHERE video_id = ? ORDER BY id ASC");

if ($stmt === false) {
    $response['error'] = 'Prepare failed: ' . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $response['improvements'][] = $row;
    }
}

$response['success'] = true;

$stmt->close();
$conn->close();

echo json_encode($response);
?>