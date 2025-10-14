<?php
session_start();
require_once 'classes/database.php';
$db = Database::getInstance();

// Step 1: get perfume id from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Product not found.");

// Step 2: fetch product + all its images
$product = $db->fetch("
    SELECT p.*, GROUP_CONCAT(i.file_path) AS images
    FROM perfumes p
    LEFT JOIN images i ON p.perfume_id = i.perfume_id
    WHERE p.perfume_id = ?
    GROUP BY p.perfume_id
", [$id]);

if (!$product) die("Product not found.");

// Step 3: prepare images array
$images = [];
if (!empty($product['images'])) {
    $images = explode(",", $product['images']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['perfume_name']) ?> - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#fff; color:#000;}

/* Top Navbar */
.top-nav {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  background: #fff;
  border-bottom: 1px solid #eee;
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: 'Playfair Display', serif;
  font-size: 28px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 2px;
  z-index: 1000;
}

.top-nav .logo {
  flex: 1;
  text-align: center;
}

/* Right side icons container */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 25px;
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
}

/* Icon Buttons */
.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  outline: none;
}

.icon-btn:focus {
  outline: none;
  box-shadow: none;
}

/* Profile Link */
.profile-link svg {
  display: block;
  width: 18px;  
  height: 17px;
  stroke: black;
}

.profile-link:hover svg {
  stroke: #555;
}

/* Cart Icon */
.cart-link svg {
  display: block;
  width: 22px;
  height: 23px;
  stroke: black;
}

