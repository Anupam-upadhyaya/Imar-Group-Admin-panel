<?php
/**
 * IMAR Group Admin Panel - Add Blog Post
 * File: admin/BLOG_CODE/add-blog.php
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

$error_message = '';
$success_message = '';

// Define paths
$upload_base_abs = dirname(dirname(dirname(__DIR__))) . '/Imar-Group-Website/images/blog/';
$upload_base_url = 'images/blog/';

// Handle form submission
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
    
    // Auto-generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
    
    // Validation
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } elseif (empty($category)) {
        $error_message = "Category is required.";
    } else {
        // Check if slug already exists
        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        
        $featured_image = null;
        $thumbnail = null;
        
        // Process image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['featured_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                if (!file_exists($upload_base_abs)) {
                    mkdir($upload_base_abs, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path_abs = $upload_base_abs . $filename;
                $featured_image = $upload_base_url . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    // Create thumbnail
                    $thumbnail_filename = 'thumb_' . $filename;
                    if (createThumbnail($file_path_abs, $upload_base_abs, $thumbnail_filename)) {
                        $thumbnail = $upload_base_url . $thumbnail_filename;
                    }
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }
        
        if (empty($error_message)) {
            // Auto-generate excerpt if empty
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($content), 0, 150) . '...';
            }
            
            $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, thumbnail, category, author, tags, status, is_featured, display_order, read_time, published_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssiissi", $title, $slug, $excerpt, $content, $featured_image, $thumbnail, $category, $author, $tags, $status, $is_featured, $display_order, $read_time, $published_date, $admin_id);
            
            if ($stmt->execute()) {
                $blog_id = $stmt->insert_id;
                $auth->logActivity($admin_id, 'added_blog_post', 'blog_posts', $blog_id);
                
                $success_message = "Blog post created successfully!";
                echo "<script>setTimeout(function() { window.location.href = 'blog.php'; }, 2000);</script>";
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}

// Thumbnail creation function
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

$max_order = $conn->query("SELECT MAX(display_order) as max_order FROM blog_posts")->fetch_assoc()['max_order'] ?? 0;
$suggested_order = $max_order + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blog Post - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .form-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="number"],
        .form-group input[type="date"] {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #4f46e5;
            outline: none;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        .form-group textarea.content-editor {
            min-height: 400px;
        }
        .image-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload-area:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-row-three {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .required {
            color: #ef4444;
        }
        .helper-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        .char-count {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>
<body>

   <div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Add New Blog Post</h1>
                <p style="color: #6b7280; margin-top: 5px;">Create and publish a new blog article</p>
            </div>
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

        <form method="POST" enctype="multipart/form-data" id="blogForm">
            <div class="form-container">
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Blog Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Enter blog title" required>
                    <div class="helper-text">This will be the main headline of your blog post</div>
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" placeholder="auto-generated-from-title">
                    <div class="helper-text">Leave empty to auto-generate from title</div>
                </div>

                <!-- Excerpt -->
                <div class="form-group">
                    <label for="excerpt">Excerpt/Summary</label>
                    <textarea id="excerpt" name="excerpt" rows="3" placeholder="Brief summary of the blog post..." maxlength="300"></textarea>
                    <div class="char-count"><span id="excerptCount">0</span>/300 characters</div>
                </div>

                <!-- Featured Image -->
                <div class="form-group">
                    <label>Featured Image</label>
                    <div class="image-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload or drag and drop</h3>
                        <p style="color: #9ca3af; font-size: 13px;">JPG, PNG, GIF or WebP (max 5MB)</p>
                        <input type="file" name="featured_image" id="imageInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                </div>

                <!-- Content -->
                <div class="form-group">
                    <label for="content">Blog Content <span class="required">*</span></label>
                    <textarea id="content" name="content" class="content-editor" placeholder="Write your blog content here..." required></textarea>
                    <div class="helper-text">You can use HTML tags for formatting</div>
                </div>

                <!-- Category, Author, Status -->
                <div class="form-row-three">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="tax-planning">Tax Planning</option>
                            <option value="investment">Investment</option>
                            <option value="retirement">Retirement</option>
                            <option value="news">News</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($admin_name); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <!-- Tags, Read Time, Published Date -->
                <div class="form-row-three">
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" id="tags" name="tags" placeholder="finance, investment, tips">
                        <div class="helper-text">Comma-separated tags</div>
                    </div>

                    <div class="form-group">
                        <label for="read_time">Read Time (minutes)</label>
                        <input type="number" id="read_time" name="read_time" value="5" min="1">
                    </div>

                    <div class="form-group">
                        <label for="published_date">Published Date</label>
                        <input type="date" id="published_date" name="published_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- Display Order & Featured -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $suggested_order; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group" style="margin-top: 28px;">
                            <input type="checkbox" id="is_featured" name="is_featured">
                            <label for="is_featured" style="margin: 0;">Mark as Featured Post</label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        Publish Blog Post
                    </button>
                    <a href="blog.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Image upload functionality
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

// Drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#4f46e5';
    uploadArea.style.background = '#eef2ff';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.borderColor = '#d1d5db';
    uploadArea.style.background = '#f9fafb';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#d1d5db';
    uploadArea.style.background = '#f9fafb';
    
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

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    document.getElementById('slug').value = slug;
});

// Character count for excerpt
document.getElementById('excerpt').addEventListener('input', function() {
    document.getElementById('excerptCount').textContent = this.value.length;
});
</script>

</body>
</html>