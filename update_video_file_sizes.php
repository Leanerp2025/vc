<?php
require_once 'db.php';

$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads_secure' . DIRECTORY_SEPARATOR;

// Get all videos from the database
$sql = "SELECT id, video_path FROM videos";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $video_id = $row['id'];
        $video_path = $row['video_path'];
        $full_path = $upload_dir . $video_path;

        if (file_exists($full_path)) {
            $file_size = filesize($full_path);
            // Update the file_size in the database
            $update_sql = "UPDATE videos SET file_size = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("di", $file_size, $video_id);
            if ($stmt->execute()) {
                echo "Updated video ID $video_id with file size: $file_size bytes\n";
            } else {
                echo "Error updating video ID $video_id: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "File not found for video ID $video_id: $full_path\n";
        }
    }
} else {
    echo "No videos found in the database.\n";
}

$conn->close();
?>