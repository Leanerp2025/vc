<?php
$config = require 'config.php';

$servername = $config['db']['host'];
$username = $config['db']['username'];
$password = $config['db']['password'];
$dbname = $config['db']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
  // Database created successfully or already exists
} else {
  // Error creating database
  die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// sql to create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
  // Table users created successfully or already exists
} else {
  // Error creating table
  die("Error creating table: " . $conn->error);
}

// sql to create organizations table if not exists
$sql = "CREATE TABLE IF NOT EXISTS organizations (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
)";

if ($conn->query($sql) === TRUE) {
  // Table organizations created successfully or already exists
} else {
  // Error creating table
  die("Error creating table: " . $conn->error);
}

// sql to create folders table if not exists
$sql = "CREATE TABLE IF NOT EXISTS folders (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
)";

if ($conn->query($sql) === TRUE) {
  // Table categories created successfully or already exists
} else {
  // Error creating table
  die("Error creating table: " . $conn->error);
}

// sql to create videos table if not exists
$sql = "CREATE TABLE IF NOT EXISTS videos (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_path VARCHAR(255) NULL,
  name VARCHAR(255) NOT NULL,
  file_size BIGINT UNSIGNED NULL
)";

if ($conn->query($sql) === TRUE) {
  // Table videos created successfully or already exists
} else {
  // Error creating table
  die("Error creating table: " . $conn->error);
}

// sql to create video_details table if not exists
$sql = "CREATE TABLE IF NOT EXISTS video_details (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id INT(6) UNSIGNED NOT NULL,
  operator VARCHAR(30) NOT NULL,
  description VARCHAR(255) NOT NULL,
  va_nva_enva VARCHAR(30) NOT NULL,
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
  cycle_number INT(6) UNSIGNED NOT NULL,
  improvement TEXT NOT NULL,
  type_of_benefits VARCHAR(255) NOT NULL,
  video_id INT(6) UNSIGNED NOT NULL,
  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
  // Table possible_improvements created successfully or already exists
} else {
  // Error creating table
  die("Error creating table: " . $conn->error);
}

// Check if the column 'operator_name' exists in 'video_details' table and rename it
$result = $conn->query("SHOW COLUMNS FROM video_details LIKE 'operator_name'");
if ($result && $result->num_rows > 0) {
    $conn->query("ALTER TABLE video_details CHANGE operator_name operator VARCHAR(30) NOT NULL");
}

// Check if the column 'activity_type' exists in 'video_details' table and drop it
$result = $conn->query("SHOW COLUMNS FROM video_details LIKE 'activity_type'");
if ($result && $result->num_rows > 0) {
    $conn->query("ALTER TABLE video_details DROP COLUMN activity_type");
}

// Check if video_detail_id column exists in possible_improvements table
$result = $conn->query("SHOW COLUMNS FROM `possible_improvements` LIKE 'video_detail_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `possible_improvements` ADD COLUMN `video_detail_id` INT(6) UNSIGNED AFTER `video_id`");
    $conn->query("ALTER TABLE `possible_improvements` ADD FOREIGN KEY (`video_detail_id`) REFERENCES `video_details`(`id`) ON DELETE SET NULL");
}

// Check if folder_id column exists in videos table
$result = $conn->query("SHOW COLUMNS FROM `videos` LIKE 'folder_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `videos` ADD COLUMN `folder_id` INT(6) UNSIGNED NULL AFTER `name`");
}

// Check if organization_id column exists in folders table
$result = $conn->query("SHOW COLUMNS FROM `folders` LIKE 'organization_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `folders` ADD COLUMN `organization_id` INT(6) UNSIGNED NULL AFTER `name`");
}

// Ensure created_at column exists in videos table
$result = $conn->query("SHOW COLUMNS FROM `videos` LIKE 'created_at'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE `videos` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `file_size`");
}

// Ensure correct foreign key on video_details.video_id -> videos.id
// 1) Find existing foreign keys on video_details
$fkResult = $conn->query("SHOW CREATE TABLE `video_details`");
if ($fkResult && $row = $fkResult->fetch_assoc()) {
    $createSql = $row['Create Table'];
    // If there's an FK that references the wrong table (e.g., folder_id), drop it
    if (strpos($createSql, 'CONSTRAINT') !== false && (strpos($createSql, '`folder_id`') !== false || strpos($createSql, 'REFERENCES `folder_id`') !== false)) {
        // Retrieve all constraints to find their names
        $constraintsRes = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'video_details' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if ($constraintsRes) {
            while ($c = $constraintsRes->fetch_assoc()) {
                // Drop each FK to be safe, will recreate the correct one below
                $conn->query("ALTER TABLE `video_details` DROP FOREIGN KEY `" . $c['CONSTRAINT_NAME'] . "`");
            }
        }
        // Recreate correct FK
        $conn->query("ALTER TABLE `video_details` ADD CONSTRAINT `fk_video_details_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE");
    } else if (strpos($createSql, 'FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`)') === false) {
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
    if (strpos($createSql, 'REFERENCES `folder_id`') !== false) {
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
            $conn->query("ALTER TABLE `possible_improvements` ADD CONSTRAINT `fk_possible_improvements_video_detail_id` FOREIGN KEY (`video_detail_id`) REFERENCES `video_details`(`id`) ON DELETE SET NULL");
        }
    }
}

return $conn;