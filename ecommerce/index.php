<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Home";

// Get featured products
$stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY RAND() LIMIT 6");
$featured_products = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("SELECT * FROM categories LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to Francoder Electronics</h1>
                <p>Your one-stop shop for the latest electronics at the best prices</p>
                <a href="products.php" class="btn-primary">Shop Now</a>
            </div>
        </section>
        
        <!-- Featured Products -->
        <section class="featured-products">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="price"><?php echo formatPrice($product['price']); ?></p>
                        <div class="product-actions">
                            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn-secondary">View Details</a>
                            <?php if (isLoggedIn()): ?>
                                <button class="btn-primary add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Categories -->
        <section class="categories">
            <h2>Shop by Category</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-icon">
                        <?php 
                        $icons = ['fas fa-laptop', 'fas fa-mobile-alt', 'fas fa-gamepad', 'fas fa-headphones', 'fas fa-keyboard'];
                        $icon = $icons[array_rand($icons)];
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <a href="products.php?category=<?php echo $category['category_id']; ?>" class="btn-secondary">Browse</a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Features -->
        <section class="features">
            <div class="feature">
                <i class="fas fa-shipping-fast"></i>
                <h3>Free Shipping</h3>
                <p>On orders over $100</p>
            </div>
            <div class="feature">
                <i class="fas fa-undo-alt"></i>
                <h3>30-Day Returns</h3>
                <p>Easy return policy</p>
            </div>
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Payment</h3>
                <p>100% secure payment</p>
            </div>
            <div class="feature">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Customer support</p>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>