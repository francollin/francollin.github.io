<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.quantity, p.product_id, p.product_name, p.price, p.stock_quantity
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
$shipping = 10.00;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $shipping;

$error = '';
$success = '';

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Validate
    if (empty($shipping_address)) {
        $error = 'Shipping address is required';
    } else {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $total, $shipping_address, $payment_method]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                // Check stock
                if ($item['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for: " . $item['product_name']);
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success = 'Order placed successfully! Order ID: #' . $order_id;
            $cart_items = []; // Empty cart after successful order
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

$page_title = "Checkout";
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
            <h1>Checkout</h1>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success">
                <?php echo $success; ?>
                <br>
                <a href="orders.php" class="btn-primary">View Your Orders</a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($success)): ?>
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Shipping Information</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label>Shipping Address *</label>
                        <textarea name="shipping_address" rows="4" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <h2>Payment Method</h2>
                    
                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash on Delivery</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="card" name="payment_method" value="card">
                            <label for="card">
                                <i class="fas fa-credit-card"></i>
                                <span>Credit/Debit Card</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="bank" name="payment_method" value="bank_transfer">
                            <label for="bank">
                                <i class="fas fa-university"></i>
                                <span>Bank Transfer</span>
                            </label>
                        </div>
                    </div>
                    
                    <?php if (!empty($cart_items)): ?>
                    <div class="order-review">
                        <h2>Order Review</h2>
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                                <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="order-totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Shipping:</span>
                                    <span><?php echo formatPrice($shipping); ?></span>
                                </div>
                                <div class="total-row grand-total">
                                    <strong>Total:</strong>
                                    <strong><?php echo formatPrice($total); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group terms">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the terms and conditions</label>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-place-order">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show payment details based on selection
            document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updatePaymentDetails(this.value);
                });
            });
            
            function updatePaymentDetails(method) {
                // This could show additional fields based on payment method
                console.log('Payment method selected:', method);
            }
        });
    </script>
</body>
</html>