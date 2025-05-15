<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Database - TechShop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4a90e2;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .button:hover {
            background-color: #3a80d2;
        }
        .output {
            margin-top: 20px;
            padding: 15px;
            background-color: #f1f1f1;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .home-link {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>TechShop Database Initialization</h1>
        
        <div class="warning">
            <strong>Warning:</strong> Running this script will reset the database and all existing data will be lost!
        </div>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize'])): ?>
            <div class="output">
                <?php include 'database/init_db.php'; ?>
            </div>
            
            <a href="index.php" class="home-link button">Go to Homepage</a>
        <?php else: ?>
            <form method="post" onsubmit="return confirm('Are you sure you want to initialize the database? All existing data will be lost!');">
                <button type="submit" name="initialize" class="button">Initialize Database</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 