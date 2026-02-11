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
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        
        if ($product_id > 0) {
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.full_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.product_id = ? 
                ORDER BY r.review_date DESC
            ");
            $stmt->execute([$product_id]);
            $reviews = $stmt->fetchAll();
            echo json_encode($reviews);
        } else {
            echo json_encode(['error' => 'Product ID required']);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $product_id = (int)sanitize($data['product_id'] ?? 0);
        $rating = (int)sanitize($data['rating'] ?? 0);
        $comment = sanitize($data['comment'] ?? '');
        
        if ($product_id <= 0) {
            echo json_encode(['error' => 'Invalid product ID']);
            exit();
        }
        
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            exit();
        }
        
        if (empty($comment)) {
            echo json_encode(['error' => 'Comment is required']);
            exit();
        }
        
        // Check if user already reviewed this product
        $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['error' => 'You have already reviewed this product']);
            exit();
        }
        
        // Check if user purchased this product
        $stmt = $pdo->prepare("
            SELECT oi.order_item_id 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        ");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['error' => 'You must purchase the product before reviewing']);
            exit();
        }
        
        // Insert review
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $product_id, $rating, $comment]);
        
        echo json_encode(['success' => $success]);
        break;
        
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $_DELETE);
        $review_id = (int)sanitize($_DELETE['review_id'] ?? 0);
        
        // Check if review belongs to user (or user is admin)
        $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE review_id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        if (!$review) {
            echo json_encode(['error' => 'Review not found']);
            exit();
        }
        
        if ($review['user_id'] != $user_id && !isAdmin()) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
        $success = $stmt->execute([$review_id]);
        
        echo json_encode(['success' => $success]);
        break;
}
?>