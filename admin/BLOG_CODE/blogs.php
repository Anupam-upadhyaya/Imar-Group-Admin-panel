<?php
/**
 * IMAR Group Admin Panel - Blog Management
 * File: admin/blog.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/BlogManager.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$blogManager = new BlogManager($conn);
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_initials = strtoupper(substr($admin_name, 0, 1));

// Handle filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

// Get posts
$result = $blogManager->getAllPosts($page, 20, $filters);
$posts = $result['posts'];
$total_pages = $result['total_pages'];

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as c FROM blog_posts")->fetch_assoc()['c'];
$stats['published'] = $conn->query("SELECT COUNT(*) as c FROM blog_posts WHERE status='published'")->fetch_assoc()['c'];
$stats['draft'] = $conn->query("SELECT COUNT(*) as c FROM blog_posts WHERE status='draft'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .blog-actions { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; justify-content: space-between; align-items: center; }
        .blog-filters { display: flex; gap: 10px; flex-wrap: wrap; }
        .blog-filters select, .blog-filters input { padding: 10px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
        .blog-filters input[type="search"] { min-width: 250px; }
        .blog-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .blog-table table { width: 100%; border-collapse: collapse; }
        .blog-table th { background: #f9fafb; padding: 16px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .blog-table td { padding: 16px; border-bottom: 1px solid #f3f4f6; }
        .blog-table tr:hover { background: #f9fafb; }
        .blog-thumbnail { width: 80px; height: 50px; object-fit: cover; border-radius: 6px; }
        .blog-title { font-weight: 600; color: #111827; margin-bottom: 4px; }
        .blog-excerpt { font-size: 13px; color: #6b7280; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-badge.published { background: #d1fae5; color: #065f46; }
        .status-badge.draft { background: #fef3c7; color: #92400e; }
        .status-badge.archived { background: #e5e7eb; color: #374151; }
        .category-tag { display: inline-block; padding: 3px 10px; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 12px; }
        .action-btns { display: flex; gap: 8px; }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 13px; cursor: pointer; border: 1px solid #e5e7eb; background: white; transition: all 0.2s; }
        .action-btn:hover { background: #f3f4f6; }
        .action-btn.edit { color: #2563eb; border-color: #2563eb; }
        .action-btn.delete { color: #dc2626; border-color: #dc2626; }
        .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 30px; }
        .pagination button { padding: 8px 14px; border: 1px solid #e5e7eb; background: white; border-radius: 6px; cursor: pointer; }
        .pagination button.active { background: #2563eb; color: white; border-color: #2563eb; }
        .stats-mini { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-mini { padding: 12px 20px; background: white; border-radius: 8px; border-left: 3px solid #2563eb; }
        .stat-mini h4 { font-size: 24px; font-weight: 700; margin: 0; }
        .stat-mini p { font-size: 13px; color: #6b7280; margin: 4px 0 0; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; fill: #d1d5db; }
        .empty-state h3 { margin: 0 0 8px; color: #374151; }
        .empty-state p { color: #6b7280; margin: 0; }
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
            <a href="dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="inquiries.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                <span>Inquiries</span>
            </a>
            <a href="gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2z"/></svg>
                <span>Gallery</span>
            </a>
            <a href="blog.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="videos.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                <span>Videos</span>
            </a>
            <a href="users.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3z"/></svg>
                <span>Users</span>
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
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-mini">
            <div class="stat-mini">
                <h4><?php echo $stats['total']; ?></h4>
                <p>Total Posts</p>
            </div>
            <div class="stat-mini" style="border-color: #10b981;">
                <h4><?php echo $stats['published']; ?></h4>
                <p>Published</p>
            </div>
            <div class="stat-mini" style="border-color: #f59e0b;">
                <h4><?php echo $stats['draft']; ?></h4>
                <p>Drafts</p>
            </div>
        </div>

        <!-- Actions and Filters -->
        <div class="blog-actions">
            <div class="blog-filters">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="search" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="published" <?php echo ($filters['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($filters['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="archived" <?php echo ($filters['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="tax-planning" <?php echo ($filters['category'] ?? '') === 'tax-planning' ? 'selected' : ''; ?>>Tax Planning</option>
                        <option value="investment" <?php echo ($filters['category'] ?? '') === 'investment' ? 'selected' : ''; ?>>Investment</option>
                        <option value="retirement" <?php echo ($filters['category'] ?? '') === 'retirement' ? 'selected' : ''; ?>>Retirement</option>
                        <option value="news" <?php echo ($filters['category'] ?? '') === 'news' ? 'selected' : ''; ?>>News</option>
                    </select>
                    <button type="submit" class="action-btn" style="padding: 10px 20px;">Filter</button>
                    <?php if (!empty($filters)): ?>
                        <a href="blog.php" class="action-btn" style="padding: 10px 20px; text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <a href="blog_create.php" class="btn btn-primary">+ New Post</a>
        </div>

        <!-- Blog Posts Table -->
        <div class="blog-table">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/></svg>
                    <h3>No Blog Posts Found</h3>
                    <p>Create your first blog post to get started</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <?php if ($post['featured_image']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                             alt="Thumbnail" class="blog-thumbnail">
                                    <?php else: ?>
                                        <div class="blog-thumbnail" style="background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#9ca3af">
                                                <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="blog-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                    <div class="blog-excerpt"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 80)) . '...'; ?></div>
                                </td>
                                <td><span class="category-tag"><?php echo ucwords(str_replace('-', ' ', $post['category'])); ?></span></td>
                                <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                <td><span class="status-badge <?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span></td>
                                <td><?php echo number_format($post['views_count']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="action-btn edit">Edit</a>
                                        <button onclick="deletePost(<?php echo $post['id']; ?>)" class="action-btn delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button class="<?php echo $i === $page ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this blog post? This action cannot be undone.')) {
        return;
    }
    
    window.location.href = 'blog_delete.php?id=' + postId;
}
</script>

</body>
</html>