<?php
session_start();
require_once __DIR__ . "/classes/database.php";

$db = Database::getInstance();

// Protect page: must be logged in as customer
if (!$db->isUserLoggedIn() || $db->getCurrentUserRole() !== 'customer') {
    header("Location: customer_login.php");
    exit;
}

// Fetch customer orders
$allOrders = $db->getCustomerOrders();

// Get customer data for profile icon
$dashboardData = $db->getCustomerDashboardData();
$customer = $dashboardData['customer'];

// Filter orders by status
$toReceive = array_filter($allOrders, function($order) {
    $status = strtolower($order['status'] ?? $order['order_status'] ?? '');
    return $status === 'shipped';
});

$completed = array_filter($allOrders, function($order) {
    $status = strtolower($order['status'] ?? $order['order_status'] ?? '');
    return $status === 'completed' || $status === 'delivered';
});

$cancelled = array_filter($allOrders, function($order) {
    $status = strtolower($order['status'] ?? $order['order_status'] ?? '');
    return $status === 'cancelled';
});

// Get active tab from URL parameter, default to 'all'
$activeTab = $_GET['tab'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin: 0; padding: 0; box-sizing: border-box;}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #fff;
    color: #000;
    padding-top: 120px;
    min-height: 100vh;
}

/* Top Navbar - Consistent with dashboard */
.top-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
}

