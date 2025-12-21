<?php
/**
 * IMAR Group Admin Panel - Add User
 * File: admin/add-user.php
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

// Only super_admin can add users
if ($admin_role !== 'super_admin') {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'editor');
    $dob = trim($_POST['dob'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($name)) {
        $error_message = "Name is required.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Valid email is required.";
    } elseif (empty($password)) {
        $error_message = "Password is required.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!in_array($role, ['super_admin', 'admin', 'editor'])) {
        $error_message = "Invalid role selected.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO admin_users (name, email, password, role, dob, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $dob, $status);
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                $auth->logActivity($admin_id, 'added_user', 'admin_users', $new_user_id);
                
                $success_message = "User created successfully!";
                
                // Send welcome email (optional - you'll need to implement email sending)
                // sendWelcomeEmail($email, $name, $password);
                
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
    <title>Add User - IMAR Group Admin</title>
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
        
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        
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
        
        .role-description {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .role-description strong {
            color: #0f172a;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Add New User</h1>
                <p style="color: #6b7280; margin-top: 5px;">Create a new admin user account</p>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;">Super Admin</div>
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

        <form method="POST" id="addUserForm">
            <div class="form-container">
                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required placeholder="e.g., John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    <div class="helper-text">This name will be displayed in the admin panel</div>
                </div>

                <!-- Email & Date of Birth -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="email@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="helper-text">Used for login and notifications</div>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                        <div class="helper-text">Optional field</div>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                            <button type="button" class="toggle-password-btn" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="helper-text" id="strengthText">Password strength: None</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="password-toggle">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
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
                            <option value="editor" <?php echo ($_POST['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                            <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="super_admin" <?php echo ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Account Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="helper-text">Inactive users cannot log in</div>
                    </div>
                </div>

                <!-- Role Description -->
                <div class="role-description" id="roleDescription">
                    <strong>Editor Permissions:</strong>
                    Can create and edit content but cannot delete or manage users.
                </div>

                <!-- Submit Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create User
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
// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    strengthBar.className = 'password-strength-bar';
    
    if (strength === 0) {
        strengthText.textContent = 'Password strength: None';
        strengthBar.style.width = '0%';
    } else if (strength <= 2) {
        strengthText.textContent = 'Password strength: Weak';
        strengthBar.classList.add('strength-weak');
    } else if (strength <= 4) {
        strengthText.textContent = 'Password strength: Medium';
        strengthBar.classList.add('strength-medium');
    } else {
        strengthText.textContent = 'Password strength: Strong';
        strengthBar.classList.add('strength-strong');
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
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

// Role descriptions
const roleDescriptions = {
    'editor': '<strong>Editor Permissions:</strong> Can create and edit content but cannot delete or manage users.',
    'admin': '<strong>Admin Permissions:</strong> Full access to content management, can delete content, but cannot manage other admin users.',
    'super_admin': '<strong>Super Admin Permissions:</strong> Complete system access including user management, system settings, and all content operations.'
};

document.getElementById('role').addEventListener('change', function() {
    document.getElementById('roleDescription').innerHTML = roleDescriptions[this.value];
});
</script>

</body>
</html>