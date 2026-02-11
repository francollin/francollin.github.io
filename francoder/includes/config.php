<?php
session_start();

// Database configuration - UPDATE THESE WITH YOUR ACTUAL VALUES
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // XAMPP default username is 'root'
define('DB_PASS', '');      // XAMPP default password is empty
define('DB_NAME', 'francoder');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Test connection
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur'); // Or your timezone

// Site configuration - UPDATE THIS TO MATCH YOUR PROJECT PATH
define('SITE_NAME', 'Francoder Electronics');
define('SITE_URL', 'http://localhost/francoder/'); // Make sure this matches your folder path

// Global error reporting (disable for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>