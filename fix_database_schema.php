<?php
// This script will check and fix the database schema for the videos table
require_once 'db.php';

echo "Checking and fixing database schema...\n";

try {
    // Check current table structure
    $result = $conn->query("DESCRIBE videos");
    if ($result) {
        echo "Current videos table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
    }
    
    // Check if video_path column exists and its properties
    $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'video_path'");
    if ($result && $result->num_rows > 0) {
        $column = $result->fetch_assoc();
        echo "\nvideo_path column details:\n";
        echo "- Type: {$column['Type']}\n";
        echo "- Null: {$column['Null']}\n";
        echo "- Default: " . ($column['Default'] ?? 'NULL') . "\n";
        
        // If video_path is NOT NULL and has no default, fix it
        if ($column['Null'] === 'NO' && $column['Default'] === null) {
            echo "\nFixing video_path column to allow NULL values...\n";
            $conn->query("ALTER TABLE videos MODIFY COLUMN video_path VARCHAR(500) NULL");
            echo "Fixed! video_path column now allows NULL values.\n";
        } else {
            echo "video_path column is already properly configured.\n";
        }
    } else {
        echo "video_path column does not exist. Creating it...\n";
        $conn->query("ALTER TABLE videos ADD COLUMN video_path VARCHAR(500) NULL");
        echo "Created video_path column.\n";
    }
    
    // Verify the fix
    $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'video_path'");
    if ($result && $result->num_rows > 0) {
        $column = $result->fetch_assoc();
        echo "\nAfter fix - video_path column details:\n";
        echo "- Type: {$column['Type']}\n";
        echo "- Null: {$column['Null']}\n";
        echo "- Default: " . ($column['Default'] ?? 'NULL') . "\n";
    }
    
    echo "\nDatabase schema fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
