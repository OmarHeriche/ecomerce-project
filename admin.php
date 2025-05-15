<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize success/error messages
$success = '';
$error = '';

// Handle form submission to add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, we would validate and save to database
    // For now, just show a success message
    $success = 'Product added successfully!';
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <ul>
                <li class="active"><a href="#products">Products</a></li>
                <li><a href="#orders">Orders</a></li>
                <li><a href="#customers">Customers</a></li>
                <li><a href="#settings">Settings</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
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
                <form action="admin.php" method="post" enctype="multipart/form-data" class="product-form">
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
                        <label for="product_image">Product Image</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_featured">Featured Product</label>
                        <input type="checkbox" id="product_featured" name="product_featured" value="1">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-product-btn" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn">Add Product</button>
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
                    <tbody id="products-table-body">
                        <!-- Products will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle product form
    const addProductBtn = document.getElementById('add-product-btn');
    const cancelProductBtn = document.getElementById('cancel-product-btn');
    const productFormContainer = document.getElementById('product-form-container');
    
    addProductBtn.addEventListener('click', function() {
        productFormContainer.style.display = 'block';
    });
    
    cancelProductBtn.addEventListener('click', function() {
        productFormContainer.style.display = 'none';
    });
    
    // Display products in table
    const productsTableBody = document.getElementById('products-table-body');
    
    products.forEach(product => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${product.id}</td>
            <td><img src="${product.image}" alt="${product.name}" class="product-thumbnail"></td>
            <td>${product.name}</td>
            <td>$${product.price.toFixed(2)}</td>
            <td>${product.category}</td>
            <td>${product.stock}</td>
            <td>${product.featured ? 'Yes' : 'No'}</td>
            <td>
                <button class="btn-edit" data-id="${product.id}">Edit</button>
                <button class="btn-delete" data-id="${product.id}">Delete</button>
            </td>
        `;
        
        productsTableBody.appendChild(tr);
    });
    
    // Add event listeners for edit and delete buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            alert(`Edit product with ID: ${productId}`);
            // In a real app, we would load the product data and show the edit form
        });
    });
    
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const confirmed = confirm('Are you sure you want to delete this product?');
            
            if (confirmed) {
                alert(`Delete product with ID: ${productId}`);
                // In a real app, we would send a request to delete the product
            }
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

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th,
.products-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.products-table th {
    background-color: #f2f2f2;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
}

.btn-edit,
.btn-delete {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-edit {
    background-color: #ffc107;
    color: #212529;
}

.btn-delete {
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

@media (max-width: 768px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>