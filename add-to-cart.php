<?php
session_start();

// Handle POST request to add items to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID or quantity'
        ]);
        exit;
    }
    
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product is already in cart
    $product_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] === $product_id) {
            $item['quantity'] += $quantity;
            $product_exists = true;
            break;
        }
    }
    
    // If product is not in cart, add it
    if (!$product_exists) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => count($_SESSION['cart'])
    ]);
    exit;
}

// If not a POST request, redirect to home page
header('Location: index.php');
exit;
?>