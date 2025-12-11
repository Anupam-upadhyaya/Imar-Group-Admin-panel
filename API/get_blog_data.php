<?php
/**
 * IMAR Group - Frontend Blog Data API
 * File: api/get_blog_data.php
 * 
 * This file provides JSON data for the frontend blog page
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('SECURE_ACCESS', true);

require_once '../config/config.php';
require_once '../includes/classes/BlogManager.php';
require_once '../includes/classes/VideoManager.php';

$blogManager = new BlogManager($conn);
$videoManager = new VideoManager($conn);

// Get request parameters
$action = $_GET['action'] ?? 'posts';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$category = $_GET['category'] ?? null;
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = $_GET['slug'] ?? null;

try {
    switch ($action) {
        case 'posts':
            // Get published blog posts
            $result = $blogManager->getPublishedPosts($page, $per_page, $category);
            
            // Format posts for frontend
            $formatted_posts = array_map(function($post) {
                return [
                    'id' => (int)$post['id'],
                    'title' => $post['title'],
                    'slug' => $post['slug'],
                    'excerpt' => $post['excerpt'],
                    'category' => $post['category'],
                    'author' => $post['author_name'],
                    'date' => date('Y-m-d', strtotime($post['published_at'] ?? $post['created_at'])),
                    'read_time' => (int)$post['read_time'],
                    'image' => $post['featured_image'] ? BASE_URL . 'uploads/blog/' . $post['featured_image'] : null,
                    'views' => (int)$post['views_count']
                ];
            }, $result['posts']);
            
            echo json_encode([
                'success' => true,
                'data' => $formatted_posts,
                'pagination' => [
                    'current_page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'total' => $result['total'],
                    'total_pages' => $result['total_pages']
                ]
            ]);
            break;
            
        case 'post':
            // Get single post by ID or slug
            if ($post_id) {
                $post = $blogManager->getPostById($post_id);
            } elseif ($slug) {
                $post = $blogManager->getPostBySlug($slug);
            } else {
                throw new Exception('Post ID or slug required');
            }
            
            if (!$post) {
                throw new Exception('Post not found');
            }
            
            // Increment view count
            if ($post_id || $slug) {
                $blogManager->incrementViews($post['id']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => (int)$post['id'],
                    'title' => $post['title'],
                    'slug' => $post['slug'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'category' => $post['category'],
                    'author' => $post['author_name'],
                    'date' => date('Y-m-d', strtotime($post['published_at'] ?? $post['created_at'])),
                    'read_time' => (int)$post['read_time'],
                    'image' => $post['featured_image'] ? BASE_URL . 'uploads/blog/' . $post['featured_image'] : null,
                    'views' => (int)$post['views_count']
                ]
            ]);
            break;
            
        case 'videos':
            // Get active videos
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $videos = $videoManager->getActiveVideos($limit);
            
            $formatted_videos = array_map(function($video) {
                return [
                    'youtube_id' => $video['youtube_id'],
                    'title' => $video['title'],
                    'duration' => $video['duration'],
                    'category' => $video['category'],
                    'thumbnail' => 'https://img.youtube.com/vi/' . $video['youtube_id'] . '/maxresdefault.jpg'
                ];
            }, $videos);
            
            echo json_encode([
                'success' => true,
                'data' => $formatted_videos
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}