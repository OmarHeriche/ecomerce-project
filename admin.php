<?php
session_start();
require_once 'models/Product.php';
require_once 'models/User.php';
require_once 'models/Order.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize models
$productModel = new Product();
$userModel = new User();
$orderModel = new Order();

// Get products, users, and orders from database
$products = $productModel->getAllProducts();
$users = $userModel->getAllUsers();
$orders = $orderModel->getAllOrders();

// Initialize success/error messages
$success = '';
$error = '';

// Determine which tab is active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';

// Handle processing orders
if (isset($_GET['process']) && is_numeric($_GET['process'])) {
    $order_id = (int)$_GET['process'];
    if ($orderModel->updateOrderStatus($order_id, 'shipped')) {
        $success = "Order #$order_id has been processed and marked as shipped.";
        // Refresh orders list
        $orders = $orderModel->getAllOrders();
    } else {
        $error = "Failed to process order #$order_id.";
    }
}

// Handle deleting products
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    if ($productModel->deleteProduct($product_id)) {
        $success = "Product #$product_id has been deleted.";
        // Refresh product list
        $products = $productModel->getAllProducts();
    } else {
        $error = "Failed to delete product #$product_id.";
    }
}

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    
    if (isset($_GET['process_order']) && is_numeric($_GET['process_order'])) {
        $order_id = (int)$_GET['process_order'];
        $result = $orderModel->updateOrderStatus($order_id, 'shipped');
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => "Order #$order_id has been processed and marked as shipped."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Failed to process order #$order_id."]);
        }
        exit;
    }
    
    if (isset($_GET['delete_product']) && is_numeric($_GET['delete_product'])) {
        $product_id = (int)$_GET['delete_product'];
        $result = $productModel->deleteProduct($product_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => "Product #$product_id has been deleted."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Failed to delete product #$product_id."]);
        }
        exit;
    }
}

// Handle editing products - get product data to populate the form
$editing_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $editing_product = $productModel->getProductById($product_id);
    if (!$editing_product) {
        $error = "Product #$product_id not found.";
    }
}

// Handle form submission to update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $name = $_POST['product_name'] ?? '';
    $description = $_POST['product_description'] ?? '';
    $price = $_POST['product_price'] ?? 0;
    $category = $_POST['product_category'] ?? '';
    $stock = $_POST['product_stock'] ?? 0;
    $featured = isset($_POST['product_featured']) ? 1 : 0;
    $image = $_POST['product_image'] ?? '';
    
    // Update product in database
    $result = $productModel->updateProduct($product_id, $name, $description, $price, $image, $category, $featured, $stock);
    
    if ($result) {
        $success = 'Product updated successfully!';
        // Refresh product list
        $products = $productModel->getAllProducts();
    } else {
        $error = 'Failed to update product.';
    }
}

