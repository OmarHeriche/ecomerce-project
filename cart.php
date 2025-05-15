<?php
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle removing item from cart
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    
    // Filter out the product to be removed
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] !== $product_id;
    });
    
    // Redirect back to cart page
    header('Location: cart.php');
    exit;
}

// Handle updating cart quantities
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or negative
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
                return $item['product_id'] !== $product_id;
            });
        } else {
            // Update quantity
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $product_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
    }
    
    // Redirect back to cart page
    header('Location: cart.php');
    exit;
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Your Shopping Cart</h1>
    
    <?php if (empty($_SESSION['cart'])): ?>
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
                <tbody id="cart-items">
                    <!-- Cart items will be loaded here by JavaScript -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                        <td id="cart-subtotal"></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get cart from session (This would normally come from the PHP session, but for now we'll use our client-side data)
    const cartItems = <?php echo json_encode($_SESSION['cart']); ?>;
    
    if (cartItems.length > 0) {
        const cartTableBody = document.getElementById('cart-items');
        let subtotal = 0;
        
        cartItems.forEach(item => {
            // Find product details
            const product = products.find(p => p.id === item.product_id);
            
            if (product) {
                const total = product.price * item.quantity;
                subtotal += total;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="product-info">
                        <img src="${product.image}" alt="${product.name}" class="cart-product-image">
                        <div>
                            <h3>${product.name}</h3>
                        </div>
                    </td>
                    <td class="product-price">$${product.price.toFixed(2)}</td>
                    <td class="product-quantity">
                        <input type="number" name="quantity[${product.id}]" min="1" value="${item.quantity}">
                    </td>
                    <td class="product-total">$${total.toFixed(2)}</td>
                    <td class="product-remove">
                        <a href="cart.php?remove=${product.id}" class="remove-item">Remove</a>
                    </td>
                `;
                
                cartTableBody.appendChild(tr);
            }
        });
        
        // Update subtotal
        document.getElementById('cart-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    }
});
</script>

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