<?php
session_start();

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    // Redirect to cart page
    header('Location: cart.php');
    exit;
}

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Handle checkout submission
$order_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // In a real app, we would process the order, save to database, etc.
    // For now, just simulate order completion
    
    // Clear the cart
    $_SESSION['cart'] = [];
    
    // Set success flag
    $order_success = true;
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <?php if ($order_success): ?>
        <div class="order-success">
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been received and is being processed.</p>
            <a href="products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <h1>Checkout</h1>
        
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
                <div id="checkout-items">
                    <!-- Order items will be loaded here by JavaScript -->
                </div>
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span id="checkout-subtotal"></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span id="checkout-shipping">$5.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span id="checkout-total"></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('checkout-items')) return;
    
    // Get cart from session (This would normally come from the PHP session, but for now we'll use our client-side data)
    const cartItems = <?php echo json_encode($_SESSION['cart']); ?>;
    
    if (cartItems.length > 0) {
        const checkoutItems = document.getElementById('checkout-items');
        let subtotal = 0;
        
        cartItems.forEach(item => {
            // Find product details
            const product = products.find(p => p.id === item.product_id);
            
            if (product) {
                const total = product.price * item.quantity;
                subtotal += total;
                
                const itemElement = document.createElement('div');
                itemElement.className = 'checkout-item';
                itemElement.innerHTML = `
                    <div class="item-details">
                        <h3>${product.name}</h3>
                        <p>Quantity: ${item.quantity}</p>
                    </div>
                    <div class="item-price">$${total.toFixed(2)}</div>
                `;
                
                checkoutItems.appendChild(itemElement);
            }
        });
        
        // Update totals
        const shipping = 5.00;
        const total = subtotal + shipping;
        
        document.getElementById('checkout-subtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;
    }
});
</script>

<style>
.checkout-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin: 30px 0;
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
    color: #4CAF50;
    margin-bottom: 20px;
}

.order-success p {
    font-size: 1.2rem;
    margin-bottom: 30px;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>