<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Access denied', 'error');
}

$page_title = "Manage Users";

// Handle actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $role = sanitize($_POST['role']);
                
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $success = $stmt->execute([$role, $user_id]);
                
                if ($success) {
                    redirect('users.php', 'User role updated successfully');
                } else {
                    $error = 'Failed to update user role';
                }
                break;
                
            case 'delete':
                $user_id = (int)$_POST['user_id'];
                
                // Don't allow deleting yourself
                if ($user_id == $_SESSION['user_id']) {
                    $error = 'You cannot delete your own account';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $success = $stmt->execute([$user_id]);
                    
                    if ($success) {
                        redirect('users.php', 'User deleted successfully');
                    } else {
                        $error = 'Failed to delete user';
                    }
                }
                break;
        }
    }
}

// Get users with filters
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql = "SELECT * FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
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
                <h1>Manage Users</h1>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #3498db;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #2ecc71;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_customers; ?></h3>
                        <p>Customers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #e74c3c;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_admins; ?></h3>
                        <p>Admins</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="admin-filters">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="customer" <?php echo $role_filter == 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn-secondary">Filter</button>
                    <a href="users.php" class="btn-secondary">Clear</a>
                </form>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Users Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="actions">
                                    <button onclick="showRoleModal(<?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" 
                                            class="btn-primary btn-sm">
                                        <i class="fas fa-user-cog"></i> Role
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
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
            
            <!-- Role Update Modal -->
            <div id="roleModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Update User Role</h2>
                        <button class="close-btn" onclick="closeRoleModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Updating role for: <strong id="roleUsername"></strong></p>
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" id="roleUserId">
                            
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" id="roleSelect" required>
                                    <option value="customer">Customer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Update Role</button>
                                <button type="button" class="btn-secondary" onclick="closeRoleModal()">Cancel</button>
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
                        <p>Are you sure you want to delete user "<span id="deleteUsername"></span>"?</p>
                        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                        <form id="deleteForm" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" id="deleteUserId" value="">
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
        function showRoleModal(userId, currentRole, username) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleSelect').value = currentRole;
            document.getElementById('roleUsername').textContent = username;
            document.getElementById('roleModal').style.display = 'block';
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
        
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
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