<?php
require_once 'config.php';

/**
 * Database Connection Class
 */
class Database {
    private $conn = null;
    private static $instance = null;
    
    /**
     * Constructor - Create a new database connection if one doesn't exist
     */
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set character set
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Get database instance - Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the database connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a query and return the result
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    /**
     * Prepare a statement
     */
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    /**
     * Get the last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Call a stored procedure
     */
    public function callProcedure($procedure, $params = []) {
        // Build the parameter placeholders
        $placeholders = str_repeat('?, ', count($params) - 1) . '?';
        
        // Only add parameters if there are any
        $query = "CALL $procedure(" . (empty($params) ? '' : $placeholders) . ")";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            // Determine parameter types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i'; // integer
                } elseif (is_float($param)) {
                    $types .= 'd'; // double
                } elseif (is_string($param)) {
                    $types .= 's'; // string
                } else {
                    $types .= 'b'; // blob
                }
            }
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $output = [];
        if ($result) {
            // Collect all result sets
            do {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                if (!empty($rows)) {
                    $output[] = $rows;
                }
                
                if ($stmt->more_results()) {
                    $stmt->next_result();
                    $result = $stmt->get_result();
                } else {
                    break;
                }
            } while (true);
        }
        
        $stmt->close();
        return $output;
    }
    
    /**
     * Escape string for security
     */
    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
}
?> 