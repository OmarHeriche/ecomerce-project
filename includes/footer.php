    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>About TechShop</h3>
                    <p>TechShop is your one-stop destination for all electronics and gadgets. We provide high-quality products at competitive prices.</p>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="order-history.php">Order History</a></li>
                            <li><a href="cart.php">Shopping Cart</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Us</h3>
                    <p>123 Tech Street, Digital City</p>
                    <p>+1 234 567 890</p>
                    <p>info@techshop.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> TechShop. All rights reserved.</p>
                <p>Created by Omar - Computer Science Student Project</p>
            </div>
        </div>
    </footer>
</body>
</html>