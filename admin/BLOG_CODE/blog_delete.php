<?php
/**
 * File: admin/blog_delete.php
 */
?>
<?php
session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/BlogManager.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$blogManager = new BlogManager($conn);
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = $blogManager->deletePost($post_id);

if ($result['success']) {
    header('Location: blog.php?success=' . urlencode($result['message']));
} else {
    header('Location: blog.php?error=' . urlencode($result['message']));
}
exit();
?>