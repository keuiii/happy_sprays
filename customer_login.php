<?php
session_start();
require_once 'classes/database.php';

// Move PHPMailer imports to top
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$db = Database::getInstance();

// If already logged in, redirect
if ($db->isLoggedIn()) {
    $role = $db->getCurrentUserRole();
    if ($role === 'customer') {
        header("Location: index.php");
    } else {
        header("Location: admin_dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usernameOrEmail = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } 
    // ✅ HARD-CODED ADMIN LOGIN CHECK
    elseif ($usernameOrEmail === 'admin' && $password === 'admin123') {
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['admin_id'] = 3;
        
        header("Location: admin_dashboard.php");
        exit;
    } 
    // ✅ CUSTOMER LOGIN (via database)
    else {
        try {
            // First, check if user exists and get their info
            $customer = $db->fetch(
                "SELECT * FROM customers WHERE customer_username = ? OR customer_email = ? LIMIT 1",
                [$usernameOrEmail, $usernameOrEmail]
            );
            
            if (!$customer) {
                $error = "Invalid username/email or password.";
            } 
            // ✅ CHECK PASSWORD FIRST (before checking verification)
            elseif (!password_verify($password, $customer['customer_password'])) {
                $error = "Invalid username/email or password.";
            }
            // ✅ CHECK IF EMAIL IS VERIFIED
            elseif ($customer['is_verified'] == 0) {
                // Check if OTP has expired
                $otp_expired = false;
                if (!empty($customer['otp_expires'])) {
                    $otp_expired = strtotime($customer['otp_expires']) < time();
                }
                
                if ($otp_expired || empty($customer['verification_code'])) {
                    // Generate new OTP
                    $new_otp = rand(100000, 999999);
                    $new_expiry = date("Y-m-d H:i:s", strtotime("+100 seconds"));
                    
                    $db->update(
                        "UPDATE customers SET verification_code = ?, otp_expires = ? WHERE customer_id = ?",
                        [$new_otp, $new_expiry, $customer['customer_id']]
                    );
                    
                    // Send new OTP
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.hostinger.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'happyspray@happyspray.shop';
                        $mail->Password = 'JANJANbuen@5';  // Use consistent password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;
                        
                        $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays');
                        $mail->addAddress($customer['customer_email'], $customer['customer_firstname'] . ' ' . $customer['customer_lastname']);
                        
                        $mail->isHTML(true);
                        $mail->Subject = 'New Verification Code - Happy Sprays';
                        $mail->Body = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2>New Verification Code</h2>
                            <p>Hello <strong>{$customer['customer_firstname']} {$customer['customer_lastname']}</strong>,</p>
                            <p>Your OTP expired. Here is your new code:</p>
                            <div style='background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0;'>
                                <h1 style='color: #000; letter-spacing: 5px;'>{$new_otp}</h1>
                            </div>
                            <p><strong>This code will expire in 100 seconds.</strong></p>
                            <br>
                            <p>Best regards,<br>Happy Sprays Team</p>
                        </body>
                        </html>
                        ";
                        
                        $mail->send();
                        $success = "A new verification code has been sent to your email!";
                    } catch (MailException $e) {
                        error_log("Failed to send new OTP: " . $e->getMessage());
                        $error = "Failed to send verification code. Your OTP is: <strong>{$new_otp}</strong>";
                    }
                }
                
                // Set session for OTP page
                $_SESSION['pending_email'] = $customer['customer_email'];
                $_SESSION['pending_customer_id'] = $customer['customer_id'];
                
                // Redirect to OTP page after 2 seconds if success
                if (!empty($success)) {
                    header("refresh:2;url=verify_otp.php");
                } else {
                    header("Location: verify_otp.php");
                    exit;
                }
            }
            // ✅ LOGIN SUCCESS
            else {
                // Set session variables
                $_SESSION['role'] = 'customer';
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_username'] = $customer['customer_username'];
                $_SESSION['customer_email'] = $customer['customer_email'];
                $_SESSION['customer_firstname'] = $customer['customer_firstname'];
                $_SESSION['customer_lastname'] = $customer['customer_lastname'];
                
                // Load saved cart from database
                $db->loadCartFromDatabase();
                
                // Redirect
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: " . $redirect);
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Login failed. Please try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f5f5;
    color: #333;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.login-container {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    overflow: hidden;
    max-width: 1000px;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 2px solid #000;
}

.login-left {
    background: linear-gradient(135deg, #000 0%, #333 100%);
    padding: 80px 50px;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.login-left::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -100px;
    right: -100px;
}

.login-left::after {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    bottom: -50px;
    left: -50px;
}

.login-left h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #fff;
    position: relative;
    z-index: 1;
}

.login-left p {
    font-size: 16px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
    position: relative;
    z-index: 1;
}

.login-right {
    background: #fff;
    padding: 80px 50px;
}

.login-header {
    margin-bottom: 40px;
}

.login-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 8px;
    color: #000;
}

.login-header p {
    color: #666;
    font-size: 15px;
}

.error-message {
    background: #ffebee;
    color: #c62828;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
    border-left: 4px solid #c62828;
    font-size: 14px;
}

.success-message {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
    border-left: 4px solid #2e7d32;
    font-size: 14px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #000;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    background: #fafafa;
    font-family: 'Poppins', sans-serif;
}

.form-group input:focus {
    outline: none;
    border-color: #000;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    font-size: 14px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.remember-me input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.forgot-link {
    color: #000;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.3s;
}

.forgot-link:hover {
    text-decoration: underline;
}

.login-btn {
    width: 100%;
    padding: 16px;
    background: #000;
    color: #fff;
    border: 2px solid #000;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
}

.login-btn:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.register-link {
    text-align: center;
    margin-top: 28px;
    color: #666;
    font-size: 15px;
}

.register-link a {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}

.register-link a:hover {
    text-decoration: underline;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 50px;
}

.toggle-password {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    font-size: 22px;
    user-select: none;
    transition: color 0.3s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-password:hover {
    color: #000;
}

.eye-icon {
    width: 24px;
    height: 24px;
    display: inline-block;
}

@media (max-width: 768px) {
    body {
        padding: 20px;
    }
    
    .login-container {
        grid-template-columns: 1fr;
    }
    
    .login-left {
        padding: 60px 40px;
    }
    
    .login-left h1 {
        font-size: 36px;
    }
    
    .login-right {
        padding: 60px 40px;
    }
    
    .login-header h2 {
        font-size: 28px;
    }
}

</style>
</head>
<body>

<div class="login-container">
    <div class="login-left">
        <h1>Happy Sprays</h1>
        <p>Welcome back! Sign in to continue exploring our premium fragrances, track your orders, and manage your account with ease.</p>
    </div>

    
    <div class="login-right">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please login to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="customer_login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
            <div class="form-group">
                <label for="username">Email or Username</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Enter your email or username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password"
                           required>
                    <span class="toggle-password" onclick="togglePassword('password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
            </div>
            
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>
            
            <button type="submit" name="login" class="login-btn">Login</button>
            
            <div class="register-link">
                Don't have an account? <a href="customer_register.php">Register here</a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId, iconElement) {
    const field = document.getElementById(fieldId);
    const svg = iconElement.querySelector('svg');
    
    if (field.type === 'password') {
        field.type = 'text';
        // Eye (visible) - password is now shown
        svg.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    } else {
        field.type = 'password';
        // Eye with slash (hidden) - password is now hidden
        svg.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    }
}
</script>

</body>
</html>