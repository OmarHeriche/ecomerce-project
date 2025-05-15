<?php
require_once 'database/db.php';
require_once 'models/Product.php';

class Cart {
    private $db;
    private $product;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->product = new Product();
    }
    
    /**
     * Get or create a cart for a user
     */
    public function getCart($user_id = null, $session_id = null) {
        // Try to find an existing cart
        if ($user_id) {
            $query = "SELECT * FROM carts WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else if ($session_id) {
            $query = "SELECT * FROM carts WHERE session_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $session_id);
        } else {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If cart exists, return it
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Create a new cart
        if ($user_id) {
            $query = "INSERT INTO carts (user_id) VALUES (?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else {
            $query = "INSERT INTO carts (session_id) VALUES (?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $session_id);
        }
        
        if ($stmt->execute()) {
            $cart_id = $this->db->lastInsertId();
            
            // Get the cart data
            $query = "SELECT * FROM carts WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $cart_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        
        return null;
    }
    
    /**
     * Link a guest cart to a user after login
     */
    public function linkCartToUser($session_id, $user_id) {
        $query = "UPDATE carts SET user_id = ?, session_id = NULL WHERE session_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("is", $user_id, $session_id);
        
        return $stmt->execute();
    }
    
    /**
     * Add item to cart
     */
    public function addItem($cart_id, $product_id, $quantity = 1) {
        // Check if product exists and is in stock
        if (!$this->product->isInStock($product_id, $quantity)) {
            return ['success' => false, 'message' => 'Product is out of stock'];
        }
        
        // Check if item already exists in cart
        $query = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $cart_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            // Update quantity
            $cart_item = $result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Check stock again with new quantity
            if (!$this->product->isInStock($product_id, $new_quantity)) {
                return ['success' => false, 'message' => 'Not enough stock available'];
            }
            
            $query = "UPDATE cart_items SET quantity = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'cart_item_id' => $cart_item['id']];
            }
        } else {
            // Insert new item
            $query = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("iii", $cart_id, $product_id, $quantity);
            
            if ($stmt->execute()) {
                return ['success' => true, 'cart_item_id' => $this->db->lastInsertId()];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to add item to cart'];
    }
    
    /**
     * Get all items in a cart with product details
     */
    public function getCartItems($cart_id) {
        $query = "SELECT ci.*, p.name, p.price, p.image, p.stock 
                 FROM cart_items ci 
                 JOIN products p ON ci.product_id = p.id 
                 WHERE ci.cart_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
    
    /**
     * Update cart item quantity
     */
    public function updateItemQuantity($cart_item_id, $quantity) {
        // Get cart item
        $query = "SELECT ci.*, p.stock 
                 FROM cart_items ci 
                 JOIN products p ON ci.product_id = p.id 
                 WHERE ci.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $item = $result->fetch_assoc();
            
            // Check stock
            if ($quantity > $item['stock']) {
                return ['success' => false, 'message' => 'Not enough stock available'];
            }
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                return $this->removeItem($cart_item_id);
            } else {
                // Update quantity
                $query = "UPDATE cart_items SET quantity = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ii", $quantity, $cart_item_id);
                
                if ($stmt->execute()) {
                    return ['success' => true];
                }
            }
        }
        
        return ['success' => false, 'message' => 'Failed to update item quantity'];
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($cart_item_id) {
        $query = "DELETE FROM cart_items WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_item_id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to remove item from cart'];
    }
    
    /**
     * Clear cart
     */
    public function clearCart($cart_id) {
        $query = "DELETE FROM cart_items WHERE cart_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get cart count
     */
    public function getCartItemCount($cart_id) {
        $query = "SELECT COUNT(*) as count FROM cart_items WHERE cart_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['count'];
        }
        
        return 0;
    }
    
    /**
     * Calculate cart total
     */
    public function getCartTotal($cart_id) {
        $query = "SELECT SUM(ci.quantity * p.price) as total 
                 FROM cart_items ci 
                 JOIN products p ON ci.product_id = p.id 
                 WHERE ci.cart_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'] ? $row['total'] : 0;
        }
        
        return 0;
    }
    
    /**
     * Convert cart to order
     */
    public function checkout($cart_id, $user_id) {
        // Check if cart has items
        $items = $this->getCartItems($cart_id);
        if (empty($items)) {
            error_log("Checkout failed: Cart is empty for cart_id=$cart_id");
            return ['success' => false, 'message' => 'Cart is empty'];
        }
        
        error_log("Starting checkout process for cart_id=$cart_id, user_id=$user_id");
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Using a direct query to call the procedure with output parameter
            $conn = $this->db->getConnection();
            
            error_log("Calling FinalizeOrder procedure for cart_id=$cart_id");
            
            // Prepare call statement with output parameter
            $stmt = $conn->prepare("CALL FinalizeOrder(?, ?, @order_id)");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            
            // Get the output parameter value
            $result = $conn->query("SELECT @order_id as order_id");
            $row = $result->fetch_assoc();
            $order_id = $row['order_id'];
            
            error_log("FinalizeOrder procedure completed. order_id=" . ($order_id ?? 'NULL'));
            
            if ($order_id) {
                // Process the order to trigger the stock update
                error_log("Processing order #$order_id");
                require_once 'models/Order.php';
                $orderModel = new Order();
                $orderModel->processOrder($order_id);
                
                // Commit the transaction
                $this->db->commit();
                error_log("Order #$order_id created successfully, transaction committed");
                
                return ['success' => true, 'order_id' => $order_id];
            } else {
                // Rollback on failure
                $this->db->rollback();
                error_log("Failed to create order - order_id is empty or null");
                return ['success' => false, 'message' => 'Failed to create order'];
            }
        } catch (Exception $e) {
            // Rollback on exception
            $this->db->rollback();
            error_log("Exception during checkout: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?> 