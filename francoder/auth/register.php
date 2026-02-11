<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

$page_title = "Register - Francoder Electronics";
$errors = [];
$success = '';

// Check for flash messages
$flashMessage = getFlashMessage();
if ($flashMessage) {
    if ($flashMessage['type'] === 'error') {
        $errors[] = $flashMessage['text'];
    } else {
        $success = $flashMessage['text'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif (preg_match('/[^a-zA-Z0-9_]/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        } elseif (usernameExists($pdo, $username)) {
            $errors[] = 'Username already exists';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (emailExists($pdo, $email)) {
            $errors[] = 'Email already registered';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } else {
            $passwordErrors = validatePassword($password);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, register user
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Hash password
                $hashed_password = hashPassword($password);
                
                // Create user
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, phone, address, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'customer')
                ");
                
                $stmt->execute([
                    $username,
                    $email,
                    $hashed_password,
                    $full_name,
                    $phone,
                    $address
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Get the created user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                // Create session and log in automatically
                createUserSession($user);
                
                // Log activity
                logActivity($pdo, $user_id, 'register', 'New user registration');
                
                $pdo->commit();
                
                // Send welcome email (in production)
                // sendWelcomeEmail($email, $full_name);
                
                redirect('../index.php', 'Registration successful! Welcome to Francoder!');
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Registration error: " . $e->getMessage());
                $errors[] = 'An error occurred during registration. Please try again.';
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
            max-width: 500px;
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
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .form-group .input-with-icon input,
        .form-group .input-with-icon textarea {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group .input-with-icon textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group .input-with-icon input:focus,
        .form-group .input-with-icon textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
        }
        
        .strength-meter {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-weak .strength-fill {
            width: 33%;
            background-color: #e74c3c;
        }
        
        .strength-medium .strength-fill {
            width: 66%;
            background-color: #f39c12;
        }
        
        .strength-strong .strength-fill {
            width: 100%;
            background-color: #2ecc71;
        }
        
        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .terms-agreement input[type="checkbox"] {
            margin-top: 3px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .terms-agreement label {
            font-size: 0.95rem;
            color: #2c3e50;
            cursor: pointer;
            line-height: 1.5;
        }
        
        .terms-agreement a {
            color: #3498db;
            text-decoration: none;
        }
        
        .terms-agreement a:hover {
            text-decoration: underline;
        }
        
        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
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
        }
        
        .error-box ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        
        .error-box li {
            margin-bottom: 5px;
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
        
        @media (max-width: 600px) {
            .auth-card {
                padding: 30px 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 20px;
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
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join Francoder Electronics today</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-box">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" 
                                       id="full_name" 
                                       name="full_name" 
                                       placeholder="Enter your full name"
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-at"></i>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Choose a username"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       required>
                            </div>
                            <div class="username-hint" style="font-size: 0.85rem; color: #7f8c8d; margin-top: 5px;">
                                Letters, numbers, and underscores only
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Enter your email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Create a password"
                                       required>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                Password strength: <span id="strengthText">None</span>
                                <div class="strength-meter">
                                    <div class="strength-fill"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Confirm your password"
                                       required>
                            </div>
                            <div id="passwordMatch" style="font-size: 0.85rem; margin-top: 5px;"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="Enter your phone number"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-home"></i>
                            <textarea id="address" 
                                      name="address" 
                                      placeholder="Enter your address (optional)"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="terms-agreement">
                        <input type="checkbox" 
                               id="terms" 
                               name="terms" 
                               required>
                        <label for="terms">
                            I agree to the <a href="../terms.php" target="_blank">Terms of Service</a> 
                            and <a href="../privacy.php" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>Already have an account?</span>
                </div>
                
                <div class="auth-footer">
                    <p>Already registered? 
                        <a href="login.php">Sign In</a>
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
            const form = document.getElementById('registerForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            const strengthFill = document.querySelector('.strength-fill');
            const passwordMatch = document.getElementById('passwordMatch');
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                
                // Character type checks
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update UI
                let strengthClass = '';
                let text = '';
                
                if (password.length === 0) {
                    text = 'None';
                    strengthClass = '';
                } else if (strength <= 2) {
                    text = 'Weak';
                    strengthClass = 'strength-weak';
                } else if (strength <= 4) {
                    text = 'Medium';
                    strengthClass = 'strength-medium';
                } else {
                    text = 'Strong';
                    strengthClass = 'strength-strong';
                }
                
                strengthText.textContent = text;
                passwordStrength.className = 'password-strength ' + strengthClass;
            });
            
            // Password confirmation check
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    passwordMatch.style.color = '#2ecc71';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                    passwordMatch.style.color = '#e74c3c';
                }
            }
            
            passwordInput.addEventListener('input', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            // Form validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.input-error').forEach(el => el.remove());
                document.querySelectorAll('.input-with-icon input, .input-with-icon textarea').forEach(input => {
                    input.style.borderColor = '';
                });
                
                // Validate required fields
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        showError(field, 'This field is required');
                        isValid = false;
                    }
                });
                
                // Validate email format
                const emailField = document.getElementById('email');
                if (emailField.value && !isValidEmail(emailField.value)) {
                    showError(emailField, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Validate password match
                if (passwordInput.value !== confirmPasswordInput.value) {
                    showError(confirmPasswordInput, 'Passwords do not match');
                    isValid = false;
                }
                
                // Validate password strength
                const password = passwordInput.value;
                if (password.length > 0 && password.length < 8) {
                    showError(passwordInput, 'Password must be at least 8 characters');
                    isValid = false;
                }
                
                // Validate terms agreement
                const termsCheckbox = document.getElementById('terms');
                if (!termsCheckbox.checked) {
                    const termsLabel = termsCheckbox.parentNode;
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'input-error';
                    errorDiv.style.color = '#e74c3c';
                    errorDiv.style.marginTop = '10px';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> You must agree to the terms and conditions';
                    termsLabel.parentNode.appendChild(errorDiv);
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
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Clear error on input
            form.querySelectorAll('input, textarea').forEach(input => {
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                    const errorDiv = this.parentNode.parentNode.querySelector('.input-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                    
                    // Clear terms error if checkbox is checked
                    if (this.id === 'terms' && this.checked) {
                        const termsError = this.parentNode.parentNode.querySelector('.input-error');
                        if (termsError) {
                            termsError.remove();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>