<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// ✅ Check if user is logged in AND is an admin
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

// Get dashboard statistics
$stats = [
    'total_products' => $db->getProductsCount(),
    'total_customers' => $db->getUsersCount(),
    'total_orders' => $db->getOrdersCount(),
    'unread_messages' => $db->getUnreadContactCount()
];

$orderStats = $db->getOrderStats();
$recentOrders = $db->select("SELECT o.*, c.customer_firstname, c.customer_lastname FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.o_created_at DESC LIMIT 5");
$lowStockProducts = $db->getLowStockProducts(10);

// NEW: Get report data
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$currentYear = date('Y');

// Sales comparison
$currentMonthSales = $db->fetch("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE_FORMAT(o_created_at, '%Y-%m') = ?", [$currentMonth]);
$lastMonthSales = $db->fetch("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE_FORMAT(o_created_at, '%Y-%m') = ?", [$lastMonth]);

// Calculate growth percentage
$revenueGrowth = 0;
if ($lastMonthSales['total'] > 0) {
    $revenueGrowth = (($currentMonthSales['total'] - $lastMonthSales['total']) / $lastMonthSales['total']) * 100;
}

$ordersGrowth = 0;
if ($lastMonthSales['count'] > 0) {
    $ordersGrowth = (($currentMonthSales['count'] - $lastMonthSales['count']) / $lastMonthSales['count']) * 100;
}

