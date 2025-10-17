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

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter orders by status
$toReceive = array_filter($allOrders, function($order) {
    $status = strtolower($order['status'] ?? $order['order_status'] ?? '');
    return in_array($status, ['shipped', 'out for delivery', 'received']);
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #4caf50;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: white;
    font-weight: bold;
    font-size: 14px;
    overflow: hidden;
}

.profile-icon:hover {
    background: #45a049;
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

/* Confirm Delivery Button */
.confirm-delivery-btn {
    margin-top: 15px;
    padding: 12px 24px;
    background: #4caf50;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.confirm-delivery-btn:hover {
    background: #45a049;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
}

.confirm-delivery-btn:active {
    transform: translateY(0);
}

.order-card-wrapper {
    border: 1px solid #e0e0e0;
    padding: 20px;
    background: #fff;
    transition: all 0.3s ease;
}

.order-card-wrapper:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #ccc;
}

.order-card-content {
    text-decoration: none;
    color: #000;
    display: block;
    cursor: pointer;
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

/* Custom SweetAlert Styling */
.custom-swal-popup {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    border-radius: 8px !important;
    padding: 30px !important;
}

.custom-swal-title {
    font-family: 'Playfair Display', serif !important;
    font-size: 28px !important;
    font-weight: 700 !important;
    color: #000 !important;
    margin-bottom: 10px !important;
}

.custom-swal-confirm {
    background: #000 !important;
    color: #fff !important;
    border: 2px solid #000 !important;
    padding: 12px 30px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    font-size: 13px !important;
    letter-spacing: 1px !important;
    transition: all 0.3s !important;
    border-radius: 4px !important;
    margin: 5px !important;
}

.custom-swal-confirm:hover {
    background: #333 !important;
    border-color: #333 !important;
    transform: translateY(-1px) !important;
}

.custom-swal-cancel {
    background: #fff !important;
    color: #000 !important;
    border: 2px solid #999 !important;
    padding: 12px 30px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    font-size: 13px !important;
    letter-spacing: 1px !important;
    transition: all 0.3s !important;
    border-radius: 4px !important;
    margin: 5px !important;
}

.custom-swal-cancel:hover {
    background: #f5f5f5 !important;
    border-color: #666 !important;
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

/* Review Modal Styles */
.review-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.review-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 550px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.review-modal-header {
    padding: 25px 30px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.review-modal-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 700;
    margin: 0;
    color: #000;
}

.review-close {
    color: #999;
    font-size: 32px;
    font-weight: 300;
    cursor: pointer;
    transition: color 0.3s;
    line-height: 1;
}

.review-close:hover {
    color: #000;
}

.review-modal-body {
    padding: 30px;
}

.review-product-info {
    background: #f8f8f8;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 25px;
    border-left: 4px solid #000;
}

.review-product-info h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 5px 0;
    color: #000;
}

.review-product-info p {
    font-size: 13px;
    color: #666;
    margin: 0;
}

.review-form-group {
    margin-bottom: 25px;
}

.review-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    color: #000;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.star-rating {
    display: flex;
    gap: 8px;
    font-size: 32px;
}

.star {
    cursor: pointer;
    color: #ddd;
    transition: all 0.2s;
}

.star:hover,
.star.active {
    color: #ffd700;
    transform: scale(1.1);
}

.review-form-group textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
    min-height: 120px;
    transition: border-color 0.3s;
}

.review-form-group textarea:focus {
    outline: none;
    border-color: #000;
}

.review-modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.review-submit-btn,
.review-cancel-btn {
    padding: 12px 30px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid;
}

.review-submit-btn {
    background: #000;
    color: #fff;
    border-color: #000;
}

.review-submit-btn:hover {
    background: #333;
    border-color: #333;
    transform: translateY(-1px);
}

.review-submit-btn:disabled {
    background: #ccc;
    border-color: #ccc;
    cursor: not-allowed;
    transform: none;
}

.review-cancel-btn {
    background: #fff;
    color: #000;
    border-color: #999;
}

.review-cancel-btn:hover {
    background: #f5f5f5;
    border-color: #666;
}

