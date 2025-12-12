<?php
session_start();
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

// Handle delete
if (isset($_GET['delete']) && $admin_role !== 'editor') {
    $delete_id = (int)$_GET['delete'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        // Log activity
        $auth->logActivity($admin_id, 'deleted_gallery_item', 'gallery', $delete_id);
        
        $success_message = "Gallery item deleted successfully!";
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];
$types = '';

if ($filter !== 'all') {
    if ($filter === 'featured') {
        $whereConditions[] = "is_featured = 1";
    } else {
        $whereConditions[] = "category = ?";
        $params[] = $filter;
        $types .= 's';
    }
}

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
    $types .= 'ss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT * FROM gallery $whereClause ORDER BY display_order ASC, created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$gallery_items = $result->fetch_all(MYSQLI_ASSOC);

// Get counts
$counts = [
    'all' => $conn->query("SELECT COUNT(*) as count FROM gallery")->fetch_assoc()['count'],
    'featured' => $conn->query("SELECT COUNT(*) as count FROM gallery WHERE is_featured = 1")->fetch_assoc()['count'],
    'offices' => $conn->query("SELECT COUNT(*) as count FROM gallery WHERE category = 'offices'")->fetch_assoc()['count'],
    'team' => $conn->query("SELECT COUNT(*) as count FROM gallery WHERE category = 'team'")->fetch_assoc()['count'],
    'events' => $conn->query("SELECT COUNT(*) as count FROM gallery WHERE category = 'events'")->fetch_assoc()['count'],
    'awards' => $conn->query("SELECT COUNT(*) as count FROM gallery WHERE category = 'awards'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .gallery-grid-admin {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
        }
        
        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .gallery-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
        }
        
        .gallery-card-content {
            padding: 15px;
        }
        
        .gallery-card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #0f172a;
        }
        
        .gallery-card-description {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .gallery-card-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-category {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .badge-featured {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-size {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .gallery-card-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #4f46e5;
            color: white;
        }
        
        .btn-edit:hover {
            background: #4338ca;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .add-new-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            font-size: 24px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .add-new-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.6);
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
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
        }
        
        .filter-tab.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
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
            <a href="/Imar_Group_Admin_panel/admin/dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/INQUIRY_CODE/inquiries.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>Inquiries</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/GALLERY_CODE/gallery.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                <span>Gallery</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/BLOG_CODE/blog.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/videos.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                <span>Videos</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/users.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span>Users</span>
            </a>
            <a href="/Imar_Group_Admin_panel/admin/settings.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Gallery Management</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="/Imar_Group_Admin_panel/admin/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="inquiries-header">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All <span class="filter-badge"><?php echo $counts['all']; ?></span>
                </a>
                <a href="?filter=featured" class="filter-tab <?php echo $filter === 'featured' ? 'active' : ''; ?>">
                    Featured <span class="filter-badge"><?php echo $counts['featured']; ?></span>
                </a>
                <a href="?filter=offices" class="filter-tab <?php echo $filter === 'offices' ? 'active' : ''; ?>">
                    Offices <span class="filter-badge"><?php echo $counts['offices']; ?></span>
                </a>
                <a href="?filter=team" class="filter-tab <?php echo $filter === 'team' ? 'active' : ''; ?>">
                    Team <span class="filter-badge"><?php echo $counts['team']; ?></span>
                </a>
                <a href="?filter=events" class="filter-tab <?php echo $filter === 'events' ? 'active' : ''; ?>">
                    Events <span class="filter-badge"><?php echo $counts['events']; ?></span>
                </a>
                <a href="?filter=awards" class="filter-tab <?php echo $filter === 'awards' ? 'active' : ''; ?>">
                    Awards <span class="filter-badge"><?php echo $counts['awards']; ?></span>
                </a>
            </div>

            <div class="search-box">
               <form method="GET" action="" style="display: flex; gap: 10px;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="text" name="search" class="search-input" placeholder="Search gallery..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="action-btn view">Search</button>
                </form>
            </div>
        </div>

        <!-- Gallery Grid -->
        <?php if (empty($gallery_items)): ?>
            <div class="empty-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="#d1d5db">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <h3>No Gallery Items Found</h3>
                <p>Start by adding your first gallery item.</p>
            </div>
        <?php else: ?>
            <div class="gallery-grid-admin">
                <?php foreach ($gallery_items as $item): ?>
                    <div class="gallery-card">
                        <img src="../../../Imar-Group-Website/<?php echo htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="gallery-card-image"
                             onerror="this.src='../assets/placeholder.jpg'">
                        
                        <div class="gallery-card-content">
                            <div class="gallery-card-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="gallery-card-description"><?php echo htmlspecialchars($item['description']); ?></div>
                            
                            <div class="gallery-card-meta">
                                <span class="badge badge-category"><?php echo ucfirst($item['category']); ?></span>
                                <?php if ($item['is_featured']): ?>
                                    <span class="badge badge-featured">Featured</span>
                                <?php endif; ?>
                                <span class="badge badge-size"><?php echo ucfirst($item['size_class']); ?></span>
                            </div>
                            
                            <div class="gallery-card-actions">
                                <a href="edit-gallery.php?id=<?php echo $item['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <?php if ($admin_role !== 'editor'): ?>
                                    <a href="?delete=<?php echo $item['id']; ?>&filter=<?php echo $filter; ?>" 
                                       class="btn-small btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add New Button -->
        <a href="add-gallery.php" class="add-new-btn" title="Add New Gallery Item">+</a>
    </div>
</div>

</body>
</html>