<?php
require_once 'database/db.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get order by ID
     */
    public function getOrderById($id) {
        $query = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get order details (calls stored procedure)
     */
    public function getOrderDetails($order_id) {
        try {
            // First, try to use the stored procedure
            $result = $this->db->callProcedure("GetOrderDetails", [$order_id]);
            
            if ($result && !empty($result)) {
                return [
                    'items' => $result[0] ?? [],
                    'total' => isset($result[1][0]['total_amount']) ? $result[1][0]['total_amount'] : 0
                ];
            }
        } catch (Exception $e) {
            error_log("Error calling GetOrderDetails procedure: " . $e->getMessage());
            // Fall back to direct query if stored procedure fails
        }
        
        // Fallback: Use direct queries instead
        $items = [];
        $total = 0;
        
        // Get order items
        $query = "SELECT oi.*, p.name, p.image 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
                $total += ($row['price'] * $row['quantity']);
            }
        }
        
        return [
            'items' => $items,
            'total' => $total
        ];
    }
    
    /**
     * Get order history for a user (calls stored procedure)
     */
    public function getOrderHistory($user_id) {
        try {
            // First, try to use the stored procedure
            $result = $this->db->callProcedure("GetOrderHistory", [$user_id]);
            
            if ($result && !empty($result[0])) {
                // Map the fields to match what we need
                $orders = [];
                foreach ($result[0] as $order) {
                    $orders[] = [
                        'id' => $order['order_id'] ?? $order['id'] ?? 0,
                        'created_at' => $order['order_date'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'),
                        'status' => $order['status'] ?? 'processing',
                        'total' => $order['total'] ?? 0,
                        'user_id' => $user_id
                    ];
                }
                return $orders;
            }
        } catch (Exception $e) {
            error_log("Error calling GetOrderHistory procedure: " . $e->getMessage());
            // Fall back to direct query if stored procedure fails
        }
        
        // Fallback: Use a direct query instead
        $query = "SELECT o.id, o.created_at, o.status, o.total, o.user_id 
                 FROM orders o 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($order_id, $status) {
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $status, $order_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get all orders (admin function)
     */
    public function getAllOrders() {
        $query = "SELECT o.*, u.name as customer_name 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC";
        $result = $this->db->query($query);
        $orders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
    
    /**
     * Get orders by status (admin function)
     */
    public function getOrdersByStatus($status) {
        $query = "SELECT o.*, u.name as customer_name 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.status = ? 
                 ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
    
    /**
     * Process order to 'processing' state which triggers stock update
     */
    public function processOrder($order_id) {
        // Log the processing attempt
        error_log("Processing order #$order_id - changing status to processing");
        
        // Update order status which will trigger the stock reduction
        if ($this->updateOrderStatus($order_id, 'processing')) {
            error_log("Order #$order_id successfully changed to processing status");
            return true;
        }
        
        error_log("Failed to update order #$order_id status");
        return false;
    }
    
    /**
     * Cancel order which will trigger stock restoration and logging
     */
    public function cancelOrder($order_id, $reason = 'Order cancelled by user') {
        // Update order status which will trigger stock restoration and cancellation logging
        if ($this->updateOrderStatus($order_id, 'cancelled')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get canceled orders
     */
    public function getCanceledOrders() {
        $query = "SELECT co.*, o.user_id, o.total, o.created_at as order_date, u.name as customer_name 
                 FROM canceled_orders co 
                 JOIN orders o ON co.order_id = o.id 
                 LEFT JOIN users u ON o.user_id = u.id 
                 ORDER BY co.canceled_at DESC";
        $result = $this->db->query($query);
        $canceledOrders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $canceledOrders[] = $row;
            }
        }
        
        return $canceledOrders;
    }
}
?> 