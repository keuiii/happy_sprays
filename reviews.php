<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Get all reviews from database
$allReviews = $db->getAllReviews();

// Add color palette for avatars
$colors = ['#ffcdd2', '#ffe0b2', '#fff9c4', '#c8e6c9', '#e1bee7', '#f8bbd0', '#b3e5fc', '#ffccbc', '#d1c4e9', '#c5e1a5', '#ffe082', '#f0f4c3'];

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalReviews = count($allReviews);
$totalPages = ceil($totalReviews / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get reviews for current page
$paginatedReviews = array_slice($allReviews, $offset, $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Happy Sprays - Customer Reviews</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background: #f5f5f5;
  color: #333;
  margin: 0;
  padding: 0;
  line-height: 1.6;
}

header {
  text-align: center;
  padding: 80px 20px 60px;
  background: #fff;
}
header h1 {
  font-family: 'Playfair Display', serif;
  font-size: 48px;
  font-weight: 700;
  margin: 0 0 10px 0;
  color: #000;
  letter-spacing: 0.5px;
}
header p {
  color: #666;
  font-size: 18px;
  font-weight: 400;
  max-width: 600px;
  margin: 0 auto;
}

.reviews-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
  max-width: 1400px;
  margin: 60px auto;
  padding: 0 40px 80px;
}

.review-card {
  border-radius: 20px;
  padding: 32px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  min-height: 240px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: #fff !important;
  border: 2px solid #000;
}

.review-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.product-info {
  flex: 1;
}

.product-info h4 {
  font-size: 16px;
  font-weight: 600;
  color: #000;
  margin: 0 0 4px 0;
}

.product-info p {
  font-size: 13px;
  color: #666;
  margin: 0;
}

.star-rating {
  display: flex;
  gap: 2px;
}

.star {
  color: #ddd;
  font-size: 18px;
}

.star.filled {
  color: #FFD700;
}

.review-text {
  font-size: 15px;
  line-height: 1.7;
  color: #333;
  font-style: italic;
  margin-bottom: 24px;
  flex-grow: 1;
}

.reviewer-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(0, 0, 0, 0.1);
}

.reviewer-details {
  display: flex;
  flex-direction: column;
}

.reviewer-name {
  font-size: 16px;
  font-weight: 600;
  color: #000;
  margin-bottom: 2px;
}

.reviewer-location {
  font-size: 13px;
  color: #666;
  font-weight: 400;
}

/* Back Button */
.back-btn {
  position: fixed;
  top: 30px;
  left: 30px;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #000;
  color: #fff;
  text-decoration: none;
  font-size: 24px;
  transition: 0.3s;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 100;
}
.back-btn:hover {
  background: #333;
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* --- FOOTER --- */
footer {
    background: #000;
    color: #fff;
    padding: 50px 20px 30px;
    text-align: center;
    font-size: 14px;
    margin-top: 0;
}
.footer-columns {
    display: flex;
    justify-content: center;
    gap: 100px;
    margin-bottom: 30px;
}
.footer-columns h4 {
    font-size: 16px;
    margin-bottom: 12px;
    font-weight: bold;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.footer-columns a {
    display: block;
    text-decoration: none;
    color: #ccc;
    margin: 8px 0;
    transition: 0.2s;
}
.footer-columns a:hover { 
    color: #fff;
    padding-left: 5px;
}
.social-icons { 
    margin-top: 20px;
    margin-bottom: 20px;
}
.social-icons a {
    margin: 0 12px;
    color: #ccc;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: 0.2s;
}
.social-icons a:hover { 
    color: #fff;
}
footer p {
    color: #999;
    margin-top: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    header h1 {
        font-size: 36px;
    }
    .reviews-container {
        grid-template-columns: 1fr;
        padding: 0 20px 60px;
    }
    .footer-columns {
        flex-direction: column;
        gap: 30px;
    }
}

/* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin: 30px auto 60px;
    padding: 20px 0;
    max-width: 1200px;
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
    transform: translateY(-2px);
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
  <a href="index.php" class="back-btn">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="19" y1="12" x2="5" y2="12"></line>
      <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
  </a>

  <header>
    <h1>What people are saying?</h1>
    <p>Don't just take our word for it—see what our customers have to say about their experience!</p>
  </header>

  <div class="reviews-container">
    <?php if (empty($paginatedReviews)): ?>
      <div class="empty-reviews">
        <p style="text-align: center; color: #999; padding: 60px 20px; font-size: 18px;">
          No reviews yet. Be the first to share your experience!
        </p>
      </div>
    <?php else: ?>
      <?php foreach ($paginatedReviews as $index => $review): 
        $customerName = htmlspecialchars($review['customer_firstname'] . ' ' . $review['customer_lastname']);
        $rating = $review['rating'] ?? 5;
        $comment = htmlspecialchars($review['comment']);
        $productName = htmlspecialchars($review['perfume_name'] ?? 'Product');
        $productBrand = htmlspecialchars($review['perfume_brand'] ?? '');
        $reviewDate = date('M d, Y', strtotime($review['created_at']));
        $colorIndex = $index % count($colors);
        $avatarColor = $colors[$colorIndex];
        $initial = strtoupper(substr($review['customer_firstname'], 0, 1));
      ?>
        <div class="review-card">
          <div class="review-header">
            <div class="product-info">
              <strong><?= $productName ?></strong>
              <?php if ($productBrand): ?>
                <span class="brand"> by <?= $productBrand ?></span>
              <?php endif; ?>
            </div>
            <div class="star-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
              <?php endfor; ?>
            </div>
          </div>
          <p class="review-text">"<?= $comment ?>"</p>
          <div class="reviewer-info">
            <?php if (!empty($review['profile_picture']) && file_exists($review['profile_picture'])): ?>
              <img src="<?= htmlspecialchars($review['profile_picture']) ?>" alt="<?= $customerName ?>" class="avatar">
            <?php else: ?>
              <div class="avatar" style="background-color: <?= $avatarColor ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 20px;">
                <?= $initial ?>
              </div>
            <?php endif; ?>
            <div class="reviewer-details">
              <div class="reviewer-name"><?= $customerName ?></div>
              <div class="reviewer-location"><?= $reviewDate ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>" class="page-btn">Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                <a href="?page=<?= $i ?>" 
                   class="page-btn <?= $i == $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php elseif (abs($i - $currentPage) == 3): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>" class="page-btn">Next</a>
        <?php endif; ?>
    </div>
  <?php endif; ?>

 <!-- Footer -->
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

</body>
</html>
