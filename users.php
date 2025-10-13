<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

// Handle delete customer
if (isset($_GET['delete'])) {
    $customer_id = intval($_GET['delete']);
    try {
        $db->delete("DELETE FROM customers WHERE customer_id = ?", [$customer_id]);
        $message = "Customer deleted successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Failed to delete customer.";
        $messageType = "error";
    }
}

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$searchTerm = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);

    // Prepare and execute search query safely
    $stmt = $db->getConnection()->prepare("
        SELECT * FROM customers 
        WHERE customer_firstname LIKE ? 
           OR customer_lastname LIKE ? 
           OR customer_email LIKE ? 
           OR customer_username LIKE ?
        ORDER BY cs_created_at DESC
    ");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"]);
    $allCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCustomers = count($allCustomers);
    
    // Apply pagination to search results
    $customers = array_slice($allCustomers, $offset, $itemsPerPage);
} else {
    // Get total count for pagination
    $totalCustomers = count($db->getAllCustomers());
    $customers = $db->getAllCustomers($itemsPerPage, $offset);
}

$totalPages = ceil($totalCustomers / $itemsPerPage);


// Get unread messages count for badge
$unreadCount = $db->getUnreadContactCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers - Happy Sprays Admin</title>
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

.message {
    padding: 16px 20px;
    border-radius: 12px;
    margin-top: 20px;
    font-size: 14px;
    font-weight: 500;
}

.message.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.customers-table {
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

th {
    padding: 18px 20px;
    text-align: left;
    font-weight: 700;
    color: #000;
    border-bottom: 2px solid #e8e8e8;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 18px 20px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

tbody tr:hover {
    background: #fafafa;
}

tbody tr:last-child td {
    border-bottom: none;
}

.customer-name {
    font-weight: 600;
    color: #000;
    font-size: 15px;
    margin-bottom: 4px;
}

.customer-email {
    color: #888;
    font-size: 13px;
}

.badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-verified {
    background: #d1fae5;
    color: #065f46;
}

.badge-unverified {
    background: #fef3c7;
    color: #92400e;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-delete {
    background: #868686ff;
    color: #fff;
}

.btn-delete:hover {
    background: #ef4444;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
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
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .main-content {
        margin-left: 70px;
        padding: 20px;
    }
    
    .customers-table {
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
        <a href="orders.php" class="menu-item">Orders</a>
        <a href="products_list.php" class="menu-item">Products</a>
        <a href="users.php" class="menu-item active">Customers</a>
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
        <h1 class="page-title">Customers Management</h1>
        <div class="welcome-text">Manage all registered customers</div>
    </div>
        
    <?php if (isset($message)): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- 🔍 Search Bar -->
    <form method="get" action="users.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
               placeholder="🔍 Search customer by name, email, or username..." 
               style="flex: 1; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; font-size: 14px;">
        <button type="submit" class="btn" 
                style="background: #000; color: #fff; border-radius: 8px; padding: 10px 18px; font-weight: 600; cursor: pointer;">
            Search
        </button>
        <?php if (!empty($searchTerm)): ?>
            <a href="users.php" class="btn" style="background: #e5e7eb; color: #333; padding: 10px 18px; border-radius: 8px; text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>

    <div class="customers-table">
        <?php if (empty($customers)): ?>
            <div class="empty-state">
                <h3>No Customers Found</h3>
                <p><?= !empty($searchTerm) ? "No results for '".htmlspecialchars($searchTerm)."'" : "Customers will appear here once they register." ?></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= $customer['customer_id'] ?></td>
                            <td>
                                <div class="customer-name">
                                    <?= htmlspecialchars($customer['customer_firstname'] . ' ' . $customer['customer_lastname']) ?>
                                </div>
                                <div class="customer-email">
                                    <?= htmlspecialchars($customer['customer_email']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($customer['customer_username']) ?></td>
                            <td>
                                <?php if ($customer['is_verified'] == 1): ?>
                                    <span class="badge badge-verified">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-unverified">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($customer['cs_created_at'])) ?></td>
                            <td>
                                <a href="users.php?delete=<?= $customer['customer_id'] ?>" 
                                    class="btn btn-delete"
                                    title="Delete Customer"
                                    onclick="return confirm('Delete this customer? This will also delete their orders.')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" 
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5-3h4a2 2 0 0 1 2 2v1H8V5a2 2 0 0 1 2-2z"></path>
                                </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
        <?php endif; ?>
    </div>
</div>

</body>
</html>