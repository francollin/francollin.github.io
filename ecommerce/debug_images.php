<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug: Why Images Aren't Showing</h1>";

// Check 1: Database has images
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
echo "<h2>1. Checking Database</h2>";
$result = $conn->query("SELECT product_id, product_name, image_url FROM products LIMIT 5");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Image URL</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['product_id']}</td>";
    echo "<td>{$row['product_name']}</td>";
    echo "<td style='word-break:break-all;'>" . ($row['image_url'] ? $row['image_url'] : '<span style="color:red">NO IMAGE</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check 2: See what script.js actually contains
echo "<h2>2. Checking script.js</h2>";
$js_file = 'script.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    // Check for image tags
    if (strpos($js_content, '<img') !== false) {
        echo "<p style='color:green'>✅ script.js contains IMG tags</p>";
    } else {
        echo "<p style='color:red'>❌ script.js does NOT contain IMG tags</p>";
        echo "<p>It's still showing emoji 🛍️ instead of images</p>";
    }
    
    // Check specific functions
    if (strpos($js_content, 'product-image') !== false) {
        echo "<p style='color:green'>✅ Found 'product-image' class</p>";
    }
    
    if (strpos($js_content, 'product-detail-image') !== false) {
        echo "<p style='color:green'>✅ Found 'product-detail-image' class</p>";
    }
} else {
    echo "<p style='color:red'>❌ script.js file not found!</p>";
}

// Check 3: See what HTML is being generated
echo "<h2>3. Testing HTML Generation</h2>";
echo "<div id='test-output' style='border:1px solid #ccc;padding:10px;background:#f5f5f5;'></div>";
?>

<script>
// Test what HTML would be generated
const testProduct = {
    product_id: 1,
    product_name: "Test Product",
    price: 99.99,
    stock_quantity: 10,
    image_url: "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop",
    category_name: "Test"
};

// Test displayProducts function (if it exists)
if (typeof displayProducts === 'function') {
    console.log("✅ displayProducts function exists");
} else {
    console.log("❌ displayProducts function NOT FOUND");
}

// Generate HTML sample
const imageUrl = testProduct.image_url || 'https://via.placeholder.com/300x200';
const html = `
    <div class="product-card">
        <div class="product-image">
            <img src="${imageUrl}" alt="${testProduct.product_name}" 
                 onerror="this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Product+Image'">
        </div>
        <div class="product-info">
            <h3>${testProduct.product_name}</h3>
            <p class="price">$${testProduct.price.toFixed(2)}</p>
        </div>
    </div>
`;

document.getElementById('test-output').innerHTML = 
    "<h3>Sample HTML that SHOULD be generated:</h3>" + 
    html +
    "<hr><p>If you see an image above, the HTML is correct.</p>";

// Check if images load
const img = new Image();
img.src = testProduct.image_url;
img.onload = () => console.log("✅ Test image loads successfully");
img.onerror = () => console.log("❌ Test image failed to load");
</script>