/**
 * FRANCODER ELECTRONICS
 * Complete JavaScript for Product Layout Fixes
 */

// Cart Manager Class
class CartManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.updateCartCount();
        this.initQuantitySelectors();
        this.fixProductLayout();
    }
    
    setupEventListeners() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart') || 
                e.target.closest('.add-to-cart')) {
                e.preventDefault();
                const button = e.target.classList.contains('add-to-cart') ? 
                              e.target : e.target.closest('.add-to-cart');
                const productId = button.dataset.productId;
                this.addToCart(productId);
            }
            
            // Remove from cart
            if (e.target.classList.contains('remove-from-cart') || 
                e.target.closest('.remove-from-cart')) {
                e.preventDefault();
                const button = e.target.classList.contains('remove-from-cart') ? 
                              e.target : e.target.closest('.remove-from-cart');
                const cartId = button.dataset.cartId;
                this.removeFromCart(cartId);
            }
        });
        
        // Cart quantity changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cart-quantity')) {
                const cartId = e.target.dataset.cartId;
                const quantity = parseInt(e.target.value);
                this.updateCartQuantity(cartId, quantity);
            }
        });
    }
    
    async updateCartCount() {
        try {
            const response = await fetch('api/cart-api.php');
            if (response.ok) {
                const cartItems = await response.json();
                if (!cartItems.error) {
                    const total = cartItems.reduce((sum, item) => sum + (item.quantity || 0), 0);
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = total;
                        cartCount.style.display = total > 0 ? 'inline-block' : 'none';
                    }
                }
            }
        } catch (error) {
            console.log('Could not update cart count:', error);
        }
    }
    
    async addToCart(productId, quantity = 1) {
        try {
            const response = await fetch('api/cart-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Product added to cart!', 'success');
                this.updateCartCount();
            } else {
                this.showNotification('Failed to add to cart', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showNotification('Network error. Please try again.', 'error');
        }
    }
    
    async updateCartQuantity(cartId, quantity) {
        if (quantity < 1) {
            this.removeFromCart(cartId);
            return;
        }
        
        try {
            const response = await fetch('api/cart-api.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${quantity}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Cart updated', 'success');
                this.updateCartCount();
                
                // Update the page if on cart page
                if (window.location.pathname.includes('cart.php')) {
                    setTimeout(() => location.reload(), 500);
                }
            }
        } catch (error) {
            console.error('Error updating cart:', error);
            this.showNotification('Failed to update cart', 'error');
        }
    }
    
    async removeFromCart(cartId) {
        if (!confirm('Remove this item from cart?')) return;
        
        try {
            const response = await fetch('api/cart-api.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Item removed from cart', 'success');
                this.updateCartCount();
                
                // Remove from DOM if on cart page
                const itemElement = document.querySelector(`[data-cart-id="${cartId}"]`);
                if (itemElement) {
                    itemElement.style.opacity = '0';
                    itemElement.style.transform = 'translateX(-20px)';
                    setTimeout(() => itemElement.remove(), 300);
                }
            }
        } catch (error) {
            console.error('Error removing from cart:', error);
            this.showNotification('Failed to remove item', 'error');
        }
    }
    
    // Initialize quantity selectors
    initQuantitySelectors() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('qty-btn')) {
                const button = e.target;
                const input = button.parentElement.querySelector('input[type="number"]');
                if (!input) return;
                
                let value = parseInt(input.value) || 0;
                const min = parseInt(input.min) || 1;
                const max = parseInt(input.max) || 999;
                
                if (button.classList.contains('minus')) {
                    if (value > min) value--;
                } else if (button.classList.contains('plus')) {
                    if (value < max) value++;
                }
                
                input.value = value;
                
                // Trigger change event for product detail page
                if (input.id === 'quantity') {
                    const event = new Event('change');
                    input.dispatchEvent(event);
                }
            }
        });
        
        // Validate manual input
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[type="number"]')) {
                const input = e.target;
                let value = parseInt(input.value) || 0;
                const min = parseInt(input.min) || 1;
                const max = parseInt(input.max) || 999;
                
                if (value < min) input.value = min;
                if (value > max) input.value = max;
            }
        });
    }
    
    // Fix product layout issues
    fixProductLayout() {
        this.equalizeProductHeights();
        
        // Re-run on window resize
        window.addEventListener('resize', () => {
            this.equalizeProductHeights();
        });
        
        // Re-run after images load
        window.addEventListener('load', () => {
            this.equalizeProductHeights();
        });
    }
    
    equalizeProductHeights() {
        const productCards = document.querySelectorAll('.product-card');
        if (productCards.length === 0) return;
        
        // Reset all heights
        productCards.forEach(card => {
            card.style.minHeight = '';
        });
        
        // Calculate max height
        let maxHeight = 0;
        productCards.forEach(card => {
            card.style.height = 'auto';
            const height = card.offsetHeight;
            if (height > maxHeight) maxHeight = height;
        });
        
        // Apply consistent height (with limits)
        if (maxHeight > 300 && maxHeight < 800) {
            productCards.forEach(card => {
                card.style.minHeight = `${maxHeight}px`;
            });
        }
    }
    
    showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '6px',
            zIndex: '9999',
            color: 'white',
            fontWeight: '600',
            fontSize: '14px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            animation: 'slideIn 0.3s ease',
            backgroundColor: type === 'success' ? '#2ecc71' : 
                           type === 'error' ? '#e74c3c' : '#3498db'
        });
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Product Filter Class
class ProductFilter {
    constructor() {
        this.searchInput = document.getElementById('product-search');
        this.categoryFilter = document.getElementById('category-filter');
        this.priceFilter = document.getElementById('price-filter');
        this.sortFilter = document.getElementById('sort-filter');
        
        if (this.searchInput || this.categoryFilter || this.priceFilter || this.sortFilter) {
            this.init();
        }
    }
    
