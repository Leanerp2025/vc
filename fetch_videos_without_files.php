<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'videos' => []];

$sql = "SELECT id, name FROM videos ORDER BY name ASC";
$result = $conn->query($sql);

if ($result === FALSE) {
    $response['error'] = 'SQL Error: ' . $conn->error;
    echo json_encode($response);
    exit;
}

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $response['videos'][] = $row;
    }
}

$response['success'] = true;

$conn->close();

echo json_encode($response);
?>