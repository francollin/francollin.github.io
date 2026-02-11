<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get cart items
        $stmt = $pdo->prepare("
            SELECT c.*, p.product_name, p.price, p.image_url 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll();
        echo json_encode($cart_items);
        break;
        
    case 'POST':
        // Add to cart
        $data = json_decode(file_get_contents('php://input'), true);
        $product_id = sanitize($data['product_id']);
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        
        // Check if already in cart
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'PUT':
        // Update cart quantity
        parse_str(file_get_contents('php://input'), $_PUT);
        $cart_id = sanitize($_PUT['cart_id']);
        $quantity = (int)sanitize($_PUT['quantity']);
        
        if ($quantity <= 0) {
            // Remove item
            $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'DELETE':
        // Remove from cart
        parse_str(file_get_contents('php://input'), $_DELETE);
        $cart_id = sanitize($_DELETE['cart_id']);
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        
        echo json_encode(['success' => true]);
        break;
}
?>