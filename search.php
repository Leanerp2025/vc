<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$conn = require 'db.php';

$search_query = isset($_GET['query']) ? $_GET['query'] : '';

// Fetch videos based on the search query
$videos = [];
if (!empty($search_query)) {
    $stmt = $conn->prepare("SELECT id, name FROM videos WHERE name LIKE ? ORDER BY id ASC");
    $search_param = "%{$search_query}%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
    <header class="main-header">
        <div class="container flex-between">
            <h1 class="logo">Search</h1>
            <div class="user-info">
                <form action="search.php" method="get" class="search-form">
                    <button type="submit" class="search-btn"><span class="material-symbols-outlined">search</span></button>
                    <input type="text" name="query" placeholder="Search..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="button" class="clear-btn"><span class="material-symbols-outlined">close</span></button>
                </form>
                <span>👤 <?php echo htmlspecialchars($_SESSION["email"]); ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="dashboard-content">
            <div class="card">
                <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <div class="item-list video-grid">
                    <?php if (empty($videos)): ?>
                        <p>No videos found matching your search.</p>
                    <?php else: ?>
                        <?php foreach ($videos as $video): ?>
                            <div class="video-card">
                                <a href="content.php?video_id=<?php echo htmlspecialchars($video['id']); ?>&video_name=<?php echo htmlspecialchars($video['name']); ?>">
                                    <div class="video-thumb-placeholder">📹</div>
                                    <div class="video-meta"><?php echo htmlspecialchars(str_replace('_', ' ', $video['name'])); ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <script src="search.js"></script>
</body>
</html>