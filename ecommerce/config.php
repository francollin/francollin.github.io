<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

// Security Configuration
define('JWT_SECRET', 'your-secret-key-change-this-in-production');
define('SESSION_LIFETIME', 3600); // 1 hour

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Connection Class
class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prepared statement helper
    public function executeQuery($sql, $types = '', $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Security Helper Functions
class Security {
    // Sanitize input
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Hash password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate session token
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Validate session
    public static function validateSession() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
            return false;
        }
        
        if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    // Get current user
    public static function getCurrentUser() {
        if (self::validateSession()) {
            return [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    // Check if admin
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return $user && $user['role'] === 'admin';
    }
}

// Response Helper
class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
    
    public static function success($message, $data = null) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }
    
    public static function error($message, $statusCode = 400) {
        self::json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
    
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
}
?>