<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: products_list.php");
    exit;
}

$product_id = intval($_GET['id']);
$product = $db->getProductById($product_id);

if (!$product) {
    header("Location: products_list.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $data = [
        'perfume_id' => $product_id,
        'perfume_name' => trim($_POST['perfume_name']),
        'perfume_brand' => trim($_POST['perfume_brand']),
        'perfume_price' => floatval($_POST['perfume_price']),
        'perfume_ml' => trim($_POST['perfume_ml']),
        'sex' => $_POST['sex'],
        'perfume_desc' => trim($_POST['perfume_desc']),
        'stock' => intval($_POST['stock']),
        'scent_family' => trim($_POST['scent_family'])
    ];
    
    // Validation
    if (empty($data['perfume_name']) || empty($data['perfume_brand']) || empty($data['perfume_price'])) {
        $error = "Please fill in all required fields.";
    } elseif ($data['perfume_price'] <= 0) {
        $error = "Price must be greater than 0.";
    } elseif ($data['stock'] < 0) {
        $error = "Stock cannot be negative.";
    } else {
        // Handle image upload (optional)
        $imageName = $product['image']; // Keep existing image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = "Image size must be less than 5MB.";
            } else {
                // Delete old image
                if (!empty($product['image']) && file_exists("images/" . $product['image'])) {
                    unlink("images/" . $product['image']);
                }
                
                $imageName = time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = "images/" . $imageName;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $error = "Failed to upload image.";
                }
            }
        }
        
        if (empty($error)) {
            try {
                $updated = $db->update(
                    "UPDATE perfumes SET 
                        perfume_name = ?, 
                        perfume_brand = ?, 
                        perfume_price = ?, 
                        perfume_ml = ?, 
                        sex = ?, 
                        perfume_descr = ?, 
                        stock = ?, 
                        scent_family = ?,
                        image = ?
                    WHERE perfume_id = ?",
                    [
                        $data['perfume_name'],
                        $data['perfume_brand'],
                        $data['perfume_price'],
                        $data['perfume_ml'],
                        $data['sex'],
                        $data['perfume_desc'],
                        $data['stock'],
                        $data['scent_family'],
                        $imageName,
                        $product_id
                    ]
                );
                
                if ($updated >= 0) {
                    $success = "Product updated successfully!";
                    // Reload product data
                    $product = $db->getProductById($product_id);
                } else {
                    $error = "Failed to update product.";
                }
            } catch (Exception $e) {
                error_log("Update product error: " . $e->getMessage());
                $error = "Failed to update product. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product - Happy Sprays Admin</title>
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

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 25px;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: all 0.3s;
}

.back-link:hover {
    color: #000;
}

.form-container {
    background: #fff;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    max-width: 900px;
}

.page-title {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 30px;
    color: #000;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #000;
    font-size: 14px;
}

.form-group label .required {
    color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    background: #fafafa;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #000;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #888;
    font-size: 13px;
}

.current-image {
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 10px;
    border: 1px solid #e8e8e8;
}

.current-image p {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
    font-weight: 500;
}

.current-image img {
    width: 100%;
    max-width: 200px;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.image-preview {
    margin-top: 15px;
    padding: 15px;
    background: #f0f7ff;
    border-radius: 10px;
    border: 1px solid #d1e7ff;
}

.image-preview p {
    font-size: 13px;
    color: #0066cc;
    margin-bottom: 10px;
    font-weight: 500;
}

.image-preview img {
    width: 100%;
    max-width: 200px;
    border-radius: 8px;
    border: 2px solid #0066cc;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    margin-top: 10px;
}

.file-input-wrapper input[type="file"] {
    padding: 10px 16px;
    cursor: pointer;
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
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

.btn-secondary {
    background: #fff;
    color: #000;
    border: 2px solid #e0e0e0;
}

.btn-secondary:hover {
    background: #f5f5f5;
    border-color: #000;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 35px;
    padding-top: 25px;
    border-top: 1px solid #e8e8e8;
}

@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    
    .main-content {
        margin-left: 220px;
        padding: 25px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
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
    
    .form-container {
        padding: 25px;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
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
        <a href="orders.php" class="menu-item">Orders</a>
        <a href="products_list.php" class="menu-item active">Products</a>
        <a href="users.php" class="menu-item">Customers</a>
        <a href="admin_contact_messages.php" class="menu-item">Messages</a>
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
    <a href="products_list.php" class="back-link">← Back to Products</a>
    
    <div class="form-container">
        <h1 class="page-title">Edit Product</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="edit_product.php?id=<?= $product_id ?>" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="perfume_name">Product Name <span class="required">*</span></label>
                    <input type="text" 
                           id="perfume_name" 
                           name="perfume_name" 
                           placeholder="Enter product name"
                           value="<?= htmlspecialchars($product['perfume_name']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="perfume_brand">Brand <span class="required">*</span></label>
                    <input type="text" 
                           id="perfume_brand" 
                           name="perfume_brand" 
                           placeholder="Enter brand name"
                           value="<?= htmlspecialchars($product['perfume_brand']) ?>"
                           required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="perfume_price">Price (₱) <span class="required">*</span></label>
                    <input type="number" 
                           id="perfume_price" 
                           name="perfume_price" 
                           step="0.01" 
                           min="0"
                           placeholder="0.00"
                           value="<?= htmlspecialchars($product['perfume_price']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="perfume_ml">Volume (ml) <span class="required">*</span></label>
                    <input type="text" 
                           id="perfume_ml" 
                           name="perfume_ml" 
                           placeholder="e.g., 30, 50, 100"
                           value="<?= htmlspecialchars($product['perfume_ml']) ?>"
                           required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Category <span class="required">*</span></label>
                    <select id="sex" name="sex" required>
                        <option value="">Select Category</option>
                        <option value="Male" <?= $product['sex'] === 'Male' ? 'selected' : '' ?>>For Him</option>
                        <option value="Female" <?= $product['sex'] === 'Female' ? 'selected' : '' ?>>For Her</option>
                        <option value="Unisex" <?= $product['sex'] === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity <span class="required">*</span></label>
                    <input type="number" 
                           id="stock" 
                           name="stock" 
                           min="0"
                           placeholder="0"
                           value="<?= htmlspecialchars($product['stock']) ?>"
                           required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="scent_family">Scent Family</label>
                <input type="text" 
                       id="scent_family" 
                       name="scent_family" 
                       placeholder="e.g., Floral, Woody, Fresh, Citrus"
                       value="<?= htmlspecialchars($product['scent_family'] ?? '') ?>">
                <small>Separate multiple scents with commas</small>
            </div>
            
            <div class="form-group">
                <label for="perfume_desc">Description</label>
                <textarea id="perfume_desc" 
                          name="perfume_desc" 
                          placeholder="Enter a detailed product description..."><?= htmlspecialchars($product['perfume_descr'] ?? $product['perfume_desc'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <?php if (!empty($product['image'])): ?>
                    <div class="current-image">
                        <p>Current Image:</p>
                        <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="Current">
                    </div>
                <?php endif; ?>
                <div class="file-input-wrapper">
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*"
                           onchange="previewImage(event)">
                </div>
                <small>Leave empty to keep current image. JPG, PNG, GIF (Max 5MB)</small>
                <div id="imagePreview" class="image-preview" style="display:none;">
                    <p>New Image Preview:</p>
                    <img id="preview" src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_product" class="btn btn-primary">
                    Update Product
                </button>
                <a href="products_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>