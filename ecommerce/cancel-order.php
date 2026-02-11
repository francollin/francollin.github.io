<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('auth/login.php', 'Please login to cancel orders', 'error');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Check for flash messages
$flashMessage = getFlashMessage();
if ($flashMessage) {
    if ($flashMessage['type'] === 'error') {
        $error = $flashMessage['text'];
    } else {
        $success = $flashMessage['text'];
    }
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.order_item_id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.order_id = ? AND o.user_id = ?
    GROUP BY o.order_id
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php', 'Order not found', 'error');
}

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $cancel_reason = sanitize($_POST['cancel_reason'] ?? '');
        
        // Call the cancelOrder function
        if (cancelOrder($pdo, $order_id, $_SESSION['user_id'])) {
            redirect('orders.php', 'Order cancelled successfully', 'success');
        } else {
            $error = 'Failed to cancel order. It may have already been shipped or delivered.';
        }
    }
}

// Check if order can be cancelled
$can_cancel = canCancelOrder($order['status']);

$page_title = "Cancel Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Francoder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cancel-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cannot-cancel-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="cancel-container">
            <h1 style="color: #e74c3c; margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i> Cancel Order
            </h1>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-summary">
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </p>
                <p><strong>Items:</strong> <?php echo $order['item_count']; ?> item(s)</p>
            </div>
            
            <?php if ($can_cancel): ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <h3 style="margin: 0 0 10px 0;">Important Notice</h3>
                        <p style="margin: 0;">
                            Once cancelled, this order cannot be restored. 
                            Your payment will be refunded according to our refund policy.
                        </p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="cancel_order" value="1">
                    
                    <div class="form-group">
                        <label for="cancel_reason">Reason for cancellation (optional)</label>
                        <textarea name="cancel_reason" id="cancel_reason" rows="4" 
                                  placeholder="Please tell us why you're cancelling this order..."
                                  class="form-control"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn-danger" style="flex: 1;">
                            <i class="fas fa-times-circle"></i> Confirm Cancellation
                        </button>
                        <a href="orders.php" class="btn-secondary" style="flex: 1; text-align: center;">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="cannot-cancel-box">
                    <i class="fas fa-ban fa-3x" style="margin-bottom: 15px;"></i>
                    <h2>Order Cannot Be Cancelled</h2>
                    <p>
                        This order is currently <strong><?php echo ucfirst($order['status']); ?></strong> 
                        and cannot be cancelled at this stage.
                    </p>
                    <p style="margin-top: 15px;">
                        If you need assistance, please contact our customer support.
                    </p>
                    <a href="orders.php" class="btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cancelForm = document.querySelector('form');
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>