<?php
session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/includes/avatar-helper.php';
require_once __DIR__ . '/../includes/classes/AccessControl.php';
require_once __DIR__ . '/../includes/classes/Permissions.php';

$auth = new Auth($conn);
$access = new AccessControl($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!Permissions::canAccessUserManagement($access->getCurrentRole())) {
    header('Location: dashboard.php?error=access_denied');
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

$pending_deletion = null;
if ($admin_role === Permissions::ROLE_SUPER_ADMIN) {
    $stmt = $conn->prepare("SELECT * FROM user_deletion_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $pending_deletion = $stmt->get_result()->fetch_assoc();
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        if ($delete_id == $admin_id) {
            $error_message = "You cannot delete your own account directly. Use the self-deletion request feature.";
        } else {
            $stmt = $conn->prepare("SELECT role, avatar FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $target_user = $stmt->get_result()->fetch_assoc();
            
            if (!$target_user) {
                $error_message = "User not found.";
            } else {
                if (!Permissions::canManageUser($admin_role, $target_user['role'], Permissions::ACTION_DELETE)) {
                    $error_message = "You don't have permission to delete this user.";
                } else {
                    if (!$access->isReAuthenticated()) {
                        $_SESSION['pending_action'] = 'delete_user';
                        $_SESSION['pending_target'] = $delete_id;
                        $_SESSION['pending_return_url'] = $_SERVER['REQUEST_URI'];
                        header('Location: reauth.php?return=' . urlencode($_SERVER['REQUEST_URI']));
                        exit();
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
                    $stmt->bind_param("i", $delete_id);
                    
                    if ($stmt->execute()) {
                        if ($target_user['avatar']) {
                            $avatar_path = __DIR__ . '/../uploads/avatars/' . $target_user['avatar'];
                            if (file_exists($avatar_path)) {
                                unlink($avatar_path);
                            }
                        }
                        
                        $access->logPrivilegedAction($admin_id, 'deleted_user', 'admin_users', $delete_id);
                        $access->clearReAuthentication();
                        
                        $success_message = "User deleted successfully!";
                    } else {
                        $error_message = "Failed to delete user.";
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
    }
}

if (isset($_GET['toggle_status'])) {
    $toggle_id = (int)$_GET['toggle_status'];
    
    try {
        if ($toggle_id == $admin_id) {
            $error_message = "You cannot change your own status.";
        } else {

            $stmt = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $toggle_id);
            $stmt->execute();
            $target_user = $stmt->get_result()->fetch_assoc();
            
            if (!$target_user) {
                $error_message = "User not found.";
            } else {

                if (!Permissions::canManageUser($admin_role, $target_user['role'], Permissions::ACTION_EDIT)) {
                    $error_message = "You don't have permission to modify this user.";
                } else {
                    $stmt = $conn->prepare("UPDATE admin_users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                    $stmt->bind_param("i", $toggle_id);
                    
                    if ($stmt->execute()) {
                        $auth->logActivity($admin_id, 'toggled_user_status', 'admin_users', $toggle_id);
                        $success_message = "User status updated!";
                    } else {
                        $error_message = "Failed to update user status.";
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
    }
}

$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

$whereConditions = ["1=1"];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
    $types .= 'ss';
}

if ($filter_role !== 'all') {
    $whereConditions[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

if ($filter_status !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

$query = "SELECT * FROM admin_users $whereClause ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM admin_users) as total,
        (SELECT COUNT(*) FROM admin_users WHERE status = 'active') as active,
        (SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin') as super_admins,
        (SELECT COUNT(*) FROM admin_users WHERE role = 'admin') as admins,
        (SELECT COUNT(*) FROM admin_users WHERE role = 'editor') as editors
";
$stats = $conn->query($stats_query)->fetch_assoc();

function getAvatarUrl($avatar) {
    if (!$avatar) {
        return null;
    }
    
    $file_path = __DIR__ . '/../uploads/avatars/' . $avatar;

    if (file_exists($file_path) && is_file($file_path)) {

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
    <title>Users Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .users-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table thead {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
        }
        
        .users-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .users-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .user-avatar-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar-table {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .user-avatar-table img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .user-email {
            color: #6b7280;
            font-size: 13px;
        }
        
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .role-super_admin {
            background: #fef3c7;
            color: #92400e;
        }
        
        .role-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-editor {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 14px;
            text-decoration: none;
        }
        
        .btn-icon:disabled,
        .btn-icon.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-edit:hover:not(:disabled) {
            background: #bfdbfe;
        }
        
        .btn-toggle {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-toggle:hover:not(:disabled) {
            background: #fde68a;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete:hover:not(:disabled) {
            background: #fecaca;
        }
        
        .btn-add {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-add:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
        
        .btn-self-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-self-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .btn-cancel-deletion {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-cancel-deletion:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .deletion-pending-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 15px;
            animation: pulse 2s infinite;
            border: 1px solid #fecaca;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .current-user-badge {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        
        .filters-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            max-width: 1000px;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            color: #374151;
            transition: all 0.3s ease;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .search-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            background: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .dob-display {
            color: #6b7280;
            font-size: 13px;
        }
        
        .no-permission-text {
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }
        
        @media (min-width: 768px) {
            .search-input {
                min-width: 400px;
            }
        }

        @media (min-width: 1024px) {
            .search-input {
                min-width: 500px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Users Management</h1>
                <p style="color: #6b7280; margin-top: 5px;">Manage admin users and permissions</p>
                
                <?php if ($pending_deletion): ?>
                    <div class="deletion-pending-badge">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>
                            Your account deletion is scheduled for 
                            <?php echo date('M d, Y g:i A', strtotime($pending_deletion['scheduled_deletion_at'])); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <?php if (Permissions::canAccessUserManagement($admin_role)): ?>
                    <a href="add-user.php" class="btn-add">
                        <i class="fas fa-plus"></i> Add New User
                    </a>
                <?php endif; ?>
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
                        <div style="font-size: 12px; color: #6b7280;"><?php echo str_replace('_', ' ', ucwords($admin_role)); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✓ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✗ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Active Users</h3>
                        <div class="stat-value"><?php echo $stats['active']; ?></div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Super Admins</h3>
                        <div class="stat-value"><?php echo $stats['super_admins']; ?></div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-crown"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Editors</h3>
                        <div class="stat-value"><?php echo $stats['editors']; ?></div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-pen"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-row">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($filter_role); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="text" name="search" class="search-input" placeholder="🔍 Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <select class="filter-select" onchange="location.href='?status=<?php echo $filter_status; ?>&role=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                <option value="super_admin" <?php echo $filter_role === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="editor" <?php echo $filter_role === 'editor' ? 'selected' : ''; ?>>Editor</option>
            </select>
            
            <select class="filter-select" onchange="location.href='?role=<?php echo $filter_role; ?>&status=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>

        <!-- Users Table -->
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px;"></i>
                <h3>No Users Found</h3>
                <p>Start by adding your first admin user.</p>
            </div>
        <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date of Birth</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $avatarUrl = getAvatarUrl($user['avatar']);
                            $userInitials = strtoupper(substr($user['name'], 0, 1));
                            
                            // ✅ RBAC: Check permissions for each action
                            $canEdit = Permissions::canManageUser($admin_role, $user['role'], Permissions::ACTION_EDIT);
                            $canDelete = Permissions::canManageUser($admin_role, $user['role'], Permissions::ACTION_DELETE) 
                                         && $user['id'] != $admin_id;
                            $canToggleStatus = $canEdit && $user['id'] != $admin_id;
                            
                            // ✅ NEW: Check if this is current user
                            $isCurrentUser = ($user['id'] == $admin_id);
                            
                            //Check if user has pending deletion
                            $userHasPendingDeletion = false;
                            if ($user['role'] === Permissions::ROLE_SUPER_ADMIN) {
                                $stmt = $conn->prepare("SELECT id FROM user_deletion_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
                                $stmt->bind_param("i", $user['id']);
                                $stmt->execute();
                                $userHasPendingDeletion = $stmt->get_result()->num_rows > 0;
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="user-avatar-cell">
                                        <div class="user-avatar-table">
                                            <?php if ($avatarUrl): ?>
                                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                                                     alt="<?php echo htmlspecialchars($user['name']); ?>"
                                                     onerror="this.outerHTML='<span><?php echo $userInitials; ?></span>';">
                                            <?php else: ?>
                                                <?php echo $userInitials; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                                <?php if ($isCurrentUser): ?>
                                                    <span class="current-user-badge">
                                                        <i class="fas fa-user-check"></i> You
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo str_replace('_', ' ', ucwords($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="dob-display">
                                        <?php echo $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : 'N/A'; ?>
                                    </span>
                                </td>
                                <td style="color: #6b7280;">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($isCurrentUser): ?>
                                            <!--Self-deletion option for Super Admins -->
                                            <?php if ($admin_role === Permissions::ROLE_SUPER_ADMIN): ?>
                                                <?php if ($userHasPendingDeletion): ?>
                                                    <a href="cancel-deletion.php" 
                                                       class="btn-cancel-deletion"
                                                       title="Cancel Deletion Request">
                                                        <i class="fas fa-times-circle"></i> Cancel Deletion
                                                    </a>
                                                <?php else: ?>
                                                    <a href="request-account-deletion.php" 
                                                       class="btn-self-delete"
                                                       title="Request Account Deletion">
                                                        <i class="fas fa-user-times"></i> Delete Account
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="current-user-badge" style="padding: 8px 16px;">
                                                    <i class="fas fa-user"></i> Current User
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Edit Button -->
                                            <?php if ($canEdit): ?>
                                                <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn-icon btn-edit" 
                                                   title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn-icon btn-edit disabled" title="No permission to edit">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Toggle Status Button -->
                                            <?php if ($canToggleStatus): ?>
                                                <a href="?toggle_status=<?php echo $user['id']; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>" 
                                                   class="btn-icon btn-toggle" 
                                                   title="Toggle Status"
                                                   onclick="return confirm('Toggle user status?')">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn-icon btn-toggle disabled" title="No permission to toggle status">
                                                    <i class="fas fa-power-off"></i>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Button -->
                                            <?php if ($canDelete): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>" 
                                                   class="btn-icon btn-delete" 
                                                   title="Delete User"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn-icon btn-delete disabled" title="No permission to delete">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>