<?php
/**
 * DIAGNOSTIC TEST FILE
 * Save this as: C:\xampp\htdocs\ecommerce\diagnostic.php
 * Then open: http://localhost/ecommerce/diagnostic.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>E-Commerce Diagnostic Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .pass { border-left: 5px solid #28a745; }
        .fail { border-left: 5px solid #dc3545; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 0; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #667eea; color: white; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
        .solution { background: #fff3cd; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 E-Commerce Diagnostic Test</h1>
    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php
$allTestsPassed = true;

// TEST 1: PHP Version
echo '<div class="test pass">';
echo '<h2>✅ Test 1: PHP Environment</h2>';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Server:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
echo '<p class="success">PHP is working correctly!</p>';
echo '</div>';

// TEST 2: File Structure
echo '<div class="test ';
$filesExist = file_exists('config.php') && 
              file_exists('index.html') && 
              file_exists('api/products.php') &&
              file_exists('api/auth.php');
echo $filesExist ? 'pass' : 'fail';
echo '">';
echo '<h2>' . ($filesExist ? '✅' : '❌') . ' Test 2: File Structure</h2>';

$files = [
    'config.php' => file_exists('config.php'),
    'index.html' => file_exists('index.html'),
    'styles.css' => file_exists('styles.css'),
    'script.js' => file_exists('script.js'),
    'api/products.php' => file_exists('api/products.php'),
    'api/auth.php' => file_exists('api/auth.php'),
    'api/orders.php' => file_exists('api/orders.php'),
    'api/categories.php' => file_exists('api/categories.php'),
];

echo '<table>';
echo '<tr><th>File</th><th>Status</th></tr>';
foreach ($files as $file => $exists) {
    echo '<tr>';
    echo '<td>' . $file . '</td>';
    echo '<td>' . ($exists ? '<span class="success">✓ Found</span>' : '<span class="error">✗ Missing</span>') . '</td>';
    echo '</tr>';
}
echo '</table>';

if (!$filesExist) {
    $allTestsPassed = false;
    echo '<div class="solution">';
    echo '<strong>Solution:</strong> Make sure all files are in correct locations:<br>';
    echo '<div class="code">ecommerce/<br>├── api/<br>│   ├── auth.php<br>│   ├── products.php<br>│   ├── orders.php<br>│   └── categories.php<br>├── config.php<br>├── index.html<br>├── styles.css<br>└── script.js</div>';
    echo '</div>';
} else {
    echo '<p class="success">All files found in correct locations!</p>';
}
echo '</div>';

// TEST 3: Database Connection
echo '<div class="test ';
$dbConnected = false;
$conn = @new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    echo 'fail">';
    echo '<h2>❌ Test 3: Database Connection</h2>';
    echo '<p class="error">Failed to connect: ' . $conn->connect_error . '</p>';
    echo '<div class="solution">';
    echo '<strong>Solution:</strong><br>';
    echo '1. Open XAMPP Control Panel<br>';
    echo '2. Make sure MySQL shows "Running" in GREEN<br>';
    echo '3. If not, click "Start" next to MySQL<br>';
    echo '</div>';
    $allTestsPassed = false;
} else {
    $dbConnected = true;
    echo 'pass">';
    echo '<h2>✅ Test 3: Database Connection</h2>';
    echo '<p class="success">Connected to MySQL successfully!</p>';
    echo '<p><strong>Host:</strong> localhost</p>';
    echo '<p><strong>Database:</strong> ecommerce_db</p>';
}
echo '</div>';

// TEST 4: Tables Check
if ($dbConnected) {
    echo '<div class="test ';
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result->num_rows;
    $hasAllTables = $tableCount >= 6;
    
    echo $hasAllTables ? 'pass' : 'fail';
    echo '">';
    echo '<h2>' . ($hasAllTables ? '✅' : '❌') . ' Test 4: Database Tables</h2>';
    echo '<p><strong>Tables found:</strong> ' . $tableCount . ' (Expected: 6)</p>';
    
    if ($tableCount > 0) {
        echo '<table>';
        echo '<tr><th>#</th><th>Table Name</th></tr>';
        $i = 1;
        $result->data_seek(0);
        while($row = $result->fetch_array()) {
            echo '<tr><td>' . $i++ . '</td><td>' . $row[0] . '</td></tr>';
        }
        echo '</table>';
    }
    
    if (!$hasAllTables) {
        $allTestsPassed = false;
        echo '<div class="solution">';
        echo '<strong>Solution:</strong> Import database.sql in phpMyAdmin<br>';
        echo '1. Go to: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a><br>';
        echo '2. Click on "ecommerce_db" in left sidebar<br>';
        echo '3. Click "Import" tab<br>';
        echo '4. Choose your database.sql file<br>';
        echo '5. Click "Go"<br>';
        echo '</div>';
    } else {
        echo '<p class="success">All tables exist!</p>';
    }
    echo '</div>';
    
    // TEST 5: Products Data
    echo '<div class="test ';
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    $productCount = $row['count'];
    $hasProducts = $productCount > 0;
    
    echo $hasProducts ? 'pass' : 'fail';
    echo '">';
    echo '<h2>' . ($hasProducts ? '✅' : '❌') . ' Test 5: Products Data</h2>';
    echo '<p><strong>Products in database:</strong> ' . $productCount . '</p>';
    
    if ($hasProducts) {
        $result = $conn->query("SELECT product_id, product_name, price, stock_quantity FROM products LIMIT 10");
        echo '<table>';
        echo '<tr><th>ID</th><th>Product Name</th><th>Price</th><th>Stock</th></tr>';
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['product_id'] . '</td>';
            echo '<td>' . $row['product_name'] . '</td>';
            echo '<td>$' . number_format($row['price'], 2) . '</td>';
            echo '<td>' . $row['stock_quantity'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p class="success">Products data looks good!</p>';
    } else {
        $allTestsPassed = false;
        echo '<p class="error">No products found in database!</p>';
        echo '<div class="solution">';
        echo '<strong>Solution:</strong> Import database.sql with sample products<br>';
        echo '1. Go to phpMyAdmin<br>';
        echo '2. Click "ecommerce_db" → "Import"<br>';
        echo '3. Select database.sql<br>';
        echo '4. Make sure "Format" is SQL<br>';
        echo '5. Click "Go"<br>';
        echo '</div>';
    }
    echo '</div>';
    
    // TEST 6: Categories Data
    echo '<div class="test ';
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();
    $categoryCount = $row['count'];
    $hasCategories = $categoryCount > 0;
    
    echo $hasCategories ? 'pass' : 'fail';
    echo '">';
    echo '<h2>' . ($hasCategories ? '✅' : '❌') . ' Test 6: Categories Data</h2>';
    echo '<p><strong>Categories in database:</strong> ' . $categoryCount . '</p>';
    
    if ($hasCategories) {
        $result = $conn->query("SELECT category_id, category_name, description FROM categories");
        echo '<table>';
        echo '<tr><th>ID</th><th>Category Name</th><th>Description</th></tr>';
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['category_id'] . '</td>';
            echo '<td>' . $row['category_name'] . '</td>';
            echo '<td>' . $row['description'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p class="success">Categories data looks good!</p>';
    } else {
        echo '<p class="error">No categories found!</p>';
    }
    echo '</div>';
    
    // TEST 7: Users Data
    echo '<div class="test ';
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    $userCount = $row['count'];
    $hasUsers = $userCount > 0;
    
    echo $hasUsers ? 'pass' : 'fail';
    echo '">';
    echo '<h2>' . ($hasUsers ? '✅' : '❌') . ' Test 7: Users Data</h2>';
    echo '<p><strong>Users in database:</strong> ' . $userCount . '</p>';
    
    if ($hasUsers) {
        $result = $conn->query("SELECT user_id, username, email, role FROM users");
        echo '<table>';
        echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>';
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['user_id'] . '</td>';
            echo '<td>' . $row['username'] . '</td>';
            echo '<td>' . $row['email'] . '</td>';
            echo '<td>' . $row['role'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p class="success">Users data looks good!</p>';
        echo '<p class="info"><strong>Default Admin:</strong> username: <code>admin</code>, password: <code>admin123</code></p>';
    } else {
        echo '<p class="error">No users found!</p>';
    }
    echo '</div>';
    
    $conn->close();
}

// TEST 8: API Accessibility
echo '<div class="test ';
$apiAccessible = file_exists('api/products.php');
echo $apiAccessible ? 'pass' : 'fail';
echo '">';
echo '<h2>' . ($apiAccessible ? '✅' : '❌') . ' Test 8: API Files</h2>';

if ($apiAccessible) {
    echo '<p class="success">API files are accessible!</p>';
    echo '<p><strong>Test API endpoints:</strong></p>';
    echo '<ul>';
    echo '<li><a href="api/products.php" target="_blank">Products API</a> - Should return JSON with products</li>';
    echo '<li><a href="api/categories.php" target="_blank">Categories API</a> - Should return JSON with categories</li>';
    echo '</ul>';
} else {
    $allTestsPassed = false;
    echo '<p class="error">API files not found!</p>';
    echo '<div class="solution">';
    echo '<strong>Solution:</strong> Make sure api/ folder exists with all PHP files inside it';
    echo '</div>';
}
echo '</div>';

// FINAL SUMMARY
echo '<div class="test ' . ($allTestsPassed ? 'pass' : 'fail') . '" style="margin-top: 20px;">';
echo '<h2>' . ($allTestsPassed ? '🎉 All Tests Passed!' : '⚠️ Some Tests Failed') . '</h2>';

if ($allTestsPassed) {
    echo '<p class="success" style="font-size: 18px;">Your e-commerce system is ready to use!</p>';
    echo '<p><strong>Next steps:</strong></p>';
    echo '<ol>';
    echo '<li>Open your website: <a href="index.html">index.html</a></li>';
    echo '<li>You should see products displayed</li>';
    echo '<li>Login with: username <code>admin</code>, password <code>admin123</code></li>';
    echo '<li>Start testing features!</li>';
    echo '</ol>';
} else {
    echo '<p class="error" style="font-size: 18px;">Please fix the failed tests above before proceeding.</p>';
    echo '<p><strong>Common solutions:</strong></p>';
    echo '<ul>';
    echo '<li>Make sure XAMPP Apache and MySQL are running (green)</li>';
    echo '<li>Import database.sql in phpMyAdmin</li>';
    echo '<li>Check that all files are in correct folders</li>';
    echo '<li>Verify config.php has correct database credentials</li>';
    echo '</ul>';
}
echo '</div>';

echo '<div style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 5px;">';
echo '<h3>📋 Quick Links</h3>';
echo '<ul>';
echo '<li><a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a> - Manage database</li>';
echo '<li><a href="index.html">Your Website</a> - Main application</li>';
echo '<li><a href="api/products.php" target="_blank">Test Products API</a></li>';
echo '<li><a href="diagnostic.php">Refresh This Page</a> - Run tests again</li>';
echo '</ul>';
echo '</div>';
?>

</body>
</html>