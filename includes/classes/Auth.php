<?php
/**
 * IMAR Group Admin Panel - Authentication Class
 * File: includes/classes/Auth.php
 * Updated to use admin_users table
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class Auth {
    private $conn;
    private $table = 'admin_users';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initSession();
    }
    
    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Only for HTTPS
            
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        // Check for too many failed login attempts
        if ($this->isLoginLocked($email)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
            ];
        }
        
        // Sanitize email
        $email = $this->conn->real_escape_string(trim($email));
        
        // Get user from database - REMOVED username column
        $sql = "SELECT id, name, email, password, role, status, avatar 
                FROM {$this->table} 
                WHERE email = ? 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Login successful - Set session variables
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_avatar'] = $user['avatar'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Log successful login
        $this->logLoginAttempt($email, true);
        $this->logActivity($user['id'], 'login');
        
        // Clear failed login attempts
        $this->clearLoginAttempts($email);
        
        return [
            'success' => true,
            'message' => 'Login successful!'
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->logActivity($_SESSION['admin_id'], 'logout');
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            $this->logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'name' => $_SESSION['admin_name'] ?? null,
            'role' => $_SESSION['admin_role'] ?? null,
            'avatar' => $_SESSION['admin_avatar'] ?? null
        ];
    }
    
    /**
     * Check if login is locked due to failed attempts
     */
    private function isLoginLocked($email) {
        $email = $this->conn->real_escape_string($email);
        $ip = $_SERVER['REMOTE_ADDR'];
        $lockout_time = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME);
        
        $sql = "SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND success = 0 
                AND attempt_time > ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $email, $ip, $lockout_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($email, $success) {
        $email = $this->conn->real_escape_string($email);
        $ip = $_SERVER['REMOTE_ADDR'];
        $success_flag = $success ? 1 : 0;
        
        $sql = "INSERT INTO login_attempts (email, ip_address, attempt_time, success) 
                VALUES (?, ?, NOW(), ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $email, $ip, $success_flag);
        $stmt->execute();
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearLoginAttempts($email) {
        $email = $this->conn->real_escape_string($email);
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $sql = "DELETE FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND success = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $email, $ip);
        $stmt->execute();
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($user_id) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $sql = "UPDATE {$this->table} 
                SET last_login = NOW(), last_ip = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $ip, $user_id);
        $stmt->execute();
    }
    
    /**
     * Log user activity
     */
    public function logActivity($user_id, $action, $table_affected = null, $record_id = null, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO activity_logs 
                (admin_id, action, table_affected, record_id, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ississs", $user_id, $action, $table_affected, $record_id, $details, $ip, $user_agent);
        $stmt->execute();
    }
    
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check user role/permission
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['admin_role'] ?? '';
        
        // Super admin has all permissions
        if ($userRole === 'super_admin') {
            return true;
        }
        
        // Check specific role
        if (is_array($role)) {
            return in_array($userRole, $role);
        }
        
        return $userRole === $role;
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: dashboard.php?error=insufficient_permissions');
            exit();
        }
    }
}
?>