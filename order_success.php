<?php
session_start();
require_once 'classes/database.php';

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = Database::getInstance();

if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$order = $db->getCustomerOrder($order_id);

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch order items with perfume names
$orderItems = $db->getCustomerOrderItems($order_id);

// Calculate total items
$totalItems = 0;
foreach ($orderItems as $item) {
    $totalItems += $item['order_quantity'];
}

// Get customer details
$customer = $db->getCurrentCustomer();

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

// ============================================================================
// SEND ORDER CONFIRMATION EMAIL
// ============================================================================
function sendOrderConfirmationEmail($order, $orderItems, $customer, $totalItems) {
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
        $mail->setFrom('happyspray@happyspray.shop', 'happyspray@happyspray.shop');
        $mail->addAddress($customer['customer_email'], $customer['customer_firstname'] . ' ' . $customer['customer_lastname']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation #' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . ' - Happy Sprays';
        $mail->CharSet = 'UTF-8';
        
        // Build items HTML
        $itemsHtml = '';
        foreach ($orderItems as $item) {
            $itemTotal = $item['order_price'] * $item['order_quantity'];
            $itemsHtml .= "
            <tr>
                <td style='padding: 15px; border-bottom: 1px solid #e0e0e0;'>
                    <div style='font-weight: 600; color: #000; margin-bottom: 5px;'>" . htmlspecialchars($item['perfume_name']) . "</div>
                    <div style='font-size: 13px; color: #666;'>Quantity: " . $item['order_quantity'] . " × ₱" . number_format($item['order_price'], 2) . "</div>
                </td>
                <td style='padding: 15px; border-bottom: 1px solid #e0e0e0; text-align: right; font-weight: 600;'>
                    ₱" . number_format($itemTotal, 2) . "
                </td>
            </tr>";
        }
        
        // Email HTML Body
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background: #f5f5f5;
                }
                .email-container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #ffffff;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .header { 
                    background: #000000; 
                    color: #ffffff; 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                    font-weight: 700;
                    letter-spacing: 3px;
                }
                .success-icon {
                    background: #10b981;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    margin: 20px auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 36px;
                }
                .content { 
                    padding: 40px 30px; 
                }
                .content h2 {
                    color: #000;
                    margin: 0 0 15px 0;
                    font-size: 24px;
                }
                .message-box {
                    background: #f0fdf4;
                    border-left: 4px solid #10b981;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 4px;
                }
                .order-info {
                    background: #f9f9f9;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 25px 0;
                }
                .order-info table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .order-info td {
                    padding: 10px 0;
                    border-bottom: 1px dotted #ccc;
                }
                .order-info td:first-child {
                    font-weight: 600;
                    color: #666;
                }
                .order-info tr:last-child td {
                    border-bottom: none;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 25px 0;
                    background: #fff;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .items-table th {
                    background: #fafafa;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    color: #000;
                    text-transform: uppercase;
                    font-size: 12px;
                    letter-spacing: 0.5px;
                }
                .total-row {
                    background: #000;
                    color: #fff;
                    font-weight: 700;
                    font-size: 16px;
                }
                .total-row td {
                    padding: 15px !important;
                    border: none !important;
                }
                .btn {
                    display: inline-block;
                    padding: 14px 32px;
                    background: #000000;
                    color: #ffffff !important;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    margin: 20px 0;
                    text-align: center;
                }
                .status-badge {
                    display: inline-block;
                    padding: 6px 12px;
                    background: #fef3c7;
                    color: #92400e;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .footer { 
                    background: #f5f5f5;
                    padding: 30px; 
                    text-align: center;
                    border-top: 1px solid #e0e0e0;
                }
                .footer p {
                    margin: 5px 0;
                    font-size: 13px;
                    color: #666;
                }
                .footer a {
                    color: #000;
                    text-decoration: none;
                    font-weight: 600;
                }
                .divider {
                    border-top: 2px dashed #e0e0e0;
                    margin: 30px 0;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>HAPPY SPRAYS</h1>
                    <div class='success-icon'>✓</div>
                </div>
                
                <div class='content'>
                    <h2>Thank You for Your Order!</h2>
                    
                    <div class='message-box'>
                        <p style='margin: 0; font-size: 15px;'>
                            <strong>Hi " . htmlspecialchars($customer['customer_firstname']) . ",</strong><br><br>
                            We've received your order and it's being processed. You'll receive another email when your order is ready for delivery.
                        </p>
                    </div>
                    
                    <h3 style='margin: 30px 0 15px 0; color: #000;'>Order Details</h3>
                    <div class='order-info'>
                        <table>
                            <tr>
                                <td>Order Number:</td>
                                <td style='text-align: right; font-weight: 700; color: #000;'>#" . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . "</td>
                            </tr>
                            <tr>
                                <td>Order Date:</td>
                                <td style='text-align: right;'>" . date('F d, Y h:i A', strtotime($order['o_created_at'])) . "</td>
                            </tr>
                            <tr>
                                <td>Payment Method:</td>
                                <td style='text-align: right;'>" . strtoupper($order['payment_method']) . "</td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td style='text-align: right;'><span class='status-badge'>" . ucfirst($order['order_status']) . "</span></td>
                            </tr>
                        </table>
                    </div>
                    
                    <h3 style='margin: 30px 0 15px 0; color: #000;'>Order Items</h3>
                    <table class='items-table'>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style='text-align: right;'>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            " . $itemsHtml . "
                            <tr class='total-row'>
                                <td>Total (" . $totalItems . " items)</td>
                                <td style='text-align: right;'>₱" . number_format($order['total_amount'], 2) . "</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/my_orders.php' class='btn'>View Order Status</a>
                    </div>
                    
                    <div class='divider'></div>
                    
                    <div style='background: #f0f9ff; border-left: 4px solid #0284c7; padding: 20px; border-radius: 4px;'>
                        <h4 style='margin: 0 0 10px 0; color: #075985;'>📦 What's Next?</h4>
                        <ul style='margin: 0; padding-left: 20px; color: #0c4a6e;'>
                            <li style='margin: 5px 0;'>We'll process your order and prepare it for delivery</li>
                            <li style='margin: 5px 0;'>You'll receive email updates when your order status changes</li>
                            <li style='margin: 5px 0;'>Track your order anytime from your account dashboard</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>Need Help?</strong></p>
                    <p>If you have any questions about your order, please contact us.</p>
                    <p style='margin-top: 15px;'>
                        <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/contact.php'>Contact Support</a>
                    </p>
                    <p style='margin-top: 20px; font-size: 12px; color: #999;'>
                        &copy; " . date('Y') . " Happy Sprays. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version
        $mail->AltBody = "Thank you for your order!\n\n";
        $mail->AltBody .= "Order #" . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . "\n";
        $mail->AltBody .= "Order Date: " . date('F d, Y h:i A', strtotime($order['o_created_at'])) . "\n";
        $mail->AltBody .= "Total Amount: ₱" . number_format($order['total_amount'], 2) . "\n\n";
        $mail->AltBody .= "We'll send you updates as your order is processed.";

        // Send email
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Order confirmation email error: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

// Send email only once when page first loads
$emailSent = false;
if (!isset($_SESSION['email_sent_for_order_' . $order_id])) {
    $emailResult = sendOrderConfirmationEmail($order, $orderItems, $customer, $totalItems);
    $_SESSION['email_sent_for_order_' . $order_id] = true;
    $emailSent = $emailResult['success'];
    
    if (!$emailSent) {
        error_log("Failed to send order confirmation email for order #" . $order_id . ": " . $emailResult['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Receipt - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body { font-family: 'Courier New', Courier, monospace; background: #f2f2f2; color: #222; line-height: 1.5; }
.top-nav { background: #fff; border-bottom: 2px dashed #ccc; padding: 20px; text-align: center; }
.top-nav h1 { font-family: 'Playfair Display', serif; font-size: 28px; text-transform: uppercase; letter-spacing: 2px; color: #333; }
.container { max-width: 500px; margin: 40px auto; padding: 0 20px; }
.success-card { background: #fff; padding: 30px 25px; border-radius: 5px; border: 1px solid #ccc; box-shadow: 0 2px 6px rgba(0,0,0,0.05); position: relative; }
.logo { display: block; margin: 0 auto 15px; max-width: 150px; }
.success-icon { width: 50px; height: 50px; background: #4caf50; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 32px; }
.success-title { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 10px; color: #4caf50; text-align: center; }
.success-message { color: #555; margin-bottom: 25px; text-align: center; font-size: 13px; }
.email-notice {
    background: #e0f2fe;
    border-left: 4px solid #0284c7;
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-size: 12px;
    color: #075985;
    text-align: center;
}
.order-details { background: #fafafa; padding: 15px; border-radius: 5px; border: 1px dashed #ccc; margin: 20px 0; text-align: left; font-size: 13px; }
.detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #bbb; }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-weight: 600; }
.order-items { margin: 20px 0; text-align: left; font-size: 13px; }
.order-items h3 { font-family: 'Playfair Display', serif; margin-bottom: 10px; font-size: 16px; border-bottom: 1px dotted #ccc; padding-bottom: 3px; }
.item-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #ddd; }
.item-info { flex: 1; }
.item-name { font-weight: 600; margin-bottom: 3px; }
.item-details { font-size: 12px; color: #555; }
.item-row div:last-child { font-family: 'Courier New', Courier, monospace; font-weight: 600; }
.total-summary { margin-top: 15px; border-top: 1px dashed #bbb; padding-top: 10px; font-size: 14px; }
.total-summary div { display: flex; justify-content: space-between; margin-bottom: 4px; font-weight: 600; }
.action-buttons { display: flex; gap: 12px; margin-top: 20px; justify-content: center; }
.btn { padding: 8px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-block; font-size: 13px; }
.btn-primary { background: #000; color: #fff; }
.btn-primary:hover { background: #333; }
.btn-secondary { background: #fff; color: #000; border: 2px solid #000; }
.btn-secondary:hover { background: #000; color: #fff; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #fff3cd; color: #856404; }
.tear-off { width: 100%; border-top: 2px dashed #999; margin-top: 30px; position: relative; text-align: center; font-size: 12px; color: #999; }
.tear-off span { background: #fff; padding: 0 5px; position: relative; top: -10px; }
@media (max-width: 768px) { .action-buttons { flex-direction: column; } .btn { width: 100%; } }
</style>
</head>
<body>

<div class="top-nav">
    <h1>Happy Sprays</h1>
</div>

<div class="container">
    <div class="success-card">
        <!-- Logo -->
        <img src="images/happyslogo2.png" alt="Happy Sprays Logo" class="logo">

        <div class="success-icon">✓</div>
        <h1 class="success-title">Order Received!</h1>
        <p class="success-message">
            Thank you for your purchase! Your order has been received and is being processed.
        </p>
        
        <div class="email-notice">
            📧 A confirmation email has been sent to <strong><?= htmlspecialchars($customer['customer_email']) ?></strong>
        </div>
        
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Order Number:</span>
                <span>#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span><?= date('M d, Y h:i A', strtotime($order['o_created_at'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span><?= strtoupper($order['payment_method']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="status-badge"><?= ucfirst($order['order_status']) ?></span>
            </div>
        </div>
        
        <div class="order-items">
            <h3>Order Items</h3>
            <?php foreach ($orderItems as $item): ?>
                <div class="item-row">
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['perfume_name']) ?></div>
                        <div class="item-details">
                            Qty: <?= $item['order_quantity'] ?> × ₱<?= number_format($item['order_price'], 2) ?>
                        </div>
                    </div>
                    <div>
                        ₱<?= number_format($item['order_price'] * $item['order_quantity'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-summary">
    <div>
        <span>Subtotal:</span>
        <span>₱<?= number_format($order['total_amount'] - $order['shipping_fee'], 2) ?></span>
    </div>
    <div>
        <span>Shipping Fee:</span>
        <span>₱<?= number_format($order['shipping_fee'], 2) ?></span>
    </div>
    <div>
        <span>Total Amount:</span>
        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
    </div>
</div>

        
        <div class="action-buttons">
            <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
            <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
        </div>

        <div class="tear-off"><span>--- Tear Here ---</span></div>
    </div>
</div>

</body>
</html>