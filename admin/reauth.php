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

$error = '';
$returnUrl = $_GET['return'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if ($access->requireReAuthentication($password)) {
        // Success - redirect back
        header('Location: ' . $returnUrl);
        exit();
    } else {
        $error = 'Incorrect password';
    }
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Identity</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="reauth-container">
        <h2>🔒 Verify Your Identity</h2>
        <p>This action requires password confirmation</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Your Password</label>
                <input type="password" name="password" required autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary">Verify</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>