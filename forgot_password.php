<?php
require_once 'classes/database.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();

$conn = Database::getInstance()->getConnection();
$msg = "";
$step = 1;
$username_or_email = '';
$otp = '';
$email = '';

// Step 1: Enter email/username
if (isset($_POST['step']) && $_POST['step'] == '1') {
    $username_or_email = trim($_POST['username_or_email']);
    if ($username_or_email === "") {
        $msg = "⚠️ Please enter your email or username.";
    } else {
        $stmt = $conn->prepare("SELECT customer_id, customer_email, customer_firstname, customer_lastname FROM customers WHERE customer_username = ? OR customer_email = ? LIMIT 1");
        $stmt->execute([$username_or_email, $username_or_email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($customer) {
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt = $conn->prepare("INSERT INTO password_resets (customer_id, otp, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$customer['customer_id'], $otp, $expires_at]);
            // DEBUG: Show last inserted OTP row
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$customer['customer_id']]);
            $lastOtpRow = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div style="color:blue;font-size:13px;">DEBUG: Last inserted OTP row: ' . htmlspecialchars(json_encode($lastOtpRow)) . '</div>';

            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'happyspray@happyspray.shop';
                $mail->Password = 'JANJANbuen@5';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays');
                $mail->addAddress($customer['customer_email'], $customer['customer_firstname'] . ' ' . $customer['customer_lastname']);
                $mail->isHTML(true);
                $mail->Subject = 'Your Happy Sprays Password Reset OTP';
                $mail->Body = "<h3>Password Reset OTP</h3>"
                    . "Hello {$customer['customer_firstname']} {$customer['customer_lastname']},<br><br>"
                    . "Here is your OTP code for password reset:<br><br>"
                    . "<b>{$otp}</b><br><br>"
                    . "This code will expire in 10 minutes.<br><br>Best regards,<br>Happy Sprays Team";
                $mail->send();
                $msg = "✅ OTP sent to your email. Please check your inbox.";
                $_SESSION['reset_customer_id'] = $customer['customer_id'];
                $_SESSION['reset_email'] = $customer['customer_email'];
                $step = 2;
            } catch (Exception $e) {
                $msg = "❌ Could not send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $msg = "❌ Account not found.";
        }
    }
}

// Step 2: Enter OTP
elseif (isset($_POST['step']) && $_POST['step'] == '2') {
    $otp = trim($_POST['otp']);
    if ($otp === "") {
        $msg = "⚠️ Please enter the OTP.";
        $step = 2;
    } else {
        $customer_id = $_SESSION['reset_customer_id'] ?? null;
        if ($customer_id) {
            // DEBUG: Show values being checked
            echo '<div style="color:red;font-size:13px;">DEBUG: customer_id=' . htmlspecialchars($customer_id) . ', otp=' . htmlspecialchars($otp) . '</div>';
            // DEBUG: List all unused, unexpired OTPs for this customer
            $stmt = $conn->prepare("SELECT otp, expires_at, used FROM password_resets WHERE customer_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC");
            $stmt->execute([$customer_id]);
            $allOtps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<div style="color:red;font-size:13px;">DEBUG: All unused, unexpired OTPs: ' . htmlspecialchars(json_encode($allOtps)) . '</div>';

            // Fetch the OTP row (don't rely on DB NOW() to avoid timezone mismatches)
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE customer_id = ? AND otp = ? AND used = 0 ORDER BY id DESC LIMIT 1");
            $stmt->execute([$customer_id, $otp]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            // DEBUG: Show query result
            echo '<div style="color:red;font-size:13px;">DEBUG: Query result: ' . htmlspecialchars(json_encode($reset)) . '</div>';
            if ($reset) {
                $expiresAtTs = strtotime($reset['expires_at']);
                $nowTs = time();
                if ($expiresAtTs >= $nowTs) {
                    // Mark OTP as used
                    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                    $stmt->execute([$reset['id']]);
                    $_SESSION['otp_verified'] = true;
                    $msg = "✅ OTP verified. Please enter your new password.";
                    $step = 3;
                } else {
                    $msg = "❌ Invalid or expired OTP.";
                    $step = 2;
                }
            } else {
                $msg = "❌ Invalid or expired OTP.";
                $step = 2;
            }
        } else {
            $msg = "❌ Session expired. Please start again.";
            $step = 1;
        }
    }
}

// Step 3: Enter new password
elseif (isset($_POST['step']) && $_POST['step'] == '3') {
    $new_password      = trim($_POST['new_password']);
    $confirm_password  = trim($_POST['confirm_password']);
    $customer_id = $_SESSION['reset_customer_id'] ?? null;
    if ($new_password === "" || $confirm_password === "") {
        $msg = "⚠️ Please fill in all fields.";
        $step = 3;
    } elseif ($new_password !== $confirm_password) {
        $msg = "❌ Passwords do not match.";
        $step = 3;
    } elseif (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $msg = "❌ OTP not verified.";
        $step = 1;
    } else {
        // ✅ Validate password strength (Uppercase + Special + Min 6 chars)
        if (!preg_match('/^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/', $new_password)) {
            $msg = "❌ Password must be at least 6 characters and include an uppercase letter and special character.";
            $step = 3;
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customers SET customer_password = ? WHERE customer_id = ?");
            $stmt->execute([$hashed, $customer_id]);
            $msg = "✅ Password successfully updated! You can now login.";
            unset($_SESSION['reset_customer_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            $step = 4;
        }
    }
}
elseif (isset($_SESSION['reset_customer_id']) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified']) {
    $step = 3;
}
else {
    $step = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Happy Sprays</title>
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
.reset-container {
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
.reset-left {
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
.reset-left::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -100px;
    right: -100px;
}
.reset-left::after {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    bottom: -50px;
    left: -50px;
}
.reset-left h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    margin-bottom: 20px;
    color: #fff;
    position: relative;
    z-index: 1;
}
.reset-left p {
    font-size: 16px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
    position: relative;
    z-index: 1;
}

.reset-right {
    background: #fff;
    padding: 80px 50px;
}
.reset-header {
    margin-bottom: 40px;
}
.reset-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 8px;
    color: #000;
}
.reset-header p {
    color: #666;
    font-size: 15px;
}

.msg {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    text-align: left;
}
.msg.error {
    background: #ffebee;
    color: #c62828;
    border-left: 4px solid #c62828;
}
.msg.success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #2e7d32;
}

.form-group {
    margin-bottom: 24px;
    position: relative;
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
    padding: 14px 44px 14px 18px;
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
}
.toggle-password:hover {
    color: #000;
}
.eye-icon {
    width: 24px;
    height: 24px;
    display: inline-block;
}

.password-hint {
    font-size: 12px;
    color: #777;
    margin-top: 6px;
    font-style: italic;
}
.password-strength {
    font-size: 13px;
    margin-top: 6px;
    color: #c62828;
}
.valid { color: #2e7d32 !important; }

.reset-btn {
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
}
.reset-btn:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.back-link {
    text-align: center;
    margin-top: 28px;
    color: #666;
    font-size: 15px;
}
.back-link a {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}
.back-link a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    body { padding: 20px; }
    .reset-container { grid-template-columns: 1fr; }
    .reset-left { padding: 60px 40px; }
    .reset-left h1 { font-size: 36px; }
    .reset-right { padding: 60px 40px; }
    .reset-header h2 { font-size: 28px; }
}
</style>
</head>
<body>

<div class="reset-container">
    <div class="reset-left">
        <h1>Happy Sprays</h1>
        <p>Forgot your password? Don’t worry! Reset it easily and regain access to your account in just a few steps.</p>
    </div>

    <div class="reset-right">
        <div class="reset-header">
            <h2>Reset Password</h2>
            <p>Enter your username or email and create a new password.</p>
        </div>

        <?php if($msg): ?>
            <p class="msg <?= strpos($msg,'✅')!==false ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>


        <!-- Step 1: Enter email/username -->
        <?php if ($step == 1): ?>
        <form method="post" id="resetForm">
            <input type="hidden" name="step" value="1">
            <div class="form-group">
                <label for="username_or_email">Email or Username</label>
                <input type="text" id="username_or_email" name="username_or_email" placeholder="Enter your email or username" required value="<?= htmlspecialchars($username_or_email ?? '') ?>">
            </div>
            <button type="submit" class="reset-btn">Send OTP</button>
        </form>
        <?php elseif ($step == 2): ?>
        <!-- Step 2: Enter OTP -->
        <form method="post" id="otpForm">
            <input type="hidden" name="step" value="2">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" id="otp" name="otp" placeholder="Enter the OTP sent to your email" required maxlength="6">
            </div>
            <button type="submit" class="reset-btn">Verify OTP</button>
        </form>
        <div style="margin-top:18px; font-size:14px; color:#555;">
            We've sent a 6-digit verification code to:<br>
            <b><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></b>
        </div>
        <?php elseif ($step == 3): ?>
        <!-- Step 3: Enter new password -->
        <form method="post" id="resetForm">
            <input type="hidden" name="step" value="3">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    <span class="toggle-password" onclick="togglePassword('new_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
                <div class="password-hint">Must include at least 6 characters, one uppercase, and one special character.</div>
                <div class="password-strength" id="strengthMessage"></div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
            </div>
            <button type="submit" class="reset-btn">Update Password</button>
        </form>
        <?php elseif ($step == 4): ?>
        <div class="msg success">✅ Password successfully updated! You can now <a href="customer_login.php">login</a>.</div>
        <?php endif; ?>

        <div class="back-link">
            <a href="customer_login.php">← Back to Login</a>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId, iconElement) {
    const field = document.getElementById(fieldId);
    const svg = iconElement.querySelector('svg');
    if (field.type === 'password') {
        field.type = 'text';
        svg.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    } else {
        field.type = 'password';
        svg.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    }
}

// 🧠 Password Strength Checker
const passwordInput = document.getElementById('new_password');
const strengthMessage = document.getElementById('strengthMessage');
const submitBtn = document.querySelector('.reset-btn');

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    const requirements = [
        { regex: /.{6,}/, message: "At least 6 characters" },
        { regex: /[A-Z]/, message: "One uppercase letter" },
        { regex: /[@$!%*?&]/, message: "One special character" }
    ];

    let valid = true;
    let messages = requirements.map(req => {
        const ok = req.regex.test(value);
        if (!ok) valid = false;
        return `<div class="${ok ? 'valid' : ''}">${ok ? '✔️' : '❌'} ${req.message}</div>`;
    }).join("");

    strengthMessage.innerHTML = messages;
    submitBtn.disabled = !valid;
});
</script>

</body>
</html>
