<?php
session_start();
require_once 'classes/database.php';

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
        <h1 class="success-title">Order Receipt</h1>
        <p class="success-message">
            Thank you for your purchase! Your order has been received and is being processed.
        </p>
        
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
                <span>Total Items:</span>
                <span><?= $totalItems ?></span>
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
