<?php
/**
 * Database Import Script
 * File: database/db-import.php
 * 
 * Usage: Run this after pulling from Git
 * URL: http://localhost/Imar_Group_Admin_panel/database/db-import.php
 */

// Security: Only allow local access
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This script can only be run locally.');
}

define('SECURE_ACCESS', true);
require_once '../config/config.php';

$imported = false;
$error = '';
$stats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $sqlFile = __DIR__ . '/backups/latest.sql';
    
    if (!file_exists($sqlFile)) {
        $error = 'No backup file found. Please export database first.';
    } else {
        try {
            // Read SQL file
            $sql = file_get_contents($sqlFile);
            
            // Split into individual queries
            $queries = array_filter(array_map('trim', explode(";\n", $sql)));
            
            $successCount = 0;
            $failCount = 0;
            
            // Disable foreign key checks temporarily
            $conn->query('SET FOREIGN_KEY_CHECKS = 0');
            
            // Execute each query
            foreach ($queries as $query) {
                // Skip comments and empty lines
                if (empty($query) || substr($query, 0, 2) === '--' || substr($query, 0, 2) === '/*') {
                    continue;
                }
                
                if ($conn->query($query)) {
                    $successCount++;
                } else {
                    $failCount++;
                    // Don't stop on error, continue importing
                }
            }
            
            // Re-enable foreign key checks
            $conn->query('SET FOREIGN_KEY_CHECKS = 1');
            
            $imported = true;
            $stats = [
                'success' => $successCount,
                'failed' => $failCount,
                'total' => $successCount + $failCount
            ];
            
        } catch (Exception $e) {
            $error = 'Import failed: ' . $e->getMessage();
        }
    }
}

// Check if backup file exists
$backupExists = file_exists(__DIR__ . '/backups/latest.sql');
$backupSize = $backupExists ? filesize(__DIR__ . '/backups/latest.sql') : 0;
$backupDate = $backupExists ? date('Y-m-d H:i:s', filemtime(__DIR__ . '/backups/latest.sql')) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Import</title>
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
            color: #0f172a;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icon {
            width: 40px;
            height: 40px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-size: 24px;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ef4444;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #f59e0b;
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
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #4f46e5;
        }
        .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($imported): ?>
            <!-- Success State -->
            <h1>
                <span class="icon" style="background: #d1fae5; color: #10b981;">✓</span>
                Import Completed!
            </h1>
            
            <div class="success-message">
                <strong>Database imported successfully!</strong><br>
                Your database has been synchronized with the latest backup.
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value" style="color: #10b981;"><?php echo $stats['success']; ?></div>
                    <div class="stat-label">Successful</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #ef4444;"><?php echo $stats['failed']; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Queries</div>
                </div>
            </div>
            
            <div class="btn-group">
                <a href="../admin/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="db-import.php" class="btn btn-secondary">Import Again</a>
            </div>
            
        <?php elseif ($error): ?>
            <!-- Error State -->
            <h1>
                <span class="icon" style="background: #fee2e2; color: #ef4444;">✕</span>
                Import Failed
            </h1>
            
            <div class="error-message">
                <strong>Error:</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
            
            <div class="btn-group">
                <a href="db-import.php" class="btn btn-secondary">Try Again</a>
                <a href="../admin/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
            </div>
            
        <?php else: ?>
            <!-- Import Confirmation -->
            <h1>
                <span class="icon">↓</span>
                Import Database
            </h1>
            
            <?php if (!$backupExists): ?>
                <div class="error-message">
                    <strong>No backup file found!</strong><br>
                    Please run the export script on your other computer first, then pull from Git.
                </div>
                
                <div class="info">
                    <strong>Expected file location:</strong>
                    <code>database/backups/latest.sql</code>
                </div>
                
                <div class="btn-group">
                    <a href="../admin/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                </div>
                
            <?php else: ?>
                <p>You are about to import the database from the backup file. This will:</p>
                
                <div class="warning">
                    <strong>⚠️ Warning:</strong><br>
                    This will <strong>replace all current data</strong> in your database with the backup data. This action cannot be undone!
                </div>
                
                <div class="info">
                    <strong>Backup File Info:</strong>
                    <div style="margin-top: 10px;">
                        <strong>File:</strong> <code>latest.sql</code><br>
                        <strong>Size:</strong> <?php echo number_format($backupSize / 1024, 2); ?> KB<br>
                        <strong>Last Modified:</strong> <?php echo $backupDate; ?>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="btn-group">
                        <button type="submit" name="confirm_import" class="btn btn-danger">
                            Confirm Import
                        </button>
                        <a href="../admin/dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>