<?php
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

define('ENVIRONMENT', 'development');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'imar_admin');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'IMAR Group Admin');
define('SITE_URL', 'http://localhost/Imar_Group_Admin_panel/admin');

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('SESSION_LIFETIME', 1800);
define('SESSION_NAME', 'IMAR_ADMIN_SESSION');

define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('BASE_URL', '/');

define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('GALLERY_PATH', UPLOAD_PATH . 'gallery/');
define('BLOG_PATH', UPLOAD_PATH . 'blog/');

define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/gif','image/webp']);
define('ITEMS_PER_PAGE', 20);

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

date_default_timezone_set('Asia/Kathmandu');
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
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

$db = Database::getInstance();
$conn = $db->getConnection();

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;
?>