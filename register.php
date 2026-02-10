<?php
require 'connection.php';

$error_message = '';
$success_message = '';
$account_type = isset($_POST['account_type']) ? $_POST['account_type'] : 'user';

if(isset($_POST['submit'])){
    $full_name = mysqli_real_escape_string($con, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $phone = mysqli_real_escape_string($con, trim($_POST['phone']));
    $address = mysqli_real_escape_string($con, trim($_POST['address'] ?? 'Not provided'));
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    $account_type = $_POST['account_type'];
    
    // Validation
    if($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif(strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check which table to use based on account type
        if($account_type === 'admin') {
            // Check if email already exists in admin table
            $check_query = mysqli_query($con, "SELECT * FROM `admin` WHERE u_email = '$email'");
            
            if($check_query && mysqli_num_rows($check_query) > 0) {
                $error_message = "This email is already registered as an admin.";
            } else {
                // Insert into admin table - using correct column names
                $insert = mysqli_query($con, "INSERT INTO `admin` (u_name, u_email, u_phonenumber, u_address, u_password) VALUES ('$full_name', '$email', '$phone', '$address', '$password')");
                
                if($insert) {
                    $success_message = "Admin account created successfully! You can now sign in.";
                } else {
                    $error_message = "Registration failed: " . mysqli_error($con);
                }
            }
        } else {
            // Check if email already exists in user table
            $check_query = mysqli_query($con, "SELECT * FROM `user` WHERE u_email = '$email'");
            
            if($check_query && mysqli_num_rows($check_query) > 0) {
                $error_message = "This email is already registered.";
            } else {
                // Insert into user table - using correct column names
                $insert = mysqli_query($con, "INSERT INTO `user` (u_name, u_email, u_phonenumber, u_address, u_password) VALUES ('$full_name', '$email', '$phone', '$address', '$password')");
                
                if($insert) {
                    $success_message = "Account created successfully! You can now sign in.";
                } else {
                    $error_message = "Registration failed: " . mysqli_error($con);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
    <title>BAFRACOO - Create Account</title>
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
            overflow-x: hidden;
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
        
        .testimonial {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            text-align: left;
            backdrop-filter: blur(10px);
        }
        
        .testimonial-text {
            color: var(--white);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 24px;
            font-style: italic;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .author-info {
            color: var(--white);
        }
        
        .author-name {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .author-role {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Right Panel - Register Form */
        .register-panel {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 60px;
            position: relative;
            overflow-y: auto;
        }
        
        .register-container {
            width: 100%;
            max-width: 420px;
        }
        
        .register-header {
            margin-bottom: 32px;
        }
        
        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        .register-header p {
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
        
        /* Account Type Toggle */
        .account-type-toggle {
            display: flex;
            background: var(--gray-100);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 28px;
        }
        
        .type-option {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .type-option input {
            display: none;
        }
        
        .type-option.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        
        .type-option:hover:not(.active) {
            color: var(--gray-700);
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background: var(--gray-200);
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .strength-weak { width: 33%; background: var(--error); }
        .strength-medium { width: 66%; background: var(--warning); }
        .strength-strong { width: 100%; background: var(--success); }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 28px;
        }
        
        .terms-checkbox input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .terms-checkbox label {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.5;
        }
        
        .terms-checkbox a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .terms-checkbox a:hover {
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
            margin: 28px 0;
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
        
        .signin-link {
            text-align: center;
            font-size: 0.95rem;
            color: var(--gray-600);
        }
        
        .signin-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .signin-link a:hover {
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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .brand-panel {
                display: none;
            }
            
            .register-panel {
                flex: 1;
            }
        }
        
        @media (max-width: 480px) {
            .register-panel {
                padding: 30px 20px;
            }
            
            .register-header h1 {
                font-size: 1.75rem;
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
            <p class="brand-subtitle">Join thousands of satisfied customers who trust us for quality fire safety equipment</p>
            
            <div class="testimonial">
                <p class="testimonial-text">"BAFRACOO has transformed how we manage our safety equipment inventory. The platform is intuitive and the support is exceptional."</p>
                <div class="testimonial-author">
                    <div class="author-avatar">JM</div>
                    <div class="author-info">
                        <div class="author-name">Jean-Marie K.</div>
                        <div class="author-role">Business Owner, Kigali</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Panel - Register Form -->
    <div class="register-panel">
        <a href="website.php" class="back-to-website">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to website
        </a>
        
        <div class="register-container">
            <div class="register-header">
                <h1>Create your account</h1>
                <p>Start managing your inventory today</p>
            </div>
            
            <?php if(!empty($error_message)): ?>
            <div class="alert alert-error">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Account Type Toggle -->
                <div class="account-type-toggle">
                    <label class="type-option <?php echo $account_type === 'user' ? 'active' : ''; ?>" id="userOption">
                        <input type="radio" name="account_type" value="user" <?php echo $account_type === 'user' ? 'checked' : ''; ?>>
                        <ion-icon name="person-outline" style="margin-right: 6px;"></ion-icon> Customer
                    </label>
                    <label class="type-option <?php echo $account_type === 'admin' ? 'active' : ''; ?>" id="adminOption">
                        <input type="radio" name="account_type" value="admin" <?php echo $account_type === 'admin' ? 'checked' : ''; ?>>
                        <ion-icon name="shield-outline" style="margin-right: 6px;"></ion-icon> Admin
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
                        <ion-icon name="person-outline" class="input-icon"></ion-icon>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" placeholder="Enter your email" required autocomplete="email">
                        <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="input-wrapper">
                        <input type="tel" name="phone" class="form-input" placeholder="Enter your phone number" required>
                        <ion-icon name="call-outline" class="input-icon"></ion-icon>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <div class="input-wrapper">
                        <input type="text" name="address" class="form-input" placeholder="Enter your address (city, district)" required>
                        <ion-icon name="location-outline" class="input-icon"></ion-icon>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-input" placeholder="Create a password" required autocomplete="new-password" onkeyup="checkPasswordStrength(this.value)">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <ion-icon name="eye-outline" id="toggleIcon1"></ion-icon>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Confirm your password" required autocomplete="new-password">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <ion-icon name="eye-outline" id="toggleIcon2"></ion-icon>
                        </button>
                    </div>
                </div>
                
                <div class="terms-checkbox">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                
                <button type="submit" name="submit" class="btn-primary">
                    <span>Create Account</span>
                    <ion-icon name="arrow-forward-outline" class="btn-icon"></ion-icon>
                </button>
            </form>
            
            <div class="divider">
                <div class="divider-line"></div>
                <span class="divider-text">Already have an account?</span>
                <div class="divider-line"></div>
            </div>
            
            <p class="signin-link">
                <a href="login.php">Sign in to your account</a>
            </p>
        </div>
    </div>
    
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('name', 'eye-off-outline');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('name', 'eye-outline');
            }
        }
        
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength-bar';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 1) {
                strengthBar.className = 'password-strength-bar strength-weak';
            } else if (strength <= 2) {
                strengthBar.className = 'password-strength-bar strength-medium';
            } else {
                strengthBar.className = 'password-strength-bar strength-strong';
            }
        }
        
        // Account type toggle
        document.querySelectorAll('.type-option input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.type-option').forEach(option => {
                    option.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });
    </script>
</body>
</html>
