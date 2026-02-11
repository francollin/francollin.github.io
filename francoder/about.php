<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "About Us";
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
            <h1>About Francoder Electronics</h1>
        </div>
        
        <div class="about-container">
            <section class="about-section">
                <h2>Our Story</h2>
                <p>
                    Founded in 2023, Francoder Electronics started with a simple mission: to provide 
                    high-quality electronics at affordable prices. What began as a small online store 
                    has grown into one of the most trusted names in electronics retail.
                </p>
                <p>
                    We believe that everyone should have access to the latest technology, whether 
                    you're a student, professional, or gaming enthusiast. That's why we carefully 
                    curate our product selection to include the best devices from leading brands.
                </p>
            </section>
            
            <section class="about-section">
                <h2>Our Mission</h2>
                <div class="mission-grid">
                    <div class="mission-item">
                        <i class="fas fa-award"></i>
                        <h3>Quality Products</h3>
                        <p>We only sell products that meet our strict quality standards.</p>
                    </div>
                    <div class="mission-item">
                        <i class="fas fa-shipping-fast"></i>
                        <h3>Fast Delivery</h3>
                        <p>Get your orders delivered quickly and securely.</p>
                    </div>
                    <div class="mission-item">
                        <i class="fas fa-headset"></i>
                        <h3>Customer Support</h3>
                        <p>Our team is here to help you 24/7.</p>
                    </div>
                    <div class="mission-item">
                        <i class="fas fa-hand-holding-usd"></i>
                        <h3>Best Prices</h3>
                        <p>We offer competitive prices with regular discounts.</p>
                    </div>
                </div>
            </section>
            
            <section class="about-section">
                <h2>Why Choose Us?</h2>
                <div class="features-list">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>Authentic Products</h3>
                            <p>All products are 100% authentic with manufacturer warranty.</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>Easy Returns</h3>
                            <p>30-day return policy for all products.</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>Secure Payment</h3>
                            <p>Multiple secure payment options available.</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>Expert Advice</h3>
                            <p>Our team can help you choose the right product.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="about-section">
                <h2>Contact Information</h2>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> 123 Tech Street, Digital City, DC 10001</p>
                    <p><i class="fas fa-phone"></i> <strong>Phone:</strong> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> info@francoder.com</p>
                    <p><i class="fas fa-clock"></i> <strong>Business Hours:</strong> Mon-Sun, 9:00 AM - 10:00 PM</p>
                </div>
            </section>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>