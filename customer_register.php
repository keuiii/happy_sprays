<?php
session_start();
require_once 'classes/database.php';

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = Database::getInstance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email, '@') === false) {
        $error = "Please enter a valid email address with @ symbol.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one special character.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if email or username already exists
            $existing = $db->fetch(
                "SELECT customer_id FROM customers WHERE customer_username = ? OR customer_email = ? LIMIT 1",
                [$username, $email]
            );

            if ($existing) {
                $error = "Username or Email already exists.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Generate OTP
                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+100 seconds"));
                
                // Insert new customer (NOT VERIFIED YET)
                $customer_id = $db->insert(
                    "INSERT INTO customers 
                    (customer_firstname, customer_lastname, customer_username, customer_email, customer_password, verification_code, otp_expires, is_verified, cs_created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())",
                    [$firstname, $lastname, $username, $email, $hashedPassword, $otp, $expiry]
                );

                if ($customer_id) {
                    // Store pending email in session for OTP verification
                    $_SESSION['pending_email'] = $email;
                    $_SESSION['pending_customer_id'] = $customer_id;

                    // Send OTP Email via PHPMailer
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.hostinger.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'happyspray@happyspray.shop';
                        $mail->Password   = 'JANJANbuen@5';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays');
                        $mail->addAddress($email, $firstname . ' ' . $lastname);

                        $mail->isHTML(true);
                        $mail->Subject = 'Verify Your Email - Happy Sprays';
                        $mail->Body    = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2>Welcome to Happy Sprays!</h2>
                            <p>Hello <strong>{$firstname} {$lastname}</strong>,</p>
                            <p>Thank you for registering. Please use the following OTP code to verify your email:</p>
                            <div style='background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0;'>
                                <h1 style='color: #000; letter-spacing: 5px;'>{$otp}</h1>
                            </div>
                            <p><strong>This code will expire in 100 seconds.</strong></p>
                            <p>If you didn't create this account, please ignore this email.</p>
                            <br>
                            <p>Best regards,<br>Happy Sprays Team</p>
                        </body>
                        </html>
                        ";

                        $mail->send();
                        
                        // Redirect to OTP verification page
                        header("Location: verify_otp.php");
                        exit;
                        
                    } catch (Exception $e) {
                        // If email fails, still show OTP for testing
                        $error = "Registration successful but email could not be sent.<br>Your OTP code is: <strong>{$otp}</strong><br>Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Happy Sprays</title>
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

.register-container {
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

.register-left {
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

.register-left::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -100px;
    right: -100px;
}

.register-left::after {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    bottom: -50px;
    left: -50px;
}

.register-left h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.register-left p {
    font-size: 16px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
    max-width: 350px;
    position: relative;
    z-index: 1;
}

.register-right {
    background: #fff;
    padding: 60px 50px;
    max-height: 90vh;
    overflow-y: auto;
}

.register-header {
    margin-bottom: 35px;
}

.register-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 8px;
    color: #000;
}

.register-header p {
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
    margin-bottom: 22px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.register-btn {
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
    margin-top: 8px;
}

.register-btn:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.login-link {
    text-align: center;
    margin-top: 28px;
    color: #666;
    font-size: 15px;
}

.login-link a {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}

.login-link a:hover {
    text-decoration: underline;
}

.password-hint {
    font-size: 12px;
    color: #777;
    margin-top: 6px;
    font-style: italic;
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

.password-requirements {
    font-size: 13px;
    color: #333;
    margin-top: 8px;
    padding: 12px 14px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    display: none;
}

.password-requirements.show {
    display: block;
}

.password-requirements strong {
    display: block;
    margin-bottom: 8px;
    color: #000;
}

.password-requirements ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.password-requirements li {
    margin: 6px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.password-requirements li .icon {
    font-size: 16px;
    min-width: 20px;
}

.requirement-met {
    color: #2e7d32;
}

.requirement-met .icon::before {
    content: '✔';
}

.requirement-unmet {
    color: #c62828;
}

.requirement-unmet .icon::before {
    content: '✖';
}

.password-success {
    font-size: 14px;
    color: #2e7d32;
    margin-top: 8px;
    padding: 12px 14px;
    background: #e8f5e9;
    border-radius: 8px;
    border: 1px solid #2e7d32;
    display: none;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.password-success.show {
    display: flex;
}

.password-success::before {
    content: '✅';
    font-size: 16px;
}

@media (max-width: 768px) {
    body {
        padding: 20px;
    }
    
    .register-container {
        grid-template-columns: 1fr;
    }
    
    .register-left {
        padding: 60px 40px;
    }
    
    .register-left h1 {
        font-size: 36px;
    }
    
    .register-right {
        padding: 50px 40px;
    }
    
    .register-header h2 {
        font-size: 28px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}

</style>
</head>
<body>

<div class="register-container">
    <div class="register-left">
    <h1>HAPPY SPRAYS</h1>
    <p>Join our community and discover exclusive fragrances. Create an account to enjoy personalized recommendations, track your orders, and get special offers!</p>
</div>

    
    <div class="register-right">
        <div class="register-header">
            <h2>Create Account</h2>
            <p>Fill in your details to get started</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST" action="customer_register.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" 
                           id="firstname" 
                           name="firstname" 
                           placeholder="Juan"
                           value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" 
                           id="lastname" 
                           name="lastname" 
                           placeholder="Dela Cruz"
                           value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>"
                           required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Choose a unique username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="your.email@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                       title="Please enter a valid email address with @ symbol"
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <div class="password-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Create a strong password"
                           required>
                    <span class="toggle-password" onclick="togglePassword('password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
                <div class="password-requirements" id="passwordRequirements">
                    <strong>Password must contain:</strong>
                    <ul>
                        <li class="requirement-unmet"><span class="icon"></span><span>At least 8 characters</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One uppercase letter (A-Z)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One lowercase letter (a-z)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One number (0-9)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One special character (!@#$%^&*)</span></li>
                    </ul>
                </div>
                <div class="password-success" id="passwordSuccess">
                    Password is strong!
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <div class="password-wrapper">
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Re-enter your password"
                           required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
            </div>
            
            <button type="submit" name="register" class="register-btn">Create Account</button>
            
            <div class="login-link">
                Already have an account? <a href="customer_login.php">Login here</a>
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

// Real-time password validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('confirm_password');
    const requirementsBox = document.getElementById('passwordRequirements');
    const successBox = document.getElementById('passwordSuccess');
    
    if (passwordField) {
        // Show requirements when user focuses on password field
        passwordField.addEventListener('focus', function() {
            if (this.value.length === 0) {
                requirementsBox.classList.add('show');
                successBox.classList.remove('show');
            }
        });
        
        // Hide both when user leaves password field and it's empty
        passwordField.addEventListener('blur', function() {
            if (this.value.length === 0) {
                requirementsBox.classList.remove('show');
                successBox.classList.remove('show');
            }
        });
        
        // Validate as user types
        passwordField.addEventListener('input', function() {
            validatePasswordStrength(this.value);
        });
    }
    
    if (confirmField) {
        confirmField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }
});

function validatePasswordStrength(password) {
    const requirements = document.querySelectorAll('.password-requirements li');
    const requirementsBox = document.getElementById('passwordRequirements');
    const successBox = document.getElementById('passwordSuccess');
    if (requirements.length === 0) return;
    
    const checks = [
        password.length >= 8,
        /[A-Z]/.test(password),
        /[a-z]/.test(password),
        /[0-9]/.test(password),
        /[^A-Za-z0-9]/.test(password)
    ];
    
    requirements.forEach((req, index) => {
        if (checks[index]) {
            req.classList.remove('requirement-unmet');
            req.classList.add('requirement-met');
        } else {
            req.classList.remove('requirement-met');
            req.classList.add('requirement-unmet');
        }
    });
    
    // Check if all requirements are met
    const allMet = checks.every(check => check === true);
    
    if (allMet) {
        // Hide requirements, show success
        requirementsBox.classList.remove('show');
        successBox.classList.add('show');
    } else {
        // Show requirements, hide success
        requirementsBox.classList.add('show');
        successBox.classList.remove('show');
    }
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const confirmField = document.getElementById('confirm_password');
    
    if (confirm.length > 0) {
        if (password !== confirm) {
            confirmField.style.borderColor = '#c62828';
        } else {
            confirmField.style.borderColor = '#2e7d32';
        }
    } else {
        confirmField.style.borderColor = '#e0e0e0';
    }
}
</script>

</body>
</html>