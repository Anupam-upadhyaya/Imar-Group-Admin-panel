<?php
session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/AccessControl.php';

$auth = new Auth($conn);
$access = new AccessControl($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUser = $auth->getCurrentUser();

// ✅ Only Super Admins can request self-deletion
if ($currentUser['role'] !== Permissions::ROLE_SUPER_ADMIN) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Require re-authentication
    if (!$access->requireReAuthentication($password)) {
        $error = 'Incorrect password';
    } else {
        // Check business rules
        $result = $access->handleSuperAdminSelfDeletion($currentUser['id']);
        
        if ($result['status'] === 'allowed') {
            // Create deletion request with 5-day cooling period
            $scheduledDate = date('Y-m-d H:i:s', strtotime('+5 days'));
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt = $conn->prepare("INSERT INTO user_deletion_requests (user_id, scheduled_deletion_at, ip_address) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $currentUser['id'], $scheduledDate, $ip);
            $stmt->execute();
            
            $success = "Deletion request submitted. Your account will be deleted on $scheduledDate. You can cancel this request anytime before then.";
            
            // Log the action
            $access->logPrivilegedAction($currentUser['id'], 'requested_self_deletion', 'user_deletion_requests', $stmt->insert_id);
        } elseif ($result['status'] === 'existing') {
            $info = "You already have a pending deletion request.";
        }
    }
}

// Check for existing requests
$stmt = $conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$existingRequest = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Deletion Request</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="deletion-container">
        <h2>⚠️ Request Account Deletion</h2>
        
        <?php if ($existingRequest): ?>
            <div class="alert alert-warning">
                <strong>Pending Deletion Request</strong>
                <p>Your account is scheduled for deletion on <?php echo $existingRequest['scheduled_deletion_at']; ?></p>
                <form method="POST" action="cancel-deletion.php">
                    <button type="submit" class="btn btn-primary">Cancel Deletion</button>
                </form>
            </div>
        <?php else: ?>
            <div class="warning-box">
                <h3>⏰ 5-Day Cooling Period</h3>
                <p>Your account will be scheduled for deletion 5 days from now. You can cancel this request at any time during the waiting period.</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Confirm Your Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-danger">Request Deletion</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>