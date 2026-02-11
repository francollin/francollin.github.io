<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Access denied', 'error');
}

$page_title = "Manage Categories";

// Handle actions
$action = $_GET['action'] ?? '';
$category_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $category_name = sanitize($_POST['category_name']);
                $description = sanitize($_POST['description']);
                
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $success = $stmt->execute([$category_name, $description]);
                
                if ($success) {
                    redirect('categories.php', 'Category added successfully');
                } else {
                    $error = 'Failed to add category';
                }
                break;
                
            case 'edit':
                $category_id = (int)$_POST['category_id'];
                $category_name = sanitize($_POST['category_name']);
                $description = sanitize($_POST['description']);
                
                $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                $success = $stmt->execute([$category_name, $description, $category_id]);
                
                if ($success) {
                    redirect('categories.php', 'Category updated successfully');
                } else {
                    $error = 'Failed to update category';
                }
                break;
                
            case 'delete':
                $category_id = (int)$_POST['category_id'];
                
                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $error = "Cannot delete category with {$product_count} product(s). Move products first.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $success = $stmt->execute([$category_id]);
                    
                    if ($success) {
                        redirect('categories.php', 'Category deleted successfully');
                    } else {
                        $error = 'Failed to delete category';
                    }
                }
                break;
        }
    }
}

// Get all categories
$categories = $pdo->query("
    SELECT c.*, COUNT(p.product_id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.category_id = p.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Manage Categories</h1>
                <button class="btn-primary" onclick="showAddForm()">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Categories List -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>#<?php echo $category['category_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                <td><?php echo $category['product_count']; ?> products</td>
                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                <td class="actions">
                                    <button onclick="showEditForm(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>', '<?php echo htmlspecialchars($category['description'] ?? ''); ?>')" 
                                            class="btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <button onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')" 
                                                class="btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add/Edit Form Modal -->
            <div id="categoryModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Add Category</h2>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="categoryForm" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="category_id" id="categoryId" value="0">
                            
                            <div class="form-group">
                                <label>Category Name *</label>
                                <input type="text" name="category_name" id="categoryName" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="description" rows="4"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Save Category</button>
                                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Confirm Delete</h2>
                        <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete "<span id="deleteCategoryName"></span>"?</p>
                        <form id="deleteForm" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" id="deleteCategoryId" value="">
                            <div class="form-actions">
                                <button type="submit" class="btn-danger">Delete</button>
                                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function showAddForm() {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryId').value = '0';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function showEditForm(categoryId, categoryName, description) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = categoryId;
            document.getElementById('categoryName').value = categoryName;
            document.getElementById('description').value = description;
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').textContent = categoryName;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        };
    </script>
</body>
</html>