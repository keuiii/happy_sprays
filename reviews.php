<?php
$reviews = [
    [
        'text' => 'I\'ve tried countless perfume brands, but nothing compares to Happy Sprays. Every scent feels luxurious and long-lasting! My signature fragrance now.',
        'name' => 'Olivia Richardson',
        'location' => 'New York, USA',
        'avatar' => 'r1.png',
        'color' => '#ffcdd2'
    ],
    [
        'text' => 'As a perfume lover, I appreciate the rich and sophisticated notes in every bottle. Happy Sprays has become my go-to for both day and evening wear!',
        'name' => 'Sophia Mitchell',
        'location' => 'London, UK',
        'avatar' => 'r2.png',
        'color' => '#ffe0b2'
    ],
    [
        'text' => 'I never knew perfume could smell this amazing! The fragrances are so unique and captivating. Plus, the packaging is beautiful—perfect for gifting!',
        'name' => 'Aisha Khan',
        'location' => 'Dubai, UAE',
        'avatar' => 'r3.png',
        'color' => '#fff9c4'
    ],
    [
        'text' => 'The variety of scents is incredible! Whether I need something fresh for work or romantic for date night, Happy Sprays has it all. Highly recommend!',
        'name' => 'Emily Sanders',
        'location' => 'Sydney, Australia',
        'avatar' => 'r4.png',
        'color' => '#c8e6c9'
    ],
    [
        'text' => 'This perfume has changed my daily routine for the better! The scent lasts all day without being overpowering. Love the elegant bottle design too!',
        'name' => 'Priya Deshmukh',
        'location' => 'Mumbai, India',
        'avatar' => 'r5.png',
        'color' => '#e1bee7'
    ],
    [
        'text' => 'I\'m obsessed with the floral notes! The perfume gives me confidence and makes me feel elegant all day long. A must-have for any perfume enthusiast!',
        'name' => 'Mia Lawrence',
        'location' => 'Toronto, Canada',
        'avatar' => 'r6.png',
        'color' => '#f8bbd0'
    ],
    [
        'text' => 'Absolutely delightful fragrance! The quality is outstanding, and I love how each perfume has its unique character and depth. My new favorite brand!',
        'name' => 'Chen Wei',
        'location' => 'Singapore',
        'avatar' => 'r7.png',
        'color' => '#b3e5fc'
    ],
    [
        'text' => 'Perfect scent for any occasion! The notes are perfectly balanced—not too sweet, not too strong. I appreciate the premium quality at an affordable price!',
        'name' => 'Isabella Garcia',
        'location' => 'Madrid, Spain',
        'avatar' => 'r8.png',
        'color' => '#ffccbc'
    ],
    [
        'text' => 'This perfume exceeded my expectations! The woody and musky notes are incredibly soothing and sophisticated. I get compliments everywhere I go!',
        'name' => 'Sarah Johnson',
        'location' => 'Los Angeles, USA',
        'avatar' => 'r9.png',
        'color' => '#d1c4e9'
    ],
    [
        'text' => 'The best perfume I\'ve ever owned! Each spritz feels like a luxury experience. The scent lingers beautifully without being overwhelming. Love it!',
        'name' => 'Yuki Tanaka',
        'location' => 'Tokyo, Japan',
        'avatar' => 'r10.png',
        'color' => '#c5e1a5'
    ],
    [
        'text' => 'I bought this as a gift and ended up buying more for myself! The fragrance is phenomenal—fresh, elegant, and timeless. Definitely worth every penny!',
        'name' => 'Emma Brown',
        'location' => 'Melbourne, Australia',
        'avatar' => 'r11.png',
        'color' => '#ffe082'
    ]
];

// Pagination setup
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalReviews = count($reviews);
$totalPages = ceil($totalReviews / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get reviews for current page
$paginatedReviews = array_slice($reviews, $offset, $itemsPerPage);
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
    <?php foreach ($paginatedReviews as $review): ?>
      <div class="review-card">
        <p class="review-text">"<?= htmlspecialchars($review['text']) ?>"</p>
        <div class="reviewer-info">
          <img src="images/<?= $review['avatar'] ?>" alt="<?= htmlspecialchars($review['name']) ?>" class="avatar">
          <div class="reviewer-details">
            <div class="reviewer-name"><?= htmlspecialchars($review['name']) ?></div>
            <div class="reviewer-location"><?= htmlspecialchars($review['location']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
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
