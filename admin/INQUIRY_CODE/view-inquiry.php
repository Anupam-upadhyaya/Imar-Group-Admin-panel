<?php
/**
 * IMAR Group Admin Panel - View Inquiry Detail
 * File: admin/view-inquiry.php
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
$admin_id = $_SESSION['admin_id'];

// Get inquiry ID
$inquiry_id = $_GET['id'] ?? 0;

if (!$inquiry_id) {
    header('Location: inquiries.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    $stmt = $conn->prepare("UPDATE inquiries SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $admin_notes, $inquiry_id);
    $stmt->execute();
    
    // Log activity
    $auth->logActivity($admin_id, 'updated_inquiry_status', 'inquiries', $inquiry_id, "Status changed to: $new_status");
    
    $success_message = "Inquiry updated successfully!";
}
// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry'])) {
    $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->bind_param("i", $inquiry_id);
    $stmt->execute();

    // Log activity
    $auth->logActivity($admin_id, 'deleted_inquiry', 'inquiries', $inquiry_id);

    header('Location: inquiries.php?deleted=1');
    exit();
}

// Get inquiry details
$stmt = $conn->prepare("SELECT * FROM inquiries WHERE id = ?");
$stmt->bind_param("i", $inquiry_id);
$stmt->execute();
$result = $stmt->get_result();
$inquiry = $result->fetch_assoc();

if (!$inquiry) {
    header('Location: inquiries.php');
    exit();
}

// Mark as read if it's new
// When fetching the inquiry
if ($inquiry['status'] === 'new') {
    $stmt = $conn->prepare("UPDATE inquiries SET status = 'reading', read_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $inquiry_id);
    $stmt->execute();
    $inquiry['status'] = 'reading'; // update local variable for display
    // Log activity
    $auth->logActivity($admin_id, 'viewed_inquiry', 'inquiries', $inquiry_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiry #<?php echo $inquiry_id; ?> - IMAR Admin</title>
<link rel="stylesheet" href="../../css/styles.css">
<link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .inquiry-detail-container {
            max-width: 900px;
        }
        
        .inquiry-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .inquiry-header {
            display: flex;
            justify-content: flex-start;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .inquiry-title h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .inquiry-meta {
            font-size: 14px;
            color: #6b7280;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 125px;
        }
        
        .status-badge.new { background: #dbeafe; color: #1e40af; }
        .status-badge.reading { background: #fc900259; color: #fc9002ff; }
        .status-badge.responded { background: #d1fae5; color: #065f46; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-item label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .info-item value {
            display: block;
            font-size: 15px;
            color: #0f172a;
            font-weight: 500;
        }
        
        .message-section {
            margin: 25px 0;
        }
        
        .message-section label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .message-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4f46e5;
            font-size: 14px;
            line-height: 1.6;
            color: #374151;
        }
        
        .action-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f3f4f6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #6ee7b7;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

  <div class="dashboard">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
    <div class="dashboard-header">
        <h1>Inquiry Details</h1>
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

    <div class="inquiry-detail-container">
        <a href="inquiries.php" class="back-link">&larr; Back to Inquiries</a>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="inquiry-card">
            <!-- Header -->
            <div class="inquiry-header">
                <div class="inquiry-title">
                    <h2>Inquiry #<?php echo $inquiry['id']; ?></h2>
                    <div class="inquiry-meta">
                        Submitted on <?php echo date('F d, Y \a\t h:i A', strtotime($inquiry['created_at'])); ?>
                    </div>
                </div>
                <span class="status-badge <?php echo $inquiry['status']; ?>">
                    <?php echo ucfirst($inquiry['status']); ?>
                </span>
            </div>

            <!-- Customer Info -->
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?></value>
                </div>
                <div class="info-item">
                    <label>Email Address</label>
                    <value><a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" style="color: #4f46e5;"><?php echo htmlspecialchars($inquiry['email']); ?></a></value>
                </div>
                <div class="info-item">
                    <label>Phone Number</label>
                    <value><a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" style="color: #4f46e5;"><?php echo htmlspecialchars($inquiry['phone']); ?></a></value>
                </div>
                <div class="info-item">
                    <label>Appointment Date</label>
                    <value><?php echo htmlspecialchars($inquiry['appointment_date']); ?></value>
                </div>
            </div>

            <!-- Message Section -->
            <div class="message-section">
                <label>Message / Inquiry</label>
                <div class="message-box">
                    <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                </div>
            </div>

            <!-- Admin Notes & Update Form -->
            <div class="action-section">
                <h3 style="margin-bottom: 20px;">Update Inquiry</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" required>
                            <option value="new" <?php echo $inquiry['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="reading" <?php echo $inquiry['status'] === 'reading' ? 'selected' : ''; ?>>Reading</option>
                            <option value="responded" <?php echo $inquiry['status'] === 'responded' ? 'selected' : ''; ?>>Responded</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="admin_notes">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" id="admin_notes" rows="4" placeholder="Add internal notes about this inquiry..."><?php echo htmlspecialchars($inquiry['admin_notes'] ?? ''); ?></textarea>
                    </div>

              <div class="btn-group" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <!-- Update Form -->
    <form method="POST" action="">
        <button type="submit" name="update_status" class="btn btn-primary">Update Inquiry</button>
    </form>

    <!-- Delete Form -->
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
        <input type="hidden" name="delete_inquiry" value="1">
        <button type="submit" class="btn btn-delete">Delete Inquiry</button>
    </form>
</div>


            </div>
        </div>
    </div>
</div>

</div>

</body>
</html>