.leave-review-btn {
    margin-top: 15px;
    padding: 10px 20px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.leave-review-btn:hover {
    background: #333;
    transform: translateY(-1px);
}

.leave-review-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

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

/* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    padding: 20px 0;
}

.page-btn {
    padding: 10px 16px;
    border: 1px solid #e0e0e0;
    background: #fff;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s;
    cursor: pointer;
}

.page-btn:hover {
    background: #f5f5f5;
    border-color: #000;
    color: #000;
    transform: translateY(-2px);
}

.page-btn.active {
    background: #000;
    color: #fff;
    border-color: #000;
}

.page-ellipsis {
    padding: 10px 8px;
    color: #999;
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
            <?php if (!empty($customer['profile_picture']) && file_exists($customer['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($customer['profile_picture']) ?>?v=<?= time() ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
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
    
    // Apply pagination to the filtered orders
    $totalOrders = count($displayOrders);
    $totalPages = ceil($totalOrders / $itemsPerPage);
    $paginatedOrders = array_slice($displayOrders, $offset, $itemsPerPage);
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
            <?php foreach ($paginatedOrders as $order): 
                $orderId = $order['id'] ?? $order['order_id'] ?? 0;
                $createdAt = $order['created_at'] ?? $order['o_created_at'] ?? '';
                $status = $order['status'] ?? $order['order_status'] ?? 'Pending';
                $total = $order['total_amount'] ?? $order['total'] ?? 0;
                $statusClass = strtolower(str_replace(' ', '-', $status));
                
                // Check if this is a "To Receive" order
                $statusLower = strtolower($status);
                $isToReceive = ($activeTab === 'to-receive' && in_array($statusLower, ['shipped', 'out for delivery', 'received']));
                $isCompleted = ($activeTab === 'completed' && $statusLower === 'completed');
                
                // Check if review button should be shown (within 7 days of completion)
                $showReviewBtn = false;
                if ($isCompleted) {
                    // Use updated_at if available, otherwise use created_at (for testing)
                    $dateToCheck = !empty($order['updated_at']) ? $order['updated_at'] : $createdAt;
                    
                    if (!empty($dateToCheck)) {
                        try {
                            $completedDate = new DateTime($dateToCheck);
                            $now = new DateTime();
                            $daysDiff = $now->diff($completedDate)->days;
                            $showReviewBtn = ($daysDiff <= 7);
                        } catch (Exception $e) {
                            // If date parsing fails, show button anyway for testing
                            $showReviewBtn = true;
                        }
                    } else {
                        // No date available, show button anyway for testing
                        $showReviewBtn = true;
                    }
                }
            ?>
                <?php if ($isToReceive): ?>
                    <!-- Wrapper for To Receive orders with Confirm Delivery button -->
                    <div class="order-card-wrapper">
                        <a href="order_details.php?id=<?= $orderId ?>" class="order-card-content">
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
                        <button class="confirm-delivery-btn" onclick="confirmDelivery(<?= $orderId ?>); event.stopPropagation();">
                            Confirm Delivery
                        </button>
                    </div>
                <?php elseif ($isCompleted && $showReviewBtn): ?>
                    <!-- Wrapper for Completed orders with Leave a Review button -->
                    <div class="order-card-wrapper">
                        <a href="order_details.php?id=<?= $orderId ?>" class="order-card-content">
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
                        <button class="leave-review-btn" onclick="openReviewModal(<?= $orderId ?>); event.stopPropagation();">
                            Leave a Review
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Regular order card for other tabs -->
                    <a href="order_details.php?id=<?= $orderId ?>" class="order-card">
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
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?><?= !empty($activeTab) && $activeTab !== 'all' ? '&tab=' . urlencode($activeTab) : '' ?>" class="page-btn">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                        <a href="?page=<?= $i ?><?= !empty($activeTab) && $activeTab !== 'all' ? '&tab=' . urlencode($activeTab) : '' ?>" 
                           class="page-btn <?= $i == $currentPage ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php elseif (abs($i - $currentPage) == 3): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= !empty($activeTab) && $activeTab !== 'all' ? '&tab=' . urlencode($activeTab) : '' ?>" class="page-btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="review-modal">
  <div class="review-modal-content">
    <div class="review-modal-header">
      <h2>Leave a Review</h2>
      <span class="review-close" onclick="closeReviewModal()">&times;</span>
    </div>
    <div class="review-modal-body">
      <div id="reviewProductInfo" class="review-product-info"></div>

      <form id="reviewForm" enctype="multipart/form-data">
        <input type="hidden" id="reviewOrderId" name="order_id">
        <input type="hidden" id="reviewPerfumeId" name="perfume_id">

        <div class="review-form-group">
          <label>Rating *</label>
          <div class="star-rating" id="starRating">
            <span class="star" data-rating="1">★</span>
            <span class="star" data-rating="2">★</span>
            <span class="star" data-rating="3">★</span>
            <span class="star" data-rating="4">★</span>
            <span class="star" data-rating="5">★</span>
          </div>
          <input type="hidden" id="ratingValue" name="rating" value="0">
        </div>

        <div class="review-form-group">
          <label for="reviewComment">Your Review *</label>
          <textarea id="reviewComment" name="comment" placeholder="Share your experience..." required></textarea>
        </div>

        <div class="review-form-group">
          <label for="reviewImage">Add Image (optional)</label>
          <input type="file" id="reviewImage" name="review_image" accept="image/*">
        </div>
      </form>
    </div>
    <div class="review-modal-footer">
      <button type="button" class="review-cancel-btn" onclick="closeReviewModal()">Cancel</button>
      <button type="button" class="review-submit-btn" onclick="submitReview()">Submit Review</button>
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

<script>
function confirmDelivery(orderId) {
    // First confirmation dialog
    Swal.fire({
        title: 'Confirm Delivery?',
        html: '<p style="font-size: 15px; color: #555; margin-top: 10px;">Have you received this order?</p>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#000',
        cancelButtonColor: '#999',
        confirmButtonText: 'Yes, I Received It',
        cancelButtonText: 'Not Yet',
        customClass: {
            popup: 'custom-swal-popup',
            title: 'custom-swal-title',
            confirmButton: 'custom-swal-confirm',
            cancelButton: 'custom-swal-cancel'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                html: '<p style="font-size: 14px; color: #666;">Confirming your delivery</p>',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'custom-swal-popup'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send AJAX request to update order status
            fetch('ajax/confirm_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show review prompt
                    Swal.fire({
                        title: 'Delivery Confirmed! ✓',
                        html: '<p style="font-size: 15px; color: #555; margin-top: 10px;">Would you like to leave a review for this product?</p>',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#000',
                        cancelButtonColor: '#999',
                        confirmButtonText: 'Leave a Review',
                        cancelButtonText: 'Skip for Now',
                        customClass: {
                            popup: 'custom-swal-popup',
                            title: 'custom-swal-title',
                            confirmButton: 'custom-swal-confirm',
                            cancelButton: 'custom-swal-cancel'
                        },
                        buttonsStyling: false
                    }).then((reviewResult) => {
                        if (reviewResult.isConfirmed) {
                            // Reload page first, then open review modal
                            window.location.reload();
                        } else {
                            // Reload the page to show updated status
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        html: '<p style="font-size: 15px; color: #555; margin-top: 10px;">' + (data.message || 'Failed to confirm delivery. Please try again.') + '</p>',
                        icon: 'error',
                        confirmButtonColor: '#000',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'custom-swal-popup',
                            title: 'custom-swal-title',
                            confirmButton: 'custom-swal-confirm'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    html: '<p style="font-size: 15px; color: #555; margin-top: 10px;">An error occurred. Please try again.</p>',
                    icon: 'error',
                    confirmButtonColor: '#000',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'custom-swal-popup',
                        title: 'custom-swal-title',
                        confirmButton: 'custom-swal-confirm'
                    },
                    buttonsStyling: false
                });
            });
        }
    });
}

// Review Modal Functions
let selectedRating = 0;
let currentOrderId = 0;

function openReviewModal(orderId) {
    currentOrderId = orderId;
    
    // Fetch order products
    fetch('ajax/get_order_products.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            console.log('Order products response:', data); // Debug log
            
            if (data.success && data.products && data.products.length > 0) {
                // For now, show first product (can be enhanced to show multiple)
                const product = data.products[0];
                
                // Check if we have valid product data
                if (!product.perfume_id || product.perfume_id === 0) {
                    Swal.fire({
                        title: 'Error',
                        html: '<p style="font-size: 15px; color: #555;">Product information not found. Please contact support.</p>',
                        icon: 'error',
                        confirmButtonColor: '#000',
                        customClass: {
                            popup: 'custom-swal-popup',
                            confirmButton: 'custom-swal-confirm'
                        },
                        buttonsStyling: false
                    });
                    return;
                }
                
                document.getElementById('reviewProductInfo').innerHTML = `
                    <h3>${product.perfume_name || product.product_name || 'Product'}</h3>
                    <p>${product.perfume_brand || ''}</p>
                `;
                document.getElementById('reviewOrderId').value = orderId;
                document.getElementById('reviewPerfumeId').value = product.perfume_id;
                document.getElementById('reviewModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                Swal.fire({
                    title: 'Error',
                    html: '<p style="font-size: 15px; color: #555;">' + (data.message || 'Unable to load product information.') + '</p>',
                    icon: 'error',
                    confirmButtonColor: '#000',
                    customClass: {
                        popup: 'custom-swal-popup',
                        confirmButton: 'custom-swal-confirm'
                    },
                    buttonsStyling: false
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('reviewForm').reset();
    selectedRating = 0;
    document.querySelectorAll('.star').forEach(star => star.classList.remove('active'));
}

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.getAttribute('data-rating'));
            document.getElementById('ratingValue').value = selectedRating;
            
            stars.forEach(s => s.classList.remove('active'));
            
            for (let i = 0; i < selectedRating; i++) {
                stars[i].classList.add('active');
            }
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffd700';
                } else {
                    s.style.color = selectedRating > index ? '#ffd700' : '#ddd';
                }
            });
        });
    });
    
    document.getElementById('starRating').addEventListener('mouseleave', function() {
        stars.forEach((s, index) => {
            s.style.color = selectedRating > index ? '#ffd700' : '#ddd';
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('reviewModal');
        if (event.target === modal) {
            closeReviewModal();
        }
    });
});

function submitReview() {
    const rating = document.getElementById('ratingValue').value;
    const comment = document.getElementById('reviewComment').value.trim();
    const orderId = document.getElementById('reviewOrderId').value;
    const perfumeId = document.getElementById('reviewPerfumeId').value;
    
    // Validation
    if (!rating || rating == 0) {
        Swal.fire({
            title: 'Rating Required',
            html: '<p style="font-size: 15px; color: #555;">Please select a rating</p>',
            icon: 'warning',
            confirmButtonColor: '#000',
            customClass: {
                popup: 'custom-swal-popup',
                confirmButton: 'custom-swal-confirm'
            },
            buttonsStyling: false
        });
        return;
    }
    
    if (!comment) {
        Swal.fire({
            title: 'Comment Required',
            html: '<p style="font-size: 15px; color: #555;">Please write a review comment</p>',
            icon: 'warning',
            confirmButtonColor: '#000',
            customClass: {
                popup: 'custom-swal-popup',
                confirmButton: 'custom-swal-confirm'
            },
            buttonsStyling: false
        });
        return;
    }
    
    // Disable submit button
    const submitBtn = document.querySelector('.review-submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    // Submit review
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('perfume_id', perfumeId);
    formData.append('rating', rating);
    formData.append('comment', comment);
    
    fetch('ajax/submit_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
        }
        
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
        
        if (data.success) {
            closeReviewModal();
            Swal.fire({
                title: 'Review Submitted! ✓',
                html: '<p style="font-size: 15px; color: #555; margin-top: 10px;">Thank you for your feedback!</p>',
                icon: 'success',
                confirmButtonColor: '#000',
                confirmButtonText: 'OK',
                customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    confirmButton: 'custom-swal-confirm'
                },
                buttonsStyling: false
            }).then(() => {
                window.location.href = 'reviews.php';
            });
        } else {
            Swal.fire({
                title: 'Error',
                html: '<p style="font-size: 15px; color: #555;">' + (data.message || 'Failed to submit review') + '</p>',
                icon: 'error',
                confirmButtonColor: '#000',
                customClass: {
                    popup: 'custom-swal-popup',
                    confirmButton: 'custom-swal-confirm'
                },
                buttonsStyling: false
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
        
        Swal.fire({
            title: 'Error',
            html: '<p style="font-size: 15px; color: #555;">Error: ' + error.message + '</p>',
            icon: 'error',
            confirmButtonColor: '#000',
            customClass: {
                popup: 'custom-swal-popup',
                confirmButton: 'custom-swal-confirm'
            },
            buttonsStyling: false
        });
    });
}

document.querySelectorAll('.star').forEach(star => {
  star.addEventListener('click', function() {
    const rating = this.getAttribute('data-rating');
    document.getElementById('ratingValue').value = rating;
    document.querySelectorAll('.star').forEach(s => s.classList.remove('selected'));
    this.classList.add('selected');
    let prev = this.previousElementSibling;
    while (prev) { prev.classList.add('selected'); prev = prev.previousElementSibling; }
  });
});

function submitReview() {
  const form = document.getElementById('reviewForm');
  const formData = new FormData(form);

  fetch('save_review.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✅ Review submitted successfully!');
      closeReviewModal();
      form.reset();
    } else {
      alert('⚠️ ' + data.message);
    }
  })
  .catch(() => alert('⚠️ Something went wrong!'));
}
</script>

</body>
</html>