-- Complete Database Schema
CREATE DATABASE IF NOT EXISTS francoder;
USE francoder;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(500),
    brand VARCHAR(100),
    specifications JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Orders table
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method ENUM('cod', 'card', 'bank_transfer') DEFAULT 'cod',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO categories (category_name, description) VALUES
('Laptops', 'Various laptop models from different brands'),
('Smartphones', 'Latest smartphones and accessories'),
('Gaming Consoles', 'Gaming consoles and accessories'),
('Audio Equipment', 'Headphones, speakers, and audio systems'),
('Computer Accessories', 'Keyboards, mice, monitors, and other accessories');

INSERT INTO products (product_name, description, price, stock_quantity, category_id, brand, specifications, image_url) VALUES
('Gaming Laptop Pro', 'High-performance gaming laptop with RTX 4070', 1499.99, 25, 1, 'BrandX', '{"processor": "Intel i7", "ram": "32GB", "storage": "1TB SSD", "display": "15.6 inch"}', 'https://via.placeholder.com/300x300'),
('Smartphone Ultra', 'Latest smartphone with 5G and 200MP camera', 899.99, 50, 2, 'BrandY', '{"storage": "256GB", "ram": "12GB", "battery": "5000mAh", "camera": "200MP"}', 'https://via.placeholder.com/300x300'),
('Wireless Headphones', 'Noise cancelling wireless headphones', 199.99, 100, 4, 'AudioTech', '{"battery": "30 hours", "connectivity": "Bluetooth 5.3", "weight": "250g"}', 'https://via.placeholder.com/300x300'),
('Mechanical Keyboard', 'RGB mechanical keyboard with blue switches', 129.99, 75, 5, 'KeyMaster', '{"switches": "Blue", "backlight": "RGB", "connectivity": "USB-C"}', 'https://via.placeholder.com/300x300'),
('Gaming Console', 'Next-gen gaming console with 1TB storage', 499.99, 30, 3, 'GameBox', '{"storage": "1TB", "resolution": "4K", "controller": "Wireless"}', 'https://via.placeholder.com/300x300'),
('UltraBook Air', 'Thin and light laptop for professionals', 1299.99, 40, 1, 'TechBrand', '{"processor": "Intel i5", "ram": "16GB", "storage": "512GB SSD", "display": "14 inch"}', 'https://via.placeholder.com/300x300'),
('Smart Watch Pro', 'Advanced smartwatch with health monitoring', 299.99, 100, 5, 'WatchTech', '{"battery": "7 days", "connectivity": "Bluetooth 5.2", "water_resistant": "50m"}', 'https://via.placeholder.com/300x300'),
('Gaming Mouse', 'RGB gaming mouse with 16000 DPI', 79.99, 150, 5, 'GameGear', '{"dpi": "16000", "buttons": "8", "weight": "95g"}', 'https://via.placeholder.com/300x300');

-- Create admin user (password: admin123) and customer user (password: customer123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@francoder.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin'),
('customer1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'customer');