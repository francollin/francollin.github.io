<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DEBUG ADMIN PANEL</h1>";
echo "<pre>";

// Check session
echo "=== SESSION DATA ===\n";
print_r($_SESSION);
echo "\n";

// Check if admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
echo "Is Admin: " . ($is_admin ? "YES" : "NO") . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";

// Check database connection
require_once '../includes/config.php';
echo "\n=== DATABASE CONNECTION ===\n";
try {
    $test = $pdo->query("SELECT 1");
    echo "✓ Database connection successful\n";
    
    // Check orders table
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Orders table exists\n";
        
        // Check orders table structure
        $stmt = $pdo->query("DESCRIBE orders");
        echo "\n=== ORDERS TABLE STRUCTURE ===\n";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . " | " . $row['Default'] . "\n";
        }
        
        // Check some sample orders
        $stmt = $pdo->query("SELECT order_id, status, user_id FROM orders LIMIT 5");
        echo "\n=== SAMPLE ORDERS ===\n";
        while ($row = $stmt->fetch()) {
            echo "Order #{$row['order_id']}: Status={$row['status']}, User={$row['user_id']}\n";
        }
    } else {
        echo "✗ Orders table does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// Check functions.php
echo "\n=== FUNCTION CHECKS ===\n";
require_once '../includes/functions.php';

// Test isLoggedIn
echo "isLoggedIn(): " . (isLoggedIn() ? "TRUE" : "FALSE") . "\n";
echo "isAdmin(): " . (isAdmin() ? "TRUE" : "FALSE") . "\n";

// Test CSRF
$token = generateCsrfToken();
echo "CSRF Token generated: " . substr($token, 0, 10) . "...\n";
echo "validateCSRF test: " . (validateCSRF($token) ? "PASS" : "FAIL") . "\n";

// Test direct order update
echo "\n=== TEST DIRECT ORDER UPDATE ===\n";
if (isset($_GET['test_update'])) {
    $order_id = (int)$_GET['test_update'];
    try {
        $sql = "UPDATE orders SET status = 'processing', updated_at = NOW() WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $affected = $stmt->rowCount();
        echo "Direct update affected $affected row(s)\n";
    } catch (Exception $e) {
        echo "Direct update error: " . $e->getMessage() . "\n";
        echo "SQL: $sql\n";
    }
}

// Test form submission simulation
echo "\n=== TEST FORM SUBMISSION ===\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Form submitted!\n";
    print_r($_POST);
    
    if (isset($_POST['csrf_token'])) {
        echo "CSRF Token from form: " . $_POST['csrf_token'] . "\n";
        echo "Session CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
        echo "CSRF Validation: " . (validateCSRF($_POST['csrf_token']) ? "VALID" : "INVALID") . "\n";
    }
}

echo "</pre>";

// Simple test form
?>
<hr>
<h2>Test Form</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <input type="hidden" name="order_id" value="1">
    <input type="hidden" name="update_status" value="1">
    <select name="status">
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
    </select>
    <button type="submit">Test Update</button>
</form>

<hr>
<h2>Quick Links</h2>
<p><a href="orders.php">Go to Orders Page</a></p>
<p><a href="debug_admin.php?test_update=1">Test Update Order #1</a></p>