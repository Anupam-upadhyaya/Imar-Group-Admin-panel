<?php
/**
 * IMAR Group Admin Panel - Inquiries List
 * File: admin/inquiries.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';

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
            background: #fc900259; color: #fc9002ff; }
        
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
           <a href="../dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="inquiries.php" class="menu-item active">
                <svg viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                </svg>
                <span>Inquiries</span>
                <?php if ($counts['new'] > 0): ?>
                    <span style="margin-left: auto; background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                        <?php echo $counts['new']; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="../GALLERY_CODE/gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <span>Gallery</span>
            </a>

            <a href="../BLOG_CODE/blog.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                </svg>
                <span>Blog Posts</span>
            </a>

            <a href="../users.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <span>Users</span>
            </a>

            <a href="../settings.php" class="menu-item">
                <svg viewBox="0 0 24 24">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Customer Inquiries</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">Logout</a>
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
            // Show admin notes if available, otherwise display a placeholder
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