    init() {
        this.setupEventListeners();
        this.debouncedSearch();
    }
    
    setupEventListeners() {
        // Real-time search with debounce
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                this.debouncedSearch();
            });
        }
        
        // Filter changes
        [this.categoryFilter, this.priceFilter, this.sortFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => {
                    this.applyFilters();
                });
            }
        });
    }
    
    debouncedSearch() {
        if (!this.searchInput) return;
        
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.applyFilters();
        }, 500);
    }
    
    async applyFilters() {
        const params = new URLSearchParams();
        
        if (this.searchInput?.value) {
            params.append('search', this.searchInput.value);
        }
        
        if (this.categoryFilter?.value) {
            params.append('category', this.categoryFilter.value);
        }
        
        if (this.priceFilter?.value) {
            params.append('price', this.priceFilter.value);
        }
        
        if (this.sortFilter?.value) {
            params.append('sort', this.sortFilter.value);
        }
        
        // Update URL without page reload
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);
        
        // Show loading
        this.showLoading();
        
        try {
            const response = await fetch(`api/products-api.php?${params.toString()}`);
            const products = await response.json();
            
            if (Array.isArray(products)) {
                this.renderProducts(products);
            }
        } catch (error) {
            console.error('Error filtering products:', error);
            this.showNotification('Failed to load products', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    renderProducts(products) {
        const container = document.getElementById('products-container');
        if (!container) return;
        
        if (!products || products.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h2>No Products Found</h2>
                    <p>Try adjusting your search or filter criteria</p>
                    <button onclick="location.href='products.php'" class="btn-primary">
                        Clear Filters
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = products.map(product => `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.image_url || 'assets/images/default-product.jpg'}" 
                         alt="${product.product_name}"
                         loading="lazy">
                    ${product.stock_quantity <= 10 ? 
                      `<span class="stock-badge">Only ${product.stock_quantity} left</span>` : ''}
                </div>
                
                <div class="product-info">
                    <h3>${product.product_name}</h3>
                    <p class="product-brand">${product.brand || ''}</p>
                    <p class="product-desc">${this.truncateText(product.description || '', 100)}</p>
                    
                    <div class="price">$${parseFloat(product.price).toFixed(2)}</div>
                    
                    <div class="product-actions">
                        <a href="product-detail.php?id=${product.product_id}" class="btn-secondary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        
                        ${window.isLoggedIn ? 
                          `<button class="btn-primary add-to-cart" data-product-id="${product.product_id}">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                          </button>` :
                          `<a href="auth/login.php" class="btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Buy
                          </a>`
                        }
                    </div>
                </div>
            </div>
        `).join('');
        
        // Reinitialize cart functionality for new products
        window.cartManager?.initQuantitySelectors();
        window.cartManager?.fixProductLayout();
    }
    
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }
    
    showLoading() {
        const container = document.getElementById('products-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading products...</p>
            </div>
        `;
    }
    
    hideLoading() {
        // Loading state is replaced by renderProducts
    }
    
    showNotification(message, type) {
        if (window.cartManager) {
            window.cartManager.showNotification(message, type);
        }
    }
}

// Form Validation
class FormValidator {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupFormValidation();
    }
    
    setupFormValidation() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.hasAttribute('data-validate')) {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            }
        });
        
        // Real-time validation
        document.addEventListener('blur', (e) => {
            if (e.target.matches('input[required], select[required], textarea[required]')) {
                this.validateField(e.target);
            }
        }, true);
    }
    
    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Special validation for email fields
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });
        
        // Password confirmation
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            this.showFieldError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }
        
        return isValid;
    }
    
    validateField(field) {
        this.clearFieldError(field);
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showFieldError(field, 'This field is required');
            return false;
        }
        
        if (field.type === 'email' && field.value && !this.isValidEmail(field.value)) {
            this.showFieldError(field, 'Please enter a valid email address');
            return false;
        }
        
        if (field.type === 'number' && field.hasAttribute('min') && field.hasAttribute('max')) {
            const value = parseFloat(field.value);
            const min = parseFloat(field.getAttribute('min'));
            const max = parseFloat(field.getAttribute('max'));
            
            if (value < min || value > max) {
                this.showFieldError(field, `Please enter a value between ${min} and ${max}`);
                return false;
            }
        }
        
        return true;
    }
    
    showFieldError(field, message) {
        field.classList.add('error');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.cssText = `
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        `;
        
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-circle';
        
        errorElement.prepend(icon);
        field.parentNode.appendChild(errorElement);
    }
    
    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
}

// Mobile Menu Toggle
function initMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('show');
            menuBtn.innerHTML = navLinks.classList.contains('show') ? 
                              '<i class="fas fa-times"></i>' : 
                              '<i class="fas fa-bars"></i>';
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('nav') && navLinks.classList.contains('show')) {
                navLinks.classList.remove('show');
                menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
        
        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('show');
                menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });
    }
}

// Image Loading Helper
function initImageLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('loading' in HTMLImageElement.prototype) {
        // Browser supports native lazy loading
        images.forEach(img => {
            img.loading = 'lazy';
        });
    } else {
        // Fallback for older browsers
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            img.dataset.src = img.src;
            img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
            imageObserver.observe(img);
        });
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize cart manager
    window.cartManager = new CartManager();
    
    // Initialize product filter if on products page
    if (document.querySelector('.filter-form') || 
        document.getElementById('products-container')) {
        window.productFilter = new ProductFilter();
    }
    
    // Initialize form validation
    window.formValidator = new FormValidator();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize image loading
    initImageLoading();
    
    // Set global logged in state
    window.isLoggedIn = document.querySelector('.nav-links a[href*="logout.php"]') !== null;
    
    // Add CSS animation for notifications
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(100px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            .error {
                border-color: #e74c3c !important;
            }
            
            .error:focus {
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.25) !important;
            }
        `;
        document.head.appendChild(style);
    }
});

// Make functions available globally
window.equalizeProductHeights = () => window.cartManager?.equalizeProductHeights();
window.showNotification = (message, type) => window.cartManager?.showNotification(message, type);