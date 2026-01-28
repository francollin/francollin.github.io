// API Base URL
const API_URL = 'http://localhost/ecommerce/api';

// Global State
let currentUser = null;
let cart = [];
let allProducts = [];
let allCategories = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    loadCategories();
    loadProducts();
    updateCartUI();
});

// Section Navigation
function showSection(sectionName) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.getElementById(`${sectionName}-section`).classList.add('active');
    
    if (sectionName === 'products') {
        loadProducts();
    } else if (sectionName === 'orders') {
        loadOrders();
    } else if (sectionName === 'admin') {
        loadAdminData();
    } else if (sectionName === 'cart') {
        displayCart();
    }
}

// Authentication
async function checkSession() {
    try {
        const response = await fetch(`${API_URL}/auth.php?action=check`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.data;
            updateAuthUI(true);
        } else {
            updateAuthUI(false);
        }
    } catch (error) {
        console.error('Session check failed:', error);
        updateAuthUI(false);
    }
}

function updateAuthUI(isLoggedIn) {
    const authLinks = document.getElementById('auth-links');
    const userLinks = document.getElementById('user-links');
    const adminLink = document.getElementById('admin-link');
    const userName = document.getElementById('user-name');
    
    if (isLoggedIn) {
        authLinks.style.display = 'none';
        userLinks.style.display = 'flex';
        userName.textContent = currentUser.username;
        
        if (currentUser.role === 'admin') {
            adminLink.style.display = 'inline';
        }
    } else {
        authLinks.style.display = 'flex';
        userLinks.style.display = 'none';
        adminLink.style.display = 'none';
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    
    try {
        const response = await fetch(`${API_URL}/auth.php?action=login`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Login successful!');
            currentUser = data.data;
            updateAuthUI(true);
            showSection('products');
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Please try again.');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const userData = {
        username: document.getElementById('reg-username').value,
        email: document.getElementById('reg-email').value,
        password: document.getElementById('reg-password').value,
        full_name: document.getElementById('reg-fullname').value,
        phone: document.getElementById('reg-phone').value
    };
    
    try {
        const response = await fetch(`${API_URL}/auth.php?action=register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Registration successful! Please login.');
            showSection('login');
            document.getElementById('register-form').reset();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Registration error:', error);
        alert('Registration failed. Please try again.');
    }
}

async function logout() {
    try {
        await fetch(`${API_URL}/auth.php?action=logout`, {
            credentials: 'include'
        });
        
        currentUser = null;
        cart = [];
        updateAuthUI(false);
        updateCartUI();
        showSection('products');
        alert('Logged out successfully');
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// Categories
async function loadCategories() {
    try {
        const response = await fetch(`${API_URL}/categories.php`);
        const data = await response.json();
        
        if (data.success) {
            allCategories = data.data;
            populateCategoryFilters();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function populateCategoryFilters() {
    const categoryFilter = document.getElementById('category-filter');
    const productCategory = document.getElementById('product-category');
    
    allCategories.forEach(cat => {
        const option1 = document.createElement('option');
        option1.value = cat.category_id;
        option1.textContent = cat.category_name;
        categoryFilter.appendChild(option1);
        
        if (productCategory) {
            const option2 = document.createElement('option');
            option2.value = cat.category_id;
            option2.textContent = cat.category_name;
            productCategory.appendChild(option2);
        }
    });
}

// Products - FIXED WITH IMAGES
async function loadProducts() {
    try {
        const response = await fetch(`${API_URL}/products.php`);
        const data = await response.json();
        
        if (data.success) {
            allProducts = data.data;
            displayProducts(allProducts);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function displayProducts(products) {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = '';
    
    if (products.length === 0) {
        grid.innerHTML = '<p>No products found.</p>';
        return;
    }
    
    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.onclick = () => viewProductDetail(product.product_id);
        
        const stockClass = product.stock_quantity > 0 ? 'stock' : 'out-of-stock';
        const stockText = product.stock_quantity > 0 ? `In Stock: ${product.stock_quantity}` : 'Out of Stock';
        
        // Use actual image or placeholder
        const imageUrl = product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop';
        
        card.innerHTML = `
            <div class="product-image">
                <img src="${imageUrl}" alt="${escapeHtml(product.product_name)}" 
                     onerror="this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Product+Image'">
            </div>
            <div class="product-info">
                <h3>${escapeHtml(product.product_name)}</h3>
                <span class="category">${escapeHtml(product.category_name || 'Uncategorized')}</span>
                <p class="price">$${parseFloat(product.price).toFixed(2)}</p>
                <p class="rating">⭐ ${parseFloat(product.avg_rating || 0).toFixed(1)} (${product.review_count || 0} reviews)</p>
                <p class="${stockClass}">${stockText}</p>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

async function filterProducts() {
    const search = document.getElementById('search-input').value;
    const category = document.getElementById('category-filter').value;
    const minPrice = document.getElementById('min-price').value;
    const maxPrice = document.getElementById('max-price').value;
    const sort = document.getElementById('sort-select').value;
    
    let url = `${API_URL}/products.php?sort=${sort}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (category) url += `&category=${category}`;
    if (minPrice) url += `&min_price=${minPrice}`;
    if (maxPrice) url += `&max_price=${maxPrice}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.data);
        }
    } catch (error) {
        console.error('Error filtering products:', error);
    }
}

async function viewProductDetail(productId) {
    try {
        const response = await fetch(`${API_URL}/products.php?id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            displayProductDetail(data.data);
            showSection('product-detail');
        }
    } catch (error) {
        console.error('Error loading product:', error);
    }
}

function displayProductDetail(product) {
    const container = document.getElementById('product-detail');
    
    const stockClass = product.stock_quantity > 0 ? 'stock' : 'out-of-stock';
    const stockText = product.stock_quantity > 0 ? `In Stock: ${product.stock_quantity}` : 'Out of Stock';
    const addToCartBtn = product.stock_quantity > 0 ? 
        `<div class="quantity-selector">
            <button onclick="changeQuantity(-1)">-</button>
            <input type="number" id="quantity-input" value="1" min="1" max="${product.stock_quantity}">
            <button onclick="changeQuantity(1)">+</button>
        </div>
        <button class="btn-primary" onclick="addToCart(${product.product_id})">Add to Cart</button>` :
        '<p style="color: red;">Currently unavailable</p>';
    
    // Use actual image or placeholder
    const imageUrl = product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=400&fit=crop';
    
    let reviewsHTML = '<h3>Reviews</h3>';
    if (product.reviews && product.reviews.length > 0) {
        product.reviews.forEach(review => {
            reviewsHTML += `
                <div class="review-card">
                    <div class="review-header">
                        <strong>${escapeHtml(review.full_name)}</strong>
                        <span>⭐ ${review.rating}/5</span>
                    </div>
                    <p>${escapeHtml(review.review_text || '')}</p>
                    <small>${new Date(review.created_at).toLocaleDateString()}</small>
                </div>
            `;
        });
    } else {
        reviewsHTML += '<p>No reviews yet.</p>';
    }
    
    container.innerHTML = `
        <div class="product-detail-container">
            <div class="product-detail-image">
                <img src="${imageUrl}" alt="${escapeHtml(product.product_name)}" 
                     onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=Product+Image'">
            </div>
            <div class="product-detail-info">
                <h2>${escapeHtml(product.product_name)}</h2>
                <span class="category">${escapeHtml(product.category_name || 'Uncategorized')}</span>
                <p class="price">$${parseFloat(product.price).toFixed(2)}</p>
                <p class="${stockClass}">${stockText}</p>
                <p class="rating">⭐ ${parseFloat(product.avg_rating || 0).toFixed(1)} (${product.review_count || 0} reviews)</p>
                <p>${escapeHtml(product.description || 'No description available.')}</p>
                ${addToCartBtn}
            </div>
        </div>
        <div class="reviews-section">
            ${reviewsHTML}
        </div>
    `;
}

function changeQuantity(delta) {
    const input = document.getElementById('quantity-input');
    const newValue = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    if (newValue >= min && newValue <= max) {
        input.value = newValue;
    }
}

// Cart Management
function addToCart(productId) {
    const product = allProducts.find(p => p.product_id == productId);
    if (!product) return;
    
    const quantityInput = document.getElementById('quantity-input');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
    
    const existingItem = cart.find(item => item.product_id == productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            product_id: productId,
            product_name: product.product_name,
            price: product.price,
            quantity: quantity
        });
    }
    
    updateCartUI();
    alert('Added to cart!');
}

function updateCartUI() {
    const cartCount = document.getElementById('cart-count');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
}

function displayCart() {
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const checkoutForm = document.getElementById('checkout-form');
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p>Your cart is empty.</p>';
        cartTotal.innerHTML = '';
        checkoutForm.style.display = 'none';
        return;
    }
    
    let total = 0;
    cartItems.innerHTML = '';
    
    cart.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div>
                <h4>${escapeHtml(item.product_name)}</h4>
                <p>Price: $${parseFloat(item.price).toFixed(2)} x ${item.quantity}</p>
            </div>
            <div>
                <p>$${subtotal.toFixed(2)}</p>
                <button onclick="removeFromCart(${index})" class="btn-secondary">Remove</button>
            </div>
        `;
        cartItems.appendChild(cartItem);
    });
    
    cartTotal.innerHTML = `<h3>Total: $${total.toFixed(2)}</h3>`;
    
    if (currentUser) {
        checkoutForm.style.display = 'block';
    } else {
        checkoutForm.style.display = 'none';
        cartTotal.innerHTML += '<p>Please <a href="#" onclick="showSection(\'login\'); return false;">login</a> to checkout.</p>';
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
    displayCart();
}

async function placeOrder() {
    const address = document.getElementById('shipping-address').value.trim();
    
    if (!address) {
        alert('Please enter shipping address');
        return;
    }
    
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    const orderData = {
        items: cart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        })),
        shipping_address: address
    };
    
    try {
        const response = await fetch(`${API_URL}/orders.php`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Order placed successfully!');
            cart = [];
            updateCartUI();
            document.getElementById('shipping-address').value = '';
            showSection('orders');
            loadOrders();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Order error:', error);
        alert('Failed to place order');
    }
}

// Orders
async function loadOrders() {
    if (!currentUser) return;
    
    try {
        const response = await fetch(`${API_URL}/orders.php`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            displayOrders(data.data);
        }
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function displayOrders(orders) {
    const ordersList = document.getElementById('orders-list');
    
    if (orders.length === 0) {
        ordersList.innerHTML = '<p>No orders yet.</p>';
        return;
    }
    
    ordersList.innerHTML = '';
    
    orders.forEach(order => {
        const orderCard = document.createElement('div');
        orderCard.className = 'order-card';
        orderCard.innerHTML = `
            <div class="order-header">
                <div>
                    <strong>Order #${order.order_id}</strong>
                    <p>${new Date(order.order_date).toLocaleString()}</p>
                </div>
                <div>
                    <span class="order-status status-${order.status}">${order.status.toUpperCase()}</span>
                    <p><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></p>
                </div>
            </div>
            <p><strong>Shipping:</strong> ${escapeHtml(order.shipping_address)}</p>
            <button onclick="viewOrderDetail(${order.order_id})" class="btn-secondary">View Details</button>
        `;
        ordersList.appendChild(orderCard);
    });
}

async function viewOrderDetail(orderId) {
    try {
        const response = await fetch(`${API_URL}/orders.php?id=${orderId}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Order Details:\n' + JSON.stringify(data.data, null, 2));
        }
    } catch (error) {
        console.error('Error loading order:', error);
    }
}

// Admin Functions
async function loadAdminData() {
    if (!currentUser || currentUser.role !== 'admin') {
        alert('Admin access required');
        showSection('products');
        return;
    }
    
    loadAdminProducts();
    loadAdminOrders();
}

function showAdminTab(tab) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(`admin-${tab}`).classList.add('active');
    event.target.classList.add('active');
}

async function handleAddProduct(e) {
    e.preventDefault();
    
    const productData = {
        product_name: document.getElementById('product-name').value,
        category_id: document.getElementById('product-category').value,
        description: document.getElementById('product-description').value,
        price: document.getElementById('product-price').value,
        stock_quantity: document.getElementById('product-stock').value,
        image_url: document.getElementById('product-image').value
    };
    
    try {
        const response = await fetch(`${API_URL}/products.php`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(productData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Product added successfully!');
            document.getElementById('add-product-form').reset();
            loadProducts();
            loadAdminProducts();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error adding product:', error);
        alert('Failed to add product');
    }
}

async function loadAdminProducts() {
    try {
        const response = await fetch(`${API_URL}/products.php`);
        const data = await response.json();
        
        if (data.success) {
            displayAdminProducts(data.data);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function displayAdminProducts(products) {
    const list = document.getElementById('admin-products-list');
    list.innerHTML = '';
    
    products.forEach(product => {
        const item = document.createElement('div');
        item.className = 'admin-product-item';
        const imageUrl = product.image_url || 'https://via.placeholder.com/60x60/667eea/ffffff?text=IMG';
        
        item.innerHTML = `
            <div style="display:flex;align-items:center;">
                <img src="${imageUrl}" alt="${escapeHtml(product.product_name)}" 
                     style="width:60px;height:60px;object-fit:cover;border-radius:5px;margin-right:10px;">
                <div>
                    <strong>${escapeHtml(product.product_name)}</strong>
                    <p>Price: $${parseFloat(product.price).toFixed(2)} | Stock: ${product.stock_quantity}</p>
                </div>
            </div>
            <button onclick="deleteProduct(${product.product_id})" class="btn-secondary">Delete</button>
        `;
        list.appendChild(item);
    });
}

async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    try {
        const response = await fetch(`${API_URL}/products.php?id=${productId}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Product deleted');
            loadProducts();
            loadAdminProducts();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        alert('Failed to delete product');
    }
}

async function loadAdminOrders() {
    try {
        const response = await fetch(`${API_URL}/orders.php`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            displayAdminOrders(data.data);
        }
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function displayAdminOrders(orders) {
    const list = document.getElementById('admin-orders-list');
    list.innerHTML = '';
    
    orders.forEach(order => {
        const item = document.createElement('div');
        item.className = 'order-card';
        item.innerHTML = `
            <div class="order-header">
                <div>
                    <strong>Order #${order.order_id}</strong>
                    <p>Customer: ${escapeHtml(order.full_name)}</p>
                    <p>${new Date(order.order_date).toLocaleString()}</p>
                </div>
                <div>
                    <select onchange="updateOrderStatus(${order.order_id}, this.value)">
                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                        <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                        <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                    <p><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></p>
                </div>
            </div>
        `;
        list.appendChild(item);
    });
}

async function updateOrderStatus(orderId, status) {
    try {
        const response = await fetch(`${API_URL}/orders.php?id=${orderId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Order status updated');
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error updating order:', error);
        alert('Failed to update order');
    }
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}