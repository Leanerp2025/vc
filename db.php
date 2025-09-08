<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'videocapture';
$username = 'root';
$password = '';

// Initialize connection as null
$conn = null;

try {
    // Check if mysqli extension is available
    if (!class_exists('mysqli')) {
        throw new Exception('mysqli extension not available');
    }
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log the error but don't output anything
    error_log("db.php: Database connection failed: " . $e->getMessage());
    $conn = null;
}

// Only create tables if database connection is available
if ($conn) {
    // sql to create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql) === TRUE) {
        // Table users created successfully or already exists
    } else {
        echo "Error creating users table: " . $conn->error;
    }

    // sql to create organizations table if not exists
$sql = "CREATE TABLE IF NOT EXISTS organizations (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    // Table organizations created successfully or already exists
} else {
    echo "Error creating organizations table: " . $conn->error;
}

// sql to create folders table if not exists
$sql = "CREATE TABLE IF NOT EXISTS folders (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    // Table folders created successfully or already exists
} else {
    echo "Error creating folders table: " . $conn->error;
}

// Create videos table if not exists
$sql = "CREATE TABLE IF NOT EXISTS videos (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED,
    video_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    // Table videos created successfully or already exists
} else {
    echo "Error creating videos table: " . $conn->error;
}

// Check if the column 'video_path' exists in 'videos' table and add it if it doesn't
$result = $conn->query("SHOW COLUMNS FROM videos LIKE 'video_path'");
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE videos ADD COLUMN video_path VARCHAR(500) AFTER file_size";
    if ($conn->query($sql) === TRUE) {
        // Column added successfully
    } else {
        echo "Error adding video_path column: " . $conn->error;
    }
}

// sql to create video_details table if not exists
$sql = "CREATE TABLE IF NOT EXISTS video_details (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_fe INT(6) UNSIGNED,
    video_id INT(6) UNSIGNED NOT NULL,
    operator VARCHAR(30) NOT NULL,
    description TEXT NOT NULL,
    va_nva_enva VARCHAR(30) NOT NULL,
    activity_type VARCHAR(30) NOT NULL DEFAULT 'manual',
    start_time VARCHAR(30) NOT NULL,
    end_time VARCHAR(30) NOT NULL,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    // Table video_details created successfully or already exists
} else {
    // Error creating table
    die("Error creating table: " . $conn->error);
}

// sql to create possible_improvements table if not exists
$sql = "CREATE TABLE IF NOT EXISTS possible_improvements (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_detail_id INT(6) UNSIGNED NOT NULL,
    cycle_number INT(6) UNSIGNED NOT NULL,
    improvement TEXT NOT NULL,
    type_of_benefits VARCHAR(255) NOT NULL,
    video_id INT(6) UNSIGNED NOT NULL,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (video_detail_id) REFERENCES video_details(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    // Table possible_improvements created successfully or already exists
} else {
    echo "Error creating possible_improvements table: " . $conn->error;
}

// Check if the column 'operator_name' exists in 'video_details' table and rename it
$result = $conn->query("SHOW COLUMNS FROM video_details LIKE 'operator_name'");
if ($result && $result->num_rows > 0) {
    $conn->query("ALTER TABLE video_details CHANGE operator_name operator VARCHAR(30) NOT NULL");
}

// Check if the column 'id_fe' exists in 'video_details' table and add it if it doesn't
$result = $conn->query("SHOW COLUMNS FROM video_details LIKE 'id_fe'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE video_details ADD COLUMN id_fe INT(6) UNSIGNED AFTER id");
}

// Migrate from possible_improvements column to activity_type column
$result = $conn->query("SHOW COLUMNS FROM video_details LIKE 'possible_improvements'");
if ($result && $result->num_rows > 0) {
    // Add activity_type column if it doesn't exist
    $activityTypeExists = $conn->query("SHOW COLUMNS FROM video_details LIKE 'activity_type'");
    if ($activityTypeExists && $activityTypeExists->num_rows == 0) {
        $conn->query("ALTER TABLE video_details ADD COLUMN activity_type VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER va_nva_enva");
    }
    // Drop the old possible_improvements column
    $conn->query("ALTER TABLE video_details DROP COLUMN possible_improvements");
}

// Check if video_detail_id column exists in possible_improvements table
$result = $conn->query("SHOW COLUMNS FROM `possible_improvements` LIKE 'video_detail_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `possible_improvements` ADD COLUMN `video_detail_id` INT(6) UNSIGNED AFTER `video_id`");
    $conn->query("ALTER TABLE `possible_improvements` ADD FOREIGN KEY (`video_detail_id`) REFERENCES `video_details`(`id`) ON DELETE CASCADE");
}

// Ensure created_at column exists in videos table
$result = $conn->query("SHOW COLUMNS FROM `videos` LIKE 'created_at'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `videos` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `file_size`");
}

// Ensure correct foreign key on video_details.video_id -> videos.id
$fkResult = $conn->query("SHOW CREATE TABLE `video_details`");
if ($fkResult && $row = $fkResult->fetch_assoc()) {
    $createSql = $row['Create Table'];
    if (strpos($createSql, 'FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`)') === false) {
        // If no correct FK exists, add it
        // First try to drop any FK on video_id silently
        $conn->query("ALTER TABLE `video_details` DROP FOREIGN KEY `video_details_ibfk_1`");
        $conn->query("ALTER TABLE `video_details` ADD CONSTRAINT `fk_video_details_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE");
    }
}

// Ensure correct foreign key on possible_improvements.video_id -> videos.id
$fkPIResult = $conn->query("SHOW CREATE TABLE `possible_improvements`");
if ($fkPIResult && $row = $fkPIResult->fetch_assoc()) {
    $createSql = $row['Create Table'];
    if (strpos($createSql, 'FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`)') === false) {
        // Drop all foreign keys for safety, then recreate correct ones
        $constraintsRes = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'possible_improvements' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if ($constraintsRes) {
            while ($c = $constraintsRes->fetch_assoc()) {
                $conn->query("ALTER TABLE `possible_improvements` DROP FOREIGN KEY `" . $c['CONSTRAINT_NAME'] . "`");
            }
        }
        // Recreate FKs: video_id -> videos.id, and keep video_detail_id -> video_details.id if column exists
        $conn->query("ALTER TABLE `possible_improvements` ADD CONSTRAINT `fk_possible_improvements_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE");
        $colRes = $conn->query("SHOW COLUMNS FROM `possible_improvements` LIKE 'video_detail_id'");
        if ($colRes && $colRes->num_rows > 0) {
            $conn->query("ALTER TABLE `possible_improvements` ADD CONSTRAINT `fk_possible_improvements_video_detail_id` FOREIGN KEY (`video_detail_id`) REFERENCES `video_details`(`id`) ON DELETE CASCADE");
        }
    }
}

} // End of database operations

return $conn;