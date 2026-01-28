<?php
require_once '../config.php';

if (!Security::validateSession()) {
    Response::unauthorized();
}

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$user = Security::getCurrentUser();

switch ($method) {
    case 'GET':
        if ($id) {
            getOrder($conn, $id, $user);
        } else {
            getOrders($conn, $user);
        }
        break;
    
    case 'POST':
        createOrder($conn, $user);
        break;
    
    case 'PUT':
        if (!$id) {
            Response::error('Order ID required');
        }
        updateOrderStatus($conn, $id, $user);
        break;
    
    default:
        Response::error('Method not allowed', 405);
}

function getOrders($conn, $user) {
    $status = isset($_GET['status']) ? Security::sanitizeInput($_GET['status']) : null;
    
    if ($user['role'] === 'admin') {
        $sql = "SELECT o.*, u.username, u.full_name 
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE 1=1";
    } else {
        $sql = "SELECT o.*, u.username, u.full_name 
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.user_id = ?";
    }
    
    $params = [];
    $types = '';
    
    if ($user['role'] !== 'admin') {
        $params[] = $user['user_id'];
        $types .= 'i';
    }
    
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $sql .= " ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    Response::success('Orders retrieved', $orders);
}

function getOrder($conn, $id, $user) {
    $sql = "SELECT o.*, u.username, u.full_name 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ?";
    
    if ($user['role'] !== 'admin') {
        $sql .= " AND o.user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($user['role'] !== 'admin') {
        $stmt->bind_param("ii", $id, $user['user_id']);
    } else {
        $stmt->bind_param("i", $id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        Response::error('Order not found', 404);
    }
    
    $order = $result->fetch_assoc();
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.product_name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itemsResult = $stmt->get_result();
    
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = $row;
    }
    
    $order['items'] = $items;
    
    Response::success('Order retrieved', $order);
}

function createOrder($conn, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['items']) || empty($data['shipping_address'])) {
        Response::error('Items and shipping address required');
    }
    
    $shipping_address = Security::sanitizeInput($data['shipping_address']);
    $items = $data['items'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $total_amount = 0;
        
        // Validate items and calculate total
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                throw new Exception('Invalid item data');
            }
            
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            
            if ($quantity <= 0) {
                throw new Exception('Invalid quantity');
            }
            
            // Check product availability
            $stmt = $conn->prepare("SELECT price, stock_quantity, product_name FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Product ID $product_id not found");
            }
            
            $product = $result->fetch_assoc();
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("Insufficient stock for " . $product['product_name']);
            }
            
            $total_amount += $product['price'] * $quantity;
        }
        
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("ids", $user['user_id'], $total_amount, $shipping_address);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        
        $order_id = $conn->insert_id;
        
        // Add order items and update stock
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            
            // Get product price
            $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            $unit_price = $product['price'];
            $subtotal = $unit_price * $quantity;
            
            // Insert order item
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $order_id, $product_id, $quantity, $unit_price, $subtotal);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add order item: " . $stmt->error);
            }
            
            // Update stock
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update stock: " . $stmt->error);
            }
        }
        
        // Commit transaction - THIS IS CRITICAL
        $conn->commit();
        
        // Send success response - MUST BE AFTER COMMIT
        Response::success('Order placed successfully', [
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        Response::error($e->getMessage());
    }
}