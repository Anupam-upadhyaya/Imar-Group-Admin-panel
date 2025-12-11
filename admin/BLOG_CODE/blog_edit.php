<?php
/**
 * File: admin/blog_edit.php
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
$admin_initials = strtoupper(substr($admin_name, 0, 1));

// Get post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = $blogManager->getPostById($post_id);

if (!$post) {
    header('Location: blog.php?error=Post+not+found');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_file = !empty($_FILES['featured_image']['name']) ? $_FILES['featured_image'] : null;
    $result = $blogManager->updatePost($post_id, $_POST, $image_file);
    
    if ($result['success']) {
        header('Location: blog.php?success=' . urlencode($result['message']));
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blog Post - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.tiny.mce.com/1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .form-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-family: inherit; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .image-preview { margin-top: 12px; max-width: 300px; }
        .image-preview img { width: 100%; border-radius: 8px; }
        .form-actions { display: flex; gap: 12px; margin-top: 30px; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .form-hint { font-size: 13px; color: #6b7280; margin-top: 6px; }
        .current-image { margin-top: 12px; padding: 12px; background: #f9fafb; border-radius: 8px; }
        .current-image img { max-width: 200px; border-radius: 6px; }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm13.66-2.66l6.94 6.94-6.94 6.94-6.94-6.94 6.94-6.94z"/></svg>
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
            <a href="blog.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/></svg>
                <span>Blog Posts</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Edit Blog Post</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                </div>
                <a href="blog.php" class="btn-secondary" style="padding: 8px 16px; text-decoration: none; display: inline-block; border-radius: 6px;">← Back</a>
            </div>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Post Title *</label>
                    <input type="text" id="title" name="title" required maxlength="255" 
                           value="<?php echo htmlspecialchars($post['title']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="tax-planning" <?php echo $post['category'] === 'tax-planning' ? 'selected' : ''; ?>>Tax Planning</option>
                            <option value="investment" <?php echo $post['category'] === 'investment' ? 'selected' : ''; ?>>Investment</option>
                            <option value="retirement" <?php echo $post['category'] === 'retirement' ? 'selected' : ''; ?>>Retirement</option>
                            <option value="news" <?php echo $post['category'] === 'news' ? 'selected' : ''; ?>>News</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $post['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt *</label>
                    <textarea id="excerpt" name="excerpt" required rows="3"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="author_name">Author Name</label>
                        <input type="text" id="author_name" name="author_name" value="<?php echo htmlspecialchars($post['author_name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="read_time">Read Time (minutes)</label>
                        <input type="number" id="read_time" name="read_time" value="<?php echo $post['read_time']; ?>" min="1" max="60">
                    </div>
                </div>

                <div class="form-group">
                    <label for="featured_image">Featured Image</label>
                    <?php if ($post['featured_image']): ?>
                        <div class="current-image">
                            <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280;">Current Image:</p>
                            <img src="<?php echo BASE_URL; ?>uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Current">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*" onchange="previewImage(this)" style="margin-top: 12px;">
                    <div class="form-hint">Upload new image to replace current one. Max 5MB</div>
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <label for="meta_title">SEO Title</label>
                    <input type="text" id="meta_title" name="meta_title" maxlength="255" 
                           value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="meta_description">SEO Description</label>
                    <textarea id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Post</button>
                    <a href="blog.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
tinymce.init({
    selector: '#content',
    height: 500,
    menubar: true,
    plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'],
    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.6; }',
    branding: false
});

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>