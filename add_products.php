<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $data = [
        'perfume_name' => trim($_POST['perfume_name']),
        'perfume_brand' => trim($_POST['perfume_brand']),
        'perfume_price' => floatval($_POST['perfume_price']),
        'perfume_ml' => trim($_POST['perfume_ml']),
        'sex' => $_POST['sex'],
        'perfume_desc' => trim($_POST['perfume_desc']),
        'stock' => intval($_POST['stock']),
        'scent_family' => trim($_POST['scent_family']),
        'admin_id' => $_SESSION['admin_id'] ?? 3
    ];

    // Validation
    if (empty($data['perfume_name']) || empty($data['perfume_brand']) || empty($data['perfume_price'])) {
        $error = "Please fill in all required fields.";
    } elseif ($data['perfume_price'] <= 0) {
        $error = "Price must be greater than 0.";
    } elseif ($data['stock'] < 0) {
        $error = "Stock cannot be negative.";
    } else {
        // ---- Handle multiple image uploads ----
        $imageNames = []; // store uploaded image names

        function uploadImage($fileKey)
        {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $filename = $_FILES[$fileKey]['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Invalid file type for $fileKey. Only JPG, PNG, GIF allowed.");
                }
                if ($_FILES[$fileKey]['size'] > 5 * 1024 * 1024) {
                    throw new Exception("$fileKey must be less than 5MB.");
                }
                $newName = time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = "images/" . $newName;
                if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadPath)) {
                    throw new Exception("Failed to upload $fileKey.");
                }
                return $newName;
            }
            return null;
        }

        try {
            $mainImage = uploadImage('image');
            $extraImage = uploadImage('image2');
            if ($mainImage) $imageNames[] = $mainImage;
            if ($extraImage) $imageNames[] = $extraImage;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        // If everything validated
        if (empty($error)) {
            try {
                // Step 1: Insert perfume
                $productId = $db->addProduct($data, $_FILES);

                // Step 2: Insert image records if perfume inserted
                if ($productId && !empty($imageNames)) {
                    foreach ($imageNames as $img) {
                        $filePath = "images/" . $img;
                        $db->insert(
                            "INSERT INTO images (perfume_id, file_name, file_path) VALUES (?, ?, ?)",
                            [$productId, $img, $filePath]
                        );
                    }
                }

                if ($productId) {
                    $success = "Product added successfully!";
                    $_POST = []; // Clear form
                } else {
                    $error = "Failed to add product.";
                }

            } catch (Exception $e) {
                error_log("Add product error: " . $e->getMessage());
                $error = "Failed to add product. Please try again.";
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
<title>Add Product - Happy Sprays Admin</title>
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

.image-preview {
    margin-top: 15px;
    padding: 15px;
    background: #f0f7ff;
    border-radius: 10px;
    border: 1px solid #d1e7ff;
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
    margin-top: 5px;
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
        <h1 class="page-title">Add New Product</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="add_products.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="perfume_name">Product Name *</label>
                    <input type="text" 
                           id="perfume_name" 
                           name="perfume_name" 
                           value="<?= htmlspecialchars($_POST['perfume_name'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="perfume_brand">Brand *</label>
                    <input type="text" 
                           id="perfume_brand" 
                           name="perfume_brand" 
                           value="<?= htmlspecialchars($_POST['perfume_brand'] ?? '') ?>"
                           required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="perfume_price">Price (₱) *</label>
                    <input type="number" 
                           id="perfume_price" 
                           name="perfume_price" 
                           step="0.01" 
                           min="0"
                           value="<?= htmlspecialchars($_POST['perfume_price'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="perfume_ml">Volume (ml) *</label>
                    <input type="text" 
                           id="perfume_ml" 
                           name="perfume_ml" 
                           value="<?= htmlspecialchars($_POST['perfume_ml'] ?? '') ?>"
                           required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Category *</label>
                    <select id="sex" name="sex" required>
                        <option value="">Select Category</option>
                        <option value="Male" <?= ($_POST['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>For Him</option>
                        <option value="Female" <?= ($_POST['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>For Her</option>
                        <option value="Unisex" <?= ($_POST['sex'] ?? '') === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" 
                           id="stock" 
                           name="stock" 
                           min="0"
                           value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>"
                           required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="scent_family">Scent Family</label>
                <input type="text" 
                       id="scent_family" 
                       name="scent_family" 
                       placeholder="e.g., Floral, Woody, Fresh"
                       value="<?= htmlspecialchars($_POST['scent_family'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="perfume_desc">Description</label>
                <textarea id="perfume_desc" 
                          name="perfume_desc" 
                          placeholder="Enter product description..."><?= htmlspecialchars($_POST['perfume_desc'] ?? '') ?></textarea>
            </div>

            <!-- Image Uploads -->
            <div class="form-group">
                <label for="image">Main Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event, 'preview1', 'imagePreview1')">
                <div id="imagePreview1" class="image-preview" style="display:none;">
                    <img id="preview1" src="" alt="Preview 1">
                </div>
            </div>

            <div class="form-group">
                <label for="image2">Additional Image (optional)</label>
                <input type="file" id="image2" name="image2" accept="image/*" onchange="previewImage(event, 'preview2', 'imagePreview2')">
                <div id="imagePreview2" class="image-preview" style="display:none;">
                    <img id="preview2" src="" alt="Preview 2">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                <a href="products_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(event, imgId, containerId) {
    const preview = document.getElementById(imgId);
    const container = document.getElementById(containerId);
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>