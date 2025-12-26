<?php
/**
 * IMAR Group Admin Panel - Inquiries List
 * File: admin/INQUIRY_CODE/inquiries.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/avatar-helper.php';
$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
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

// Get filter from URL
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query based on filter
$whereConditions = [];
$params = [];
$types = '';

if ($filter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $filter;
    $types .= 's';
}

if (!empty($search)) {
    $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= 'ssss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get inquiries
$query = "SELECT * FROM inquiries $whereClause ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$inquiries = $result->fetch_all(MYSQLI_ASSOC);

// Get counts for filter badges
$counts = [
    'all' => $conn->query("SELECT COUNT(*) as count FROM inquiries")->fetch_assoc()['count'],
    'new' => $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'")->fetch_assoc()['count'],
    'reading' => $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'reading'")->fetch_assoc()['count'],
    'responded' => $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'responded'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .inquiries-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-tab:hover {
            border-color: #4f46e5;
            color: #4f46e5;
        }
        
        .filter-tab.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }
        
        .filter-badge {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .filter-tab.active .filter-badge {
            background: rgba(255,255,255,0.2);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4f46e5;
        }
        
        .inquiries-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .inquiry-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .inquiry-email {
            color: #6b7280;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.new {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.reading { 
            background: #fc900259; 
            color: #fc9002ff; 
        }
        
        .status-badge.responded {
            background: #d1fae5;
            color: #065f46;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .action-btn.view {
            background: #4f46e5;
            color: white;
        }
        
        .action-btn.view:hover {
            background: #4338ca;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            fill: #d1d5db;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

   <div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Customer Inquiries</h1>
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
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="/Imar_Group_Admin_panel/admin/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="inquiries-header">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All <span class="filter-badge"><?php echo $counts['all']; ?></span>
                </a>
                <a href="?filter=new" class="filter-tab <?php echo $filter === 'new' ? 'active' : ''; ?>">
                    New <span class="filter-badge"><?php echo $counts['new']; ?></span>
                </a>
                <a href="?filter=reading" class="filter-tab <?php echo $filter === 'reading' ? 'active' : ''; ?>">
                    Reading <span class="filter-badge"><?php echo $counts['reading']; ?></span>
                </a>
                <a href="?filter=responded" class="filter-tab <?php echo $filter === 'responded' ? 'active' : ''; ?>">
                    Responded <span class="filter-badge"><?php echo $counts['responded']; ?></span>
                </a>
            </div>

            <div class="search-box">
                <form method="GET" action="" style="display: flex; gap: 10px;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="text" name="search" class="search-input" placeholder="Search by name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="action-btn view">Search</button>
                </form>
            </div>
        </div>

        <!-- Inquiries Table -->
        <div class="inquiries-table">
            <?php if (empty($inquiries)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <h3>No Inquiries Found</h3>
                    <p>No inquiries match your current filter.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td>#<?php echo $inquiry['id']; ?></td>
                                <td>
                                    <div class="inquiry-name">
                                        <?php echo htmlspecialchars($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($inquiry['email']); ?></div>
                                    <div class="inquiry-email"><?php echo htmlspecialchars($inquiry['phone']); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $inquiry['status']; ?>">
                                        <?php echo ucfirst($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    echo !empty($inquiry['admin_notes']) 
                                        ? htmlspecialchars($inquiry['admin_notes']) 
                                        : '<em>No notes</em>'; 
                                    ?>
                                </td>
                                <td>
                                    <a href="view-inquiry.php?id=<?php echo $inquiry['id']; ?>" class="action-btn view">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>