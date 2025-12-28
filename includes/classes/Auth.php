<?php
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

    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Only for HTTPS
            
            session_name(SESSION_NAME);
            session_start();
            
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public function login($email, $password) {
        if ($this->isLoginLocked($email)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
            ];
        }

        $email = $this->conn->real_escape_string(trim($email));
        
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
        
        if ($user['status'] !== 'active') {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ];
        }
        
        if (!password_verify($password, $user['password'])) {
            $this->logLoginAttempt($email, false);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_avatar'] = $user['avatar'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        $this->updateLastLogin($user['id']);
        
        $this->logLoginAttempt($email, true);
        $this->logActivity($user['id'], 'login');
        
        $this->clearLoginAttempts($email);
        
        return [
            'success' => true,
            'message' => 'Login successful!'
        ];
    }
  
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->logActivity($_SESSION['admin_id'], 'logout');
        }
        
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        return true;
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        return true;
    }

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
    

    private function updateLastLogin($user_id) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $sql = "UPDATE {$this->table} 
                SET last_login = NOW(), last_ip = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $ip, $user_id);
        $stmt->execute();
    }

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
    

    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    

    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['admin_role'] ?? '';
        
        if ($userRole === 'super_admin') {
            return true;
        }

        if (is_array($role)) {
            return in_array($userRole, $role);
        }
        
        return $userRole === $role;
    }
    

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: dashboard.php?error=insufficient_permissions');
            exit();
        }
    }
}
?>