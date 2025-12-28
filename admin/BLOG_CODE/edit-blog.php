<?php
session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';
require_once '../../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/avatar-helper.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$currentUser = getCurrentUserAvatar($conn, $admin_id);

$admin_name = $currentUser['name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $currentUser['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $currentUser['role'] ?? $_SESSION['admin_role'] ?? 'editor';
$admin_avatar = $currentUser['avatar'] ?? null;
$admin_initials = strtoupper(substr($admin_name, 0, 1));

$avatarUrl = getAvatarPath($admin_avatar, __DIR__);


$error_message = '';
$success_message = '';

$document_root = $_SERVER['DOCUMENT_ROOT'];
$upload_base_abs = $document_root . '/Imar-Group-Website/images/blog/';
$upload_base_url = 'images/blog/';

if (!file_exists($upload_base_abs)) {
    mkdir($upload_base_abs, 0755, true);
}

$blog_id = (int)($_GET['id'] ?? 0);

if (!$blog_id) {
    header('Location: blog.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog_post = $result->fetch_assoc();

if (!$blog_post) {
    header('Location: blog.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $author = trim($_POST['author'] ?? $admin_name);
    $tags = trim($_POST['tags'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $read_time = (int)($_POST['read_time'] ?? 5);
    $published_date = !empty($_POST['published_date']) ? $_POST['published_date'] : date('Y-m-d');
    
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } elseif (empty($category)) {
        $error_message = "Category is required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $blog_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        
        $featured_image = $blog_post['featured_image'];
        $thumbnail = $blog_post['thumbnail'];
        
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['featured_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                $document_root = $_SERVER['DOCUMENT_ROOT'];
                if ($blog_post['featured_image']) {
                    $old_image = $document_root . '/Imar-Group-Website/' . $blog_post['featured_image'];
                    if (file_exists($old_image)) @unlink($old_image);
                }
                if ($blog_post['thumbnail']) {
                    $old_thumb = $document_root . '/Imar-Group-Website/' . $blog_post['thumbnail'];
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                
                if (!file_exists($upload_base_abs)) {
                    mkdir($upload_base_abs, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path_abs = $upload_base_abs . $filename;
                $featured_image = $upload_base_url . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    $thumbnail_filename = 'thumb_' . $filename;
                    if (createThumbnail($file_path_abs, $upload_base_abs, $thumbnail_filename)) {
                        $thumbnail = $upload_base_url . $thumbnail_filename;
                    }
                } else {
                    $error_message = "Failed to upload new image.";
                }
            }
        }
        
        if (empty($error_message)) {
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($content), 0, 150) . '...';
            }
            
            $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, thumbnail = ?, category = ?, author = ?, tags = ?, status = ?, is_featured = ?, display_order = ?, read_time = ?, published_date = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssiissi", $title, $slug, $excerpt, $content, $featured_image, $thumbnail, $category, $author, $tags, $status, $is_featured, $display_order, $read_time, $published_date, $blog_id);
            
            if ($stmt->execute()) {
                $auth->logActivity($admin_id, 'updated_blog_post', 'blog_posts', $blog_id);
                
                $success_message = "Blog post updated successfully!";
                
                $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
                $stmt->bind_param("i", $blog_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $blog_post = $result->fetch_assoc();
                
                echo "<script>setTimeout(function() { window.location.href = 'blog.php'; }, 2000);</script>";
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}

function createThumbnail($source, $dest_dir, $thumbnail_filename) {
    if (!extension_loaded('gd')) return false;
    
    $max_width = 400;
    $max_height = 300;
    
    $image_info = @getimagesize($source);
    if (!$image_info) return false;
    
    list($width, $height, $type) = $image_info;
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    $src_image = false;
    switch ($type) {
        case IMAGETYPE_JPEG: $src_image = @imagecreatefromjpeg($source); break;
        case IMAGETYPE_PNG: $src_image = @imagecreatefrompng($source); break;
        case IMAGETYPE_GIF: $src_image = @imagecreatefromgif($source); break;
        case IMAGETYPE_WEBP: if (function_exists('imagecreatefromwebp')) $src_image = @imagecreatefromwebp($source); break;
    }
    
    if (!$src_image) return false;
    
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
    }
    
    imagecopyresampled($thumb, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    $thumb_path = $dest_dir . $thumbnail_filename;
    $saved = false;
    
    switch ($type) {
        case IMAGETYPE_JPEG: $saved = imagejpeg($thumb, $thumb_path, 85); break;
        case IMAGETYPE_PNG: $saved = imagepng($thumb, $thumb_path, 8); break;
        case IMAGETYPE_GIF: $saved = imagegif($thumb, $thumb_path); break;
        case IMAGETYPE_WEBP: if (function_exists('imagewebp')) $saved = imagewebp($thumb, $thumb_path, 85); break;
    }
    
    imagedestroy($src_image);
    imagedestroy($thumb);
    
    return $saved;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blog Post - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .form-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #0f172a; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea, .form-group select, .form-group input[type="number"], .form-group input[type="date"] { width: 100%; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #4f46e5; outline: none; }
        .form-group textarea { resize: vertical; min-height: 100px; font-family: inherit; }
        .form-group textarea.content-editor { min-height: 400px; }
        .current-image { border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; background: #f9fafb; margin-bottom: 15px; }
        .current-image img { max-width: 100%; max-height: 300px; border-radius: 8px; }
        .current-image p { margin-top: 10px; color: #6b7280; font-size: 13px; }
        .image-upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s; }
        .image-upload-area:hover { border-color: #4f46e5; background: #eef2ff; }
        .image-preview { max-width: 100%; max-height: 300px; margin-top: 15px; border-radius: 8px; display: none; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-row-three { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .btn-group { display: flex; gap: 10px; margin-top: 30px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.3s; font-size: 14px; }
        .btn-primary { background: #4f46e5; color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .required { color: #ef4444; }
        .helper-text { font-size: 12px; color: #6b7280; margin-top: 5px; }
        .char-count { font-size: 12px; color: #6b7280; text-align: right; margin-top: 5px; }
    </style>
</head>
<body>

<div class="dashboard">
   <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Edit Blog Post</h1>
                <p style="color: #6b7280; margin-top: 5px;">Update blog post details and content</p>
            </div>
            <div class="header-actions">
                <div class="user-info">
                 <div class="user-avatar">
<?php if ($avatarUrl): ?>
<img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
          alt="<?php echo htmlspecialchars($admin_name); ?>"
          onerror="this.outerHTML='<span><?php echo $admin_initials; ?></span>';">
          <?php else: ?>
          <?php echo $admin_initials; ?>
          <?php endif; ?>
</div>
<div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">✓ <?php echo $success_message; ?> Redirecting...</div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">✗ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="blogForm">
            <div class="form-container">
                <div class="form-group">
                    <label>Current Featured Image</label>
                    <div class="current-image">
                        <img src="../../../Imar-Group-Website/<?php echo htmlspecialchars($blog_post['featured_image'] ?: 'images/blog/default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($blog_post['title']); ?>"
                             onerror="this.src='../assets/placeholder.jpg'">
                        <p>Uploaded: <?php echo date('M d, Y', strtotime($blog_post['created_at'])); ?></p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Replace Featured Image (Optional)</label>
                    <div class="image-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload new image</h3>
                        <p style="color: #9ca3af; font-size: 13px;">JPG, PNG, GIF or WebP (max 5MB)</p>
                        <input type="file" name="featured_image" id="imageInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                    <div class="helper-text">Leave empty to keep current image</div>
                </div>

                <div class="form-group">
                    <label for="title">Blog Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($blog_post['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">URL Slug <span class="required">*</span></label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($blog_post['slug']); ?>" required>
                    <div class="helper-text">SEO-friendly URL identifier</div>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt/Summary</label>
                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="300"><?php echo htmlspecialchars($blog_post['excerpt']); ?></textarea>
                    <div class="char-count"><span id="excerptCount"><?php echo strlen($blog_post['excerpt']); ?></span>/300 characters</div>
                </div>

                <div class="form-group">
                    <label for="content">Blog Content <span class="required">*</span></label>
                    <textarea id="content" name="content" class="content-editor" required><?php echo htmlspecialchars($blog_post['content']); ?></textarea>
                    <div class="helper-text">You can use HTML tags for formatting</div>
                </div>

                <div class="form-row-three">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="tax-planning" <?php echo $blog_post['category'] === 'tax-planning' ? 'selected' : ''; ?>>Tax Planning</option>
                            <option value="investment" <?php echo $blog_post['category'] === 'investment' ? 'selected' : ''; ?>>Investment</option>
                            <option value="retirement" <?php echo $blog_post['category'] === 'retirement' ? 'selected' : ''; ?>>Retirement</option>
                            <option value="news" <?php echo $blog_post['category'] === 'news' ? 'selected' : ''; ?>>News</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($blog_post['author']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $blog_post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $blog_post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $blog_post['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-three">
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($blog_post['tags']); ?>" placeholder="finance, investment, tips">
                        <div class="helper-text">Comma-separated tags</div>
                    </div>

                    <div class="form-group">
                        <label for="read_time">Read Time (minutes)</label>
                        <input type="number" id="read_time" name="read_time" value="<?php echo $blog_post['read_time']; ?>" min="1">
                    </div>

                    <div class="form-group">
                        <label for="published_date">Published Date</label>
                        <input type="date" id="published_date" name="published_date" value="<?php echo $blog_post['published_date']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $blog_post['display_order']; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group" style="margin-top: 28px;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?php echo $blog_post['is_featured'] ? 'checked' : ''; ?>>
                            <label for="is_featured" style="margin: 0;">Mark as Featured Post</label>
                        </div>
                    </div>
                </div>

                <div class="form-row" style="padding: 20px; background: #f9fafb; border-radius: 8px; margin-top: 20px;">
                    <div style="font-size: 14px; color: #6b7280;">
                        <strong>Created:</strong> <?php echo date('M d, Y h:i A', strtotime($blog_post['created_at'])); ?>
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <strong>Views:</strong> <?php echo number_format($blog_post['views']); ?>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                        Update Blog
                    </button>
                    <a href="gallery.php" class="btn btn-secondary">Cancel</a>
                </div>

              <script>
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');

uploadArea.addEventListener('click', () => imageInput.click());

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', () => { uploadArea.classList.remove('dragover'); });
uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        imageInput.files = e.dataTransfer.files;
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
