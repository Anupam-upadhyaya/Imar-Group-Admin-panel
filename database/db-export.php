<?php
/**
 * db-export.php
 * Full DB export: structure + data
 * Usage: http://localhost/Imar_Group_Admin_panel/database/db-export.php
 * IMPORTANT: Run locally only.
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

// Local-only guard
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    die('Access denied. This script can only be run locally.');
}

define('SECURE_ACCESS', true);
require_once '../config/config.php'; // must provide $conn and DB_NAME

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection ($conn) not found. Check ../config/config.php');
}

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        die('Failed to create backups directory: ' . $backupDir);
    }
}

$timestamp = date('Y-m-d_His');
$backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
$latestFile = $backupDir . '/latest.sql';

$sqlOut = [];
$sqlOut[] = "-- =====================================================";
$sqlOut[] = "-- IMAR Admin Database Backup";
$sqlOut[] = "-- Generated: " . date('Y-m-d H:i:s');
$sqlOut[] = "-- Database: " . (defined('DB_NAME') ? DB_NAME : '(unknown)');
$sqlOut[] = "-- =====================================================\n";
$sqlOut[] = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";";
$sqlOut[] = "SET time_zone = \"+00:00\";\n";
$sqlOut[] = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;";
$sqlOut[] = "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;";
$sqlOut[] = "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;";
$sqlOut[] = "/*!40101 SET NAMES utf8mb4 */;\n";

$dbName = defined('DB_NAME') ? DB_NAME : $conn->query('SELECT DATABASE()')->fetch_row()[0];
$sqlOut[] = "-- Database: `" . $dbName . "`";
$sqlOut[] = "CREATE DATABASE IF NOT EXISTS `" . $dbName . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$sqlOut[] = "USE `" . $dbName . "`;\n";

// Get tables
$tables = [];
$res = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
if (!$res) {
    die('Failed to list tables: ' . $conn->error);
}
while ($row = $res->fetch_array(MYSQLI_NUM)) {
    $tables[] = $row[0];
}

// Export each table: DROP, CREATE, DATA
foreach ($tables as $table) {
    // CREATE TABLE
    $row = $conn->query("SHOW CREATE TABLE `" . $conn->real_escape_string($table) . "`");
    if (!$row) {
        die("SHOW CREATE TABLE failed for {$table}: " . $conn->error);
    }
    $rowArr = $row->fetch_array(MYSQLI_NUM);
    $createSQL = $rowArr[1];

    $sqlOut[] = "-- -----------------------------------------------------";
    $sqlOut[] = "-- Table structure for table `$table`";
    $sqlOut[] = "-- -----------------------------------------------------\n";

    $sqlOut[] = "DROP TABLE IF EXISTS `" . $table . "`;";
    $sqlOut[] = $createSQL . ";\n";

    // DATA
    $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "`");
    if (!$result) {
        die("SELECT failed for {$table}: " . $conn->error);
    }

    $numRows = $result->num_rows;
    if ($numRows > 0) {
        $cols = [];
        $fieldsRes = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
        while ($field = $fieldsRes->fetch_assoc()) {
            $cols[] = $field['Field'];
        }
        $colList = "`" . implode("`, `", $cols) . "`";

        $sqlOut[] = "-- Dumping data for table `$table`";
        // fetch rows and write single-row INSERTs (safe) — could be batched
        while ($rowData = $result->fetch_assoc()) {
            $values = [];
            foreach ($cols as $col) {
                $v = $rowData[$col];
                if ($v === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . $conn->real_escape_string($v) . "'";
                }
            }
            $sqlOut[] = "INSERT INTO `" . $table . "` (" . $colList . ") VALUES (" . implode(", ", $values) . ");";
        }
        $sqlOut[] = ""; // blank line
    }
}

// Routines/triggers/views (optional) — attempt to export triggers
$triggersRes = $conn->query("SHOW TRIGGERS");
if ($triggersRes && $triggersRes->num_rows > 0) {
    $sqlOut[] = "-- -----------------------------------------------------";
    $sqlOut[] = "-- Triggers";
    $sqlOut[] = "-- -----------------------------------------------------\n";
    while ($tr = $triggersRes->fetch_assoc()) {
        // MySQL SHOW TRIGGERS output is limited; best to fetch trigger body via INFORMATION_SCHEMA.ROUTINES or SHOW CREATE TRIGGER (if available)
        $tname = $tr['Trigger'];
        $show = $conn->query("SHOW CREATE TRIGGER `" . $conn->real_escape_string($tname) . "`");
        if ($show && $showRow = $show->fetch_array(MYSQLI_NUM)) {
            $sqlOut[] = $showRow[2] . ";";
        }
    }
    $sqlOut[] = "";
}

// Footer
$sqlOut[] = "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;";
$sqlOut[] = "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;";
$sqlOut[] = "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";

// Write to files
$finalSql = implode("\n", $sqlOut) . "\n";

if (file_put_contents($backupFile, $finalSql) === false) {
    die("Failed to write backup file: {$backupFile}");
}
if (file_put_contents($latestFile, $finalSql) === false) {
    die("Failed to write latest file: {$latestFile}");
}

// Minimal HTML output for convenience
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>DB Export</title></head>
<body style="font-family:system-ui,Segoe UI,Roboto,Arial;">
    <h1>Database Exported</h1>
    <p>Backup file: <strong><?php echo htmlspecialchars(basename($backupFile)); ?></strong></p>
    <p>Latest file: <strong>latest.sql</strong></p>
    <p>Tables exported: <?php echo count($tables); ?></p>
    <p><a href="../admin/dashboard.php">Back to Dashboard</a> | <a href="backups/<?php echo rawurlencode(basename($latestFile)); ?>">Download latest.sql</a></p>
</body>
</html>
