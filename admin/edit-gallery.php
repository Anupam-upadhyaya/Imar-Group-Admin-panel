<?php
/**
 * IMAR Group Admin Panel - Edit Gallery Item
 * File: admin/edit-gallery.php
 * Fixed with absolute paths
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

$error_message = '';
$success_message = '';

// Define paths
$document_root = $_SERVER['DOCUMENT_ROOT'];
$upload_base_abs = $document_root . '/Imar-Group-Website/Gallery/';
$upload_base_url = 'Gallery/';

// Get gallery item ID
$gallery_id = (int)($_GET['id'] ?? 0);

if (!$gallery_id) {
    header('Location: gallery.php');
    exit();
}

// Fetch existing gallery item
$stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ?");
$stmt->bind_param("i", $gallery_id);
$stmt->execute();
$result = $stmt->get_result();
$gallery_item = $result->fetch_assoc();

if (!$gallery_item) {
    header('Location: gallery.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'all');
    $size_class = trim($_POST['size_class'] ?? 'normal');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    
    // Validate status value
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        $status = 'active';
    }
    
    // Validation
    if (empty($title)) {
        $error_message = "Title is required.";
    } else {
        $image_path = $gallery_item['image_path'];
        $thumbnail_path = $gallery_item['thumbnail_path'];
        
        // Check if new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                // Delete old image
                $old_image_full = $document_root . '/Imar-Group-Website/' . $gallery_item['image_path'];
                if (file_exists($old_image_full)) {
                    @unlink($old_image_full);
                }
                
                // Delete old thumbnail
                if ($gallery_item['thumbnail_path']) {
                    $old_thumb_full = $document_root . '/Imar-Group-Website/' . $gallery_item['thumbnail_path'];
                    if (file_exists($old_thumb_full)) {
                        @unlink($old_thumb_full);
                    }
                }
                
                // Create category folder
                $category_folder = strtoupper($category);
                $upload_dir_abs = $upload_base_abs . $category_folder . '/';
                
                if (!file_exists($upload_dir_abs)) {
                    mkdir($upload_dir_abs, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'gallery_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path_abs = $upload_dir_abs . $filename;
                
                // Relative path for database
                $image_path = $upload_base_url . $category_folder . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    // Create thumbnail
                    $thumbnail_filename = 'thumb_' . $filename;
                    $thumbnail_created = createThumbnail($file_path_abs, $upload_dir_abs, $thumbnail_filename);
                    $thumbnail_path = $thumbnail_created ? $upload_base_url . $category_folder . '/' . $thumbnail_filename : null;
                } else {
                    $error_message = "Failed to upload new image.";
                }
            }
        }
        
        // Update database if no errors
        if (empty($error_message)) {
            $stmt = $conn->prepare("UPDATE gallery SET title = ?, description = ?, image_path = ?, thumbnail_path = ?, category = ?, size_class = ?, is_featured = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssiisi", $title, $description, $image_path, $thumbnail_path, $category, $size_class, $is_featured, $display_order, $status, $gallery_id);
            
            if ($stmt->execute()) {
                $auth->logActivity($admin_id, 'updated_gallery_item', 'gallery', $gallery_id);
                
                $success_message = "Gallery item updated successfully!";
                
                // Refresh item data
                $stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ?");
                $stmt->bind_param("i", $gallery_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $gallery_item = $result->fetch_assoc();
                
                header("refresh:2;url=gallery.php");
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}

// Thumbnail creation function
function createThumbnail($source, $dest_dir, $thumbnail_filename) {
    if (!extension_loaded('gd')) {
        return false;
    }
    
    $max_width = 400;
    $max_height = 300;
    
    $image_info = @getimagesize($source);
    if (!$image_info) {
        return false;
    }
    
    list($width, $height, $type) = $image_info;
    
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    $src_image = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_image = @imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_image = @imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $src_image = @imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $src_image = @imagecreatefromwebp($source);
            }
            break;
    }
    
    if (!$src_image) {
        return false;
    }
    
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
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($thumb, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($thumb, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($thumb, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $saved = imagewebp($thumb, $thumb_path, 85);
            }
            break;
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
    <title>Edit Gallery Item - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .form-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #0f172a; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea, .form-group select, .form-group input[type="number"] { width: 100%; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #4f46e5; outline: none; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .current-image { border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; background: #f9fafb; margin-bottom: 15px; }
        .current-image img { max-width: 100%; max-height: 300px; border-radius: 8px; }
        .current-image p { margin-top: 10px; color: #6b7280; font-size: 13px; }
        .image-upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s; }
        .image-upload-area:hover { border-color: #4f46e5; background: #eef2ff; }
        .image-preview { max-width: 100%; max-height: 300px; margin-top: 15px; border-radius: 8px; display: none; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
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
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm13.66-2.66l6.94 6.94-6.94 6.94-6.94-6.94 6.94-6.94z"/></svg>
                <div class="sidebar-logo-text"><h2>IMAR Group</h2><p>Admin Panel</p></div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg><span>Dashboard</span></a>
            <a href="inquiries.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg><span>Inquiries</span></a>
            <a href="gallery.php" class="menu-item active"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg><span>Gallery</span></a>
            <a href="blog.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg><span>Blog Posts</span></a>
            <a href="users.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg><span>Users</span></a>
            <a href="settings.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg><span>Settings</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <div><h1>Edit Gallery Item</h1><p style="color: #6b7280; margin-top: 5px;">Update gallery item details and image</p></div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
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

        <form method="POST" enctype="multipart/form-data" id="galleryForm">
            <div class="form-container">
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image">
                        <img src="/Imar-Group-Website/<?php echo htmlspecialchars($gallery_item['image_path']); ?>" alt="<?php echo htmlspecialchars($gallery_item['title']); ?>" onerror="this.src='../assets/placeholder.jpg'">
                        <p>Uploaded: <?php echo date('M d, Y', strtotime($gallery_item['created_at'])); ?></p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Replace Image (Optional)</label>
                    <div class="image-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload new image</h3>
                        <p style="color: #9ca3af; font-size: 13px;">JPG, PNG, GIF or WebP (max 5MB)</p>
                        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                    <div class="helper-text">Leave empty to keep current image</div>
                </div>

                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($gallery_item['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($gallery_item['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="all" <?php echo $gallery_item['category'] === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="offices" <?php echo $gallery_item['category'] === 'offices' ? 'selected' : ''; ?>>Offices</option>
                            <option value="team" <?php echo $gallery_item['category'] === 'team' ? 'selected' : ''; ?>>Team</option>
                            <option value="events" <?php echo $gallery_item['category'] === 'events' ? 'selected' : ''; ?>>Events</option>
                            <option value="awards" <?php echo $gallery_item['category'] === 'awards' ? 'selected' : ''; ?>>Awards</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="size_class">Size Class</label>
                        <select id="size_class" name="size_class">
                            <option value="normal" <?php echo $gallery_item['size_class'] === 'normal' ? 'selected' : ''; ?>>Normal (1x1)</option>
                            <option value="large" <?php echo $gallery_item['size_class'] === 'large' ? 'selected' : ''; ?>>Large (2x2)</option>
                            <option value="tall" <?php echo $gallery_item['size_class'] === 'tall' ? 'selected' : ''; ?>>Tall (1x2)</option>
                            <option value="wide" <?php echo $gallery_item['size_class'] === 'wide' ? 'selected' : ''; ?>>Wide (2x1)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $gallery_item['display_order']; ?>" min="0">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $gallery_item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $gallery_item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo $gallery_item['status'] === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" <?php echo $gallery_item['is_featured'] ? 'checked' : ''; ?>>
                        <label for="is_featured" style="margin: 0;">Mark as Featured</label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                        Update Gallery Item
                    </button>
                    <a href="gallery.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
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

</body>
</html>