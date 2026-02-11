<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Allow GET requests for product data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $where = ["stock_quantity > 0"];
    $params = [];
    
    // Handle filters
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where[] = "(product_name LIKE ? OR description LIKE ?)";
        $search_term = "%" . sanitize($_GET['search']) . "%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (isset($_GET['category']) && is_numeric($_GET['category'])) {
        $where[] = "category_id = ?";
        $params[] = (int)$_GET['category'];
    }
    
    if (isset($_GET['price'])) {
        switch ($_GET['price']) {
            case 'under100':
                $where[] = "price < 100";
                break;
            case '100-500':
                $where[] = "price BETWEEN 100 AND 500";
                break;
            case '500-1000':
                $where[] = "price BETWEEN 500 AND 1000";
                break;
            case 'over1000':
                $where[] = "price > 1000";
                break;
        }
    }
    
    // Build query
    $sql = "SELECT * FROM products";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Add limit for AJAX requests
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $sql .= " LIMIT " . $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    echo json_encode($products);
}

// POST requests for admin operations (require admin authentication)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $stmt = $pdo->prepare("
                INSERT INTO products (product_name, description, price, stock_quantity, category_id, brand, specifications) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                sanitize($data['product_name']),
                sanitize($data['description']),
                (float)$data['price'],
                (int)$data['stock_quantity'],
                (int)$data['category_id'],
                sanitize($data['brand']),
                json_encode($data['specifications'])
            ]);
            echo json_encode(['success' => $success]);
            break;
            
        case 'update':
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, description = ?, price = ?, stock_quantity = ?, 
                    category_id = ?, brand = ?, specifications = ? 
                WHERE product_id = ?
            ");
            $success = $stmt->execute([
                sanitize($data['product_name']),
                sanitize($data['description']),
                (float)$data['price'],
                (int)$data['stock_quantity'],
                (int)$data['category_id'],
                sanitize($data['brand']),
                json_encode($data['specifications']),
                (int)$data['product_id']
            ]);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $success = $stmt->execute([(int)$data['product_id']]);
            echo json_encode(['success' => $success]);
            break;
    }
}
?>