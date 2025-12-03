<?php
/**
 * Debug Check - Place this in admin/ folder
 * File: admin/debug-check.php
 * URL: http://localhost/Imar_Group_Admin_panel/admin/debug-check.php
 */

echo "<h1>IMAR Admin Panel - System Check</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #4f46e5; margin-top: 30px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4f46e5; }
    pre { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

// 1. CHECK PHP VERSION
echo "<h2>1. PHP Version</h2>";
if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
    echo "<div class='info'><span class='success'>✅ PHP Version: " . PHP_VERSION . "</span></div>";
} else {
    echo "<div class='info'><span class='error'>❌ PHP Version too old: " . PHP_VERSION . " (Need 7.0+)</span></div>";
}

// 2. CHECK FILE PATHS
echo "<h2>2. File Structure Check</h2>";

$required_files = [
    '../config/config.php' => 'Config file',
    '../includes/classes/Auth.php' => 'Auth class',
    '../css/Style.css' => 'CSS file (Style.css)',
    'login.php' => 'Login page',
    'dashboard.php' => 'Dashboard page',
    'logout.php' => 'Logout handler'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='info'><span class='success'>✅ {$description}</span> - Found at: {$file}</div>";
    } else {
        echo "<div class='info'><span class='error'>❌ {$description}</span> - NOT FOUND at: {$file}</div>";
    }
}

// 3. CHECK CSS FILES
echo "<h2>3. CSS Files in css/ folder</h2>";
$css_dir = dirname(__DIR__) . '/css';
if (is_dir($css_dir)) {
    echo "<div class='info'><span class='success'>✅ CSS folder exists</span></div>";
    $files = scandir($css_dir);
    echo "<pre>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- " . $file . " (" . filesize($css_dir . '/' . $file) . " bytes)\n";
        }
    }
    echo "</pre>";
} else {
    echo "<div class='info'><span class='error'>❌ CSS folder does not exist!</span></div>";
}

// 4. CHECK DATABASE CONNECTION
echo "<h2>4. Database Connection</h2>";

define('SECURE_ACCESS', true);

if (file_exists('../config/config.php')) {
    try {
        require_once '../config/config.php';
        
        if (isset($conn) && $conn->ping()) {
            echo "<div class='info'><span class='success'>✅ Database connected successfully!</span></div>";
            echo "<div class='info'>Database: " . DB_NAME . "</div>";
            
            // Check tables
            echo "<h3>Database Tables:</h3>";
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                echo "<pre>";
                while ($row = $result->fetch_array()) {
                    echo "- " . $row[0] . "\n";
                }
                echo "</pre>";
            }
            
            // Check admin user
            echo "<h3>Admin User Check:</h3>";
            $result = $conn->query("SELECT id, username, email, role, status FROM admins WHERE email = 'admin@imargroup.com'");
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "<div class='info'><span class='success'>✅ Admin user found!</span></div>";
                echo "<pre>";
                print_r($user);
                echo "</pre>";
                
                // Check password hash
                $pwd_result = $conn->query("SELECT password FROM admins WHERE email = 'admin@imargroup.com'");
                $pwd_row = $pwd_result->fetch_assoc();
                echo "<div class='info'>Password hash length: " . strlen($pwd_row['password']) . " characters</div>";
                echo "<div class='info'>Password starts with: " . substr($pwd_row['password'], 0, 7) . "...</div>";
                
                // Test password verification
                $test_password = 'Admin@123';
                if (password_verify($test_password, $pwd_row['password'])) {
                    echo "<div class='info'><span class='success'>✅ Password verification TEST PASSED!</span></div>";
                    echo "<div class='info'>The password 'Admin@123' matches the stored hash.</div>";
                } else {
                    echo "<div class='info'><span class='error'>❌ Password verification FAILED!</span></div>";
                    echo "<div class='info'>The password 'Admin@123' does NOT match. Hash might be incorrect.</div>";
                    
                    // Generate correct hash
                    $correct_hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 12]);
                    echo "<div class='info'><strong>FIX:</strong> Run this SQL to set correct password:</div>";
                    echo "<pre>UPDATE admins SET password = '{$correct_hash}' WHERE email = 'admin@imargroup.com';</pre>";
                }
                
            } else {
                echo "<div class='info'><span class='error'>❌ Admin user NOT found!</span></div>";
                echo "<div class='info'><strong>FIX:</strong> Run this SQL:</div>";
                $hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12]);
                echo "<pre>";
                echo "INSERT INTO admins (username, email, password, full_name, role, status) \n";
                echo "VALUES ('admin', 'admin@imargroup.com', '{$hash}', 'System Administrator', 'super_admin', 'active');";
                echo "</pre>";
            }
            
        } else {
            echo "<div class='info'><span class='error'>❌ Database connection failed!</span></div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='info'><span class='error'>❌ Error: " . $e->getMessage() . "</span></div>";
    }
} else {
    echo "<div class='info'><span class='error'>❌ config.php not found!</span></div>";
}

// 5. CHECK SESSION
echo "<h2>5. Session Check</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='info'><span class='success'>✅ Sessions are working</span></div>";
    echo "<div class='info'>Session ID: " . session_id() . "</div>";
} else {
    echo "<div class='info'><span class='error'>❌ Sessions are not working!</span></div>";
}

// 6. RECOMMENDATIONS
echo "<h2>6. Quick Fixes</h2>";
echo "<div class='info'>";
echo "<h3>If CSS is not loading:</h3>";
echo "1. Make sure file is named <strong>Style.css</strong> (with capital S)<br>";
echo "2. In login.php, use: <code>&lt;link rel='stylesheet' href='../css/Style.css'&gt;</code><br><br>";

echo "<h3>If login fails with 'Invalid email or password':</h3>";
echo "1. Run the SQL query shown in section 4 above to fix the password hash<br>";
echo "2. Or manually update the password in phpMyAdmin<br><br>";

echo "<h3>Current working directory:</h3>";
echo "<code>" . __DIR__ . "</code><br><br>";

echo "<h3>Parent directory (project root):</h3>";
echo "<code>" . dirname(__DIR__) . "</code>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<p><strong>After fixing issues, try logging in at:</strong><br>";
echo "<a href='login.php'>login.php</a></p>";
?>