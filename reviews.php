<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance()->getConnection();

// Fetch all reviews
$stmt = $db->query("
    SELECT r.*, 
           p.perfume_name, 
           p.perfume_brand, 
           c.customer_firstname, 
           c.customer_lastname, 
           c.profile_picture
    FROM reviews r
    LEFT JOIN perfumes p ON r.perfume_id = p.perfume_id
    LEFT JOIN customers c ON r.customer_id = c.customer_id
    ORDER BY r.created_at DESC
");
$allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalReviews = count($allReviews);
$totalPages = ceil($totalReviews / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get reviews for current page
$paginatedReviews = array_slice($allReviews, $offset, $itemsPerPage);

// Colors for avatar placeholders
$colors = ['#ffcdd2', '#ffe0b2', '#fff9c4', '#c8e6c9', '#e1bee7', '#f8bbd0', '#b3e5fc', '#ffccbc', '#d1c4e9', '#c5e1a5', '#ffe082', '#f0f4c3'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Happy Sprays - Customer Reviews</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Poppins',sans-serif; background:#f5f5f5; color:#333; line-height:1.6; }
header { text-align:center; padding:80px 20px 60px; background:#fff; }
header h1 { font-family:'Playfair Display',serif; font-size:48px; font-weight:700; margin-bottom:10px; color:#000; letter-spacing:0.5px; }
header p { color:#666; font-size:18px; font-weight:400; max-width:600px; margin:0 auto; }

.reviews-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:24px; max-width:1400px; margin:60px auto; padding:0 40px 80px; }
.review-card { border-radius:20px; padding:32px; transition:transform 0.3s ease,box-shadow 0.3s ease; position:relative; min-height:240px; display:flex; flex-direction:column; justify-content:space-between; background:#fff; border:2px solid #000; }
.review-card:hover { transform:translateY(-4px); box-shadow:0 12px 24px rgba(0,0,0,0.12); }
.review-header { display:flex; justify-content:space-between; align-items:start; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #f0f0f0; }
.product-info { flex:1; }
.product-info strong { font-size:16px; font-weight:600; color:#000; }
.product-info .brand { font-size:13px; color:#666; }
.star-rating { display:flex; gap:2px; }
.star { color:#ddd; font-size:18px; }
.star.filled { color:#FFD700; }
.review-text { font-size:15px; line-height:1.7; color:#333; font-style:italic; margin-bottom:24px; flex-grow:1; }

.reviewer-info { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
.avatar-container { display:flex; gap:12px; align-items:center; }
.avatar { width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:20px; }
.reviewer-details { display:flex; flex-direction:column; }
.reviewer-name { font-size:16px; font-weight:600; color:#000; }
.reviewer-location { font-size:13px; color:#666; }

.review-images { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
.review-images img { max-width:80px; max-height:80px; border-radius:8px; cursor:pointer; transition:0.3s; }
.review-images img:hover { transform:scale(1.1); }

/* Lightbox */
.lightbox { display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; }
.lightbox img { max-width:90%; max-height:90%; border-radius:12px; box-shadow:0 0 30px rgba(0,0,0,0.5); cursor:zoom-out; }

.back-btn { position:fixed; top:30px; left:30px; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#000; color:#fff; text-decoration:none; font-size:24px; transition:0.3s; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:100; }
.back-btn:hover { background:#333; transform:scale(1.1); box-shadow:0 6px 16px rgba(0,0,0,0.2); }

footer { background:#000; color:#fff; padding:50px 20px 30px; text-align:center; font-size:14px; margin-top:0; }
.footer-columns { display:flex; justify-content:center; gap:100px; margin-bottom:30px; }
.footer-columns h4 { font-size:16px; margin-bottom:12px; font-weight:bold; color:#fff; text-transform:uppercase; letter-spacing:1px; }
.footer-columns a { display:block; text-decoration:none; color:#ccc; margin:8px 0; transition:0.2s; }
.footer-columns a:hover { color:#fff; padding-left:5px; }
.social-icons { margin:20px 0; }
.social-icons a { margin:0 12px; color:#ccc; text-decoration:none; font-size:16px; font-weight:500; transition:0.2s; }
.social-icons a:hover { color:#fff; }

.pagination { display:flex; justify-content:center; align-items:center; gap:8px; margin:30px auto 60px; padding:20px 0; max-width:1200px; }
.page-btn { padding:10px 16px; border:1px solid #e0e0e0; background:#fff; color:#333; text-decoration:none; border-radius:8px; font-weight:500; font-size:14px; transition:all 0.3s; cursor:pointer; }
.page-btn:hover { background:#f5f5f5; border-color:#000; color:#000; transform:translateY(-2px); }
.page-btn.active { background:#000; color:#fff; border-color:#000; }
.page-ellipsis { padding:10px 8px; color:#999; }

@media(max-width:768px){ header h1{ font-size:36px; } .reviews-container{ grid-template-columns:1fr; padding:0 20px 60px; } .footer-columns{ flex-direction:column; gap:30px; } }
</style>
</head>
<body>

<a href="index.php" class="back-btn">←</a>

<header>
  <h1>What people are saying?</h1>
  <p>Don't just take our word for it—see what our customers have to say about their experience!</p>
</header>

<div class="reviews-container">
<?php if(empty($paginatedReviews)): ?>
    <div class="empty-reviews">
        <p style="text-align:center; color:#999; padding:60px 20px; font-size:18px;">
        No reviews yet. Be the first to share your experience!
        </p>
    </div>
<?php else: ?>
    <?php foreach($paginatedReviews as $index => $review): 
        $customerName = htmlspecialchars($review['customer_firstname'].' '.$review['customer_lastname']);
        $rating = $review['rating'] ?? 5;
        $comment = htmlspecialchars($review['comment']);
        $productName = htmlspecialchars($review['perfume_name'] ?? 'Product');
        $productBrand = htmlspecialchars($review['perfume_brand'] ?? '');
        $reviewDate = date('M d, Y', strtotime($review['created_at']));
        $colorIndex = $index % count($colors);
        $avatarColor = $colors[$colorIndex];
        $initial = strtoupper(substr($review['customer_firstname'],0,1));

        // Fetch review images
        $stmtImg = $db->prepare("SELECT file_path FROM images WHERE order_id = ? AND image_type = 'review'");
        $stmtImg->execute([$review['order_id']]);
        $reviewImages = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
    ?>
    <div class="review-card">
      <div class="review-header">
        <div class="product-info">
          <strong><?= $productName ?></strong>
          <?php if($productBrand): ?><span class="brand"> by <?= $productBrand ?></span><?php endif; ?>
        </div>
        <div class="star-rating">
          <?php for($i=1;$i<=5;$i++): ?>
            <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
          <?php endfor; ?>
        </div>
      </div>

      <p class="review-text">"<?= $comment ?>"</p>

      <div class="reviewer-info">
        <div class="avatar-container">
          <?php if(!empty($review['profile_picture']) && file_exists($review['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($review['profile_picture']) ?>" alt="<?= $customerName ?>" class="avatar">
          <?php else: ?>
            <div class="avatar" style="background-color:<?= $avatarColor ?>"><?= $initial ?></div>
          <?php endif; ?>
          <div class="reviewer-details">
            <div class="reviewer-name"><?= $customerName ?></div>
            <div class="reviewer-location"><?= $reviewDate ?></div>
          </div>
        </div>

        <?php if(!empty($reviewImages)): ?>
          <div class="review-images">
            <?php foreach($reviewImages as $img): ?>
              <img src="<?= htmlspecialchars($img) ?>" alt="Review Image">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php if($totalPages > 1): ?>
<div class="pagination">
    <?php if($currentPage>1): ?><a href="?page=<?= $currentPage-1 ?>" class="page-btn">Previous</a><?php endif; ?>
    <?php for($i=1;$i<=$totalPages;$i++): ?>
        <?php if($i==1||$i==$totalPages||abs($i-$currentPage)<=2): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= $i==$currentPage?'active':'' ?>"><?= $i ?></a>
        <?php elseif(abs($i-$currentPage)==3): ?>
            <span class="page-ellipsis">...</span>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if($currentPage<$totalPages): ?><a href="?page=<?= $currentPage+1 ?>" class="page-btn">Next</a><?php endif; ?>
</div>
<?php endif; ?>

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

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <img src="" alt="Review Image">
</div>

<script>
const lightbox = document.getElementById('lightbox');
const lightboxImg = lightbox.querySelector('img');

document.querySelectorAll('.review-images img').forEach(img => {
    img.addEventListener('click', () => {
        lightboxImg.src = img.src;
        lightbox.style.display = 'flex';
    });
});

lightbox.addEventListener('click', () => {
    lightbox.style.display = 'none';
});
</script>

</body>
</html>