// Top selling products
$topProducts = $db->select("
    SELECT p.perfume_name, p.perfume_price, 
           SUM(oi.order_quantity) as total_sold, 
           SUM(oi.order_quantity * oi.order_price) as revenue
    FROM order_items oi
    JOIN perfumes p ON oi.perfume_id = p.perfume_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE YEAR(o.o_created_at) = ?
    GROUP BY oi.perfume_id, p.perfume_name, p.perfume_price
    ORDER BY total_sold DESC
    LIMIT 5
", [$currentYear]);

// Monthly sales data for chart (last 12 months by default)
$reportRange = isset($_GET['range']) ? intval($_GET['range']) : 12;
$isAllTime = ($reportRange === 0);
$monthlySales = [];

if ($isAllTime) {
    // Get all months with sales data from the beginning
    $allMonthsData = $db->select("
        SELECT 
            DATE_FORMAT(o_created_at, '%Y-%m') as month_key,
            DATE_FORMAT(o_created_at, '%b %Y') as month_name,
            COALESCE(SUM(total_amount), 0) as total
        FROM orders
        GROUP BY DATE_FORMAT(o_created_at, '%Y-%m')
        ORDER BY month_key ASC
    ");
    
    foreach ($allMonthsData as $monthData) {
        $monthlySales[] = [
            'month' => $monthData['month_name'],
            'sales' => $monthData['total']
        ];
    }
} else {
    // Get specific range of months
    for ($i = ($reportRange - 1); $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        $sales = $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE_FORMAT(o_created_at, '%Y-%m') = ?", [$month]);
        $monthlySales[] = ['month' => $monthName, 'sales' => $sales['total']];
    }
}

// Order status distribution
$statusDistribution = [
    'pending' => $orderStats['pending'] ?? 0,
    'processing' => $orderStats['processing'] ?? 0,
    'out for delivery' => $orderStats['out for delivery'] ?? 0,
    'received' => $orderStats['received'] ?? 0,
    'cancelled' => $orderStats['cancelled'] ?? 0
];

// Customer insights
$newCustomersThisMonth = $db->fetch("SELECT COUNT(*) as count FROM customers WHERE DATE_FORMAT(cs_created_at, '%Y-%m') = ?", [$currentMonth]);
$repeatCustomers = $db->fetch("SELECT COUNT(DISTINCT customer_id) as count FROM orders GROUP BY customer_id HAVING COUNT(*) > 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f0f5;
    display: flex;
}

.sidebar {
    width: 260px;
    background: #fff;
    color: #333;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
}

.sidebar-header {
    padding: 20px 20px;
    border-bottom: 1px solid #e8e8e8;
    background: #fff;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
}

.sidebar-header img {
    max-width: 120px;
    height: auto;
    display: block;
}

.sidebar-menu {
    padding: 30px 0;
}

.menu-item {
    padding: 16px 15px;
    color: #666;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 15px;
    margin: 4px 8px;
    border-radius: 10px;
    position: relative;
}

.menu-item::before {
    content: '○';
    font-size: 18px;
}

.menu-item:hover {
    background: #f5f5f5;
    color: #000;
}

.menu-item.active {
    background: #000;
    color: #fff;
}

.menu-item.active::before {
    content: '●';
}

.unread-badge {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: #ef4444;
    color: #fff;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}

.sidebar-footer {
    position: absolute;
    bottom: 30px;
    width: 100%;
    padding: 0 8px;
}

.logout-item {
    padding: 16px 15px;
    color: #d32f2f;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 15px;
    margin: 4px 0;
    border-radius: 10px;
    transition: all 0.3s;
}

.logout-item:hover {
    background: #ffebee;
}

.main-content {
    margin-left: 260px;
    flex: 1;
    padding: 40px;
    background: #f0f0f5;
}

.top-bar {
    background: #fff;
    padding: 30px 35px;
    border-radius: 16px;
    margin-bottom: 35px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    color: #000;
}

.welcome-text {
    color: #666;
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.stat-card {
    background: #fff;
    padding: 30px 28px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.stat-card.dark {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: #fff;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(0,0,0,0.08);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 20px;
}

.stat-card.dark .stat-icon {
    background: rgba(255,255,255,0.15);
}

.stat-label {
    color: #999;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card.dark .stat-label {
    color: rgba(255,255,255,0.7);
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 8px;
    font-family: 'Segoe UI', sans-serif;
    color: #000;
}

.stat-card.dark .stat-value {
    color: #fff;
}

.stat-change {
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

.stat-card.dark .stat-change {
    opacity: 0.9;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    margin-bottom: 40px;
}

.dashboard-card {
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e8e8e8;
}

.card-title {
    font-family: 'Segoe UI', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

.view-all {
    color: #666;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s;
    background: #f5f5f5;
}

.view-all:hover {
    background: #e8e8e8;
    color: #000;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-item {
    padding: 18px;
    background: #fafafa;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
    border: 1px solid transparent;
}

.order-item:hover {
    background: #f5f5f5;
    border-color: #e0e0e0;
}

.order-info h4 {
    font-size: 15px;
    margin-bottom: 6px;
    font-weight: 600;
    color: #000;
}

.order-meta {
    font-size: 13px;
    color: #888;
}

.status-badge {
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
    text-transform: capitalize;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-out-for-delivery {
    background: #ddd6fe;
    color: #5b21b6;
}

.status-delivered, .status-received {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.product-item {
    padding: 16px;
    background: #fafafa;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    transition: all 0.3s;
    border: 1px solid transparent;
}

.product-item:hover {
    background: #f5f5f5;
    border-color: #e0e0e0;
}

.product-name {
    font-weight: 600;
    font-size: 14px;
    color: #000;
}

.stock-warning {
    color: #dc2626;
    font-weight: 600;
    font-size: 13px;
    background: #fee2e2;
    padding: 4px 12px;
    border-radius: 20px;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #999;
    font-size: 14px;
}

/* REPORTS STYLES */
.reports-section {
    margin-top: 40px;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: #000;
    margin-bottom: 25px;
    padding-left: 5px;
}

.reports-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.chart-container {
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.chart-title {
    font-size: 18px;
    font-weight: 700;
    color: #000;
    margin-bottom: 25px;
}

.chart-controls {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 20px;
}

.range-select {
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    transition: all 0.3s;
}

.range-select:hover {
    border-color: #000;
}

.range-select:focus {
    outline: none;
    border-color: #000;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
}

.download-btn {
    padding: 10px 18px;
    background: #10b981;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.download-btn:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.line-chart-container {
    position: relative;
    height: 300px;
    padding: 20px 0;
}

.line-chart {
    position: relative;
    height: 100%;
    display: flex;
    align-items: flex-end;
}

.chart-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.data-points {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 100%;
    width: 100%;
    position: relative;
    z-index: 2;
}

.data-point {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    position: relative;
}

.point-dot {
    width: 12px;
    height: 12px;
    background: #000;
    border: 3px solid #fff;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.point-dot:hover {
    width: 16px;
    height: 16px;
    background: #333;
    transform: scale(1.2);
}

.point-value {
    position: absolute;
    top: -30px;
    background: #000;
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}

.point-dot:hover + .point-value {
    opacity: 1;
}

.point-label {
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-align: center;
    transform: rotate(-45deg);
    transform-origin: top center;
    margin-top: 20px;
    white-space: nowrap;
}

.donut-chart {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.donut-svg {
    width: 200px;
    height: 200px;
}

.status-legend {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.legend-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #fafafa;
    border-radius: 8px;
}

.legend-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.legend-value {
    font-weight: 700;
    color: #000;
}

.top-products-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.product-rank-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px;
    background: #fafafa;
    border-radius: 12px;
    transition: all 0.3s;
}

.product-rank-item:hover {
    background: #f5f5f5;
    transform: translateX(5px);
}

.rank-badge {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #000;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.rank-badge.gold {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    color: #000;
}

.rank-badge.silver {
    background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%);
    color: #000;
}

.rank-badge.bronze {
    background: linear-gradient(135deg, #CD7F32 0%, #B87333 100%);
    color: #fff;
}

.product-rank-info {
    flex: 1;
}

.product-rank-name {
    font-weight: 600;
    font-size: 15px;
    color: #000;
    margin-bottom: 4px;
}

.product-rank-meta {
    font-size: 13px;
    color: #888;
}

.product-rank-sales {
    text-align: right;
}

.sales-amount {
    font-weight: 700;
    font-size: 16px;
    color: #000;
    margin-bottom: 2px;
}

.sales-units {
    font-size: 12px;
    color: #888;
}

@media (max-width: 1200px) {
    .reports-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    .sidebar {
        width: 220px;
    }
    .main-content {
        margin-left: 220px;
        padding: 25px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    .menu-item {
        padding: 16px;
        justify-content: center;
    }
    .main-content {
        margin-left: 70px;
        padding: 20px;
    }
    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .page-title {
        font-size: 24px;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .line-chart-container {
        height: 200px;
    }
    .point-label {
        font-size: 9px;
    }
    .chart-controls {
        flex-direction: column;
        align-items: stretch;
    }
}
/* Export Dropdown Styles */
.export-dropdown {
    position: relative;
}

.export-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow: hidden;
}

.export-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.export-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border-bottom: 1px solid #f0f0f0;
}

.export-option:last-child {
    border-bottom: none;
}

.export-option:hover {
    background: #f8f8f8;
    padding-left: 22px;
}

.export-option.highlight {
    background: #f0fdf4;
    color: #059669;
    font-weight: 600;
}

.export-option.highlight:hover {
    background: #dcfce7;
}

.export-option svg {
    opacity: 0.5;
}

.export-option:hover svg {
    opacity: 1;
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="images/logoo.png" alt="Happy Sprays">
    </div>
    <nav class="sidebar-menu">
        <a href="admin_dashboard.php" class="menu-item active">Dashboard</a>
        <a href="orders.php" class="menu-item">Orders</a>
        <a href="products_list.php" class="menu-item">Products</a>
        <a href="users.php" class="menu-item">Customers</a>
        <a href="admin_contact_messages.php" class="menu-item">Messages<?php if ($stats['unread_messages'] > 0): ?><span class="unread-badge"><?= $stats['unread_messages'] ?></span><?php endif; ?></a>
    </nav>
    <div class="sidebar-footer">
        <a href="customer_logout.php" class="logout-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Log out
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1 class="page-title">Dashboard</h1>
        <div class="welcome-text">Welcome, Admin</div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-icon">🛍️</div>
            <div class="stat-value"><?= $stats['total_products'] ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Customers</div>
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $stats['total_customers'] ?></div>
            <div class="stat-change positive">↑ <?= $newCustomersThisMonth['count'] ?> new this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $stats['total_orders'] ?></div>
            <div class="stat-change <?= $ordersGrowth >= 0 ? 'positive' : 'negative' ?>">
                <?= $ordersGrowth >= 0 ? '↑' : '↓' ?> <?= abs(number_format($ordersGrowth, 1)) ?>% from last month
            </div>
        </div>
        
        <div class="stat-card dark">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-icon">💰</div>
            <div class="stat-value">₱<?= number_format($orderStats['total_revenue'] ?? 0, 2) ?></div>
            <div class="stat-change <?= $revenueGrowth >= 0 ? 'positive' : 'negative' ?>">
                <?= $revenueGrowth >= 0 ? '↑' : '↓' ?> <?= abs(number_format($revenueGrowth, 1)) ?>% from last month
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Recent Orders</h3>
                <a href="orders.php" class="view-all">View All</a>
            </div>
            
            <div class="order-list">
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">No orders yet</div>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></h4>
                                <div class="order-meta">
                                    <?= htmlspecialchars($order['customer_firstname'] . ' ' . $order['customer_lastname']) ?> • 
                                    ₱<?= number_format($order['total_amount'], 2) ?>
                                </div>
                            </div>
                            <div class="status-badge status-<?= strtolower(str_replace(' ', '-', $order['order_status'])) ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Low Stock Alert</h3>
                <a href="products_list.php" class="view-all">View All</a>
            </div>
            
            <?php if (empty($lowStockProducts)): ?>
                <div class="empty-state">All products have sufficient stock</div>
            <?php else: ?>
                <?php foreach ($lowStockProducts as $product): ?>
                    <div class="product-item">
                        <div class="product-name"><?= htmlspecialchars($product['perfume_name']) ?></div>
                        <div class="stock-warning">Stock: <?= $product['stock'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- REPORTS SECTION -->
    <div class="reports-section">
        <h2 class="section-title">Sales Reports & Analytics</h2>
        
        <div class="reports-grid">
            <!-- Sales Trend Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Monthly Sales Trend</h3>
                
                <div class="chart-controls">
    <select class="range-select" onchange="window.location.href='?range='+this.value">
        <option value="1" <?= $reportRange == 1 ? 'selected' : '' ?>>Last 1 Month</option>
        <option value="3" <?= $reportRange == 3 ? 'selected' : '' ?>>Last 3 Months</option>
        <option value="6" <?= $reportRange == 6 ? 'selected' : '' ?>>Last 6 Months</option>
        <option value="9" <?= $reportRange == 9 ? 'selected' : '' ?>>Last 9 Months</option>
        <option value="12" <?= $reportRange == 12 ? 'selected' : '' ?>>Last 12 Months</option>
        <option value="0" <?= $reportRange == 0 ? 'selected' : '' ?>>All Time</option>
    </select>
    
    <div class="export-dropdown">
        <button class="download-btn" onclick="toggleExportMenu()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Export to Excel
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>
        
        <div class="export-menu" id="exportMenu">
            <a href="export_sales_report.php?range=1" class="export-option">
                <span>Last 1 Month</span>
            </a>
            <a href="export_sales_report.php?range=3" class="export-option">
                <span>Last 3 Months</span>
            </a>
            <a href="export_sales_report.php?range=6" class="export-option">
                <span>Last 6 Months</span>
            </a>
            <a href="export_sales_report.php?range=9" class="export-option">
                <span>Last 9 Months</span>
            </a>
            <a href="export_sales_report.php?range=12" class="export-option">
                <span>Last 12 Months</span>
            </a>
            <a href="export_sales_report.php?range=0" class="export-option highlight">
                <span>All Time Data</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </a>
        </div>
    </div>
</div>
                
                <div class="line-chart-container">
                    <svg class="chart-line" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="lineGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#000;stop-opacity:0.3" />
                                <stop offset="100%" style="stop-color:#000;stop-opacity:0" />
                            </linearGradient>
                        </defs>
                        <?php
                        $maxSales = max(array_column($monthlySales, 'sales'));
                        if ($maxSales == 0) $maxSales = 1; // Prevent division by zero
                        $points = [];
                        $pathData = "M ";
                        $areaData = "M 0 100 ";
                        
                        foreach ($monthlySales as $index => $data) {
                            $x = count($monthlySales) > 1 ? ($index / (count($monthlySales) - 1)) * 100 : 50;
                            $y = $maxSales > 0 ? 100 - (($data['sales'] / $maxSales) * 90) : 100;
                            $points[] = ['x' => $x, 'y' => $y];
                            $pathData .= "$x $y ";
                            $areaData .= "L $x $y ";
                        }
                        $areaData .= "L 100 100 Z";
                        ?>
                        <path d="<?= $areaData ?>" fill="url(#lineGradient)" />
                        <polyline points="<?= implode(' ', array_map(function($p) { return $p['x'].','.$p['y']; }, $points)) ?>" 
                                  fill="none" stroke="#000" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    
                    <div class="data-points">
                        <?php foreach ($monthlySales as $index => $data): 
                            $y = $maxSales > 0 ? (($data['sales'] / $maxSales) * 90) : 0;
                        ?>
                            <div class="data-point">
                                <div style="height: <?= $y ?>%; display: flex; align-items: flex-end;">
                                    <div class="point-dot"></div>
                                    <div class="point-value">₱<?= number_format($data['sales'], 2) ?></div>
                                </div>
                                <div class="point-label"><?= $data['month'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="chart-container">
                <h3 class="chart-title">Order Status Distribution</h3>
                <div class="status-legend">
                    <?php
                    $statusColors = [
                        'pending' => '#fbbf24',
                        'processing' => '#3b82f6',
                        'out for delivery' => '#8b5cf6',
                        'received' => '#10b981',
                        'cancelled' => '#ef4444'
                    ];
                    $totalOrders = array_sum($statusDistribution);
                    foreach ($statusDistribution as $status => $count):
                        $percentage = $totalOrders > 0 ? ($count / $totalOrders) * 100 : 0;
                    ?>
                        <div class="legend-item">
                            <div class="legend-label">
                                <div class="legend-color" style="background: <?= $statusColors[$status] ?>"></div>
                                <span><?= ucwords($status) ?></span>
                            </div>
                            <div class="legend-value"><?= $count ?> (<?= number_format($percentage, 1) ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="chart-container">
            <div class="card-header">
                <h3 class="chart-title">Top Selling Products (<?= $currentYear ?>)</h3>
                <a href="products_list.php" class="view-all">View All Products</a>
            </div>
            
            <?php if (empty($topProducts)): ?>
                <div class="empty-state">No sales data available</div>
            <?php else: ?>
                <div class="top-products-list">
                    <?php foreach ($topProducts as $index => $product): 
                        $rankClass = '';
                        if ($index === 0) $rankClass = 'gold';
                        elseif ($index === 1) $rankClass = 'silver';
                        elseif ($index === 2) $rankClass = 'bronze';
                    ?>
                        <div class="product-rank-item">
                            <div class="rank-badge <?= $rankClass ?>">
                                <?= $index + 1 ?>
                            </div>
                            <div class="product-rank-info">
                                <div class="product-rank-name"><?= htmlspecialchars($product['perfume_name']) ?></div>
                                <div class="product-rank-meta">₱<?= number_format($product['perfume_price'], 2) ?> per unit</div>
                            </div>
                            <div class="product-rank-sales">
                                <div class="sales-amount">₱<?= number_format($product['revenue'], 2) ?></div>
                                <div class="sales-units"><?= $product['total_sold'] ?> units sold</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    menu.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.export-dropdown');
    const menu = document.getElementById('exportMenu');
    
    if (!dropdown.contains(event.target)) {
        menu.classList.remove('show');
    }
});
</script>
</body>
</html>