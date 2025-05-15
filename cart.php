<?php
session_start();
require_once 'models/Cart.php';
require_once 'models/Product.php';

// Initialize Cart model
$cartModel = new Cart();

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

// Handle removing item from cart
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $cart_item_id = (int)$_GET['remove'];
    
    // Remove item from database cart
    $cartModel->removeItem($cart_item_id);
    
    // Redirect back to cart page
    header('Location: cart.php');
    exit;
}

// Handle updating cart quantities
if (isset($_POST['update_cart']) && isset($_POST['quantity']) && isset($_POST['item_id'])) {
    foreach ($_POST['quantity'] as $index => $quantity) {
        $cart_item_id = (int)$_POST['item_id'][$index];
        $quantity = (int)$quantity;
        
        // Update quantity or remove if zero
        if ($quantity <= 0) {
            $cartModel->removeItem($cart_item_id);
        } else {
            $cartModel->updateItemQuantity($cart_item_id, $quantity);
        }
    }
    
    // Redirect back to cart page
    header('Location: cart.php');
    exit;
}

// Update cart count in session
if ($cart_id) {
    $_SESSION['cart_count'] = $cartModel->getCartItemCount($cart_id);
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Your Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form action="cart.php" method="post" id="cart-form">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="product-info">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-image">
                                <div>
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                </div>
                            </td>
                            <td class="product-price">$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="product-quantity">
                                <input type="number" name="quantity[]" min="0" value="<?php echo $item['quantity']; ?>">
                                <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                            </td>
                            <td class="product-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td class="product-remove">
                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-item">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                        <td>$<?php echo number_format($cart_total, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="cart-actions">
                <a href="products.php" class="btn">Continue Shopping</a>
                <button type="submit" name="update_cart" class="btn">Update Cart</button>
                <a href="checkout.php" class="btn">Proceed to Checkout</a>
            </div>
        </form>
    <?php endif; ?>
</main>

<style>
.cart-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.cart-table th,
.cart-table td {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.cart-product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    margin-right: 15px;
}

.product-info {
    display: flex;
    align-items: center;
}

.product-quantity input {
    width: 60px;
    padding: 5px;
}

.text-right {
    text-align: right;
}

.cart-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.empty-cart {
    text-align: center;
    padding: 50px 0;
}

.empty-cart p {
    font-size: 1.2rem;
    margin-bottom: 20px;
}

.remove-item {
    color: #dc3545;
    text-decoration: underline;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>