<?php
/**
 * IMAR Group Admin Panel - Add Service
 * File: admin/SERVICES_CODE/add-service.php
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

// Define absolute path for file operations
$document_root = $_SERVER['DOCUMENT_ROOT'];
$upload_base_abs = $document_root . '/Imar-Group-Website/images/Services/';
$upload_base_url = 'images/Services/';

// Create directory if it doesn't exist
if (!file_exists($upload_base_abs)) {
    mkdir($upload_base_abs, 0755, true);
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
    
    // Auto-generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
    
    // Validation
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($short_description)) {
        $error_message = "Short description is required.";
    } else {
        // Check if slug already exists
        $stmt = $conn->prepare("SELECT id FROM services WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        
        $icon_path = null;
        
        // Process icon upload
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['icon'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, WebP, and SVG are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "File size exceeds 2MB limit.";
            } else {
                if (!file_exists($upload_base_abs)) {
                    mkdir($upload_base_abs, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path_abs = $upload_base_abs . $filename;
                $icon_path = $upload_base_url . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    $error_message = "Failed to upload icon.";
                }
            }
        }
        
        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO services (title, slug, icon_path, short_description, full_content, category, is_featured, has_offer, offer_text, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiiissi", $title, $slug, $icon_path, $short_description, $full_content, $category, $is_featured, $has_offer, $offer_text, $display_order, $status, $admin_id);
            
            if ($stmt->execute()) {
                $service_id = $stmt->insert_id;
                $auth->logActivity($admin_id, 'added_service', 'services', $service_id);
                
                $success_message = "Service created successfully!";
                echo "<script>setTimeout(function() { window.location.href = 'services.php'; }, 2000);</script>";
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}

$max_order = $conn->query("SELECT MAX(display_order) as max_order FROM services")->fetch_assoc()['max_order'] ?? 0;
$suggested_order = $max_order + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - IMAR Group Admin</title>
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
        .form-group input[type="number"] {
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
        .icon-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s;
        }
        .icon-upload-area:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .icon-preview {
            max-width: 120px;
            max-height: 120px;
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
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Add New Service</h1>
                <p style="color: #6b7280; margin-top: 5px;">Create a new service offering</p>
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
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Service Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="e.g., Financial Consulting" required>
                    <div class="helper-text">This will be the main heading for the service</div>
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" placeholder="auto-generated-from-title">
                    <div class="helper-text">Leave empty to auto-generate from title</div>
                </div>

                <!-- Icon Upload -->
                <div class="form-group">
                    <label>Service Icon</label>
                    <div class="icon-upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#9ca3af">
                            <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
                        </svg>
                        <h3 style="margin: 15px 0 5px 0; color: #374151;">Click to upload icon</h3>
                        <p style="color: #9ca3af; font-size: 13px;">PNG, SVG, JPG (max 2MB)</p>
                        <input type="file" name="icon" id="iconInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="iconPreview" class="icon-preview" alt="Preview">
                </div>

                <!-- Short Description -->
                <div class="form-group">
                    <label for="short_description">Short Description <span class="required">*</span></label>
                    <textarea id="short_description" name="short_description" rows="3" placeholder="Brief overview of the service..." required></textarea>
                    <div class="helper-text">This will appear on the service cards (recommended 150-200 characters)</div>
                </div>

                <!-- Full Content -->
                <div class="form-group">
                    <label for="full_content">Full Service Details</label>
                    <textarea id="full_content" name="full_content" class="content-editor" placeholder="Detailed description of the service..."></textarea>
                    <div class="helper-text">You can use HTML tags for formatting</div>
                </div>

                <!-- Category, Status, Display Order -->
                <div class="form-row-three">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="general">General</option>
                            <option value="financial">Financial</option>
                            <option value="investment">Investment</option>
                            <option value="property">Property</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $suggested_order; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first</div>
                    </div>
                </div>

                <!-- Checkboxes: Featured & Has Offer -->
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured">
                            <label for="is_featured" style="margin: 0;">Mark as Featured Service</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="has_offer" name="has_offer">
                            <label for="has_offer" style="margin: 0;">This Service Has an Active Offer</label>
                        </div>
                    </div>
                </div>

                <!-- Offer Text (shown when has_offer is checked) -->
                <div class="form-group" id="offerTextGroup" style="display: none;">
                    <label for="offer_text">Offer Details (Optional)</label>
                    <input type="text" id="offer_text" name="offer_text" placeholder="e.g., 20% off for new clients">
                    <div class="helper-text">Brief description of the offer</div>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        Create Service
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

uploadArea.addEventListener('click', () => iconInput.click());

iconInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            iconPreview.src = e.target.result;
            iconPreview.style.display = 'block';
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
        iconInput.files = e.dataTransfer.files;
        const reader = new FileReader();
        reader.onload = function(e) {
            iconPreview.src = e.target.result;
            iconPreview.style.display = 'block';
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

// Show/hide offer text field
document.getElementById('has_offer').addEventListener('change', function() {
    document.getElementById('offerTextGroup').style.display = this.checked ? 'block' : 'none';
});
</script>

</body>
</html>