<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Check if email exists (except for current user)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $error = 'Email already exists';
    } else {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([$full_name, $email, $phone, $address, $user_id])) {
            $success = 'Profile updated successfully';
            $_SESSION['full_name'] = $full_name;
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!verifyPassword($current_password, $user['password_hash'])) {
        $error = 'Current password is incorrect';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hashed_password = hashPassword($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password';
        }
    }
}

$page_title = "My Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="page-header">
            <h1>My Profile</h1>
        </div>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle fa-5x"></i>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="member-since">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
                
                <div class="profile-menu">
                    <a href="#profile-info" class="active">Profile Information</a>
                    <a href="#change-password">Change Password</a>
                    <a href="orders.php">My Orders</a>
                </div>
            </div>
            
            <div class="profile-content">
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Profile Information Form -->
                <div id="profile-info" class="profile-section active">
                    <h2>Profile Information</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="4"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password Form -->
                <div id="change-password" class="profile-section">
                    <h2>Change Password</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile menu navigation
            const menuLinks = document.querySelectorAll('.profile-menu a');
            const profileSections = document.querySelectorAll('.profile-section');
            
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and sections
                    menuLinks.forEach(l => l.classList.remove('active'));
                    profileSections.forEach(s => s.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const targetId = this.getAttribute('href').substring(1);
                    document.getElementById(targetId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>