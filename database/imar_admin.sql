-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2025 at 09:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `imar_admin`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_last_super_admin` (IN `p_user_id` INT)   BEGIN
    DECLARE sa_count INT;
    DECLARE u_role ENUM('super_admin','admin','editor');

    SELECT role INTO u_role
    FROM admin_users
    WHERE id = p_user_id;

    SELECT COUNT(*) INTO sa_count
    FROM admin_users
    WHERE role = 'super_admin' AND status = 'active';

    IF u_role = 'super_admin' AND sa_count <= 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete the last Super Admin';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `table_affected`, `record_id`, `details`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 08:58:46'),
(2, 3, 'logout', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:12:53'),
(3, 3, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:12:57'),
(4, 3, 'logout', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:55:17'),
(5, 3, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:55:19'),
(6, NULL, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:58:50'),
(7, 3, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:59:08'),
(8, NULL, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 09:59:39'),
(9, 3, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:00:23'),
(10, NULL, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:12:24'),
(11, NULL, 'viewed_inquiry', 'inquiries', 4, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:38:37'),
(12, NULL, 'deleted_inquiry', 'inquiries', 4, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:38:53'),
(13, NULL, 'updated_gallery_item', 'gallery', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:39:06'),
(14, NULL, 'deleted_gallery_item', 'gallery', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:39:11'),
(15, NULL, 'added_gallery_item', 'gallery', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:15'),
(16, NULL, 'viewed_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:24'),
(17, NULL, 'updated_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:36'),
(18, NULL, 'deleted_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:41'),
(19, NULL, 'deleted_video', 'videos', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:55'),
(20, NULL, 'updated_service', 'services', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:41:49'),
(21, NULL, 'updated_gallery_item', 'gallery', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:42:33'),
(22, NULL, 'added_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:43:29'),
(23, NULL, 'added_video', 'videos', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:44:36'),
(24, NULL, 'updated_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:45:10'),
(25, NULL, 'updated_inquiry_status', 'inquiries', 3, 'Status changed to: reading', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 11:08:12'),
(26, NULL, 'updated_inquiry_status', 'inquiries', 3, 'Status changed to: reading', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 11:08:22'),
(27, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 06:04:43'),
(28, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 08:23:50'),
(29, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:14:59'),
(30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:29:11'),
(31, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:29:16'),
(32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:18:29'),
(33, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:18:33'),
(34, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:20:06'),
(35, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:25:13'),
(36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:27:27'),
(37, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 11:00:18'),
(38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 11:00:20'),
(39, NULL, 'added_service', 'services', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 11:05:34'),
(40, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 05:38:50'),
(41, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 06:29:32'),
(42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 06:29:34'),
(43, NULL, 'added_user', 'admin_users', 9, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 06:51:20'),
(44, NULL, 'updated_user', 'admin_users', 3, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 06:55:35'),
(45, NULL, 'updated_user', 'admin_users', 8, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 07:21:02'),
(46, NULL, 'updated_user', 'admin_users', 3, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 07:21:21'),
(47, NULL, 'updated_user', 'admin_users', 3, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 07:21:29'),
(48, NULL, 'updated_user', 'admin_users', 3, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 07:21:51'),
(49, NULL, 'viewed_inquiry', 'inquiries', 5, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 08:34:07'),
(50, NULL, 'viewed_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 08:44:19'),
(51, NULL, 'viewed_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 08:45:56'),
(52, NULL, 'viewed_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 08:49:46'),
(53, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:01:10'),
(54, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:01:16'),
(55, 3, 'deleted_user', 'admin_users', 9, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:15:15'),
(56, 3, 'added_user', 'admin_users', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:15:55'),
(57, 3, 'added_user', 'admin_users', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:17:19'),
(58, 3, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:17:50'),
(59, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:18:33'),
(60, NULL, 'deleted_user', 'admin_users', 8, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:18:49'),
(61, NULL, 'deleted_user', 'admin_users', 8, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:18:52'),
(62, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:20:27'),
(63, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:20:57'),
(64, NULL, 'deleted_user', 'admin_users', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:21:41'),
(65, NULL, 'deleted_user', 'admin_users', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:21:47'),
(66, NULL, 'deleted_user', 'admin_users', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:22:20'),
(67, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:25:10'),
(68, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:25:17'),
(69, 3, 'added_user', 'admin_users', 12, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 09:26:28'),
(70, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 05:18:34'),
(71, 3, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:27:59'),
(72, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:28:01'),
(73, 3, 'added_blog_post', 'blog_posts', 12, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:28:44'),
(74, 3, 'updated_blog_post', 'blog_posts', 12, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:29:17'),
(75, NULL, 'deleted_blog_post', 'blog_posts', 12, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:29:34'),
(76, 3, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:45:46'),
(77, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:45:54'),
(78, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:46:21'),
(79, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 06:46:31'),
(80, 3, 'created_user', 'admin_users', 13, 'Created Editor user: subarna nepal (subarnaeditor@gmail.com)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:03:53'),
(81, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:32:22'),
(82, 3, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:53:58'),
(83, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:54:27'),
(84, 3, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:55:03'),
(85, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:55:27'),
(86, NULL, 'added_blog_post', 'blog_posts', 13, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 07:56:13'),
(87, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:08:18'),
(88, NULL, 'viewed_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:08:22'),
(89, NULL, 'viewed_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:10:05'),
(90, NULL, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:21:10'),
(91, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:21:21'),
(92, 3, 'deleted_user', 'admin_users', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:21:51'),
(93, 3, 'deleted_user', 'admin_users', 13, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-28 08:27:12');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','editor') NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `status`, `last_login`, `last_ip`, `dob`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`) VALUES
(3, 'subash nepal', 'admin@imargroup.com', '$2y$12$N5oo41.PdoSmCr3QdEUaN.peplW.4bR5b9ZBZDSlwCkMdSZ8f.66.', 'super_admin', 'avatar_694e378fda75a_1766733711.jpg', 'active', '2025-12-28 14:06:21', '::1', '2001-09-15', '2025-12-03 05:29:35', '2025-12-28 08:21:21', NULL, NULL),
(12, 'Anupam Upadhyaya', 'upadhyayanaupam078@gmail.com', '$2y$10$73RalHAYlFqdR/vv4CfYLe7j7PukXVMeKFfcdSF0lUATPwrmYqIEa', 'super_admin', 'avatar_694e54c4b47f6_1766741188.jpg', 'active', NULL, NULL, '2001-09-15', '2025-12-26 09:26:28', '2025-12-26 09:26:28', NULL, NULL);

