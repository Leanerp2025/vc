<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? null;

    if (!$id || !$type) {
        $response['error'] = 'ID and type are required.';
        echo json_encode($response);
        exit;
    }

    $stmt = null;
    $table = '';
    $id_column = 'id'; // Default ID column name

    switch ($type) {
        case 'organization':
            $table = 'organizations';
            break;
        case 'folder':
            $table = 'folders';
            break;
        case 'video':
            $table = 'videos';
            // Before deleting the video record, get the video_path to delete the file
            $path_stmt = $conn->prepare("SELECT video_path FROM videos WHERE id = ?");
            $path_stmt->bind_param("i", $id);
            $path_stmt->execute();
            $path_result = $path_stmt->get_result();
            $video_data = $path_result->fetch_assoc();
            $path_stmt->close();

            if ($video_data && !empty($video_data['video_path'])) {
                $filePath = __DIR__ . '/' . $video_data['video_path'];
                if (file_exists($filePath)) {
                    if (!unlink($filePath)) {
                        $response['error'] = 'Failed to delete video file.';
                        echo json_encode($response);
                        exit;
                    }
                }
            }
            break;
        case 'video_detail': // NEW CASE
            $table = 'video_details';
            break;
        case 'possible_improvement':
            $table = 'possible_improvements';
            break;
    }

    if (!empty($table)) {
        $stmt = $conn->prepare("DELETE FROM " . $table . " WHERE " . $id_column . " = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = ucfirst($type) . ' deleted successfully.';
                } else {
                    $response['error'] = ucfirst($type) . ' not found or already deleted.';
                }
            } else {
                $response['error'] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['error'] = 'Failed to prepare statement: ' . $conn->error;
        }}
} else {
    $response['error'] = 'Invalid request method.';
}

$conn->close();
echo json_encode($response);
?>