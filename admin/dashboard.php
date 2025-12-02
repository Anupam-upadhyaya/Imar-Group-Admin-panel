<?php
/**
 * IMAR Group Admin Panel - Dashboard
 * File: admin/dashboard.php
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

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get current user info
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_initials = strtoupper(substr($admin_name, 0, 1));

// Fetch dashboard statistics
$stats = [];

// New inquiries count
$result = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'");
$stats['new_inquiries'] = $result->fetch_assoc()['count'] ?? 0;

// Total gallery images
$result = $conn->query("SELECT COUNT(*) as count FROM gallery WHERE status = 'active'");
$stats['gallery_count'] = $result->fetch_assoc()['count'] ?? 0;

// Published blog posts
$result = $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'");
$stats['blog_count'] = $result->fetch_assoc()['count'] ?? 0;

// Total admin users
$result = $conn->query("SELECT COUNT(*) as count FROM admins WHERE status = 'active'");
$stats['admin_count'] = $result->fetch_assoc()['count'] ?? 0;

// Recent inquiries
$recent_inquiries = [];
$result = $conn->query("
    SELECT id, name, email, subject, status, created_at 
    FROM inquiries 
    ORDER BY created_at DESC 
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_inquiries[] = $row;
}

// Recent activities
$recent_activities = [];
$result = $conn->query("
    SELECT al.action, al.table_affected, al.created_at, a.full_name 
    FROM activity_logs al 
    LEFT JOIN admins a ON al.admin_id = a.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/Style.css">
</head>
<body>

<div class="dashboard">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm13.66-2.66l6.94 6.94-6.94 6.94-6.94-6.94 6.94-6.94z"/>
                </svg>
                <div class="sidebar-logo-text">
                    <h2>IMAR Group</h2>
                    <p>Admin Panel</p>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <svg viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="inquiries.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                </svg>
                <span>Inquiries</span>
                <?php if ($stats['new_inquiries'] > 0): ?>
                    <span style="margin-left: auto; background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                        <?php echo $stats['new_inquiries']; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <span>Gallery</span>
            </a>

            <a href="blog.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                </svg>
                <span>Blog Posts</span>
            </a>

            <a href="users.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <span>Users</span>
            </a>

            <a href="settings.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-header">
                        <h3>New Inquiries</h3>
                    </div>
                    <div class="stat-value"><?php echo $stats['new_inquiries']; ?></div>
                </div>
                <div class="stat-icon blue">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-header">
                        <h3>Gallery Images</h3>
                    </div>
                    <div class="stat-value"><?php echo $stats['gallery_count']; ?></div>
                </div>
                <div class="stat-icon green">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-header">
                        <h3>Published Posts</h3>
                    </div>
                    <div class="stat-value"><?php echo $stats['blog_count']; ?></div>
                </div>
                <div class="stat-icon purple">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-header">
                        <h3>Active Users</h3>
                    </div>
                    <div class="stat-value"><?php echo $stats['admin_count']; ?></div>
                </div>
                <div class="stat-icon orange">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- CONTENT SECTIONS -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <!-- Recent Inquiries -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <h2 style="font-size: 18px; margin-bottom: 15px;">Recent Inquiries</h2>
                <?php if (empty($recent_inquiries)): ?>
                    <p style="color: #6b7280; font-size: 14px;">No inquiries yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_inquiries as $inquiry): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                            <div style="font-size: 13px; color: #6b7280;"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                                <?php echo date('M d, Y h:i A', strtotime($inquiry['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <h2 style="font-size: 18px; margin-bottom: 15px;">Recent Activities</h2>
                <?php if (empty($recent_activities)): ?>
                    <p style="color: #6b7280; font-size: 14px;">No activities yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                            <div style="font-size: 14px;">
                                <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                <?php echo htmlspecialchars($activity['action']); ?>
                                <?php if ($activity['table_affected']): ?>
                                    in <em><?php echo htmlspecialchars($activity['table_affected']); ?></em>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                                <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>