--
-- Triggers `admin_users`
--
DELIMITER $$
CREATE TRIGGER `after_admin_role_update` AFTER UPDATE ON `admin_users` FOR EACH ROW BEGIN
    IF OLD.role <> NEW.role THEN
        INSERT INTO permission_changes (
            changed_by,
            target_user,
            old_role,
            new_role,
            ip_address
        ) VALUES (
            @current_admin_id,
            NEW.id,
            OLD.role,
            NEW.role,
            @current_ip
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `post_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `author` varchar(100) NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `read_time` int(11) DEFAULT 5,
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `thumbnail`, `category`, `author`, `tags`, `status`, `views`, `read_time`, `is_featured`, `display_order`, `meta_title`, `meta_description`, `published_date`, `created_by`, `created_at`, `updated_at`) VALUES
(11, 'test blog', 'test-blog', 'blog', 'qwegfa', 'images/blog/blog_1766486608_694a7250cff50.jpg', 'images/blog/thumb_blog_1766486608_694a7250cff50.jpg', 'tax-planning', 'Super Admin', '', 'published', 0, 5, 0, 1, NULL, NULL, '2025-12-23', NULL, '2025-12-23 10:43:29', '2025-12-23 10:45:10'),
(13, 'testing permissions', 'testing-permissions', 'dasfD', 'wsfS', 'images/blog/blog_1766908573_6950e29dde63e.jfif', 'images/blog/thumb_blog_1766908573_6950e29dde63e.jfif', 'tax-planning', 'subarna nepal', 'sdf', 'published', 0, 5, 0, 2, NULL, NULL, '2025-12-28', NULL, '2025-12-28 07:56:13', '2025-12-28 07:56:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `dashboard_stats` (
`new_inquiries` bigint(21)
,`total_gallery` bigint(21)
,`published_posts` bigint(21)
,`active_admins` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` enum('all','offices','team','events','awards') DEFAULT 'all',
  `size_class` enum('normal','large','tall','wide') DEFAULT 'normal',
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `image_path`, `thumbnail_path`, `category`, `size_class`, `is_featured`, `display_order`, `views`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'Imar helping its clients to get their dream home', 'Imar helps you full fill your dreams', 'Gallery/EVENTS/gallery_1766486415_694a718f04f7d.png', 'Gallery/EVENTS/thumb_gallery_1766486415_694a718f04f7d.png', 'events', 'normal', 1, 1, 0, 'active', NULL, '2025-12-23 10:40:15', '2025-12-23 10:42:33');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('new','reading','responded') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `first_name`, `last_name`, `email`, `phone`, `appointment_date`, `message`, `ip_address`, `user_agent`, `status`, `created_at`, `updated_at`, `read_at`, `admin_notes`) VALUES
(2, 'test', 'inquiry', 'Testclient@gmail.com', '0123456987', '2025-12-25', 'test inquiry', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'reading', '2025-12-23 10:25:50', '2025-12-23 10:25:57', '2025-12-23 16:10:57', NULL),
(3, 'test', 'inquiry', 'Testclient@gmail.com', '0123456987', '2025-12-25', 'test 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'reading', '2025-12-23 10:26:20', '2025-12-23 11:08:22', '2025-12-23 16:11:31', 't'),
(5, 'test', '3', 'admin@imargroup.com', '0123456987', '2025-12-25', 'testing notification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'reading', '2025-12-24 06:35:39', '2025-12-26 08:34:07', '2025-12-26 14:19:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempt_time`, `success`) VALUES
(1, 'admin@imargroup.com', '::1', '2025-12-23 14:43:46', 1),
(2, 'admin@imargroup.com', '::1', '2025-12-23 14:57:57', 1),
(3, 'admin@imargroup.com', '::1', '2025-12-23 15:40:19', 1),
(4, 'anupamupadhyaya57@gmail.com', '::1', '2025-12-23 15:43:50', 1),
(5, 'admin@imargroup.com', '::1', '2025-12-23 15:44:08', 1),
(6, 'anupamupadhyaya57@gmail.com', '::1', '2025-12-23 15:44:39', 1),
(7, 'admin@imargroup.com', '::1', '2025-12-23 15:45:23', 1),
(8, 'superadmin@gmail.com', '::1', '2025-12-23 15:57:24', 1),
(9, 'superadmin@gmail.com', '::1', '2025-12-24 11:49:43', 1),
(10, 'admin@imargroup.com', '::1', '2025-12-24 14:08:50', 1),
(11, 'superadmin@gmail.com', '::1', '2025-12-24 15:14:11', 1),
(12, 'superadmin@gmail.com', '::1', '2025-12-24 16:03:29', 1),
(13, 'superadmin@gmail.com', '::1', '2025-12-24 16:05:06', 1),
(14, 'superadmin@gmail.com', '::1', '2025-12-24 16:12:27', 1),
(15, 'superadmin@gmail.com', '::1', '2025-12-24 16:45:20', 1),
(16, 'superadmin@gmail.com', '::1', '2025-12-26 11:23:50', 1),
(17, 'superadmin@gmail.com', '::1', '2025-12-26 12:14:34', 1),
(18, 'admin@imargroup.com', '::1', '2025-12-26 14:46:16', 1),
(21, 'upadhyayanaupam078@gmail.com', '::1', '2025-12-26 15:03:33', 1),
(22, 'anuragupadhyaya@gmail.com', '::1', '2025-12-26 15:05:57', 1),
(23, 'admin@imargroup.com', '::1', '2025-12-26 15:10:17', 1),
(24, 'admin@imargroup.com', '::1', '2025-12-28 11:03:34', 1),
(25, 'admin@imargroup.com', '::1', '2025-12-28 12:13:01', 1),
(26, 'anuragupadhyaya@gmail.com', '::1', '2025-12-28 12:30:54', 1),
(29, 'admin@imargroup.com', '::1', '2025-12-28 12:31:31', 1),
(30, 'admin@imargroup.com', '::1', '2025-12-28 13:17:22', 1),
(33, 'admin@imargroup.com', '::1', '2025-12-28 13:39:27', 1),
(34, 'subarnaeditor@gmail.com', '::1', '2025-12-28 13:40:27', 1),
(35, 'subarnaeditor@gmail.com', '::1', '2025-12-28 13:53:18', 1),
(36, 'admin@imargroup.com', '::1', '2025-12-28 14:06:21', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `permission_audit`
-- (See below for the actual view)
--
CREATE TABLE `permission_audit` (
`id` int(11)
,`created_at` timestamp
,`changed_by_name` varchar(255)
,`changed_by_role` enum('super_admin','admin','editor')
,`target_user_name` varchar(255)
,`old_role` enum('super_admin','admin','editor')
,`new_role` enum('super_admin','admin','editor')
,`reason` text
);

-- --------------------------------------------------------

--
-- Table structure for table `permission_changes`
--

CREATE TABLE `permission_changes` (
  `id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `target_user` int(11) NOT NULL,
  `old_role` enum('super_admin','admin','editor') DEFAULT NULL,
  `new_role` enum('super_admin','admin','editor') NOT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reauth_logs`
--

CREATE TABLE `reauth_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_attempted` varchar(100) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_activities`
-- (See below for the actual view)
--
CREATE TABLE `recent_activities` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `security_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `security_dashboard` (
`log_date` date
,`event_type` enum('unauthorized_access','failed_reauth','suspicious_activity','role_escalation_attempt')
,`incident_count` bigint(21)
,`affected_users` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `event_type` enum('unauthorized_access','failed_reauth','suspicious_activity','role_escalation_attempt') NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `admin_id`, `event_type`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'suspicious_activity', 'RBAC & security system initialized', '127.0.0.1', NULL, '2025-12-28 05:55:52');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon_path` varchar(500) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `full_content` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT 'general',
  `is_featured` tinyint(1) DEFAULT 0,
  `has_offer` tinyint(1) DEFAULT 0 COMMENT 'Shows if service currently has an offer',
  `offer_text` varchar(255) DEFAULT NULL COMMENT 'Optional offer description',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `icon_path`, `short_description`, `full_content`, `category`, `is_featured`, `has_offer`, `offer_text`, `display_order`, `status`, `views`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'auditing', 'auditing', 'Images/Services/service_1766486509_694a71ed7bc0a.png', 'auditing test', 'test details', 'general', 1, 1, '10', 1, 'active', 11, NULL, '2025-12-23 09:25:05', '2025-12-26 08:51:31'),
(2, 'consulting', 'consulting', 'Images/Services/service_1766574334_694bc8fe00c97.png', 'asfd', 'asfda', 'general', 0, 0, '0', 2, 'active', 1, NULL, '2025-12-24 11:05:34', '2025-12-26 05:39:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_deletion_requests`
--

CREATE TABLE `user_deletion_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scheduled_deletion_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` enum('pending','cancelled','completed') NOT NULL DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `user_deletion_requests`
--
DELIMITER $$
CREATE TRIGGER `before_user_deletion_request` BEFORE INSERT ON `user_deletion_requests` FOR EACH ROW BEGIN
    IF NEW.scheduled_deletion_at IS NULL THEN
        SET NEW.scheduled_deletion_at = NOW() + INTERVAL 5 DAY;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `youtube_url` varchar(500) NOT NULL,
  `youtube_id` varchar(50) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `display_order` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `youtube_url`, `youtube_id`, `thumbnail_url`, `duration`, `status`, `display_order`, `views`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'reliance spinning mills ipo | reliance spinning mills ipo analysis', 'https://www.youtube.com/watch?v=OIeAYVqXgCM', 'OIeAYVqXgCM', 'https://img.youtube.com/vi/OIeAYVqXgCM/maxresdefault.jpg', '16:28', 'active', 1, 0, NULL, '2025-12-23 10:44:36', '2025-12-23 10:44:36');

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `inquiries` where `inquiries`.`status` = 'new') AS `new_inquiries`, (select count(0) from `gallery` where `gallery`.`status` = 'active') AS `total_gallery`, (select count(0) from `blog_posts` where `blog_posts`.`status` = 'published') AS `published_posts`, (select count(0) from `admin_users` where `admin_users`.`status` = 'active') AS `active_admins` ;

