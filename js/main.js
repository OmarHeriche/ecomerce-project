// Sample product data (for first iteration without database)
const products = [
    {
        id: 1,
        name: "Smartphone X Pro",
        description: "The latest smartphone with advanced camera and long battery life.",
        price: 899.99,
        image: "images/products/smartphone.jpg",
        category: "smartphones",
        featured: true,
        stock: 15
    },
    {
        id: 2,
        name: "Laptop UltraBook",
        description: "Thin and light laptop with powerful performance for professionals.",
        price: 1299.99,
        image: "images/products/laptop.jpg",
        category: "laptops",
        featured: true,
        stock: 10
    },
    {
        id: 3,
        name: "Wireless Headphones",
        description: "Premium noise-cancelling headphones with crystal clear sound.",
        price: 249.99,
        image: "images/products/headphones.jpg",
        category: "audio",
        featured: true,
        stock: 20
    },
    {
        id: 4,
        name: "Smart Watch",
        description: "Track your fitness and stay connected with this feature-packed smartwatch.",
        price: 199.99,
        image: "images/products/smartwatch.jpg",
        category: "wearables",
        featured: true,
        stock: 18
    },
    {
        id: 5,
        name: "4K Smart TV",
        description: "Ultra HD smart TV with stunning picture quality and smart features.",
        price: 799.99,
        image: "images/products/tv.jpg",
        category: "tvs",
        featured: false,
        stock: 8
    },
    {
        id: 6,
        name: "Wireless Earbuds",
        description: "Compact earbuds with great sound quality and long battery life.",
        price: 129.99,
        image: "images/products/earbuds.jpg",
        category: "audio",
        featured: true,
        stock: 25
    },
    {
        id: 7,
        name: "Digital Camera",
        description: "Professional-grade camera for stunning photos and videos.",
        price: 699.99,
        image: "images/products/camera.jpg",
        category: "cameras",
        featured: false,
        stock: 12
    },
    {
        id: 8,
        name: "Gaming Console",
        description: "Next-generation gaming with incredible graphics and performance.",
        price: 499.99,
        image: "images/products/console.jpg",
        category: "gaming",
        featured: true,
        stock: 7
    }
];

// Function to display featured products on the home page
function displayFeaturedProducts() {
    const featuredContainer = document.getElementById('featured-products-container');
    if (!featuredContainer) return;
    
    const featuredProducts = products.filter(product => product.featured);
    
    featuredProducts.forEach(product => {
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
        
        featuredContainer.appendChild(productCard);
    });
}

// Function to add products to cart
function addToCart(productId, quantity = 1) {
    fetch('add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in the header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
            
            // Show success message
            alert('Product added to cart!');
        } else {
            alert(data.message || 'Error adding product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding to cart');
    });
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Display featured products on home page
    displayFeaturedProducts();
    
    // Add event listeners for add to cart buttons
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart')) {
            e.preventDefault();
            const productId = e.target.dataset.productId;
            const quantity = document.querySelector(`#quantity-${productId}`)?.value || 1;
            addToCart(productId, quantity);
        }
    });
});