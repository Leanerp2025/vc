<?php
require_once 'db.php';

header('Content-Type: application/json');

// Get sorting parameters
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'name'; // Default to 'name'
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC'; // Default to 'ASC'

// Whitelist allowed columns to prevent SQL injection
$allowedSortBy = ['id', 'name', 'file_size', 'created_at'];
if (!in_array($sortBy, $allowedSortBy)) {
    $sortBy = 'name'; // Fallback to default if invalid
}

// Whitelist allowed sort orders
$allowedSortOrder = ['ASC', 'DESC'];
if (!in_array(strtoupper($sortOrder), $allowedSortOrder)) {
    $sortOrder = 'ASC'; // Fallback to default if invalid
}

// Construct the SQL query with dynamic sorting
$sql = "SELECT id, name, file_size, video_path, created_at FROM videos ORDER BY " . $sortBy . " " . $sortOrder;

$result = $conn->query($sql);

$videos = [];

if ($result === FALSE) {
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