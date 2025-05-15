<?php
session_start();
require_once 'models/Order.php';
require_once 'models/Product.php';
require_once 'database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int)$_GET['id'];

// Initialize database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Wrap everything in try-catch to handle errors gracefully
try {
    // Get the order details with direct query
    $query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    // Get order items with direct query
    $query = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    // Prepare the response
    $response = [
        'success' => true,
        'order' => $order,
        'items' => $items
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in get_order_details.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while retrieving order details'
    ]);
}
?> 