<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Fixing Product Images</h1>";

// Sample images from Unsplash (free to use)
$sample_images = [
    'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop', // Laptop
    'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w-400&h=300&fit=crop', // Smartphone
    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop', // Headphones
    'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=400&h=300&fit=crop', // Keyboard
    'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=400&h=300&fit=crop', // Gaming Console
    'https://images.unsplash.com/photo-1556656793-08538906a9f8?w=400&h=300&fit=crop', // Tablet
    'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop', // Smart Watch
    'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=400&h=300&fit=crop'  // Camera
];

// Get all products
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll();

$updated_count = 0;

foreach ($products as $index => $product) {
    $image_index = $index % count($sample_images);
    $image_url = $sample_images[$image_index];
    
    $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE product_id = ?");
    $stmt->execute([$image_url, $product['product_id']]);
    
    echo "<p>Updated product #{$product['product_id']}: {$product['product_name']}</p>";
    echo "<img src='{$image_url}' width='100' style='margin-left: 20px;'><br><br>";
    
    $updated_count++;
}

echo "<h3 style='color: green;'>✓ Updated {$updated_count} products with images</h3>";
echo "<p><a href='index.php'>View Homepage</a> | <a href='products.php'>View Products</a></p>";

// Also create default image if it doesn't exist
$default_image_path = 'assets/images/default-product.jpg';
if (!file_exists($default_image_path)) {
    echo "<h3>Creating default placeholder image...</h3>";
    
    // Create assets/images directory if it doesn't exist
    if (!is_dir('assets/images')) {
        mkdir('assets/images', 0777, true);
    }
    
    // Create a simple placeholder image using GD library
    $width = 400;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 100, 100, 100);
    $border_color = imagecolorallocate($image, 200, 200, 200);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Add border
    imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
    
    // Add text
    $text = "Product Image";
    $font = 5; // Built-in font
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font, $x, $y, $text, $text_color);
    
    // Save image
    imagejpeg($image, $default_image_path, 80);
    imagedestroy($image);
    
    echo "<p style='color: green;'>✓ Created default placeholder image at {$default_image_path}</p>";
    echo "<img src='{$default_image_path}' width='200'>";
}
?>