// Handle form submission to add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'] ?? '';
    $description = $_POST['product_description'] ?? '';
    $price = $_POST['product_price'] ?? 0;
    $category = $_POST['product_category'] ?? '';
    $stock = $_POST['product_stock'] ?? 0;
    $featured = isset($_POST['product_featured']) ? 1 : 0;
    
    // Handle image URL (in a real app, we would handle file upload)
    $image = $_POST['product_image'] ?? '';
    
    // Add product to database
    $result = $productModel->addProduct($name, $description, $price, $image, $category, $featured, $stock);
    
    if ($result) {
        $success = 'Product added successfully!';
        // Refresh product list
        $products = $productModel->getAllProducts();
    } else {
        $error = 'Failed to add product.';
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <ul>
                <li class="<?php echo $active_tab === 'products' ? 'active' : ''; ?>">
                    <a href="admin.php?tab=products">Products</a>
                </li>
                <li class="<?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                    <a href="admin.php?tab=orders">Orders</a>
                </li>
                <li class="<?php echo $active_tab === 'customers' ? 'active' : ''; ?>">
                    <a href="admin.php?tab=customers">Customers</a>
                </li>
            </ul>
        </div>
        
        <div class="admin-content">
            <?php if ($active_tab === 'products'): ?>
                <!-- Products Tab -->
                <div class="admin-header">
                    <h2>Manage Products</h2>
                    <button id="add-product-btn" class="btn">Add New Product</button>
                </div>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="product-form-container" id="product-form-container" style="display: <?php echo $editing_product ? 'block' : 'none'; ?>;">
                    <h3><?php echo $editing_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                    <form action="admin.php?tab=products<?php echo $editing_product ? '&edit=' . $editing_product['id'] : ''; ?>" method="post" class="product-form">
                        <?php if ($editing_product): ?>
                            <input type="hidden" name="product_id" value="<?php echo $editing_product['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" value="<?php echo $editing_product ? htmlspecialchars($editing_product['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_description">Description</label>
                            <textarea id="product_description" name="product_description" rows="4" required><?php echo $editing_product ? htmlspecialchars($editing_product['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_price">Price ($)</label>
                                <input type="number" id="product_price" name="product_price" step="0.01" min="0" value="<?php echo $editing_product ? $editing_product['price'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_stock">Stock</label>
                                <input type="number" id="product_stock" name="product_stock" min="0" value="<?php echo $editing_product ? $editing_product['stock'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_category">Category</label>
                            <select id="product_category" name="product_category" required>
                                <option value="">Select Category</option>
                                <option value="smartphones" <?php echo ($editing_product && $editing_product['category'] == 'smartphones') ? 'selected' : ''; ?>>Smartphones</option>
                                <option value="laptops" <?php echo ($editing_product && $editing_product['category'] == 'laptops') ? 'selected' : ''; ?>>Laptops</option>
                                <option value="audio" <?php echo ($editing_product && $editing_product['category'] == 'audio') ? 'selected' : ''; ?>>Audio</option>
                                <option value="wearables" <?php echo ($editing_product && $editing_product['category'] == 'wearables') ? 'selected' : ''; ?>>Wearables</option>
                                <option value="tvs" <?php echo ($editing_product && $editing_product['category'] == 'tvs') ? 'selected' : ''; ?>>TVs</option>
                                <option value="cameras" <?php echo ($editing_product && $editing_product['category'] == 'cameras') ? 'selected' : ''; ?>>Cameras</option>
                                <option value="gaming" <?php echo ($editing_product && $editing_product['category'] == 'gaming') ? 'selected' : ''; ?>>Gaming</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_image">Product Image URL</label>
                            <input type="text" id="product_image" name="product_image" placeholder="https://example.com/image.jpg" value="<?php echo $editing_product ? htmlspecialchars($editing_product['image']) : ''; ?>" required>
                            <small>Enter a valid image URL. For example: https://images.unsplash.com/photo-1511707171634-5f897ff02aa9</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_featured">Featured Product</label>
                            <input type="checkbox" id="product_featured" name="product_featured" value="1" <?php echo ($editing_product && $editing_product['featured']) ? 'checked' : ''; ?>>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancel-product-btn" class="btn btn-secondary">Cancel</button>
                            <?php if ($editing_product): ?>
                                <button type="submit" name="update_product" class="btn">Update Product</button>
                            <?php else: ?>
                                <button type="submit" name="add_product" class="btn">Add Product</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="products-table-container">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No products found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail"></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td><?php echo $product['stock']; ?></td>
                                        <td><?php echo $product['featured'] ? 'Yes' : 'No'; ?></td>
                                        <td>
                                            <a href="admin.php?tab=products&edit=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                                            <a href="admin.php?tab=products&delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'orders'): ?>
                <!-- Orders Tab -->
                <div class="admin-header">
                    <h2>Manage Orders</h2>
                </div>
                
                <div class="orders-table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin.php?tab=orders&view=<?php echo $order['id']; ?>" class="btn-view">View</a>
                                            <a href="admin.php?tab=orders&process=<?php echo $order['id']; ?>" class="btn-process" onclick="return confirm('Process this order?')">Process</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'customers'): ?>
                <!-- Customers Tab -->
                <div class="admin-header">
                    <h2>Manage Customers</h2>
                </div>
                
                <div class="customers-table-container">
                    <table class="customers-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                                <th>Admin Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td><?php echo $user['is_admin'] ? 'Admin' : 'Customer'; ?></td>
                                        <td>
                                            <a href="admin.php?tab=customers&view=<?php echo $user['id']; ?>" class="btn-view">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle product form
    const addProductBtn = document.getElementById('add-product-btn');
    const cancelProductBtn = document.getElementById('cancel-product-btn');
    const productFormContainer = document.getElementById('product-form-container');
    
    if (addProductBtn && cancelProductBtn && productFormContainer) {
        addProductBtn.addEventListener('click', function() {
            // Clear form values when adding a new product
            const form = productFormContainer.querySelector('form');
            if (form) {
                form.reset();
                // Update form action and title for add mode
                form.action = 'admin.php?tab=products';
                productFormContainer.querySelector('h3').textContent = 'Add New Product';
                
                // Remove any hidden product_id field
                const hiddenField = form.querySelector('input[name="product_id"]');
                if (hiddenField) {
                    hiddenField.remove();
                }
                
                // Change submit button to Add Product
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.name = 'add_product';
                    submitBtn.textContent = 'Add Product';
                }
            }
            
            productFormContainer.style.display = 'block';
        });
        
        cancelProductBtn.addEventListener('click', function() {
            productFormContainer.style.display = 'none';
            // Redirect to products tab without edit parameter if we're in edit mode
            if (window.location.href.includes('&edit=')) {
                window.location.href = 'admin.php?tab=products';
            }
        });
    }
    
    // Handle edit buttons (non-AJAX)
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Let the link navigate normally to edit mode
            // The form will be pre-populated with the product data on page load
        });
    });
    
    // Handle processing orders via AJAX
    const processButtons = document.querySelectorAll('.btn-process');
    processButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Process this order?')) {
                const orderId = this.getAttribute('href').split('process=')[1];
                
                // Send AJAX request to process the order
                fetch(`admin.php?ajax=true&process_order=${orderId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the UI - change status to "Shipped"
                            const statusElement = this.closest('tr').querySelector('.order-status');
                            statusElement.className = 'order-status status-shipped';
                            statusElement.textContent = 'Shipped';
                            
                            // Show success message
                            alert(data.message);
                            
                            // Disable the Process button
                            this.style.display = 'none';
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing the order.');
                    });
            }
            
            return false;
        });
    });
    
    // Handle deleting products via AJAX
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this product?')) {
                const productId = this.getAttribute('href').split('delete=')[1];
                
                // Send AJAX request to delete the product
                fetch(`admin.php?ajax=true&delete_product=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            this.closest('tr').remove();
                            
                            // Show success message
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the product.');
                    });
            }
            
            return false;
        });
    });
});
</script>

