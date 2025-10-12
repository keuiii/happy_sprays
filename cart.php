<?php
session_start();
require_once "classes/database.php";
$db = Database::getInstance();

// --- ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    // Start output buffering to prevent any accidental output
    ob_start();
    
    $result = $db->addToCart(
        $_POST['id'],
        $_POST['name'] ?? '',
        $_POST['price'] ?? 0,
        $_POST['image'] ?? '', 
        $_POST['qty'] ?? 1
    );

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        // Clear any accidental output
        ob_clean();
        
        // Send only the result with proper headers
        header('Content-Type: text/plain; charset=utf-8');
        echo trim($result); // Returns 'added' or 'already_exists'
        exit;
    }
    
    ob_end_clean();
}

// --- UPDATE QUANTITY ---
if (isset($_POST['update_qty'])) {
    $db->updateCartQuantity($_POST['id'], $_POST['quantity']);
    header("Location: cart.php");
    exit;
}

// --- REMOVE ITEM ---
if (isset($_GET['remove'])) {
    $db->removeFromCart($_GET['remove']);
    header("Location: cart.php");
    exit;
}

// Check for checkout error message
$checkoutError = '';
if (isset($_SESSION['checkout_error'])) {
    $checkoutError = $_SESSION['checkout_error'];
    unset($_SESSION['checkout_error']);
}

$cart = $_SESSION['cart'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Cart - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin: 0; padding: 0; box-sizing: border-box;}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #fff;
    color: #000;
    min-height: 100vh;
    padding: 0;
    padding-top: 80px;
}

/* Top Navbar */
.top-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
}

.back-arrow {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
    z-index: 10;
}

.back-arrow:hover {
    opacity: 0.6;
}

.back-arrow svg {
    width: 24px;
    height: 24px;
    stroke: #000;
    stroke-width: 2;
}

.logo {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #000;
    margin-left: 40px;
}

.nav-icons {
    display: flex;
    align-items: center;
    gap: 25px;
}

.nav-icon {
    color: #000;
    text-decoration: none;
    transition: opacity 0.2s;
    position: relative;
    display: inline-block;
}

.nav-icon:hover {
    opacity: 0.6;
}

.nav-icon svg {
    width: 24px;
    height: 24px;
    stroke: #000;
    stroke-width: 2;
}

.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff0000;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 50%;
    min-width: 20px;
    text-align: center;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 20px 40px 20px;
    background: #fff;
}

h1 {
    font-family: 'Playfair Display', serif;
    text-align: center;
    font-size: 42px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #000;
    font-weight: 700;
    border-bottom: 2px solid #000;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    background: #fff;
    border: 2px solid #000;
    border-bottom: none;
}

th {
    background: #fff;
    color: #000;
    padding: 18px 15px;
    text-align: center;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #000;
    border-right: 2px solid #000;
}

th:last-child {
    border-right: none;
}

td {
    padding: 20px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
    border-right: 2px solid #000;
    vertical-align: middle;
}

td:last-child {
    border-right: none;
}

tr:last-child td {
    border-bottom: 2px solid #000;
}

tr:hover {
    background: #fafafa;
}

img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid #000;
}

.product-name {
    font-weight: 600;
    font-size: 16px;
}

.price-cell {
    font-weight: 700;
    font-size: 18px;
}

.qty-input {
    width: 80px;
    padding: 10px;
    text-align: center;
    border: 2px solid #000;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    background: #fff;
    margin: 0 10px;
}

.qty-input:focus {
    outline: none;
    border-color: #000;
}

.qty-btn {
    width: 40px;
    height: 40px;
    background: #fff;
    color: #000;
    border: 2px solid #000;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.qty-btn:hover {
    background: #000;
    color: #fff;
    border-color: #000;
}

.qty-btn:active {
    transform: scale(0.95);
}

.qty-form {
    display: flex;
    gap: 0;
    justify-content: center;
    align-items: center;
}

.remove-btn {
    padding: 10px 20px;
    background: #000;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    border: 2px solid #000;
    font-weight: 600;
    transition: 0.2s;
    display: inline-block;
    font-size: 14px;
}

.remove-btn:hover {
    background: #fff;
    color: #000;
}

/* Subtotal row styling */
.subtotal-row {
    background: #f5f5f5;
    border-top: 2px solid #000;
}

.subtotal-row td {
    padding: 20px 15px;
    font-weight: 700;
    border-bottom: 2px solid #000 !important;
}

.subtotal-amount {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    text-align: center;
}

.grand-total-row {
    background: #fafafa;
    border-top: 3px solid #000;
}

.grand-total-row th {
    background: #fafafa;
    color: #000;
    font-size: 18px;
    padding: 25px;
    border-bottom: 2px solid #000;
}

.grand-total-amount {
    font-size: 24px;
    font-weight: 700;
}

/* Checkbox styling */
.cart-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #000;
}

