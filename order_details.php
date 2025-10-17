<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Protect page: only logged-in customers
if (!$db->isUserLoggedIn() || $db->getCurrentUserRole() !== 'customer') {
    header("Location: customer_login.php?redirect_to=customer_dashboard.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: customer_dashboard.php");
    exit;
}

$order_id = intval($_GET['id']);
$order = $db->getCustomerOrder($order_id);

if (!$order) {
    header("Location: customer_dashboard.php");
    exit;
}

// Fetch order items
$orderItems = $db->getCustomerOrderItems($order_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= str_pad($order['order_id'],6,'0',STR_PAD_LEFT) ?> - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; color:#111; }
.top-nav { background:#fff; padding:20px; text-align:center; border-bottom:1px solid #eee; }
.top-nav h1 { font-family:'Playfair Display', serif; font-size:28px; letter-spacing:2px; }
.container { max-width:1000px; margin:40px auto; padding:0 20px; }
.back-link { display:inline-block; margin-bottom:20px; color:#111; text-decoration:none; font-weight:600; }
.back-link:hover { text-decoration:underline; }

.order-header, .order-items, .order-summary { background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:20px; }
.order-title { font-family:'Playfair Display', serif; font-size:28px; margin-bottom:15px; }
.order-meta { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:20px; margin-top:20px; }
.meta-item { display:flex; flex-direction:column; }
.meta-label { font-size:12px; color:#999; text-transform:uppercase; margin-bottom:5px; }
.meta-value { font-weight:600; font-size:16px; }

.order-status { display:inline-block; padding:8px 20px; border-radius:20px; font-weight:600; text-transform:capitalize; }
.status-pending { background:#fff3cd; color:#856404; }
.status-processing { background:#cfe2ff; color:#084298; }
.status-preparing { background:#d1ecf1; color:#0c5460; }
.status-shipping, .status-out-for-delivery { background:#d1ecf1; color:#0c5460; }
.status-delivered, .status-received { background:#d4edda; color:#155724; }
.status-cancelled { background:#f8d7da; color:#721c24; }

.item-row { display:flex; justify-content:space-between; padding:15px 0; border-bottom:1px solid #eee; }
.item-row:last-child { border-bottom:none; }
.item-name { font-weight:600; font-size:16px; }
.item-details { color:#666; font-size:14px; }
.item-subtotal { font-weight:700; font-size:16px; }

.summary-row { display:flex; justify-content:space-between; padding:10px 0; }
.summary-row.total { border-top:2px solid #000; margin-top:10px; padding-top:15px; font-weight:700; font-size:20px; }
</style>
</head>
<body>

<div class="top-nav"><h1>Happy Sprays</h1></div>

<div class="container">
    <a href="customer_dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="order-header">
        <h1 class="order-title">Order #<?= str_pad($order['order_id'],6,'0',STR_PAD_LEFT) ?></h1>
        <div class="order-meta">
            <div class="meta-item">
                <span class="meta-label">Order Date</span>
                <span class="meta-value"><?= date('M d, Y h:i A', strtotime($order['o_created_at'])) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Status</span>
                <span class="order-status status-<?= strtolower(str_replace(' ','-',$order['order_status'])) ?>">
                    <?= ucfirst($order['order_status']) ?>
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Payment Method</span>
                <span class="meta-value"><?= strtoupper($order['payment_method']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Amount</span>
                <span class="meta-value">₱<?= number_format($order['total_amount'],2) ?></span>
            </div>
        </div>
    </div>

    <div class="order-items">
        <h2 class="section-title">Order Items</h2>
        <?php foreach($orderItems as $item): ?>
            <div class="item-row">
                <div>
                    <div class="item-name"><?= htmlspecialchars($item['perfume_name']) ?></div>
                    <div class="item-details">Quantity: <?= $item['order_quantity'] ?> × ₱<?= number_format($item['order_price'],2) ?></div>
                </div>
                <div class="item-subtotal">₱<?= number_format($item['order_price']*$item['order_quantity'],2) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    // ✅ Calculate shipping fee correctly
    $shipping_fee = isset($order['shipping_fee']) ? floatval($order['shipping_fee']) : 0;
    $subtotal = floatval($order['total_amount']) - $shipping_fee;
    ?>

    <div class="order-summary">
        <h2 class="section-title">Order Summary</h2>
        <div class="summary-row">
            <span>Subtotal:</span>
            <span>₱<?= number_format($subtotal, 2) ?></span>
        </div>
        <div class="summary-row">
            <span>Shipping:</span>
            <span><?= $shipping_fee > 0 ? '₱' . number_format($shipping_fee, 2) : 'FREE' ?></span>
        </div>
        <div class="summary-row total">
            <span>Total:</span>
            <span>₱<?= number_format($order['total_amount'], 2) ?></span>
        </div>
    </div>
</div>

</body>
</html>
