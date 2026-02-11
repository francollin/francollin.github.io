<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get product reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.username, u.full_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.product_id = ? 
    ORDER BY r.review_date DESC 
    LIMIT 10
");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
$avg_rating_stmt->execute([$product_id]);
$avg_rating = $avg_rating_stmt->fetch()['avg_rating'];

// Get related products
$related_stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND product_id != ? AND stock_quantity > 0 
    ORDER BY RAND() 
    LIMIT 4
");
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

$page_title = $product['product_name'];
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
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="products.php">Products</a> &gt; 
            <span><?php echo htmlspecialchars($product['product_name']); ?></span>
        </div>
        
        <div class="product-detail-container">
            <div class="product-images">
                <div class="main-image">
                    <img src="<?php echo $product['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="product-meta">
                    <span class="category">Category: <?php echo $product['category_name']; ?></span>
                    <span class="brand">Brand: <?php echo htmlspecialchars($product['brand']); ?></span>
                    <span class="stock <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock_quantity'] > 0 ? 'In Stock (' . $product['stock_quantity'] . ' available)' : 'Out of Stock'; ?>
                    </span>
                </div>
                
                <div class="product-rating">
                    <?php if ($avg_rating): ?>
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span>(<?php echo number_format($avg_rating, 1); ?>)</span>
                        </div>
                    <?php else: ?>
                        <span class="no-rating">No ratings yet</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-price">
                    <h2><?php echo formatPrice($product['price']); ?></h2>
                </div>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <?php if ($product['specifications']): ?>
                    <?php $specs = json_decode($product['specifications'], true); ?>
                    <div class="product-specs">
                        <h3>Specifications</h3>
                        <ul>
                            <?php foreach ($specs as $key => $value): ?>
                                <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="qty-btn minus">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button class="qty-btn plus">+</button>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <button class="btn-primary btn-add-to-cart" 
                                    data-product-id="<?php echo $product['product_id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            
                            <button class="btn-secondary btn-buy-now" 
                                    data-product-id="<?php echo $product['product_id']; ?>">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="out-of-stock">
                        <p>This product is currently out of stock.</p>
                        <button class="btn-secondary notify-me">Notify Me When Available</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>Customer Reviews</h2>
            
            <?php if (isLoggedIn()): ?>
                <div class="add-review">
                    <h3>Write a Review</h3>
                    <form id="review-form" method="POST">
                        <div class="rating-input">
                            <label>Rating:</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>">
                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Your Review:</label>
                            <textarea id="comment" name="comment" rows="4" placeholder="Share your experience..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer">
                                <strong><?php echo htmlspecialchars($review['full_name']); ?></strong>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($review['review_date'])); ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>Related Products</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $related): ?>
                <div class="product-card">
                    <img src="<?php echo $related['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                    <h3><?php echo htmlspecialchars($related['product_name']); ?></h3>
                    <p class="price"><?php echo formatPrice($related['price']); ?></p>
                    <a href="product-detail.php?id=<?php echo $related['product_id']; ?>" class="btn-secondary">View Details</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity selector
            const quantityInput = document.getElementById('quantity');
            const minusBtn = document.querySelector('.qty-btn.minus');
            const plusBtn = document.querySelector('.qty-btn.plus');
            
            if (minusBtn && plusBtn && quantityInput) {
                minusBtn.addEventListener('click', function() {
                    const current = parseInt(quantityInput.value);
                    if (current > 1) {
                        quantityInput.value = current - 1;
                    }
                });
                
                plusBtn.addEventListener('click', function() {
                    const current = parseInt(quantityInput.value);
                    const max = parseInt(quantityInput.getAttribute('max'));
                    if (current < max) {
                        quantityInput.value = current + 1;
                    }
                });
            }
            
            // Add to cart with quantity
            const addToCartBtn = document.querySelector('.btn-add-to-cart');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                    
                    fetch('api/cart-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Added to cart!', 'success');
                            updateCartCount();
                        } else {
                            showNotification('Failed to add to cart', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred', 'error');
                    });
                });
            }
            
            // Buy now
            const buyNowBtn = document.querySelector('.btn-buy-now');
            if (buyNowBtn) {
                buyNowBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                    
                    // Add to cart and redirect to checkout
                    fetch('api/cart-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'checkout.php';
                        }
                    });
                });
            }
            
            // Review form submission
            const reviewForm = document.getElementById('review-form');
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const reviewData = {
                        product_id: <?php echo $product_id; ?>,
                        rating: formData.get('rating'),
                        comment: formData.get('comment')
                    };
                    
                    fetch('api/reviews-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(reviewData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Review submitted successfully!', 'success');
                            this.reset();
                            // Reload reviews after a delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.error || 'Failed to submit review', 'error');
                        }
                    });
                });
            }
        });
        
        function showNotification(message, type) {
            // Implementation from script.js
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px;
                border-radius: 5px;
                z-index: 1000;
                color: white;
                font-weight: bold;
                background-color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            `;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
        
        function updateCartCount() {
            // Implementation to update cart count
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                const current = parseInt(cartCount.textContent) || 0;
                cartCount.textContent = current + 1;
                cartCount.style.display = 'inline';
            }
        }
    </script>
</body>
</html>