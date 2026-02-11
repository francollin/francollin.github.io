<?php
require_once 'includes/config.php';

echo "<h1>Adding 20 New Electronics Products</h1>";
echo "<p>This will add 20 new products WITHOUT deleting existing ones</p>";

// Array of 20 new products with actual Unsplash images
$new_products = [
    // Gaming Laptops
    [
        'ASUS ROG Strix Scar 18',
        '18-inch gaming laptop with Intel i9-14900HX, RTX 4090, 32GB RAM',
        3299.99,
        12,
        1,
        'ASUS',
        'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=800&h=600&fit=crop',
        '{"processor": "Intel Core i9-14900HX", "ram": "32GB DDR5", "storage": "2TB NVMe SSD", "display": "18-inch 2.5K 240Hz", "graphics": "NVIDIA RTX 4090 16GB"}'
    ],
    [
        'Lenovo Legion Pro 7i',
        '16-inch gaming laptop with i9-13900HX, RTX 4080, Mini-LED display',
        2799.99,
        18,
        1,
        'Lenovo',
        'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&h=600&fit=crop',
        '{"processor": "Intel Core i9-13900HX", "ram": "32GB DDR5", "storage": "2TB SSD", "display": "16-inch Mini-LED 165Hz", "graphics": "NVIDIA RTX 4080"}'
    ],
    // Premium Smartphones
    [
        'Samsung Galaxy Z Fold 5',
        'Foldable smartphone with 7.6-inch main display, S Pen support',
        1799.99,
        15,
        2,
        'Samsung',
        'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800&h=600&fit=crop',
        '{"storage": "512GB", "ram": "12GB", "main_display": "7.6-inch Dynamic AMOLED 2X", "cover_display": "6.2-inch", "s_pen": "Fold Edition compatible"}'
    ],
    [
        'Google Pixel Fold',
        'Googles first foldable with Tensor G2, dual displays',
        1799.99,
        10,
        2,
        'Google',
        'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=800&h=600&fit=crop',
        '{"storage": "256GB", "ram": "12GB", "main_display": "7.6-inch OLED", "cover_display": "5.8-inch", "camera": "48MP + 10.8MP + 10.8MP"}'
    ],
    // High-End Audio
    [
        'Bose QuietComfort Earbuds II',
        'True wireless earbuds with CustomTune technology',
        299.99,
        45,
        4,
        'Bose',
        'https://images.unsplash.com/photo-1599660662165-58b89c6c9c6e?w=800&h=600&fit=crop',
        '{"battery": "6 hours (24 with case)", "noise_cancelling": "CustomTune adaptive ANC", "fit": "Customizable Fit Kit", "bluetooth": "5.3"}'
    ],
    [
        'Sony WF-1000XM5',
        'True wireless earbuds with AI noise cancellation',
        299.99,
        50,
        4,
        'Sony',
        'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&h=600&fit=crop',
        '{"battery": "8 hours (24 with case)", "noise_cancelling": "Integrated Processor V2", "drivers": "8.4mm dynamic driver X", "bluetooth": "5.3"}'
    ],
    // Gaming Peripherals
    [
        'Razer Viper V2 Pro',
        'Ultra-lightweight wireless gaming mouse, 58g',
        149.99,
        65,
        5,
        'Razer',
        'https://images.unsplash.com/photo-1527814050087-3793815479db?w=800&h=600&fit=crop',
        '{"sensor": "Focus Pro 30K Optical", "weight": "58g", "battery": "80 hours", "switches": "Optical Mouse Switches Gen-3"}'
    ],
    [
        'Corsair K100 RGB',
        'Mechanical gaming keyboard with optical-mechanical switches',
        229.99,
        35,
        5,
        'Corsair',
        'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=800&h=600&fit=crop',
        '{"switches": "OPX RGB Optical-Mechanical", "rgb": "Per-key RGB with LightEdge", "macro_keys": "6 dedicated macro keys", "polling_rate": "4000Hz"}'
    ],
    // Monitors
    [
        'LG UltraGear OLED 27"',
        '27-inch OLED gaming monitor, 240Hz, 0.03ms response',
        999.99,
        22,
        5,
        'LG',
        'https://images.unsplash.com/photo-1593305841991-05c297ba4575?w=800&h=600&fit=crop',
        '{"size": "27-inch", "resolution": "2560x1440 OLED", "refresh_rate": "240Hz", "response_time": "0.03ms GtG", "hdr": "HDR10"}'
    ],
    [
        'Dell UltraSharp U4323QE',
        '43-inch 4K USB-C Hub Monitor for productivity',
        1149.99,
        18,
        5,
        'Dell',
        'https://images.unsplash.com/photo-1461151304267-38535e780c79?w=800&h=600&fit=crop',
        '{"size": "43-inch", "resolution": "3840x2160 4K", "panel": "IPS Black technology", "usb_c": "90W power delivery", "kvm": "Built-in KVM switch"}'
    ],
    // Smart Watches
    [
        'Garmin Fenix 7X Pro',
        'Multisport GPS smartwatch with solar charging',
        899.99,
        25,
        5,
        'Garmin',
        'https://images.unsplash.com/photo-1551816230-ef5deaed4a26?w=800&h=600&fit=crop',
        '{"display": "1.4-inch sunlight-visible", "battery": "37 days smartwatch mode", "solar": "Power Glass solar charging", "sensors": "HRV Status, Pulse Ox"}'
    ],
    [
        'Fitbit Sense 2',
        'Advanced health smartwatch with stress management',
        299.99,
        40,
        5,
        'Fitbit',
        'https://images.unsplash.com/photo-1551816230-ef5deaed4a26?w=800&h=600&fit=crop',
        '{"display": "1.58-inch AMOLED", "battery": "6+ days", "sensors": "EDA, ECG, skin temperature", "stress_management": "cEDA for stress detection"}'
    ],
    // Tablets
    [
        'Microsoft Surface Pro 9',
        '2-in-1 laptop-tablet with Intel i7, 5G optional',
        1499.99,
        28,
        1,
        'Microsoft',
        'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&h=600&fit=crop',
        '{"processor": "Intel Core i7-1255U", "ram": "16GB", "storage": "256GB SSD", "display": "13-inch PixelSense", "pen": "Surface Slim Pen 2 compatible"}'
    ],
    [
        'Apple iPad Air M1',
        '10.9-inch tablet with M1 chip, Center Stage',
        749.99,
        55,
        1,
        'Apple',
        'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&h=600&fit=crop',
        '{"processor": "Apple M1", "storage": "256GB", "display": "10.9-inch Liquid Retina", "camera": "12MP Ultra Wide with Center Stage"}'
    ],
    // Speakers
    [
        'Sonos Beam (Gen 2)',
        'Compact smart soundbar with Dolby Atmos',
        449.99,
        38,
        4,
        'Sonos',
        'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&h=600&fit=crop',
        '{"audio": "Dolby Atmos", "voice": "Amazon Alexa, Google Assistant built-in", "connectivity": "Wi-Fi, Apple AirPlay 2, HDMI eARC"}'
    ],
    [
        'Marshall Stanmore III',
        'Bluetooth speaker with vintage design, 80W power',
        399.99,
        30,
        4,
        'Marshall',
        'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&h=600&fit=crop',
        '{"power": "80W", "drivers": "5.25\" woofer, two 0.75\" tweeters", "connectivity": "Bluetooth 5.2, RCA, 3.5mm", "design": "Classic Marshall look"}'
    ],
    // Storage & Networking
    [
        'Samsung T9 Portable SSD 4TB',
        'Portable SSD with 2000MB/s speeds, rugged design',
        449.99,
        42,
        5,
        'Samsung',
        'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=800&h=600&fit=crop',
        '{"capacity": "4TB", "speed": "2000MB/s read/write", "interface": "USB 3.2 Gen 2x2", "durability": "3-meter drop resistance"}'
    ],
    [
        'TP-Link Archer AXE95',
        'Tri-band Wi-Fi 6E router with 10G port',
        399.99,
        32,
        5,
        'TP-Link',
        'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=800&h=600&fit=crop',
        '{"wifi": "Tri-band Wi-Fi 6E", "speed": "AXE7800 (2.4GHz + 5GHz + 6GHz)", "ports": "1× 10G WAN/LAN, 4× 1G LAN", "processor": "2.0GHz Quad-Core CPU"}'
    ],
    // Cameras
    [
        'GoPro HERO12 Black',
        'Action camera with 5.3K video, HyperSmooth 6.0',
        399.99,
        60,
        5,
        'GoPro',
        'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&h=600&fit=crop',
        '{"video": "5.3K60 + 4K120", "stabilization": "HyperSmooth 6.0", "battery": "Enduro battery (2x longer life)", "waterproof": "33ft (10m) without housing"}'
    ],
    [
        'Insta360 X3',
        '360-degree action camera with 5.7K video',
        449.99,
        35,
        5,
        'Insta360',
        'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&h=600&fit=crop',
        '{"video": "5.7K 360°, 4K single-lens", "display": "2.29-inch touchscreen", "waterproof": "33ft (10m)", "battery": "1800mAh (80 min recording)"}'
    ]
];

