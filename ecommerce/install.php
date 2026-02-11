<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if already installed
if (file_exists('includes/config.php')) {
    die('Francoder is already installed. Remove this file for security.');
}

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'francoder';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $site_url = $_POST['site_url'] ?? 'http://localhost/francoder/';
    
    // Test database connection
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute SQL file
        $sql = file_get_contents('database/francoder.sql');
        $pdo->exec($sql);
        
        // Create config file
        $config_content = <<<EOT
<?php
session_start();

define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
try {
    \$pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException \$e) {
    die("Database connection failed: " . \$e->getMessage());
}

// Site configuration
define('SITE_NAME', 'Francoder Electronics');
define('SITE_URL', '$site_url');

// Timezone
date_default_timezone_set('UTC');

// CSRF token
if (empty(\$_SESSION['csrf_token'])) {
    \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
EOT;
        
        file_put_contents('includes/config.php', $config_content);
        
        // Create assets directories
        if (!file_exists('assets/images')) {
            mkdir('assets/images', 0777, true);
        }
        
        // Create default product image
        copy('https://via.placeholder.com/300x300', 'assets/images/default-product.jpg');
        
        // Remove install file
        unlink(__FILE__);
        
        header('Location: index.php?installed=1');
        exit();
        
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Francoder</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #34495e; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #3498db; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; }
        button:hover { background: #2980b9; }
        .error { background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #2ecc71; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Francoder Installation</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="francoder" required>
            </div>
            
            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" value="root" required>
            </div>
            
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass">
            </div>
            
            <div class="form-group">
                <label>Site URL</label>
                <input type="url" name="site_url" value="http://localhost/francoder/" required>
                <small>Include trailing slash (/)</small>
            </div>
            
            <button type="submit">Install Francoder</button>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h3>Default Login Credentials:</h3>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>Customer:</strong> customer1 / customer123</p>
        </div>
    </div>
</body>
</html>