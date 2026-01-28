<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Login Debug Tool</h2>";

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<h3>Step 1: Check Users in Database</h3>";

$result = $conn->query("SELECT user_id, username, email, role, SUBSTRING(password_hash, 1, 30) as hash_preview FROM users ORDER BY created_at DESC");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash (preview)</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td><strong>" . $row['username'] . "</strong></td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td><code>" . $row['hash_preview'] . "...</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>✅ Found " . $result->num_rows . " users</p>";
} else {
    echo "<p style='color:red;'>❌ No users found!</p>";
}

echo "<hr>";
echo "<h3>Step 2: Test Password Hashing</h3>";

$test_password = "test123";
$hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "<p><strong>Test Password:</strong> <code>$test_password</code></p>";
echo "<p><strong>Generated Hash:</strong> <code>$hash</code></p>";

if (password_verify($test_password, $hash)) {
    echo "<p style='color:green; font-weight:bold;'>✅ Password hashing/verification WORKS!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Password hashing/verification BROKEN!</p>";
}

echo "<hr>";
echo "<h3>Step 3: Test Your Actual Account</h3>";

echo "<form method='post' style='background:#f5f5f5; padding:20px; border-radius:5px;'>";
echo "<p><label>Username: <input type='text' name='test_username' required></label></p>";
echo "<p><label>Password: <input type='password' name='test_password' required></label></p>";
echo "<p><button type='submit' name='test_login'>Test Login</button></p>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $username = $_POST['test_username'];
    $password = $_POST['test_password'];
    
    echo "<div style='background:#fff3cd; padding:15px; margin-top:10px; border-radius:5px;'>";
    echo "<h4>Testing Login for: <strong>$username</strong></h4>";
    
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>❌ User not found in database!</p>";
    } else {
        $user = $result->fetch_assoc();
        echo "<p>✅ User found in database</p>";
        echo "<p><strong>User ID:</strong> " . $user['user_id'] . "</p>";
        echo "<p><strong>Username:</strong> " . $user['username'] . "</p>";
        echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
        echo "<p><strong>Stored Hash:</strong> <code>" . substr($user['password_hash'], 0, 50) . "...</code></p>";
        echo "<p><strong>Input Password:</strong> <code>$password</code></p>";
        
        echo "<hr>";
        
        // Test password verification
        if (password_verify($password, $user['password_hash'])) {
            echo "<p style='color:green; font-size:18px; font-weight:bold;'>✅✅✅ PASSWORD MATCHES! ✅✅✅</p>";
            echo "<p>Login SHOULD work with these credentials!</p>";
            echo "<p>If login still fails on the website, the problem is in auth.php</p>";
        } else {
            echo "<p style='color:red; font-size:18px; font-weight:bold;'>❌❌❌ PASSWORD DOES NOT MATCH! ❌❌❌</p>";
            echo "<p><strong>Problem:</strong> The password you entered doesn't match the hash in database.</p>";
            echo "<p><strong>Possible causes:</strong></p>";
            echo "<ul>";
            echo "<li>You typed the wrong password</li>";
            echo "<li>The password was hashed incorrectly during registration</li>";
            echo "<li>The database got corrupted</li>";
            echo "</ul>";
            
            // Create a new correct hash
            $correct_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            echo "<hr>";
            echo "<p><strong>FIX:</strong> Run this SQL in phpMyAdmin to update the password:</p>";
            echo "<div style='background:#000; color:#0f0; padding:10px; font-family:monospace; overflow-x:auto;'>";
            echo "UPDATE users SET password_hash = '$correct_hash' WHERE username = '$username';";
            echo "</div>";
        }
    }
    echo "</div>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
    h2, h3 { color: #333; }
    code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
    table { background: white; margin: 10px 0; }
    th { background: #667eea; color: white; }
</style>