.top-nav .logo {
    flex: 1;
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.top-nav .logo a {
    color: #000;
    text-decoration: none;
}

/* Right side icons container */
.nav-actions {
    display: flex;
    align-items: center;
    gap: 25px;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

.icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

.nav-icon svg {
    display: block;
    width: 22px;
    height: 23px;
    stroke: black;
}

.nav-icon:hover svg {
    stroke: #555;
}

.profile-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid #e0e0e0;
    text-decoration: none;
    color: #000;
    font-weight: 600;
    font-size: 14px;
}

.profile-icon:hover {
    background: #e0e0e0;
    border-color: #ccc;
    transform: scale(1.05);
}

.logout-btn {
    background: #000;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    letter-spacing: 0.5px;
}

.logout-btn:hover {
    background: #333;
}

/* Sub Nav */
.sub-nav {
    position: fixed;
    top: 60px;
    left: 0;
    width: 100%;
    background: #fff;
    border-bottom: 1px solid #ccc;
    text-align: center;
    padding: 12px 0;
    z-index: 999;
    font-family: 'Playfair Display', serif;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 1px;
}

.sub-nav a {
    margin: 0 20px;
    text-decoration: none;
    color: #000;
    font-size: 16px;
    transition: color 0.3s;
}

.sub-nav a:hover {
    color: #555;
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Page Header */
.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #000;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.tab {
    padding: 15px 30px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 15px;
    font-weight: 600;
    color: #666;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    margin-bottom: -2px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab:hover {
    color: #000;
    background: #f9f9f9;
}

.tab.active {
    color: #000;
    border-bottom-color: #000;
    font-weight: 700;
}

.tab-count {
    background: #e0e0e0;
    color: #000;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
}

.tab.active .tab-count {
    background: #000;
    color: #fff;
}

/* Orders Grid */
.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.order-card {
    border: 1px solid #e0e0e0;
    padding: 20px;
    background: #fff;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: #000;
    display: block;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #ccc;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.order-number {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

.order-status {
    display: inline-block;
    padding: 6px 12px;
    font-weight: 600;
    border: 1px solid #ddd;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.5px;
}

.status-pending { background: #fff3cd; border-color: #ffc107; color: #856404; }
.status-processing { background: #cfe2ff; border-color: #0d6efd; color: #084298; }
.status-shipped { background: #e7d6f0; border-color: #9b59b6; color: #5a357a; }
.status-delivered { background: #d1e7dd; border-color: #198754; color: #0f5132; }
.status-completed { background: #d1e7dd; border-color: #198754; color: #0f5132; }
.status-cancelled { background: #f8d7da; border-color: #dc3545; color: #842029; }

.order-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.order-detail {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #555;
}

.order-detail strong {
    color: #000;
    font-weight: 600;
}

.order-total {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.total-amount {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    color: #000;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 16px;
    color: #999;
    margin-bottom: 20px;
}

.shop-btn {
    display: inline-block;
    padding: 12px 30px;
    background: #000;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 14px;
    letter-spacing: 0.5px;
    transition: all 0.3s;
}

.shop-btn:hover {
    background: #333;
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

/* Responsive */
@media(max-width:768px) {
    body {
        padding-top: 110px;
    }
    
    .top-nav .logo {
        font-size: 20px;
        letter-spacing: 1px;
    }
    
    .nav-actions {
        gap: 15px;
    }
    
    .logout-btn {
        padding: 8px 14px;
        font-size: 11px;
    }
    
    .sub-nav {
        padding: 10px 0;
    }
    
    .sub-nav a {
        margin: 0 10px;
        font-size: 14px;
    }
    
    .container {
        padding: 30px 15px;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab {
        padding: 12px 20px;
        font-size: 13px;
        white-space: nowrap;
    }
    
    .orders-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-columns {
        gap: 40px;
    }
}
</style>
</head>
<body>
</head>
<body>

<div class="top-nav">
    <div class="logo"><a href="index.php">Happy Sprays</a></div>
    <div class="nav-actions">
        <a href="index.php" class="nav-icon icon-btn" title="Home">
            <svg width="22" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
        </a>
        <a href="cart.php" class="nav-icon icon-btn" title="Cart">
            <svg width="22" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        </a>
        <a href="customer_profile.php" class="profile-icon" title="Profile">
            <?= strtoupper(substr($customer['customer_firstname'], 0, 1)) ?>
        </a>
        <a href="customer_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="sub-nav">
    <a href="customer_dashboard.php">Dashboard</a>
    <a href="my_orders.php">My Orders</a>
    <a href="index.php">Shop</a>
</div>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <p>Track and manage all your orders</p>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?tab=all" class="tab <?= $activeTab === 'all' ? 'active' : '' ?>">
            All Orders
            <span class="tab-count"><?= count($allOrders) ?></span>
        </a>
        <a href="?tab=to-receive" class="tab <?= $activeTab === 'to-receive' ? 'active' : '' ?>">
            To Receive
            <span class="tab-count"><?= count($toReceive) ?></span>
        </a>
        <a href="?tab=completed" class="tab <?= $activeTab === 'completed' ? 'active' : '' ?>">
            Completed
            <span class="tab-count"><?= count($completed) ?></span>
        </a>
        <a href="?tab=cancelled" class="tab <?= $activeTab === 'cancelled' ? 'active' : '' ?>">
            Cancelled
            <span class="tab-count"><?= count($cancelled) ?></span>
        </a>
    </div>

    <!-- Orders Content -->
    <?php
    // Determine which orders to display based on active tab
    $displayOrders = $allOrders;
    $emptyMessage = "You have no orders yet.";
    $emptyIcon = "📦";
    
    switch($activeTab) {
        case 'to-receive':
            $displayOrders = $toReceive;
            $emptyMessage = "No orders to receive at the moment.";
            $emptyIcon = "🚚";
            break;
        case 'completed':
            $displayOrders = $completed;
            $emptyMessage = "No completed orders yet.";
            $emptyIcon = "✅";
            break;
        case 'cancelled':
            $displayOrders = $cancelled;
            $emptyMessage = "No cancelled orders.";
            $emptyIcon = "❌";
            break;
    }
    ?>

    <?php if (empty($displayOrders)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><?= $emptyIcon ?></div>
            <h3><?= $emptyMessage ?></h3>
            <p>Start shopping to see your orders here!</p>
            <a href="index.php" class="shop-btn">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($displayOrders as $order): 
                $orderId = $order['id'] ?? $order['order_id'] ?? 0;
                $createdAt = $order['created_at'] ?? $order['o_created_at'] ?? '';
                $status = $order['status'] ?? $order['order_status'] ?? 'Pending';
                $total = $order['total_amount'] ?? $order['total'] ?? 0;
                $statusClass = strtolower(str_replace(' ', '-', $status));
            ?>
                <a href="order_status.php?id=<?= $orderId ?>" class="order-card">
                    <div class="order-header">
                        <div class="order-number">Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></div>
                        <span class="order-status status-<?= $statusClass ?>">
                            <?= htmlspecialchars(ucfirst($status)) ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail">
                            <span>Order Date</span>
                            <strong><?= $createdAt ? date("M d, Y", strtotime($createdAt)) : '-' ?></strong>
                        </div>
                        
                        <?php if (!empty($order['gcash_proof'])): ?>
                        <div class="order-detail">
                            <span>Payment</span>
                            <strong style="color: #198754;">Proof Submitted</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-total">
                        <span class="total-label">Total Amount</span>
                        <span class="total-amount">₱<?= number_format($total, 2) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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