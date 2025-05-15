<?php
session_start();
require_once 'models/Product.php';

// Initialize Product model
$productModel = new Product();

// Handle filters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Get products based on filters
if ($category !== 'all' && !empty($search)) {
    // Both category and search
    $products = $productModel->getProductsByCategory($category);
    // Filter further by search
    $products = array_filter($products, function($product) use ($search) {
        return (stripos($product['name'], $search) !== false || 
                stripos($product['description'], $search) !== false);
    });
} elseif ($category !== 'all') {
    // Only category filter
    $products = $productModel->getProductsByCategory($category);
} elseif (!empty($search)) {
    // Only search filter
    $products = $productModel->searchProducts($search);
} else {
    // No filters
    $products = $productModel->getAllProducts();
}

// Apply sorting
if (!empty($products)) {
    switch($sort) {
        case 'price-low':
            usort($products, function($a, $b) {
                return $a['price'] - $b['price'];
            });
            break;
        case 'price-high':
            usort($products, function($a, $b) {
                return $b['price'] - $a['price'];
            });
            break;
        case 'name-asc':
            usort($products, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            break;
        case 'name-desc':
            usort($products, function($a, $b) {
                return strcmp($b['name'], $a['name']);
            });
            break;
        // default - no sorting needed
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <div class="products-header">
        <h1>All Products</h1>
        <form action="products.php" method="get" class="filters">
            <div class="filter-group">
                <label for="category-filter">Category:</label>
                <select id="category-filter" name="category">
                    <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="smartphones" <?php echo $category === 'smartphones' ? 'selected' : ''; ?>>Smartphones</option>
                    <option value="laptops" <?php echo $category === 'laptops' ? 'selected' : ''; ?>>Laptops</option>
                    <option value="audio" <?php echo $category === 'audio' ? 'selected' : ''; ?>>Audio</option>
                    <option value="wearables" <?php echo $category === 'wearables' ? 'selected' : ''; ?>>Wearables</option>
                    <option value="tvs" <?php echo $category === 'tvs' ? 'selected' : ''; ?>>TVs</option>
                    <option value="cameras" <?php echo $category === 'cameras' ? 'selected' : ''; ?>>Cameras</option>
                    <option value="gaming" <?php echo $category === 'gaming' ? 'selected' : ''; ?>>Gaming</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort-filter">Sort By:</label>
                <select id="sort-filter" name="sort">
                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name-asc" <?php echo $sort === 'name-asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    <option value="name-desc" <?php echo $sort === 'name-desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" id="search-filter" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" id="search-btn" class="btn">Search</button>
            </div>
        </form>
    </div>
    
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p class="no-products">No products found matching your criteria.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
    .filters {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    #search-btn {
        width: 100%;
        margin-top: 5px;
    }
    
    .no-products {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px;
        color: #666;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>