<?php
/**
 * IMAR Group Admin Panel - Cancel Self-Deletion Request
 * File: admin/cancel-deletion.php
 * 
 * Allows Super Admins to cancel their pending self-deletion requests
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

// ✅ Only Super Admins can cancel self-deletion
if ($currentUser['role'] !== Permissions::ROLE_SUPER_ADMIN) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

$error = '';
$success = '';

// Check for existing deletion request
$stmt = $conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$existingRequest = $stmt->get_result()->fetch_assoc();

if (!$existingRequest) {
    header('Location: users.php?error=no_deletion_request');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    // Require re-authentication
    if (!$access->requireReAuthentication($password)) {
        $error = 'Incorrect password';
    } else {
        // Cancel the deletion request
        $stmt = $conn->prepare("
            UPDATE user_deletion_requests 
            SET status = 'cancelled', 
                cancellation_reason = ?,
                completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $existingRequest['id']);
        
        if ($stmt->execute()) {
            // Log the action
            $access->logPrivilegedAction(
                $currentUser['id'], 
                'cancelled_self_deletion', 
                'user_deletion_requests', 
                $existingRequest['id'],
                "Cancelled deletion request. Reason: $reason"
            );
            
            $access->clearReAuthentication();
            
            header('Location: users.php?success=deletion_cancelled');
            exit();
        } else {
            $error = 'Failed to cancel deletion request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Account Deletion - IMAR Group Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .cancel-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 550px;
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
        
        .cancel-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        
        .cancel-header h2 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .cancel-header p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .info-box {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-box h3 {
            color: #166534;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            color: #15803d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .scheduled-date {
            background: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            color: #166534;
            margin-top: 10px;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
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
            color: #10b981;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.5);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .help-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }
        
        @media (max-width: 480px) {
            .cancel-container {
                padding: 30px 20px;
            }
            
            .cancel-header h2 {
                font-size: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-header">
            <div class="success-icon">
                <i class="fas fa-undo-alt"></i>
            </div>
            <h2>Cancel Account Deletion</h2>
            <p>You can cancel your account deletion request at any time before it's processed</p>
        </div>
        
        <div class="info-box">
            <h3>
                <i class="fas fa-info-circle"></i>
                Current Deletion Schedule
            </h3>
            <p>Your account is currently scheduled for deletion on:</p>
            <div class="scheduled-date">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('l, F j, Y \a\t g:i A', strtotime($existingRequest['scheduled_deletion_at'])); ?>
            </div>
            <p style="margin-top: 12px; font-size: 13px;">
                Cancelling this request will keep your account active and remove the scheduled deletion.
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Confirm Your Password
                </label>
                <div class="password-input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autofocus 
                           placeholder="Enter your password">
                    <button type="button" 
                            class="toggle-password" 
                            onclick="togglePassword()"
                            title="Show/Hide Password">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason">
                    <i class="fas fa-comment"></i> Reason for Cancellation (Optional)
                </label>
                <textarea id="reason" 
                          name="reason" 
                          placeholder="Why are you cancelling the deletion request? (This helps us improve)"></textarea>
                <div class="help-text">
                    <i class="fas fa-lightbulb"></i> This is optional but helps us understand your needs better
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Cancel Deletion Request
                </button>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </form>
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
        
        // Auto-focus on password input
        document.getElementById('password').focus();
    </script>
</body>
</html>