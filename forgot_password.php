<?php
require_once 'classes/database.php';
session_start();

$conn = Database::getInstance()->getConnection();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $new_password      = trim($_POST['new_password']);
    $confirm_password  = trim($_POST['confirm_password']);

    if ($username_or_email === "" || $new_password === "" || $confirm_password === "") {
        $msg = "⚠️ Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $msg = "❌ Passwords do not match.";
    } else {
        // ✅ Validate password strength (Uppercase + Special + Min 6 chars)
        if (!preg_match('/^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/', $new_password)) {
            $msg = "❌ Password must be at least 6 characters and include an uppercase letter and special character.";
        } else {
            $stmt = $conn->prepare("
                SELECT customer_id 
                FROM customers 
                WHERE customer_username = ? OR customer_email = ? 
                LIMIT 1
            ");
            $stmt->execute([$username_or_email, $username_or_email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE customers SET customer_password = ? WHERE customer_id = ?");
                $stmt->execute([$hashed, $customer['customer_id']]);
                $msg = "✅ Password successfully updated! You can now login.";
            } else {
                $msg = "❌ Account not found.";
            }
        }
    }
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

        <form method="post" id="resetForm">
            <div class="form-group">
                <label for="username_or_email">Email or Username</label>
                <input type="text" id="username_or_email" name="username_or_email" placeholder="Enter your email or username" required>
            </div>

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
