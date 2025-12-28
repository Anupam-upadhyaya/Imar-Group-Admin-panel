<?php
session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address']);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email FROM admin_users WHERE email = ? AND status = 'active'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => true, 
        'message' => 'If this email exists, you will receive a password reset link shortly.'
    ]);
    exit();
}

$user = $result->fetch_assoc();

$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
try {
    $stmt = $conn->prepare("INSERT INTO password_reset_requests (user_id, token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user['id'], $token, $ip_address, $user_agent, $expires_at);
    $stmt->execute();
} catch (Exception $e) {
    error_log("Password reset requests table error: " . $e->getMessage());
}

$stmt = $conn->prepare("UPDATE admin_users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
$stmt->bind_param("ssi", $token, $expires_at, $user['id']);
$stmt->execute();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$reset_link = "{$protocol}://{$host}/Imar_Group_Admin_panel/admin/reset-password.php?token={$token}";

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

$email_sent = @mail($to, $subject, $message, $headers);

error_log("Password reset link for {$email}: {$reset_link}");

echo json_encode([
    'success' => true,
    'message' => 'If this email exists, you will receive a password reset link shortly.',
    'debug_link' => $reset_link 
]);
?>