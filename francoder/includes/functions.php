<?php
/**
 * FRANCODER - Helper Functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get cart count for user
 */
function getCartCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Format price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token (alias for validateCsrfToken for backward compatibility)
 * This fixes the function name mismatch in admin panel
 */
function validateCSRF($token) {
    return validateCsrfToken($token);
}

/**
 * Add CSRF token field to form
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Get user by ID
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = [
            'text' => $message,
            'type' => $type
        ];
    }
    header("Location: $url");
    exit();
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Log user activity
 */
function logActivity($pdo, $user_id, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activities (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $ip_address,
            $user_agent
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Check if username exists
 */
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

/**
 * Check if email exists
 */
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

/**
 * Create user session
 */
function createUserSession($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    session_regenerate_id(true);
}

/**
 * Destroy user session
 */
function destroyUserSession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 */
function baseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base = dirname($_SERVER['SCRIPT_NAME']);
    
    if ($base === '/') {
        $base = '';
    }
    
    $url = $protocol . $host . $base;
    
    if ($path) {
        $url .= '/' . ltrim($path, '/');
    }
    
    return $url;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Send email (placeholder function)
 */
function sendEmail($to, $subject, $message, $headers = null) {
    if (!$headers) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@francoder.com\r\n";
        $headers .= "Reply-To: no-reply@francoder.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
    }
    
    error_log("Email would be sent to: $to, Subject: $subject");
    return true;
}

/**
 * Get user's IP address
 */
function getUserIP() {
    $ipaddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    
    return $ipaddress;
}

/**
 * Check if user is logged in and redirect if not
 */
function requireLogin($redirectTo = 'auth/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = currentUrl();
        redirect($redirectTo, 'Please login to continue', 'error');
    }
}

/**
 * Check if user is admin and redirect if not
 */
function requireAdmin($redirectTo = 'index.php') {
    requireLogin();
    
    if (!isAdmin()) {
        redirect($redirectTo, 'Access denied. Admin privileges required.', 'error');
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y, g:i a') {
    return date($format, strtotime($date));
}

/**
 * Get page title
 */
function getPageTitle($page = '') {
    $titles = [
        '' => 'Home - Francoder Electronics',
        'index' => 'Home - Francoder Electronics',
        'products' => 'Products - Francoder Electronics',
        'product-detail' => 'Product Details - Francoder Electronics',
        'cart' => 'Shopping Cart - Francoder Electronics',
        'checkout' => 'Checkout - Francoder Electronics',
        'orders' => 'My Orders - Francoder Electronics',
        'profile' => 'My Profile - Francoder Electronics',
        'login' => 'Login - Francoder Electronics',
        'register' => 'Register - Francoder Electronics',
        'admin' => 'Admin Dashboard - Francoder Electronics',
        'admin-products' => 'Manage Products - Francoder Electronics',
        'admin-orders' => 'Manage Orders - Francoder Electronics',
        'admin-users' => 'Manage Users - Francoder Electronics'
    ];
    
    return $titles[$page] ?? 'Francoder Electronics';
}

/**
 * Check if user can cancel order
 */
function canCancelOrder($order_status) {
    $cancellable_statuses = ['pending', 'processing'];
    return in_array($order_status, $cancellable_statuses);
}

/**
 * Cancel order for customer
 */
function cancelOrder($pdo, $order_id, $user_id) {
    try {
        $pdo->beginTransaction();
        
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, oi.product_id, oi.quantity 
            FROM orders o 
            JOIN order_items oi ON o.order_id = oi.order_id 
            WHERE o.order_id = ? AND o.user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order_items = $stmt->fetchAll();
        
        if (empty($order_items)) {
            throw new Exception("Order not found or unauthorized");
        }
        
        $order = $order_items[0];
        
        // Check if order can be cancelled
        if (!canCancelOrder($order['status'])) {
            throw new Exception("Order cannot be cancelled at this stage");
        }
        
        // Restore product stock
        foreach ($order_items as $item) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ? 
                WHERE product_id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', 
                updated_at = NOW(),
                admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nCancelled by customer on ', NOW())
            WHERE order_id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        
        // Log activity
        logActivity($pdo, $user_id, 'order_cancelled', "Customer cancelled order #{$order_id}");
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order cancellation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete product (admin only)
 */
function deleteProduct($pdo, $product_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM order_items 
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = 0, 
                    updated_at = NOW()
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);
            return "Product deactivated (exists in {$order_count} orders)";
        } else {
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            return "Product deleted successfully";
        }
        
    } catch (PDOException $e) {
        error_log("Product deletion error: " . $e->getMessage());
        return "Failed to delete product: " . $e->getMessage();
    }
}

/**
 * Delete user (admin only)
 */
function deleteUser($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()),
                    username = CONCAT(username, '_deleted_', UNIX_TIMESTAMP()),
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            return "User deactivated (has {$order_count} orders)";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return "User deleted successfully";
        }
        
    } catch (PDOException $e) {
        error_log("User deletion error: " . $e->getMessage());
        return "Failed to delete user: " . $e->getMessage();
    }
}
?>