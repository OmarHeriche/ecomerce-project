<?php
session_start();

// Initialize shopping cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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
        <div class="products-grid" id="featured-products-container">
            <!-- Featured products will be loaded here by JavaScript -->
        </div>
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>