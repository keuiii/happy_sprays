<?php
// orders.php - Admin Order Management Page with PHPMailer Email Notifications
session_start();
require_once 'classes/database.php';

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}
define('SMTP_HOST', 'smtp.hostinger.com');           // SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'happyspray@happyspray.shop');
define('SMTP_PASSWORD', 'JANJANbuen@5');
define('SMTP_FROM_EMAIL', 'happyspray@happyspray.shop');
define('SMTP_FROM_NAME', 'Happy Sprays');
define('SMTP_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);

// ============================================================================
// FUNCTION: Send Order Status Email using PHPMailer
// ============================================================================
function sendOrderStatusEmail($orderId, $customerEmail, $customerName, $status, $db) {
    $statusMessages = [
        'processing' => [
            'subject' => 'Your Order is Being Processed',
            'message' => 'Great news! Your order #' . $orderId . ' is now being processed. We\'re getting your items ready for shipment.',
            'icon' => '📦'
        ],
        'out for delivery' => [
            'subject' => 'Your Order is Out for Delivery',
            'message' => 'Your order #' . $orderId . ' is now out for delivery! It should arrive soon. Please be available to receive your package.',
            'icon' => '🚚'
        ],
        'received' => [
            'subject' => 'Order Received - Thank You!',
            'message' => 'Your order #' . $orderId . ' has been marked as received. Thank you for shopping with Happy Sprays! We hope you enjoy your purchase.',
            'icon' => '✅'
        ],
        'cancelled' => [
            'subject' => 'Your Order Has Been Cancelled',
            'message' => 'Your order #' . $orderId . ' has been cancelled. If you have any questions, please contact our support team.',
            'icon' => '❌'
        ]
    ];

    $statusInfo = $statusMessages[strtolower($status)] ?? null;
    
    if (!$statusInfo) {
        return ['success' => false, 'error' => 'Invalid status'];
    }

    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'happyspray@happyspray.shop';
        $mail->Password   = 'JANJANbuen@5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Optional: Disable SSL verification for localhost testing
        // $mail->SMTPOptions = array(
        //     'ssl' => array(
        //         'verify_peer' => false,
        //         'verify_peer_name' => false,
        //         'allow_self_signed' => true
        //     )
        // );

        // Recipients
        $mail->setFrom('happyspray@happyspray.shop', 'Happy Sprays Admin');
        $mail->addAddress($customerEmail, $customerName);
        $mail->addReplyTo('happyspray@happyspray.shop', 'Happy Sprays');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $statusInfo['subject'];
        $mail->CharSet = 'UTF-8';
        
        // Email HTML Body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: #ffffff;
                }
                .header { 
                    background: #000000; 
                    color: #ffffff; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                    letter-spacing: 2px;
                }
                .content { 
                    background: #f9f9f9; 
                    padding: 40px 30px; 
                    border: 1px solid #e0e0e0; 
                }
                .content h2 {
                    color: #000;
                    margin-top: 0;
                    margin-bottom: 20px;
                    font-size: 24px;
                }
                .status-box {
                    background: #ffffff;
                    border-left: 4px solid #10b981;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .status-badge { 
                    display: inline-block; 
                    padding: 8px 16px; 
                    border-radius: 20px; 
                    font-weight: bold; 
                    font-size: 14px;
                    margin-top: 10px;
                }
                .status-processing { background: #dbeafe; color: #1e40af; }
                .status-delivery { background: #ddd6fe; color: #5b21b6; }
                .status-received { background: #d1fae5; color: #065f46; }
                .status-cancelled { background: #fee2e2; color: #991b1b; }
                .footer { 
                    text-align: center; 
                    padding: 30px 20px; 
                    font-size: 12px; 
                    color: #666;
                    background: #f5f5f5;
                }
                .footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 30px;
                    background: #000000;
                    color: #ffffff !important;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>HAPPY SPRAYS</h1>
                </div>
                <div class='content'>
                    <h2>" . $statusInfo['icon'] . " Order Update</h2>
                    <p>Hello <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
                    <p>" . $statusInfo['message'] . "</p>
                    
                    <div class='status-box'>
                        <p style='margin: 0 0 10px 0;'><strong>Order Number:</strong> #" . $orderId . "</p>
                        <p style='margin: 0;'><strong>Status:</strong> 
                            <span class='status-badge status-" . strtolower(str_replace(' ', '-', $status)) . "'>
                                " . ucwords($status) . "
                            </span>
                        </p>
                    </div>
                    
                    <p>You can view your complete order details by logging into your account.</p>
                    
                    <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/customer_dashboard.php' class='btn'>View My Orders</a>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p style='margin-top: 15px;'>&copy; " . date('Y') . " Happy Sprays. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version for email clients that don't support HTML
        $mail->AltBody = strip_tags($statusInfo['message']) . "\n\nOrder #" . $orderId . "\nStatus: " . ucwords($status);

        // Send email
        $mail->send();
        
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        // Log the error
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// ============================================================================
// HANDLE STATUS UPDATE
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = trim($_POST['status']);
    
    error_log("=== ORDER STATUS UPDATE ===");
    error_log("Order ID: $orderId");
    error_log("New Status: '$newStatus'");
    
    // Get customer details before updating
    $orderDetails = $db->fetch("
        SELECT o.*, c.customer_email, c.customer_firstname, c.customer_lastname 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        WHERE o.order_id = ?
    ", [$orderId]);
    
    if ($orderDetails) {
        $result = $db->updateOrderStatus($orderId, $newStatus);
        
        if ($result['success']) {
            // Send email notification
            $customerName = $orderDetails['customer_firstname'] . ' ' . $orderDetails['customer_lastname'];
            $emailResult = sendOrderStatusEmail(
                $orderId, 
                $orderDetails['customer_email'], 
                $customerName, 
                $newStatus, 
                $db
            );
            
            $successMessage = "Order #$orderId status updated to '$newStatus' successfully!";
            
            if ($emailResult['success']) {
                $successMessage .= " Email notification sent to customer.";
            } else {
                $successMessage .= " (Email notification failed: " . htmlspecialchars($emailResult['error']) . ")";
                error_log("Email failed: " . $emailResult['error']);
            }
            
            $_SESSION['status_message'] = $successMessage;
            $_SESSION['status_type'] = 'success';
        } else {
            $_SESSION['status_message'] = $result['message'];
            $_SESSION['status_type'] = 'error';
        }
    } else {
        $_SESSION['status_message'] = "Order not found.";
        $_SESSION['status_type'] = 'error';
    }
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
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

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Handle search
$searchTerm = $_GET['search'] ?? '';
if (!empty($searchTerm)) {
    // Get total count for pagination
    $totalOrders = count($db->searchOrders($searchTerm));
    $orders = $db->searchOrders($searchTerm, $itemsPerPage, $offset);
} else {
    // Get total count for pagination
    $totalOrders = count($db->getAllOrders());
    $orders = $db->getAllOrders($itemsPerPage, $offset);
}

$totalPages = ceil($totalOrders / $itemsPerPage);

// Get order statistics
$stats = $db->getOrderStats();

// Get unread messages count for badge
$unreadCount = $db->getUnreadContactCount();
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
            background: #868686ff;
            color: #fff;
        }

        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .btn-view svg {
            vertical-align: middle;
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
                <input type="text" name="search" placeholder="🔍 Search by customer name, email, or order ID..." value="<?= htmlspecialchars($searchTerm) ?>">
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
                                <a href="order_view.php?id=<?= $order['order_id'] ?>" 
                                class="btn btn-view" 
                                title="View Order Details">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                </a>
                            </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="page-btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                            <a href="?page=<?= $i ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                               class="page-btn <?= $i == $currentPage ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php elseif (abs($i - $currentPage) == 3): ?>
                            <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="page-btn">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
// Auto-dismiss alert after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    }
});
</script>

</body>
</html>