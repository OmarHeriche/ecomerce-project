<?php
require_once 'database/config.php';
require_once 'database/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

// Check if admin exists
$check_query = "SELECT * FROM users WHERE email = 'admin@example.com'";
$result = $conn->query($check_query);

if ($result && $result->num_rows > 0) {
    // Admin exists, update password
    $password_hash = '$2y$10$djZEFtoJS80HnBPAlDpRHOSe3CJ9YXmu/6MRH/SAsIgBD2Kq5bSKC';
    $update_query = "UPDATE users SET password = ? WHERE email = 'admin@example.com'";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        echo "Admin password updated successfully.<br>";
    } else {
        echo "Error updating admin password: " . $conn->error . "<br>";
    }
} else {
    // Admin doesn't exist, create new admin
    $name = "Admin User";
    $email = "admin@example.com";
    $password_hash = '$2y$10$djZEFtoJS80HnBPAlDpRHOSe3CJ9YXmu/6MRH/SAsIgBD2Kq5bSKC';
    $is_admin = 1;
    
    $insert_query = "INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssi", $name, $email, $password_hash, $is_admin);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully.<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
}

echo "<br><a href='login.php'>Go to login page</a>";
?> 