.select-all-container {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.select-all-container label {
    cursor: pointer;
    user-select: none;
}

.checkout-btn {
    display: block;
    width: 300px;
    margin: 40px auto;
    padding: 18px;
    border: 2px solid #000;
    background: #000;
    color: #fff;
    text-align: center;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: 0.2s;
}

.checkout-btn:hover {
    background: #fff;
    color: #000;
}

.checkout-btn.disabled {
    background: #ccc;
    border-color: #ccc;
    color: #666;
    cursor: not-allowed;
    pointer-events: none;
}

.checkout-btn.disabled:hover {
    background: #ccc;
    color: #666;
}

.empty {
    text-align: center;
    padding: 80px 20px;
    color: #666;
}

.empty-icon {
    font-size: 100px;
    margin-bottom: 30px;
    opacity: 0.3;
}

.empty p {
    font-size: 20px;
    margin-bottom: 15px;
    color: #333;
}

.empty a {
    display: inline-block;
    margin-top: 30px;
    padding: 15px 40px;
    background: #000;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    border: 2px solid #000;
    transition: 0.2s;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 14px;
}

.empty a:hover {
    background: #fff;
    color: #000;
}

@media (max-width: 768px) {
    body {
        padding-top: 80px;
    }
    
    .top-nav .logo {
        font-size: 20px;
    }
    
    .back-arrow svg {
        width: 20px;
        height: 20px;
    }
    
    .container {
        padding: 20px 15px;
    }
    
    table {
        font-size: 12px;
    }
    
    th, td {
        padding: 10px 5px;
    }
    
    img {
        width: 60px;
        height: 60px;
    }
    
    h1 {
        font-size: 28px;
        margin-bottom: 30px;
    }
    
    .qty-input {
        width: 60px;
        padding: 8px;
        margin: 0 5px;
    }
    
    .qty-btn {
        width: 35px;
        height: 35px;
        font-size: 18px;
    }
    
    .checkout-btn {
        width: 90%;
        padding: 15px;
        font-size: 14px;
    }
    
    .empty-icon {
        font-size: 70px;
    }
    
    .empty p {
        font-size: 16px;
    }
    
    .remove-btn {
        padding: 8px 15px;
        font-size: 12px;
    }
}
</style>
</head>
<body>

<!-- Top Navigation -->
<div class="top-nav">
    <button class="back-arrow" onclick="goBack()" title="Go Back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
    </button>
    <div class="logo">HAPPY SPRAYS</div>
    <div class="nav-icons">
        <a href="index.php" class="nav-icon" title="Shop">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
            </svg>
        </a>
        <a href="cart.php" class="nav-icon" title="Cart">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <?php 
            $cart_count = 0;
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cart_count += $item['quantity'];
                }
            }
            if ($cart_count > 0): 
            ?>
            <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>
        <?php if(isset($_SESSION['customer_id'])): ?>
        <a href="customer_dashboard.php" class="nav-icon" title="My Account">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
        <?php else: ?>
        <a href="customer_login.php" class="nav-icon" title="Login">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <h1>My Cart</h1>
    
    <?php if (!empty($checkoutError)): ?>
    <div style="background: #fee; border: 2px solid #f00; color: #c00; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
        <strong>⚠️ <?= htmlspecialchars($checkoutError) ?></strong>
    </div>
    <?php endif; ?>

<script>
// Smart navigation: Track navigation history excluding checkout.php
(function() {
    // Get the referrer when page loads
    var referrer = document.referrer;
    
    // Store the last valid page (not checkout.php) in sessionStorage
    if (referrer && referrer.includes(window.location.hostname) && !referrer.includes('checkout.php')) {
        sessionStorage.setItem('lastValidPage', referrer);
    }
})();

function goBack() {
    var referrer = document.referrer;
    
    // Check if user came from checkout.php
    if (referrer && referrer.includes('checkout.php')) {
        // Try to get the last valid page from sessionStorage
        var lastValidPage = sessionStorage.getItem('lastValidPage');
        
        if (lastValidPage && lastValidPage.includes(window.location.hostname)) {
            // Go to the last valid page (before checkout)
            window.location.href = lastValidPage;
        } else {
            // Fallback to index if no valid history
            window.location.href = 'index.php';
        }
    } else if (referrer && referrer.includes(window.location.hostname)) {
        // User came from another page (not checkout), go back normally
        window.history.back();
    } else {
        // No referrer or external referrer, go to index
        window.location.href = 'index.php';
    }
}

function updateQty(itemId, change) {
    const input = document.getElementById('qty' + itemId);
    const currentQty = parseInt(input.value);
    const newQty = currentQty + change;
    
    if (newQty >= 1) {
        input.value = newQty;
        document.getElementById('qtyForm' + itemId).submit();
    }
}

