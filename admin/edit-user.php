<?php
/**
 * IMAR Group Admin Panel - Edit User
 * File: admin/edit-user.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

// Get user ID
$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    header('Location: users.php');
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Only super_admin can edit users, or users can edit themselves
if ($admin_role !== 'super_admin' && $admin_id != $user_id) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'editor');
    $dob = trim($_POST['dob'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($name)) {
        $error_message = "Name is required.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Valid email is required.";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!in_array($role, ['super_admin', 'admin', 'editor'])) {
        $error_message = "Invalid role selected.";
    } else {
        // Check if email exists for another user
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Update user
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ?, password = ?, role = ?, dob = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $name, $email, $hashed_password, $role, $dob, $status, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ?, role = ?, dob = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $role, $dob, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                $auth->logActivity($admin_id, 'updated_user', 'admin_users', $user_id);
                
                $success_message = "User updated successfully!";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // If user updated themselves, update session
                if ($user_id == $admin_id) {
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['admin_role'] = $user['role'];
                }
                
                echo "<script>setTimeout(function() { window.location.href = 'users.php'; }, 2000);</script>";
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
        
        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-radius: 12px;
            margin-bottom: 30px;
            color: white;
        }
        
        .user-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
        }
        
        .user-header-info h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        
        .user-header-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
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
        
        .password-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        
        .password-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #0f172a;
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
        
        .info-box {
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #1e40af;
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
                <p style="color: #6b7280; margin-top: 5px;">Update user information and permissions</p>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst(str_replace('_', ' ', $admin_role)); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?> Redirecting...
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="editUserForm">
            <div class="form-container">
                <!-- User Header -->
                <div class="user-header">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="user-header-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?> • Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>

                <?php if ($user_id == $admin_id): ?>
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i> You are editing your own profile. Changes will be reflected immediately.
                    </div>
                <?php endif; ?>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>

                <!-- Email & Date of Birth -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="password-section">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                    <div class="helper-text" style="margin-bottom: 15px;">Leave blank to keep current password</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-toggle">
                                <input type="password" id="new_password" name="new_password" placeholder="Minimum 6 characters">
                                <button type="button" class="toggle-password-btn" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-toggle">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password">
                                <button type="button" class="toggle-password-btn" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="helper-text" id="matchText"></div>
                        </div>
                    </div>
                </div>

                <!-- Role & Status (only super_admin can change) -->
                <?php if ($admin_role === 'super_admin'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">User Role <span class="required">*</span></label>
                            <select id="role" name="role" required <?php echo $user_id == $admin_id ? 'disabled' : ''; ?>>
                                <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                            <?php if ($user_id == $admin_id): ?>
                                <div class="helper-text">You cannot change your own role</div>
                                <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="status">Account Status</label>
                            <select id="status" name="status" <?php echo $user_id == $admin_id ? 'disabled' : ''; ?>>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <?php if ($user_id == $admin_id): ?>
                                <div class="helper-text">You cannot deactivate yourself</div>
                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                    <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                <?php endif; ?>

                <!-- Account Info -->
                <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 13px; color: #6b7280;">
                    <strong style="color: #0f172a;">Account Information:</strong><br>
                    Created: <?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?><br>
                    Last Updated: <?php echo date('M d, Y h:i A', strtotime($user['updated_at'])); ?><br>
                    Last Login: <?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
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
// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    const matchText = document.getElementById('matchText');
    
    if (confirm.length === 0) {
        matchText.textContent = '';
        matchText.style.color = '';
    } else if (password === confirm) {
        matchText.textContent = '✓ Passwords match';
        matchText.style.color = '#10b981';
    } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.style.color = '#ef4444';
    }
});

// Toggle password visibility
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
</script>

</body>
</html>