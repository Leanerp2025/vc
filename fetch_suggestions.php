<?php
$conn = require 'db.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';
$suggestions = [];

if (strlen($query) > 0) {
    $stmt = $conn->prepare("SELECT name FROM videos WHERE name LIKE ? LIMIT 10");
    $search_param = "%{$query}%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
    $stmt->close();
}

$conn->close();

echo json_encode($suggestions);
?>