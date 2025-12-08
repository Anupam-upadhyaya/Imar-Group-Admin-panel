<?php
/**
 * IMAR Group Admin Panel - Database Configuration
 * File: config/config.php
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Environment Configuration
define('ENVIRONMENT', 'development'); // Change to 'production' when live

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'imar_admin');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'IMAR Group Admin');
define('SITE_URL', 'http://localhost/Imar_Group_Admin_panel/admin');
define('BASE_PATH', dirname(__DIR__)); // Gets the parent directory of config

// Session Configuration
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'IMAR_ADMIN_SESSION');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('GALLERY_PATH', UPLOAD_PATH . 'gallery/');
define('BLOG_PATH', UPLOAD_PATH . 'blog/');

// Pagination
define('ITEMS_PER_PAGE', 20);

// Error Reporting (Turn off in production)
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Timezone
date_default_timezone_set('Asia/Kathmandu');

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset(DB_CHARSET);
            
        } catch (Exception $e) {
            if (ENVIRONMENT === 'development') {
                die("Database Error: " . $e->getMessage());
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Legacy compatibility: Create simple connection variables for older code
// This allows both $conn (object-oriented) and traditional mysqli usage
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

// Note: $conn is already created above via the Database class
// This ensures compatibility with both:
// 1. Modern code using Database::getInstance()->getConnection()
// 2. Legacy code expecting $conn variable directly
?>