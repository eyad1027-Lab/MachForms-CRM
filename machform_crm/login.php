<?php
/**
 * Machform CRM - Login Page
 */

// Load configuration
require_once __DIR__ . '/config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';

// Initialize
$db = Database::getInstance();
$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif ($auth->login($username, $password)) {
        // Check if redirect is requested
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-logo {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #388E3C;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            margin-top: 30px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        .demo-credentials h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .demo-credentials p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-form"></i>
            </div>
            <h1><?= APP_NAME ?></h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="#">Forgot Password?</a>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Credentials:</h4>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p style="margin-top: 10px; font-size: 0.75rem; color: #999;">
                Note: You need to create the users table first or modify the authentication system.
            </p>
        </div>
    </div>
</body>
</html>
