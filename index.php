<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "db.php";

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password_hash, role 
             FROM users 
             WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && password_verify($password, $user["password_hash"])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION["user"] = [
                "id" => $user["id"],
                "full_name" => $user["full_name"],
                "email" => $user["email"],
                "role" => $user["role"]
            ];
            
            // Add login timestamp
            $_SESSION["login_time"] = time();
            
            // Store user agent for security
            $_SESSION["user_agent"] = $_SERVER["HTTP_USER_AGENT"];
            
            // Redirect based on role
            $redirect = ($user["role"] === "admin") 
                ? "admin_dashboard.php" 
                : "employee_dashboard.php";
            
            header("Location: " . $redirect);
            exit;
        } else {
            // Delay to prevent brute force
            sleep(1);
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Feedback System Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --gray-dark: #475569;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }

        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            min-height: 600px;
        }

        /* Left Panel - Brand/Info */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.1;
        }

        .brand {
            margin-bottom: 40px;
            z-index: 1;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .brand-icon i {
            font-size: 28px;
        }

        .brand h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .brand p {
            opacity: 0.9;
            font-size: 16px;
        }

        .features {
            margin-top: 40px;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .feature i {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Right Panel - Login Form */
        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 15px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        .form-control {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 16px;
            transition: var(--transition);
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .form-control.error {
            border-color: var(--danger);
        }

        .error-message {
            color: var(--danger);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 16px;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .login-footer {
            margin-top: 30px;
            text-align: center;
        }

        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .register-link:hover {
            color: var(--primary-dark);
            gap: 10px;
        }

        .demo-credentials {
            background: var(--light);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-top: 30px;
            border-left: 4px solid var(--secondary);
        }

        .demo-credentials h4 {
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credential {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .credential:last-child {
            border-bottom: none;
        }

        .credential-label {
            color: var(--gray-dark);
            font-weight: 500;
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.05);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: var(--primary-dark);
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .login-left,
            .login-right {
                padding: 30px 20px;
            }
            
            .brand h1 {
                font-size: 26px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .feature {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Brand & Info -->
        <div class="login-left">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h1>Feedback Portal</h1>
                <p>Share insights. Drive improvement. Together.</p>
            </div>
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h4>Secure & Private</h4>
                        <p>Enterprise-grade security for all your feedback</p>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <h4>Track Progress</h4>
                        <p>Monitor feedback implementation and results</p>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <div>
                        <h4>Team Collaboration</h4>
                        <p>Work together to improve workplace culture</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your feedback portal</p>
            </div>

            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control <?php echo $error ? 'error' : ''; ?>"
                            placeholder="you@company.com" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            autocomplete="email"
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control <?php echo $error ? 'error' : ''; ?>"
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    <div class="password-toggle">
                        <button type="button" id="togglePassword" class="text-button">
                            <i class="fas fa-eye"></i> Show Password
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="login-btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>New employee? 
                    <a href="register.php" class="register-link">
                        Create an account
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </p>
                
                <div class="demo-credentials">
                    <h4>
                        <i class="fas fa-vial"></i>
                        Demo Credentials
                    </h4>
                    <div class="credential">
                        <span class="credential-label">Admin:</span>
                        <span class="credential-value">admin@company.com</span>
                        <button class="copy-btn" data-copy="admin@company.com">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="credential">
                        <span class="credential-label">Password:</span>
                        <span class="credential-value">Admin@123</span>
                        <button class="copy-btn" data-copy="Admin@123">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const submitBtn = document.getElementById('submitBtn');
            const copyButtons = document.querySelectorAll('.copy-btn');
            
            // Toggle password visibility
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.innerHTML = type === 'password' 
                    ? '<i class="fas fa-eye"></i> Show Password'
                    : '<i class="fas fa-eye-slash"></i> Hide Password';
            });
            
            // Copy to clipboard functionality
            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const textToCopy = this.getAttribute('data-copy');
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        this.style.background = 'var(--secondary)';
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.style.background = '';
                        }, 2000);
                    });
                });
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(el => {
                    if (!el.classList.contains('server-error')) {
                        el.remove();
                    }
                });
                
                document.querySelectorAll('.form-control').forEach(input => {
                    input.classList.remove('error');
                });
                
                // Email validation
                if (!emailInput.value.trim()) {
                    showError(emailInput, 'Email is required');
                    isValid = false;
                } else if (!isValidEmail(emailInput.value)) {
                    showError(emailInput, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Password validation
                if (!passwordInput.value) {
                    showError(passwordInput, 'Password is required');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                } else {
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after 5 seconds (fallback)
                    setTimeout(() => {
                        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
            
            // Real-time validation
            emailInput.addEventListener('blur', function() {
                if (this.value && !isValidEmail(this.value)) {
                    showError(this, 'Please enter a valid email address');
                } else {
                    clearError(this);
                }
            });
            
            passwordInput.addEventListener('blur', function() {
                if (!this.value) {
                    showError(this, 'Password is required');
                } else {
                    clearError(this);
                }
            });
            
            // Helper functions
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            function showError(input, message) {
                input.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                input.parentNode.parentNode.appendChild(errorDiv);
            }
            
            function clearError(input) {
                input.classList.remove('error');
                const errorDiv = input.parentNode.parentNode.querySelector('.error-message');
                if (errorDiv && !errorDiv.classList.contains('server-error')) {
                    errorDiv.remove();
                }
            }
            
            // Focus email field on page load
            emailInput.focus();
        });
    </script>
</body>
</html>