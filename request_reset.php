<?php
require_once 'classes/database.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    if ($username_or_email === "") {
        $msg = "⚠️ Please enter your email or username.";
    } else {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT customer_id, customer_email FROM customers WHERE customer_username = ? OR customer_email = ? LIMIT 1");
        $stmt->execute([$username_or_email, $username_or_email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $conn->prepare("INSERT INTO password_resets (customer_id, otp, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$customer['customer_id'], $otp, $expires_at]);

            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.example.com'; // Change to your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'your@email.com'; // Change to your SMTP username
                $mail->Password = 'yourpassword';   // Change to your SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('your@email.com', 'Happy Sprays');
                $mail->addAddress($customer['customer_email']);
                $mail->Subject = 'Your Happy Sprays Password Reset OTP';
                $mail->Body    = "Your OTP for password reset is: <b>$otp</b>\n\nThis OTP will expire in 15 minutes.";
                $mail->isHTML(true);

                $mail->send();
                $msg = "✅ OTP sent to your email. Please check your inbox.";
            } catch (Exception $e) {
                $msg = "❌ Could not send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $msg = "❌ Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Password Reset - Happy Sprays</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: #fff; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); max-width: 400px; width: 100%; padding: 40px; border: 2px solid #000; }
        h2 { font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 18px; color: #000; }
        .msg { padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; text-align: left; }
        .msg.error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .msg.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 10px; font-weight: 600; color: #000; font-size: 14px; }
        input { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; background: #fafafa; font-family: 'Poppins', sans-serif; }
        input:focus { outline: none; border-color: #000; background: #fff; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
        .btn { width: 100%; padding: 16px; background: #000; color: #fff; border: 2px solid #000; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn:hover { background: #fff; color: #000; }
        .back-link { text-align: center; margin-top: 28px; color: #666; font-size: 15px; }
        .back-link a { color: #000; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Request Password Reset</h2>
    <?php if($msg): ?>
        <p class="msg <?= strpos($msg,'✅')!==false ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="username_or_email">Email or Username</label>
            <input type="text" id="username_or_email" name="username_or_email" placeholder="Enter your email or username" required>
        </div>
        <button type="submit" class="btn">Send OTP</button>
    </form>
    <div class="back-link">
        <a href="customer_login.php">← Back to Login</a>
    </div>
</div>
</body>
</html>
