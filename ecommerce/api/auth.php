<?php
require_once '../config.php';

// Start session early to avoid headers already sent errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'POST':
        if ($action === 'register') {
            handleRegister($conn);
        } elseif ($action === 'login') {
            handleLogin($conn);
        } else {
            Response::error('Invalid action');
        }
        break;
    
    case 'GET':
        if ($action === 'logout') {
            handleLogout();
        } elseif ($action === 'check') {
            handleCheckSession();
        } else {
            Response::error('Invalid action');
        }
        break;
    
    default:
        Response::error('Method not allowed', 405);
}

function handleRegister($conn) {
    // Get raw POST data
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        Response::error('No data received');
    }
    
    $data = json_decode($input, true);
    
    // Check if JSON decode failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Log for debugging
    file_put_contents('register_debug.log', date('Y-m-d H:i:s') . " - " . print_r($data, true) . "\n", FILE_APPEND);
    
    // Validate input
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        Response::error('All fields are required');
    }
    
    $username = Security::sanitizeInput($data['username']);
    $email = Security::sanitizeInput($data['email']);
    $password = $data['password'];
    $full_name = Security::sanitizeInput($data['full_name']);
    $phone = isset($data['phone']) ? Security::sanitizeInput($data['phone']) : null;
    
    // Validate email
    if (!Security::validateEmail($email)) {
        Response::error('Invalid email format');
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        Response::error('Password must be at least 6 characters');
    }
    
    // Check if username or email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        Response::error('Username or email already exists');
    }
    
    // Hash password
    $password_hash = Security::hashPassword($password);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $phone);
    
    if ($stmt->execute()) {
        Response::success('Registration successful', [
            'user_id' => $conn->insert_id,
            'username' => $username
        ]);
    } else {
        Response::error('Registration failed: ' . $stmt->error);
    }
}

function handleLogin($conn) {
    // Get raw POST data
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        Response::error('No data received');
    }
    
    $data = json_decode($input, true);
    
    // Check if JSON decode failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Log for debugging
    file_put_contents('login_debug.log', date('Y-m-d H:i:s') . " - Received: " . print_r($data, true) . "\n", FILE_APPEND);
    
    // Validate input
    if (empty($data['username']) || empty($data['password'])) {
        Response::error('Username and password are required');
    }
    
    $username = Security::sanitizeInput($data['username']);
    $password = $data['password'];
    
    // Log the query
    file_put_contents('login_debug.log', "Looking for user: $username\n", FILE_APPEND);
    
    // Get user from database - FIXED QUERY
    $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, full_name, role FROM users WHERE username = ? OR email = ?");
    if (!$stmt) {
        Response::error('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    file_put_contents('login_debug.log', "Found " . $result->num_rows . " users\n", FILE_APPEND);
    
    if ($result->num_rows === 0) {
        Response::error('Invalid credentials - User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Log password verification
    file_put_contents('login_debug.log', "Verifying password...\n", FILE_APPEND);
    file_put_contents('login_debug.log', "Stored hash: " . substr($user['password_hash'], 0, 20) . "...\n", FILE_APPEND);
    
    // Verify password
    if (!Security::verifyPassword($password, $user['password_hash'])) {
        file_put_contents('login_debug.log', "Password verification FAILED\n", FILE_APPEND);
        Response::error('Invalid credentials - Password incorrect');
    }
    
    file_put_contents('login_debug.log', "Password verification SUCCESS\n", FILE_APPEND);
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['token'] = Security::generateToken();
    $_SESSION['login_time'] = time();
    
    // Log session creation
    file_put_contents('login_debug.log', "Session created for user_id: " . $user['user_id'] . "\n", FILE_APPEND);
    
    Response::success('Login successful', [
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role']
    ]);
}

function handleLogout() {
    // Clear session data
    $_SESSION = array();
    
    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    Response::success('Logout successful');
}

function handleCheckSession() {
    if (Security::validateSession()) {
        $user = Security::getCurrentUser();
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        Response::success('Session valid', [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $userData['email'],
            'full_name' => $userData['full_name']
        ]);
    } else {
        Response::unauthorized('Session invalid');
    }
}
?>