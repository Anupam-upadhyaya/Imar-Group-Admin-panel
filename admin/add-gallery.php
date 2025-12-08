<?php
/**
 * IMAR Group Admin Panel - Add Gallery Item
 * File: admin/add-gallery.php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'all';
    $size_class = $_POST['size_class'] ?? 'normal';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error_message = "Please select an image to upload.";
    } else {
        // Process image upload
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error_message = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
        } elseif ($file['size'] > $max_size) {
            $error_message = "File size exceeds 5MB limit.";
        } else {
            // Create upload directory structure
            $upload_base = '../../../Imar-Group-Website/Gallery/';
            $category_folder = strtoupper($category);
            $upload_dir = $upload_base . $category_folder . '/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'gallery_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            // Relative path for database (from website root)
            $db_path = 'Gallery/' . $category_folder . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Create thumbnail (optional but recommended)
                $thumbnail_path = createThumbnail($file_path, $upload_dir, $filename);
                $db_thumbnail = $thumbnail_path ? 'Gallery/' . $category_folder . '/thumb_' . $filename : null;
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO gallery (title, description, image_path, thumbnail_path, category, size_class, is_featured, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssiiis", $title, $description, $db_path, $db_thumbnail, $category, $size_class, $is_featured, $display_order, $status, $admin_id);
                
                if ($stmt->execute()) {
                    $gallery_id = $stmt->insert_id;
                    
                    // Log activity
                    $auth->logActivity($admin_id, 'added_gallery_item', 'gallery', $gallery_id);
                    
                    $success_message = "Gallery item added successfully!";
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=gallery.php");
                } else {
                    $error_message = "Database error: " . $stmt->error;
                    // Delete uploaded file if database insert fails
                    unlink($file_path);
                    if ($thumbnail_path && file_exists($thumbnail_path)) {
                        unlink($thumbnail_path);
                    }
                }
            } else {
                $error_message = "Failed to upload image. Check directory permissions.";
            }
        }
    }
}

// Function to create thumbnail
function createThumbnail($source, $dest_dir, $filename) {
    $max_width = 400;
    $max_height = 300;
    
    $image_info = getimagesize($source);
    if (!$image_info) return false;
    
    list($width, $height, $type) = $image_info;
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $src_image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $src_image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    // Create thumbnail
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    $thumb_path = $dest_dir . 'thumb_' . $filename;
    
    // Save thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $thumb_path, 85);
            break;
    }
    
    imagedestroy($src_image);
    imagedestroy($thumb);
    
    return $thumb_path;
}

// Get max display order for suggestion
$max_order_result = $conn->query("SELECT MAX(display_order) as max_order FROM gallery");
$max_order = $max_order_result->fetch_assoc()['max_order'] ?? 0;
$suggested_order = $max_order + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Gallery Item - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .form-container {
            max-width: 800px;
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
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
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
        
        .image-upload-area.dragover {
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
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>Inquiries</span>
            </a>
            <a href="gallery.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                <span>Gallery</span>
            </a>
            <a href="blog.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="users.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span>Users</span>
            </a>
            <a href="settings.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Add New Gallery Item</h1>
                <p style="color: #6b7280; margin-top: 5px;">Upload and configure a new gallery image</p>
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
            <div class="alert alert-success">
                ✓ <?php echo $success_message; ?> Redirecting...
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ✗ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="galleryForm">
            <div class="form-container">
                <!-- Image Upload -->
                <div class="form-group">
                    <label>Image <span class="required">*</span></label>
                    <div class="image-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload or drag and drop</h3>
                        <p style="color: #9ca3af; font-size: 13px;">JPG, PNG, GIF or WebP (max 5MB)</p>
                        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" required>
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="e.g., Annual Financial Summit" required>
                    <div class="helper-text">This will be displayed when users hover over the image</div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter a brief description..."></textarea>
                    <div class="helper-text">Optional additional context about the image</div>
                </div>

                <!-- Category and Size Class -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="all">All</option>
                            <option value="offices">Offices</option>
                            <option value="team">Team</option>
                            <option value="events">Events</option>
                            <option value="awards">Awards</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="size_class">Size Class</label>
                        <select id="size_class" name="size_class">
                            <option value="normal">Normal (1x1)</option>
                            <option value="large">Large (2x2)</option>
                            <option value="tall">Tall (1x2)</option>
                            <option value="wide">Wide (2x1)</option>
                        </select>
                        <div class="helper-text">Controls the display size in the gallery grid</div>
                    </div>
                </div>

                <!-- Display Order and Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $suggested_order; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first (suggested: <?php echo $suggested_order; ?>)</div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending Review</option>
                        </select>
                    </div>
                </div>

                <!-- Featured Checkbox -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <label for="is_featured" style="margin: 0;">Mark as Featured</label>
                    </div>
                    <div class="helper-text">Featured items appear in the carousel at the top</div>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        Add Gallery Item
                    </button>
                    <a href="gallery.php" class="btn btn-secondary">Cancel</a>
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
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

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

// Auto-generate title from filename
imageInput.addEventListener('change', function(e) {
    const titleInput = document.getElementById('title');
    if (!titleInput.value && e.target.files[0]) {
        const filename = e.target.files[0].name;
        const titleFromFile = filename
            .replace(/\.[^/.]+$/, '') // Remove extension
            .replace(/[_-]/g, ' ') // Replace _ and - with spaces
            .replace(/\b\w/g, l => l.toUpperCase()); // Capitalize words
        titleInput.value = titleFromFile;
    }
});

// Form validation
document.getElementById('galleryForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const image = imageInput.files[0];
    
    if (!title) {
        e.preventDefault();
        alert('Please enter a title');
        return false;
    }
    
    if (!image) {
        e.preventDefault();
        alert('Please select an image');
        return false;
    }
    
    // Check file size (5MB)
    if (image.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('Image size must be less than 5MB');
        return false;
    }
});
</script>

</body>
</html>