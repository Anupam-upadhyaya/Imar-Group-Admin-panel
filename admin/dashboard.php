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
    SELECT 
        id,
        CONCAT(first_name, ' ', last_name) AS name,
        email,
        status,
        created_at
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
    LIMIT 8
");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Get greeting based on time
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
            <a href="/Imar_Group_Admin_panel/admin/dashboard.php" class="menu-item active">
                <svg viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="/Imar_Group_Admin_panel/admin/INQUIRY_CODE/inquiries.php" class="menu-item">
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

            <a href="/Imar_Group_Admin_panel/admin/GALLERY_CODE/gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <span>Gallery</span>
            </a>

            <a href="/Imar_Group_Admin_panel/admin/BLOG_CODE/blog.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/>
                </svg>
                <span>Blog Posts</span>
            </a>

            <a href="/Imar_Group_Admin_panel/admin/VIDEOS_CODE/videos.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                </svg>
                <span>Videos</span>
            </a>

            <a href="/Imar_Group_Admin_panel/admin/users.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <span>Users</span>
            </a>

            <a href="/Imar_Group_Admin_panel/admin/settings.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
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
                <a href="/Imar_Group_Admin_panel/admin/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2><?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $admin_name)[0]); ?>! 👋</h2>
            <p>Here's what's happening with your projects today.</p>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="/Imar_Group_Admin_panel/admin/INQUIRY_CODE/inquiries.php" class="quick-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                <span>View Inquiries</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/GALLERY_CODE/gallery.php" class="quick-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <span>Upload Image</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/BLOG_CODE/blog.php" class="quick-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                </svg>
                <span>Create Blog</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/users.php" class="quick-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <span>Add User</span>
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>New Inquiries</h3>
                        <div class="stat-value"><?php echo $stats['new_inquiries']; ?></div>
                        <div class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 14l5-5 5 5z"/>
                            </svg>
                            <span>+12% from last week</span>
                        </div>
                    </div>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Gallery Images</h3>
                        <div class="stat-value"><?php echo $stats['gallery_count']; ?></div>
                        <div class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 14l5-5 5 5z"/>
                            </svg>
                            <span>+5 this month</span>
                        </div>
                    </div>
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Published Posts</h3>
                        <div class="stat-value"><?php echo $stats['blog_count']; ?></div>
                        <div class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 14l5-5 5 5z"/>
                            </svg>
                            <span>+2 this week</span>
                        </div>
                    </div>
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Active Users</h3>
                        <div class="stat-value"><?php echo $stats['admin_count']; ?></div>
                        <div class="stat-change">
                            <span>Total admin users</span>
                        </div>
                    </div>
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="two-column-grid">
            <!-- Recent Inquiries -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Recent Inquiries</h2>
                    <a href="/Imar_Group_Admin_panel/admin/INQUIRY_CODE/inquiries.php" class="view-all">View All →</a>
                </div>
                
                <?php if (empty($recent_inquiries)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                        </svg>
                        <h3>No Inquiries Yet</h3>
                        <p>New customer inquiries will appear here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_inquiries as $inquiry): ?>
                        <div class="inquiry-item">
                            <div class="inquiry-header">
                                <div class="inquiry-name"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                                <span class="inquiry-badge <?php echo strtolower($inquiry['status']); ?>">
                                    <?php echo ucfirst($inquiry['status']); ?>
                                </span>
                            </div>
                            <div class="inquiry-meta">
                                <span>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                    <?php echo htmlspecialchars($inquiry['email']); ?>
                                </span>
                                <span>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                    </svg>
                                    <?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Recent Activities</h2>
                </div>
                
                <?php if (empty($recent_activities)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24">
                            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                        <h3>No Activities Yet</h3>
                        <p>System activities will be logged here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php 
                                $name = $activity['full_name'] ?? 'System';
                                echo strtoupper(substr($name, 0, 1)); 
                                ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                    <?php if ($activity['table_affected']): ?>
                                        in <em><?php echo htmlspecialchars($activity['table_affected']); ?></em>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                    </svg>
                                    <?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?>
                                </div>
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