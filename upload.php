<?php
// Ensure clean JSON output without stray buffering warnings
if (ob_get_level() > 0) {
    @ob_end_clean();
}
error_reporting(E_ALL); // Report all errors
header('Content-Type: application/json');

$conn = require 'db.php';

function get_video_by_id($id, $conn) {
    $stmt = $conn->prepare("SELECT id, name FROM videos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function handle_delete($conn) {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'error' => 'No video ID provided.']);
        exit;
    }
    $id = $_GET['id'];
    $video = get_video_by_id($id, $conn);

    if ($video) {
        // Delete file from server
            // File deletion logic removed since we no longer store video_path

        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete video from database.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Video not found.']);
    }
}

function handle_list_videos($conn) {
    $result = $conn->query("SELECT id, name FROM videos ORDER BY id DESC");
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    echo json_encode($videos);
}

function handle_add_video($conn) {
    error_log("handle_add_video: Received POST data: " . print_r($_POST, true));

    try {
        if (empty($_POST['videoName'])) {
            echo json_encode(['success' => false, 'error' => 'Video name cannot be empty.']);
            exit;
        }
        $videoName = trim($_POST['videoName']);

        // Validate video name length (only maximum length check)
        if (strlen($videoName) > 255) {
            echo json_encode(['success' => false, 'error' => 'Video name must be less than 255 characters.']);
            exit;
        }

        // Check if video with this name already exists
        $checkStmt = $conn->prepare("SELECT id FROM videos WHERE name = ?");
        $checkStmt->bind_param("s", $videoName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $checkStmt->close();
            echo json_encode(['success' => false, 'error' => 'A video with this name already exists. Please choose a different name.']);
            exit;
        }
        $checkStmt->close();

        // Insert video name into database with proper defaults
        $stmt = $conn->prepare("INSERT INTO videos (name, video_path, file_size, created_at) VALUES (?, NULL, NULL, NOW())");
        $stmt->bind_param("s", $videoName);

        if ($stmt->execute()) {
            $newVideoId = $conn->insert_id;
            error_log("upload.php: New video capture created with ID = " . $newVideoId . ", name = " . $videoName);
            
            // Return success with all necessary data for dashboard
            echo json_encode([
                'success' => true,
                'message' => 'Video capture created successfully! You can now upload the video file.',
                'video_id' => $newVideoId,
                'video_name' => $videoName,
                'file_size' => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("handle_add_video: Exception caught: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}

function handle_add_details($conn) {
    $video_id = $_POST['video_id'];
    $operator_name = $_POST['operator_name'];
    $description = $_POST['description'];
    $va_nva_enva = $_POST['va_nva_enva'];
    $possible_improvements = $_POST['possible_improvements'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO video_details (video_id, operator_name, description, va_nva_enva, possible_improvements, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $video_id, $operator_name, $description, $va_nva_enva, $possible_improvements, $start_time, $end_time);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}

function handle_get_details($conn) {
    $video_id = $_GET['video_id'];
    $stmt = $conn->prepare("SELECT * FROM video_details WHERE video_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    echo json_encode($details);
}

function handle_delete_detail($conn) {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'error' => 'No detail ID provided.']);
        exit;
    }
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM video_details WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete detail from database.']);
    }
    $stmt->close();
}

function handle_add_organization($conn) {
    if (empty($_POST['organizationName'])) {
        echo json_encode(['success' => false, 'error' => 'Organization name cannot be empty.']);
        exit;
    }
    $organizationName = $_POST['organizationName'];

    $stmt = $conn->prepare("INSERT INTO organizations (name) VALUES (?)");
    $stmt->bind_param("s", $organizationName);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Organization added successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}

function handle_list_organizations($conn) {
    $result = $conn->query("SELECT id, name FROM organizations ORDER BY name ASC");
    $organizations = [];
    while ($row = $result->fetch_assoc()) {
        $organizations[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    echo json_encode($organizations);
}

function handle_add_folder($conn) {
    if (empty($_POST['folderName'])) {
        echo json_encode(['success' => false, 'error' => 'Folder name is required.']);
        exit;
    }
    
    $folderName = $_POST['folderName'];
    $stmt = $conn->prepare("INSERT INTO folders (name) VALUES (?)");
    $stmt->bind_param("s", $folderName);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Folder added successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}

function handle_list_folders($conn) {
    $result = $conn->query("SELECT id, name FROM folders ORDER BY name ASC");
    $folders = [];
    while ($row = $result->fetch_assoc()) {
        $folders[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    echo json_encode(['success' => true, 'data' => $folders]);
}

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_organization':
            handle_add_organization($conn);
            break;
        case 'add_folder': 
            handle_add_folder($conn);
            break;
        case 'add_video':
            handle_add_video($conn);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid POST action.']);
    }
} else {
    switch ($action) {
        case 'list_videos':
            handle_list_videos($conn);
            break;
        case 'list_organizations':
            handle_list_organizations($conn);
            break;
        case 'list_folders':
            handle_list_folders($conn);
            break;
        case 'delete':
            handle_delete($conn);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid GET action.']);
    }
}

$conn->close();