<?php
/**
 * IMAR Group Admin Panel - Login Page
 * File: admin/login.php
 * Updated to use admin_users table
 */

// Start secure session
session_start();

// Security constant
define('SECURE_ACCESS', true);

// Include configuration and classes
require_once '../config/config.php';
require_once '../includes/classes/Auth.php';

// Initialize Auth
$auth = new Auth($conn);

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Auth::verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            // Attempt login
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = Auth::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login - IMAR Group Admin Panel</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- LOGIN PAGE -->
    <div class="login-container">
        <div class="bg-shape bg-shape-1"></div>
        <div class="bg-shape bg-shape-2"></div>
        
        <div style="position: relative; z-index: 1; width: 100%; max-width: 450px;">
            <div class="login-card">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm13.66-2.66l6.94 6.94-6.94 6.94-6.94-6.94 6.94-6.94z"/>
                        </svg>
                    </div>
                    <h1>IMAR Group</h1>
                    <p class="subtitle">Investment Management Admin Portal</p>
                </div>

                <div class="security-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                    </svg>
                    <span>Secure Admin Access</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- FORM START -->
                <form method="POST" action="login.php" id="loginForm">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                placeholder="admin@imargroup.com" 
                                required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                placeholder="••••••••" 
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <label class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <span id="loginText">Sign In</span>
                        <svg id="loginArrow" width="20" height="20" viewBox="0 0 24 24" fill="white">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                        </svg>
                        <div id="loginSpinner" class="spinner hidden"></div>
                    </button>
                </form>
                <!-- FORM END -->

                <div class="login-footer">
                    <p>Need help? Contact IT support</p>
                </div>
            </div>
            
            <p class="copyright">© <?php echo date('Y'); ?> IMAR Group. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('loginText');
            const arrow = document.getElementById('loginArrow');
            const spinner = document.getElementById('loginSpinner');
            
            // Disable button and show loading
            btn.disabled = true;
            text.textContent = 'Signing in...';
            arrow.classList.add('hidden');
            spinner.classList.remove('hidden');
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>

</body>
</html>