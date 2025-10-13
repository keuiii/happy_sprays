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

// Monthly sales data for chart (last 6 months)
$monthlySales = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    $sales = $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE_FORMAT(o_created_at, '%Y-%m') = ?", [$month]);
    $monthlySales[] = ['month' => $monthName, 'sales' => $sales['total']];
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

/* NEW REPORTS STYLES */
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

.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 250px;
    gap: 15px;
    padding: 20px 0;
}

.bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.bar-fill {
    width: 100%;
    background: linear-gradient(180deg, #000 0%, #333 100%);
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    min-height: 20px;
}

.bar-fill:hover {
    background: linear-gradient(180deg, #333 0%, #555 100%);
    transform: translateY(-5px);
}

.bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: 700;
    color: #000;
    white-space: nowrap;
}

.bar-label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-align: center;
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
    .bar-chart {
        height: 200px;
        gap: 8px;
    }
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

    <!-- NEW REPORTS SECTION -->
    <div class="reports-section">
        <h2 class="section-title">Sales Reports & Analytics</h2>
        
        <div class="reports-grid">
            <!-- Sales Trend Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Monthly Sales Trend (Last 6 Months)</h3>
                <div class="bar-chart">
                    <?php 
                    $maxSales = max(array_column($monthlySales, 'sales'));
                    foreach ($monthlySales as $data): 
                        $height = $maxSales > 0 ? ($data['sales'] / $maxSales) * 100 : 0;
                    ?>
                        <div class="bar">
                            <div class="bar-fill" style="height: <?= $height ?>%">
                                <div class="bar-value">₱<?= number_format($data['sales'], 0) ?></div>
                            </div>
                            <div class="bar-label"><?= $data['month'] ?></div>
                        </div>
                    <?php endforeach; ?>
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

</body>
</html>