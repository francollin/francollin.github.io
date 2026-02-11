# Francoder Electronics Ordering System

A complete full-stack web application for electronic product ordering built with PHP, MySQL, and vanilla JavaScript.

## Features

- **User Authentication**: Registration, login, password hashing, role-based access
- **Product Management**: Browse, search, filter, view details
- **Shopping Cart**: Add, remove, update quantities
- **Order System**: Checkout, order tracking, order history
- **Admin Panel**: Full CRUD for products, categories, users, orders
- **Reviews & Ratings**: Product reviews with ratings
- **Responsive Design**: Mobile-friendly interface
- **Security**: CSRF protection, XSS prevention, SQL injection prevention

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Security**: bcrypt password hashing, prepared statements, CSRF tokens
- **APIs**: RESTful API with Fetch API

## Installation

### Method 1: Quick Install
1. Upload all files to your web server
2. Navigate to `/install.php` in your browser
3. Follow the installation wizard
4. Delete `install.php` after installation

### Method 2: Manual Installation
1. Import `database/francoder.sql` to your MySQL database
2. Update database credentials in `includes/config.php`
3. Set `SITE_URL` in `includes/config.php`
4. Ensure `assets/images/` directory is writable

### Default Credentials
- **Admin**: admin / admin123
- **Customer**: customer1 / customer123

## Project Structure
