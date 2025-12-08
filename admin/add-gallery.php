<?php
/**
 * IMAR Group Admin Panel - Add Gallery Item (Production-ready)
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth($conn);

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Admin info
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'] ?? 1;

// Messages
$error_message = '';
$success_message = '';

// Base paths
define('BASE_URL', '/Imar-Group-Website/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . BASE_URL . 'Gallery/');

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
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            $error_message = "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
        } elseif ($file['size'] > $max_size) {
            $error_message = "File size exceeds 5MB limit.";
        } else {
            // Create category folder if not exists
            $category_folder = strtoupper($category);
            $upload_dir = UPLOAD_PATH . $category_folder . '/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'gallery_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;

            // URL path for DB
            $db_path = BASE_URL . 'Gallery/' . $category_folder . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Create thumbnail
                $db_thumbnail = createThumbnail($file_path, $upload_dir, $filename);

                // Insert into database
                $stmt = $conn->prepare("INSERT INTO gallery (title, description, image_path, thumbnail_path, category, size_class, is_featured, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssiiis", $title, $description, $db_path, $db_thumbnail, $category, $size_class, $is_featured, $display_order, $status, $admin_id);

                if ($stmt->execute()) {
                    $gallery_id = $stmt->insert_id;
                    $auth->logActivity($admin_id, 'added_gallery_item', 'gallery', $gallery_id);
                    $success_message = "Gallery item added successfully!";
                    header("refresh:2;url=gallery.php");
                } else {
                    $error_message = "Database error: " . $stmt->error;
                    unlink($file_path);
                    if ($db_thumbnail && file_exists($db_thumbnail)) unlink($db_thumbnail);
                }
            } else {
                $error_message = "Failed to upload image. Check directory permissions.";
            }
        }
    }
}

// Thumbnail function
function createThumbnail($source, $dest_dir, $filename) {
    $max_width = 400;
    $max_height = 300;
    $image_info = getimagesize($source);
    if (!$image_info) return null;
    list($width, $height, $type) = $image_info;

    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG: $src_image = imagecreatefromjpeg($source); break;
        case IMAGETYPE_PNG: $src_image = imagecreatefrompng($source); break;
        case IMAGETYPE_GIF: $src_image = imagecreatefromgif($source); break;
        case IMAGETYPE_WEBP: $src_image = imagecreatefromwebp($source); break;
        default: return null;
    }

    $thumb = imagecreatetruecolor($new_width, $new_height);
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    imagecopyresampled($thumb, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $thumb_path = $dest_dir . 'thumb_' . $filename;
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $thumb_path, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $thumb_path, 8); break;
        case IMAGETYPE_GIF: imagegif($thumb, $thumb_path); break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $thumb_path, 85); break;
    }

    imagedestroy($src_image);
    imagedestroy($thumb);

    // Return URL path for DB
    return BASE_URL . 'Gallery/' . strtoupper($_POST['category']) . '/thumb_' . $filename;
}

// Suggested display order
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
        /* Include all your existing styling here */
        .form-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #0f172a; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea, .form-group select, .form-group input[type="number"] { width: 100%; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #4f46e5; outline: none; }
        .image-upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s; }
        .image-upload-area:hover, .image-upload-area.dragover { border-color: #4f46e5; background: #eef2ff; }
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
    <!-- Include your sidebar and header HTML here -->
    <div class="main-content">
        <h1>Add New Gallery Item</h1>
        <?php if ($success_message): ?>
            <div class="alert alert-success">✓ <?php echo $success_message; ?> Redirecting...</div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error">✗ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="galleryForm">
            <div class="form-container">
                <!-- Image Upload -->
                <div class="form-group">
                    <label>Image <span class="required">*</span></label>
                    <div class="image-upload-area" id="uploadArea">
                        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" required>
                        <img id="imagePreview" class="image-preview" alt="Preview">
                        <p>Click or drag to upload (JPG, PNG, GIF, WebP, max 5MB)</p>
                    </div>
                </div>
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Gallery Title" required>
                </div>
                <!-- Category -->
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="all">All</option>
                        <option value="offices">Offices</option>
                        <option value="team">Team</option>
                        <option value="events">Events</option>
                        <option value="awards">Awards</option>
                    </select>
                </div>
                <!-- Display Order -->
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="<?php echo $suggested_order; ?>">
                </div>
                <!-- Featured -->
                <div class="checkbox-group">
                    <input type="checkbox" id="is_featured" name="is_featured">
                    <label for="is_featured">Mark as Featured</label>
                </div>
                <!-- Submit -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Add Gallery Item</button>
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

imageInput.addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', e => { uploadArea.classList.remove('dragover'); });
uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    imageInput.files = e.dataTransfer.files;
    const reader = new FileReader();
    reader.onload = function(e){
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'block';
    };
    reader.readAsDataURL(e.dataTransfer.files[0]);
});
</script>

</body>
</html>
