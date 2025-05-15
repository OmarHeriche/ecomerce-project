<?php
session_start();
require_once 'models/Product.php';
require_once 'models/User.php';
require_once 'database/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize models
$productModel = new Product();
$userModel = new User();
$db = Database::getInstance();

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove all sample products
    if (isset($_POST['remove_sample_products'])) {
        try {
            $conn = $db->getConnection();
            $conn->query("DELETE FROM products WHERE id IN (1, 2, 3, 4, 5, 6, 7, 8)");
            $message = "Sample products have been removed successfully.";
        } catch (Exception $e) {
            $error = "Error removing sample products: " . $e->getMessage();
        }
    }
    
    // Clean all orders
    if (isset($_POST['clean_orders'])) {
        try {
            $conn = $db->getConnection();
            
            // Start transaction
            $conn->begin_transaction();
            
            // First delete from canceled_orders to avoid foreign key constraints
            $conn->query("DELETE FROM canceled_orders");
            
            // Then delete from order_items
            $conn->query("DELETE FROM order_items");
            
            // Then delete from orders
            $conn->query("DELETE FROM orders");
            
            // Commit transaction
            $conn->commit();
            
            $message = "All orders have been removed successfully.";
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $error = "Error removing orders: " . $e->getMessage();
        }
    }
    
    // Add new product
    if (isset($_POST['add_product'])) {
        $name = $_POST['product_name'] ?? '';
        $description = $_POST['product_description'] ?? '';
        $price = $_POST['product_price'] ?? 0;
        $image = $_POST['product_image'] ?? '';
        $category = $_POST['product_category'] ?? '';
        $stock = $_POST['product_stock'] ?? 0;
        $featured = isset($_POST['product_featured']) ? 1 : 0;
        
        if (empty($name) || empty($description) || empty($price) || empty($image) || empty($category) || empty($stock)) {
            $error = "All fields are required.";
        } else {
            $result = $productModel->addProduct($name, $description, $price, $image, $category, $featured, $stock);
            
            if ($result) {
                $message = "Product added successfully!";
            } else {
                $error = "Failed to add product.";
            }
        }
    }
}

// Get current products
$products = $productModel->getAllProducts();

// Get current orders
$conn = $db->getConnection();
$result = $conn->query("SELECT o.*, u.name as customer_name 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC");
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Data Management</h1>
    
    <?php if (!empty($message)): ?>
        <div class="success-message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <ul>
                <li><a href="admin.php?tab=products">Back to Admin</a></li>
                <li class="active"><a href="admin_cleanup.php">Data Management</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="data-management-section">
                <h2>Manage Sample Data</h2>
                <div class="warning-message">
                    <p><strong>Warning:</strong> The actions on this page are irreversible. Make sure you understand what you're doing.</p>
                </div>
                
                <form action="admin_cleanup.php" method="post" onsubmit="return confirm('Are you sure you want to remove all sample products? This action cannot be undone.');">
                    <button type="submit" name="remove_sample_products" class="btn btn-danger">Remove Sample Products</button>
                </form>
            </div>
            
            <div class="data-management-section">
                <h2>Manage Orders</h2>
                <div class="warning-message">
                    <p><strong>Warning:</strong> This will remove ALL orders from the system. This action cannot be undone.</p>
                </div>
                
                <form action="admin_cleanup.php" method="post" onsubmit="return confirm('Are you sure you want to delete ALL orders? This action cannot be undone.');">
                    <button type="submit" name="clean_orders" class="btn btn-danger">Delete All Orders</button>
                </form>
                
                <h3>Current Orders (<?php echo count($orders); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="data-management-section">
                <h2>Quick Add Product</h2>
                <form action="admin_cleanup.php" method="post" class="product-form">
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
                    
                    <button type="submit" name="add_product" class="btn">Add Product</button>
                </form>
            </div>
            
            <div class="data-management-section">
                <h2>Current Products (<?php echo count($products); ?>)</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No products found</td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

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

.data-management-section {
    margin-bottom: 40px;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
}

.data-management-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.warning-message {
    background-color: #fff3cd;
    color: #856404;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.data-table th {
    background-color: #f2f2f2;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
}

.btn-danger {
    background-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
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

.product-form {
    margin-top: 20px;
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

.status-badge {
    display: inline-block;
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
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 