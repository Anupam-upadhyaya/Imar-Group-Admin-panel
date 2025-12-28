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

$error_message = '';
$success_message = '';

$new_inquiries_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'")->fetch_assoc()['count'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    $youtube_id = extractYouTubeId($youtube_url);
    
    if (empty($youtube_url)) {
        $error_message = "YouTube URL is required.";
    } elseif (!$youtube_id) {
        $error_message = "Invalid YouTube URL. Please enter a valid YouTube video link.";
    } else {

        if (empty($title)) {
            $title = "Video " . time();
        }
        
        $thumbnail_url = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
        
        $stmt = $conn->prepare("INSERT INTO videos (title, youtube_url, youtube_id, thumbnail_url, status, display_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiii", $title, $youtube_url, $youtube_id, $thumbnail_url, $status, $display_order, $admin_id);
        
        if ($stmt->execute()) {
            $video_id = $stmt->insert_id;
            $auth->logActivity($admin_id, 'added_video', 'videos', $video_id);
            $success_message = "Video added successfully!";
        } else {
            $error_message = "Database error: " . $stmt->error;
        }
    }
}

if (isset($_GET['delete']) && $admin_role !== 'editor') {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $auth->logActivity($admin_id, 'deleted_video', 'videos', $delete_id);
        $success_message = "Video deleted successfully!";
    }
}

if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $toggle_id = (int)$_GET['toggle'];
    $new_status = $_GET['status'] === 'active' ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE videos SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $toggle_id);
    $stmt->execute();
    
    $success_message = "Video status updated!";
}

$videos = $conn->query("SELECT * FROM videos ORDER BY display_order ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);

$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM videos WHERE status = 'active'")->fetch_assoc()['count'],
    'views' => $conn->query("SELECT SUM(views) as count FROM videos")->fetch_assoc()['count'] ?? 0
];

function extractYouTubeId($url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return false;
}

$max_order = $conn->query("SELECT MAX(display_order) as max_order FROM videos")->fetch_assoc()['max_order'] ?? 0;
$suggested_order = $max_order + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .video-thumbnail {
            position: relative;
            width: 100%;
            padding-top: 56.25%; 
            background: #f3f4f6;
            overflow: hidden;
        }
        
        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ef4444;
        }
        
        .video-content {
            padding: 20px;
        }
        
        .video-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .video-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-order {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .video-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-view {
            background: #10b981;
            color: white;
        }
        
        .btn-view:hover {
            background: #059669;
        }
        
        .btn-toggle {
            background: #f59e0b;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #d97706;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .add-video-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: #374151;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4f46e5;
            outline: none;
        }
        
        .helper-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
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
        
        .youtube-preview {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .youtube-preview.show {
            display: block;
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
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Video Management</h1>
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

        <?php if ($success_message): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✓ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✗ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Total Videos</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3>Active Videos</h3>
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
                        <h3>Total Views</h3>
                        <div class="stat-value"><?php echo number_format($stats['views']); ?></div>
                    </div>
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($videos)): ?>
            <div class="empty-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="#d1d5db">
                    <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                </svg>
                <h3>No Videos Yet</h3>
                <p>Add your first YouTube video above</p>
            </div>
        <?php else: ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <div class="video-thumbnail">
                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/maxresdefault.jpg" 
                                 alt="<?php echo htmlspecialchars($video['title']); ?>"
                                 onerror="this.src='https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/hqdefault.jpg'">
                            <div class="video-play-overlay">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        
                        <div class="video-content">
                            <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                            
                            <div class="video-meta">
                                <span class="badge badge-<?php echo $video['status']; ?>"><?php echo ucfirst($video['status']); ?></span>
                                <span class="badge badge-order">Order: <?php echo $video['display_order']; ?></span>
                            </div>
                            
                            <div class="video-actions">
                                <a href="https://www.youtube.com/watch?v=<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                   target="_blank" class="btn-small btn-view">
                                    <i class="fab fa-youtube"></i> View
                                </a>
                                <a href="?toggle=<?php echo $video['id']; ?>&status=<?php echo $video['status']; ?>" 
                                   class="btn-small btn-toggle">
                                    <?php echo $video['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <?php if ($admin_role !== 'editor'): ?>
                                    <a href="?delete=<?php echo $video['id']; ?>" 
                                       class="btn-small btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this video?')">
                                        Delete
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="add-videos.php" class="add-new-btn" title="Add New video">+</a>
    </div>
</div>

<script>

const youtubeUrlInput = document.getElementById('youtube_url');
if (youtubeUrlInput) {
    youtubeUrlInput.addEventListener('input', function() {
        const url = this.value;
        const preview = document.getElementById('youtubePreview');
        const previewId = document.getElementById('previewId');
        const previewThumb = document.getElementById('previewThumb');
        
        const patterns = [
            /youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/,
            /youtu\.be\/([a-zA-Z0-9_-]+)/,
            /youtube\.com\/embed\/([a-zA-Z0-9_-]+)/,
            /youtube\.com\/v\/([a-zA-Z0-9_-]+)/
        ];
        
        let youtubeId = null;
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                youtubeId = match[1];
                break;
            }
        }
        
        if (youtubeId && preview && previewId && previewThumb) {
            preview.classList.add('show');
            previewId.textContent = youtubeId;
            previewThumb.src = `https://img.youtube.com/vi/${youtubeId}/hqdefault.jpg`;
        } else if (preview) {
            preview.classList.remove('show');
        }
    });
}
</script>

</body>
</html>