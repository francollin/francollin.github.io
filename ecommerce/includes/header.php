<?php
require_once 'config.php';
require_once 'functions.php';

$cart_count = isLoggedIn() ? getCartCount($pdo, $_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>index.php">
                    <i class="fas fa-laptop-code"></i> Francoder
                </a>
            </div>
            
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cart_count > 0): ?>
                            <span id="cart-count" class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>orders.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo SITE_URL; ?>admin/"><i class="fas fa-cog"></i> Admin</a></li>
                    <?php endif; ?>
                    
                    <li><a href="<?php echo SITE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>auth/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">