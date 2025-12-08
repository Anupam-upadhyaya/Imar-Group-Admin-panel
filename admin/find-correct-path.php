<?php
/**
 * Path Finder Tool
 * Save as: admin/find-correct-path.php
 * This will help you find the correct upload path
 */

echo "<h1>🔍 Path Finder Tool</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .code { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
    h2 { color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 5px; }
</style>";

// Show current location
echo "<h2>📍 Current Script Location</h2>";
echo "<div class='info'>";
echo "<strong>This file is at:</strong><br>" . __FILE__ . "<br><br>";
echo "<strong>Current directory:</strong><br>" . __DIR__ . "<br><br>";
echo "<strong>Document root:</strong><br>" . $_SERVER['DOCUMENT_ROOT'];
echo "</div>";

// Test possible paths
echo "<h2>🧪 Testing Possible Paths to Gallery Folder</h2>";

$possible_paths = [
    '../../Imar-Group-Website/Gallery/',
    '../../../Imar-Group-Website/Gallery/',
    '../../Imar_Group_Website/Gallery/',
    '../Imar-Group-Website/Gallery/',
    $_SERVER['DOCUMENT_ROOT'] . '/Imar-Group-Website/Gallery/',
];

$found_path = null;

foreach ($possible_paths as $path) {
    echo "<div style='margin: 15px 0; padding: 15px; background: white; border-radius: 5px;'>";
    echo "<strong>Testing:</strong> <code>" . htmlspecialchars($path) . "</code><br>";
    
    $real_path = realpath($path);
    
    if ($real_path && file_exists($real_path)) {
        echo "<span class='success'>✅ PATH FOUND!</span><br>";
        echo "<strong>Real path:</strong> " . $real_path . "<br>";
        
        // Check if writable
        if (is_writable($real_path)) {
            echo "<span class='success'>✅ Folder is WRITABLE</span><br>";
        } else {
            echo "<span class='error'>❌ Folder is NOT writable</span><br>";
            echo "Fix: Run <code>chmod 777 " . $real_path . "</code><br>";
        }
        
        // List contents
        $files = scandir($real_path);
        echo "<strong>Contents:</strong> " . implode(', ', array_diff($files, ['.', '..'])) . "<br>";
        
        if (!$found_path) {
            $found_path = $path;
        }
    } else {
        echo "<span class='error'>❌ Path does not exist</span><br>";
    }
    echo "</div>";
}

// Show recommendation
if ($found_path) {
    echo "<h2>✨ Recommended Configuration</h2>";
    echo "<div class='code'>";
    echo "// In add-gallery.php and edit-gallery.php, use:<br>";
    echo "\$upload_base = '" . $found_path . "';<br><br>";
    echo "// For fetch-gallery.php, images will be at:<br>";
    echo "// Gallery/CATEGORY/filename.jpg (relative to website root)";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>📋 Next Steps:</strong><br>";
    echo "1. Copy the path above<br>";
    echo "2. Open <code>admin/add-gallery.php</code><br>";
    echo "3. Find line with <code>\$upload_base =</code><br>";
    echo "4. Replace with the path shown above<br>";
    echo "5. Do the same in <code>admin/edit-gallery.php</code><br>";
    echo "6. Test uploading an image";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h2>❌ Gallery Folder Not Found</h2>";
    echo "<p>The Gallery folder doesn't exist. Please create it:</p>";
    echo "<ol>";
    echo "<li>Navigate to your <code>Imar-Group-Website</code> folder</li>";
    echo "<li>Create a <code>Gallery</code> folder</li>";
    echo "<li>Inside Gallery, create: AWARDS, EVENTS, OFFICES, TEAM, ALL folders</li>";
    echo "<li>Run this script again</li>";
    echo "</ol>";
    echo "</div>";
}

// Check database paths
echo "<h2>🗄️ Database Image Paths Check</h2>";
echo "<div class='info'>";
echo "Database paths should be stored as:<br>";
echo "<code>Gallery/AWARDS/filename.jpg</code><br>";
echo "<code>Gallery/EVENTS/filename.jpg</code><br><br>";
echo "<strong>NOT:</strong><br>";
echo "❌ <code>/Gallery/AWARDS/filename.jpg</code> (no leading slash)<br>";
echo "❌ <code>../../Gallery/AWARDS/filename.jpg</code> (no ../ paths)<br>";
echo "❌ <code>C:\\xampp\\htdocs\\...</code> (no absolute paths)";
echo "</div>";

// Check if we can connect to database and show existing paths
try {
    require_once '../config/config.php';
    
    $result = $conn->query("SELECT image_path FROM gallery LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<h2>📊 Current Database Paths</h2>";
        echo "<table border='1' cellpadding='10' style='background: white; border-collapse: collapse;'>";
        echo "<tr><th>Image Path in Database</th><th>Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $db_path = $row['image_path'];
            // Check if file exists (assuming path is relative to website root)
            $check_path = $_SERVER['DOCUMENT_ROOT'] . '/Imar-Group-Website/' . $db_path;
            $exists = file_exists($check_path);
            
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($db_path) . "</code></td>";
            echo "<td>" . ($exists ? "<span class='success'>✅ File exists</span>" : "<span class='error'>❌ File not found</span>") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Could not check database: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><em>Run this script whenever you need to verify paths. Delete it when done for security.</em></p>";
?>