<?php
session_start();
require_once 'classes/database.php';

// Move these to the top of the file
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = Database::getInstance();

// Check if user came from registration
if (!isset($_SESSION['pending_email'])) {
    header("Location: customer_register.php");
    exit;
}

$error = '';
$success = '';
$email = $_SESSION['pending_email'];

// Check if OTP was just resent
$otp_resent = isset($_SESSION['otp_resent']) ? $_SESSION['otp_resent'] : false;
if ($otp_resent) {
    unset($_SESSION['otp_resent']);
}

// Get OTP expiry time for countdown
$customer = $db->fetch("SELECT otp_expires FROM customers WHERE customer_email = ?", [$email]);
$otp_expires = $customer ? $customer['otp_expires'] : null;

// Handle OTP verification
// Accept either an explicit submit (verify_otp) or an auto-submitted form that includes only 'otp'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['verify_otp']) || (isset($_POST['otp']) && !isset($_POST['resend_otp'])))) {
    $entered_otp = trim($_POST['otp']);
    
    if (empty($entered_otp)) {
        $error = "Please enter the OTP code.";
    } else {
        try {
            // Get customer with OTP
            $customer = $db->fetch(
                "SELECT * FROM customers WHERE customer_email = ? AND verification_code = ? LIMIT 1",
                [$email, $entered_otp]
            );
            
            if (!$customer) {
                $error = "Invalid OTP code. Please check and try again.";
            } else {
                // Check if OTP has expired
                $current_time = date("Y-m-d H:i:s");
                if ($current_time > $customer['otp_expires']) {
                    $error = "OTP code has expired. Please register again.";
                    
                    // Optionally delete the unverified account
                    $db->delete("DELETE FROM customers WHERE customer_email = ?", [$email]);
                    unset($_SESSION['pending_email']);
                    unset($_SESSION['pending_customer_id']);
                } else {
                    // OTP is valid! Verify the account
                    $db->update(
                        "UPDATE customers SET is_verified = 1, verification_code = NULL, otp_expires = NULL WHERE customer_email = ?",
                        [$email]
                    );
                    
                    $success = "Email verified successfully! You can now login.";
                    
                    // Clear session
                    unset($_SESSION['pending_email']);
                    unset($_SESSION['pending_customer_id']);
                    
                    // Redirect to login after 2 seconds
                    header("refresh:2;url=customer_login.php");
                }
            }
        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            $error = "Verification failed. Please try again.";
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    try {
        // Generate new OTP
        $new_otp = rand(100000, 999999);
        $new_expiry = date("Y-m-d H:i:s", strtotime("+100 seconds"));
        
        // Update customer record
        $db->update(
            "UPDATE customers SET verification_code = ?, otp_expires = ? WHERE customer_email = ?",
            [$new_otp, $new_expiry, $email]
        );
        
        // Get customer info
        $customer = $db->fetch("SELECT * FROM customers WHERE customer_email = ?", [$email]);
        
        // Send new OTP email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'happyspray@happyspray.shop';
        $mail->Password   = 'JANJANbuen@5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays');
        $mail->addAddress($email, $customer['customer_firstname'] . ' ' . $customer['customer_lastname']);

        $mail->isHTML(true);
        $mail->Subject = 'New OTP Code - Happy Sprays';
        $mail->Body    = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>New Verification Code</h2>
            <p>Hello <strong>{$customer['customer_firstname']} {$customer['customer_lastname']}</strong>,</p>
            <p>Here is your new OTP code:</p>
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
        $success = "New OTP code sent to your email!";
        
        // Set flag to restart timer
        $_SESSION['otp_resent'] = true;
        
    } catch (Exception $e) {
        $error = "Failed to resend OTP. Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.verify-container {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
    padding: 50px 40px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin-bottom: 10px;
    color: #000;
}

.subtitle {
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.email-display {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 30px;
    font-weight: 600;
    color: #000;
    border: 1px solid #e0e0e0;
}

.error-message {
    background: #fff;
    color: #d32f2f;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #d32f2f;
}

.success-message {
    background: #fff;
    color: #000;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #000;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    text-align: center;
    color: #000;
    font-size: 14px;
}

.otp-input {
    width: 100%;
    padding: 15px;
    border: 2px solid #000;
    border-radius: 5px;
    font-size: 24px;
    text-align: center;
    letter-spacing: 10px;
    font-weight: 700;
}

.otp-input:focus {
    outline: none;
    border-color: #000;
    box-shadow: 0 0 0 1px #000;
}

.verify-btn {
    width: 100%;
    padding: 15px;
    background: #000;
    color: #fff;
    border: 2px solid #000;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    margin-bottom: 15px;
}

.verify-btn:hover {
    background: #333;
    border-color: #333;
}

.resend-btn {
    width: 100%;
    padding: 15px;
    background: #fff;
    color: #000;
    border: 2px solid #000;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.resend-btn:hover {
    background: #000;
    color: #fff;
}

.helper-text {
    margin-top: 20px;
    color: #666;
    font-size: 14px;
}

.helper-text a {
    color: #000;
    text-decoration: none;
    font-weight: 600;
}

.helper-text a:hover {
    text-decoration: underline;
}

.countdown {
    margin-top: 10px;
    color: #999;
    font-size: 13px;
}
</style>
</head>
<body>

<div class="verify-container">
    <h1>Verify Your Email</h1>
    <p class="subtitle">We've sent a 6-digit verification code to:</p>
    <div class="email-display"><?= htmlspecialchars($email) ?></div>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="verify_otp.php">
        <div class="form-group">
            <label for="otp">Enter OTP Code</label>
            <input type="text" 
                   id="otp" 
                   name="otp" 
                   class="otp-input"
                   placeholder="000000"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   required
                   autofocus>
        </div>
        
        <button type="submit" name="verify_otp" class="verify-btn">
            Verify Email
        </button>
    </form>
    
    <form method="POST" action="verify_otp.php">
        <button type="submit" name="resend_otp" class="resend-btn">
            Resend OTP Code
        </button>
    </form>
    
    <div class="helper-text">
        <p>Didn't receive the code? Check your spam folder.</p>
        <p class="countdown" id="countdown">Code expires in <span id="timer">100</span> seconds</p>
        <p style="margin-top: 15px;">
            <a href="customer_register.php">← Back to Registration</a>
        </p>
    </div>
</div>

<script>
// Auto-focus and format OTP input
const otpInput = document.getElementById('otp');

otpInput.addEventListener('input', function(e) {
    // Only allow numbers
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Auto-submit when 6 digits entered
otpInput.addEventListener('keyup', function(e) {
    if (this.value.length === 6) {
        // Prefer clicking the submit button so the button name/value is included in POST
        const btn = this.form.querySelector('button[name="verify_otp"]');
        if (btn) {
            btn.click();
        } else {
            this.form.submit();
        }
    }
});

// Countdown timer
let timeLeft = 100;
const timerElement = document.getElementById('timer');
const countdownElement = document.getElementById('countdown');

<?php if ($otp_expires): ?>
// Calculate actual time left from server
const expiryTime = new Date("<?= $otp_expires ?>").getTime();
const currentTime = new Date().getTime();
const serverTimeLeft = Math.floor((expiryTime - currentTime) / 1000);

// Use server time if valid, otherwise use default 100 seconds
if (serverTimeLeft > 0 && serverTimeLeft <= 100) {
    timeLeft = serverTimeLeft;
}
<?php endif; ?>

// Update timer display
function updateTimer() {
    if (timeLeft <= 0) {
        timerElement.textContent = '0';
        countdownElement.innerHTML = '<span style="color: #d32f2f; font-weight: 600;">Code has expired</span>';
        countdownElement.style.color = '#d32f2f';
        return;
    }
    
    timerElement.textContent = timeLeft;
    timeLeft--;
    setTimeout(updateTimer, 1000);
}

// Start countdown
updateTimer();

<?php if ($otp_resent): ?>
// Restart timer when new code is sent
timeLeft = 100;
countdownElement.innerHTML = 'Code expires in <span id="timer">100</span> seconds';
countdownElement.style.color = '#999';
updateTimer();
<?php endif; ?>
</script>

</body>
</html>