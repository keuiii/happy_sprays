<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if customer is logged in
if (!$db->isUserLoggedIn() || $db->getCurrentUserRole() !== 'customer') {
    header("Location: customer_login.php?redirect_to=customer_dashboard.php");
    exit;
}

$dashboardData = $db->getCustomerDashboardData();
$customer = $dashboardData['customer'];
$recentOrders = $dashboardData['recent_orders'] ?? [];
$totalOrders = $dashboardData['total_orders'] ?? 0;
$pendingOrders = $dashboardData['pending_orders'] ?? 0;
$completedOrders = $dashboardData['completed_orders'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #fff;
    color: #000;
    padding-top: 120px;
}

/* Top Navbar - Consistent with index.php */
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
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4caf50;
    color: white;
    font-weight: bold;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    text-decoration: none;
    overflow: hidden;
}

.profile-icon img.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
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

/* Sub Nav for Dashboard */
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

/* Dashboard Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    min-height: calc(100vh - 200px);
}

/* Welcome Banner */
.welcome-banner {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    padding: 40px 30px;
    margin-bottom: 40px;
    text-align: center;
}

.welcome-banner h2 { 
    font-family: 'Playfair Display', serif; 
    font-size: 36px; 
    margin-bottom: 10px;
    font-weight: 700;
    line-height: 1.2;
    color: #000;
}

.welcome-banner p { 
    font-size: 16px; 
    color: #666;
    line-height: 1.6;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 50px;
}

.stat-card {
    border: 1px solid #e0e0e0;
    padding: 30px 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fff;
}

.stat-card:hover { 
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #ccc;
}

.stat-value { 
    font-size: 48px; 
    font-weight: 700; 
    margin-bottom: 10px; 
    color: #000;
    font-family: 'Playfair Display', serif;
    line-height: 1;
}

.stat-label { 
    font-size: 12px; 
    text-transform: uppercase; 
    color: #666;
    font-weight: 600;
    letter-spacing: 1px;
}

/* Orders Section */
.dashboard-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 30px;
    margin-bottom: 40px;
}

.dashboard-section h3 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin-bottom: 25px;
    font-weight: 700;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
    color: #000;
}

.recent-orders { 
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px; 
}

.order-card {
    border: 1px solid #e0e0e0;
    padding: 20px;
    transition: all 0.3s ease;
    background: #fff;
    cursor: pointer;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #ccc;
}

.order-card h4 { 
    font-size: 18px; 
    margin-bottom: 12px; 
    color: #000;
    font-weight: 700;
    font-family: 'Playfair Display', serif;
}

.order-card p { 
    font-size: 14px; 
    margin-bottom: 6px; 
    color: #555;
    line-height: 1.5;
}

.order-card p strong {
    color: #000;
    font-weight: 600;
}

.order-status {
    display: inline-block;
    padding: 6px 12px;
    font-weight: 600;
    border: 1px solid #ddd;
    text-transform: uppercase;
    font-size: 10px;
    color: #000;
    background: #f9f9f9;
    letter-spacing: 0.5px;
    margin-top: 10px;
}

.status-pending { background: #fff3cd; border-color: #ffc107; color: #856404; }
.status-processing { background: #cfe2ff; border-color: #0d6efd; color: #084298; }
.status-shipped { background: #e7d6f0; border-color: #9b59b6; color: #5a357a; }
.status-delivered { background: #d1e7dd; border-color: #198754; color: #0f5132; }
.status-completed { background: #d1e7dd; border-color: #198754; color: #0f5132; }
.status-cancelled { background: #f8d7da; border-color: #dc3545; color: #842029; }

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 40px;
}

.action-btn {
    border: 1px solid #000;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
    color: #000;
    transition: all 0.3s ease;
    background: #fff;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
}

.action-btn:hover { 
    background: #000;
    color: #fff;
    transform: translateY(-2px);
}

.no-orders {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 16px;
    background: #f9f9f9;
    border: 1px dashed #ddd;
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
    
    .welcome-banner {
        padding: 30px 20px;
    }
    
    .welcome-banner h2 {
        font-size: 28px;
    }
    
    .welcome-banner p {
        font-size: 14px;
    }
    
    .stats-grid {
        gap: 15px;
        grid-template-columns: 1fr;
    }
    
    .stat-value {
        font-size: 36px;
    }
    
    .dashboard-section {
        padding: 20px 15px;
    }
    
    .dashboard-section h3 {
        font-size: 22px;
    }
    
    .recent-orders {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .footer-columns {
        gap: 40px;
    }
}
</style>
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
    <?php if (!empty($customer['profile_picture']) && file_exists($customer['profile_picture'])): ?>
        <img src="<?= htmlspecialchars($customer['profile_picture']) ?>" alt="Profile" class="profile-img">
    <?php else: ?>
        <?= strtoupper(substr($customer['customer_firstname'], 0, 1)) ?>
    <?php endif; ?>
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
    <div class="welcome-banner">
        <h2>Welcome back, <?= htmlspecialchars($customer['customer_firstname']) ?>!</h2>
        <p>Manage your orders, track shipments, and explore new fragrances.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $pendingOrders ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $completedOrders ?></div>
            <div class="stat-label">Completed Orders</div>
        </div>
    </div>

    <div class="dashboard-section">
        <h3>Recent Orders</h3>
        <?php if(empty($recentOrders)): ?>
            <div class="no-orders">No orders yet. Start shopping to see your orders here!</div>
        <?php else: ?>
            <div class="recent-orders">
                <?php foreach($recentOrders as $order): ?>
                    <a href="order_details.php?id=<?= $order['order_id'] ?>" style="text-decoration:none;">
                        <div class="order-card">
                            <h4>Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></h4>
                            <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'],2) ?></p>
                            <p><strong>Date:</strong> <?= date('M d, Y', strtotime($order['o_created_at'])) ?></p>
                            <span class="order-status status-<?= strtolower(str_replace(' ', '-', $order['order_status'])) ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                            <?php if(!empty($order['gcash_proof'])): ?>
                                <p style="margin-top:10px; font-size: 12px; color: #666;">Payment Proof Submitted</p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="quick-actions">
        <a href="index.php" class="action-btn">Browse Products</a>
        <a href="cart.php" class="action-btn">View Cart</a>
        <a href="my_orders.php" class="action-btn">All Orders</a>
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
