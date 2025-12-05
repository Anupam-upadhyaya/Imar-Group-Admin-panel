<?php
/**
 * Database Export Script
 * File: database/db-export.php
 * 
 * Usage: Run this before pushing to Git
 * URL: http://localhost/Imar_Group_Admin_panel/database/db-export.php
 */

// Security: Only allow local access
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This script can only be run locally.');
}

define('SECURE_ACCESS', true);
require_once '../config/config.php';

// Create backups directory if it doesn't exist
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Backup filename with timestamp
$timestamp = date('Y-m-d_His');
$backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
$latestFile = $backupDir . '/latest.sql';

// Get all tables
$tables = [];
$result = $conn->query('SHOW TABLES');
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Start SQL content
$sqlContent = "-- =====================================================\n";
$sqlContent .= "-- IMAR Admin Database Backup\n";
$sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sqlContent .= "-- Database: " . DB_NAME . "\n";
$sqlContent .= "-- =====================================================\n\n";

$sqlContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sqlContent .= "SET time_zone = \"+00:00\";\n\n";

$sqlContent .= "-- Database: `" . DB_NAME . "`\n";
$sqlContent .= "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sqlContent .= "USE `" . DB_NAME . "`;\n\n";

// Export each table
foreach ($tables as $table) {
    // Get CREATE TABLE statement
    $result = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $result->fetch_array();
    
    $sqlContent .= "-- =====================================================\n";
    $sqlContent .= "-- Table structure for table `$table`\n";
    $sqlContent .= "-- =====================================================\n\n";
    
    $sqlContent .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlContent .= $row[1] . ";\n\n";
    
    // Get table data
    $result = $conn->query("SELECT * FROM `$table`");
    $numRows = $result->num_rows;
    
    if ($numRows > 0) {
        $sqlContent .= "-- Dumping data for table `$table`\n\n";
        
        // Get column names
        $columns = [];
        $fields = $conn->query("SHOW COLUMNS FROM `$table`");
        while ($field = $fields->fetch_array()) {
            $columns[] = $field[0];
        }
        
        // Insert data
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $sqlContent .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (";
            
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            
            $sqlContent .= implode(', ', $values) . ");\n";
        }
        
        $sqlContent .= "\n";
    }
}

// Save to timestamped file
file_put_contents($backupFile, $sqlContent);

// Save to latest.sql (for easy sync)
file_put_contents($latestFile, $sqlContent);

// Also create a .gitignore to exclude timestamped backups
$gitignore = $backupDir . '/.gitignore';
if (!file_exists($gitignore)) {
    file_put_contents($gitignore, "backup_*.sql\n");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Export - Success</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #10b981;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success-icon {
            width: 40px;
            height: 40px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
            font-size: 24px;
        }
        .info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #4f46e5;
        }
        .info strong {
            display: block;
            margin-bottom: 5px;
            color: #374151;
        }
        .info code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        .next-steps {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f3f4f6;
        }
        .next-steps h3 {
            margin: 0 0 15px 0;
            color: #374151;
        }
        .next-steps ol {
            padding-left: 20px;
        }
        .next-steps li {
            margin: 8px 0;
            color: #6b7280;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="success-icon">✓</span>
            Database Exported Successfully!
        </h1>
        
        <p>Your database has been backed up and is ready to sync.</p>
        
        <div class="info">
            <strong>Backup Files Created:</strong>
            <code><?php echo basename($backupFile); ?></code><br>
            <code>latest.sql</code> (for Git sync)
        </div>
        
        <div class="info">
            <strong>Tables Exported:</strong>
            <?php echo count($tables); ?> tables (<?php 
                echo implode(', ', array_map(function($t) { 
                    return '<code>' . $t . '</code>'; 
                }, $tables)); 
            ?>)
        </div>
        
        <div class="info">
            <strong>File Size:</strong>
            <?php echo number_format(filesize($latestFile) / 1024, 2); ?> KB
        </div>
        
        <div class="next-steps">
            <h3>📝 Next Steps:</h3>
            <ol>
                <li>Commit the <code>database/backups/latest.sql</code> file to Git</li>
                <li>Push to your repository (GitHub/GitLab)</li>
                <li>Pull on your other computer</li>
                <li>Run the import script</li>
            </ol>
        </div>
        
        <div class="btn-group">
            <a href="../admin/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="db-export.php" class="btn btn-secondary">Export Again</a>
        </div>
    </div>
</body>
</html>