<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

$page_title = "Login - Francoder Electronics";
$error = '';
$success = '';

// Check for flash messages
$flashMessage = getFlashMessage();
if ($flashMessage) {
    if ($flashMessage['type'] === 'error') {
        $error = $flashMessage['text'];
    } else {
        $success = $flashMessage['text'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password_hash'])) {
                    // Check if user is active (you might add an 'active' column later)
                    
                    // Create user session
                    createUserSession($user);
                    
                    // Remember me functionality
                    if ($remember) {
                        $token = generateRandomString(32);
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO remember_tokens (user_id, token, expires_at) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$user['user_id'], $token, date('Y-m-d H:i:s', $expiry)]);
                        
                        setcookie('remember_token', $token, $expiry, '/', '', false, true);
                    }
                    
                    // Log activity
                    logActivity($pdo, $user['user_id'], 'login', 'User logged in');
                    
                    // Redirect to intended page or homepage
                    $redirect_url = isset($_SESSION['redirect_after_login']) ? 
                                   $_SESSION['redirect_after_login'] : '../index.php';
                    
                    unset($_SESSION['redirect_after_login']);
                    
                    redirect($redirect_url, 'Login successful! Welcome back, ' . $user['full_name'] . '!');
                    
                } else {
                    $error = 'Invalid username/email or password';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .auth-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .auth-logo {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .form-group .input-with-icon {
            position: relative;
        }
        
        .form-group .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .form-group .input-with-icon input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group .input-with-icon input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            color: #2c3e50;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #3498db;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .auth-divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #95a5a6;
        }
        
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #eee;
        }
        
        .auth-divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            z-index: 1;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .auth-footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .auth-footer a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .error-box {
            background: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 20px;
            }
            
            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h1>Welcome Back</h1>
                    <p>Sign in to your Francoder account</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-box">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter your username or email"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required>
                        </div>
                    </div>
                    
                    <div class="remember-forgot">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" id="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>New to Francoder?</span>
                </div>
                
                <div class="auth-footer">
                    <p>Don't have an account? 
                        <a href="register.php">Create Account</a>
                    </p>
                    <p style="margin-top: 10px; font-size: 0.9rem; color: #7f8c8d;">
                        <a href="../index.php">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.querySelector('form');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.input-error').forEach(el => el.remove());
                document.querySelectorAll('.input-with-icon input').forEach(input => {
                    input.style.borderColor = '';
                });
                
                // Validate username
                if (!usernameInput.value.trim()) {
                    showError(usernameInput, 'Username or email is required');
                    isValid = false;
                }
                
                // Validate password
                if (!passwordInput.value) {
                    showError(passwordInput, 'Password is required');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            function showError(input, message) {
                input.style.borderColor = '#e74c3c';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'input-error';
                errorDiv.style.color = '#e74c3c';
                errorDiv.style.fontSize = '0.85rem';
                errorDiv.style.marginTop = '5px';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                
                input.parentNode.parentNode.appendChild(errorDiv);
            }
            
            // Clear error on input
            [usernameInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                    const errorDiv = this.parentNode.parentNode.querySelector('.input-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                });
            });
            
            // Toggle password visibility (for future enhancement)
            // You can add an eye icon to toggle password visibility
        });
    </script>
</body>
</html>