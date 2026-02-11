<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Products - Francoder Electronics";

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Build query for products
$where = ["stock_quantity > 0"];
$params = [];

// Handle filters from GET parameters
if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $where[] = "p.category_id = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = "(p.product_name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $search_term = "%" . sanitize($_GET['search']) . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (isset($_GET['price'])) {
    switch ($_GET['price']) {
        case 'under100':
            $where[] = "p.price < 100";
            break;
        case '100-500':
            $where[] = "p.price BETWEEN 100 AND 500";
            break;
        case '500-1000':
            $where[] = "p.price BETWEEN 500 AND 1000";
            break;
        case '1000-2000':
            $where[] = "p.price BETWEEN 1000 AND 2000";
            break;
        case 'over2000':
            $where[] = "p.price > 2000";
            break;
    }
}

// Build SQL query
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.product_name ASC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.product_id DESC"; // In real app, order by sales/views
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Count total products for display
$total_products = count($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for products page */
        .price-filters {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 5px;
        }
        
        .price-filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .price-filter-option:hover {
            background: #f0f0f0;
        }
        
        .price-filter-option input[type="radio"] {
            margin: 0;
        }
        
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
        }
        
        .active-filter {
            background: #2196F3;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .active-filter .remove {
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .active-filter .remove:hover {
            color: #ffcdd2;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>Our Electronics Collection</h1>
            <p>Discover the latest gadgets and electronics at the best prices</p>
        </div>
        
        <div class="products-container">
            <!-- Sidebar Filters -->
            <aside class="sidebar">
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Filters</h3>
                    
                    <form method="GET" class="filter-form">
                        <!-- Search -->
                        <div class="form-group">
                            <label for="product-search">
                                <i class="fas fa-search"></i> Search Products
                            </label>
                            <input type="text" 
                                   id="product-search" 
                                   name="search" 
                                   placeholder="Type product name, brand..."
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="form-group">
                            <label for="category-filter">
                                <i class="fas fa-folder"></i> Category
                            </label>
                            <select id="category-filter" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Filter -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i> Price Range
                            </label>
                            <div class="price-filters">
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="" 
                                           <?php echo !isset($_GET['price']) ? 'checked' : ''; ?>>
                                    <span>All Prices</span>
                                </label>
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="under100"
                                           <?php echo (isset($_GET['price']) && $_GET['price'] == 'under100') ? 'checked' : ''; ?>>
                                    <span>Under $100</span>
                                </label>
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="100-500"
                                           <?php echo (isset($_GET['price']) && $_GET['price'] == '100-500') ? 'checked' : ''; ?>>
                                    <span>$100 - $500</span>
                                </label>
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="500-1000"
                                           <?php echo (isset($_GET['price']) && $_GET['price'] == '500-1000') ? 'checked' : ''; ?>>
                                    <span>$500 - $1,000</span>
                                </label>
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="1000-2000"
                                           <?php echo (isset($_GET['price']) && $_GET['price'] == '1000-2000') ? 'checked' : ''; ?>>
                                    <span>$1,000 - $2,000</span>
                                </label>
                                <label class="price-filter-option">
                                    <input type="radio" name="price" value="over2000"
                                           <?php echo (isset($_GET['price']) && $_GET['price'] == 'over2000') ? 'checked' : ''; ?>>
                                    <span>Over $2,000</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="form-group">
                            <label for="sort-filter">
                                <i class="fas fa-sort"></i> Sort By
                            </label>
                            <select id="sort-filter" name="sort">
                                <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo ($sort == 'name') ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="popular" <?php echo ($sort == 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn-primary" style="flex: 1;">
                                <i class="fas fa-check"></i> Apply Filters
                            </button>
                            <a href="products.php" class="btn-secondary" style="flex: 1; text-align: center;">
                                <i class="fas fa-times"></i> Clear All
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Categories List -->
                <div class="filter-section">
                    <h3><i class="fas fa-list"></i> Shop by Category</h3>
                    <div style="margin-top: 15px;">
                        <?php foreach ($categories as $category): ?>
                        <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                           class="price-filter-option" 
                           style="display: block; text-decoration: none; color: inherit;">
                            <i class="fas fa-arrow-right"></i>
                            <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                            <span style="margin-left: auto; color: #7f8c8d; font-size: 0.9rem;">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND stock_quantity > 0");
                                $stmt->execute([$category['category_id']]);
                                echo '(' . $stmt->fetchColumn() . ')';
                                ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            
            <!-- Main Products Area -->
            <div class="products-main">
                <!-- Active Filters -->
                <?php if (isset($_GET['search']) || isset($_GET['category']) || isset($_GET['price'])): ?>
                <div class="active-filters">
                    <span style="font-weight: 600; color: #1565C0;">Active Filters:</span>
                    
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <div class="active-filter">
                        Search: "<?php echo htmlspecialchars($_GET['search']); ?>"
                        <a href="<?php echo removeQueryParam('search'); ?>" class="remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['category']) && is_numeric($_GET['category'])): 
                        $cat_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['category_id'] == $_GET['category']) {
                                $cat_name = $cat['category_name'];
                                break;
                            }
                        }
                    ?>
                    <div class="active-filter">
                        Category: <?php echo htmlspecialchars($cat_name); ?>
                        <a href="<?php echo removeQueryParam('category'); ?>" class="remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['price'])): 
                        $price_labels = [
                            'under100' => 'Under $100',
                            '100-500' => '$100 - $500',
                            '500-1000' => '$500 - $1,000',
                            '1000-2000' => '$1,000 - $2,000',
                            'over2000' => 'Over $2,000'
                        ];
                    ?>
                    <div class="active-filter">
                        Price: <?php echo $price_labels[$_GET['price']] ?? $_GET['price']; ?>
                        <a href="<?php echo removeQueryParam('price'); ?>" class="remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <a href="products.php" class="btn-secondary btn-sm" style="margin-left: auto;">
                        <i class="fas fa-times-circle"></i> Clear All Filters
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Products Header -->
                <div class="products-header">
                    <div>
                        <h2 style="margin: 0; color: #2c3e50; font-size: 1.4rem;">
                            <?php echo $total_products; ?> Product<?php echo $total_products != 1 ? 's' : ''; ?> Found
                        </h2>
                        <?php if ($total_products > 0): ?>
                        <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                            Showing all products
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: #7f8c8d; font-size: 0.95rem;">View:</span>
                        <button id="grid-view" class="btn-secondary btn-sm" style="padding: 8px 12px;">
                            <i class="fas fa-th"></i> Grid
                        </button>
                        <button id="list-view" class="btn-secondary btn-sm" style="padding: 8px 12px;">
                            <i class="fas fa-list"></i> List
                        </button>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if ($total_products > 0): ?>
                <div class="products-grid" id="products-container">
                    <?php foreach ($products as $product): 
                        // Clean description
                        $description = strip_tags($product['description']);
                        $short_desc = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php 
                                if (!empty($product['image_url']) && filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                                    echo htmlspecialchars($product['image_url']);
                                } else {
                                    echo 'assets/images/default-product.jpg';
                                }
                            ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                 loading="lazy">
                            
                            <?php if ($product['stock_quantity'] <= 10 && $product['stock_quantity'] > 0): ?>
                            <span class="stock-badge">
                                <i class="fas fa-exclamation-circle"></i>
                                Only <?php echo $product['stock_quantity']; ?> left
                            </span>
                            <?php elseif ($product['stock_quantity'] == 0): ?>
                            <span class="stock-badge" style="background: #95a5a6;">
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            
                            <p class="product-brand">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($product['brand'] ?: 'Unknown Brand'); ?>
                            </p>
                            
                            <p class="product-desc"><?php echo htmlspecialchars($short_desc); ?></p>
                            
                            <div class="price">
                                <i class="fas fa-dollar-sign"></i>
                                <?php echo number_format($product['price'], 2); ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <?php if (isLoggedIn()): ?>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn-primary add-to-cart" 
                                            data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-secondary" disabled>
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login to Buy
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination (for future implementation) -->
                <?php if ($total_products > 20): ?>
                <div class="pagination" style="margin-top: 40px; text-align: center;">
                    <button class="btn-secondary">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <span style="margin: 0 15px; color: #7f8c8d;">Page 1 of <?php echo ceil($total_products / 20); ?></span>
                    <button class="btn-secondary">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- No Products Found -->
                <div class="empty-state">
                    <i class="fas fa-search fa-4x"></i>
                    <h2>No Products Found</h2>
                    <p>We couldn't find any products matching your criteria. Try adjusting your search or filters.</p>
                    <a href="products.php" class="btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Helper function to remove query parameters
        function removeQueryParam(param) {
            const url = new URL(window.location.href);
            url.searchParams.delete(param);
            return url.toString();
        }
        
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const gridViewBtn = document.getElementById('grid-view');
            const listViewBtn = document.getElementById('list-view');
            const productsGrid = document.getElementById('products-container');
            
            if (gridViewBtn && listViewBtn && productsGrid) {
                gridViewBtn.addEventListener('click', function() {
                    productsGrid.classList.remove('list-view');
                    gridViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    localStorage.setItem('product-view', 'grid');
                });
                
                listViewBtn.addEventListener('click', function() {
                    productsGrid.classList.add('list-view');
                    listViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    localStorage.setItem('product-view', 'list');
                });
                
                // Load saved view preference
                const savedView = localStorage.getItem('product-view') || 'grid';
                if (savedView === 'list') {
                    listViewBtn.click();
                } else {
                    gridViewBtn.click();
                }
            }
            
            // Initialize cart functionality
            if (typeof window.cartManager !== 'undefined') {
                window.cartManager.equalizeProductHeights();
            }
        });
        
        // Add CSS for list view
        const style = document.createElement('style');
        style.textContent = `
            .products-grid.list-view {
                grid-template-columns: 1fr !important;
            }
            
            .products-grid.list-view .product-card {
                flex-direction: row !important;
                min-height: 200px !important;
            }
            
            .products-grid.list-view .product-image {
                width: 200px !important;
                height: 200px !important;
                flex-shrink: 0 !important;
            }
            
            .products-grid.list-view .product-info {
                padding-left: 20px !important;
            }
            
            .products-grid.list-view .product-actions {
                flex-direction: row !important;
                gap: 10px !important;
            }
            
            .products-grid.list-view .product-actions .btn-secondary,
            .products-grid.list-view .product-actions .btn-primary {
                width: auto !important;
            }
            
            @media (max-width: 768px) {
                .products-grid.list-view .product-card {
                    flex-direction: column !important;
                }
                
                .products-grid.list-view .product-image {
                    width: 100% !important;
                }
                
                .products-grid.list-view .product-info {
                    padding-left: 0 !important;
                }
            }
            
            .btn-secondary.active,
            .btn-primary.active {
                background: #2980b9 !important;
                color: white !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<?php
// Helper function to remove query parameters
function removeQueryParam($param) {
    $query = $_GET;
    unset($query[$param]);
    return 'products.php' . (count($query) > 0 ? '?' . http_build_query($query) : '');
}
?>