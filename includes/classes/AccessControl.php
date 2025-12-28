<?php
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/Permissions.php';
require_once __DIR__ . '/Auth.php';

class AccessControl {
    private $auth;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->auth = new Auth($conn);
    }
    
    public function checkAccess($resource, $action = Permissions::ACTION_VIEW) {
        // Ensure user is logged in
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
        
        $user = $this->auth->getCurrentUser();
        $role = $user['role'];
        
        if (!Permissions::can($role, $resource, $action)) {
            $this->logUnauthorizedAccess($user['id'], $resource, $action);
            $this->denyAccess("Access denied: Cannot $action $resource");
        }
        
        return true;
    }
    
    public function checkUserManagement($targetUserId, $action) {
        if (!$this->auth->isLoggedIn()) {
            $this->denyAccess("Not authenticated");
        }
        
        $currentUser = $this->auth->getCurrentUser();
        $currentRole = $currentUser['role'];
        $currentId = $currentUser['id'];
        
        $stmt = $this->conn->prepare("SELECT role FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $targetUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->denyAccess("Target user not found");
        }
        
        $targetUser = $result->fetch_assoc();
        $targetRole = $targetUser['role'];
        
        if ($action === Permissions::ACTION_DELETE && $currentId === $targetUserId) {
            if ($currentRole !== Permissions::ROLE_SUPER_ADMIN) {
                $this->denyAccess("Cannot delete your own account");
            }
            return $this->handleSuperAdminSelfDeletion($currentId);
        }
        
        if (!Permissions::canManageUser($currentRole, $targetRole, $action)) {
            $this->logUnauthorizedAccess($currentId, "user_$targetRole", $action);
            $this->denyAccess("Cannot $action user with role $targetRole");
        }
        
        return true;
    }
    
    private function handleSuperAdminSelfDeletion($adminId) {

        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role = ? AND status = 'active'");
        $role = Permissions::ROLE_SUPER_ADMIN;
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] <= 1) {
            $this->denyAccess("Cannot delete the last Super Admin account");
        }
        

        $stmt = $this->conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            return ['status' => 'existing', 'request' => $existing];
        }
        
        return ['status' => 'allowed'];
    }
    
    public function requireReAuthentication($password) {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }
        
        $user = $this->auth->getCurrentUser();
        $email = $user['email'];
        
        $stmt = $this->conn->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $userData = $result->fetch_assoc();
        
        if (!password_verify($password, $userData['password'])) {
            $this->logFailedReAuth($user['id']);
            return false;
        }

        $_SESSION['reauth_time'] = time();
        $_SESSION['reauth_verified'] = true;
        
        return true;
    }
    
    public function isReAuthenticated() {
        if (!isset($_SESSION['reauth_verified']) || !isset($_SESSION['reauth_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['reauth_time'] > 300) {
            unset($_SESSION['reauth_verified']);
            unset($_SESSION['reauth_time']);
            return false;
        }
        
        return true;
    }
    
    public function clearReAuthentication() {
        unset($_SESSION['reauth_verified']);
        unset($_SESSION['reauth_time']);
    }
    
    private function logUnauthorizedAccess($userId, $resource, $action) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $details = "Attempted $action on $resource";
        
        $stmt = $this->conn->prepare("INSERT INTO security_logs (admin_id, event_type, details, ip_address, user_agent, created_at) VALUES (?, 'unauthorized_access', ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $userId, $details, $ip, $userAgent);
        $stmt->execute();
    }
    
    private function logFailedReAuth($userId) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("INSERT INTO security_logs (admin_id, event_type, details, ip_address, created_at) VALUES (?, 'failed_reauth', 'Failed password re-verification', ?, NOW())");
        $stmt->bind_param("is", $userId, $ip);
        $stmt->execute();
    }
    
    private function denyAccess($reason) {
        header('Location: ' . SITE_URL . '/dashboard.php?error=' . urlencode($reason));
        exit();
    }
    
    public function logPrivilegedAction($userId, $action, $resource, $targetId = null, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (admin_id, action, table_affected, record_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssss", $userId, $action, $resource, $targetId, $details, $ip, $userAgent);
        $stmt->execute();
    }
    
    public function getCurrentRole() {
        if (!$this->auth->isLoggedIn()) {
            return null;
        }
        
        $user = $this->auth->getCurrentUser();
        return $user['role'];
    }
}