<?php
require 'connection.php';

$error_message = '';
$success_message = '';

if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    // First check admin table
    $admin_query = mysqli_query($con, "SELECT * FROM `admin` WHERE u_email = '$email' AND u_password = '$password'");
    
    if($admin_query && mysqli_num_rows($admin_query) > 0){
        $admin = mysqli_fetch_array($admin_query);
        $_SESSION["login"] = true;
        $_SESSION["id"] = $admin["id"];
        $_SESSION["role"] = "admin";
        header('location:admindashboard.php');
        exit();
    }
    
    // If not admin, check user table
    $user_query = mysqli_query($con, "SELECT * FROM `user` WHERE u_email = '$email' AND u_password = '$password'");
    
    if($user_query && mysqli_num_rows($user_query) > 0){
        $user = mysqli_fetch_array($user_query);
        $_SESSION["login"] = true;
        $_SESSION["id"] = $user["id"];
        $_SESSION["role"] = "user";
        header('location:USERS/userdashboard.php');
        exit();
    }
    
    // If neither matched
    $error_message = "Invalid email or password. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
    <title>BAFRACOO - Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --secondary: #0f172a;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }
        
        /* Left Panel - Branding */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #4c1d95 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }
        
        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 30s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }
        
        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }
        
        .brand-logo {
            width: 100px;
            height: 100px;
            border-radius: 24px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-2xl);
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        
        .brand-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 16px;
            letter-spacing: -1px;
        }
        
        .brand-subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 48px;
            line-height: 1.6;
        }
        
        .features-list {
            text-align: left;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-item:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .feature-text {
            color: var(--white);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .feature-desc {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            margin-top: 4px;
        }
        
        /* Right Panel - Login Form */
        .login-panel {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--gray-500);
            font-size: 1rem;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.25rem;
            pointer-events: none;
            transition: color 0.2s;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: var(--white);
            color: var(--gray-900);
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-input::placeholder {
            color: var(--gray-400);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--primary);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 1.25rem;
            padding: 4px;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--gray-600);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .checkbox-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .forgot-link {
            font-size: 0.875rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px 24px;
            font-size: 1rem;
            font-weight: 600;
            color: var(--white);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(37, 99, 235, 0.5);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-icon {
            font-size: 1.25rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 32px 0;
        }
        
        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider-text {
            font-size: 0.875rem;
            color: var(--gray-400);
            font-weight: 500;
        }
        
        .signup-link {
            text-align: center;
            font-size: 0.95rem;
            color: var(--gray-600);
        }
        
        .signup-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .back-to-website {
            position: absolute;
            top: 30px;
            left: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .back-to-website:hover {
            color: var(--primary);
        }
        
        .trust-badges {
            margin-top: 40px;
            text-align: center;
        }
        
        .trust-text {
            font-size: 0.8rem;
            color: var(--gray-400);
            margin-bottom: 16px;
        }
        
        .trust-icons {
            display: flex;
            justify-content: center;
            gap: 24px;
            color: var(--gray-300);
            font-size: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .brand-panel {
                display: none;
            }
            
            .login-panel {
                flex: 1;
            }
        }
        
        @media (max-width: 480px) {
            .login-panel {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 1.75rem;
            }
            
            .form-options {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Left Panel - Branding -->
    <div class="brand-panel">
        <div class="brand-content">
            <img src="./images/Captured.JPG" alt="BAFRACOO" class="brand-logo">
            <h1 class="brand-title">BAFRACOO</h1>
            <p class="brand-subtitle">Your trusted partner all equipments and construction tools in Rwanda</p>
            
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon">🛡️</div>
                    <div>
                        <div class="feature-text">Secure Platform</div>
                        <div class="feature-desc">Enterprise-grade security for your data</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📦</div>
                    <div>
                        <div class="feature-text">Inventory Management</div>
                        <div class="feature-desc">Track stock levels in real-time</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💳</div>
                    <div>
                        <div class="feature-text">Easy Payments</div>
                        <div class="feature-desc">Secure checkout with multiple options</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div>
                        <div class="feature-text">Analytics & Reports</div>
                        <div class="feature-desc">Insights to grow your business</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Panel - Login Form -->
    <div class="login-panel">
        <a href="website.php" class="back-to-website">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to website
        </a>
        
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome back</h1>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if(!empty($error_message)): ?>
            <div class="alert alert-error">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" placeholder="Enter your email" required autocomplete="email">
                        <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <ion-icon name="eye-outline" id="toggleIcon"></ion-icon>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" class="checkbox-input">
                        <span class="checkbox-label">Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" name="submit" class="btn-primary">
                    <span>Sign In</span>
                    <ion-icon name="arrow-forward-outline" class="btn-icon"></ion-icon>
                </button>
            </form>
            
            <div class="divider">
                <div class="divider-line"></div>
                <span class="divider-text">New to BAFRACOO?</span>
                <div class="divider-line"></div>
            </div>
            
            <p class="signup-link">
                Don't have an account? <a href="register.php">Create an account</a>
            </p>
            
            <div class="trust-badges">
                <p class="trust-text">Protected by industry-standard security</p>
                <div class="trust-icons">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <ion-icon name="finger-print-outline"></ion-icon>
                </div>
            </div>
        </div>
    </div>
    
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('name', 'eye-off-outline');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('name', 'eye-outline');
            }
        }
    </script>
</body>
</html>
