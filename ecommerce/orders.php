<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('auth/login.php', 'Please login to view your orders', 'error');
}

$user_id = $_SESSION['user_id'];
$page_title = "My Orders";
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

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.order_item_id) as item_count,
           SUM(oi.quantity) as items_total
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Get order statistics
$total_orders = count($orders);
$pending_orders = array_filter($orders, function($order) {
    return $order['status'] === 'pending';
});
$delivered_orders = array_filter($orders, function($order) {
    return $order['status'] === 'delivered';
});

// CSRF token for any future form submissions
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Francoder Electronics</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .orders-container {
            margin: 30px 0;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin: 10px 0 5px 0;
        }
        
        .stat-card p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-total { background: #3498db; }
        .icon-pending { background: #f39c12; }
        .icon-delivered { background: #2ecc71; }
        
        .orders-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .empty-orders i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-orders h2 {
            color: #34495e;
            margin-bottom: 15px;
        }
        
        .empty-orders p {
            color: #7f8c8d;
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        @media (max-width: 768px) {
            .orders-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-sm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track and manage your purchases</p>
        </div>
        
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
        
        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon icon-total">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3><?php echo $total_orders; ?></h3>
                <p>Total Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?php echo count($pending_orders); ?></h3>
                <p>Pending Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-delivered">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?php echo count($delivered_orders); ?></h3>
                <p>Delivered Orders</p>
            </div>
        </div>
        
        <!-- Orders List -->
        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <i class="fas fa-box-open"></i>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="products.php" class="btn-primary">
                        <i class="fas fa-shopping-cart"></i> Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
                                <small style="color: #7f8c8d;">
                                    <?php echo date('h:i A', strtotime($order['order_date'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo $order['item_count']; ?> item(s)<br>
                                <small style="color: #7f8c8d;">
                                    <?php echo $order['items_total']; ?> total qty
                                </small>
                            </td>
                            <td style="font-weight: 600; color: #2c3e50;">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <?php if ($order['status'] === 'shipped'): ?>
                                    <br><small style="color: #7f8c8d; font-size: 0.8rem;">
                                        <i class="fas fa-truck"></i> Shipped on <?php echo date('M d', strtotime($order['updated_at'])); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                       class="btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <?php if (canCancelOrder($order['status'])): ?>
                                        <a href="cancel-order.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to cancel order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>?');">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <a href="order-review.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-secondary btn-sm">
                                            <i class="fas fa-star"></i> Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Order Status Legend -->
                <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-badge status-pending" style="width: 20px; height: 20px;"></span>
                        <span>Pending</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-badge status-processing" style="width: 20px; height: 20px;"></span>
                        <span>Processing</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-badge status-shipped" style="width: 20px; height: 20px;"></span>
                        <span>Shipped</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-badge status-delivered" style="width: 20px; height: 20px;"></span>
                        <span>Delivered</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-badge status-cancelled" style="width: 20px; height: 20px;"></span>
                        <span>Cancelled</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Help Section -->
        <div style="margin-top: 40px; padding: 25px; background: #f8f9fa; border-radius: 10px;">
            <h3 style="margin-bottom: 15px; color: #2c3e50;">
                <i class="fas fa-question-circle"></i> Need Help With Your Orders?
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-shipping-fast"></i> Shipping Information
                    </h4>
                    <p style="color: #666; font-size: 0.95rem;">
                        Standard shipping: 3-5 business days<br>
                        Express shipping: 1-2 business days<br>
                        Free shipping on orders over $100
                    </p>
                </div>
                
                <div>
                    <h4 style="color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-undo"></i> Returns & Refunds
                    </h4>
                    <p style="color: #666; font-size: 0.95rem;">
                        30-day return policy<br>
                        Free returns for defective items<br>
                        Refunds processed within 5-7 business days
                    </p>
                </div>
                
                <div>
                    <h4 style="color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-headset"></i> Contact Support
                    </h4>
                    <p style="color: #666; font-size: 0.95rem;">
                        Email: support@francoder.com<br>
                        Phone: (555) 123-4567<br>
                        Hours: Mon-Fri, 9am-6pm EST
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add confirmation for all cancel links
            const cancelLinks = document.querySelectorAll('a.btn-danger');
            cancelLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Status update notifications
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('cancelled')) {
                showNotification('Order cancelled successfully', 'success');
            }
            if (urlParams.has('error')) {
                showNotification('Failed to cancel order', 'error');
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 25px;
                    border-radius: 6px;
                    z-index: 9999;
                    color: white;
                    font-weight: 600;
                    background-color: ${type === 'success' ? '#2ecc71' : '#e74c3c'};
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    animation: slideIn 0.3s ease;
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100px)';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
            
            // Add animation style
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>