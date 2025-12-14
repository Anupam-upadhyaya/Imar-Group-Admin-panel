<?php
/**
 * IMAR Group Admin Panel - Blog Management
 * File: admin/BLOG_CODE/blog.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

// Handle delete
if (isset($_GET['delete']) && $admin_role !== 'editor') {
    $delete_id = (int)$_GET['delete'];
    
    // Get image paths before deleting
    $stmt = $conn->prepare("SELECT featured_image, thumbnail FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        // Delete image files
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        if ($row['featured_image']) {
            $image_path = $document_root . '/Imar-Group-Website/' . $row['featured_image'];
            if (file_exists($image_path)) @unlink($image_path);
        }
        if ($row['thumbnail']) {
            $thumb_path = $document_root . '/Imar-Group-Website/' . $row['thumbnail'];
            if (file_exists($thumb_path)) @unlink($thumb_path);
        }
        
        // Log activity
        $auth->logActivity($admin_id, 'deleted_blog_post', 'blog_posts', $delete_id);
        
        $success_message = "Blog post deleted successfully!";
    }
}

// Get filter and search
$filter_category = $_GET['category'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];
$types = '';

if ($filter_category !== 'all') {
    $whereConditions[] = "category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

if ($filter_status !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR excerpt LIKE ? OR author LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT * FROM blog_posts $whereClause ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$blog_posts = $result->fetch_all(MYSQLI_ASSOC);

// Get counts for stats
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM blog_posts")->fetch_assoc()['count'],
    'published' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")->fetch_assoc()['count'],
    'draft' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'draft'")->fetch_assoc()['count'],
    'views' => $conn->query("SELECT SUM(views) as count FROM blog_posts")->fetch_assoc()['count'] ?? 0
];

// Get category counts
$category_counts = [
    'all' => $stats['total'],
    'tax-planning' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE category = 'tax-planning'")->fetch_assoc()['count'],
    'investment' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE category = 'investment'")->fetch_assoc()['count'],
    'retirement' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE category = 'retirement'")->fetch_assoc()['count'],
    'news' => $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE category = 'news'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .blog-grid-admin {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .blog-card-admin {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .blog-card-admin:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .blog-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
        }
        
        .blog-card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .blog-card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            line-height: 1.4;
        }
        
        .blog-card-excerpt {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 12px;
            line-height: 1.6;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .blog-card-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-category {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .badge-status {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-status.draft {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-status.archived {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .blog-card-info {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 15px;
            flex: 1;
        }
        
        .blog-card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }
        
        .btn-small {
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-view {
            background: #10b981;
            color: white;
        }
        
        .btn-view:hover {
            background: #059669;
        }
        
        .btn-edit {
            background: #4f46e5;
            color: white;
        }
        
        .btn-edit:hover {
            background: #4338ca;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .add-new-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            font-size: 24px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
            transition: all 0.3s;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-new-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.6);
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-tab.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }
        
        .filter-badge {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .filter-tab.active .filter-badge {
            background: rgba(255,255,255,0.2);
        }
        
        .search-filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
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
            <a href="/Imar_Group_Admin_panel/admin/dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/INQUIRY_CODE/inquiries.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>Inquiries</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/GALLERY_CODE/gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                <span>Gallery</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/BLOG_CODE/blog.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/users.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span>Users</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/settings.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Blog Management</h1>
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

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Total Posts</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Published</h3>
                        <div class="stat-value"><?php echo $stats['published']; ?></div>
                    </div>
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Drafts</h3>
                        <div class="stat-value"><?php echo $stats['draft']; ?></div>
                    </div>
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Total Views</h3>
                        <div class="stat-value"><?php echo number_format($stats['views']); ?></div>
                    </div>
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="search-filter-row">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="text" name="search" class="search-input" placeholder="Search blogs..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <select class="filter-select" onchange="location.href='?status=<?php echo $filter_status; ?>&category=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="tax-planning" <?php echo $filter_category === 'tax-planning' ? 'selected' : ''; ?>>Tax Planning</option>
                <option value="investment" <?php echo $filter_category === 'investment' ? 'selected' : ''; ?>>Investment</option>
                <option value="retirement" <?php echo $filter_category === 'retirement' ? 'selected' : ''; ?>>Retirement</option>
                <option value="news" <?php echo $filter_category === 'news' ? 'selected' : ''; ?>>News</option>
            </select>
            
            <select class="filter-select" onchange="location.href='?category=<?php echo $filter_category; ?>&status=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <!-- Blog Grid -->
        <?php if (empty($blog_posts)): ?>
            <div class="empty-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="#d1d5db">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                </svg>
                <h3>No Blog Posts Found</h3>
                <p>Start by creating your first blog post.</p>
            </div>
        <?php else: ?>
            <div class="blog-grid-admin">
                <?php foreach ($blog_posts as $post): ?>
                    <div class="blog-card-admin">
                        <img src="../../../Imar-Group-Website/<?php echo htmlspecialchars($post['featured_image'] ?: 'images/blog/default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                             class="blog-card-image"
                             onerror="this.src='../assets/placeholder.jpg'">
                        
                        <div class="blog-card-content">
                            <div class="blog-card-title"><?php echo htmlspecialchars($post['title']); ?></div>
                            <div class="blog-card-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></div>
                            
                            <div class="blog-card-meta">
                                <span class="badge badge-category"><?php echo ucfirst(str_replace('-', ' ', $post['category'])); ?></span>
                                <span class="badge badge-status <?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                            </div>
                            
                            <div class="blog-card-info">
                                <div>By <?php echo htmlspecialchars($post['author']); ?></div>
                                <div><?php echo date('M d, Y', strtotime($post['created_at'])); ?> • <?php echo $post['views']; ?> views</div>
                            </div>
                            
                            <div class="blog-card-actions">
                                <a href="view-blog.php?id=<?php echo $post['id']; ?>" class="btn-small btn-view">View</a>
                                <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <?php if ($admin_role !== 'editor'): ?>
                                    <a href="?delete=<?php echo $post['id']; ?>&category=<?php echo $filter_category; ?>&status=<?php echo $filter_status; ?>" 
                                       class="btn-small btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this blog post?')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add New Button -->
        <a href="add-blog.php" class="add-new-btn" title="Add New Blog Post">+</a>
    </div>
</div>

</body>
</html>