<?php
session_start();
require_once 'models/Order.php';
require_once 'models/Product.php';
require_once 'database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Initialize database and models
$db = Database::getInstance();
$orderModel = new Order();
$productModel = new Product();

// Get order history directly with a simple query
try {
    $conn = $db->getConnection();
    $query = "SELECT id, created_at, status, total, user_id 
              FROM orders 
              WHERE user_id = ? 
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error loading order history: " . $e->getMessage());
    $orders = [];
}

// Get product details for all products to use in JavaScript
try {
    $allProducts = $productModel->getAllProducts();
    $productsForJS = [];
    foreach ($allProducts as $product) {
        $productsForJS[$product['id']] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $product['price']
        ];
    }
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
    $productsForJS = [];
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Order History</h1>
    
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-container">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p>Placed on <?php echo date('Y-m-d', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="order-status <?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    
                    <div class="order-items" id="order-items-<?php echo $order['id']; ?>">
                        <!-- Order items loaded directly with a simple query -->
                        <?php 
                        try {
                            // Get order items directly
                            $query = "SELECT oi.*, p.name, p.image 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $order['id']);
                            $stmt->execute();
                            $itemsResult = $stmt->get_result();
                            
                            $items = [];
                            if ($itemsResult) {
                                while ($row = $itemsResult->fetch_assoc()) {
                                    $items[] = $row;
                                }
                            }
                            
                            if (!empty($items)) {
                                // Display up to 2 items in the summary
                                $displayItems = array_slice($items, 0, 2);
                                foreach ($displayItems as $item) {
                                    ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-image">
                                        <div class="order-item-details">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <p>Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                    </div>
                                    <?php
                                }
                                
                                // Show "and X more items" if there are more than 2 items
                                if (count($items) > 2) {
                                    echo '<div class="more-items">and ' . (count($items) - 2) . ' more item(s)</div>';
                                }
                            } else {
                                echo '<div>No items found for this order</div>';
                            }
                        } catch (Exception $e) {
                            error_log("Error loading order details for order #" . $order['id'] . ": " . $e->getMessage());
                            echo '<div class="error-message">Could not load order items</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                        <a href="#" class="btn view-details" data-order-id="<?php echo $order['id']; ?>">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Order Details Modal -->
    <div id="order-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Order Details</h2>
            <div id="modal-order-details"></div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get products data
    const products = <?php echo json_encode($productsForJS); ?>;
    
    // Modal functionality
    const modal = document.getElementById('order-modal');
    const modalContent = document.getElementById('modal-order-details');
    const closeModal = document.querySelector('.close-modal');
    
    // Close modal when clicking the X
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Open modal with order details
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const orderId = parseInt(this.dataset.orderId);
            
            // Fetch order details via AJAX
            fetch(`get_order_details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;
                        const items = data.items;
                        
                        // Build modal content
                        let modalHTML = `
                            <div class="order-info">
                                <p><strong>Order ID:</strong> #${order.id}</p>
                                <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                                <p><strong>Status:</strong> <span class="status ${order.status.toLowerCase()}">${order.status}</span></p>
                            </div>
                            <h3>Items</h3>
                            <div class="modal-items">
                        `;
                        
                        // Add all items to the modal
                        items.forEach(item => {
                            const product = products[item.product_id];
                            if (!product) return;
                            
                            modalHTML += `
                                <div class="modal-item">
                                    <img src="${item.image || product.image}" alt="${item.name || product.name}" class="modal-item-image">
                                    <div class="modal-item-details">
                                        <h4>${item.name || product.name}</h4>
                                        <p>Quantity: ${item.quantity}</p>
                                        <p>Price: $${parseFloat(item.price).toFixed(2)}</p>
                                        <p>Subtotal: $${(parseFloat(item.price) * item.quantity).toFixed(2)}</p>
                                    </div>
                                </div>
                            `;
                        });
                        
                        modalHTML += `
                            </div>
                            <div class="modal-total">
                                <p><strong>Total:</strong> $${parseFloat(order.total).toFixed(2)}</p>
                            </div>
                        `;
                        
                        // Update modal content and display it
                        modalContent.innerHTML = modalHTML;
                        modal.style.display = 'block';
                    } else {
                        alert('Could not load order details.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    alert('Error loading order details.');
                });
        });
    });
});
</script>

<style>
.orders-container {
    margin: 30px 0;
}

.order-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.order-status {
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    font-size: 0.9em;
}

.order-status.delivered {
    background-color: #d4edda;
    color: #155724;
}

.order-status.processing {
    background-color: #fff3cd;
    color: #856404;
}

.order-status.shipped {
    background-color: #cce5ff;
    color: #004085;
}

.order-status.cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.order-items {
    padding: 15px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.more-items {
    color: #666;
    font-style: italic;
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #f9f9f9;
    border-top: 1px solid #ddd;
}

.order-total {
    font-weight: bold;
}

.empty-orders {
    text-align: center;
    padding: 50px 0;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.9em;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 50px auto;
    padding: 20px;
    border-radius: 5px;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.close-modal {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #999;
}

.modal-items {
    margin: 20px 0;
}

.modal-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.modal-item:last-child {
    border-bottom: none;
}

.modal-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
}

.modal-total {
    text-align: right;
    font-size: 1.2em;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 