<?php
session_start();
define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth($conn);

$auth->logout();

header('Location: login.php?logout=success');
exit();
?>