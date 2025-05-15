<?php
session_start();

// Include header
include 'includes/header.php';

// In a real app, we would fetch the product from the database
// For now, we'll use JavaScript to get the product details
?>

<main class="container">
    <div class="product-details" id="product-details-container">
        <!-- Product details will be loaded here by JavaScript -->
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get product ID from URL query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const productId = parseInt(urlParams.get('id'));
        
        if (isNaN(productId)) {
            // If no valid product ID, redirect to products page
            window.location.href = 'products.php';
            return;
        }
        
        // Find the product in our array
        const product = products.find(p => p.id === productId);
        
        if (!product) {
            // If product not found, show error
            document.getElementById('product-details-container').innerHTML = `
                <div class="error-message">
                    <h2>Product Not Found</h2>
                    <p>The product you're looking for does not exist.</p>
                    <a href="products.php" class="btn">Back to Products</a>
                </div>
            `;
            return;
        }
        
        // Display product details
        document.getElementById('product-details-container').innerHTML = `
            <div class="product-details-layout">
                <div class="product-image-container">
                    <img src="${product.image}" alt="${product.name}" class="product-detail-image">
                </div>
                <div class="product-info-container">
                    <h1 class="product-title">${product.name}</h1>
                    <p class="product-price">$${product.price.toFixed(2)}</p>
                    <div class="product-description">
                        <h3>Description</h3>
                        <p>${product.description}</p>
                    </div>
                    <div class="product-stock">
                        <p>${product.stock > 0 ? `In Stock (${product.stock} available)` : 'Out of Stock'}</p>
                    </div>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label for="quantity-${product.id}">Quantity:</label>
                            <input type="number" id="quantity-${product.id}" min="1" max="${product.stock}" value="1">
                        </div>
                        <button class="btn add-to-cart" data-product-id="${product.id}" ${product.stock <= 0 ? 'disabled' : ''}>
                            ${product.stock > 0 ? 'Add to Cart' : 'Out of Stock'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
</script>

<style>
    .product-details-layout {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin: 40px 0;
    }
    
    .product-image-container {
        flex: 1;
        min-width: 300px;
    }
    
    .product-detail-image {
        width: 100%;
        max-height: 500px;
        object-fit: contain;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .product-info-container {
        flex: 1;
        min-width: 300px;
    }
    
    .product-title {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .product-price {
        font-size: 1.5rem;
        color: #4CAF50;
        margin-bottom: 20px;
    }
    
    .product-description {
        margin-bottom: 20px;
    }
    
    .product-stock {
        margin-bottom: 20px;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .quantity-selector label {
        margin-right: 10px;
    }
    
    .quantity-selector input {
        width: 60px;
        padding: 5px;
    }
    
    .error-message {
        text-align: center;
        padding: 50px 0;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>