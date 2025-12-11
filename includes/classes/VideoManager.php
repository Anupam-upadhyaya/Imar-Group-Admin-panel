<?php
/**
 * IMAR Group - Video Manager Class
 * File: includes/classes/VideoManager.php
 */

if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class VideoManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Extract YouTube video ID from various URL formats
     */
    private function extractYouTubeId($url_or_id) {
        // If it's already just an ID (11 characters)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url_or_id)) {
            return $url_or_id;
        }
        
        // Extract from various YouTube URL formats
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url_or_id, $matches)) {
                return $matches[1];
            }
        }
        
        throw new Exception("Invalid YouTube URL or ID");
    }
    
    /**
     * Validate duration format (MM:SS or HH:MM:SS)
     */
    private function validateDuration($duration) {
        $pattern = '/^(\d{1,2}:)?\d{1,2}:\d{2}$/';
        if (!preg_match($pattern, $duration)) {
            throw new Exception("Invalid duration format. Use MM:SS or HH:MM:SS");
        }
        return trim($duration);
    }
    
    /**
     * Add new video
     */
    public function addVideo($data) {
        try {
            // Validate required fields
            if (empty($data['youtube_url'])) {
                throw new Exception("YouTube URL/ID is required");
            }
            if (empty($data['title'])) {
                throw new Exception("Title is required");
            }
            if (empty($data['duration'])) {
                throw new Exception("Duration is required");
            }
            
            // Extract and validate YouTube ID
            $youtube_id = $this->extractYouTubeId($data['youtube_url']);
            
            // Check if video already exists
            $check_stmt = $this->conn->prepare("SELECT id FROM video_links WHERE youtube_id = ?");
            $check_stmt->bind_param("s", $youtube_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("This video already exists in the system");
            }
            
            // Validate duration
            $duration = $this->validateDuration($data['duration']);
            
            // Prepare data
            $title = trim($data['title']);
            $category = !empty($data['category']) ? trim($data['category']) : 'general';
            $display_order = !empty($data['display_order']) ? (int)$data['display_order'] : 0;
            $status = !empty($data['status']) ? $data['status'] : 'active';
            
            // Insert into database
            $sql = "INSERT INTO video_links (youtube_id, title, duration, category, display_order, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssIs", $youtube_id, $title, $duration, $category, $display_order, $status);
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'message' => 'Video added successfully',
                'video_id' => $this->conn->insert_id
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update video
     */
    public function updateVideo($video_id, $data) {
        try {
            // Check if video exists
            $video = $this->getVideoById($video_id);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            // Validate required fields
            if (empty($data['youtube_url'])) {
                throw new Exception("YouTube URL/ID is required");
            }
            if (empty($data['title'])) {
                throw new Exception("Title is required");
            }
            if (empty($data['duration'])) {
                throw new Exception("Duration is required");
            }
            
            // Extract and validate YouTube ID
            $youtube_id = $this->extractYouTubeId($data['youtube_url']);
            
            // Check if YouTube ID is used by another video
            $check_stmt = $this->conn->prepare("SELECT id FROM video_links WHERE youtube_id = ? AND id != ?");
            $check_stmt->bind_param("si", $youtube_id, $video_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("This YouTube video is already linked to another entry");
            }
            
            // Validate duration
            $duration = $this->validateDuration($data['duration']);
            
            // Prepare data
            $title = trim($data['title']);
            $category = !empty($data['category']) ? trim($data['category']) : $video['category'];
            $display_order = isset($data['display_order']) ? (int)$data['display_order'] : $video['display_order'];
            $status = !empty($data['status']) ? $data['status'] : $video['status'];
            
            // Update database
            $sql = "UPDATE video_links SET 
                    youtube_id = ?, title = ?, duration = ?, category = ?, 
                    display_order = ?, status = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssisi", $youtube_id, $title, $duration, $category, $display_order, $status, $video_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'message' => 'Video updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete video
     */
    public function deleteVideo($video_id) {
        try {
            $video = $this->getVideoById($video_id);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            $stmt = $this->conn->prepare("DELETE FROM video_links WHERE id = ?");
            $stmt->bind_param("i", $video_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'message' => 'Video deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video by ID
     */
    public function getVideoById($video_id) {
        $stmt = $this->conn->prepare("SELECT * FROM video_links WHERE id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all videos
     */
    public function getAllVideos($status = null) {
        if ($status) {
            $stmt = $this->conn->prepare("SELECT * FROM video_links WHERE status = ? ORDER BY display_order ASC, created_at DESC");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query("SELECT * FROM video_links ORDER BY display_order ASC, created_at DESC");
        }
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
        return $videos;
    }
    
    /**
     * Get active videos for frontend
     */
    public function getActiveVideos($limit = null) {
        $sql = "SELECT * FROM video_links WHERE status = 'active' ORDER BY display_order ASC, created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $result = $this->conn->query($sql);
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
        return $videos;
    }
    
    /**
     * Update display order
     */
    public function updateDisplayOrder($video_id, $order) {
        $stmt = $this->conn->prepare("UPDATE video_links SET display_order = ? WHERE id = ?");
        $stmt->bind_param("ii", $order, $video_id);
        return $stmt->execute();
    }
    
    /**
     * Toggle video status
     */
    public function toggleStatus($video_id) {
        $video = $this->getVideoById($video_id);
        if (!$video) {
            return ['success' => false, 'message' => 'Video not found'];
        }
        
        $new_status = ($video['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $this->conn->prepare("UPDATE video_links SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $video_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Video status updated',
                'new_status' => $new_status
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update status'];
    }
}