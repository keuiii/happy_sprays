<?php
// orders.php - Admin Order Management Page
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

$message = '';
$messageType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = trim($_POST['status']);
    
    // Debug: Log the values
    error_log("=== ORDER STATUS UPDATE ===");
    error_log("Order ID: $orderId");
    error_log("New Status: '$newStatus'");
    error_log("POST data: " . print_r($_POST, true));
    
    $result = $db->updateOrderStatus($orderId, $newStatus);
    
    // Debug: Log the result
    error_log("Update result: " . json_encode($result));
    
    // Verify the update by checking the database
    $updatedOrder = $db->fetch("SELECT order_status FROM orders WHERE order_id = ?", [$orderId]);
    error_log("Current status in DB after update: " . ($updatedOrder['order_status'] ?? 'NOT FOUND'));
    
    if ($result['success']) {
        $_SESSION['status_message'] = "Order #$orderId status updated to '$newStatus' successfully!";
        $_SESSION['status_type'] = 'success';
    } else {
        $_SESSION['status_message'] = $result['message'];
        $_SESSION['status_type'] = 'error';
    }
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Redirect to prevent form resubmission with cache busting
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: orders.php?updated=" . $orderId . "&t=" . time());
    exit;
}

// Display session messages
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    $messageType = $_SESSION['status_type'];
    unset($_SESSION['status_message']);
    unset($_SESSION['status_type']);
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if (!empty($searchTerm)) {
    $orders = $db->searchOrders($searchTerm);
} else {
    $orders = $db->getAllOrders();
}

// Get order statistics
$stats = $db->getOrderStats();

// Get unread messages count for badge
$unreadCount = $db->getUnreadContactCount();

// Debug: Log the stats
error_log("Current stats: " . json_encode($stats));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Happy Sprays Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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

        .sidebar-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
            font-weight: 700;
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

        .logout-item svg {
            stroke: #d32f2f;
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
            grid-template-columns: repeat(6, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #fff;
            padding: 30px 28px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #999;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #000;
        }
        
        .search-bar {
            margin-bottom: 25px;
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .search-bar input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            transition: all 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #000;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }
        
        .orders-table {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #fafafa;
        }
        
        th, td {
            padding: 18px 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            font-weight: 700;
            color: #000;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-preparing { background: #e0e7ff; color: #3730a3; }
        .status-shipping { background: #ddd6fe; color: #5b21b6; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-received { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        select {
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            background: #fafafa;
            transition: all 0.3s;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        
        .alert {
            padding: 16px 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            
            .main-content {
                margin-left: 220px;
                padding: 25px;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2 {
                font-size: 20px;
            }

            .menu-item {
                padding: 16px;
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .orders-table {
                overflow-x: auto;
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
        <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
        <a href="orders.php" class="menu-item active">Orders</a>
        <a href="products_list.php" class="menu-item">Products</a>
        <a href="users.php" class="menu-item">Customers</a>
        <a href="admin_contact_messages.php" class="menu-item">Messages<?php if ($unreadCount > 0): ?><span class="unread-badge"><?= $unreadCount ?></span><?php endif; ?></a>
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
            <h1 class="page-title">Order Management</h1>
            <div class="welcome-text">Manage all customer orders</div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Processing</div>
                <div class="stat-value"><?= $stats['processing'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Out for Delivery</div>
                <div class="stat-value"><?= $stats['out for delivery'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Received</div>
                <div class="stat-value"><?= $stats['received'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Cancelled</div>
                <div class="stat-value"><?= $stats['cancelled'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
            </div>
        </div>
        
        <div class="search-bar">
            <form method="GET">
                <input type="text" name="search" placeholder="Search by customer name, email, or order ID..." value="<?= htmlspecialchars($searchTerm) ?>">
            </form>
        </div>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <h3>No Orders Found</h3>
                                <p>Orders will appear here once customers place them.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['order_id'] ?></strong></td>
                                <td>
                                    <?php 
                                    $customer = $db->fetch("SELECT customer_firstname, customer_lastname FROM customers WHERE customer_id = ?", [$order['customer_id']]);
                                    echo htmlspecialchars($customer['customer_firstname'] . ' ' . $customer['customer_lastname']);
                                    ?>
                                </td>
                                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                                <td>
                                    <!-- Debug: Current status = <?= htmlspecialchars($order['order_status']) ?> -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php 
                                            foreach ($db->getOrderStatuses() as $key => $label): 
                                            ?>
                                                <option value="<?= htmlspecialchars($key) ?>" <?= strtolower(trim($order['order_status'])) === strtolower(trim($key)) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['o_created_at'])) ?></td>
                                <td>
                                    <a href="order_view.php?id=<?= $order['order_id'] ?>" class="btn btn-view">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
// Auto-dismiss alert after 2 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 2000);
    }
});
</script>

</body>
</html>