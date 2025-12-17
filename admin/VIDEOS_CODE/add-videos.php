<?php
/**
 * IMAR Group Admin Panel - Add Video
 * File: admin/VIDEOS_CODE/add-video.php
 */

session_start();
define('SECURE_ACCESS', true);

require_once '../../config/config.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'editor';
$admin_id = $_SESSION['admin_id'];

$error_message = '';
$success_message = '';

// Function to extract YouTube ID from various URL formats
function extractYouTubeId($url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',      // Standard URL
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',        // Embed URL
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',                  // Shortened URL
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',            // V URL
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/'        // Shorts URL
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    return false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Validation
    if (empty($title)) {
        $error_message = "Video title is required.";
    } elseif (empty($youtube_url)) {
        $error_message = "YouTube URL is required.";
    } else {
        $youtube_id = extractYouTubeId($youtube_url);
        
        if (!$youtube_id) {
            $error_message = "Invalid YouTube URL. Please provide a valid YouTube video link.";
        } else {
            // Generate thumbnail URL
            $thumbnail_url = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
            
            // Insert into database (Matches your SCHEMA)
            // Columns: title, youtube_url, youtube_id, thumbnail_url, duration, status, display_order, created_by
            $stmt = $conn->prepare("INSERT INTO videos (title, youtube_url, youtube_id, thumbnail_url, duration, status, display_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $title, $youtube_url, $youtube_id, $thumbnail_url, $duration, $status, $display_order, $admin_id);
            
            if ($stmt->execute()) {
                $video_id = $stmt->insert_id;
                $auth->logActivity($admin_id, 'added_video', 'videos', $video_id);
                
                $success_message = "Video added successfully!";
                echo "<script>setTimeout(function() { window.location.href = 'videos.php'; }, 2000);</script>";
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
        }
    }
}

$max_order = $conn->query("SELECT MAX(display_order) as max_order FROM videos")->fetch_assoc()['max_order'] ?? 0;
$suggested_order = $max_order + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Video - IMAR Group Admin</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background-color: #f8fafc;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4f46e5;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            flex: 1;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .required { color: #ef4444; }
        
        .helper-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }
        
        /* Preview Section Styling */
        .preview-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            background: #f8fafc;
            margin: 20px 0;
        }
        
        .preview-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        #videoPreview {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            display: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        #videoPreview iframe {
            width: 100%;
            height: 100%;
        }

        .empty-preview-state {
            height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Add New Video</h1>
                <p style="color: #64748b; margin-top: 4px;">Update your video carousel with YouTube content</p>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $admin_initials; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: #64748b;"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <svg style="margin-right: 8px" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <?php echo $success_message; ?> Redirecting...
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <svg style="margin-right: 8px" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="videoForm">
                <div class="form-group">
                    <label for="title">Video Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="e.g. Smart Investment Strategies" required>
                    <div class="helper-text">This title will appear as the heading in the carousel.</div>
                </div>

                <div class="form-group">
                    <label for="youtube_url">YouTube Video URL <span class="required">*</span></label>
                    <input type="text" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                    <div class="helper-text">Paste the full URL or the Video ID (e.g., 15Gr4BAJi00).</div>
                </div>

                <div class="preview-card">
                    <div class="preview-title">Live Preview</div>
                    <div id="videoPreview">
                        <iframe id="previewIframe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <div id="emptyPreview" class="empty-preview-state">
                        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span>Enter a valid URL to preview</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" id="duration" name="duration" placeholder="e.g. 05:30">
                        <div class="helper-text">Format: MM:SS</div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active">Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $suggested_order; ?>" min="0">
                        <div class="helper-text">Lower numbers appear first in the carousel.</div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="videos.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Save Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Utility to extract ID from YouTube URLs
 */
function extractYouTubeId(url) {
    const patterns = [
        /youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/,
        /youtube\.com\/embed\/([a-zA-Z0-9_-]+)/,
        /youtu\.be\/([a-zA-Z0-9_-]+)/,
        /youtube\.com\/v\/([a-zA-Z0-9_-]+)/,
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/
    ];
    
    for (let pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) return match[1];
    }
    
    // Check if user just entered the 11-char ID
    if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
    
    return null;
}

/**
 * Handle Live Preview logic
 */
const urlInput = document.getElementById('youtube_url');
const videoPreview = document.getElementById('videoPreview');
const emptyPreview = document.getElementById('emptyPreview');
const previewIframe = document.getElementById('previewIframe');

urlInput.addEventListener('input', function() {
    const url = this.value.trim();
    
    if (url) {
        const videoId = extractYouTubeId(url);
        
        if (videoId) {
            previewIframe.src = `https://www.youtube.com/embed/${videoId}`;
            videoPreview.style.display = 'block';
            emptyPreview.style.display = 'none';
        } else {
            videoPreview.style.display = 'none';
            emptyPreview.style.display = 'flex';
            emptyPreview.innerHTML = '<span style="color:#ef4444">Invalid YouTube URL</span>';
        }
    } else {
        videoPreview.style.display = 'none';
        emptyPreview.style.display = 'flex';
        emptyPreview.innerHTML = '<span>Enter a valid URL to preview</span>';
    }
});
</script>

</body>
</html>