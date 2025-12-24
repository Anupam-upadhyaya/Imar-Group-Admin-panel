<?php
/**
 * IMAR Group Admin Panel - Sidebar Module
 * File: admin/includes/sidebar.php
 * Works from any location in the admin panel
 */

// Determine the base path based on current file location
$current_path = $_SERVER['PHP_SELF'];
$base_url = '/Imar_Group_Admin_panel/admin';

// Determine correct asset path based on current location
if (strpos($current_path, '/admin/') !== false && 
    (strpos($current_path, 'BLOG_CODE') !== false || 
     strpos($current_path, 'GALLERY_CODE') !== false || 
     strpos($current_path, 'INQUIRY_CODE') !== false || 
     strpos($current_path, 'SERVICES_CODE') !== false || 
     strpos($current_path, 'VIDEOS_CODE') !== false)) {
    // We're in a subfolder
    $logo_path = '../../assets/logo.png';
} else {
    // We're in the main admin folder
    $logo_path = '../assets/logo.png';
}

// Fetch notification counts for sidebar
$new_inquiries_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'")->fetch_assoc()['count'] ?? 0;
?>
<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-container">
                <img src="<?php echo $logo_path; ?>" alt="IMAR Group Logo">
            </div>
            <div class="sidebar-logo-text">
                <h2>IMAR Group</h2>
                <p>Admin Panel</p>
            </div>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="<?php echo $base_url; ?>/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span>Dashboard</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/INQUIRY_CODE/inquiries.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'INQUIRY_CODE') !== false ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
            </svg>
            <span>Inquiries</span>
            <?php if ($new_inquiries_count > 0): ?>
                <span style="margin-left: auto; background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                    <?php echo $new_inquiries_count; ?>
                </span>
            <?php endif; ?>
        </a>
        
        <a href="<?php echo $base_url; ?>/GALLERY_CODE/gallery.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'GALLERY_CODE') !== false ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            <span>Gallery</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/BLOG_CODE/blog.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'BLOG_CODE') !== false ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            <span>Blog Posts</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/VIDEOS_CODE/videos.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'VIDEOS_CODE') !== false ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
            <span>Videos</span>
        </a>

        <a href="<?php echo $base_url; ?>/SERVICES_CODE/services.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'SERVICES_CODE') !== false ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
            </svg>
            <span>Services</span>
        </a>
        
        <a href="<?php echo $base_url; ?>/users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            <span>Users</span>
        </a>
    </div>
</div>