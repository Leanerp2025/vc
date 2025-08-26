<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php'); // Redirect to the new login page
    exit;
}
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
    <!-- dashboard_styles.css removed - not needed -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container content-main-container">
        <div class="main-content-wrapper" style="display: flex; flex-direction: column; gap: 25px;">
            <section class="dashboard-content" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-start;">
                <div class="video-player-card card" style="padding-bottom: 0; padding-left: 25px; padding-right: 25px; flex: 1.5;">
                    
                    <h2>Now Playing</h2>
                    <div style="display: flex; flex-direction: row; gap: 8px; align-items: stretch; height: 280px;">
                        <div class="video-player-wrapper" style="flex: 1;">
                            <video id="videoPlayer" controls style="height: 100%; width: 100%; object-fit: cover;">
                                <source id="videoSource" src="" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        
                        <!-- Possible Improvements Section -->
                        <div class="card possible-improvements-card" style="flex: 1; min-width: 350px; max-width: none; display: flex; flex-direction: column; overflow: hidden;">
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
                            <div class="improvements-table-container" style="flex-grow: 1; overflow-y: auto;">
                                <table class="improvements-table">
                                    <thead>
                                        <tr>
                                            <th style="width:12%;">Id</th>
                                            <th style="width:18%;">Cycle #</th>
                                            <th style="width:35%;">Improvement</th>
                                            <th style="width:22%;">Benefit</th>
                                            <th style="width:13%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="improvementsTableBody">
                                        <!-- Dynamic rows go here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add the "Upload again" button here -->
                    
                    <section id="videoDetailsSection" class="video-details-section">
                        <div class="video-details-table-container">
                        <table class="video-details-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">ID</th>
                                    <th style="width: 5%;">Operator</th>
                                    <th style="width: 45%;">Description</th>
                                    <th style="width: 10%;">Type</th>
                                    <th style="width: 15%;">Start Time</th>
                                    <th style="width: 15%;">End Time</th>
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
        <button id="fabUpload" class="fab" title="Upload Video">
            <span class="material-symbols-outlined">add</span>
        </button>
    </main>

    <!-- Upload and Details Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeUploadModal">&times;</span>
            <h2>Upload Video</h2>
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
    </div>
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script src="script.js?v=1.2"></script>
    <script src="search.js"></script>
</html>