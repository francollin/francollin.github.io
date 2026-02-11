<?php
http_response_code(403);
$page_title = "Access Denied";
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
        <div class="error-container">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1>403</h1>
                <h2>Access Denied</h2>
                <p>You don't have permission to access this page.</p>
                <div class="error-actions">
                    <a href="index.php" class="btn-primary">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <a href="auth/login.php" class="btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>