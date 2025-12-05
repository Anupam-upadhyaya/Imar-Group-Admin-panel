<?php
/**
 * db-import.php
 * Full DB import (replace current DB with backup)
 * Usage: http://localhost/Imar_Group_Admin_panel/database/db-import.php
 * IMPORTANT: Run locally only. This will replace your DB!
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
$latestFile = $backupDir . '/latest.sql';

$imported = false;
$error = '';
$stats = ['success' => 0, 'failed' => 0, 'total' => 0];

function export_current_db_as_backup($conn, $backupDir) {
    // This reuses logic similar to export; simple single-file precautionary backup
    $timestamp = date('Y-m-d_His');
    $file = $backupDir . '/pre-import-' . $timestamp . '.sql';
    // Build a small dump by calling the export logic inline (kept minimal to avoid duplication)
    $sqlOut = [];
    $sqlOut[] = "-- Pre-import backup " . date('Y-m-d H:i:s');
    $sqlOut[] = "SET FOREIGN_KEY_CHECKS = 0;\n";

    $res = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
    if (!$res) {
        return [false, 'Failed listing tables: ' . $conn->error];
    }
    while ($row = $res->fetch_array(MYSQLI_NUM)) {
        $table = $row[0];
        $cr = $conn->query("SHOW CREATE TABLE `" . $conn->real_escape_string($table) . "`");
        if ($cr && ($crRow = $cr->fetch_array(MYSQLI_NUM))) {
            $sqlOut[] = "DROP TABLE IF EXISTS `" . $table . "`;";
            $sqlOut[] = $crRow[1] . ";";
        }
        $dataRes = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "`");
        if ($dataRes && $dataRes->num_rows > 0) {
            $fields = [];
            $fRes = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
            while ($f = $fRes->fetch_assoc()) { $fields[] = $f['Field']; }
            $colList = "`" . implode("`,`", $fields) . "`";
            while ($r = $dataRes->fetch_assoc()) {
                $vals = [];
                foreach ($fields as $c) {
                    $v = $r[$c];
                    $vals[] = ($v === null) ? "NULL" : ("'" . $conn->real_escape_string($v) . "'");
                }
                $sqlOut[] = "INSERT INTO `" . $table . "` (" . $colList . ") VALUES (" . implode(",", $vals) . ");";
            }
        }
    }
    $sqlOut[] = "SET FOREIGN_KEY_CHECKS = 1;\n";
    if (file_put_contents($file, implode("\n", $sqlOut)) === false) {
        return [false, "Failed writing pre-import backup to $file"];
    }
    return [true, $file];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    if (!file_exists($latestFile)) {
        $error = 'No backup file found. Please run the export script first.';
    } else {
        // ensure backups dir exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // create a precautionary pre-import dump
        list($ok, $preBackupInfo) = export_current_db_as_backup($conn, $backupDir);
        if (!$ok) {
            $error = 'Failed to create pre-import backup: ' . $preBackupInfo;
        } else {
            // Read dump
            $sql = file_get_contents($latestFile);
            if ($sql === false) {
                $error = 'Failed to read backup file: ' . $latestFile;
            } else {
                // Execute dump via multi_query
                $conn->query('SET FOREIGN_KEY_CHECKS = 0');

                // multi_query execution
                if ($conn->multi_query($sql)) {
                    $success = 0;
                    $failed = 0;
                    do {
                        // store_result - necessary to flush multi_query results
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                        if ($conn->errno) {
                            $failed++;
                        } else {
                            $success++;
                        }
                        $conn->next_result();
                    } while ($conn->more_results());
                    $stats['success'] = $success;
                    $stats['failed'] = $failed;
                    $stats['total'] = $success + $failed;
                    $imported = true;
                } else {
                    // multi_query failed (likely syntax error or permission)
                    $error = 'Import failed: ' . $conn->error;
                }

                $conn->query('SET FOREIGN_KEY_CHECKS = 1');
            }
        }
    }
}

// UI: show status and import confirmation
$backupExists = file_exists($latestFile);
$backupSize = $backupExists ? filesize($latestFile) : 0;
$backupDate = $backupExists ? date('Y-m-d H:i:s', filemtime($latestFile)) : 'N/A';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>DB Import</title></head>
<body style="font-family:system-ui,Segoe UI,Roboto,Arial; padding:20px;">
    <h1>Database Import</h1>

<?php if ($imported): ?>
    <div style="background:#e6ffed;padding:12px;border:1px solid #9ef0c6;border-radius:6px;">
        <strong>Import Completed</strong><br>
        Success: <?php echo (int)$stats['success']; ?> queries<br>
        Failed: <?php echo (int)$stats['failed']; ?><br>
        Total: <?php echo (int)$stats['total']; ?><br>
        <p>Pre-import backup created: <?php echo htmlspecialchars(basename($preBackupInfo)); ?></p>
    </div>
    <p><a href="../admin/dashboard.php">Go to Dashboard</a></p>

<?php elseif ($error): ?>
    <div style="background:#fff0f0;padding:12px;border:1px solid #f3a6a6;border-radius:6px;">
        <strong>Error:</strong><br>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <p><a href="../admin/dashboard.php">Go to Dashboard</a></p>

<?php else: ?>

    <p>This will replace the entire database with the backup file <strong>latest.sql</strong>.</p>

    <?php if (!$backupExists): ?>
        <div style="background:#fff6e6;padding:12px;border:1px solid #f2d6a6;border-radius:6px;">
            <strong>No backup found.</strong> Please run db-export.php on your local PC and commit/pull the generated file into <code>database/backups/latest.sql</code> first.
        </div>
    <?php else: ?>
        <div style="background:#f0f7ff;padding:12px;border:1px solid #cbe1ff;border-radius:6px;">
            <strong>Backup file:</strong> <?php echo htmlspecialchars(basename($latestFile)); ?><br>
            <strong>Size:</strong> <?php echo number_format($backupSize / 1024, 2); ?> KB<br>
            <strong>Modified:</strong> <?php echo htmlspecialchars($backupDate); ?>
        </div>

        <form method="POST" action="">
            <p style="margin-top:12px;">
                <button type="submit" name="confirm_import" style="padding:10px 16px;background:#d9534f;border:none;color:#fff;border-radius:6px;cursor:pointer;">
                    Confirm Import (Replace Database)
                </button>
                <a href="../admin/dashboard.php" style="margin-left:10px;">Cancel</a>
            </p>
        </form>
    <?php endif; ?>

<?php endif; ?>
</body>
</html>