/* Sub Navbar */
.sub-nav {position:fixed; top:60px; left:0; width:100%; background:#fff; border-bottom:1px solid #ccc; text-align:center; padding:12px 0; transition:top 0.3s; z-index:999; font-family:'Playfair Display', serif; text-transform:uppercase; font-weight:700; letter-spacing:1px;}
.sub-nav a {margin:0 20px; text-decoration:none; color:#000; font-size:18px; transition:color 0.3s;}
.sub-nav a:hover {color:#555;}

/* Back to Shop Button */
.back-btn-bar { position: fixed; top: 140px; left: 30px; z-index: 998; transition: top 0.4s ease;}
.back-btn-bar a { padding: 12px 24px; font-size: 16px; font-weight: bold; background: #fff; color: #000; border: 2px solid #000; text-decoration: none; border-radius: 6px; transition: 0.3s; display: inline-block;}
.back-btn-bar a:hover {background:#000;color:#fff;}

/* === Image zoom modal (small, non-intrusive) === */
.product-slider img { cursor: zoom-in; }

/* popup wrapper */
.image-popup {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.86);
  z-index: 2000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.22s ease;
}
.image-popup.active { opacity: 1; visibility: visible; }

/* popup image */
.image-popup .popup-img {
  max-width: 92%;
  max-height: 88%;
  border-radius: 10px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6);
  transform: scale(0.96);
  transition: transform 0.2s ease;
}
.image-popup.active .popup-img { transform: scale(1); }

/* close button */
.image-popup .popup-close {
  position: absolute;
  top: 22px;
  right: 28px;
  color: #fff;
  font-size: 32px;
  line-height: 1;
  cursor: pointer;
  user-select: none;
  font-weight: 700;
}
.image-popup .popup-close:hover { color: #ddd; }


/* Product Container */
.container { display: flex; justify-content: center; align-items: flex-start; gap: 80px; padding: 180px 40px 100px; max-width: 1200px; margin: auto;}

/* Image Gallery */
.product-image { flex: 1; max-width: 450px; position: relative; overflow: hidden; border: 2px solid #ddd; padding: 20px; border-radius: 12px; background: #fafafa; }
.product-slider { display: flex; width: 100%; transition: transform 0.4s ease; }
.product-slider img { min-width: 100%; height: auto; object-fit: contain; }

.slider-btn { position:absolute; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.5); color:#fff; border:none; padding:10px; cursor:pointer; font-size:18px; border-radius:50%; }
.slider-btn.left { left:10px; }
.slider-btn.right { right:10px; }

/* Product details */
.product-details { flex: 1; max-width: 500px;}
.product-details h1 { font-family: 'Playfair Display', serif; font-size: 38px; margin-bottom: 15px; letter-spacing: 1px; border-bottom: 2px solid #000; padding-bottom: 10px;}
.product-details p { font-size: 16px; margin: 8px 0; line-height: 1.5;}
.price { font-size: 28px; font-weight: bold; margin: 20px 0;}
.ml { font-size: 18px; font-weight: 500; margin: 5px 0 15px; color: #444; }

/* Stock Status */
.stock-status {
    font-size: 16px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    display: inline-block;
    margin: 10px 0 15px;
    letter-spacing: 0.5px;
}

.stock-status.in-stock {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.stock-status.out-of-stock {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Tabs */
.tabs { margin: 20px 0; display: flex; gap: 15px; border-bottom: 2px solid #000;}
.tab-btn { padding: 10px 18px; font-size: 15px; font-weight: bold; border: none; background: none; cursor: pointer; transition: 0.3s; border-bottom: 2px solid transparent;}
.tab-btn.active { border-bottom: 2px solid #000;}
.tab-btn:hover { color: #555;}
.tab-content { margin-top: 20px; font-size: 15px; line-height: 1.6;}

/* Product Reviews Styles */
.product-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 10px;
}

.product-review-card {
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    transition: 0.2s;
}

.product-review-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.review-header-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e0e0e0;
}

.review-avatar {
    flex-shrink: 0;
}

.avatar-img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(0, 0, 0, 0.1);
}

.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    border: 2px solid rgba(0, 0, 0, 0.1);
}

.review-info {
    flex: 1;
}

.review-customer-name {
    font-size: 16px;
    font-weight: 600;
    color: #000;
    margin-bottom: 2px;
}

.review-date {
    font-size: 13px;
    color: #666;
}

.review-rating {
    display: flex;
    gap: 2px;
}

.review-rating .star {
    color: #ddd;
    font-size: 18px;
}

.review-rating .star.filled {
    color: #FFD700;
}

.review-comment {
    font-size: 15px;
    line-height: 1.6;
    color: #333;
    font-style: italic;
}

/* Add to Cart button */
.add-to-cart-btn { padding: 14px 28px; font-size: 16px; font-weight: bold; background: #fff; color: #000; border: 2px solid #000; cursor: pointer; margin-top: 25px; transition: 0.3s; letter-spacing: 1px;}
.add-to-cart-btn:hover { background: #000; color: #fff; }
.add-to-cart-btn:disabled {
    background: #999;
    color: #fff;
    border-color: #999;
    cursor: not-allowed;
    opacity: 0.6;
}
.add-to-cart-btn:disabled:hover {
    background: #999;
    color: #fff;
}

.qty-box { display: flex; align-items: center; margin-top: 20px; }
.qty-box button { width: 32px; height: 32px; font-size: 18px; border: 1px solid #000; background: #fff; cursor: pointer; border-radius: 4px;}
.qty-box button:disabled {
    background: #f0f0f0;
    color: #999;
    border-color: #ccc;
    cursor: not-allowed;
}
.qty-box input { width: 60px; text-align: center; margin: 0 8px; padding: 6px; border: 1px solid #000; border-radius: 4px;}
.qty-box input:disabled {
    background: #f0f0f0;
    color: #999;
    border-color: #ccc;
}

/* Divider + Recommendations */
.divider { max-width: 1200px; margin: 60px auto 40px; border: none; border-top: 1px solid #ccc; }
.recommendations { max-width: 1200px; margin: auto; padding: 20px 40px 100px; text-align: center; }
.recommendations h2 { font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 1px; }
.recommend-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; justify-items: center; }
.recommend-item { border: 1px solid #eee; padding: 20px; border-radius: 12px; transition: 0.3s; background: #fafafa; width: 100%; max-width: 250px; cursor:pointer; }
.recommend-item:hover { box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
.recommend-item img { width: 100%; height: 200px; object-fit: contain; margin-bottom: 15px; }
.recommend-item h3 { font-size: 18px; margin-bottom: 8px; }
.recommend-item p { font-size: 14px; margin-bottom: 12px; color: #444; }

/* Footer */
footer { background: #e9e9e9; border-top: 1px solid #eee; padding: 60px 20px; text-align: center; font-size: 14px; color: #555; margin-top: 60px; }
.footer-columns { display: flex; justify-content: center; gap: 120px; margin-bottom: 30px; }
.footer-columns h4 { font-size: 16px; margin-bottom: 12px; font-weight: bold; color: #000; }
.footer-columns a { display: block; text-decoration: none; color: #555; margin: 6px 0; }
.footer-columns a:hover { color: #000; }
.social-icons { margin-top: 25px; }
.social-icons a { margin: 0 10px; color: #555; text-decoration: none; font-size: 18px; }
.social-icons a:hover { color: #000; }

/* Custom SweetAlert Styling */
.custom-swal-popup {
  font-family: 'Poppins', 'Segoe UI', sans-serif !important;
  border: 2px solid #000 !important;
  border-radius: 16px !important;
  padding: 30px !important;
}

.custom-swal-title {
  font-family: 'Playfair Display', serif !important;
  color: #000 !important;
  font-size: 28px !important;
  font-weight: 700 !important;
  margin-bottom: 10px !important;
}

.custom-swal-text {
  color: #333 !important;
  font-size: 16px !important;
  line-height: 1.6 !important;
}

.custom-swal-button {
  background: #000 !important;
  color: #fff !important;
  border: 2px solid #000 !important;
  border-radius: 8px !important;
  padding: 12px 30px !important;
  font-weight: 600 !important;
  font-size: 15px !important;
  transition: all 0.3s !important;
}

.custom-swal-button:hover {
  background: #fff !important;
  color: #000 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
</style>
</head>
<body>

<div class="top-nav">
  <div class="logo">Happy Sprays</div>
  <div class="nav-actions">
    <!-- Cart Icon with Bubble -->
    <a href="cart.php" class="cart-link" style="position:relative">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 7h12l1 12H5L6 7z"/>
        <path d="M9 7V5a3 3 0 0 1 6 0v2"/>
      </svg>
      <span style="position:absolute; top:-8px; right:-8px; background:red; color:#fff; font-size:12px; font-weight:bold; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
      <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
      </span>
    </a>

    <!-- Profile Icon -->
    <?php
      $profile_link = isset($_SESSION['customer_id']) ? "customer_dashboard.php" : "customer_login.php";
      $profile_title = isset($_SESSION['customer_id']) ? "My Account" : "Login";
    ?>
    <a href="<?= $profile_link ?>" class="profile-link" title="<?= $profile_title ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
      </svg>
    </a>
  </div>
</div>

<div class="sub-nav" id="subNav">
    <a href="index.php">HOME</a>
    <a href="index.php?gender=Male">For Him</a>
    <a href="index.php?gender=Female">For Her</a>
    <a href="reviews.php">REVIEWS</a>
</div>

<div class="back-btn-bar" id="backBtnBar">
    <a href="index.php">←</a>
</div>

<div class="container">
    <div class="product-image">
        <div class="product-slider" id="slider">
            <?php foreach($images as $img): ?>
                <img src="<?= trim($img) ?>" alt="<?= htmlspecialchars($product['perfume_name']) ?>">
            <?php endforeach; ?>
        </div>
        <?php if(count($images) > 1): ?>
            <button class="slider-btn left" onclick="prevSlide()">‹</button>
            <button class="slider-btn right" onclick="nextSlide()">›</button>
        <?php endif; ?>
    </div>

    <!-- Image zoom modal (paste before </body>) -->
<div id="imagePopup" class="image-popup" aria-hidden="true" role="dialog">
  <span class="popup-close" id="popupClose" aria-label="Close">&times;</span>
  <img id="popupImg" class="popup-img" src="" alt="Zoomed image">
</div>


    <div class="product-details">
        <h1><?= htmlspecialchars($product['perfume_name']) ?></h1>
        <p class="price">₱<?= $product['perfume_price'] ?></p>
        <?php if (!empty($product['perfume_ml'])): ?>
            <p class="ml">Size: <?= htmlspecialchars($product['perfume_ml']) ?> ml</p>
        <?php endif; ?>
        
        <?php 
        $isOutOfStock = isset($product['stock']) && $product['stock'] <= 0;
        if ($isOutOfStock): 
        ?>
            <p class="stock-status out-of-stock">⚠️ Out of Stock</p>
        <?php else: ?>
            <p class="stock-status in-stock">✓ In Stock (<?= $product['stock'] ?> available)</p>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" data-tab="desc">Description</button>
            <button class="tab-btn" data-tab="scent">Inspired Scent</button>
            <button class="tab-btn" data-tab="reviews">Reviews</button>
            <button class="tab-btn" data-tab="shipping">Shipping</button>
        </div>

        <div class="tab-content" id="desc">
            <p><?= nl2br(htmlspecialchars($product['perfume_desc'])) ?></p>
        </div>
        <div class="tab-content" id="scent" style="display:none;">
            <p><strong><?= htmlspecialchars($product['perfume_brand']) ?></strong></p>
        </div>
        <div class="tab-content" id="reviews" style="display:none;">
            <?php
            // Fetch reviews for this product
            $productReviews = $db->getProductReviews($product['perfume_id']);
            
            if (empty($productReviews)) {
                echo '<p style="color: #666; font-style: italic;">No reviews yet. Be the first to review this perfume!</p>';
            } else {
                echo '<div class="product-reviews-list">';
                foreach ($productReviews as $review) {
                    $customerName = htmlspecialchars($review['customer_firstname'] . ' ' . $review['customer_lastname']);
                    $reviewDate = date('F j, Y', strtotime($review['created_at']));
                    $rating = intval($review['rating']);
                    
                    // Generate profile picture path or colored avatar
                    $profilePicPath = 'uploads/profiles/' . $review['customer_id'] . '.jpg';
                    $hasProfilePic = file_exists($profilePicPath);
                    $initial = strtoupper(substr($review['customer_firstname'], 0, 1));
                    $colors = ['#ffcdd2', '#f8bbd0', '#e1bee7', '#d1c4e9', '#c5cae9', '#bbdefb', '#b3e5fc', '#b2ebf2', '#b2dfdb', '#c8e6c9', '#dcedc8', '#ffe0b2'];
                    $colorIndex = $review['customer_id'] % count($colors);
                    $bgColor = $colors[$colorIndex];
                    
                    echo '<div class="product-review-card">';
                    echo '<div class="review-header-section">';
                    echo '<div class="review-avatar">';
                    if ($hasProfilePic) {
                        echo '<img src="' . htmlspecialchars($profilePicPath) . '?v=' . time() . '" alt="' . $customerName . '" class="avatar-img">';
                    } else {
                        echo '<div class="avatar-circle" style="background-color: ' . $bgColor . ';">' . $initial . '</div>';
                    }
                    echo '</div>';
                    echo '<div class="review-info">';
                    echo '<div class="review-customer-name">' . $customerName . '</div>';
                    echo '<div class="review-date">' . $reviewDate . '</div>';
                    echo '</div>';
                    echo '<div class="review-rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<span class="star' . ($i <= $rating ? ' filled' : '') . '">★</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="review-comment">' . nl2br(htmlspecialchars($review['comment'])) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            ?>
        </div>
        <div class="tab-content" id="shipping" style="display:none;">
            <p>Standard shipping: 3-5 business days.<br>Express shipping: 1-2 business days.</p>
        </div>

<form class="add-to-cart-form" id="addToCartForm" method="post">
    <input type="hidden" name="id" value="<?= $product['perfume_id'] ?>">
    <input type="hidden" name="name" value="<?= htmlspecialchars($product['perfume_name']) ?>">
    <input type="hidden" name="price" value="<?= $product['perfume_price'] ?>">
    <input type="hidden" name="image" value="<?= !empty($images[0]) ? htmlspecialchars(trim($images[0])) : 'images/default.jpg' ?>">

    <div class="qty-box">
        <button type="button" onclick="changeQty(-1)" <?= $isOutOfStock ? 'disabled' : '' ?>>-</button>
        <input type="number" id="qtyInput" name="qty" value="1" min="1" <?= $isOutOfStock ? 'disabled' : '' ?>>
        <button type="button" onclick="changeQty(1)" <?= $isOutOfStock ? 'disabled' : '' ?>>+</button>
    </div>

    <button type="submit" name="add_to_cart" value="1" class="add-to-cart-btn" <?= $isOutOfStock ? 'disabled' : '' ?>>
        <?= $isOutOfStock ? 'OUT OF STOCK' : 'ADD TO CART' ?>
    </button>
</form>

    </div>
</div>

<hr class="divider">

<div class="recommendations">
    <h2>Also you may like</h2>
    <div class="recommend-grid">
        <?php
        $related = $db->select("
            SELECT p.*, 
                   (SELECT i.file_path FROM images i WHERE i.perfume_id = p.perfume_id LIMIT 1) AS image
            FROM perfumes p
            WHERE p.perfume_id != ?
            ORDER BY RAND() LIMIT 4
        ", [$id]);

        if ($related) {
            foreach($related as $rel) {
                $imgPath = !empty($rel['image']) ? trim($rel['image']) : 'images/default.jpg';
                echo "<div class='recommend-item' onclick=\"window.location.href='view_product.php?id={$rel['perfume_id']}'\">
                        <img src='{$imgPath}' alt='".htmlspecialchars($rel['perfume_name'])."'>
                        <h3>".htmlspecialchars($rel['perfume_name'])."</h3>
                        <p>₱{$rel['perfume_price']}</p>
                      </div>";
            }
        } else {
            echo "<p>No related products found.</p>";
        }
        ?>
    </div>
</div>

<footer>
    <div class="footer-columns">
        <div>
            <h4>Company</h4>
            <a href="about.php">About</a>
            <a href="reviews.php">Reviews</a>
        </div>
        <div>
            <h4>Customer Service</h4>
            <a href="faq.php">FAQ</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/thethriftbytf">Facebook</a>
        <a href="https://www.instagram.com/thehappysprays/">Instagram</a>
    </div>
    <p>© 2025 Happy Sprays. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Register this page as a valid navigation point for cart back button
sessionStorage.setItem('lastValidPage', window.location.href);

// Update cart count
function updateCartCount() {
    fetch('cart_count.php')
        .then(r => r.text())
        .then(count => {
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        })
        .catch(err => console.error('Cart count error:', err));
}

// Add to cart form submission
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('add_to_cart', '1');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => {
        if(!r.ok) throw new Error('Server error: ' + r.status);
        return r.text();
    })
    .then(response => {
        // Clean and normalize the response
        response = response.trim().toLowerCase();
        
        console.log('Cart response received:', response); // Debug logging
        
        // Update cart count on success
        if(response === 'added' || response === 'already_exists') {
            updateCartCount();
            
            // Always show success message (green checkmark)
            Swal.fire({
                title: 'Added to Cart!',
                text: 'Product has been successfully added to your cart.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#000',
                background: '#fff',
                customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    htmlContainer: 'custom-swal-text',
                    confirmButton: 'custom-swal-button'
                }
            });
        } else {
            // Unexpected response - log for debugging
            console.error('Unexpected cart response:', response);
            console.error('Response length:', response.length);
            console.error('Response bytes:', Array.from(response).map(c => c.charCodeAt(0)));
            
            Swal.fire({
                title: 'Error',
                text: 'Unexpected response from server. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#000',
                background: '#fff',
                customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    confirmButton: 'custom-swal-button'
                }
            });
        }
    })
    .catch(err => {
        console.error('Error:', err);
        Swal.fire({
            title: 'Network Error',
            text: 'Could not connect to server.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#000'
        });
    });
});

// Initialize cart count on load
document.addEventListener('DOMContentLoaded', updateCartCount);

let lastScrollTop = 0;
const subNav = document.getElementById("subNav");
const backBtnBar = document.getElementById("backBtnBar");

window.addEventListener("scroll", function(){
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop > lastScrollTop) {
        subNav.style.top = "-60px";
        backBtnBar.style.top = "-100px";
    } else {
        subNav.style.top = "60px";
        backBtnBar.style.top = "140px";
    }
    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
}, false);

// Tabs
const tabBtns = document.querySelectorAll(".tab-btn");
const tabContents = document.querySelectorAll(".tab-content");
tabBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        tabBtns.forEach(b => b.classList.remove("active"));
        tabContents.forEach(c => c.style.display = "none");
        btn.classList.add("active");
        document.getElementById(btn.dataset.tab).style.display = "block";
    });
});

// Slider
let currentIndex = 0;
const slider = document.getElementById("slider");
const slides = slider.children;

function showSlide(index){
    if(index < 0) index = slides.length - 1;
    if(index >= slides.length) index = 0;
    currentIndex = index;
    slider.style.transform = `translateX(-${index * 100}%)`;
}
function nextSlide(){ showSlide(currentIndex + 1); }
function prevSlide(){ showSlide(currentIndex - 1); }

let startX = 0;
slider.addEventListener("touchstart", e => startX = e.touches[0].clientX);
slider.addEventListener("touchend", e => {
    let endX = e.changedTouches[0].clientX;
    if(endX - startX > 50) prevSlide();
    if(startX - endX > 50) nextSlide();
});

function changeQty(val){
    let input = document.getElementById("qtyInput");
    let newVal = parseInt(input.value) + val;
    if(newVal < 1) newVal = 1;
    input.value = newVal;
}

// === Simple image zoom modal ===
(function(){
  const popup = document.getElementById('imagePopup');
  const popupImg = document.getElementById('popupImg');
  const popupClose = document.getElementById('popupClose');

  if (!popup || !popupImg) return;

  // open modal when any product image clicked
  document.querySelectorAll('.product-slider img').forEach(img => {
    img.addEventListener('click', () => {
      popupImg.src = img.src;
      popup.classList.add('active');
      popup.setAttribute('aria-hidden', 'false');
      // prevent body scroll while modal open
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
    });
  });

  // close handlers
  function closePopup() {
    popup.classList.remove('active');
    popup.setAttribute('aria-hidden', 'true');
    popupImg.src = '';
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  popupClose.addEventListener('click', closePopup);
  popup.addEventListener('click', (e) => {
    if (e.target === popup) closePopup(); // click outside image
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePopup();
  });
})();

</script>
</body>
</html>
