<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = intval($_GET['id']);
$order = $db->getOrderById($order_id);

if (!$order) {
    header("Location: orders.php");
    exit;
}

$orderItems = $db->getOrderItems($order_id);

// Get customer info
$customer = $db->fetch("SELECT * FROM customers WHERE customer_id = ?", [$order['customer_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= $order['order_id'] ?> - Happy Sprays Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    padding: 30px;
}

.container {
    max-width: 1000px;
    margin: 0 auto;
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

.order-header {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.order-title {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin-bottom: 20px;
}

.order-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.meta-item {
    display: flex;
    flex-direction: column;
}

.meta-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.meta-value {
    font-weight: 600;
    font-size: 16px;
}

.status-badge {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending {background: #fff3cd; color: #856404;}
.status-processing {background: #cfe2ff; color: #084298;}
.status-preparing {background: #d1ecf1; color: #0c5460;}
.status-out-for-delivery {background: #d1ecf1; color: #0c5460;}
.status-received {background: #d4edda; color: #155724;}
.status-cancelled {background: #f8d7da; color: #721c24;}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.card {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f5f5f5;
}

.item-row {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.item-row:last-child {
    border-bottom: none;
}

.item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 5px;
}

.item-details {
    color: #666;
    font-size: 14px;
}

.item-price {
    text-align: right;
}

.item-subtotal {
    font-weight: 700;
    font-size: 16px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
}

.summary-row.total {
    border-top: 2px solid #000;
    margin-top: 10px;
    padding-top: 15px;
    font-weight: 700;
    font-size: 18px;
}

.customer-detail {
    margin-bottom: 15px;
}

.customer-detail label {
    display: block;
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.customer-detail .value {
    font-weight: 600;
}

.proof-image {
    width: 100%;
    max-width: 300px;
    border-radius: 5px;
    margin-top: 10px;
    border: 2px solid #ddd;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .item-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .item-price {
        text-align: left;
    }
}
</style>
</head>
<body>

<div class="container">
    <a href="orders.php" class="back-link">← Back to Orders</a>
    
    <div class="order-header">
        <h1 class="order-title">Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></h1>
        
        <div class="order-meta">
            <div class="meta-item">
                <span class="meta-label">Order Date</span>
                <span class="meta-value"><?= date('M d, Y h:i A', strtotime($order['o_created_at'])) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Status</span>
                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $order['order_status'])) ?>">
                    <?= ucfirst($order['order_status']) ?>
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Payment Method</span>
                <span class="meta-value"><?= strtoupper($order['payment_method']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Amount</span>
                <span class="meta-value">₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="card">
            <h2 class="card-title">Order Items</h2>
            
            <?php foreach ($orderItems as $item): ?>
                <div class="item-row">
                    <div class="item-image">
                        <?php 
                        $productInfo = $db->getProductById($item['perfume_id']);
                        if ($productInfo && !empty($productInfo['image'])): 
                        ?>
                            <img src="images/<?= htmlspecialchars($productInfo['image']) ?>" alt="Product">
                        <?php else: ?>
                            <div style="width:80px; height:80px; background:#f5f5f5; border-radius:5px;"></div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <div class="item-name">
                            <?= $productInfo ? htmlspecialchars($productInfo['perfume_name']) : 'Product' ?>
                        </div>
                        <div class="item-details">
                            Quantity: <?= $item['order_quantity'] ?> × ₱<?= number_format($item['order_price'], 2) ?>
                        </div>
                    </div>
                    <div class="item-price">
                        <div class="item-subtotal">
                            ₱<?= number_format($item['order_price'] * $item['order_quantity'], 2) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 20px;">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>FREE</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>
        
        <div>
            <div class="card">
                <h2 class="card-title">Customer Information</h2>
                
                <?php if ($customer): ?>
                    <div class="customer-detail">
                        <label>Name</label>
                        <div class="value">
                            <?= htmlspecialchars($customer['customer_firstname'] . ' ' . $customer['customer_lastname']) ?>
                        </div>
                    </div>
                    
                    <div class="customer-detail">
                        <label>Email</label>
                        <div class="value"><?= htmlspecialchars($customer['customer_email']) ?></div>
                    </div>
                    
                    <div class="customer-detail">
                        <label>Username</label>
                        <div class="value"><?= htmlspecialchars($customer['customer_username']) ?></div>
                    </div>
                    
                    <div class="customer-detail">
                        <label>Customer Since</label>
                        <div class="value"><?= date('M d, Y', strtotime($customer['cs_created_at'])) ?></div>
                    </div>
                <?php else: ?>
                    <p style="color: #999;">Customer information not available</p>
                <?php endif; ?>
            </div>
            
            <?php if ($order['payment_method'] === 'gcash' && !empty($order['gcash_proof'])): ?>
                <div class="card" style="margin-top: 20px;">
                    <h2 class="card-title">Payment Proof</h2>
                    <img src="uploads/proofs/<?= htmlspecialchars($order['gcash_proof']) ?>" 
                         alt="Payment Proof" 
                         class="proof-image">
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>