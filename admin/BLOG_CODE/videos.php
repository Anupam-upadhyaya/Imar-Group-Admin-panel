<?php
/**
 * IMAR Group Admin Panel - Video Management
 * File: admin/videos.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/VideoManager.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$videoManager = new VideoManager($conn);
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $videoManager->addVideo($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update':
                $result = $videoManager->updateVideo($_POST['video_id'], $_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = $videoManager->deleteVideo($_POST['video_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'toggle':
                $result = $videoManager->toggleStatus($_POST['video_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get all videos
$videos = $videoManager->getAllVideos();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Management - IMAR Group Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .video-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .video-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: all 0.3s; }
        .video-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .video-thumbnail { position: relative; width: 100%; aspect-ratio: 16/9; background: #f3f4f6; cursor: pointer; }
        .video-thumbnail iframe { width: 100%; height: 100%; border: none; }
        .video-info { padding: 16px; }
        .video-title { font-weight: 600; color: #111827; margin-bottom: 8px; font-size: 15px; }
        .video-meta { display: flex; gap: 12px; font-size: 13px; color: #6b7280; margin-bottom: 12px; }
        .video-status { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .video-status.active { background: #d1fae5; color: #065f46; }
        .video-status.inactive { background: #fee2e2; color: #991b1b; }
        .video-actions-card { display: flex; gap: 8px; }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 13px; cursor: pointer; border: 1px solid #e5e7eb; background: white; transition: all 0.2s; }
        .action-btn.edit { color: #2563eb; border-color: #2563eb; }
        .action-btn.delete { color: #dc2626; border-color: #dc2626; }
        .action-btn.toggle { color: #059669; border-color: #059669; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h2 { margin: 0; font-size: 24px; color: #111827; }
        .modal-close { cursor: pointer; font-size: 24px; color: #6b7280; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
        .form-actions { display: flex; gap: 12px; margin-top: 24px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .form-hint { font-size: 13px; color: #6b7280; margin-top: 6px; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; fill: #d1d5db; }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm13.66-2.66l6.94 6.94-6.94 6.94-6.94-6.94 6.94-6.94z"/></svg>
                <div class="sidebar-logo-text">
                    <h2>IMAR Group</h2>
                    <p>Admin Panel</p>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="inquiries.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                <span>Inquiries</span>
            </a>
            <a href="gallery.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2z"/></svg>
                <span>Gallery</span>
            </a>
            <a href="blog.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="videos.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                <span>Videos</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Video Management</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="video-actions">
            <p style="color: #6b7280;">Manage YouTube videos displayed on your blog page</p>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add Video</button>
        </div>

        <?php if (empty($videos)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                <h3>No Videos Added</h3>
                <p>Add your first YouTube video to get started</p>
            </div>
        <?php else: ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <div class="video-thumbnail" onclick="playVideo('<?php echo $video['youtube_id']; ?>', this)">
                            <img src="https://img.youtube.com/vi/<?php echo $video['youtube_id']; ?>/maxresdefault.jpg" 
                                 alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="video-info">
                            <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                            <div class="video-meta">
                                <span>⏱️ <?php echo $video['duration']; ?></span>
                                <span>📂 <?php echo ucfirst($video['category']); ?></span>
                                <span class="video-status <?php echo $video['status']; ?>">
                                    <?php echo ucfirst($video['status']); ?>
                                </span>
                            </div>
                            <div class="video-actions-card">
                                <button class="action-btn edit" onclick='openEditModal(<?php echo json_encode($video); ?>)'>Edit</button>
                                <button class="action-btn toggle" onclick="toggleStatus(<?php echo $video['id']; ?>)">
                                    <?php echo $video['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button class="action-btn delete" onclick="deleteVideo(<?php echo $video['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Video Modal -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Video</h2>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        
        <form method="POST" id="videoForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="video_id" id="videoId">
            
            <div class="form-group">
                <label for="youtube_url">YouTube URL or ID *</label>
                <input type="text" id="youtube_url" name="youtube_url" required 
                       placeholder="https://www.youtube.com/watch?v=... or video ID">
                <div class="form-hint">Paste full YouTube URL or just the video ID</div>
            </div>
            
            <div class="form-group">
                <label for="title">Video Title *</label>
                <input type="text" id="title" name="title" required maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="duration">Duration *</label>
                <input type="text" id="duration" name="duration" required 
                       placeholder="10:25" pattern="(\d{1,2}:)?\d{1,2}:\d{2}">
                <div class="form-hint">Format: MM:SS or HH:MM:SS (e.g., 10:25 or 1:30:45)</div>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="e.g., budgeting, investment">
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="0" min="0">
                <div class="form-hint">Lower numbers appear first</div>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Video</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Video';
    document.getElementById('formAction').value = 'add';
    document.getElementById('videoForm').reset();
    document.getElementById('videoModal').classList.add('active');
}

function openEditModal(video) {
    document.getElementById('modalTitle').textContent = 'Edit Video';
    document.getElementById('formAction').value = 'update';
    document.getElementById('videoId').value = video.id;
    document.getElementById('youtube_url').value = video.youtube_id;
    document.getElementById('title').value = video.title;
    document.getElementById('duration').value = video.duration;
    document.getElementById('category').value = video.category;
    document.getElementById('display_order').value = video.display_order;
    document.getElementById('status').value = video.status;
    document.getElementById('videoModal').classList.add('active');
}

function closeModal() {
    document.getElementById('videoModal').classList.remove('active');
}

function playVideo(youtubeId, element) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1`;
    iframe.allow = 'autoplay; encrypted-media';
    iframe.allowFullscreen = true;
    element.innerHTML = '';
    element.appendChild(iframe);
}

function toggleStatus(videoId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="video_id" value="${videoId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteVideo(videoId) {
    if (!confirm('Are you sure you want to delete this video?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="video_id" value="${videoId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Close modal on outside click
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

</body>
</html>