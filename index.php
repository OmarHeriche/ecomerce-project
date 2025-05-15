<?php
session_start();
require_once 'models/Product.php';

// Initialize shopping cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get featured products from database
$productModel = new Product();
$featuredProducts = $productModel->getFeaturedProducts();

// Include header
include 'includes/header.php';
?>

<!-- Main content -->
<main class="container">
    <div class="hero-section">
        <h1>Welcome to TechShop</h1>
        <p>Your one-stop shop for the latest electronics and gadgets</p>
        <a href="products.php" class="btn">Shop Now</a>
    </div>
    
    <section class="featured-products">
        <h2>Featured Products</h2>
        <div class="products-grid">
            <?php if (empty($featuredProducts)): ?>
                <p>No featured products available at the moment.</p>
            <?php else: ?>
                <?php foreach ($featuredProducts as $product): ?>
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
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>