<?php
session_start();
require_once 'models/Cart.php';
require_once 'models/Product.php';
require_once 'models/Order.php';

// Initialize models
$cartModel = new Cart();
$orderModel = new Order();

// Get or create cart
$session_id = session_id();
$cart = $cartModel->getCart($_SESSION['user_id'] ?? null, $session_id);

if (!$cart) {
    // If no cart exists, create a new one
    $cart = $cartModel->getCart($_SESSION['user_id'] ?? null, $session_id);
}

$cart_id = $cart['id'] ?? 0;
$cart_items = $cart_id ? $cartModel->getCartItems($cart_id) : [];
$cart_total = $cart_id ? $cartModel->getCartTotal($cart_id) : 0;

// Check if cart is empty
if (empty($cart_items)) {
    // Redirect to cart page
    header('Location: cart.php');
    exit;
}

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 0;

// Handle checkout submission
$order_success = false;
$order_id = 0;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if ($logged_in) {
        // Process the order through database
        $result = $cartModel->checkout($cart_id, $user_id);
        
        if ($result['success']) {
            $order_id = $result['order_id'];
            $order_success = true;
            
            // Update cart count in session
            $_SESSION['cart_count'] = 0;
        } else {
            $error_message = $result['message'] ?? 'Unable to process your order. Please try again.';
            error_log("Checkout failed: " . $error_message);
        }
    } else {
        // For guest checkout, just clear the cart
        $cartModel->clearCart($cart_id);
        $order_success = true;
        $_SESSION['cart_count'] = 0;
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <?php if ($order_success): ?>
        <div class="order-success">
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been received and is being processed.</p>
            <?php if ($order_id): ?>
                <p>Your order ID is: <strong>#<?php echo $order_id; ?></strong></p>
            <?php endif; ?>
            <a href="products.php" class="btn">Continue Shopping</a>
            <?php if ($logged_in): ?>
                <a href="order-history.php" class="btn">View Your Orders</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h1>Checkout</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$logged_in): ?>
            <div class="checkout-login-message">
                <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to complete your purchase, or checkout as a guest below.</p>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form-container">
                <h2>Shipping Information</h2>
                <form action="checkout.php" method="post" id="checkout-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" required>
                    </div>
                    
                    <h2>Payment Information</h2>
                    
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="checkout-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="checkout-item">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>$5.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($cart_total + 5.00, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<style>
.checkout-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin: 30px 0;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.checkout-form-container {
    flex: 2;
    min-width: 300px;
}

.order-summary {
    flex: 1;
    min-width: 300px;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.form-group {
    margin-bottom: 15px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

input[type="text"],
input[type="email"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

h2 {
    margin: 20px 0;
    font-size: 1.5rem;
}

.checkout-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
}

.grand-total {
    font-weight: bold;
    font-size: 1.2rem;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.checkout-login-message {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.order-success {
    text-align: center;
    padding: 50px 0;
}

.order-success h1 {
    color: #28a745;
    margin-bottom: 20px;
}

.order-success p {
    font-size: 1.2rem;
    margin-bottom: 30px;
}

.order-success .btn {
    margin: 0 10px;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>