<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Access denied. Admin privileges required.', 'error');
}

$page_title = "Manage Products";
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

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    // CSRF protection - FIXED
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $product_id = (int)sanitize($_POST['product_id']);
        
        if ($product_id > 0) {
            $result = deleteProduct($pdo, $product_id);
            $success = $result;
            
            logActivity($pdo, $_SESSION['user_id'], 'product_deleted', 
                "Deleted product #{$product_id}: {$result}");
        }
    }
}

// Handle product addition/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    // CSRF protection - FIXED
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $product_id = isset($_POST['product_id']) ? (int)sanitize($_POST['product_id']) : 0;
        $product_name = sanitize($_POST['product_name']);
        $description = sanitize($_POST['description']);
        $price = (float)sanitize($_POST['price']);
        $stock_quantity = (int)sanitize($_POST['stock_quantity']);
        $category_id = (int)sanitize($_POST['category_id']);
        $brand = sanitize($_POST['brand']);
        
        try {
            if ($product_id > 0) {
                // Update existing product
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET product_name = ?, description = ?, price = ?, 
                        stock_quantity = ?, category_id = ?, brand = ?, 
                        updated_at = NOW() 
                    WHERE product_id = ?
                ");
                $stmt->execute([
                    $product_name, $description, $price, 
                    $stock_quantity, $category_id, $brand, 
                    $product_id
                ]);
                $success = 'Product updated successfully';
                
                logActivity($pdo, $_SESSION['user_id'], 'product_updated', 
                    "Updated product #{$product_id}: {$product_name}");
                    
            } else {
                // Add new product
                $stmt = $pdo->prepare("
                    INSERT INTO products (product_name, description, price, 
                                         stock_quantity, category_id, brand) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product_name, $description, $price, 
                    $stock_quantity, $category_id, $brand
                ]);
                $product_id = $pdo->lastInsertId();
                $success = 'Product added successfully';
                
                logActivity($pdo, $_SESSION['user_id'], 'product_added', 
                    "Added new product #{$product_id}: {$product_name}");
            }
        } catch (PDOException $e) {
            error_log("Product save error: " . $e->getMessage());
            $error = 'Failed to save product: ' . $e->getMessage();
        }
    }
}

// Get all products with categories
$stmt = $pdo->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC
");
$products = $stmt->fetchAll();

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <button onclick="openProductModal()" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td>
                                <img src="<?php echo $product['image_url'] ?: '../assets/images/default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo $product['category_name']; ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <span class="<?php echo $product['stock_quantity'] <= 10 ? 'stock-badge' : ''; ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="editProduct(<?php echo $product['product_id']; ?>)" 
                                            class="btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="delete_product" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" class="btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Product Modal -->
    <div id="productModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeProductModal()">&times;</span>
            <h2 id="modalTitle">Add New Product</h2>
            <form method="POST" id="productForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="save_product" value="1">
                <input type="hidden" name="product_id" id="product_id" value="0">
                
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="product_name" id="product_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Price *</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" id="brand">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Save Product</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function openProductModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('product_id').value = '0';
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function editProduct(productId) {
            // In a real implementation, you would fetch product details via AJAX
            // For now, we'll redirect to an edit page
            window.location.href = 'product-edit.php?id=' + productId;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }
    </script>
    
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }
        
        .close-modal {
            float: right;
            font-size: 28px;
            cursor: pointer;
        }
        
        .stock-badge {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</body>
</html>