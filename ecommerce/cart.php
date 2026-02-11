<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.cart_id, c.quantity, p.product_id, p.product_name, p.price, p.image_url, p.stock_quantity
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
$shipping = 10.00; // Fixed shipping for now
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $shipping;

$page_title = "Shopping Cart";
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
        <div class="page-header">
            <h1>Your Shopping Cart</h1>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-3x"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="products.php" class="btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                        <div class="cart-item-image">
                            <img src="<?php echo $item['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        </div>
                        
                        <div class="cart-item-details">
                            <h3>
                                <a href="product-detail.php?id=<?php echo $item['product_id']; ?>">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </a>
                            </h3>
                            <p class="price"><?php echo formatPrice($item['price']); ?></p>
                            
                            <div class="cart-item-controls">
                                <div class="quantity-control">
                                    <button class="qty-btn minus">-</button>
                                    <input type="number" class="cart-quantity" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <button class="qty-btn plus">+</button>
                                </div>
                                
                                <button class="btn-remove remove-from-cart" 
                                        data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            
                            <div class="cart-item-total">
                                Total: <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="shipping"><?php echo formatPrice($shipping); ?></span>
                        </div>
                        <div class="summary-row total">
                            <strong>Total</strong>
                            <strong id="total"><?php echo formatPrice($total); ?></strong>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="products.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <a href="checkout.php" class="btn-primary">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="payment-methods">
                        <p>We accept:</p>
                        <div class="payment-icons">
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                            <i class="fab fa-cc-amex"></i>
                            <i class="fab fa-cc-paypal"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart quantity
            document.querySelectorAll('.cart-quantity').forEach(input => {
                input.addEventListener('change', function() {
                    const cartId = this.getAttribute('data-cart-id');
                    const quantity = parseInt(this.value);
                    
                    updateCartItem(cartId, quantity);
                });
            });
            
            // Plus/minus buttons
            document.querySelectorAll('.qty-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item-controls');
                    const input = cartItem.querySelector('.cart-quantity');
                    const cartId = input.getAttribute('data-cart-id');
                    let quantity = parseInt(input.value);
                    
                    if (this.classList.contains('minus')) {
                        if (quantity > 1) {
                            quantity--;
                        }
                    } else if (this.classList.contains('plus')) {
                        const max = parseInt(input.getAttribute('max'));
                        if (quantity < max) {
                            quantity++;
                        }
                    }
                    
                    input.value = quantity;
                    updateCartItem(cartId, quantity);
                });
            });
            
            function updateCartItem(cartId, quantity) {
                fetch('api/cart-api.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update totals
                        updateCartTotals();
                        showNotification('Cart updated', 'success');
                    }
                });
            }
            
            function updateCartTotals() {
                // This should be implemented to recalculate totals
                // For simplicity, we'll reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        });
    </script>
</body>
</html>