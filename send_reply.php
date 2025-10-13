<?php
// send_reply.php
// Clean start - no BOM, no whitespace before this
session_start();
require_once 'classes/database.php';

// Only load PHPMailer if files exist - preserve your existing mail behavior
$phpmailerExists = file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php');

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    // Ensure admin is logged in (uses your existing DB wrapper check)
    if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Use admin_id from session if set; fallback to 3 (your hard-coded admin id)
    $adminId = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 3;

    // Validate POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Collect inputs
    $messageId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $customerEmail = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $replyMessage = isset($_POST['reply_message']) ? trim($_POST['reply_message']) : '';

    // Validate required fields
    if (empty($messageId) || empty($customerEmail) || empty($replyMessage)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Validate email
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Fetch original message to ensure it exists and to get original name/message
    $originalMessage = $db->fetch("SELECT * FROM contact_messages WHERE id = ?", [$messageId]);
    if (!$originalMessage) {
        echo json_encode(['success' => false, 'message' => 'Original message not found']);
        exit;
    }

    // Insert new reply into contact_replies (separate row)
    $insertOk = $db->update(
        "INSERT INTO contact_replies (message_id, admin_id, reply_message) VALUES (?, ?, ?)",
        [$messageId, $adminId, $replyMessage]
    );

    if ($insertOk === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to save reply']);
        exit;
    }

    // Update contact_messages to mark as read only (removed reply_at column reference)
    $db->update(
        "UPDATE contact_messages SET status = 'read' WHERE id = ?",
        [$messageId]
    );

    // Fetch the newly inserted reply (most recent reply for this message by this admin)
    $latestReply = $db->fetch(
        "SELECT reply_id, message_id, admin_id, reply_message, created_at 
         FROM contact_replies 
         WHERE message_id = ? AND admin_id = ? 
         ORDER BY created_at DESC 
         LIMIT 1",
        [$messageId, $adminId]
    );

    // Prepare response skeleton
    $response = [
        'success' => true,
        'message' => 'Reply saved successfully',
        'email_sent' => false,
        'email_error' => '',
        'reply' => [
            'reply_id' => $latestReply['reply_id'] ?? null,
            'message_id' => $messageId,
            'admin_id' => $adminId,
            'message' => $replyMessage,
            'created_at' => $latestReply['created_at'] ?? date('Y-m-d H:i:s'),
            'formatted_time' => date('M d, Y \a\t g:i A', strtotime($latestReply['created_at'] ?? date('Y-m-d H:i:s')))
        ]
    ];

    // Try to send email only if PHPMailer exists (kept faithful to your original logic)
    if ($phpmailerExists) {
        try {
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';

            // Create new instance with namespace
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings - keep your values
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'happyspray@happyspray.shop';
            $mail->Password   = 'JANJANbuen@5';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->Timeout    = 30;

            // Recipients
            $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays Support');
            $mail->addAddress($customerEmail, $originalMessage['name']);
            $mail->addReplyTo('happyspray@happyspray.shop', 'Happy Sprays Support');

            // Anti-spam headers
            $mail->addCustomHeader('X-Mailer', 'PHPMailer');
            $mail->addCustomHeader('X-Priority', '3');
            $mail->Priority = 3;

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Response to your inquiry - Happy Sprays';
            $mail->CharSet = 'UTF-8';

            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

            $mail->Body = "
            <html>
            <head>
                <style>
                    body { 
                        font-family: Arial, Helvetica, sans-serif; 
                        line-height: 1.6; 
                        color: #333; 
                        margin: 0;
                        padding: 20px;
                        background-color: #f4f4f4;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 0 auto; 
                        background: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    .header { 
                        background: #000000; 
                        color: #ffffff; 
                        padding: 20px; 
                        text-align: center; 
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                        font-weight: normal;
                    }
                    .content { 
                        padding: 30px; 
                    }
                    .reply-box {
                        background: #f9f9f9;
                        border-left: 3px solid #000;
                        padding: 15px;
                        margin: 20px 0;
                    }
                    .footer { 
                        text-align: center; 
                        padding: 20px; 
                        font-size: 12px; 
                        color: #999;
                        border-top: 1px solid #eee;
                    }
                    .unsubscribe {
                        font-size: 11px;
                        color: #999;
                        margin-top: 10px;
                    }
                    .unsubscribe a {
                        color: #666;
                        text-decoration: none;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Happy Sprays</h1>
                    </div>
                    <div class='content'>
                        <p>Hi " . htmlspecialchars($originalMessage['name']) . ",</p>
                        <p>Thank you for contacting us. Here is our response:</p>
                        
                        <div class='reply-box'>
                            " . nl2br(htmlspecialchars($replyMessage)) . "
                        </div>
                        
                        <p>Your original message:<br>
                        <em>" . nl2br(htmlspecialchars(substr($originalMessage['message'], 0, 200))) . (strlen($originalMessage['message']) > 200 ? '...' : '') . "</em></p>
                        
                        <p>If you have any other questions, feel free to reply to this email.</p>
                        
                        <p>Best regards,<br>Happy Sprays Team</p>
                    </div>
                    <div class='footer'>
                        <p>Happy Sprays &copy; " . date('Y') . "</p>
                        <p class='unsubscribe'>You received this email because you contacted us through our website.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "Hi " . $originalMessage['name'] . ",\n\nThank you for contacting us. Here is our response:\n\n" . $replyMessage . "\n\nYour original message:\n" . $originalMessage['message'] . "\n\nBest regards,\nHappy Sprays Team";

            $mail->send();
            $response['email_sent'] = true;
            $response['message'] = 'Reply saved and email delivered successfully';

        } catch (Exception $e) {
            // Save reply succeeded; email failed
            $response['email_error'] = $e->getMessage();
            $response['email_sent'] = false;
        }
    } else {
        $response['email_error'] = 'PHPMailer not found';
    }

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}