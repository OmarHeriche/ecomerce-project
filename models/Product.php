<?php
require_once 'database/db.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all products
     */
    public function getAllProducts() {
        $query = "SELECT * FROM products";
        $result = $this->db->query($query);
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
    
    /**
     * Get featured products
     */
    public function getFeaturedProducts() {
        $query = "SELECT * FROM products WHERE featured = 1";
        $result = $this->db->query($query);
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
    
    /**
     * Get product by ID
     */
    public function getProductById($id) {
        $id = (int)$id;
        $query = "SELECT * FROM products WHERE id = ?";
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
     * Get products by category
     */
    public function getProductsByCategory($category) {
        $query = "SELECT * FROM products WHERE category = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
    
    /**
     * Search for products
     */
    public function searchProducts($keyword) {
        $keyword = '%' . $keyword . '%';
        $query = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
    
    /**
     * Add a new product
     */
    public function addProduct($name, $description, $price, $image, $category, $featured = 0, $stock = 0) {
        $query = "INSERT INTO products (name, description, price, image, category, featured, stock) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssdssii", $name, $description, $price, $image, $category, $featured, $stock);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update a product
     */
    public function updateProduct($id, $name, $description, $price, $image, $category, $featured, $stock) {
        $query = "UPDATE products 
                 SET name = ?, description = ?, price = ?, image = ?, 
                    category = ?, featured = ?, stock = ? 
                 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssdssiis", $name, $description, $price, $image, $category, $featured, $stock, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a product
     */
    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Check if product is in stock
     */
    public function isInStock($id, $quantity = 1) {
        $product = $this->getProductById($id);
        
        if ($product && $product['stock'] >= $quantity) {
            return true;
        }
        
        return false;
    }
}
?> 