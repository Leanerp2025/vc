<?php
require_once 'db.php';

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['details']) && is_array($data['details'])) {
        $details = $data['details'];
        $all_success = true;

        foreach ($details as $detail) {
            $id = isset($detail['id']) && $detail['id'] !== 'null' ? $detail['id'] : null;
            $videoId = $detail['video_id'];
            $operator = $detail['operator'];
            $description = $detail['description'];
            $vaNvaEnva = $detail['va_nva_enva'];
            $startTime = $detail['start_time'];
            $endTime = $detail['end_time'];

            if ($id) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE video_details SET operator = ?, description = ?, va_nva_enva = ?, start_time = ?, end_time = ? WHERE id = ? AND video_id = ?");
                $stmt->bind_param('sssssii', $operator, $description, $vaNvaEnva, $startTime, $endTime, $id, $videoId);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO video_details (video_id, operator, description, va_nva_enva, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('isssss', $videoId, $operator, $description, $vaNvaEnva, $startTime, $endTime);
            }

            if (!$stmt->execute()) {
                $all_success = false;
                $response['error'] = $stmt->error;
                break;
            }
            $stmt->close();
        }

        if ($all_success) {
            $response['success'] = true;
            $response['message'] = 'All video details saved successfully!';
            unset($response['error']);
        } else {
            $response['message'] = 'An error occurred while saving some details.';
        }
    } else {
        $response['error'] = 'Invalid data format.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>