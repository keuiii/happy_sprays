<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if user is logged in AND is an admin
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

// Get the range parameter (0 means all time)
$range = isset($_GET['range']) ? intval($_GET['range']) : 12;
$isAllTime = ($range === 0);
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$currentYear = date('Y');

// ============ DASHBOARD STATISTICS ============
$stats = [
    'total_products' => $db->getProductsCount(),
    'total_customers' => $db->getUsersCount(),
    'total_orders' => $db->getOrdersCount(),
    'unread_messages' => $db->getUnreadContactCount()
];

$orderStats = $db->getOrderStats();

// ============ SALES COMPARISON ============
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

// ============ MONTHLY SALES TREND ============
$monthlySales = [];
for ($i = ($range - 1); $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('F Y', strtotime("-$i months"));
    $sales = $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as orders FROM orders WHERE DATE_FORMAT(o_created_at, '%Y-%m') = ?", [$month]);
    $monthlySales[] = [
        'month' => $monthName,
        'sales' => $sales['total'],
        'orders' => $sales['orders']
    ];
}

$totalSales = array_sum(array_column($monthlySales, 'sales'));
$totalOrders = array_sum(array_column($monthlySales, 'orders'));
$averageSales = count($monthlySales) > 0 ? $totalSales / count($monthlySales) : 0;

// ============ TOP SELLING PRODUCTS ============
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
    LIMIT 10
", [$currentYear]);

// ============ ORDER STATUS DISTRIBUTION ============
$statusDistribution = [
    'Pending' => $orderStats['pending'] ?? 0,
    'Processing' => $orderStats['processing'] ?? 0,
    'Out for Delivery' => $orderStats['out for delivery'] ?? 0,
    'Received' => $orderStats['received'] ?? 0,
    'Cancelled' => $orderStats['cancelled'] ?? 0
];

// ============ CUSTOMER INSIGHTS ============
$newCustomersThisMonth = $db->fetch("SELECT COUNT(*) as count FROM customers WHERE DATE_FORMAT(cs_created_at, '%Y-%m') = ?", [$currentMonth]);
$repeatCustomersResult = $db->select("SELECT COUNT(DISTINCT customer_id) as count FROM orders GROUP BY customer_id HAVING COUNT(*) > 1");
$repeatCustomers = count($repeatCustomersResult);

// ============ LOW STOCK PRODUCTS ============
$lowStockProducts = $db->getLowStockProducts(20);

