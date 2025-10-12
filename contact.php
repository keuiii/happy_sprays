<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $result = $db->saveContactMessage($name, $email, $message);
        
        if ($result) {
            $success = "Thank you for contacting us! We'll get back to you soon.";
            // Clear form
            $_POST = [];
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #fff;
    color: #000;
}

.top-nav {
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 20px;
    text-align: center;
}

.top-nav h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #000;
    text-decoration: none;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.contact-info {
    padding: 30px;
}

.contact-info h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 20px;
}

.contact-info p {
    line-height: 1.8;
    color: #666;
    margin-bottom: 30px;
}

.info-item {
    display: flex;
    align-items: start;
    gap: 15px;
    margin-bottom: 20px;
}

.info-icon {
    width: 40px;
    height: 40px;
    background: #000;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-content h4 {
    margin-bottom: 5px;
}

.info-content p {
    color: #666;
    margin: 0;
}

.contact-form {
    background: #f9f9f9;
    padding: 40px;
    border-radius: 10px;
}

.form-title {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    margin-bottom: 20px;
}

.error-message {
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid #c62828;
}

.success-message {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid #2e7d32;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-family: inherit;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #000;
}

.submit-btn {
    width: 100%;
    padding: 15px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
}

.submit-btn:hover {
    background: #333;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}
/* Footer */
footer {
    background: #000;
    border-top: 1px solid #000;
    padding: 40px 20px;
    text-align: center;
    font-size: 14px;
    color: #fff;
    margin-top: 60px;
}

.footer-columns {
    display: flex;
    justify-content: center;
    gap: 80px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.footer-columns h4 {
    font-size: 14px;
    margin-bottom: 12px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-columns a {
    display: block;
    text-decoration: none;
    color: #ccc;
    margin: 6px 0;
    font-size: 13px;
    transition: color 0.3s;
}

.footer-columns a:hover { color: #fff; }

.social-icons { 
    margin-top: 20px; 
}

.social-icons a {
    margin: 0 10px;
    color: #ccc;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.social-icons a:hover { color: #fff; }

footer p {
    margin-top: 20px;
    color: #999;
    font-size: 12px;
}
</style>
</head>
<body>

<div class="top-nav">
    <h1>Happy Sprays</h1>
</div>

<div class="container">
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <div class="contact-grid">
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <p>Have questions about our products? Need help with your order? We're here to help!</p>
            
            <div class="info-item">
                <div class="info-icon">📧</div>
                <div class="info-content">
                    <h4>Email</h4>
                    <p>happysprays@gmail.com</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📱</div>
                <div class="info-content">
                    <h4>Phone</h4>
                    <p>+63 XXX XXX XXXX</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📍</div>
                <div class="info-content">
                    <h4>Location</h4>
                    <p>Tanauan City, Batangas, Philippines</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🕐</div>
                <div class="info-content">
                    <h4>Business Hours</h4>
                    <p>Monday - Saturday: 9AM - 6PM<br>Sunday: Closed</p>
                </div>
            </div>
        </div>
        
        <div class="contact-form">
            <h3 class="form-title">Send us a Message</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="contact.php">
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" 
                              name="message" 
                              required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" name="send_message" class="submit-btn">
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-columns">
        <div>
            <h4>Company</h4>
            <a href="about.php">About</a>
            <a href="reviews.php">Reviews</a>
        </div>
        <div>
            <h4>Customer Service</h4>
            <a href="faq.php">FAQ</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/thethriftbytf">Facebook</a>
        <a href="https://www.instagram.com/thehappysprays/">Instagram</a>
    </div>
    <p>© 2025 Happy Sprays. All rights reserved.</p>
</footer>

</body>
</html>