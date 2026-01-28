<?php
// Test database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'ecommerce_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection FAILED: " . $conn->connect_error);
}

echo "✅ Database Connected Successfully!<br>";
echo "📊 Database: " . $db . "<br>";

// Test if tables exist
$result = $conn->query("SHOW TABLES");
echo "<br>📋 Tables found: " . $result->num_rows . "<br><br>";

while($row = $result->fetch_array()) {
    echo "✓ " . $row[0] . "<br>";
}

$conn->close();
?>