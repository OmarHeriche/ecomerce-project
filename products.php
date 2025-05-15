<?php
session_start();

// Include header
include 'includes/header.php';
?>

<main class="container">
    <div class="products-header">
        <h1>All Products</h1>
        <div class="filters">
            <div class="filter-group">
                <label for="category-filter">Category:</label>
                <select id="category-filter">
                    <option value="all">All Categories</option>
                    <option value="smartphones">Smartphones</option>
                    <option value="laptops">Laptops</option>
                    <option value="audio">Audio</option>
                    <option value="wearables">Wearables</option>
                    <option value="tvs">TVs</option>
                    <option value="cameras">Cameras</option>
                    <option value="gaming">Gaming</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort-filter">Sort By:</label>
                <select id="sort-filter">
                    <option value="default">Default</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="name-asc">Name: A to Z</option>
                    <option value="name-desc">Name: Z to A</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" id="search-filter" placeholder="Search products...">
                <button id="search-btn">Search</button>
            </div>
        </div>
    </div>
    
    <div class="products-grid" id="products-container">
        <!-- Products will be loaded here by JavaScript -->
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Display all products
        const productsContainer = document.getElementById('products-container');
        
        // Render all products initially
        renderProducts(products);
        
        // Event listeners for filters
        document.getElementById('category-filter').addEventListener('change', applyFilters);
        document.getElementById('sort-filter').addEventListener('change', applyFilters);
        document.getElementById('search-btn').addEventListener('click', applyFilters);
        document.getElementById('search-filter').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        // Function to render products
        function renderProducts(productsToRender) {
            productsContainer.innerHTML = '';
            
            if (productsToRender.length === 0) {
                productsContainer.innerHTML = '<p class="no-products">No products found matching your criteria</p>';
                return;
            }
            
            productsToRender.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';
                
                productCard.innerHTML = `
                    <img src="${product.image}" alt="${product.name}" class="product-image">
                    <div class="product-info">
                        <h3 class="product-title">${product.name}</h3>
                        <p class="product-price">$${product.price.toFixed(2)}</p>
                        <a href="product-details.php?id=${product.id}" class="btn">View Details</a>
                    </div>
                `;
                
                productsContainer.appendChild(productCard);
            });
        }
        
        // Function to apply filters
        function applyFilters() {
            const category = document.getElementById('category-filter').value;
            const sort = document.getElementById('sort-filter').value;
            const search = document.getElementById('search-filter').value.toLowerCase();
            
            // Filter products
            let filteredProducts = [...products];
            
            // Apply category filter
            if (category !== 'all') {
                filteredProducts = filteredProducts.filter(product => product.category === category);
            }
            
            // Apply search filter
            if (search) {
                filteredProducts = filteredProducts.filter(product => 
                    product.name.toLowerCase().includes(search) || 
                    product.description.toLowerCase().includes(search)
                );
            }
            
            // Apply sort
            switch(sort) {
                case 'price-low':
                    filteredProducts.sort((a, b) => a.price - b.price);
                    break;
                case 'price-high':
                    filteredProducts.sort((a, b) => b.price - a.price);
                    break;
                case 'name-asc':
                    filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'name-desc':
                    filteredProducts.sort((a, b) => b.name.localeCompare(a.name));
                    break;
            }
            
            // Render filtered products
            renderProducts(filteredProducts);
        }
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>