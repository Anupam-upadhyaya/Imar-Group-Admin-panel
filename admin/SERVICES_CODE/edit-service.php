<?php
/**
 * IMAR Group Admin Panel - Edit Service (FIXED)
 * File: admin/SERVICES_CODE/edit-service.php
 * 
 * FIXES:
 * 1. Proper icon path display using 4-level traversal (../../../../)
 * 2. Consistent path handling with Gallery and Services modules
 * 3. Maintain existing icon if no new upload
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

// FIX: Use capital I in Images to match actual folder structure
$upload_base_abs = dirname(dirname(dirname(__DIR__))) . '/Imar-Group-Website/Images/Services/';
$upload_base_url = 'Images/Services/'; // Database path (capital I)

// Create directory if it doesn't exist
if (!file_exists($upload_base_abs)) {
    mkdir($upload_base_abs, 0755, true);
}

// Get service ID
$service_id = (int)($_GET['id'] ?? 0);

if (!$service_id) {
    header('Location: services.php');
    exit();
}

// Fetch existing service
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    header('Location: services.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $full_content = $_POST['full_content'] ?? '';
    $category = trim($_POST['category'] ?? 'general');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $has_offer = isset($_POST['has_offer']) ? 1 : 0;
    $offer_text = trim($_POST['offer_text'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($short_description)) {
        $error_message = "Short description is required.";
    } else {
        // Check if slug already exists (excluding current service)
        $stmt = $conn->prepare("SELECT id FROM services WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $service_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        
        // FIX: Keep existing icon by default
        $icon_path = $service['icon_path'];
        
        // Check if new icon is uploaded
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['icon'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $max_size = 2 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, WebP, and SVG are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "File size exceeds 2MB limit.";
            } else {
                // Delete old icon if it exists
                if ($service['icon_path']) {
                    $old_icon_abs = dirname(dirname(dirname(__DIR__))) . '/Imar-Group-Website/' . $service['icon_path'];
                    if (file_exists($old_icon_abs)) {
                        @unlink($old_icon_abs);
                        error_log("✓ Deleted old icon: " . $old_icon_abs);
                    }
                }
                
                // Ensure directory exists
                if (!file_exists($upload_base_abs)) {
                    mkdir($upload_base_abs, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path_abs = $upload_base_abs . $filename;
                
                // FIX: Store with capital I (Images/Services/) to match actual folder
                $icon_path = $upload_base_url . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    $error_message = "Failed to upload new icon. Check directory permissions.";
                    // Revert to old icon on upload failure
                    $icon_path = $service['icon_path'];
                } else {
                    error_log("✓ New icon uploaded: " . $file_path_abs);
                    error_log("✓ Database path: " . $icon_path);
                }
            }
        }
        
        if (empty($error_message)) {
            $stmt = $conn->prepare("UPDATE services SET title = ?, slug = ?, icon_path = ?, short_description = ?, full_content = ?, category = ?, is_featured = ?, has_offer = ?, offer_text = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssiiissi", $title, $slug, $icon_path, $short_description, $full_content, $category, $is_featured, $has_offer, $offer_text, $display_order, $status, $service_id);
            
            if ($stmt->execute()) {
                $auth->logActivity($admin_id, 'updated_service', 'services', $service_id);
                
                $success_message = "Service updated successfully!";
                
                // Refresh service data
                $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->bind_param("i", $service_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $service = $result->fetch_assoc();
                
                // Add cache clearing and redirect
                echo "<script>
                    // Clear cache
                    if ('caches' in window) {
                        caches.keys().then(function(names) {
                            names.forEach(function(name) {
                                caches.delete(name);
                            });
                        });
                    }
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'services.php?refresh=' + Date.now();
                    }, 2000);
                </script>";
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .form-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #0f172a; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea, .form-group select, .form-group input[type="number"] { width: 100%; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #4f46e5; outline: none; }
        .form-group textarea { resize: vertical; min-height: 100px; font-family: inherit; }
        .form-group textarea.content-editor { min-height: 400px; }
        .current-icon { border: 2px solid #0557faff; border-radius: 12px; padding: 20px; text-align: center; background: #0b9c7393; margin-bottom: 15px; position: relative; }
        .current-icon img { max-width: 150px; max-height: 150px; object-fit: contain; display: block; margin: 0 auto; }
        .current-icon .no-icon { color: #9ca3af; font-style: italic; padding: 40px 20px; }
        .current-icon p { margin-top: 15px; color: #ffffffff; font-size: 13px; }
        .current-icon .icon-path { margin-top: 8px; font-size: 11px; color: #9ca3af; font-family: monospace; word-break: break-all; }
        .icon-upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s; }
        .icon-upload-area:hover { border-color: #4f46e5; background: #eef2ff; }
        .icon-upload-area.dragover { border-color: #4f46e5; background: #eef2ff; }
        .icon-preview { max-width: 120px; max-height: 120px; margin: 15px auto 0; border-radius: 8px; display: none; }
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
        .upload-info { background: #eff6ff; border: 1px solid #3b82f6; padding: 12px; border-radius: 8px; margin-top: 10px; font-size: 12px; color: #1e40af; display: none; }
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Edit Service</h1>
                <p style="color: #6b7280; margin-top: 5px;">Update service details and content</p>
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

        <form method="POST" enctype="multipart/form-data" id="serviceForm">
            <div class="form-container">
                <!-- Current Icon Display -->
                <div class="form-group">
                    <label>Current Service Icon</label>
                    <div class="current-icon">
                        <?php if (!empty($service['icon_path'])): ?>
                            <?php
                            // FIX: Use 4-level traversal (../../../../) to match services.php display pattern
                            $icon_display_path = '../../../../Imar-Group-Website/' . htmlspecialchars($service['icon_path']);
                            ?>
                            <img src="<?php echo $icon_display_path; ?>" 
                                 alt="<?php echo htmlspecialchars($service['title']); ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="no-icon" style="display: none;">
                                <p style="color: #ef4444; margin-top: 10px;">Image failed to load</p>
                            </div>
                            <p>📅 Uploaded: <?php echo date('M d, Y h:i A', strtotime($service['created_at'])); ?></p>
                        <?php else: ?>
                            <div class="no-icon">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="#9ca3af" style="margin: 0 auto;">
                                    <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
                                </svg>
                                <p>No icon uploaded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Replace Icon -->
                <div class="form-group">
                    <label>Replace Icon (Optional)</label>
                    <div class="icon-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload new icon</h3>
                        <p style="color: #9ca3af; font-size: 13px;">PNG, SVG, JPG, WebP (max 2MB)</p>
                        <input type="file" name="icon" id="iconInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="iconPreview" class="icon-preview" alt="Preview">
                    <div class="upload-info" id="uploadInfo">
                        <strong>📤 New Upload:</strong> <span id="uploadFilename"></span> (<span id="uploadSize"></span>)
                    </div>
                    <div class="helper-text">⚠️ Leave empty to keep current icon. Uploading a new icon will replace the existing one.</div>
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label for="title">Service Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($service['title']); ?>" required>
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="slug">URL Slug <span class="required">*</span></label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($service['slug']); ?>" required>
                    <div class="helper-text">SEO-friendly URL identifier (e.g., financial-consulting)</div>
                </div>

                <!-- Short Description -->
                <div class="form-group">
                    <label for="short_description">Short Description <span class="required">*</span></label>
                    <textarea id="short_description" name="short_description" rows="3" required><?php echo htmlspecialchars($service['short_description']); ?></textarea>
                    <div class="helper-text">Brief overview shown on service cards (recommended 150-200 characters)</div>
                </div>

                <!-- Full Content -->
                <div class="form-group">
                    <label for="full_content">Full Service Details</label>
                    <textarea id="full_content" name="full_content" class="content-editor"><?php echo htmlspecialchars($service['full_content']); ?></textarea>
                    <div class="helper-text">Complete service description. You can use HTML tags for formatting.</div>
                </div>

                <!-- Category, Status, Display Order -->
                <div class="form-row-three">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="general" <?php echo $service['category'] === 'general' ? 'selected' : ''; ?>>General</option>
                            <option value="financial" <?php echo $service['category'] === 'financial' ? 'selected' : ''; ?>>Financial</option>
                            <option value="investment" <?php echo $service['category'] === 'investment' ? 'selected' : ''; ?>>Investment</option>
                            <option value="property" <?php echo $service['category'] === 'property' ? 'selected' : ''; ?>>Property</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $service['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $service['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="draft" <?php echo $service['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $service['display_order']; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first</div>
                    </div>
                </div>

                <!-- Checkboxes -->
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" <?php echo $service['is_featured'] ? 'checked' : ''; ?>>
                            <label for="is_featured" style="margin: 0;">⭐ Mark as Featured Service</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="has_offer" name="has_offer" <?php echo $service['has_offer'] ? 'checked' : ''; ?>>
                            <label for="has_offer" style="margin: 0;">🏷️ This Service Has an Active Offer</label>
                        </div>
                    </div>
                </div>

                <!-- Offer Text -->
                <div class="form-group" id="offerTextGroup" style="display: <?php echo $service['has_offer'] ? 'block' : 'none'; ?>;">
                    <label for="offer_text">Offer Details (Optional)</label>
                    <input type="text" id="offer_text" name="offer_text" value="<?php echo htmlspecialchars($service['offer_text'] ?? ''); ?>" placeholder="e.g., 20% off for new clients">
                    <div class="helper-text">Brief description of the special offer</div>
                </div>

                <!-- Stats Display -->
                <div class="form-row" style="padding: 20px; background: #f9fafb; border-radius: 8px; margin-top: 20px;">
                    <div style="font-size: 14px; color: #6b7280;">
                        <strong>📅 Created:</strong> <?php echo date('M d, Y h:i A', strtotime($service['created_at'])); ?>
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <strong>👁️ Views:</strong> <?php echo number_format($service['views']); ?>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                        Update Service
                    </button>
                    <a href="services.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Icon upload functionality
const uploadArea = document.getElementById('uploadArea');
const iconInput = document.getElementById('iconInput');
const iconPreview = document.getElementById('iconPreview');
const uploadInfo = document.getElementById('uploadInfo');
const uploadFilename = document.getElementById('uploadFilename');
const uploadSize = document.getElementById('uploadSize');

function displayImagePreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        iconPreview.src = e.target.result;
        iconPreview.style.display = 'block';
        
        // Show upload info
        uploadInfo.style.display = 'block';
        uploadFilename.textContent = file.name;
        uploadSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
    };
    reader.readAsDataURL(file);
}

uploadArea.addEventListener('click', () => iconInput.click());

iconInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        displayImagePreview(file);
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
        iconInput.files = e.dataTransfer.files;
        displayImagePreview(file);
    }
});

// Show/hide offer text field
document.getElementById('has_offer').addEventListener('change', function() {
    document.getElementById('offerTextGroup').style.display = this.checked ? 'block' : 'none';
});

// Form validation
document.getElementById('serviceForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const shortDesc = document.getElementById('short_description').value.trim();
    
    if (!title) {
        e.preventDefault();
        alert('Please enter a service title');
        return false;
    }
    
    if (!shortDesc) {
        e.preventDefault();
        alert('Please enter a short description');
        return false;
    }
    
    const icon = iconInput.files[0];
    if (icon && icon.size > 2 * 1024 * 1024) {
        e.preventDefault();
        alert('Icon size must be less than 2MB');
        return false;
    }
});

console.log('✓ Edit Service form loaded - Icon display fixed with 4-level traversal');
</script>

</body>
</html>