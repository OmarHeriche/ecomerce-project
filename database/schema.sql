-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS canceled_orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    featured BOOLEAN DEFAULT FALSE,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create order_items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Create carts table
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create cart_items table
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create canceled_orders table (for order history)
CREATE TABLE canceled_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    reason VARCHAR(255),
    canceled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- =======================================
-- STORED PROCEDURES
-- =======================================

-- 1. Procedure to display order details for a customer
DELIMITER //
CREATE PROCEDURE GetOrderDetails(IN order_id_param INT)
BEGIN
    SELECT 
        o.id AS order_id,
        o.created_at AS order_date,
        o.status,
        u.name AS customer_name,
        u.email AS customer_email,
        p.name AS product_name,
        oi.quantity,
        oi.price AS unit_price,
        (oi.quantity * oi.price) AS subtotal
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    JOIN 
        order_items oi ON o.id = oi.order_id
    JOIN 
        products p ON oi.product_id = p.id
    WHERE 
        o.id = order_id_param;
        
    -- Calculate and display total
    SELECT 
        SUM(oi.quantity * oi.price) AS total_amount
    FROM 
        order_items oi
    WHERE 
        oi.order_id = order_id_param;
END //
DELIMITER ;

-- 2. Procedure to finalize an order and empty the cart
DELIMITER //
CREATE PROCEDURE FinalizeOrder(IN cart_id_param INT, IN user_id_param INT, OUT order_id_out INT)
BEGIN
    DECLARE cart_total DECIMAL(10, 2);
    
    -- Calculate cart total
    SELECT 
        SUM(ci.quantity * p.price) INTO cart_total
    FROM 
        cart_items ci
    JOIN 
        products p ON ci.product_id = p.id
    WHERE 
        ci.cart_id = cart_id_param;
    
    -- Create new order
    INSERT INTO orders (user_id, total) 
    VALUES (user_id_param, cart_total);
    
    -- Get the order ID
    SET order_id_out = LAST_INSERT_ID();
    
    -- Transfer cart items to order items
    INSERT INTO order_items (order_id, product_id, quantity, price)
    SELECT 
        order_id_out, 
        ci.product_id, 
        ci.quantity, 
        p.price
    FROM 
        cart_items ci
    JOIN 
        products p ON ci.product_id = p.id
    WHERE 
        ci.cart_id = cart_id_param;
    
    -- Empty the cart
    DELETE FROM cart_items WHERE cart_id = cart_id_param;
END //
DELIMITER ;

-- 3. Procedure to display order history for a customer
DELIMITER //
CREATE PROCEDURE GetOrderHistory(IN user_id_param INT)
BEGIN
    SELECT 
        o.id AS order_id,
        o.created_at AS order_date,
        o.status,
        o.total,
        COUNT(oi.id) AS item_count
    FROM 
        orders o
    LEFT JOIN 
        order_items oi ON o.id = oi.order_id
    WHERE 
        o.user_id = user_id_param
    GROUP BY 
        o.id, o.created_at, o.status, o.total
    ORDER BY 
        o.created_at DESC;
END //
DELIMITER ;

-- =======================================
-- TRIGGERS
-- =======================================

-- 1. Trigger to update product stock after order validation
DELIMITER //
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- If status changed to 'processing' from 'pending', update stock
    IF NEW.status = 'processing' AND OLD.status = 'pending' THEN
        -- Update product stock
        UPDATE products p
        JOIN order_items oi ON p.id = oi.product_id
        SET p.stock = p.stock - oi.quantity
        WHERE oi.order_id = NEW.id;
    END IF;
END //
DELIMITER ;

-- 2. Trigger to prevent order insertion if quantity exceeds stock
DELIMITER //
CREATE TRIGGER before_order_item_insert
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    
    -- Get available stock
    SELECT stock INTO available_stock
    FROM products
    WHERE id = NEW.product_id;
    
    -- Check if requested quantity exceeds available stock
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insufficient stock available for this product';
    END IF;
END //
DELIMITER ;

-- 3. Trigger to restore stock after order cancellation
DELIMITER //
CREATE TRIGGER after_order_cancellation
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- If status changed to 'cancelled'
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        -- Restore product stock
        UPDATE products p
        JOIN order_items oi ON p.id = oi.product_id
        SET p.stock = p.stock + oi.quantity
        WHERE oi.order_id = NEW.id;
        
        -- Add to cancelled orders history
        INSERT INTO canceled_orders (order_id, reason)
        VALUES (NEW.id, 'Order cancelled');
    END IF;
END //
DELIMITER ;

-- 4. Trigger to log canceled orders
-- (Note: This functionality is already covered in trigger #3)

-- Insert some sample data for testing
INSERT INTO users (name, email, password, is_admin) VALUES 
('John Doe', 'user@example.com', '$2y$10$xLOU.EE0JUK00pdElKQ5xeyouCi9RSMqJ3xvZXZmfPRPoGGvxg5zy', 0), -- password: password123
('Admin User', 'admin@example.com', '$2y$10$0i9P/xtTiZUrQvXRrSbRGe9vEC0QUzYYxnzZn6lQA3aJNKn1KkHGq', 1); -- password: admin123

-- Insert product data
INSERT INTO products (name, description, price, image, category, featured, stock) VALUES
('Smartphone X Pro', 'The latest smartphone with advanced camera and long battery life.', 899.99, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=500&auto=format&fit=crop', 'smartphones', 1, 15),
('Laptop UltraBook', 'Thin and light laptop with powerful performance for professionals.', 1299.99, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?q=80&w=500&auto=format&fit=crop', 'laptops', 1, 10),
('Wireless Headphones', 'Premium noise-cancelling headphones with crystal clear sound.', 249.99, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=500&auto=format&fit=crop', 'audio', 1, 20),
('Smart Watch', 'Track your fitness and stay connected with this feature-packed smartwatch.', 199.99, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=500&auto=format&fit=crop', 'wearables', 1, 18),
('4K Smart TV', 'Ultra HD smart TV with stunning picture quality and smart features.', 799.99, 'https://images.unsplash.com/photo-1593305841991-05c297ba4575?q=80&w=500&auto=format&fit=crop', 'tvs', 0, 8),
('Wireless Earbuds', 'Compact earbuds with great sound quality and long battery life.', 129.99, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?q=80&w=500&auto=format&fit=crop', 'audio', 1, 25),
('Digital Camera', 'Professional-grade camera for stunning photos and videos.', 699.99, 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=500&auto=format&fit=crop', 'cameras', 0, 12),
('Gaming Console', 'Next-generation gaming with incredible graphics and performance.', 499.99, 'https://images.unsplash.com/photo-1607853202273-797f1c22a38e?q=80&w=500&auto=format&fit=crop', 'gaming', 1, 7); 