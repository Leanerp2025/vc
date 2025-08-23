<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$conn = require 'db.php';

// Fetch all organizations
$organizations = [];
$stmt = $conn->prepare("SELECT id, name FROM organizations ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $organizations[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Organizations</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="dashboard_styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <section class="dashboard-content">
            <div class="card">
                <h2>All Organizations</h2>
                <div class="item-list">
                    <?php if (empty($organizations)): ?>
                        <p>No organizations added yet.</p>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="item-card">
                                <div class="item-card-header">
                                    <span class="file-name"><?php echo htmlspecialchars($org['name']); ?></span>
                                    <div class="item-card-actions">
                                        <a href="organization_details.php?id=<?php echo htmlspecialchars($org['id']); ?>" class="arrow-icon" title="Open"><span class="material-symbols-outlined">arrow_forward</span></a>
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
        </section>
    </main>
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
                location.reload();
            } else {
                alert('Error deleting item: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => alert('Error deleting item: ' + err.message));
    });
    </script>
    <script src="search.js"></script>
</body>
</html>