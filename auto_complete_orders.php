<?php
require_once 'classes/database.php';

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = Database::getIns

$db = Database::getInstance();

// ============================================================================
// EMAIL CONFIGURATION - CHANGE THESE VALUES
// ============================================================================
define('SMTP_HOST', 'smtp.hostinger.com');           // SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'happyspray@happyspray.shop');
define('SMTP_PASSWORD', 'JANJANbuen@5');
define('SMTP_FROM_EMAIL', 'happyspray@happyspray.shop');
define('SMTP_FROM_NAME', 'Happy Sprays');
define('SMTP_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);

// Function to send order auto-completion email using PHPMailer
function sendAutoCompleteEmail($orderId, $customerEmail, $customerName) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'happyspray@happyspray.shop';
        $mail->Password   = 'JANJANbuen@5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('Happyspray@happyspray.shop', 'Happy Sprays Admin');
        $mail->addAddress($customerEmail, $customerName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Automatically Marked as Received - Happy Sprays';
        $mail->CharSet = 'UTF-8';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: #ffffff;
                }
                .header { 
                    background: #000000; 
                    color: #ffffff; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                    letter-spacing: 2px;
                }
                .content { 
                    background: #f9f9f9; 
                    padding: 40px 30px; 
                    border: 1px solid #e0e0e0; 
                }
                .content h2 {
                    color: #000;
                    margin-top: 0;
                    margin-bottom: 20px;
                    font-size: 24px;
                }
                .highlight { 
                    background: #d1fae5; 
                    padding: 20px; 
                    border-left: 4px solid #10b981; 
                    margin: 25px 0; 
                    border-radius: 4px;
                }
                .highlight p {
                    margin: 8px 0;
                    color: #065f46;
                }
                .footer { 
                    text-align: center; 
                    padding: 30px 20px; 
                    font-size: 12px; 
                    color: #666;
                    background: #f5f5f5;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 30px;
                    background: #000000;
                    color: #ffffff !important;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>HAPPY SPRAYS</h1>
                </div>
                <div class='content'>
                    <h2>✅ Order Completed</h2>
                    <p>Hello <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
                    <p>Your order #" . $orderId . " has been automatically marked as <strong>received</strong> since it was delivered more than 3 days ago.</p>
                    
                    <div class='highlight'>
                        <p style='margin: 0 0 10px 0;'><strong>📦 Order Number:</strong> #" . $orderId . "</p>
                        <p style='margin: 0 0 10px 0;'><strong>Status:</strong> Received ✓</p>
                        <p style='margin: 0;'>We hope you're enjoying your purchase!</p>
                    </div>
                    
                    <p>If you have any issues with your order or if you haven't received it yet, please don't hesitate to contact our support team immediately.</p>
                    
                    <p>Thank you for shopping with Happy Sprays!</p>
                    
                    <div style='text-align: center;'>
                        <a href='http://yourdomain.com/contact.php' class='btn'>Contact Support</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p style='margin-top: 15px;'>&copy; " . date('Y') . " Happy Sprays. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Hello " . $customerName . ",\n\n";
        $mail->AltBody .= "Your order #" . $orderId . " has been automatically marked as received.\n";
        $mail->AltBody .= "We hope you're enjoying your purchase!\n\n";
        $mail->AltBody .= "Thank you for shopping with Happy Sprays.";

        $mail->send();
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        error_log("Auto-complete email error: {$mail->ErrorInfo}");
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// Log file for tracking
$logFile = __DIR__ . '/logs/auto_complete_orders.log';
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Start logging
$logMessage = "\n" . date('Y-m-d H:i:s') . " - Auto-complete orders script started\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

try {
    // Find orders that are "out for delivery" for more than 3 days
    $query = "
        SELECT o.order_id, o.customer_id, o.order_status, o.status_updated_at, o.o_created_at,
               c.customer_email, c.customer_firstname, c.customer_lastname
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        WHERE LOWER(TRIM(o.order_status)) = 'out for delivery'
        AND (
            (o.status_updated_at IS NOT NULL AND DATEDIFF(NOW(), o.status_updated_at) >= 3)
            OR
            (o.status_updated_at IS NULL AND DATEDIFF(NOW(), o.o_created_at) >= 3)
        )
    ";
    
    $ordersToComplete = $db->fetchAll($query);
    
    $completedCount = 0;
    $failedCount = 0;
    
    foreach ($ordersToComplete as $order) {
        $orderId = $order['order_id'];
        $customerName = $order['customer_firstname'] . ' ' . $order['customer_lastname'];
        $customerEmail = $order['customer_email'];
        
        // Update order status to "received"
        $updateResult = $db->updateOrderStatus($orderId, 'received');
        
        if ($updateResult['success']) {
            // Send notification email
            $emailResult = sendAutoCompleteEmail($orderId, $customerEmail, $customerName);
            
            $completedCount++;
            $logMessage = "✓ Order #$orderId auto-completed for customer: $customerName";
            if ($emailResult['success']) {
                $logMessage .= " (Email sent)";
            } else {
                $logMessage .= " (Email failed: " . $emailResult['error'] . ")";
            }
            $logMessage .= "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            echo "Order #$orderId marked as received\n";
        } else {
            $failedCount++;
            $logMessage = "✗ Failed to auto-complete Order #$orderId: " . $updateResult['message'] . "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            echo "Failed to update Order #$orderId\n";
        }
    }
    
    // Summary
    $summary = "\nSummary: $completedCount orders completed, $failedCount failed\n";
    $summary .= str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $summary, FILE_APPEND);
    
    echo "\nScript completed: $completedCount orders auto-completed, $failedCount failed\n";
    
} catch (Exception $e) {
    $errorMessage = "ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
}

$logMessage = date('Y-m-d H:i:s') . " - Auto-complete orders script finished\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);<?php

require_once 'classes/database.php';

$db = Database::getInstance();

// Function to send order auto-completion email
function sendAutoCompleteEmail($orderId, $customerEmail, $customerName) {
    $subject = 'Order Automatically Marked as Received';
    $emailMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #000; color: #fff; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .highlight { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Happy Sprays</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($customerName) . ",</h2>
                <p>Your order #" . $orderId . " has been automatically marked as <strong>received</strong> since it was delivered more than 3 days ago.</p>
                <div class='highlight'>
                    <p><strong>📦 Order Status:</strong> Received</p>
                    <p>We hope you're enjoying your purchase!</p>
                </div>
                <p>If you have any issues with your order, please don't hesitate to contact our support team.</p>
                <p>Thank you for shopping with Happy Sprays!</p>
            </div>
            <div class='footer'>
                <p>This is an automated email. Please do not reply.</p>
                <p>&copy; " . date('Y') . " Happy Sprays. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Happy Sprays <noreply@happysprays.com>' . "\r\n";

    return mail($customerEmail, $subject, $emailMessage, $headers);
}

// Log file for tracking
$logFile = __DIR__ . '/logs/auto_complete_orders.log';
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Start logging
$logMessage = "\n" . date('Y-m-d H:i:s') . " - Auto-complete orders script started\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

try {
    // Find orders that are "out for delivery" for more than 3 days
    $query = "
        SELECT o.order_id, o.customer_id, o.order_status, o.status_updated_at, o.o_created_at,
               c.customer_email, c.customer_firstname, c.customer_lastname
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        WHERE LOWER(TRIM(o.order_status)) = 'out for delivery'
        AND (
            (o.status_updated_at IS NOT NULL AND DATEDIFF(NOW(), o.status_updated_at) >= 3)
            OR
            (o.status_updated_at IS NULL AND DATEDIFF(NOW(), o.o_created_at) >= 3)
        )
    ";
    
    $ordersToComplete = $db->fetchAll($query);
    
    $completedCount = 0;
    $failedCount = 0;
    
    foreach ($ordersToComplete as $order) {
        $orderId = $order['order_id'];
        $customerName = $order['customer_firstname'] . ' ' . $order['customer_lastname'];
        $customerEmail = $order['customer_email'];
        
        // Update order status to "received"
        $updateResult = $db->updateOrderStatus($orderId, 'received');
        
        if ($updateResult['success']) {
            // Send notification email
            $emailSent = sendAutoCompleteEmail($orderId, $customerEmail, $customerName);
            
            $completedCount++;
            $logMessage = "✓ Order #$orderId auto-completed for customer: $customerName";
            if ($emailSent) {
                $logMessage .= " (Email sent)";
            } else {
                $logMessage .= " (Email failed)";
            }
            $logMessage .= "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            echo "Order #$orderId marked as received\n";
        } else {
            $failedCount++;
            $logMessage = "✗ Failed to auto-complete Order #$orderId: " . $updateResult['message'] . "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            echo "Failed to update Order #$orderId\n";
        }
    }
    
    // Summary
    $summary = "\nSummary: $completedCount orders completed, $failedCount failed\n";
    $summary .= str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $summary, FILE_APPEND);
    
    echo "\nScript completed: $completedCount orders auto-completed, $failedCount failed\n";
    
} catch (Exception $e) {
    $errorMessage = "ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
}

$logMessage = date('Y-m-d H:i:s') . " - Auto-complete orders script finished\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);