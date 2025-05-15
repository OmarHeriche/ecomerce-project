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
                
                <div class="product-form-container" id="product-form-container" style="display: none;">
                    <h3>Add New Product</h3>
                    <form action="admin.php?tab=products" method="post" class="product-form">
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_description">Description</label>
                            <textarea id="product_description" name="product_description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_price">Price ($)</label>
                                <input type="number" id="product_price" name="product_price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_stock">Stock</label>
                                <input type="number" id="product_stock" name="product_stock" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_category">Category</label>
                            <select id="product_category" name="product_category" required>
                                <option value="">Select Category</option>
                                <option value="smartphones">Smartphones</option>
                                <option value="laptops">Laptops</option>
                                <option value="audio">Audio</option>
                                <option value="wearables">Wearables</option>
                                <option value="tvs">TVs</option>
                                <option value="cameras">Cameras</option>
                                <option value="gaming">Gaming</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_image">Product Image URL</label>
                            <input type="text" id="product_image" name="product_image" placeholder="https://example.com/image.jpg" required>
                            <small>Enter a valid image URL. For example: https://images.unsplash.com/photo-1511707171634-5f897ff02aa9</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_featured">Featured Product</label>
                            <input type="checkbox" id="product_featured" name="product_featured" value="1">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancel-product-btn" class="btn btn-secondary">Cancel</button>
                            <button type="submit" name="add_product" class="btn">Add Product</button>
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
            productFormContainer.style.display = 'block';
        });
        
        cancelProductBtn.addEventListener('click', function() {
            productFormContainer.style.display = 'none';
        });
    }
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