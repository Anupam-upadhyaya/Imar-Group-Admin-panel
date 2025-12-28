<?php
/**
 * IMAR Group Admin Panel - RBAC Permission System
 * File: includes/classes/Permissions.php
 * 
 * Strict Role-Based Access Control Engine
 * Enforces least privilege and explicit permission checking
 */

if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class Permissions {
    
    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    
    // Action constants
    const ACTION_CREATE = 'create';
    const ACTION_EDIT = 'edit';
    const ACTION_DELETE = 'delete';
    const ACTION_PUBLISH = 'publish';
    const ACTION_VIEW = 'view';
    
    // Resource constants
    const RESOURCE_USER = 'user';
    const RESOURCE_CONTENT = 'content';
    const RESOURCE_BLOG = 'blog';
    const RESOURCE_GALLERY = 'gallery';
    const RESOURCE_SERVICE = 'service';
    const RESOURCE_VIDEO = 'video';
    const RESOURCE_INQUIRY = 'inquiry';
    
    /**
     * Complete permission matrix
     * Structure: [role][resource][action] = boolean
     */
    private static $permissions = [
        self::ROLE_SUPER_ADMIN => [
            self::RESOURCE_USER => [
                self::ACTION_CREATE => true,  // Can create Admin and Editor
                self::ACTION_EDIT => true,    // Can edit Admin and Editor
                self::ACTION_DELETE => true,  // Can delete Admin and Editor (with re-auth)
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_CONTENT => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_PUBLISH => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_BLOG => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_PUBLISH => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_GALLERY => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_SERVICE => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_VIDEO => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_INQUIRY => [
                self::ACTION_VIEW => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true
            ]
        ],
        
        self::ROLE_ADMIN => [
            self::RESOURCE_USER => [
                self::ACTION_CREATE => true,  // Can create Editor only
                self::ACTION_EDIT => false,   // Cannot edit users
                self::ACTION_DELETE => false, // Cannot delete users
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_CONTENT => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_PUBLISH => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_BLOG => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_PUBLISH => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_GALLERY => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_SERVICE => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_VIDEO => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_INQUIRY => [
                self::ACTION_VIEW => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => true
            ]
        ],
        
        self::ROLE_EDITOR => [
            self::RESOURCE_USER => [
                self::ACTION_CREATE => false,
                self::ACTION_EDIT => false,
                self::ACTION_DELETE => false,
                self::ACTION_VIEW => false  // No user management access
            ],
            self::RESOURCE_CONTENT => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => false,  // Cannot delete
                self::ACTION_PUBLISH => false, // Cannot publish
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_BLOG => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => false,
                self::ACTION_PUBLISH => false,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_GALLERY => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => false,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_SERVICE => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => false,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_VIDEO => [
                self::ACTION_CREATE => true,
                self::ACTION_EDIT => true,
                self::ACTION_DELETE => false,
                self::ACTION_VIEW => true
            ],
            self::RESOURCE_INQUIRY => [
                self::ACTION_VIEW => true,
                self::ACTION_EDIT => true,  // Can update status
                self::ACTION_DELETE => false
            ]
        ]
    ];
    
    /**
     * Check if user has permission for specific action
     * 
     * @param string $role User's role
     * @param string $resource Resource being accessed
     * @param string $action Action being performed
     * @return bool True if allowed, false otherwise
     */
    public static function can($role, $resource, $action) {
        // Deny if role doesn't exist
        if (!isset(self::$permissions[$role])) {
            return false;
        }
        
        // Deny if resource doesn't exist for this role
        if (!isset(self::$permissions[$role][$resource])) {
            return false;
        }
        
        // Deny if action doesn't exist for this resource
        if (!isset(self::$permissions[$role][$resource][$action])) {
            return false;
        }
        
        // Return explicit permission (true/false)
        return self::$permissions[$role][$resource][$action] === true;
    }
    
    /**
     * Check if user can manage another user based on roles
     * 
     * @param string $actorRole The role performing the action
     * @param string $targetRole The role being acted upon
     * @param string $action The action (create, edit, delete)
     * @return bool
     */
    public static function canManageUser($actorRole, $targetRole, $action) {
        // Super Admin rules
        if ($actorRole === self::ROLE_SUPER_ADMIN) {
            // Can manage Admin accounts (create, edit, delete)
            if ($targetRole === self::ROLE_ADMIN) {
                return in_array($action, [self::ACTION_CREATE, self::ACTION_EDIT, self::ACTION_DELETE]);
            }
            // Can also manage Editor accounts (create, edit, delete)
            if ($targetRole === self::ROLE_EDITOR) {
                return in_array($action, [self::ACTION_CREATE, self::ACTION_EDIT, self::ACTION_DELETE]);
            }
            // Cannot manage other Super Admins
            if ($targetRole === self::ROLE_SUPER_ADMIN) {
                return false;
            }
            return false;
        }
        
        // Admin rules
        if ($actorRole === self::ROLE_ADMIN) {
            // Can only create Editor accounts
            if ($targetRole === self::ROLE_EDITOR && $action === self::ACTION_CREATE) {
                return true;
            }
            // Cannot edit or delete any users
            return false;
        }
        
        // Editors have no user management permissions
        return false;
    }
    
    /**
     * Require permission or die with error
     * 
     * @param string $role User's role
     * @param string $resource Resource being accessed
     * @param string $action Action being performed
     */
    public static function require($role, $resource, $action) {
        if (!self::can($role, $resource, $action)) {
            self::denyAccess("Insufficient permissions: Cannot $action $resource");
        }
    }
    
    /**
     * Deny access with proper logging
     */
    private static function denyAccess($reason) {
        // Log unauthorized access attempt
        if (isset($_SESSION['admin_id'])) {
            global $conn;
            if (isset($conn)) {
                $stmt = $conn->prepare("INSERT INTO security_logs (admin_id, event_type, details, ip_address, created_at) VALUES (?, 'unauthorized_access', ?, ?, NOW())");
                $ip = $_SERVER['REMOTE_ADDR'];
                $admin_id = $_SESSION['admin_id'];
                $stmt->bind_param("iss", $admin_id, $reason, $ip);
                $stmt->execute();
            }
        }
        
        // Redirect to dashboard with error
        header('Location: ' . SITE_URL . '/dashboard.php?error=insufficient_permissions');
        exit();
    }
    
    /**
     * Get all permissions for a role (for debugging/display)
     */
    public static function getRolePermissions($role) {
        return self::$permissions[$role] ?? [];
    }
    
    /**
     * Check if role can publish content
     */
    public static function canPublish($role) {
        return $role === self::ROLE_SUPER_ADMIN || $role === self::ROLE_ADMIN;
    }
    
    /**
     * Check if role can delete content
     */
    public static function canDelete($role) {
        return $role === self::ROLE_SUPER_ADMIN || $role === self::ROLE_ADMIN;
    }
    
    /**
     * Check if user can access user management section
     */
    public static function canAccessUserManagement($role) {
        return $role === self::ROLE_SUPER_ADMIN || $role === self::ROLE_ADMIN;
    }
    
    /**
     * Check if user can delete a specific role
     * 
     * @param string $actorRole The role performing the deletion
     * @param string $targetRole The role being deleted
     * @return bool
     */
    public static function canDeleteUser($actorRole, $targetRole) {
        return self::canManageUser($actorRole, $targetRole, self::ACTION_DELETE);
    }
}