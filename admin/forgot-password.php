<?php
/**
 * FILE 1: admin/forgot-password.php
 * Handles password reset requests
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .forgot-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section h1 {
            font-size: 28px;
            color: #0f172a;
            margin: 15px 0 5px 0;
        }
        
        .logo-section p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn-reset {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .info-box {
            background: #e0e7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4338ca;
        }
    </style>
</head>
<body>

<div class="forgot-container">
    <div class="logo-section">
        <i class="fas fa-lock" style="font-size: 48px; color: #667eea;"></i>
        <h1>Forgot Password?</h1>
        <p>Enter your email to reset your password</p>
    </div>

    <div class="info-box">
        <i class="fas fa-info-circle"></i> We'll send you instructions to reset your password.
    </div>

    <div id="messageArea"></div>

    <form id="forgotPasswordForm">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="your.email@example.com">
        </div>

        <button type="submit" class="btn-reset">
            <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
    </form>

    <div class="back-to-login">
        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</div>

<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const messageArea = document.getElementById('messageArea');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    try {
        const response = await fetch('ajax/forgot-password-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageArea.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> ${data.message}
                </div>
            `;
            this.reset();
        } else {
            messageArea.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> ${data.message}
                </div>
            `;
        }
    } catch (error) {
        messageArea.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.
            </div>
        `;
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reset Link';
    }
});
</script>

</body>
</html>

<?php
/**
 * FILE 2: admin/ajax/forgot-password-handler.php
 * Backend handler for password reset requests
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address']);
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, name, email FROM admin_users WHERE email = ? AND status = 'active'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Don't reveal if email exists or not (security)
    echo json_encode([
        'success' => true, 
        'message' => 'If this email exists, you will receive a password reset link shortly.'
    ]);
    exit();
}

$user = $result->fetch_assoc();

// Generate reset token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Save reset request
$stmt = $conn->prepare("INSERT INTO password_reset_requests (user_id, token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user['id'], $token, $ip_address, $user_agent, $expires_at);
$stmt->execute();

// Update user record
$stmt = $conn->prepare("UPDATE admin_users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
$stmt->bind_param("ssi", $token, $expires_at, $user['id']);
$stmt->execute();

// Create reset link
$reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Imar_Group_Admin_panel/admin/reset-password.php?token=" . $token;

// Send email
$to = $user['email'];
$subject = "Password Reset Request - IMAR Group Admin";
$message = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Password Reset Request</h1>
        </div>
        <div class='content'>
            <p>Hello {$user['name']},</p>
            <p>We received a request to reset your password for your IMAR Group Admin account.</p>
            <p>Click the button below to reset your password:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$reset_link}' class='button'>Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; background: white; padding: 10px; border-radius: 4px;'>{$reset_link}</p>
            <p><strong>This link will expire in 1 hour.</strong></p>
            <p>If you didn't request this, please ignore this email. Your password will remain unchanged.</p>
            <p>Request details:<br>
            IP Address: {$ip_address}<br>
            Time: " . date('M d, Y h:i A') . "</p>
        </div>
        <div class='footer'>
            <p>&copy; " . date('Y') . " IMAR Group. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: IMAR Group Admin <noreply@imargroup.com>" . "\r\n";

// Send email (you may need to configure SMTP or use PHPMailer)
$email_sent = mail($to, $subject, $message, $headers);

// For development/testing, you can log the reset link instead
error_log("Password reset link for {$email}: {$reset_link}");

echo json_encode([
    'success' => true,
    'message' => 'If this email exists, you will receive a password reset link shortly.',
    // 'debug_link' => $reset_link // Remove this in production!
]);
?>

<?php
/**
 * FILE 3: admin/reset-password.php
 * Password reset form
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';

$token = $_GET['token'] ?? '';
$error_message = '';
$success_message = '';
$token_valid = false;
$user = null;

// Verify token
if (empty($token)) {
    $error_message = "Invalid reset link.";
} else {
    $stmt = $conn->prepare("SELECT u.* FROM admin_users u WHERE u.reset_token = ? AND u.reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token_valid = true;
    } else {
        $error_message = "This reset link has expired or is invalid.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $error_message = "Password is required.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'used', used_at = NOW() WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $success_message = "Password reset successful! You can now login with your new password.";
            $token_valid = false;
            
            echo "<script>setTimeout(function() { window.location.href = 'login.php'; }, 3000);</script>";
        } else {
            $error_message = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section h1 {
            font-size: 28px;
            color: #0f172a;
            margin: 15px 0 5px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-password-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-reset {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .helper-text {