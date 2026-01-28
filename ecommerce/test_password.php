<?php
// Test password verification

$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$test_password = 'admin123';

echo "<h2>Password Verification Test</h2>";
echo "<p><strong>Testing password:</strong> admin123</p>";
echo "<p><strong>Against hash:</strong> $stored_hash</p>";

if (password_verify($test_password, $stored_hash)) {
    echo "<p style='color: green; font-weight: bold;'>✅ PASSWORD MATCHES!</p>";
    echo "<p>The hash is correct for password 'admin123'</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PASSWORD DOES NOT MATCH!</p>";
    echo "<p>The hash is NOT for password 'admin123'</p>";
}

echo "<hr>";
echo "<h3>Now checking database...</h3>";

// Check what's in database
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT username, password_hash FROM users WHERE username = 'admin'");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Database username:</strong> " . $row['username'] . "</p>";
    echo "<p><strong>Database hash:</strong> " . $row['password_hash'] . "</p>";
    
    if (password_verify('admin123', $row['password_hash'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ DATABASE PASSWORD IS CORRECT!</p>";
        echo "<p>Login should work now!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ DATABASE PASSWORD IS WRONG!</p>";
        echo "<p>Need to update password hash in database!</p>";
        echo "<p><strong>Solution:</strong> Run the SQL query from Step 2 above</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Admin user not found in database!</p>";
    echo "<p><strong>Solution:</strong> Run the SQL query from Step 2 above to create admin user</p>";
}

$conn->close();
?>