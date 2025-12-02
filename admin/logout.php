<?php
/**
 * IMAR Group Admin Panel - Logout Handler
 * File: admin/logout.php
 */

// Start session
session_start();

// Security constant
define('SECURE_ACCESS', true);

// Include configuration and classes
require_once '../config/config.php';
require_once '../includes/classes/Auth.php';

// Initialize Auth
$auth = new Auth($conn);

// Perform logout
$auth->logout();

// Redirect to login page
header('Location: login.php?logout=success');
exit();
?>