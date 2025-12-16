<?php
/**
 * IMAR Group Admin Panel - View Blog Post
 * File: admin/BLOG_CODE/view-blog.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

// Get blog post ID
$blog_id = $_GET['id'] ?? 0;

if (!$blog_id) {
    header('Location: blog.php');
    exit();
}

// Fetch blog post details
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog_post = $result->fetch_assoc();

if (!$blog_post) {
    header('Location: blog.php');
    exit();
}

// Log activity - admin viewed this post
$auth->logActivity($admin_id, 'viewed_blog_post', 'blog_posts', $blog_id);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blog']) && $admin_role !== 'editor') {
    // Get image paths before deleting
    $featured_image = $blog_post['featured_image'];
    $thumbnail = $blog_post['thumbnail'];
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $blog_id);
    $stmt->execute();
    
    // Delete image files
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    if ($featured_image) {
        $image_path = $document_root . '/Imar-Group-Website/' . $featured_image;
        if (file_exists($image_path)) @unlink($image_path);
    }
    if ($thumbnail) {
        $thumb_path = $document_root . '/Imar-Group-Website/' . $thumbnail;
        if (file_exists($thumb_path)) @unlink($thumb_path);
    }
    
    // Log activity
    $auth->logActivity($admin_id, 'deleted_blog_post', 'blog_posts', $blog_id);
    
    header('Location: blog.php?deleted=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Blog Post #<?php echo $blog_id; ?> - IMAR Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .blog-detail-container {
            max-width: 900px;
        }
        
        .blog-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .blog-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .blog-title h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #0f172a;
        }
        
        .blog-meta {
            font-size: 14px;
            color: #6b7280;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .blog-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: auto;
        }
        
        .status-badge.published { 
            background: #d1fae5; 
            color: #065f46; 
        }
        
        .status-badge.draft { 
            background: #fef3c7; 
            color: #92400e; 
        }
        
        .status-badge.archived { 
            background: #f3f4f6; 
            color: #6b7280; 
        }
        
        .badge-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-category {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .badge-featured {
            background: #fef3c7;
            color: #92400e;
        }
        
        .featured-image-section {
            margin-bottom: 30px;
        }
        
        .featured-image-section img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .excerpt-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4f46e5;
            margin-bottom: 30px;
        }
        
        .excerpt-section h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #0f172a;
        }
        
        .excerpt-section p {
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
        }
        
        .content-section {
            margin-bottom: 30px;
        }
        
        .content-section h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .blog-content {
            font-size: 15px;
            line-height: 1.8;
            color: #374151;
        }
        
        .blog-content h1, .blog-content h2, .blog-content h3 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #0f172a;
        }
        
        .blog-content p {
            margin-bottom: 15px;
        }
        
        .blog-content ul, .blog-content ol {
            margin-left: 25px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-item label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .info-item value {
            display: block;
            font-size: 18px;
            color: #0f172a;
            font-weight: 600;
        }
        
        .tags-section {
            margin-bottom: 30px;
        }
        
        .tags-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .tag-item {
            background: #e5e7eb;
            color: #374151;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .action-section {
            padding-top: 25px;
            border-top: 2px solid #f3f4f6;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Blog Post Details</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="blog-detail-container">
            <a href="blog.php" class="back-link">&larr; Back to Blog Posts</a>

            <div class="blog-card">
                <!-- Header -->
                <div class="blog-header">
                    <div class="blog-title">
                        <h2><?php echo htmlspecialchars($blog_post['title']); ?></h2>
                        <div class="blog-meta">
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                <?php echo htmlspecialchars($blog_post['author']); ?>
                            </span>
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                                <?php echo date('F d, Y', strtotime($blog_post['published_date'])); ?>
                            </span>
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <?php echo number_format($blog_post['views']); ?> views
                            </span>
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                                <?php echo $blog_post['read_time']; ?> min read
                            </span>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $blog_post['status']; ?>">
                        <?php echo ucfirst($blog_post['status']); ?>
                    </span>
                </div>

                <!-- Badges -->
                <div class="badge-row">
                    <span class="badge badge-category"><?php echo ucfirst(str_replace('-', ' ', $blog_post['category'])); ?></span>
                    <?php if ($blog_post['is_featured']): ?>
                        <span class="badge badge-featured">★ Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="info-grid">
                    <div class="info-item">
                        <label>Created</label>
                        <value><?php echo date('M d, Y', strtotime($blog_post['created_at'])); ?></value>
                    </div>
                    <div class="info-item">
                        <label>Last Updated</label>
                        <value><?php echo date('M d, Y', strtotime($blog_post['updated_at'])); ?></value>
                    </div>
                    <div class="info-item">
                        <label>Display Order</label>
                        <value><?php echo $blog_post['display_order']; ?></value>
                    </div>
                </div>

                <!-- Featured Image -->
                <?php if ($blog_post['featured_image']): ?>
                <div class="featured-image-section">
                    <img src="../../../Imar-Group-Website/<?php echo htmlspecialchars($blog_post['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($blog_post['title']); ?>"
                         onerror="this.src='../assets/placeholder.jpg'">
                </div>
                <?php endif; ?>

                <!-- Excerpt -->
                <?php if ($blog_post['excerpt']): ?>
                <div class="excerpt-section">
                    <h3>Excerpt</h3>
                    <p><?php echo htmlspecialchars($blog_post['excerpt']); ?></p>
                </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="content-section">
                    <h3>Full Content</h3>
                    <div class="blog-content">
                        <?php echo $blog_post['content']; ?>
                    </div>
                </div>

                <!-- Tags -->
                <?php if ($blog_post['tags']): ?>
                <div class="tags-section">
                    <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #6b7280;">Tags</h3>
                    <div class="tags-list">
                        <?php 
                        $tags = explode(',', $blog_post['tags']);
                        foreach ($tags as $tag): 
                        ?>
                            <span class="tag-item"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- URL Slug Info -->
                <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">URL Slug:</div>
                    <div style="font-size: 14px; color: #4f46e5; font-weight: 500; font-family: monospace;">
                        /blog/<?php echo htmlspecialchars($blog_post['slug']); ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <div class="btn-group">
                        <a href="edit-blog.php?id=<?php echo $blog_post['id']; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                            </svg>
                            Edit Blog Post
                        </a>
                        
                        <?php if ($admin_role !== 'editor'): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this blog post? This action cannot be undone.');" style="display: inline;">
                            <button type="submit" name="delete_blog" class="btn btn-delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                </svg>
                                Delete Blog Post
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="blog.php" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>