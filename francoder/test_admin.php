<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

echo "<h1>Testing Admin Functions</h1>";

// Test database connection
try {
    $test = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test CSRF functions
$token = generateCsrfToken();
echo "<p>CSRF Token generated: " . substr($token, 0, 20) . "...</p>";

if (validateCSRF($token)) {
    echo "<p style='color: green;'>✓ CSRF validation works</p>";
} else {
    echo "<p style='color: red;'>✗ CSRF validation failed</p>";
}

// Test order table
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch();
    echo "<p>Total orders in database: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Could not count orders: " . $e->getMessage() . "</p>";
}

// Test if user_activities table exists
try {
    $pdo->query("SELECT 1 FROM user_activities LIMIT 1");
    echo "<p style='color: green;'>✓ user_activities table exists</p>";
} catch (Exception $e) {
    echo "<p style='color: yellow;'>⚠ user_activities table doesn't exist (not critical)</p>";
}

// Test a simple order update
if (isset($_GET['test_order'])) {
    $order_id = (int)$_GET['test_order'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$order_id]);
        echo "<p style='color: green;'>✓ Test order update successful for order #$order_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test order update failed: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Troubleshooting Steps:</h2>";
echo "<ol>";
echo "<li>Make sure the orders table has 'updated_at' column</li>";
echo "<li>Check if you're logged in as admin</li>";
echo "<li>Check PHP error logs</li>";
echo "<li>Test direct SQL update: UPDATE orders SET status = 'processing' WHERE order_id = 1</li>";
echo "</ol>";

// Check orders table structure
try {
    $stmt = $pdo->query("DESCRIBE orders");
    echo "<h3>Orders Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Could not describe orders table: " . $e->getMessage() . "</p>";
}
?>