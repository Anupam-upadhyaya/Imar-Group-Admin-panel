<?php
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

$admin_id = $_SESSION['admin_id'];
$currentUser = getCurrentUserAvatar($conn, $admin_id);

$admin_name = $currentUser['name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $currentUser['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $currentUser['role'] ?? $_SESSION['admin_role'] ?? 'editor';
$admin_avatar = $currentUser['avatar'] ?? null;
$admin_initials = strtoupper(substr($admin_name, 0, 1));

$avatarUrl = getAvatarPath($admin_avatar, __DIR__);

if (isset($_GET['delete']) && $admin_role !== 'editor') {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT icon_path FROM services WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        if ($row['icon_path']) {
            $file_path_abs = dirname(dirname(dirname(__DIR__))) . '/Imar-Group-Website/' . $row['icon_path'];
            if (file_exists($file_path_abs)) {
                @unlink($file_path_abs);
            }
        }
        
        $auth->logActivity($admin_id, 'deleted_service', 'services', $delete_id);
        
        $success_message = "Service deleted successfully!";
    }
}

$filter_category = $_GET['category'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereConditions = [];
$params = [];
$types = '';

if ($filter_category !== 'all') {
    $whereConditions[] = "category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

if ($filter_status !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR short_description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
    $types .= 'ss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT * FROM services $whereClause ORDER BY display_order ASC, created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch_assoc()['count'],
    'featured' => $conn->query("SELECT COUNT(*) as count FROM services WHERE is_featured = 1")->fetch_assoc()['count'],
    'offers' => $conn->query("SELECT COUNT(*) as count FROM services WHERE has_offer = 1")->fetch_assoc()['count']
];

$category_counts = [
    'all' => $stats['total'],
    'financial' => $conn->query("SELECT COUNT(*) as count FROM services WHERE category = 'financial'")->fetch_assoc()['count'],
    'investment' => $conn->query("SELECT COUNT(*) as count FROM services WHERE category = 'investment'")->fetch_assoc()['count'],
    'property' => $conn->query("SELECT COUNT(*) as count FROM services WHERE category = 'property'")->fetch_assoc()['count'],
    'general' => $conn->query("SELECT COUNT(*) as count FROM services WHERE category = 'general'")->fetch_assoc()['count']
];

$client_website_url = 'http://localhost/Imar-Group-Website';

$services_page = 'services.html'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .services-grid-admin {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .service-card-admin {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .service-card-admin:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .service-card-icon {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .service-card-icon img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        
        .service-card-icon svg {
            width: 80px;
            height: 80px;
            fill: white;
        }
        
        .service-card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .service-card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
            line-height: 1.4;
        }
        
        .service-card-description {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 12px;
            line-height: 1.6;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            flex: 1;
        }
        
        .service-card-meta {
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
        
        .badge-status {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-status.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-status.draft {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-featured {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-offer {
            background: #fecaca;
            color: #991b1b;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .service-card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }
        
        .btn-small {
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
            text-align: center;
        }
        
        .btn-view {
            background: #10b981;
            color: white;
            position: relative;
        }
        
        .btn-view:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-view::after {
            content: '🔗';
            margin-left: 4px;
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-new-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.6);
        }
        
        .search-filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
       .search-box {
    flex: 1;
    min-width: 250px;
    max-width: 1000px; /* Increased max width */
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

.search-box .action-btn {
    padding: 12px 24px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.search-box .action-btn:hover {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.search-box .action-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
        
        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .client-url-indicator {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 8px;
        }
        
        .url-config-box {
            margin-top: 30px;
            padding: 20px;
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 12px;
            font-size: 13px;
        }
        
        .url-config-box h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 15px;
        }
        
        .url-config-box code {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #1e40af;
        }
        
        .url-config-box .instruction {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #93c5fd;
            color: #1e40af;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Services Management</h1>
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

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>


        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Total Services</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Active Services</h3>
                        <div class="stat-value"><?php echo $stats['active']; ?></div>
                    </div>
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Featured</h3>
                        <div class="stat-value"><?php echo $stats['featured']; ?></div>
                    </div>
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Active Offers</h3>
                        <div class="stat-value"><?php echo $stats['offers']; ?></div>
                    </div>
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                    </div>
                </div>
            </div>
        </div>


        <div class="search-filter-row">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="text" name="search" class="search-input" placeholder="🔍 Search services..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <select class="filter-select" onchange="location.href='?status=<?php echo $filter_status; ?>&category=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories (<?php echo $category_counts['all']; ?>)</option>
                <option value="financial" <?php echo $filter_category === 'financial' ? 'selected' : ''; ?>>Financial (<?php echo $category_counts['financial']; ?>)</option>
                <option value="investment" <?php echo $filter_category === 'investment' ? 'selected' : ''; ?>>Investment (<?php echo $category_counts['investment']; ?>)</option>
                <option value="property" <?php echo $filter_category === 'property' ? 'selected' : ''; ?>>Property (<?php echo $category_counts['property']; ?>)</option>
                <option value="general" <?php echo $filter_category === 'general' ? 'selected' : ''; ?>>General (<?php echo $category_counts['general']; ?>)</option>
            </select>
            
            <select class="filter-select" onchange="location.href='?category=<?php echo $filter_category; ?>&status=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
            </select>
        </div>

 
        <?php if (empty($services)): ?>
            <div class="empty-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="#d1d5db">
                    <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
                </svg>
                <h3>No Services Found</h3>
                <p>Start by creating your first service.</p>
            </div>
        <?php else: ?>
            <div class="services-grid-admin">
                <?php foreach ($services as $service): ?>
                    <div class="service-card-admin">
                        <div class="service-card-icon">
                            <?php if (!empty($service['icon_path'])): ?>

                                <?php
                                $icon_display_path = '../../../../Imar-Group-Website/' . htmlspecialchars($service['icon_path']);
                                ?>
                                <img src="<?php echo $icon_display_path; ?>" 
                                     alt="<?php echo htmlspecialchars($service['title']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="white" style="display:none;">
                                    <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
                                </svg>
                            <?php else: ?>
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="white">
                                    <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8 6h8v-8h-8v8zm2-6h4v4h-4v-4z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-card-content">
                            <div class="service-card-title"><?php echo htmlspecialchars($service['title']); ?></div>
                            <div class="service-card-description"><?php echo htmlspecialchars($service['short_description']); ?></div>
                            
                            <div class="service-card-meta">
                                <span class="badge badge-category"><?php echo ucfirst($service['category']); ?></span>
                                <span class="badge badge-status <?php echo $service['status']; ?>"><?php echo ucfirst($service['status']); ?></span>
                                <?php if ($service['is_featured']): ?>
                                    <span class="badge badge-featured">★ Featured</span>
                                <?php endif; ?>
                                <?php if ($service['has_offer']): ?>
                                    <span class="badge badge-offer">🏷️ OFFER</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-card-actions">
                                
                                <?php
                                $client_service_url = $client_website_url . '/' . $services_page . '?service=' . urlencode($service['slug']);
                                ?>
                                <a href="<?php echo $client_service_url; ?>" 
                                   class="btn-small btn-view" 
                                   target="_blank" 
                                   title="View service on client website: <?php echo htmlspecialchars($service['title']); ?>">
                                   View
                                </a>
                                <a href="edit-service.php?id=<?php echo $service['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <?php if ($admin_role !== 'editor'): ?>
                                    <a href="?delete=<?php echo $service['id']; ?>&category=<?php echo $filter_category; ?>&status=<?php echo $filter_status; ?>" 
                                       class="btn-small btn-delete"
                                       onclick="return confirm('⚠️ Are you sure you want to delete this service?\n\nService: <?php echo htmlspecialchars($service['title']); ?>\n\nThis action cannot be undone!')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="add-service.php" class="add-new-btn" title="Add New Service">+</a>
        </div>
    </div>
</div>

<script>
console.log('✓ Services Management loaded');
console.log('✓ Client website URL:', '<?php echo $client_website_url; ?>');
console.log('✓ Services page:', '<?php echo $services_page; ?>');
console.log('✓ Icon paths using 4-level traversal (../../../../Imar-Group-Website/)');

// Test View button URLs on page load
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.btn-view');
    if (viewButtons.length > 0) {
        console.log('✓ Found', viewButtons.length, 'View buttons');
        console.log('✓ Example URL:', viewButtons[0].href);
    }
});
</script>

</body>
</html>