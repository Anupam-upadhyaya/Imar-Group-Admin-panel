<?php
/**
 * IMAR Group Admin Panel - Edit User
 * File: admin/edit-user.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/includes/avatar-helper.php';
$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get current user info with avatar
$admin_id = $_SESSION['admin_id'];
$currentUser = getCurrentUserAvatar($conn, $admin_id);

$admin_name = $currentUser['name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $currentUser['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $currentUser['role'] ?? $_SESSION['admin_role'] ?? 'editor';
$admin_avatar = $currentUser['avatar'] ?? null;
$admin_initials = strtoupper(substr($admin_name, 0, 1));

// Get avatar URL
$avatarUrl = getAvatarPath($admin_avatar, __DIR__);

// Only super_admin can edit users
if ($admin_role !== 'super_admin') {
    header('Location: dashboard.php');
    exit();
}

$user_id = (int)($_GET['id'] ?? 0);
$error_message = '';
$success_message = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'editor');
    $dob = trim($_POST['dob'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $remove_avatar = isset($_POST['remove_avatar']);
    
    // Validation
    if (empty($name)) {
        $error_message = "Name is required.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Valid email is required.";
    } elseif (!empty($password) && strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!in_array($role, ['super_admin', 'admin', 'editor'])) {
        $error_message = "Invalid role selected.";
    } else {
        // Check if email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            $avatar_filename = $user['avatar'];
            
            // Handle avatar removal
            if ($remove_avatar && $avatar_filename) {
                $avatar_path = __DIR__ . '/../uploads/avatars/' . $avatar_filename;
                if (file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
                $avatar_filename = null;
            }
            
            // Handle new avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/avatars/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_tmp = $_FILES['avatar']['tmp_name'];
                $file_name = $_FILES['avatar']['name'];
                $file_size = $_FILES['avatar']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_message = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
                } elseif ($file_size > 5242880) {
                    $error_message = "File size must be less than 5MB.";
                } else {
                    $image_info = getimagesize($file_tmp);
                    if ($image_info === false) {
                        $error_message = "File is not a valid image.";
                    } else {
                        // Delete old avatar if exists
                        if ($avatar_filename) {
                            $old_avatar_path = $upload_dir . $avatar_filename;
                            if (file_exists($old_avatar_path)) {
                                unlink($old_avatar_path);
                            }
                        }
                        
                        $avatar_filename = 'avatar_' . uniqid() . '_' . time() . '.' . $file_ext;
                        $upload_path = $upload_dir . $avatar_filename;
                        
                        if (!move_uploaded_file($file_tmp, $upload_path)) {
                            $error_message = "Failed to upload avatar image.";
                            $avatar_filename = $user['avatar'];
                        }
                    }
                }
            }
            
            // Update user
            if (empty($error_message)) {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ?, password = ?, role = ?, dob = ?, status = ?, avatar = ? WHERE id = ?");
                    $stmt->bind_param("sssssssi", $name, $email, $hashed_password, $role, $dob, $status, $avatar_filename, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ?, role = ?, dob = ?, status = ?, avatar = ? WHERE id = ?");
                    $stmt->bind_param("ssssssi", $name, $email, $role, $dob, $status, $avatar_filename, $user_id);
                }
                
                if ($stmt->execute()) {
                    $auth->logActivity($admin_id, 'updated_user', 'admin_users', $user_id);
                    $success_message = "User updated successfully!";
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                } else {
                    $error_message = "Database error: " . $stmt->error;
                }
            }
        }
    }
}

function getAvatarUrl($avatar) {
    if ($avatar && file_exists(__DIR__ . '/../uploads/avatars/' . $avatar)) {
        return '../uploads/avatars/' . $avatar;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 14px;
        }
        
        .required {
            color: #ef4444;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4f46e5;
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .helper-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .avatar-upload-container {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
            overflow: hidden;
            border: 4px solid #e5e7eb;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload-controls {
            flex: 1;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #4f46e5;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .file-input-label:hover {
            background: #4338ca;
        }
        
        .btn-remove-avatar {
            padding: 12px 24px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-remove-avatar:hover {
            background: #dc2626;
        }
        
        .file-name-display {
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-password-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 16px;
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
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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
            margin-bottom: 25px;
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
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Edit User</h1>
                <p style="color: #6b7280; margin-top: 5px;">Update user account information</p>
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
                        <div style="font-size: 12px; color: #6b7280;">Super Admin</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="editUserForm" enctype="multipart/form-data">
            <div class="form-container">
                <!-- Avatar Upload -->
                <div class="avatar-upload-container">
                    <div class="avatar-preview" id="avatarPreview">
                        <?php 
                        $avatarUrl = getAvatarUrl($user['avatar']);
                        if ($avatarUrl): ?>
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar">
                        <?php else: ?>
                            <span id="avatarInitials"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-upload-controls">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #0f172a; font-size: 14px;">
                            Profile Photo
                        </label>
                        <div>
                            <div class="file-input-wrapper">
                                <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                                <label for="avatar" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i> Change Photo
                                </label>
                            </div>
                            <?php if ($user['avatar']): ?>
                                <button type="button" class="btn-remove-avatar" onclick="confirmRemoveAvatar()">
                                    <i class="fas fa-trash"></i> Remove Photo
                                </button>
                                <input type="hidden" name="remove_avatar" id="remove_avatar" value="">
                            <?php endif; ?>
                        </div>
                        <div class="file-name-display" id="fileName">
                            <?php echo $user['avatar'] ? 'Current: ' . $user['avatar'] : 'No file chosen'; ?>
                        </div>
                        <div class="helper-text">JPG, PNG, GIF or WEBP. Max 5MB.</div>
                    </div>
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required placeholder="e.g., John Doe" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" oninput="updateAvatarInitials()">
                </div>

                <!-- Email & Date of Birth -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="email@example.com" 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Password (Optional) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" placeholder="Leave blank to keep current">
                            <button type="button" class="toggle-password-btn" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="helper-text">Minimum 6 characters. Leave blank to keep current password.</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-toggle">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                            <button type="button" class="toggle-password-btn" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="helper-text" id="matchText"></div>
                    </div>
                </div>

                <!-- Role & Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">User Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Account Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function previewAvatar(input) {
    const preview = document.getElementById('avatarPreview');
    const fileName = document.getElementById('fileName');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        fileName.textContent = file.name;
        
        if (file.size > 5242880) {
            alert('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar Preview">';
        };
        reader.readAsDataURL(file);
    }
}

function confirmRemoveAvatar() {
    if (confirm('Are you sure you want to remove the profile photo?')) {
        document.getElementById('remove_avatar').value = '1';
        document.getElementById('avatarPreview').innerHTML = '<span id="avatarInitials"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>';
        document.getElementById('fileName').textContent = 'Avatar will be removed on save';
    }
}

function updateAvatarInitials() {
    const nameInput = document.getElementById('name');
    const preview = document.getElementById('avatarPreview');
    const fileInput = document.getElementById('avatar');
    
    if ((!fileInput.files || !fileInput.files[0]) && document.getElementById('remove_avatar').value === '1') {
        const name = nameInput.value.trim();
        const initials = name ? name.charAt(0).toUpperCase() : '?';
        preview.innerHTML = '<span id="avatarInitials">' + initials + '</span>';
    }
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    const matchText = document.getElementById('matchText');
    
    if (password.length === 0 && confirm.length === 0) {
        matchText.textContent = '';
    } else if (confirm.length === 0) {
        matchText.textContent = '';
    } else if (password === confirm) {
        matchText.textContent = '✓ Passwords match';
        matchText.style.color = '#10b981';
    } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.style.color = '#ef4444';
    }
});
</script>

</body>
</html>