<?php
require_once '../config.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getProduct($conn, $id);
        } else {
            getProducts($conn);
        }
        break;
    
    case 'POST':
        if (!Security::validateSession()) {
            Response::unauthorized();
        }
        if (!Security::isAdmin()) {
            Response::forbidden('Admin access required');
        }
        createProduct($conn);
        break;
    
    case 'PUT':
        if (!Security::validateSession()) {
            Response::unauthorized();
        }
        if (!Security::isAdmin()) {
            Response::forbidden('Admin access required');
        }
        if (!$id) {
            Response::error('Product ID required');
        }
        updateProduct($conn, $id);
        break;
    
    case 'DELETE':
        if (!Security::validateSession()) {
            Response::unauthorized();
        }
        if (!Security::isAdmin()) {
            Response::forbidden('Admin access required');
        }
        if (!$id) {
            Response::error('Product ID required');
        }
        deleteProduct($conn, $id);
        break;
    
    default:
        Response::error('Method not allowed', 405);
}

function getProducts($conn) {
    $search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : '';
    $category = isset($_GET['category']) ? intval($_GET['category']) : null;
    $minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
    $maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
    $sort = isset($_GET['sort']) ? Security::sanitizeInput($_GET['sort']) : 'product_name';
    $order = isset($_GET['order']) ? Security::sanitizeInput($_GET['order']) : 'ASC';
    
    // Validate sort and order
    $allowedSort = ['product_name', 'price', 'created_at', 'stock_quantity'];
    $allowedOrder = ['ASC', 'DESC'];
    
    if (!in_array($sort, $allowedSort)) $sort = 'product_name';
    if (!in_array($order, $allowedOrder)) $order = 'ASC';
    
    $sql = "SELECT p.*, c.category_name, 
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.review_id) as review_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN reviews r ON p.product_id = r.product_id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($search) {
        $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    
    if ($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }
    
    if ($minPrice !== null) {
        $sql .= " AND p.price >= ?";
        $params[] = $minPrice;
        $types .= 'd';
    }
    
    if ($maxPrice !== null) {
        $sql .= " AND p.price <= ?";
        $params[] = $maxPrice;
        $types .= 'd';
    }
    
    $sql .= " GROUP BY p.product_id";
    $sql .= " ORDER BY p.$sort $order";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    Response::success('Products retrieved', $products);
}

function getProduct($conn, $id) {
    $stmt = $conn->prepare("
        SELECT p.*, c.category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.review_id) as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN reviews r ON p.product_id = r.product_id
        WHERE p.product_id = ?
        GROUP BY p.product_id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        Response::error('Product not found', 404);
    }
    
    $product = $result->fetch_assoc();
    
    // Get reviews
    $stmt = $conn->prepare("
        SELECT r.*, u.username, u.full_name
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reviewResult = $stmt->get_result();
    
    $reviews = [];
    while ($row = $reviewResult->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    $product['reviews'] = $reviews;
    
    Response::success('Product retrieved', $product);
}

function createProduct($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_name']) || empty($data['category_id']) || !isset($data['price']) || !isset($data['stock_quantity'])) {
        Response::error('Required fields missing');
    }
    
    $product_name = Security::sanitizeInput($data['product_name']);
    $category_id = intval($data['category_id']);
    $description = isset($data['description']) ? Security::sanitizeInput($data['description']) : null;
    $price = floatval($data['price']);
    $stock_quantity = intval($data['stock_quantity']);
    $image_url = isset($data['image_url']) ? Security::sanitizeInput($data['image_url']) : null;
    
    if ($price < 0 || $stock_quantity < 0) {
        Response::error('Price and stock must be non-negative');
    }
    
    $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisdis", $product_name, $category_id, $description, $price, $stock_quantity, $image_url);
    
    if ($stmt->execute()) {
        Response::success('Product created', ['product_id' => $conn->insert_id]);
    } else {
        Response::error('Failed to create product');
    }
}

function updateProduct($conn, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['product_name'])) {
        $updates[] = "product_name = ?";
        $params[] = Security::sanitizeInput($data['product_name']);
        $types .= 's';
    }
    
    if (isset($data['category_id'])) {
        $updates[] = "category_id = ?";
        $params[] = intval($data['category_id']);
        $types .= 'i';
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = Security::sanitizeInput($data['description']);
        $types .= 's';
    }
    
    if (isset($data['price'])) {
        $updates[] = "price = ?";
        $params[] = floatval($data['price']);
        $types .= 'd';
    }
    
    if (isset($data['stock_quantity'])) {
        $updates[] = "stock_quantity = ?";
        $params[] = intval($data['stock_quantity']);
        $types .= 'i';
    }
    
    if (isset($data['image_url'])) {
        $updates[] = "image_url = ?";
        $params[] = Security::sanitizeInput($data['image_url']);
        $types .= 's';
    }
    
    if (empty($updates)) {
        Response::error('No fields to update');
    }
    
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE product_id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        Response::success('Product updated');
    } else {
        Response::error('Failed to update product');
    }
}

function deleteProduct($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        Response::success('Product deleted');
    } else {
        Response::error('Failed to delete product');
    }
}
?>