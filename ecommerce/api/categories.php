<?php
require_once '../config.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getCategories($conn);
        break;
    
    default:
        Response::error('Method not allowed', 405);
}

function getCategories($conn) {
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY category_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    Response::success('Categories retrieved', $categories);
}
?>