<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Product Images</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .product { display: inline-block; margin: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .product img { width: 200px; height: 150px; object-fit: cover; }
    </style>
</head>
<body>
    <h1>Checking Product Images</h1>";

$result = $conn->query("SELECT * FROM products");
while($row = $result->fetch_assoc()) {
    echo "<div class='product'>";
    echo "<h3>" . htmlspecialchars($row['product_name']) . "</h3>";
    if (!empty($row['image_url'])) {
        echo "<img src='" . htmlspecialchars($row['image_url']) . "' 
              alt='" . htmlspecialchars($row['product_name']) . "'
              onerror=\"this.src='https://via.placeholder.com/200x150/667eea/ffffff?text=Image+Error'\">";
        echo "<p>✅ Has image URL</p>";
    } else {
        echo "<img src='https://via.placeholder.com/200x150/ff0000/ffffff?text=No+Image'>";
        echo "<p>❌ No image URL</p>";
    }
    echo "<p>Price: $" . number_format($row['price'], 2) . "</p>";
    echo "</div>";
}

echo "</body></html>";
$conn->close();
?>