<?php
// Ensure clean JSON output without stray buffering warnings
if (ob_get_level() > 0) {
    @ob_end_clean();
}
error_reporting(E_ALL); // Report all errors
header('Content-Type: application/json');

$conn = require 'db.php';

function get_video_by_id($id, $conn) {
    $stmt = $conn->prepare("SELECT id, video_path FROM videos WHERE id = ?");
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
        if (file_exists($video['video_path'])) {
            unlink($video['video_path']);
        }

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
    $result = $conn->query("SELECT id, video_path FROM videos ORDER BY id DESC");
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
        $videoName = $_POST['videoName'];
        $folderId = !empty($_POST['folderId']) ? $_POST['folderId'] : null;

        $videoPath = null; // Initialize videoPath

        // Handle video file upload if present
        if (isset($_FILES['video']) && is_array($_FILES['video']) && isset($_FILES['video']['error']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['video']['tmp_name'];
            $fileName = $_FILES['video']['name'];
            $fileSize = isset($_FILES['video']['size']) ? (int)$_FILES['video']['size'] : null;
            $fileType = $_FILES['video']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['mp4', 'webm', 'ogg']; // Add other allowed video formats
            if (!in_array($fileExtension, $allowedfileExtensions)) {
                echo json_encode(['success' => false, 'error' => 'Invalid video file type. Only MP4, WebM, Ogg are allowed.']);
                exit;
            }

            $uploadDir = realpath(__DIR__ . '/uploads_secure');
            if ($uploadDir === false) {
                echo json_encode(['success' => false, 'error' => 'Server configuration error: Upload directory not found.']);
                exit;
            }

            // Generate a unique file name to prevent overwrites and security issues
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $videoPath = $newFileName; // Store only the filename relative to uploads_secure
                error_log("handle_add_video: File uploaded successfully to " . $destPath);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
                exit;
            }
        } else {
            // Name-only creation path
            $videoPath = null;
            $fileSize = null;
        }

        // Insert video name, folder_id, video_path and file_size into database
        $stmt = $conn->prepare("INSERT INTO videos (name, folder_id, video_path, file_size) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisi", $videoName, $folderId, $videoPath, $fileSize);

        if ($stmt->execute()) {
            $newVideoId = $conn->insert_id;
            error_log("upload.php: New video entry created with ID = " . $newVideoId . ", name = " . $videoName . ", video_path = " . $videoPath);
            echo json_encode([
                'success' => true,
                'message' => 'Video entry created successfully.',
                'video_id' => $newVideoId,
                'video_name' => $videoName,
                'video_path' => $videoPath // Return video_path for client-side use if needed
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
    $sql = "SELECT o.id AS org_id, o.name AS org_name, f.id AS folder_id, f.name AS folder_name
            FROM organizations o
            LEFT JOIN folders f ON o.id = f.organization_id
            ORDER BY o.id, f.id";
    $result = $conn->query($sql);
    $organizations = [];
    while ($row = $result->fetch_assoc()) {
        $org_id = $row['org_id'];
        if (!isset($organizations[$org_id])) {
            $organizations[$org_id] = [
                'id' => $org_id,
                'name' => $row['org_name'],
                'folders' => []
            ];
        }
        if ($row['folder_id']) {
            $organizations[$org_id]['folders'][] = [
                'id' => $row['folder_id'],
                'name' => $row['folder_name']
            ];
        }
    }
    echo json_encode(array_values($organizations));
}

function handle_add_folder($conn) {
    if (empty($_POST['folderName']) || empty($_POST['organizationId'])) {
        echo json_encode(['success' => false, 'error' => 'Folder name and organization are required.']);
        exit;
    }
    $folderName = $_POST['folderName'];
    $organizationId = $_POST['organizationId'];

    $stmt = $conn->prepare("INSERT INTO folders (name, organization_id) VALUES (?, ?)");
    $stmt->bind_param("si", $folderName, $organizationId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Folder added successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}

function handle_list_folders($conn) {
    $sql = "SELECT f.id AS folder_id, f.name AS folder_name, v.id as video_id, v.name AS video_name
            FROM folders f
            LEFT JOIN videos v ON f.id = v.folder_id
            ORDER BY f.id, v.id";
    $result = $conn->query($sql);
    $folders = [];
    while ($row = $result->fetch_assoc()) {
        $folder_id = $row['folder_id'];
        if (!isset($folders[$folder_id])) {
            $folders[$folder_id] = [
                'id' => $folder_id,
                'name' => $row['folder_name'],
                'videos' => []
            ];
        }
        if ($row['video_id']) {
            $folders[$folder_id]['videos'][] = [
                'id' => $row['video_id'],
                'name' => $row['video_name']
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => array_values($folders)]);
}

function handle_list_folders_by_organization($conn) {
    $organization_id = $_GET['organization_id'] ?? null;
    if (!$organization_id) {
        echo json_encode(['success' => false, 'error' => 'No organization ID provided.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, name FROM folders WHERE organization_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $folders = [];
    while ($row = $result->fetch_assoc()) {
        $folders[] = $row;
    }
    echo json_encode($folders);
}

function handle_list_videos_by_folder($conn) {
    $folder_id = $_GET['folder_id'] ?? null;
    if (!$folder_id) {
        echo json_encode(['success' => false, 'error' => 'No folder ID provided.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, video_path FROM videos WHERE folder_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $folder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    echo json_encode($videos);
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
        case 'list_folders_by_organization':
            handle_list_folders_by_organization($conn);
            break;
        case 'list_videos_by_folder':
            handle_list_videos_by_folder($conn);
            break;
        case 'delete':
            handle_delete($conn);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid GET action.']);
    }
}

$conn->close();