<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// For first iteration, we'll use hardcoded order data
// In a real application, this would come from a database
$orders = [
    [
        'id' => 1001,
        'date' => '2023-05-01',
        'status' => 'Delivered',
        'total' => 1149.98,
        'items' => [
            [
                'product_id' => 1,
                'quantity' => 1,
                'price' => 899.99
            ],
            [
                'product_id' => 6,
                'quantity' => 2,
                'price' => 129.99
            ]
        ]
    ],
    [
        'id' => 1002,
        'date' => '2023-05-15',
        'status' => 'Processing',
        'total' => 499.99,
        'items' => [
            [
                'product_id' => 8,
                'quantity' => 1,
                'price' => 499.99
            ]
        ]
    ]
];

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
                            <p>Placed on <?php echo $order['date']; ?></p>
                        </div>
                        <div class="order-status <?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </div>
                    </div>
                    
                    <div class="order-items" id="order-items-<?php echo $order['id']; ?>">
                        <!-- Order items will be loaded here by JavaScript -->
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
    // Sample orders data (would normally come from PHP)
    const orders = <?php echo json_encode($orders); ?>;
    
    // Render order items for each order
    orders.forEach(order => {
        const orderItemsContainer = document.getElementById(`order-items-${order.id}`);
        if (!orderItemsContainer) return;
        
        // Display only the first 2 items in the summary view
        const displayItems = order.items.slice(0, 2);
        
        displayItems.forEach(item => {
            const product = products.find(p => p.id === item.product_id);
            if (!product) return;
            
            const itemElement = document.createElement('div');
            itemElement.className = 'order-item';
            itemElement.innerHTML = `
                <img src="${product.image}" alt="${product.name}" class="order-item-image">
                <div class="order-item-details">
                    <h4>${product.name}</h4>
                    <p>Qty: ${item.quantity} × $${item.price.toFixed(2)}</p>
                </div>
            `;
            
            orderItemsContainer.appendChild(itemElement);
        });
        
        // Show "and X more items" if there are more than 2 items
        if (order.items.length > 2) {
            const moreElement = document.createElement('div');
            moreElement.className = 'more-items';
            moreElement.textContent = `and ${order.items.length - 2} more item(s)`;
            orderItemsContainer.appendChild(moreElement);
        }
    });
    
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
            const order = orders.find(o => o.id === orderId);
            
            if (order) {
                // Build modal content
                let modalHTML = `
                    <div class="order-info">
                        <p><strong>Order ID:</strong> #${order.id}</p>
                        <p><strong>Date:</strong> ${order.date}</p>
                        <p><strong>Status:</strong> <span class="status ${order.status.toLowerCase()}">${order.status}</span></p>
                    </div>
                    <h3>Items</h3>
                    <div class="modal-items">
                `;
                
                // Add all items to the modal
                order.items.forEach(item => {
                    const product = products.find(p => p.id === item.product_id);
                    if (!product) return;
                    
                    modalHTML += `
                        <div class="modal-item">
                            <img src="${product.image}" alt="${product.name}" class="modal-item-image">
                            <div class="modal-item-details">
                                <h4>${product.name}</h4>
                                <p>Quantity: ${item.quantity}</p>
                                <p>Price: $${item.price.toFixed(2)}</p>
                                <p>Subtotal: $${(item.price * item.quantity).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                });
                
                modalHTML += `
                    </div>
                    <div class="modal-total">
                        <p><strong>Total:</strong> $${order.total.toFixed(2)}</p>
                    </div>
                `;
                
                // Update modal content and display it
                modalContent.innerHTML = modalHTML;
                modal.style.display = 'block';
            }
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