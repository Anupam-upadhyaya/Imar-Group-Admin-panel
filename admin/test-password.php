<?php
/**
 * Password Test - Place in admin/ folder
 * File: admin/test-password.php
 * URL: http://localhost/Imar_Group_Admin_panel/admin/test-password.php
 */

echo "<h1>Password Verification Test</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4f46e5; }
    pre { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; }
</style>";

// Test the hash directly
$stored_hash = '$2y$12$LQv3c1yycED9XO4wW5/wN.qPcYhEPJYHlJKfIqMYBz7WE8oCqjsG6';
$test_password = 'Admin@123';

echo "<h2>Direct Hash Test</h2>";
echo "<div class='info'>Testing password: <strong>{$test_password}</strong></div>";
echo "<div class='info'>Against hash: <code>{$stored_hash}</code></div>";

if (password_verify($test_password, $stored_hash)) {
    echo "<div class='info'><span class='success'>✅ SUCCESS! Password matches hash!</span></div>";
} else {
    echo "<div class='info'><span class='error'>❌ FAILED! Password does NOT match hash!</span></div>";
}

// Now test with database
echo "<h2>Database Test</h2>";

define('SECURE_ACCESS', true);
require_once '../config/config.php';

if ($conn->ping()) {
    echo "<div class='info'><span class='success'>✅ Database connected</span></div>";
    
    // Get the actual stored hash from database
    $result = $conn->query("SELECT email, password, status FROM admins WHERE email = 'admin@imargroup.com'");
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        echo "<div class='info'><span class='success'>✅ User found in database</span></div>";
        echo "<div class='info'>Email: {$user['email']}</div>";
        echo "<div class='info'>Status: {$user['status']}</div>";
        echo "<div class='info'>Hash in DB: <code>{$user['password']}</code></div>";
        echo "<div class='info'>Hash length: " . strlen($user['password']) . " characters</div>";
        
        // Compare hashes
        if ($user['password'] === $stored_hash) {
            echo "<div class='info'><span class='success'>✅ Hash in DB matches expected hash</span></div>";
        } else {
            echo "<div class='info'><span class='error'>❌ Hash in DB is DIFFERENT from expected hash!</span></div>";
        }
        
        // Test password
        if (password_verify($test_password, $user['password'])) {
            echo "<div class='info'><span class='success'>✅ Password '{$test_password}' VERIFIED against DB hash!</span></div>";
        } else {
            echo "<div class='info'><span class='error'>❌ Password '{$test_password}' FAILED verification!</span></div>";
            
            // Generate new hash
            echo "<h3>Solution: Update Database</h3>";
            $new_hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 12]);
            echo "<div class='info'>Run this SQL in phpMyAdmin:</div>";
            echo "<pre>UPDATE admins SET password = '{$new_hash}' WHERE email = 'admin@imargroup.com';</pre>";
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            echo "<div class='info'><span class='error'>⚠️ WARNING: User status is '{$user['status']}' (should be 'active')</span></div>";
            echo "<div class='info'>Run this SQL:</div>";
            echo "<pre>UPDATE admins SET status = 'active' WHERE email = 'admin@imargroup.com';</pre>";
        }
        
    } else {
        echo "<div class='info'><span class='error'>❌ User NOT found in database!</span></div>";
        
        // Provide insert query
        $hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 12]);
        echo "<div class='info'>Run this SQL to create admin user:</div>";
        echo "<pre>";
        echo "INSERT INTO admins (username, email, password, full_name, role, status)\n";
        echo "VALUES (\n";
        echo "    'admin',\n";
        echo "    'admin@imargroup.com',\n";
        echo "    '{$hash}',\n";
        echo "    'System Administrator',\n";
        echo "    'super_admin',\n";
        echo "    'active'\n";
        echo ");";
        echo "</pre>";
    }
    
} else {
    echo "<div class='info'><span class='error'>❌ Cannot connect to database</span></div>";
}

// Test login simulation
echo "<h2>Login Simulation</h2>";

$test_email = 'admin@imargroup.com';
$test_pass = 'Admin@123';

echo "<div class='info'>Simulating login with:</div>";
echo "<div class='info'>Email: <strong>{$test_email}</strong></div>";
echo "<div class='info'>Password: <strong>{$test_pass}</strong></div>";

// Simulate the exact login process
$stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='info'><span class='error'>❌ Query returned no results</span></div>";
} else {
    $user = $result->fetch_assoc();
    
    echo "<div class='info'><span class='success'>✅ Query found user</span></div>";
    
    // Check status
    if ($user['status'] !== 'active') {
        echo "<div class='info'><span class='error'>❌ Status check failed: {$user['status']}</span></div>";
    } else {
        echo "<div class='info'><span class='success'>✅ Status is active</span></div>";
    }
    
    // Verify password
    if (password_verify($test_pass, $user['password'])) {
        echo "<div class='info'><span class='success'>✅✅✅ LOGIN SHOULD WORK! Password verified successfully!</span></div>";
        echo "<div class='info'>If login still fails, check:</div>";
        echo "<div class='info'>1. Session is working (session_start() called)</div>";
        echo "<div class='info'>2. No output before header() redirect</div>";
        echo "<div class='info'>3. Auth.php file is correct</div>";
    } else {
        echo "<div class='info'><span class='error'>❌ Password verification failed!</span></div>";
        echo "<div class='info'>This is why login is failing!</div>";
    }
}

echo "<hr><p><a href='login.php'>← Back to Login</a></p>";
?>