-- --------------------------------------------------------

--
-- Structure for view `permission_audit`
--
DROP TABLE IF EXISTS `permission_audit`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `permission_audit`  AS SELECT `pc`.`id` AS `id`, `pc`.`created_at` AS `created_at`, `a1`.`name` AS `changed_by_name`, `a1`.`role` AS `changed_by_role`, `a2`.`name` AS `target_user_name`, `pc`.`old_role` AS `old_role`, `pc`.`new_role` AS `new_role`, `pc`.`reason` AS `reason` FROM ((`permission_changes` `pc` left join `admin_users` `a1` on(`pc`.`changed_by` = `a1`.`id`)) left join `admin_users` `a2` on(`pc`.`target_user` = `a2`.`id`)) ORDER BY `pc`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `recent_activities`
--
DROP TABLE IF EXISTS `recent_activities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_activities`  AS SELECT `al`.`id` AS `id`, `al`.`action` AS `action`, `al`.`table_affected` AS `table_affected`, `a`.`username` AS `admin_name`, `a`.`name` AS `full_name`, `al`.`created_at` AS `created_at` FROM (`activity_logs` `al` left join `admin_users` `a` on(`al`.`admin_id` = `a`.`id`)) ORDER BY `al`.`created_at` DESC LIMIT 0, 20 ;

