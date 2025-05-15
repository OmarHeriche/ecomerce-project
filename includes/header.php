<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechShop - Electronic Ecommerce Store</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/main.js"></script>
    
    <?php
    // Check for theme cookie
    $theme = isset($_COOKIE['site_theme']) ? $_COOKIE['site_theme'] : 'light';
    ?>
    
    <style>
        /* Theme Styles */
        :root {
            --bg-color: <?php echo $theme === 'dark' ? '#1a1a1a' : '#ffffff'; ?>;
            --text-color: <?php echo $theme === 'dark' ? '#f0f0f0' : '#333333'; ?>;
            --primary-color: #4a90e2;
            --secondary-bg: <?php echo $theme === 'dark' ? '#2a2a2a' : '#f9f9f9'; ?>;
            --border-color: <?php echo $theme === 'dark' ? '#444444' : '#dddddd'; ?>;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .product-card, .cart-table, .auth-container, .checkout-container, .order-summary, 
        .admin-content, .order-card, .modal-content {
            background-color: var(--secondary-bg);
            border-color: var(--border-color);
        }
        
        header, footer {
            background-color: var(--secondary-bg);
            border-color: var(--border-color);
        }
        
        a:not(.btn) {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">TechShop</a>
                </div>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="order-history.php">Orders</a></li>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <li><a href="admin.php">Admin</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                        <li>
                            <a href="cart.php" class="cart-icon">
                                Cart
                                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <button id="theme-toggle" class="theme-toggle">
                                <?php echo $theme === 'dark' ? '☀️' : '🌙'; ?>
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    // Get current theme
                    const currentTheme = '<?php echo $theme; ?>';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    // Set cookie for theme
                    document.cookie = `site_theme=${newTheme};path=/;max-age=31536000`; // 1 year
                    
                    // Reload page to apply new theme
                    window.location.reload();
                });
            }
        });
    </script>
    
    <style>
        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            margin-left: 10px;
        }
    </style>