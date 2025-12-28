<?php
/**
 * IMAR Group Admin Panel - Access Control Middleware
 * File: includes/classes/AccessControl.php
 * 
 * Enforces RBAC at application level
 * Must be included in every protected page
 */

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
    
    /**
     * Check access for current page/action
     * Call this at the top of every admin page
     */
    public function checkAccess($resource, $action = Permissions::ACTION_VIEW) {
        // Ensure user is logged in
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
        
        $user = $this->auth->getCurrentUser();
        $role = $user['role'];
        
        // Check permission
        if (!Permissions::can($role, $resource, $action)) {
            $this->logUnauthorizedAccess($user['id'], $resource, $action);
            $this->denyAccess("Access denied: Cannot $action $resource");
        }
        
        return true;
    }
    
    /**
     * Check if user can perform action on specific user
     * Used for user management operations
     */
    public function checkUserManagement($targetUserId, $action) {
        if (!$this->auth->isLoggedIn()) {
            $this->denyAccess("Not authenticated");
        }
        
        $currentUser = $this->auth->getCurrentUser();
        $currentRole = $currentUser['role'];
        $currentId = $currentUser['id'];
        
        // Get target user's role
        $stmt = $this->conn->prepare("SELECT role FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $targetUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->denyAccess("Target user not found");
        }
        
        $targetUser = $result->fetch_assoc();
        $targetRole = $targetUser['role'];
        
        // Prevent self-action for certain operations
        if ($action === Permissions::ACTION_DELETE && $currentId === $targetUserId) {
            // Allow self-deletion request for Super Admin only
            if ($currentRole !== Permissions::ROLE_SUPER_ADMIN) {
                $this->denyAccess("Cannot delete your own account");
            }
            // Super Admin self-deletion requires special process
            return $this->handleSuperAdminSelfDeletion($currentId);
        }
        
        // Check if action is allowed based on roles
        if (!Permissions::canManageUser($currentRole, $targetRole, $action)) {
            $this->logUnauthorizedAccess($currentId, "user_$targetRole", $action);
            $this->denyAccess("Cannot $action user with role $targetRole");
        }
        
        return true;
    }
    
    /**
     * Handle Super Admin self-deletion request
     */
    private function handleSuperAdminSelfDeletion($adminId) {
        // Check if this is the last Super Admin
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role = ? AND status = 'active'");
        $role = Permissions::ROLE_SUPER_ADMIN;
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] <= 1) {
            $this->denyAccess("Cannot delete the last Super Admin account");
        }
        
        // Check if deletion request already exists
        $stmt = $this->conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Request already exists
            return ['status' => 'existing', 'request' => $existing];
        }
        
        // Password re-authentication required (handled in calling code)
        // This method just validates the business rules
        return ['status' => 'allowed'];
    }
    
    /**
     * Require password re-authentication for sensitive actions
     */
    public function requireReAuthentication($password) {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }
        
        $user = $this->auth->getCurrentUser();
        $email = $user['email'];
        
        // Verify password
        $stmt = $this->conn->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $userData = $result->fetch_assoc();
        
        if (!password_verify($password, $userData['password'])) {
            // Log failed re-authentication
            $this->logFailedReAuth($user['id']);
            return false;
        }
        
        // Set re-authenticated flag in session (valid for 5 minutes)
        $_SESSION['reauth_time'] = time();
        $_SESSION['reauth_verified'] = true;
        
        return true;
    }
    
    /**
     * Check if user is currently re-authenticated
     */
    public function isReAuthenticated() {
        if (!isset($_SESSION['reauth_verified']) || !isset($_SESSION['reauth_time'])) {
            return false;
        }
        
        // Re-authentication expires after 5 minutes
        if (time() - $_SESSION['reauth_time'] > 300) {
            unset($_SESSION['reauth_verified']);
            unset($_SESSION['reauth_time']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Clear re-authentication
     */
    public function clearReAuthentication() {
        unset($_SESSION['reauth_verified']);
        unset($_SESSION['reauth_time']);
    }
    
    /**
     * Log unauthorized access attempt
     */
    private function logUnauthorizedAccess($userId, $resource, $action) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $details = "Attempted $action on $resource";
        
        $stmt = $this->conn->prepare("INSERT INTO security_logs (admin_id, event_type, details, ip_address, user_agent, created_at) VALUES (?, 'unauthorized_access', ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $userId, $details, $ip, $userAgent);
        $stmt->execute();
    }
    
    /**
     * Log failed re-authentication
     */
    private function logFailedReAuth($userId) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("INSERT INTO security_logs (admin_id, event_type, details, ip_address, created_at) VALUES (?, 'failed_reauth', 'Failed password re-verification', ?, NOW())");
        $stmt->bind_param("is", $userId, $ip);
        $stmt->execute();
    }
    
    /**
     * Deny access and redirect
     */
    private function denyAccess($reason) {
        header('Location: ' . SITE_URL . '/dashboard.php?error=' . urlencode($reason));
        exit();
    }
    
    /**
     * Log all privileged actions
     */
    public function logPrivilegedAction($userId, $action, $resource, $targetId = null, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (admin_id, action, table_affected, record_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssss", $userId, $action, $resource, $targetId, $details, $ip, $userAgent);
        $stmt->execute();
    }
    
    /**
     * Get current user role
     */
    public function getCurrentRole() {
        if (!$this->auth->isLoggedIn()) {
            return null;
        }
        
        $user = $this->auth->getCurrentUser();
        return $user['role'];
    }
}