// Checkbox selection functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    // Calculate selected items
    function updateSelectedInfo() {
        let selectedCount = 0;
        let selectedTotal = 0;
        
        itemCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedCount++;
                selectedTotal += parseFloat(checkbox.dataset.total);
            }
        });
        
        // Update table subtotal
        const tableSubtotalEl = document.getElementById('tableSubtotal');
        if (tableSubtotalEl) {
            tableSubtotalEl.textContent = '₱' + selectedTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Enable/disable checkout button
        if (checkoutBtn) {
            if (selectedCount === 0) {
                checkoutBtn.classList.add('disabled');
                checkoutBtn.textContent = 'Select Items to Checkout';
            } else {
                checkoutBtn.classList.remove('disabled');
                checkoutBtn.textContent = 'Proceed to Checkout (' + selectedCount + ')';
            }
        }
        
        // Update select all checkbox state
        const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
        const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }
    }
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedInfo();
        });
    }
    
    // Individual checkbox change
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedInfo);
    });
    
    // Initial update
    updateSelectedInfo();
});

// Proceed to checkout with selected items
function proceedToCheckout() {
    const selectedItems = [];
    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        selectedItems.push(checkbox.dataset.id);
    });
    
    if (selectedItems.length === 0) {
        alert('Please select at least one item to checkout.');
        return;
    }
    
    // Send selected items to server
    const formData = new FormData();
    formData.append('set_selected_items', '1');
    formData.append('selected_items', JSON.stringify(selectedItems));
    
    fetch('checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to checkout
            window.location.href = 'checkout.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Redirect anyway as fallback
        window.location.href = 'checkout.php';
    });
}
</script>

<?php if (!empty($cart)): ?>
<table>
    <tr>
        <th>
            <div class="select-all-container">
                <input type="checkbox" id="selectAll" class="cart-checkbox" checked>
                <label for="selectAll">Select All</label>
            </div>
        </th>
        <th>Image</th>
        <th>Perfume</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Total</th>
        <th>Action</th>
    </tr>
    <?php 
    $grand_total = 0;
    foreach ($cart as $id => $item): 
        $total = $item['price'] * $item['quantity'];
        $grand_total += $total;

        // Fetch actual product image from database
        $productData = $db->fetch("
            SELECT p.perfume_name, i.file_path 
            FROM perfumes p
            LEFT JOIN images i ON p.perfume_id = i.perfume_id
            WHERE p.perfume_id = ?
            LIMIT 1
        ", [$id]);
        
        // Determine image path - use database image or fallback
        $imgPath = 'images/DEFAULT.png';
        if ($productData && !empty($productData['file_path'])) {
            $imgPath = $productData['file_path'];
        } elseif (!empty($item['image'])) {
            $imgPath = $item['image'];
        }
    ?>
    <tr>
        <td style="text-align: center;">
            <input 
                type="checkbox" 
                class="cart-checkbox item-checkbox" 
                data-id="<?= htmlspecialchars($id) ?>"
                data-price="<?= $item['price'] ?>"
                data-quantity="<?= $item['quantity'] ?>"
                data-total="<?= $total ?>"
                checked
            >
        </td>
        <td>
            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='images/DEFAULT.png'">
        </td>
        <td class="product-name"><?= htmlspecialchars($item['name']) ?></td>
        <td class="price-cell">₱<?= number_format($item['price'], 2) ?></td>
        <td>
            <form method="post" class="qty-form" id="qtyForm<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="update_qty" value="1">
                <button type="button" class="qty-btn minus" onclick="updateQty('<?= htmlspecialchars($id) ?>', -1)">-</button>
                <input type="number" name="quantity" id="qty<?= htmlspecialchars($id) ?>" value="<?= (int)$item['quantity'] ?>" min="1" step="1" class="qty-input" readonly>
                <button type="button" class="qty-btn plus" onclick="updateQty('<?= htmlspecialchars($id) ?>', 1)">+</button>
            </form>
        </td>
        <td class="price-cell">₱<?= number_format($total, 2) ?></td>
        <td><a href="cart.php?remove=<?= urlencode($id) ?>" class="remove-btn" onclick="return confirm('Remove this item from cart?')">Remove</a></td>
    </tr>
    <?php endforeach; ?>
    <tr class="subtotal-row">
        <td colspan="5" style="text-align: right; font-weight: 700; padding-right: 30px; font-size: 16px;">SUBTOTAL</td>
        <td colspan="2" class="subtotal-amount" id="tableSubtotal">₱<?= number_format($grand_total, 2) ?></td>
    </tr>
</table>

<?php if(isset($_SESSION['customer_id'])): ?>
    <button id="checkoutBtn" class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
<?php else: ?>
    <a href="customer_login.php?redirect_to=checkout.php" class="checkout-btn">Login to Checkout</a>
<?php endif; ?>

<?php else: ?>
<div class="empty">
    <div class="empty-icon"></div>
    <p>Your cart is empty.</p>
    <a href="index.php">Continue Shopping</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
