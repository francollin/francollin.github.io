-- Update products with sample image URLs
UPDATE products SET image_url = 'assets/images/laptop.jpg' WHERE product_id = 1;
UPDATE products SET image_url = 'assets/images/smartphone.jpg' WHERE product_id = 2;
UPDATE products SET image_url = 'assets/images/headphones.jpg' WHERE product_id = 3;
UPDATE products SET image_url = 'assets/images/keyboard.jpg' WHERE product_id = 4;
UPDATE products SET image_url = 'assets/images/console.jpg' WHERE product_id = 5;

-- Or use placeholder images
UPDATE products SET image_url = 'https://via.placeholder.com/400x300?text=Laptop' WHERE product_id = 1;
UPDATE products SET image_url = 'https://via.placeholder.com/400x300?text=Smartphone' WHERE product_id = 2;
UPDATE products SET image_url = 'https://via.placeholder.com/400x300?text=Headphones' WHERE product_id = 3;
UPDATE products SET image_url = 'https://via.placeholder.com/400x300?text=Keyboard' WHERE product_id = 4;
UPDATE products SET image_url = 'https://via.placeholder.com/400x300?text=Console' WHERE product_id = 5;