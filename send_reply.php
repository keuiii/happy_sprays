<?php
session_start();
require_once 'classes/database.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate inputs
if (!isset($_POST['customer_email']) || !isset($_POST['reply_message']) || !isset($_POST['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$customerEmail = trim($_POST['customer_email']);
$replyMessage = trim($_POST['reply_message']);
$messageId = intval($_POST['message_id']);

if (empty($customerEmail) || empty($replyMessage)) {
    echo json_encode(['success' => false, 'message' => 'Email and message cannot be empty']);
    exit;
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'happysprays01@gmail.com';
    $mail->Password = 'fdgm grgn mlsd ptsb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Sender and recipient
    $mail->setFrom('happysprays01@gmail.com', 'Happy Sprays Admin');
    $mail->addAddress($customerEmail);
    $mail->addReplyTo('happysprays01@gmail.com', 'Happy Sprays');
    
    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Reply from Happy Sprays';
    
    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #f9f9f9;
            }
            .header {
                background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .content {
                background: white;
                padding: 30px;
                border-radius: 0 0 10px 10px;
            }
            .message-box {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                border-left: 4px solid #3b82f6;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Happy Sprays</h1>
                <p>Thank you for contacting us!</p>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>Thank you for your message. Here is our response:</p>
                <div class="message-box">
                    ' . nl2br(htmlspecialchars($replyMessage)) . '
                </div>
                <p>If you have any more questions, feel free to reach out to us.</p>
                <p>Best regards,<br><strong>Happy Sprays Team</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated reply from Happy Sprays</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    $mail->Body = $htmlMessage;
    $mail->AltBody = strip_tags($replyMessage);
    
    // Send email
    $mail->send();
    
    // Mark message as read
    $db->updateContactMessageStatus($messageId, 'read');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reply sent successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send email: ' . $mail->ErrorInfo
    ]);
}
?>
