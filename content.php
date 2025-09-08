<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php'); // Redirect to the new login page
    exit;
}

// --- START: Server-side video loading logic ---
$videoUrl = '';
$videoName = '';
$videoId = null;

// 1. Determine the video ID to load
if (isset($_GET['video_id'])) {
    $videoId = $_GET['video_id'];
    $_SESSION['last_video_id'] = $videoId; // Remember this video for subsequent refreshes
} elseif (isset($_SESSION['last_video_id'])) {
    $videoId = $_SESSION['last_video_id']; // Fallback to the last viewed video
}

// 2. If we have a video ID, fetch its details from the DB
if ($videoId) {
    $conn = require 'db.php';
    if ($conn) {
        $stmt = $conn->prepare("SELECT name, video_path, file_size FROM videos WHERE id = ?");
        $stmt->bind_param("i", $videoId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($video = $result->fetch_assoc()) {
            // A video is considered playable if it has a path or a size
            if (!empty($video['video_path']) || !empty($video['file_size'])) {
                $videoUrl = "get_video_file.php?video_id=" . $videoId . "&t=" . time(); // Cache-busting timestamp
                $videoName = $video['name'];
            }
        }
        $stmt->close();
        $conn->close();
    }
}
// --- END: Server-side video loading logic ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Upload and Details</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
</head>
<body data-video-id="<?= htmlspecialchars($videoId ?? '') ?>" data-video-name="<?= htmlspecialchars($videoName ?? '') ?>">
    <?php include 'header.php'; ?>
    <main class="container content-main-container">
        <div class="main-content-wrapper" style="display: flex; flex-direction: column; gap: 25px;">
            <section class="dashboard-content" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-start;">
                <div class="video-player-card card" style="padding-bottom: 0; padding-left: 25px; padding-right: 25px; flex: 1.5;">
                    <div class="sticky-header">
                        <h2>Upload Video</h2>
                        <div style="display: flex; flex-direction: row; gap: 8px; align-items: stretch; height: 280px;">
                                        <div class="upload-interface-wrapper" style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #007bff; border-radius: 8px; padding: 15px; background-color: #f8f9fa; box-sizing: border-box; overflow: hidden;">
                                            
                                            <!-- Upload Tabs -->
                                            <div class="upload-tabs" style="border-bottom: 1px solid #ddd; margin-bottom: 15px; width: 100%; box-sizing: border-box; display: flex;">
                                                <button class="tab-link active" onclick="openUploadTab(event, 'tabFileUpload')" style="background: none; border: none; padding: 8px 12px; cursor: pointer; border-bottom: 2px solid #007bff; color: #007bff; font-weight: 500; flex: 1; text-align: center; box-sizing: border-box;">Upload File</button>
                                                <button class="tab-link" onclick="openUploadTab(event, 'tabOneDrive')" style="background: none; border: none; padding: 8px 12px; cursor: pointer; color: #007bff; flex: 1; text-align: center; box-sizing: border-box;">From OneDrive Link</button>
                                            </div>

                                            <!-- File Upload Tab -->
                                            <div id="tabFileUpload" class="tab-content" style="display: block; width: 100%; box-sizing: border-box; overflow: hidden;">
                                                <form id="uploadForm" enctype="multipart/form-data" style="width: 100%; box-sizing: border-box;">
                                                    <div class="form-group" id="videoCaptureSelection" style="margin-bottom: 10px; box-sizing: border-box;">
                                                        <label for="videoCaptureNameDisplay" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video Capture:</label>
                                                        <span id="videoCaptureNameDisplay" class="form-control-static" style="display: block; padding: 4px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;"></span>
                                                    </div>

                                                    <div class="form-group" style="margin-bottom: 10px; box-sizing: border-box;">
                                                        <label for="video" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video File</label>
                                                        <input type="file" id="video" name="video" accept="video/*" required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9em;">
                                                    </div>
                                                    <button type="submit" class="btn-primary" style="width: 100%; padding: 8px; box-sizing: border-box; font-size: 0.9em;">Upload Video</button>
                                                    <div id="uploadProgress" class="progress-bar" style="display:none; margin-top: 8px; box-sizing: border-box;">
                                                        <div class="progress"></div>
                                                    </div>
                                                    <div id="uploadMessage" style="margin-top: 8px; box-sizing: border-box; font-size: 0.85em;"></div>
                                                </form>
                                            </div>

                                            <!-- OneDrive Tab -->
                                            <div id="tabOneDrive" class="tab-content" style="display: none; width: 100%; box-sizing: border-box; overflow: hidden;">
                                                <form id="oneDriveForm" style="width: 100%; box-sizing: border-box;">
                                                    <div class="form-group" id="videoCaptureSelectionOneDrive" style="margin-bottom: 10px; box-sizing: border-box;">
                                                        <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video Capture:</label>
                                                        <span id="videoCaptureNameDisplayOneDrive" class="form-control-static" style="display: block; padding: 4px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;"></span>
                                                    </div>
                                                    <div class="form-group" style="margin-bottom: 10px; box-sizing: border-box;">
                                                        <label for="oneDriveUrl" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">OneDrive Share Link or Embed Code</label>
                                                        <textarea id="oneDriveUrl" name="oneDriveUrl" class="form-control" rows="2" placeholder="Paste OneDrive share link or embed code here" required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; box-sizing: border-box; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word;"></textarea>
                                                    </div>
                                                    <button type="button" class="btn-primary" id="oneDriveImportBtn" style="width: 100%; padding: 8px; box-sizing: border-box; font-size: 0.9em;">Import Video</button>
                                                </form>
                                                <div id="oneDriveMessage" style="margin-top: 8px; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box; font-size: 0.85em;"></div>
                                            </div>
                                        </div>
                        
                        <!-- Possible Improvements Section -->
                        <div class="card possible-improvements-card" style="flex: 1; min-width: 350px; max-width: none; display: flex; flex-direction: column;">
                            <div class="improvements-header">
                                <h2>Possible Improvements</h2>
                                <div class="improvements-actions">
                                    <button id="improvementsAddRow" class="icon-btn" title="Add Row">
                                        <span class="material-symbols-outlined">add</span>
                                    </button>
                                    <button id="improvementsSaveAll" class="icon-btn" title="Save All">
                                        <span class="material-symbols-outlined">check</span>
                                    </button>
                                </div>
                            </div>
                            <table class="improvements-table improvements-table-header">
                                <thead>
                                    <tr>
                                        <th style="width:12%;">Id</th>
                                        <th style="width:18%;">Cycle #</th>
                                        <th style="width:35%;">Improvement</th>
                                        <th style="width:22%;">Benefit</th>
                                        <th style="width:13%;">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                            <div class="improvements-table-container" style="flex-grow: 1; overflow-y: auto;">
                                <table class="improvements-table improvements-table-body">
                                    <tbody id="improvementsTableBody">
                                        <!-- Dynamic rows go here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <section id="videoDetailsSection" class="video-details-section">
                    <div class="video-details-table-container">
                    <table class="video-details-table">
                        <thead>
                                <tr>
                                    <th style="width: 2%;">ID</th>
                                    <th style="width: 5%;">Operator</th>
                                    <th style="width: 30%;">Description</th>
                                    <th style="width: 6%;">Type</th>
                                    <th style="width: 6%;">Activity Type</th>
                                    <th style="width: 13%;" class="time-col">Start Time</th>
                                    <th style="width: 13%;" class="time-col">End Time</th>
                                <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="detailsTableBody">
                                <!-- Rows will be added here dynamically -->
                                
                            </tbody>
                        </table>
                        </div>
                        <div class="save-details-button-container">
                            <button id="addRowBtn" class="btn-primary btn-small" title="Add Row">
                                <span class="material-symbols-outlined">add</span>
                            </button>
                            <button id="saveAllDetailsBtn" class="btn-primary btn-small" style="margin-left: 10px;" title="Save All Details">
                                <span class="material-symbols-outlined">check</span>
                            </button>
                        </div>
                    </section>
                </div>
            </section>
            <div class="video-list-card card">
                <h2>My Videos</h2>
                <div class="sort-controls" style="margin-bottom: 15px;">
                    <label for="sortSelect">Sort by:</label>
                    <select id="sortSelect" class="form-control" style="width: auto; display: inline-block; margin-right: 10px;">
                        <option value="name">Name</option>
                        <option value="file_size">Size</option>
                        <option value="created_at">Date Created</option>
                    </select>
                    <button id="sortAscBtn" class="btn-primary btn-small">Ascending</button>
                    <button id="sortDescBtn" class="btn-primary btn-small" style="margin-left: 5px;">Descending</button>
                </div>
                <div class="video-grid-container">
                    <div id="videoGrid" class="video-grid"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Upload and Details Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeUploadModal">&times;</span>
            <h2>Upload Video</h2>

            <div class="upload-tabs" style="border-bottom: 1px solid #ddd; margin-bottom: 12px;">
                <button class="tab-link active" onclick="openUploadTab(event, 'tabFileUpload')" style="background: none; border: none; padding: 10px 12px; cursor: pointer; border-bottom: 2px solid #007bff; color: #007bff; font-weight: 500;">Upload File</button>
                <button class="tab-link" onclick="openUploadTab(event, 'tabOneDrive')" style="background: none; border: none; padding: 10px 12px; cursor: pointer; color: #007bff;">From OneDrive Link</button>
            </div>

            <div id="tabFileUpload" class="tab-content" style="display: block;">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group" id="videoCaptureSelection">
                        <label for="videoCaptureNameDisplay">Video Capture:</label>
                        <span id="videoCaptureNameDisplay" class="form-control-static"></span>
                    </div>

                    <div class="form-group">
                        <label for="video">Video File</label>
                        <input type="file" id="video" name="video" accept="video/*" required>
                    </div>
                    <button type="submit" class="btn-primary">Upload</button>
                    <div id="uploadProgress" class="progress-bar" style="display:none;">
                        <div class="progress"></div>
                    </div>
                    <div id="uploadMessage"></div>
                </form>
            </div>

            <div id="tabOneDrive" class="tab-content" style="display: none;">
                <form id="oneDriveForm">
                    <div class="form-group" id="videoCaptureSelectionOneDrive">
                        <label>Video Capture:</label>
                        <span id="videoCaptureNameDisplayOneDrive" class="form-control-static"></span>
                    </div>
                    <div class="form-group">
                        <label for="oneDriveUrl">OneDrive Share Link or Embed Code</label>
                        <textarea id="oneDriveUrl" name="oneDriveUrl" class="form-control" rows="4" placeholder="Paste OneDrive share link or embed code here" required></textarea>
                        <small class="form-text text-muted">You can paste either a OneDrive share link or the embed code from SharePoint</small>
                    </div>
                    <button type="button" class="btn-primary" id="oneDriveImportBtn">Import Video</button>
                </form>
                <div id="oneDriveMessage" style="margin-top: 8px;"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script>
    function openUploadTab(evt, tabId) {
        var i, contents, links;
        contents = document.getElementsByClassName('tab-content');
        for (i = 0; i < contents.length; i++) contents[i].style.display = 'none';
        links = document.getElementsByClassName('tab-link');
        for (i = 0; i < links.length; i++) links[i].style.borderBottom = '2px solid transparent';
        document.getElementById(tabId).style.display = 'block';
        evt.currentTarget.style.borderBottom = '2px solid #007bff';
        // sync capture name between tabs if present
        var src = document.getElementById('videoCaptureNameDisplay');
        var dst = document.getElementById('videoCaptureNameDisplayOneDrive');
        if (src && dst) dst.textContent = src.textContent;
    }
    </script>
    <script src="script.js?v=1.2"></script>
    <script src="search.js"></script>
</html>