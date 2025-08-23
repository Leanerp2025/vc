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
    <title>All Video Captures</title>
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
        <section class="all-videos-content">
            <h1>All Video Captures</h1>
            <div id="allVideoCapturesList" class="item-list video-grid">
                <?php
                // Fetch all videos
                $videos = [];
                $stmt = $conn->prepare("SELECT id, video_path, name FROM videos ORDER BY id ASC");
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
                            <span><?php echo htmlspecialchars(str_replace('_', ' ', $video['name'])); ?></span>
                            <span class="file-name"><?php echo htmlspecialchars(basename($video['video_path'])); ?></span>
                            <a href="content.php?video_id=<?php echo htmlspecialchars($video['id']); ?>&video_name=<?php echo htmlspecialchars($video['name']); ?>" class="arrow-icon"><span class="material-symbols-outlined">arrow_forward</span></a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="script.js"></script>
    <script src="search.js"></script>
</body>
</html>