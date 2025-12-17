<?php
/**
 * Test Upload Path Configuration
 * Save as: admin/BLOG_CODE/test-upload-path.php
 * Run: http://localhost/Imar_Group_Admin_panel/admin/BLOG_CODE/test-upload-path.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Path Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { border-left: 4px solid #10b981; }
        .error { border-left: 4px solid #ef4444; }
        .warning { border-left: 4px solid #f59e0b; }
        pre { background: #f9f9f9; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .status { font-weight: bold; padding: 4px 8px; border-radius: 4px; }
        .yes { background: #d1fae5; color: #065f46; }
        .no { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h1>🔍 Blog Upload Path Test</h1>
    
    <?php
    // Test different path configurations
    echo "<div class='box'>";
    echo "<h2>📁 Current Paths</h2>";
    
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    echo "<p><strong>Document Root:</strong><br><pre>$document_root</pre></p>";
    
    $current_dir = __DIR__;
    echo "<p><strong>Current Directory (BLOG_CODE):</strong><br><pre>$current_dir</pre></p>";
    
    // Method 1: Using dirname (current method)
    $path1 = dirname(dirname(dirname(__DIR__))) . '/Imar-Group-Website/images/blog/';
    $exists1 = is_dir($path1);
    echo "<p><strong>Method 1 (dirname):</strong><br>";
    echo "<pre>$path1</pre>";
    echo "<span class='status " . ($exists1 ? 'yes' : 'no') . "'>" . ($exists1 ? '✓ EXISTS' : '✗ NOT FOUND') . "</span>";
    echo "</p>";
    
    // Method 2: Using document root (recommended)
    $path2 = $document_root . '/Imar-Group-Website/images/blog/';
    $exists2 = is_dir($path2);
    echo "<p><strong>Method 2 (document_root):</strong><br>";
    echo "<pre>$path2</pre>";
    echo "<span class='status " . ($exists2 ? 'yes' : 'no') . "'>" . ($exists2 ? '✓ EXISTS' : '✗ NOT FOUND') . "</span>";
    echo "</p>";
    
    // Method 3: Check if folder exists
    $path3 = $document_root . '/images/blog/';
    $exists3 = is_dir($path3);
    echo "<p><strong>Method 3 (no Imar-Group-Website prefix):</strong><br>";
    echo "<pre>$path3</pre>";
    echo "<span class='status " . ($exists3 ? 'yes' : 'no') . "'>" . ($exists3 ? '✓ EXISTS' : '✗ NOT FOUND') . "</span>";
    echo "</p>";
    
    echo "</div>";
    
    // Find the correct path
    echo "<div class='box'>";
    echo "<h2>🔎 Searching for images/blog folder...</h2>";
    
    $possible_paths = [
        $document_root . '/Imar-Group-Website/images/blog/',
        $document_root . '/images/blog/',
        dirname(dirname(dirname($document_root))) . '/Imar-Group-Website/images/blog/',
        'C:/xampp/htdocs/Imar-Group-Website/images/blog/',
    ];
    
    $found_path = null;
    foreach ($possible_paths as $path) {
        if (is_dir($path)) {
            echo "<p>✓ Found: <pre>$path</pre></p>";
            $found_path = $path;
            
            // List files in directory
            $files = scandir($path);
            $image_files = array_filter($files, function($file) {
                return !is_dir($file) && preg_match('/\.(jpg|jpeg|png|gif|webp|jfif)$/i', $file);
            });
            
            if (!empty($image_files)) {
                echo "<p><strong>Files found:</strong></p>";
                echo "<ul>";
                foreach ($image_files as $file) {
                    echo "<li>$file</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>⚠️ Directory exists but is empty</p>";
            }
        }
    }
    
    if (!$found_path) {
        echo "<p class='status no'>✗ images/blog folder not found in any expected location</p>";
    }
    
    echo "</div>";
    
    // Test database paths
    define('SECURE_ACCESS', true);
    require_once __DIR__ . '/../../config/config.php';
    
    echo "<div class='box'>";
    echo "<h2>💾 Database Image Paths</h2>";
    
    $result = $conn->query("SELECT id, title, featured_image FROM blog_posts WHERE featured_image IS NOT NULL LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Database Path</th><th>File Exists?</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $db_path = $row['featured_image'];
            
            // Try different base paths
            $full_paths = [
                $document_root . '/Imar-Group-Website/' . $db_path,
                $document_root . '/' . $db_path,
                'C:/xampp/htdocs/Imar-Group-Website/' . $db_path,
                'C:/xampp/htdocs/' . $db_path,
            ];
            
            $file_exists = false;
            $existing_path = '';
            foreach ($full_paths as $full_path) {
                if (file_exists($full_path)) {
                    $file_exists = true;
                    $existing_path = $full_path;
                    break;
                }
            }
            
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td><code>" . htmlspecialchars($db_path) . "</code></td>";
            echo "<td>";
            if ($file_exists) {
                echo "<span class='status yes'>✓ EXISTS</span><br>";
                echo "<small>$existing_path</small>";
            } else {
                echo "<span class='status no'>✗ NOT FOUND</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No blog posts with images found in database</p>";
    }
    
    echo "</div>";
    
    // Solution
    echo "<div class='box success'>";
    echo "<h2>✅ Solution</h2>";
    
    if ($found_path) {
        echo "<p><strong>Your correct upload path is:</strong></p>";
        echo "<pre>\$upload_base_abs = '$found_path';</pre>";
        
        echo "<p><strong>Update these files:</strong></p>";
        echo "<ol>";
        echo "<li><code>admin/BLOG_CODE/add-blog.php</code> (around line 30)</li>";
        echo "<li><code>admin/BLOG_CODE/edit-blog.php</code> (around line 30)</li>";
        echo "</ol>";
        
        echo "<p><strong>Change to:</strong></p>";
        echo "<pre>";
        echo htmlspecialchars('$document_root = $_SERVER[\'DOCUMENT_ROOT\'];' . "\n");
        echo htmlspecialchars('$upload_base_abs = $document_root . \'/Imar-Group-Website/images/blog/\';' . "\n");
        echo htmlspecialchars('$upload_base_url = \'images/blog/\';');
        echo "</pre>";
    } else {
        echo "<p class='status no'>Could not find images/blog directory. Please create it:</p>";
        echo "<pre>mkdir C:\\xampp\\htdocs\\Imar-Group-Website\\images\\blog</pre>";
        echo "<p>Or create it manually in Windows Explorer</p>";
    }
    
    echo "</div>";
    
    // Permissions check
    if ($found_path && is_dir($found_path)) {
        echo "<div class='box'>";
        echo "<h2>🔐 Permissions Check</h2>";
        
        $writable = is_writable($found_path);
        echo "<p>Directory writable: ";
        echo "<span class='status " . ($writable ? 'yes' : 'no') . "'>" . ($writable ? '✓ YES' : '✗ NO') . "</span>";
        echo "</p>";
        
        if (!$writable) {
            echo "<p class='status no'>⚠️ Directory is not writable. Cannot upload files.</p>";
            echo "<p><strong>Fix:</strong> Right-click folder → Properties → Security → Give write permissions</p>";
        }
        
        echo "</div>";
    }
    ?>
    
</body>
</html>