-- --------------------------------------------------------

--
-- Structure for view `security_dashboard`
--
DROP TABLE IF EXISTS `security_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `security_dashboard`  AS SELECT cast(`security_logs`.`created_at` as date) AS `log_date`, `security_logs`.`event_type` AS `event_type`, count(0) AS `incident_count`, count(distinct `security_logs`.`admin_id`) AS `affected_users` FROM `security_logs` WHERE `security_logs`.`created_at` >= current_timestamp() - interval 30 day GROUP BY cast(`security_logs`.`created_at` as date), `security_logs`.`event_type` ORDER BY cast(`security_logs`.`created_at` as date) DESC, count(0) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_admin_action` (`admin_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `blog_posts_ibfk_1` (`created_by`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_order` (`display_order`),
  ADD KEY `gallery_ibfk_1` (`created_by`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`attempt_time`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `permission_changes`
--
ALTER TABLE `permission_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_target_user` (`target_user`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `reauth_logs`
--
ALTER TABLE `reauth_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`),
  ADD KEY `display_order` (`display_order`),
  ADD KEY `category` (`category`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status_order` (`status`,`display_order`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_offer` (`has_offer`);

--
-- Indexes for table `user_deletion_requests`
--
ALTER TABLE `user_deletion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_deletion_at` (`scheduled_deletion_at`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `videos_ibfk_1` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `permission_changes`
--
ALTER TABLE `permission_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reauth_logs`
--
ALTER TABLE `reauth_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_deletion_requests`
--
ALTER TABLE `user_deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
