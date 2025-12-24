-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 11:23 AM
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
(10, 8, 'login', 'admin_users', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:12:24'),
(11, 8, 'viewed_inquiry', 'inquiries', 4, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:38:37'),
(12, 8, 'deleted_inquiry', 'inquiries', 4, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:38:53'),
(13, 8, 'updated_gallery_item', 'gallery', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:39:06'),
(14, 8, 'deleted_gallery_item', 'gallery', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:39:11'),
(15, 8, 'added_gallery_item', 'gallery', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:15'),
(16, 8, 'viewed_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:24'),
(17, 8, 'updated_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:36'),
(18, 8, 'deleted_blog_post', 'blog_posts', 10, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:41'),
(19, 8, 'deleted_video', 'videos', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:40:55'),
(20, 8, 'updated_service', 'services', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:41:49'),
(21, 8, 'updated_gallery_item', 'gallery', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:42:33'),
(22, 8, 'added_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:43:29'),
(23, 8, 'added_video', 'videos', 2, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:44:36'),
(24, 8, 'updated_blog_post', 'blog_posts', 11, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 10:45:10'),
(25, 8, 'updated_inquiry_status', 'inquiries', 3, 'Status changed to: reading', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 11:08:12'),
(26, 8, 'updated_inquiry_status', 'inquiries', 3, 'Status changed to: reading', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 11:08:22'),
(27, 8, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 06:04:43'),
(28, 3, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 08:23:50'),
(29, 8, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:14:59'),
(30, 8, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:29:11'),
(31, 8, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:29:16'),
(32, 8, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:18:29'),
(33, 8, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:18:33'),
(34, 8, 'login', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 10:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','editor') NOT NULL DEFAULT 'editor',
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
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
(3, 'Anupam Upadhyaya', 'admin@imargroup.com', '$2y$12$N5oo41.PdoSmCr3QdEUaN.peplW.4bR5b9ZBZDSlwCkMdSZ8f.66.', 'super_admin', NULL, 'active', '2025-12-24 14:08:50', '::1', '2001-09-15', '2025-12-03 05:29:35', '2025-12-24 08:23:50', NULL, NULL),
(8, 'Super Admin', 'superadmin@gmail.com', '$2y$10$8xMZcBTwJkNgm1CtYI25nugHcD93.WtK3wpoRFRfStvvIxkJu5Api', 'super_admin', NULL, 'active', '2025-12-24 16:05:06', '::1', '2001-09-15', '2025-12-23 10:10:40', '2025-12-24 10:20:06', '4bb2c9847eb6670d25a267415f7c5b45360733e9787378c86a88f143fed9a1ea', '2025-12-24 17:04:47');

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
(11, 'test blog', 'test-blog', 'blog', 'qwegfa', 'images/blog/blog_1766486608_694a7250cff50.jpg', 'images/blog/thumb_blog_1766486608_694a7250cff50.jpg', 'tax-planning', 'Super Admin', '', 'published', 0, 5, 0, 1, NULL, NULL, '2025-12-23', 8, '2025-12-23 10:43:29', '2025-12-23 10:45:10');

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
(2, 'Imar helping its clients to get their dream home', 'Imar helps you full fill your dreams', 'Gallery/EVENTS/gallery_1766486415_694a718f04f7d.png', 'Gallery/EVENTS/thumb_gallery_1766486415_694a718f04f7d.png', 'events', 'normal', 1, 1, 0, 'active', 8, '2025-12-23 10:40:15', '2025-12-23 10:42:33');

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
(5, 'test', '3', 'admin@imargroup.com', '0123456987', '2025-12-25', 'testing notification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'new', '2025-12-24 06:35:39', '2025-12-24 06:35:39', NULL, NULL);

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
(13, 'superadmin@gmail.com', '::1', '2025-12-24 16:05:06', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_activities`
-- (See below for the actual view)
--
CREATE TABLE `recent_activities` (
);

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
(1, 'auditing', 'auditing', 'Images/Services/service_1766486509_694a71ed7bc0a.png', 'auditing test', 'test details', 'general', 1, 1, '10', 1, 'active', 1, NULL, '2025-12-23 09:25:05', '2025-12-23 10:41:49');

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
(2, 'reliance spinning mills ipo | reliance spinning mills ipo analysis', 'https://www.youtube.com/watch?v=OIeAYVqXgCM', 'OIeAYVqXgCM', 'https://img.youtube.com/vi/OIeAYVqXgCM/maxresdefault.jpg', '16:28', 'active', 1, 0, 8, '2025-12-23 10:44:36', '2025-12-23 10:44:36');

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `inquiries` where `inquiries`.`status` = 'new') AS `new_inquiries`, (select count(0) from `gallery` where `gallery`.`status` = 'active') AS `total_gallery`, (select count(0) from `blog_posts` where `blog_posts`.`status` = 'published') AS `published_posts`, (select count(0) from `admin_users` where `admin_users`.`status` = 'active') AS `active_admins` ;

-- --------------------------------------------------------

--
-- Structure for view `recent_activities`
--
DROP TABLE IF EXISTS `recent_activities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_activities`  AS SELECT `al`.`id` AS `id`, `al`.`action` AS `action`, `al`.`table_affected` AS `table_affected`, `a`.`username` AS `admin_name`, `a`.`name` AS `full_name`, `al`.`created_at` AS `created_at` FROM (`activity_logs` `al` left join `admin_users` `a` on(`al`.`admin_id` = `a`.`id`)) ORDER BY `al`.`created_at` DESC LIMIT 0, 20 ;

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
  ADD KEY `idx_action` (`action`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