<style>
.admin-container {
    display: flex;
    gap: 30px;
    margin: 30px 0;
}

.admin-sidebar {
    width: 200px;
    background-color: #f9f9f9;
    border-radius: 5px;
    padding: 20px;
}

.admin-sidebar ul {
    list-style: none;
    padding: 0;
}

.admin-sidebar ul li {
    margin-bottom: 10px;
}

.admin-sidebar ul li a {
    display: block;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
    text-decoration: none;
    color: #333;
}

.admin-sidebar ul li.active a,
.admin-sidebar ul li a:hover {
    background-color: #4CAF50;
    color: white;
}

.admin-content {
    flex: 1;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.product-form-container {
    background-color: #f9f9f9;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 30px;
}

.product-form .form-group {
    margin-bottom: 15px;
}

.product-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.product-form input[type="text"],
.product-form input[type="number"],
.product-form select,
.product-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.product-form small {
    display: block;
    margin-top: 5px;
    color: #666;
}

.product-form .form-row {
    display: flex;
    gap: 15px;
}

.product-form .form-row .form-group {
    flex: 1;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-secondary {
    background-color: #6c757d;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th,
table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #f2f2f2;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
}

.btn-edit,
.btn-delete,
.btn-view,
.btn-process {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    margin-right: 5px;
    display: inline-block;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-edit {
    background-color: #ffc107;
    color: #212529;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}

.btn-view {
    background-color: #17a2b8;
    color: white;
}

.btn-process {
    background-color: #28a745;
    color: white;
}

.order-status {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
}

.status-pending {
    background-color: #ffc107;
    color: #212529;
}

.status-processing {
    background-color: #17a2b8;
    color: white;
}

.status-shipped {
    background-color: #28a745;
    color: white;
}

.status-delivered {
    background-color: #4CAF50;
    color: white;
}

.status-cancelled {
    background-color: #dc3545;
    color: white;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.text-center {
    text-align: center;
}

@media (max-width: 768px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
    }
    
    .product-form .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>