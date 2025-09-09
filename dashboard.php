<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$conn = require 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Video Capture</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
</head>
<body>
    <header class="main-header">
        <a href="dashboard.php" style="text-decoration: none; color: inherit;"><h1 class="logo">Dashboard</h1></a>
        <form action="search.php" method="get" class="search-form">
            <input type="text" name="query" placeholder="Search..." class="search-input">
            <button type="button" class="clear-btn"><span class="material-symbols-outlined">close</span></button>
            <button type="submit" class="search-btn"><span class="material-symbols-outlined">search</span></button>
        </form>
        <span class="user-email-display"><span class="material-symbols-outlined">account_circle</span> <?php echo htmlspecialchars($_SESSION["email"]); ?></span>
        <a href="logout.php" class="btn-logout"><span class="material-symbols-outlined">logout</span> Logout</a>
    </header>

    <main class="container">
        <section class="dashboard-content">
            <div class="card welcome-card">
                <h2>Welcome to your Dashboard!</h2>
                <p>This is where you can manage your organizations, folders, and video captures.</p>
            </div>

            <div class="dashboard-grid-container">
                <div class="card">
                    <h2>Organizations <a href="organizations_list.php" class="arrow-icon"><span class="material-symbols-outlined">arrow_forward</span></a></h2>
                    <div id="organizationsList" class="item-list">
    <?php
    // Fetch all organizations
    $organizations = [];
    $stmt = $conn->prepare("SELECT id, name FROM organizations ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $organizations[] = $row;
    }
    $stmt->close();
    ?>
    <?php if (empty($organizations)): ?>
        <p>No organizations added yet.</p>
    <?php else: ?>
        <?php foreach ($organizations as $org): ?>
            <div class="item-card">
                <div class="item-card-header">
                    <span class="file-name"><?php echo htmlspecialchars($org['name']); ?></span>
                    <div class="item-card-actions">
                        <a href="organizations_list.php" class="arrow-icon" title="Open">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <button class="delete-btn" data-id="<?php echo htmlspecialchars($org['id']); ?>" data-type="organization" title="Delete">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h2>Folders <a href="folders_list.php" class="arrow-icon"><span class="material-symbols-outlined">arrow_forward</span></a></h2>
                    <div id="foldersList" class="item-list">
    <?php
    // Fetch all folders
    $folders = [];
    $stmt = $conn->prepare("SELECT id, name FROM folders ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $folders[] = $row;
    }
    $stmt->close();
    ?>
    <?php if (empty($folders)): ?>
        <p>No folders added yet.</p>
    <?php else: ?>
        <?php foreach ($folders as $folder): ?>
            <div class="item-card">
                <div class="item-card-header">
                    <span class="file-name"><?php echo htmlspecialchars($folder['name']); ?></span>
                    <div class="item-card-actions">
                        <a href="folders_list.php" class="arrow-icon" title="Open">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <button class="delete-btn" data-id="<?php echo htmlspecialchars($folder['id']); ?>" data-type="folder" title="Delete">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h2>Video Captures <a href="all_video_captures.php" class="arrow-icon"><span class="material-symbols-outlined">arrow_forward</span></a></h2>
                    <div id="videoCapturesList" class="item-list">
    <?php
    // Fetch all videos with creation date
    $videos = [];
    $stmt = $conn->prepare("SELECT id, name, file_size, created_at FROM videos ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    $stmt->close();
    ?>
    <?php if (empty($videos)): ?>
        <p>No video captures added yet.</p>
    <?php else: ?>
                                <?php foreach ($videos as $video): ?>
                            <div class="item-card">
                                <div class="item-card-header">
                                    <span class="file-name">
                                        <?php echo htmlspecialchars($video['name']); ?>
                                        <?php if ($video['file_size'] && $video['file_size'] > 0): ?>
                                            <span class="material-symbols-outlined" style="color: #28a745; font-size: 16px;" title="Video file uploaded">check_circle</span>
                                            <br><small style="color: #666; font-size: 0.8em;">
                                                <?php echo number_format($video['file_size']/1024/1024, 2); ?> MB
                                            </small>
                                        <?php endif; ?>
                                    </span>
                                    <div class="item-card-actions">
                                        <a href="content.php?video_id=<?php echo htmlspecialchars($video['id']); ?>&video_name=<?php echo htmlspecialchars($video['name']); ?>" class="arrow-icon" title="Open">
                                            <span class="material-symbols-outlined">arrow_forward</span>
                                        </a>
                                        <button class="delete-btn" data-id="<?php echo htmlspecialchars($video['id']); ?>" data-type="video" title="Delete">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
    <?php endif; ?>
</div>
                </div>
            </div>
        </section>
    </main>

    <button id="fabAddItem" class="fab" title="Add New Item">
    <span class="material-symbols-outlined">add</span>
</button>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddItemModal">&times;</span>
            <h2>Add New Item</h2>
            <div class="form-group">
                <label for="itemType">Select Item Type:</label>
                <select id="itemType" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="organization">Organization</option>
                    <option value="folder">Folder</option>
                    <option value="video">Video Capture</option>
                </select>
            </div>

            <div id="organizationForm" style="display:none;">
                <h3>Add Organization</h3>
                <form id="addOrganizationForm">
                    <div class="form-group">
                        <label for="organizationName">Organization Name:</label>
                        <input type="text" id="organizationName" name="organizationName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Organization</button>
                </form>
            </div>

            <div id="folderForm" style="display:none;">
                <h3>Add Folder</h3>
                <form id="addFolderForm">
                    <div class="form-group">
                        <label for="folderName">Folder Name:</label>
                        <input type="text" id="folderName" name="folderName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Folder</button>
                </form>
            </div>

            <div id="videoForm" style="display:none;">
                <h3>Add Video Capture</h3>
                <form id="addVideoForm">
                    <div class="form-group">
                        <label for="videoName">Video Name:</label>
                        <input type="text" id="videoName" name="videoName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Video Name</button>
                </form>
            </div>
        </div>
    </div>
    <script src="dashboard_script.js"></script>
    <script src="search.js"></script>
    <script>
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-btn');
        if (!btn) return;
        const id = btn.dataset.id;
        const type = btn.dataset.type;
        if (!id || !type) return;
        if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
        fetch('delete_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}&type=${encodeURIComponent(type)}`,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = btn.closest('.item-card');
                if (card) card.remove();
            } else {
                alert('Error deleting item: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => alert('Error deleting item: ' + err.message));
    });
    </script>
</body>
</html>