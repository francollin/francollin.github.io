<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('auth/login.php', 'Please login to view order details', 'error');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php', 'Order not found', 'error');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.product_name, p.image_url, p.brand
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$page_title = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Francoder Electronics</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>Order Details</h1>
            <p>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <div class="order-details-container">
            <!-- Order information -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                        <i class="fas fa-user"></i> Customer Information
                    </h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?: 'Not provided'); ?></p>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                        <i class="fas fa-shipping-fast"></i> Shipping Information
                    </h3>
                    <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                </div>
            </div>
            
            <!-- Order status and actions -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="color: #2c3e50; margin: 0;">Order Status</h3>
                        <p style="margin: 5px 0 0 0; color: #7f8c8d;">
                            Ordered on <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                        </p>
                    </div>
                    
                    <div>
                        <span class="status-badge status-<?php echo $order['status']; ?>" 
                              style="font-size: 1rem; padding: 10px 20px;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    
                    <div>
                        <?php if (canCancelOrder($order['status'])): ?>
                            <a href="cancel-order.php?id=<?php echo $order_id; ?>" 
                               class="btn-danger"
                               onclick="return confirm('Are you sure you want to cancel this order?');">
                                <i class="fas fa-times"></i> Cancel Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order items -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                <h3 style="color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                    <i class="fas fa-shopping-basket"></i> Order Items (<?php echo count($order_items); ?>)
                </h3>
                
                <?php if (empty($order_items)): ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                        No items found in this order.
                    </p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; text-align: left;">Product</th>
                                <th style="padding: 12px; text-align: left;">Price</th>
                                <th style="padding: 12px; text-align: left;">Quantity</th>
                                <th style="padding: 12px; text-align: left;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_subtotal = $item['price'] * $item['quantity'];
                                $subtotal += $item_subtotal;
                            ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             style="width: 80px; height: 80px; object-fit: contain; background: #f8f9fa; border-radius: 8px; padding: 5px;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($item['brand'] ?: ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 15px; vertical-align: top;">
                                    $<?php echo number_format($item['price'], 2); ?>
                                </td>
                                <td style="padding: 15px; vertical-align: top;">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td style="padding: 15px; vertical-align: top; font-weight: 600;">
                                    $<?php echo number_format($item_subtotal, 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="padding: 15px; text-align: right; font-weight: 600;">
                                    Subtotal:
                                </td>
                                <td style="padding: 15px; font-weight: 600;">
                                    $<?php echo number_format($subtotal, 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 15px; text-align: right; font-weight: 600;">
                                    Shipping:
                                </td>
                                <td style="padding: 15px; font-weight: 600;">
                                    $10.00
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 15px; text-align: right; font-weight: 600; font-size: 1.1rem;">
                                    Total:
                                </td>
                                <td style="padding: 15px; font-weight: 600; font-size: 1.1rem; color: #e74c3c;">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Back button -->
            <div style="margin-top: 30px; text-align: center;">
                <a href="orders.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>