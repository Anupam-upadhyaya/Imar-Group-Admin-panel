<?php
/**
 * IMAR Group Admin Panel - Authentication Class
 * File: includes/classes/Auth.php
 */

class Auth {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Secure Login Method
     */
    public function login($email, $password) {
        // Clean input
        $email = $this->sanitizeEmail($email);
        
        // Check if account is locked
        if ($this->isAccountLocked($email)) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Account locked for 15 minutes.'
            ];
        }
        
        // Get user from database using prepared statement
        $stmt = $this->conn->prepare("
            SELECT id, username, email, password, full_name, role, status 
            FROM admins 
            WHERE email = ? 
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Account is inactive. Contact administrator.'
            ];
        }
        
        // Verify password using bcrypt
        if (password_verify($password, $user['password'])) {
            // Password correct - clear failed attempts
            $this->clearLoginAttempts($email);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log successful login
            $this->logLoginAttempt($email, true);
            $this->logActivity($user['id'], 'login', 'admins', $user['id']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
            
        } else {
            // Wrong password
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->logActivity($_SESSION['admin_id'], 'logout', 'admins', $_SESSION['admin_id']);
        }
        
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Check if account is locked due to failed attempts
     */
    private function isAccountLocked($email) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE email = ? 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $lockout = LOGIN_LOCKOUT_TIME;
        $stmt->bind_param("si", $email, $lockout);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($email, $success) {
        $ip = $this->getClientIP();
        $stmt = $this->conn->prepare("
            INSERT INTO login_attempts (email, ip_address, attempt_time, success) 
            VALUES (?, ?, NOW(), ?)
        ");
        
        $stmt->bind_param("ssi", $email, $ip, $success);
        $stmt->execute();
    }
    
    /**
     * Clear login attempts after successful login
     */
    private function clearLoginAttempts($email) {
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $ip = $this->getClientIP();
        $stmt = $this->conn->prepare("
            UPDATE admins 
            SET last_login = NOW(), last_ip = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $ip, $user_id);
        $stmt->execute();
    }
    
    /**
     * Log admin activity
     */
    public function logActivity($admin_id, $action, $table = null, $record_id = null, $details = null) {
        $ip = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs 
            (admin_id, action, table_affected, record_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssss", $admin_id, $action, $table, $record_id, $details, $ip, $user_agent);
        $stmt->execute();
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
    }
    
    /**
     * Sanitize email
     */
    private function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Check user role/permission
     */
    public function hasRole($required_role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $roles = ['super_admin' => 3, 'admin' => 2, 'editor' => 1];
        $user_role_level = $roles[$_SESSION['admin_role']] ?? 0;
        $required_level = $roles[$required_role] ?? 0;
        
        return $user_role_level >= $required_level;
    }
    
    /**
     * Hash password (for registration/password reset)
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
?>