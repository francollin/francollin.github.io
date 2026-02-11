<?php
http_response_code(404);
$page_title = "Page Not Found";
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
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>404</h1>
                <h2>Page Not Found</h2>
                <p>The page you are looking for doesn't exist or has been moved.</p>
                <div class="error-actions">
                    <a href="index.php" class="btn-primary">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <a href="products.php" class="btn-secondary">
                        <i class="fas fa-shopping-bag"></i> Browse Products
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>