// Count existing products first
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$before_count = $stmt->fetch()['total'];

echo "<p>Existing products before: <strong>{$before_count}</strong></p>";

// Insert new products
$inserted = 0;
foreach ($new_products as $product) {
    $stmt = $pdo->prepare("
        INSERT INTO products (product_name, description, price, stock_quantity, category_id, brand, image_url, specifications) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute($product);
        $inserted++;
        
        echo "<div style='border: 1px solid #4CAF50; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9fff9;'>";
        echo "<h3 style='color: #2E7D32;'>{$product[0]} - \${$product[2]}</h3>";
        echo "<img src='{$product[6]}' width='200' style='border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<p><strong>Description:</strong> {$product[1]}</p>";
        echo "<p><strong>Brand:</strong> {$product[5]} | <strong>Stock:</strong> {$product[3]}</p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='border: 1px solid #f44336; padding: 10px; margin: 5px 0; background: #ffebee;'>";
        echo "<p style='color: #c62828;'>Failed to add: {$product[0]} - " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// Count after insertion
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$after_count = $stmt->fetch()['total'];

echo "<h2 style='color: #4CAF50; padding: 20px; background: #f1f8e9; border-radius: 5px;'>";
echo "✓ Successfully added {$inserted} new products!";
echo "</h2>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<p><strong>Product Count:</strong> {$before_count} → {$after_count} (Added: {$inserted})</p>";
echo "</div>";

echo "<div style='margin-top: 30px;'>";
echo "<a href='index.php' style='background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Homepage</a>";
echo "<a href='products.php' style='background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>View All Products</a>";
echo "</div>";

// Also add some sample reviews for new products
echo "<h3>Adding sample reviews for new products...</h3>";

$review_quotes = [
    "Excellent product! Exceeded my expectations.",
    "Great value for money. Highly recommended!",
    "Perfect for my needs. Works flawlessly.",
    "Amazing quality and performance.",
    "Best purchase I've made this year!",
    "Very satisfied with this product.",
    "Works exactly as described. No issues.",
    "Great features and build quality.",
    "Would definitely buy again.",
    "Perfect for gaming/work/entertainment."
];

// Get the last 10 product IDs we just inserted
$stmt = $pdo->query("SELECT product_id FROM products ORDER BY product_id DESC LIMIT 10");
$new_product_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get a user ID for reviews (use admin or customer1)
$stmt = $pdo->query("SELECT user_id FROM users WHERE username IN ('customer1', 'admin') LIMIT 1");
$user_id = $stmt->fetch()['user_id'];

$reviews_added = 0;
foreach ($new_product_ids as $product_id) {
    // Add 2-3 reviews per product
    $num_reviews = rand(2, 3);
    for ($i = 0; $i < $num_reviews; $i++) {
        $rating = rand(4, 5); // Mostly 4-5 star reviews
        $comment = $review_quotes[array_rand($review_quotes)];
        
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, product_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $product_id, $rating, $comment]);
        $reviews_added++;
    }
}

echo "<p style='color: #4CAF50;'>✓ Added {$reviews_added} sample reviews for new products</p>";
?>