<?php
/**
 * Avatar Helper Functions
 * File: admin/includes/avatar-helper.php
 * 
 * Include this file in any page that needs to display user avatars
 * Usage: require_once __DIR__ . '/includes/avatar-helper.php'; (from admin/)
 * Usage: require_once __DIR__ . '/../includes/avatar-helper.php'; (from admin/SUBFOLDER/)
 */

if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Get the avatar URL for a user with smart path detection
 * 
 * @param string|null $avatar The avatar filename from database
 * @param string $currentFilePath The current file's directory (usually __DIR__)
 * @return string|null Returns the relative URL to the avatar or null if not found
 */
function getAvatarPath($avatar, $currentFilePath = null) {
    if (!$avatar) {
        return null;
    }
    
    // If no path provided, try to detect it
    if ($currentFilePath === null) {
        $currentFilePath = __DIR__;
    }
    
    // Determine the depth level by checking the current script path
    $scriptPath = $_SERVER['SCRIPT_FILENAME'];
    
    // Count how many levels deep we are from admin folder
    $adminPos = strpos($scriptPath, '/admin/');
    if ($adminPos === false) {
        $adminPos = strpos($scriptPath, '\\admin\\'); // Windows path
    }
    
    if ($adminPos !== false) {
        $afterAdmin = substr($scriptPath, $adminPos + 7); // +7 for '/admin/'
        $depth = substr_count($afterAdmin, '/') + substr_count($afterAdmin, '\\');
    } else {
        $depth = 0;
    }
    
    // Build the correct relative path based on depth
    if ($depth === 0) {
        // File is directly in admin/ (like dashboard.php)
        $relativePath = '../uploads/avatars/' . $avatar;
        $filesystemPath = $currentFilePath . '/../uploads/avatars/' . $avatar;
    } else {
        // File is in a subfolder (like VIDEOS_CODE/videos.php)
        $relativePath = '../../uploads/avatars/' . $avatar;
        $filesystemPath = $currentFilePath . '/../../uploads/avatars/' . $avatar;
    }
    
    // Check if file exists
    if (file_exists($filesystemPath) && is_file($filesystemPath)) {
        return $relativePath;
    }
    
    return null;
}

/**
 * Render an avatar image tag or initials
 * 
 * @param string|null $avatar The avatar filename from database
 * @param string $name The user's name (for initials fallback)
 * @param string $currentFilePath The current file's directory
 * @param string $class Additional CSS classes for the container
 * @return string HTML for the avatar
 */
function renderAvatar($avatar, $name, $currentFilePath = null, $class = 'user-avatar') {
    $avatarUrl = getAvatarPath($avatar, $currentFilePath);
    $initials = strtoupper(substr($name, 0, 1));
    
    if ($avatarUrl) {
        return sprintf(
            '<div class="%s"><img src="%s" alt="%s" onerror="this.outerHTML=\'<span>%s</span>\';"></div>',
            htmlspecialchars($class),
            htmlspecialchars($avatarUrl),
            htmlspecialchars($name),
            $initials
        );
    } else {
        return sprintf(
            '<div class="%s">%s</div>',
            htmlspecialchars($class),
            $initials
        );
    }
}

/**
 * Get current logged-in user's avatar info
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return array|null User data including avatar
 */
function getCurrentUserAvatar($conn, $userId) {
    $stmt = $conn->prepare("SELECT name, email, avatar, role FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return null;
}
?>