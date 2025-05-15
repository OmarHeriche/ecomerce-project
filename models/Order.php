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
        $result = $this->db->callProcedure("GetOrderDetails", [$order_id]);
        
        if ($result && !empty($result)) {
            return [
                'items' => $result[0] ?? [],
                'total' => isset($result[1][0]['total_amount']) ? $result[1][0]['total_amount'] : 0
            ];
        }
        
        return null;
    }
    
    /**
     * Get order history for a user (calls stored procedure)
     */
    public function getOrderHistory($user_id) {
        $result = $this->db->callProcedure("GetOrderHistory", [$user_id]);
        
        if ($result && !empty($result[0])) {
            return $result[0];
        }
        
        return [];
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
        // Update order status which will trigger the stock reduction
        return $this->updateOrderStatus($order_id, 'processing');
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