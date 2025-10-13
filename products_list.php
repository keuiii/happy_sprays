<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $db->delete("DELETE FROM perfumes WHERE perfume_id = ?", [$id]);
        $message = "Product deleted successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Failed to delete product.";
        $messageType = "error";
    }
}

// Get filters
$sexFilter = isset($_GET['sex']) ? $_GET['sex'] : null;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : null;

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count for pagination
$totalProducts = count($db->getPerfumes($sexFilter, $searchQuery));
$products = $db->getPerfumes($sexFilter, $searchQuery, $itemsPerPage, $offset);
$totalPages = ceil($totalProducts / $itemsPerPage);

// Get unread messages count for badge
$unreadCount = $db->getUnreadContactCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products Management - Happy Sprays Admin</title>
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
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.top-bar-header {
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

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-primary {
    background: #000;
    color: #fff;
}

.btn-primary:hover {
    background: #333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-edit {
    background: #667eea;
    color: #fff;
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 8px;
}

.btn-edit:hover {
    background: #5568d3;
}

.btn-delete {
    background: #ef4444;
    color: #fff;
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 8px;
}

.btn-delete:hover {
    background: #dc2626;
}

.filters-bar {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 30px;
}

.search-box {
    flex: 1;
    min-width: 300px;
}

.search-box input {
    width: 100%;
    padding: 12px 18px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    background: #fafafa;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #000;
    background: #fff;
}

.filter-select {
    padding: 12px 18px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    background: #fafafa;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-select:focus {
    outline: none;
    border-color: #000;
    background: #fff;
}

.message {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
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

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.product-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.product-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
}

.product-info {
    padding: 20px;
}

.product-name {
    font-weight: 700;
    font-size: 17px;
    margin-bottom: 6px;
    color: #000;
}

.product-brand {
    color: #888;
    font-size: 14px;
    margin-bottom: 12px;
}

.product-price {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    margin-bottom: 12px;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #666;
    margin-bottom: 18px;
    padding: 10px 0;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
}

.stock-badge {
    background: #d1fae5;
    color: #065f46;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 12px;
}

.stock-low {
    background: #fee2e2;
    color: #991b1b;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 12px;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #999;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.empty-state h3 {
    font-size: 24px;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #888;
    margin-bottom: 25px;
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
    
    .page-title {
        font-size: 24px;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
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
        <a href="products_list.php" class="menu-item active">Products</a>
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
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="top-bar-header">
            <h1 class="page-title">Products Management</h1>
            <a href="add_products.php" class="btn btn-primary">+ Add New Product</a>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <form method="GET" action="products_list.php" class="filters-bar">
        <div class="search-box">
            <input type="text" 
                   name="search" 
                   placeholder="🔍 Search products..."
                   value="<?= htmlspecialchars($searchQuery ?? '') ?>">
        </div>
        
        <select name="sex" class="filter-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <option value="Male" <?= $sexFilter === 'Male' ? 'selected' : '' ?>>For Him</option>
            <option value="Female" <?= $sexFilter === 'Female' ? 'selected' : '' ?>>For Her</option>
        </select>
        
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <h3>No Products Found</h3>
            <p>Start by adding your first product.</p>
            <a href="add_products.php" class="btn btn-primary" style="margin-top: 20px;">Add Product</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): 
                // Fetch the product image from images table
                $imageData = $db->fetch("
                    SELECT file_path 
                    FROM images 
                    WHERE perfume_id = ? 
                    LIMIT 1
                ", [$product['perfume_id']]);
                
                $imagePath = $imageData['file_path'] ?? 'images/DEFAULT.png';
            ?>
                <div class="product-card">
                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                         alt="<?= htmlspecialchars($product['perfume_name']) ?>"
                         class="product-image"
                         onerror="this.src='images/DEFAULT.png'">
                    
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product['perfume_name']) ?></div>
                        <div class="product-brand"><?= htmlspecialchars($product['perfume_brand']) ?></div>
                        <div class="product-price">₱<?= number_format($product['perfume_price'], 2) ?></div>
                        
                        <div class="product-meta">
                            <span><?= htmlspecialchars($product['perfume_ml']) ?>ml</span>
                            <span class="<?= $product['stock'] < 10 ? 'stock-low' : 'stock-badge' ?>">
                                Stock: <?= $product['stock'] ?>
                            </span>
                        </div>
                        
                        <div class="product-actions">
                            <a href="edit_product.php?id=<?= $product['perfume_id'] ?>" class="btn btn-edit">
                                Edit
                            </a>
                            <a href="products_list.php?delete=<?= $product['perfume_id'] ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Delete this product?')">
                                Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?><?= !empty($sexFilter) ? '&sex=' . urlencode($sexFilter) : '' ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" class="page-btn">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                        <a href="?page=<?= $i ?><?= !empty($sexFilter) ? '&sex=' . urlencode($sexFilter) : '' ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" 
                           class="page-btn <?= $i == $currentPage ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php elseif (abs($i - $currentPage) == 3): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= !empty($sexFilter) ? '&sex=' . urlencode($sexFilter) : '' ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" class="page-btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>