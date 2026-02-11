<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Access denied. Admin privileges required.', 'error');
}

$page_title = "Manage Orders";
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // CSRF protection - FIXED: Using validateCSRF (alias function)
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $order_id = (int)sanitize($_POST['order_id']);
        $status = sanitize($_POST['status']);
        $admin_notes = sanitize($_POST['admin_notes'] ?? '');
        
        // Validate status
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            $error = 'Invalid order status';
        } else {
            try {
                // Get current order status
                $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $current_status = $stmt->fetchColumn();
                
                // Update order status - FIXED: Added admin_notes parameter
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = ?, updated_at = NOW() 
                    WHERE order_id = ?
                ");
                $stmt->execute([$status, $order_id]);
                
                // Log status change
                logActivity($pdo, $_SESSION['user_id'], 'order_status_update', 
                    "Changed order #{$order_id} status from {$current_status} to {$status}");
                
                $success = 'Order status updated successfully';
                
            } catch (PDOException $e) {
                error_log("Order status update error: " . $e->getMessage());
                $error = 'Failed to update order status: ' . $e->getMessage();
            }
        }
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    // CSRF protection - FIXED: Using validateCSRF (alias function)
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $action = sanitize($_POST['bulk_action']);
        $selected_orders = $_POST['selected_orders'] ?? [];
        
        if (empty($selected_orders)) {
            $error = 'No orders selected';
        } else {
            try {
                $order_ids = array_map('intval', $selected_orders);
                $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
                
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("
                        DELETE FROM orders 
                        WHERE order_id IN ($placeholders) 
                        AND status IN ('cancelled', 'pending')
                    ");
                    $stmt->execute($order_ids);
                    $deleted = $stmt->rowCount();
                    
                    logActivity($pdo, $_SESSION['user_id'], 'bulk_delete_orders', 
                        "Deleted {$deleted} orders");
                    
                    $success = "Deleted {$deleted} orders";
                    
                } elseif (in_array($action, ['processing', 'shipped', 'delivered'])) {
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET status = ?, updated_at = NOW() 
                        WHERE order_id IN ($placeholders)
                    ");
                    $params = array_merge([$action], $order_ids);
                    $stmt->execute($params);
                    $updated = $stmt->rowCount();
                    
                    logActivity($pdo, $_SESSION['user_id'], 'bulk_status_update', 
                        "Updated {$updated} orders to {$action}");
                    
                    $success = "Updated {$updated} orders to " . ucfirst($action);
                }
                
            } catch (PDOException $e) {
                error_log("Bulk action error: " . $e->getMessage());
                $error = 'Failed to process bulk action: ' . $e->getMessage();
            }
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query with filters
$where = [];
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where[] = "(o.order_id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Build final query
$sql = "
    SELECT o.*, u.username, u.email, u.full_name, u.phone,
           COUNT(oi.order_item_id) as item_count,
           SUM(oi.quantity) as total_items
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Francoder Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            min-width: 140px;
        }
        .status-select:focus {
            outline: none;
            border-color: #3498db;
        }
        .admin-notes {
            margin-top: 10px;
        }
        .admin-notes textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 5px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Manage Orders</h1>
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
            
            <!-- Orders Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                    <p>No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['email']); ?></small><br>
                                    <small><?php echo htmlspecialchars($order['phone'] ?? 'No phone'); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" class="status-select" 
                                                onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                    <div class="current-status">
                                        <small style="color: #7f8c8d; font-size: 12px;">
                                            Current: <?php echo ucfirst($order['status']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="../order-details.php?id=<?php echo $order['order_id']; ?>" 
                                           target="_blank"
                                           class="btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Customer View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add confirmation for status changes
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(select => {
                select.addEventListener('change', function(e) {
                    const newStatus = this.value;
                    const orderId = this.closest('form').querySelector('input[name="order_id"]').value;
                    
                    if (!confirm(`Are you sure you want to change order #${orderId} status to "${newStatus}"?`)) {
                        e.preventDefault();
                        this.blur();
                    }
                });
            });
            
            // Show error message if any
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                const errorMsg = urlParams.get('error');
                showNotification(errorMsg, 'error');
            }
            if (urlParams.has('success')) {
                const successMsg = urlParams.get('success');
                showNotification(successMsg, 'success');
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
        });
    </script>
</body>
</html>