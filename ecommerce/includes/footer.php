    </div> <!-- Close container -->
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Francoder</h3>
                <p>Your trusted source for quality electronics since 2023</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>products.php">Products</a></li>
                    <li><a href="<?php echo SITE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p><i class="fas fa-map-marker-alt"></i> machava ,kigamboni</p>
                <p><i class="fas fa-phone"></i> +255 749247378</p>
                <p><i class="fas fa-envelope"></i> francoder@gmail.com</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Francoder. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>