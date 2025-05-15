<?php
session_start();
require_once 'models/User.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to home page
    header('Location: index.php');
    exit;
}

// Initialize User model
$userModel = new User();

// Check if there's a remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $remembered_id = $_COOKIE['remember_user'];
    $remembered_user = $userModel->getUserById($remembered_id);
    
    if ($remembered_user) {
        // Set session variables
        $_SESSION['user_id'] = $remembered_user['id'];
        $_SESSION['user_name'] = $remembered_user['name'];
        $_SESSION['is_admin'] = $remembered_user['is_admin'];
        
        // Redirect to home page
        header('Location: index.php');
        exit;
    }
}

// Initialize error message
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Attempt login
        $result = $userModel->login($email, $password);
        
        if ($result['success']) {
            // Set remember me cookie if requested
            if ($remember) {
                setcookie('remember_user', $_SESSION['user_id'], time() + (86400 * 30), "/"); // 30 days
            }
            
            // Redirect to home page
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container">
    <div class="auth-container">
        <h1>Login</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="post" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</main>

<style>
    .auth-container {
        max-width: 500px;
        margin: 40px auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .auth-container h1 {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .auth-form .form-group {
        margin-bottom: 15px;
    }
    
    .auth-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .auth-form input[type="email"],
    .auth-form input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .remember-me {
        display: flex;
        align-items: center;
    }
    
    .remember-me input {
        margin-right: 5px;
    }
    
    .remember-me label {
        display: inline;
        margin-bottom: 0;
    }
    
    .auth-form button {
        width: 100%;
        margin-top: 10px;
    }
    
    .auth-links {
        text-align: center;
        margin-top: 20px;
    }
    
    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>