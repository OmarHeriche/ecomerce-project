<?php
session_start();
require_once 'models/Product.php';
require_once 'models/Cart.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Initialize Product model
$productModel = new Product();

// Get product details
$product = $productModel->getProductById($product_id);

// If product not found, redirect to products page
if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle add to cart
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity <= 0) {
        $message = 'Please enter a valid quantity.';
        $message_type = 'error';
    } elseif ($quantity > $product['stock']) {
        $message = 'Sorry, we only have ' . $product['stock'] . ' items in stock.';
        $message_type = 'error';
    } else {
        // For database integration, we'll use session ID for guest carts
        $session_id = session_id();
        
        // Initialize Cart model
        $cartModel = new Cart();
        
        // Get or create cart
        $cart = $cartModel->getCart($_SESSION['user_id'] ?? null, $session_id);
        
        if ($cart) {
            // Add item to cart
            $result = $cartModel->addItem($cart['id'], $product_id, $quantity);
            
            if ($result['success']) {
                $message = 'Product added to cart successfully!';
                $message_type = 'success';
                
                // Update cart count in session
                if (!isset($_SESSION['cart_count'])) {
                    $_SESSION['cart_count'] = 0;
                }
                $_SESSION['cart_count'] = $cartModel->getCartItemCount($cart['id']);
            } else {
                $message = $result['message'] ?? 'Error adding product to cart.';
                $message_type = 'error';
            }
        } else {
            $message = 'Error creating shopping cart.';
            $message_type = 'error';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <div class="product-details">
        <div class="product-images">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-main-image">
        </div>
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
            <div class="product-description">
                <h3>Description</h3>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
            </div>
            
            <div class="product-meta">
                <p><strong>Category:</strong> <?php echo ucfirst(htmlspecialchars($product['category'])); ?></p>
                <p><strong>Availability:</strong> 
                    <?php if ($product['stock'] > 0): ?>
                        <span class="in-stock">In Stock (<?php echo $product['stock']; ?> available)</span>
                    <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($product['stock'] > 0): ?>
                <form action="product-details.php?id=<?php echo $product_id; ?>" method="post" class="add-to-cart-form">
                    <div class="quantity">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn add-to-cart-btn">Add to Cart</button>
                </form>
            <?php else: ?>
                <button class="btn disabled-btn" disabled>Out of Stock</button>
            <?php endif; ?>
            
            <a href="products.php" class="back-to-products">← Back to Products</a>
        </div>
    </div>
</main>

<style>
    .product-details {
        display: flex;
        flex-wrap: wrap;
        gap: 40px;
        margin: 40px 0;
    }
    
    .product-images {
        flex: 1;
        min-width: 300px;
    }
    
    .product-main-image {
        width: 100%;
        height: auto;
        border-radius: 5px;
    }
    
    .product-info {
        flex: 1;
        min-width: 300px;
    }
    
    .product-info h1 {
        margin-bottom: 10px;
    }
    
    .product-price {
        font-size: 1.4rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 20px;
    }
    
    .product-description {
        margin-bottom: 20px;
    }
    
    .product-meta {
        margin-bottom: 20px;
    }
    
    .in-stock {
        color: #28a745;
    }
    
    .out-of-stock {
        color: #dc3545;
    }
    
    .add-to-cart-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .quantity {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .quantity input {
        width: 60px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .add-to-cart-btn {
        flex-grow: 1;
    }
    
    .disabled-btn {
        background-color: #6c757d;
        cursor: not-allowed;
    }
    
    .back-to-products {
        display: inline-block;
        margin-top: 20px;
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .message {
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .message.success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .message.error {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>