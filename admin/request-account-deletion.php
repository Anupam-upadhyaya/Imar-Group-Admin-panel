<?php
/**
 * IMAR Group Admin Panel - Self-Deletion Request
 * File: admin/request-self-deletion.php
 * 
 * Allows Super Admins to request deletion of their own account
 * Implements 5-day cooling period with cancellation option
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/AccessControl.php';
require_once __DIR__ . '/../includes/classes/Permissions.php';

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

$error = '';
$success = '';
$info = '';

// Check for existing deletion request
$stmt = $conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$existingRequest = $stmt->get_result()->fetch_assoc();

if ($existingRequest) {
    $info = "You already have a pending deletion request scheduled for " . 
            date('M d, Y g:i A', strtotime($existingRequest['scheduled_deletion_at']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingRequest) {
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    
    // Validate confirmation text
    if ($confirmation !== 'DELETE MY ACCOUNT') {
        $error = 'Please type "DELETE MY ACCOUNT" to confirm';
    }
    // Require re-authentication
    elseif (!$access->requireReAuthentication($password)) {
        $error = 'Incorrect password';
    } else {
        // Check if this is the last Super Admin
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role = ? AND status = 'active' AND id != ?");
        $role = Permissions::ROLE_SUPER_ADMIN;
        $stmt->bind_param("si", $role, $currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] < 1) {
            $error = "Cannot delete the last Super Admin account. Please create another Super Admin first.";
        } else {
            // Create deletion request with 5-day cooling period
            $scheduledDate = date('Y-m-d H:i:s', strtotime('+5 days'));
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt = $conn->prepare("INSERT INTO user_deletion_requests (user_id, scheduled_deletion_at, ip_address) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $currentUser['id'], $scheduledDate, $ip);
            
            if ($stmt->execute()) {
                // Log the action
                $access->logPrivilegedAction(
                    $currentUser['id'], 
                    'requested_self_deletion', 
                    'user_deletion_requests', 
                    $stmt->insert_id,
                    "Scheduled for: $scheduledDate"
                );
                
                $access->clearReAuthentication();
                
                header('Location: users.php?success=deletion_requested');
                exit();
            } else {
                $error = "Failed to create deletion request. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Account Deletion - IMAR Group Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .deletion-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.4s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .deletion-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .warning-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .deletion-header h2 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .deletion-header p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .warning-box {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .warning-box h3 {
            color: #991b1b;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-box ul {
            list-style: none;
            padding: 0;
        }
        
        .warning-box li {
            color: #dc2626;
            font-size: 14px;
            line-height: 1.8;
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        
        .warning-box li:before {
            content: "⚠️";
            position: absolute;
            left: 0;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-box h3 {
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .cooling-period {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 12px;
            border-left: 4px solid #3b82f6;
        }
        
        .cooling-period strong {
            color: #1e40af;
            font-size: 18px;
            display: block;
            margin-bottom: 5px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .alert i {
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            transition: color 0.2s;
        }
        
        .toggle-password:hover {
            color: #ef4444;
        }
        
        .confirmation-input {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .help-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }
        
        .help-text code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #dc2626;
            font-weight: 600;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.5);
        }
        
        .btn-danger:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-danger:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        @media (max-width: 480px) {
            .deletion-container {
                padding: 30px 20px;
            }
            
            .deletion-header h2 {
                font-size: 22px;
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="deletion-container">
        <div class="deletion-header">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Request Account Deletion</h2>
            <p>This action will schedule your account for permanent deletion</p>
        </div>
        
        <?php if ($existingRequest): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span><?php echo htmlspecialchars($info); ?></span>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="cancel-deletion.php" class="btn btn-secondary">
                    <i class="fas fa-undo-alt"></i> Cancel Deletion Request
                </a>
                <a href="users.php" class="btn btn-secondary" style="margin-left: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
        <?php else: ?>
            <div class="warning-box">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Important Warning
                </h3>
                <ul>
                    <li>This action will permanently delete your account</li>
                    <li>All your data and content will be removed</li>
                    <li>You will lose access to the admin panel</li>
                    <li>This action cannot be undone after the cooling period</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3>
                    <i class="fas fa-clock"></i>
                    5-Day Cooling Period
                </h3>
                <p>Your account will not be deleted immediately. There is a 5-day cooling period during which you can cancel the deletion request.</p>
                <div class="cooling-period">
                    <strong>🗓️ Scheduled Deletion:</strong>
                    <?php echo date('l, F j, Y \a\t g:i A', strtotime('+5 days')); ?>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="deletionForm">
                <div class="form-group">
                    <label for="confirmation">
                        <i class="fas fa-keyboard"></i> Type Confirmation
                    </label>
                    <input type="text" 
                           id="confirmation" 
                           name="confirmation" 
                           class="confirmation-input"
                           required 
                           placeholder="DELETE MY ACCOUNT"
                           autocomplete="off">
                    <div class="help-text">
                        Please type <code>DELETE MY ACCOUNT</code> to confirm
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Confirm Your Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Enter your password">
                        <button type="button" 
                                class="toggle-password" 
                                onclick="togglePassword()"
                                title="Show/Hide Password">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                        <i class="fas fa-user-times"></i> Request Deletion
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Enable submit button only when confirmation is correct
        const confirmationInput = document.getElementById('confirmation');
        const submitBtn = document.getElementById('submitBtn');
        
        if (confirmationInput) {
            confirmationInput.addEventListener('input', function() {
                if (this.value.trim().toUpperCase() === 'DELETE MY ACCOUNT') {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                }
            });
            
            // Double confirmation on submit
            document.getElementById('deletionForm').addEventListener('submit', function(e) {
                if (!confirm('Are you absolutely sure you want to delete your account? This will be scheduled for 5 days from now.')) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>