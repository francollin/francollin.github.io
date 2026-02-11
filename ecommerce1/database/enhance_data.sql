-- Add more sample data
INSERT INTO products (product_name, description, price, stock_quantity, category_id, brand, specifications) VALUES
('UltraBook Air', 'Thin and light laptop for professionals', 1299.99, 40, 1, 'TechBrand', '{"processor": "Intel i5", "ram": "16GB", "storage": "512GB SSD", "display": "14 inch"}'),
('Smart Watch Pro', 'Advanced smartwatch with health monitoring', 299.99, 100, 5, 'WatchTech', '{"battery": "7 days", "connectivity": "Bluetooth 5.2", "water_resistant": "50m"}'),
('Gaming Mouse', 'RGB gaming mouse with 16000 DPI', 79.99, 150, 5, 'GameGear', '{"dpi": "16000", "buttons": "8", "weight": "95g"}'),
('Bluetooth Speaker', 'Portable speaker with 360° sound', 149.99, 80, 4, 'SoundMaster', '{"battery": "15 hours", "power": "20W", "weight": "800g"}'),
('4K Monitor', '27-inch 4K UHD monitor', 399.99, 25, 5, 'DisplayPro', '{"resolution": "3840x2160", "refresh_rate": "60Hz", "panel": "IPS"}'),
('Tablet Pro', 'High-performance tablet with stylus', 899.99, 35, 1, 'TabTech', '{"screen": "11 inch", "storage": "256GB", "ram": "8GB", "battery": "10 hours"}');

-- Add some orders
INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) VALUES
(2, 899.99, 'delivered', '123 Main St, City, Country', 'card'),
(2, 1499.99, 'processing', '456 Oak Ave, Town, Country', 'cod');

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 2, 1, 899.99),
(2, 1, 1, 1499.99);

-- Add reviews
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(2, 1, 5, 'Excellent laptop! Perfect for gaming and work.'),
(2, 3, 4, 'Great headphones, noise cancellation works well.'),
(2, 2, 5, 'Amazing camera quality and battery life!');

-- Update admin password (use: admin123)
UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- Update customer password (use: customer123)
UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'customer1';