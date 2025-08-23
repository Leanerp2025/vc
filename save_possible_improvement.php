<?php
require_once 'db.php';

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['improvements']) && is_array($data['improvements'])) {
        $improvements = $data['improvements'];
        $all_success = true;

        foreach ($improvements as $imp) {
            $id = isset($imp['id']) && $imp['id'] !== 'null' ? $imp['id'] : null;
            $videoId = $imp['video_id'];
            $videoDetailId = $imp['video_detail_id'];
            $cycleNumber = $imp['cycle_number'];
            $improvement = $imp['improvement'];
            $typeOfBenefits = $imp['type_of_benefits'];

            if ($id) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE possible_improvements SET video_detail_id = ?, cycle_number = ?, improvement = ?, type_of_benefits = ? WHERE id = ? AND video_id = ?");
                $stmt->bind_param('isssii', $videoDetailId, $cycleNumber, $improvement, $typeOfBenefits, $id, $videoId);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO possible_improvements (video_id, video_detail_id, cycle_number, improvement, type_of_benefits) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iiiss', $videoId, $videoDetailId, $cycleNumber, $improvement, $typeOfBenefits);
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
            $response['message'] = 'All improvements saved successfully!';
            unset($response['error']);
        } else {
            $response['message'] = 'An error occurred while saving some improvements.';
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