// ============ RECENT ORDERS ============
$recentOrders = $db->select("
    SELECT o.*, c.customer_firstname, c.customer_lastname 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    ORDER BY o.o_created_at DESC 
    LIMIT 20
");

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Complete_Sales_Report_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #000;
            color: #fff;
            font-weight: bold;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
        }
        .report-subtitle {
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            color: #666;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 10px 0;
            padding: 10px;
            background-color: #f0f0f0;
            border-left: 5px solid #000;
        }
        .summary-box {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
        }
        .summary-item {
            margin: 5px 0;
            font-size: 14px;
        }
        .summary-label {
            font-weight: bold;
            display: inline-block;
            width: 250px;
        }
        .total-row {
            background-color: #000;
            color: #fff;
            font-weight: bold;
        }
        .highlight-row {
            background-color: #fffacd;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .positive {
            color: green;
            font-weight: bold;
        }
        .negative {
            color: red;
            font-weight: bold;
        }
        .footer {
            font-size: 11px;
            color: #666;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        .rank-medal {
            font-weight: bold;
        }
        .rank-1 { color: #FFD700; }
        .rank-2 { color: #C0C0C0; }
        .rank-3 { color: #CD7F32; }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="report-title">HAPPY SPRAYS - COMPREHENSIVE SALES REPORT</div>
    <div class="report-subtitle">Complete Business Analytics & Performance Overview</div>
    <div class="report-subtitle">Generated: <?= date('F d, Y h:i A') ?> | Report Period: Last <?= $range ?> Months</div>
    
    <!-- EXECUTIVE SUMMARY -->
    <div class="section-title">📊 EXECUTIVE SUMMARY</div>
    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Products:</span>
            <strong><?= number_format($stats['total_products']) ?></strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Customers:</span>
            <strong><?= number_format($stats['total_customers']) ?></strong> 
            (<?= number_format($newCustomersThisMonth['count']) ?> new this month)
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Orders (All Time):</span>
            <strong><?= number_format($stats['total_orders']) ?></strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Revenue (All Time):</span>
            <strong>₱<?= number_format($orderStats['total_revenue'] ?? 0, 2) ?></strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Revenue Growth (vs Last Month):</span>
            <strong class="<?= $revenueGrowth >= 0 ? 'positive' : 'negative' ?>">
                <?= $revenueGrowth >= 0 ? '↑' : '↓' ?> <?= abs(number_format($revenueGrowth, 2)) ?>%
            </strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Orders Growth (vs Last Month):</span>
            <strong class="<?= $ordersGrowth >= 0 ? 'positive' : 'negative' ?>">
                <?= $ordersGrowth >= 0 ? '↑' : '↓' ?> <?= abs(number_format($ordersGrowth, 2)) ?>%
            </strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Repeat Customers:</span>
            <strong><?= number_format($repeatCustomers) ?></strong>
        </div>
        <div class="summary-item">
            <span class="summary-label">Unread Messages:</span>
            <strong><?= number_format($stats['unread_messages']) ?></strong>
        </div>
    </div>

    <!-- MONTHLY SALES TREND -->
    <div class="section-title">📈 MONTHLY SALES TREND (Last <?= $range ?> Months)</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Number of Orders</th>
                <th class="text-right">Total Sales (₱)</th>
                <th class="text-right">Average Order Value (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthlySales as $data): 
                $avgOrderValue = $data['orders'] > 0 ? $data['sales'] / $data['orders'] : 0;
            ?>
            <tr>
                <td><?= $data['month'] ?></td>
                <td class="text-right"><?= number_format($data['orders']) ?></td>
                <td class="text-right"><?= number_format($data['sales'], 2) ?></td>
                <td class="text-right"><?= number_format($avgOrderValue, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right"><?= number_format($totalOrders) ?></td>
                <td class="text-right"><?= number_format($totalSales, 2) ?></td>
                <td class="text-right"><?= number_format($totalOrders > 0 ? $totalSales / $totalOrders : 0, 2) ?></td>
            </tr>
            
            <tr class="highlight-row">
                <td>AVERAGE PER MONTH</td>
                <td class="text-right"><?= number_format($totalOrders / max(count($monthlySales), 1), 1) ?></td>
                <td class="text-right"><?= number_format($averageSales, 2) ?></td>
                <td class="text-right">-</td>
            </tr>
        </tbody>
    </table>

    <!-- TOP SELLING PRODUCTS -->
    <div class="section-title">🏆 TOP SELLING PRODUCTS (<?= $currentYear ?>)</div>
    <table>
        <thead>
            <tr>
                <th class="text-center">Rank</th>
                <th>Product Name</th>
                <th class="text-right">Price per Unit (₱)</th>
                <th class="text-right">Units Sold</th>
                <th class="text-right">Total Revenue (₱)</th>
                <th class="text-right">% of Total Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalProductRevenue = array_sum(array_column($topProducts, 'revenue'));
            foreach ($topProducts as $index => $product): 
                $percentOfTotal = $totalProductRevenue > 0 ? ($product['revenue'] / $totalProductRevenue) * 100 : 0;
                $rankClass = '';
                $medal = '';
                if ($index === 0) { $rankClass = 'rank-1'; $medal = '🥇'; }
                elseif ($index === 1) { $rankClass = 'rank-2'; $medal = '🥈'; }
                elseif ($index === 2) { $rankClass = 'rank-3'; $medal = '🥉'; }
            ?>
            <tr>
                <td class="text-center rank-medal <?= $rankClass ?>"><?= $medal ?> <?= $index + 1 ?></td>
                <td><?= htmlspecialchars($product['perfume_name']) ?></td>
                <td class="text-right"><?= number_format($product['perfume_price'], 2) ?></td>
                <td class="text-right"><?= number_format($product['total_sold']) ?></td>
                <td class="text-right"><?= number_format($product['revenue'], 2) ?></td>
                <td class="text-right"><?= number_format($percentOfTotal, 2) ?>%</td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($topProducts)): ?>
            <tr>
                <td colspan="6" class="text-center">No sales data available for <?= $currentYear ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ORDER STATUS DISTRIBUTION -->
    <div class="section-title">📦 ORDER STATUS DISTRIBUTION</div>
    <table>
        <thead>
            <tr>
                <th>Order Status</th>
                <th class="text-right">Number of Orders</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalStatusOrders = array_sum($statusDistribution);
            foreach ($statusDistribution as $status => $count): 
                $percentage = $totalStatusOrders > 0 ? ($count / $totalStatusOrders) * 100 : 0;
            ?>
            <tr>
                <td><?= ucwords($status) ?></td>
                <td class="text-right"><?= number_format($count) ?></td>
                <td class="text-right"><?= number_format($percentage, 2) ?>%</td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right"><?= number_format($totalStatusOrders) ?></td>
                <td class="text-right">100.00%</td>
            </tr>
        </tbody>
    </table>

    <!-- LOW STOCK ALERT -->
    <div class="section-title">⚠️ LOW STOCK ALERT</div>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th class="text-right">Current Stock</th>
                <th class="text-right">Price (₱)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lowStockProducts)): ?>
            <tr>
                <td colspan="4" class="text-center">✓ All products have sufficient stock</td>
            </tr>
            <?php else: ?>
                <?php foreach ($lowStockProducts as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['perfume_name']) ?></td>
                    <td class="text-right"><?= number_format($product['stock']) ?></td>
                    <td class="text-right"><?= number_format($product['perfume_price'], 2) ?></td>
                    <td style="color: red; font-weight: bold;">⚠️ LOW STOCK</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- RECENT ORDERS -->
    <div class="section-title">🛍️ RECENT ORDERS (Last 20)</div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th class="text-right">Total Amount (₱)</th>
                <th>Status</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentOrders)): ?>
            <tr>
                <td colspan="6" class="text-center">No orders yet</td>
            </tr>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td>#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($order['customer_firstname'] . ' ' . $order['customer_lastname']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($order['o_created_at'])) ?></td>
                    <td class="text-right"><?= number_format($order['total_amount'], 2) ?></td>
                    <td><?= ucwords($order['order_status']) ?></td>
                    <td><?= ucwords($order['payment_method']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <strong>Happy Sprays - Comprehensive Sales Report</strong><br>
        This report was automatically generated by the Happy Sprays Admin Dashboard System.<br>
        Report includes: Dashboard Statistics, Monthly Sales Trends, Top Products, Order Distribution, Inventory Alerts, and Recent Orders.<br>
        For questions or concerns, please contact the system administrator.<br>
        <br>
        <em>Confidential - For Internal Use Only</em>
    </div>
</body>
</html>