<?php
session_start();
require_once 'classes/database.php';

// Create DB instance
$db = Database::getInstance();

// Handle filters
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Use centralized method from database.php
$products = $db->getPerfumes($gender_filter, $search_query);

// Poster images
$posters = ["poster1.png","poster2.png", "poster3.png"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Happy Sprays - Shop</title>
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

.cart-count {
  position: absolute;
  top: -6px;
  right: -12px;
  color: #111;
  font-size: 11px;
  font-weight: 500;
  padding: 2px 6px;
  border-radius: 999px;
  border: 1px solid #ddd;
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
  display: <?= isset($_SESSION['cart']) && count($_SESSION['cart']) > 0 ? "inline-block" : "none" ?>;
}

/* Search Icon */
.search-link svg {
  display: block;
  width: 22px;
  height: 23px;
  stroke: black;
}

/* Sub Navbar */
.sub-nav {
  position:fixed;
  top:60px;
  left:0;
  width:100%;
  background:#fff;
  border-bottom:1px solid #ccc;
  text-align:center;
  padding:12px 0;
  transition:top 0.3s;
  z-index:999;
  font-family:'Playfair Display', serif;
  text-transform:uppercase;
  font-weight:700;
  letter-spacing:1px;
}

.sub-nav a {
  margin:0 20px;
  text-decoration:none;
  color:#000;
  font-size:18px;
  transition:color 0.3s;
}

.sub-nav a:hover {
  color:#555;
}

/* Hero Slider */
.hero-slider {
  position:relative;
  margin-top:120px;
  width:100%;
  height:500px;
  overflow:hidden;
}

.hero-slider .slides img {
  width:100%;
  height:100%;
  object-fit:contain;
  position:absolute;
  top:0;
  left:0;
  opacity:0;
  transition:opacity 1s ease-in-out;
}

.hero-slider .slides img.active {
  opacity:1;
}

.hero-slider button {
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  background:rgba(255,255,255,0.7);
  border:none;
  font-size:30px;
  cursor:pointer;
  width:40px;
  height:5px;
  border-radius:2px;
  padding:0;
}

.hero-slider .prev {left:10px;}
.hero-slider .next {right:10px;}
.hero-slider button:hover {background:rgba(255,255,255,0.9);}

/* Marquee */
.marquee {
  width:100%;
  overflow:hidden;
  background:#fff;
  border-top:2px solid #000;
  border-bottom:2px solid #000;
  padding:10px 0;
  box-sizing:border-box;
}

.marquee-content {
  display:inline-block;
  padding-left:100%;
  white-space:nowrap;
  animation:marquee 15s linear infinite;
}

.marquee-content span {
  display:inline-flex;
  align-items:center;
  margin-right:50px;
  font-weight:bold;
  color:#000;
  font-family:'Playfair Display', serif;
}

.marquee-content span img {
  margin-right:8px;
}

@keyframes marquee {
  0% {transform:translateX(0);}
  100% {transform:translateX(-100%);}
}

/* Products */
h1 {text-align:center; margin:30px 0;}

.product-grid {
  display:grid;
  grid-template-columns: repeat(4, 1fr);
  gap:30px;
  padding:20px;
  max-width:1200px;
  margin:auto;
}

.product-card {
  display:flex;
  flex-direction:column;
  text-align:center;
  cursor:pointer;
  border-radius:5px;
  overflow:hidden;
  transition: transform 0.3s, box-shadow 0.3s;
}

.product-card:hover {
  transform:translateY(-5px);
  box-shadow:0 8px 15px rgba(255, 255, 255, 0.2);
}

.product-card img {
  width:100%;
  height:250px;
  object-fit:cover;
  border-bottom:0;
  transition:border-bottom 0.3s;
}

.product-card:hover img {
  border-bottom:2px solid #ffffffff;
}

.product-card h2 {
  margin:10px 0 5px 0;
  font-size:16px;
  font-weight:bold;
}

.product-card p {
  margin:0 0 10px 0;
  font-weight:normal;
}

.product-card button {
  padding:6px 12px;
  border:none;
  background:#000;
  color:#fff;
  cursor:pointer;
  border-radius:4px;
  transition:0.3s;
  margin-bottom:10px;
}

.product-card button:hover {
  background:#444;
}

/* Quick View */
.qv-img-box {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
}

.qv-img-box img {
  width: 100%;
  display: block;
  transition: transform .3s ease;
}

.product-card:hover .qv-img-box img {
  transform: scale(1.04);
}

.quick-view-trigger {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,.45);
  color: #fff;
  font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  opacity: 0;
  transition: opacity .25s ease;
  cursor: pointer;
  border: 0;
}

.qv-img-box:hover .quick-view-trigger {
  opacity: 1;
}

.qv-backdrop[hidden] {
  display: none;
}

.qv-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity .25s ease;
}

.qv-backdrop.show {
  opacity: 1;
}

.qv-modal {
  position: relative;
  width: min(92vw, 520px);
  background: #fff;
  border: 2px solid #000;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,.25);
  padding: 20px;
  transform: scale(.92);
  opacity: 0;
  transition: transform .25s ease, opacity .25s ease;
}

.qv-backdrop.show .qv-modal {
  transform: scale(1);
  opacity: 1;
}

.qv-close {
  position: absolute;
  top: 10px;
  right: 14px;
  font-size: 26px;
  line-height: 1;
  cursor: pointer;
  color: #000;
  background: transparent;
  border: 0;
}

.qv-body {
  display: grid;
  gap: 14px;
}

.qv-img {
  width: 100%;
  height: 260px;
  object-fit: contain;
  background: #fafafa;
  border: 1px solid #000;
  border-radius: 10px;
}

.qv-name {
  font-family: 'Playfair Display', serif;
  font-size: 22px;
  border-bottom: 2px solid #000;
  padding-bottom: 6px;
}

.qv-price {
  font-size: 18px;
  font-weight: 700;
}

.qv-actions {
  display: grid;
  gap: 10px;
  margin-top: 6px;
}

.qv-btn {
  padding: 12px 16px;
  font-weight: 700;
  border-radius: 10px;
  cursor: pointer;
  transition: .25s;
}

.qv-btn.primary {
  background: #000;
  color: #fff;
  border: 2px solid #000;
}

.qv-btn.primary:hover {
  background: #fff;
  color: #000;
}

.qv-btn.ghost {
  background: #fff;
  color: #000;
  border: 2px solid #000;
  text-decoration: none;
  display: block;
  text-align: center;
}

.qv-btn.ghost:hover {
  background: #000;
  color: #fff;
}

/* Poster block */
.poster-block {
  grid-column: span 4;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 30px 0;
  padding: 20px;
  border-radius: 8px;
  background: #f5f5f5;
  text-align: center;
}

.poster-block img {
  width: 50%;
  height: auto;
  border-radius: 8px;
  margin-bottom: 15px;
}

.poster-text h2 {
  font-family: 'Playfair Display', serif;
  font-size: 22px;
  font-weight: 700;
  color: #000;
}

/* Search Panel */
.search-panel {
  position: fixed;
  top: 0;
  right: -400px;
  width: 400px;
  height: 100vh;
  background: #fff;
  box-shadow: -2px 0 10px rgba(0,0,0,0.1);
  z-index: 10001;
  transition: right 0.3s ease;
  padding: 20px;
}

.search-panel.active {
  right: 0;
}

.close-btn {
  position: absolute;
  top: 15px;
  right: 20px;
  background: none;
  border: none;
  font-size: 30px;
  cursor: pointer;
}

.search-form {
  margin-top: 60px;
}

.search-form input {
  width: 100%;
  padding: 12px;
  border: 2px solid #000;
  border-radius: 8px;
  font-size: 16px;
  margin-bottom: 10px;
}

.search-form button {
  width: 100%;
  padding: 12px;
  background: #000;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
}

.search-form button:hover {
  background: #333;
}

#suggestions {
  display: none;
  margin-top: 10px;
}

#suggestions div {
  padding: 10px;
  border: 1px solid #eee;
  margin-bottom: 5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 5px;
  transition: 0.2s;
}

#suggestions div:hover {
  background: #f5f5f5;
}

/* Chat Button */
#chatBtn {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #000;
  color: #fff;
  border-radius: 50%;
  padding: 15px;
  cursor: pointer;
  font-size: 22px;
  z-index: 9999;
  box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  transition: 0.3s;
}

#chatBtn:hover {
  background: #333;
}

/* Chatbox */
#chatBox.hidden {
  display: none;
}

#chatBox {
  position: fixed;
  bottom: 80px;
  right: 20px;
  width: 320px;
  max-height: 420px;
  background: #fff;
  border: 2px solid #000;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 10000;
  transition: all 0.3s ease-in-out;
}

#chatBox.expanded {
  width: 380px;
  max-height: 540px;
}

.chat-header {
  background: #000;
  color: #fff;
  padding: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
}

.chat-actions button {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 18px;
  margin-left: 6px;
  cursor: pointer;
  transition: 0.2s;
}

.chat-actions button:hover {
  color: #bbb;
}

.chat-body {
  flex: 1;
  padding: 10px;
  overflow-y: auto;
  font-size: 14px;
  background: #fff;
}

.bot-message {
  background: #f0f0f0;
  padding: 8px 10px;
  border-radius: 8px;
  margin-bottom: 10px;
  border: 1px solid #000;
  max-width: 80%;
}

.user-message {
  text-align: right;
  margin: 6px 0;
  background: #000;
  color: #fff;
  padding: 8px 10px;
  border-radius: 8px;
  max-width: 80%;
  margin-left: auto;
}

.suggestions, .chat-suggestions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 10px;
}

.suggestion {
  border: 1px solid #000;
  background: #fff;
  padding: 6px 8px;
  border-radius: 6px;
  cursor: pointer;
  text-align: left;
  transition: 0.2s;
  font-size: 13px;
}

.suggestion:hover {
  background: #000;
  color: #fff;
}

.chat-footer {
  display: flex;
  border-top: 1px solid #000;
}

#userInput {
  flex: 1;
  padding: 8px;
  border: none;
  outline: none;
}

#sendBtn {
  background: #000;
  color: #fff;
  border: none;
  padding: 0 15px;
  cursor: pointer;
  transition: 0.2s;
}

#sendBtn:hover {
  background: #333;
}

.typing {
  display: inline-block;
  padding: 6px 12px;
  background: #f1f1f1;
  border-radius: 15px;
  font-size: 13px;
  color: #555;
  margin: 5px 0;
}

.typing span {
  display: inline-block;
  animation: blink 1.4s infinite both;
}

.typing span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes blink {
  0% {opacity: 0.2;}
  20% {opacity: 1;}
  100% {opacity: 0.2;}
}

/* Footer */
footer {
    background: #000;
    border-top: 1px solid #000;
    padding: 50px 20px;
    text-align: center;
    font-size: 14px;
    color: #fff;
    margin-top: 60px;
}

.footer-columns {
    display: flex;
    justify-content: center;
    gap: 80px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.footer-columns h4 {
    font-size: 14px;
    margin-bottom: 12px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-columns a {
    display: block;
    text-decoration: none;
    color: #ccc;
    margin: 6px 0;
    font-size: 13px;
    transition: color 0.3s;
}

.footer-columns a:hover { color: #fff; }

.social-icons { 
    margin-top: 20px; 
}

.social-icons a {
    margin: 0 10px;
    color: #ccc;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.social-icons a:hover { color: #fff; }

footer p {
    margin-top: 20px;
    color: #999;
    font-size: 12px;
}

/* Scroll Animation */
.scroll-animate {
  opacity: 0;
  transform: translateY(50px);
  transition: all 0.6s ease-out;
}

.scroll-animate.visible {
  opacity: 1;
  transform: translateY(0);
}

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

#snow-container {
  position: fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  pointer-events:none; /* lets you click through */
  z-index:9999;
}

.snowflake {
  position: absolute;
  top: -10px;
  color: #fff;
  font-size: 1em;
  user-select: none;
  z-index: 9999;
  animation-name: fall;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
}

@keyframes fall {
  0% { transform: translateY(0px); }
  100% { transform: translateY(100vh); }
}
</style>
</head>
<body>

<div id="snow-container"></div>

<div class="top-nav">
  <div class="logo">Happy Sprays</div>
  <div class="nav-actions">
    <!-- Search Icon -->
    <button id="openSearch" class="icon-btn" type="button">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </button>

    <!-- Cart Icon with Bubble -->
    <a href="cart.php" class="cart-link" style="position:relative">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 7h12l1 12H5L6 7z"/>
        <path d="M9 7V5a3 3 0 0 1 6 0v2"/>
      </svg>
      <span id="cartCount" style="position:absolute; top:-8px; right:-8px; background:red; color:#fff; font-size:12px; font-weight:bold; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
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

<!-- Sliding Search Panel -->
<div id="searchPanel" class="search-panel">
  <button id="closeSearch" class="close-btn">&times;</button>
  <form action="index.php" method="GET" class="search-form">
    <input type="text" id="liveSearch" name="q" placeholder="Search perfumes..." autocomplete="off" />
    <button type="submit">Search</button>
    <div id="suggestions"></div>
  </form>
</div>

<!-- Sub Nav -->
<div class="sub-nav" id="subNav">
    <a href="index.php">HOME</a>
    <a href="index.php?gender=Male">For Him</a>
    <a href="index.php?gender=Female">For Her</a>
    <a href="reviews.php">REVIEWS</a>
</div>

<!-- Hero Slider -->
<div class="hero-slider">
    <div class="slides">
        <img src="images/ss5.png" class="slide active" alt="Banner 1">
          <img src="images/ss.png" class="slide" alt="Banner 5">
        <img src="images/ss2.png" class="slide" alt="Banner 2">
        <img src="images/ss3.png" class="slide" alt="Banner 3">
        <img src="images/ss4.png" class="slide" alt="Banner 4">
        
    </div>
    <button class="prev">&#8212;</button>
    <button class="next">&#8212;</button>
</div>

<!-- Marquee -->
<div class="marquee">
    <div class="marquee-content">
        <span><img src="images/icon1.png" width="20" alt=""> Happy Sprays – New Fragrances</span>
        <span><img src="images/icon2.png" width="20" alt=""> Happy Sprays – Free Delivery</span>
        <span><img src="images/icon3.png" width="20" alt=""> Happy Sprays – Limited Edition</span>
    </div>
</div>

<!-- QUICK VIEW MODAL -->
<div id="qvBackdrop" class="qv-backdrop" hidden>
  <div class="qv-modal" role="dialog" aria-modal="true" aria-labelledby="qvName">
    <button class="qv-close" type="button" aria-label="Close">&times;</button>
    <div class="qv-body">
      <img id="qvImage" class="qv-img" src="" alt="">
      <h3 id="qvName" class="qv-name"></h3>
      <div id="qvPrice" class="qv-price"></div>
      <p id="qvDesc" class="qv-desc"></p>
      <div class="qv-actions">
        <button id="qvAddToCart" class="qv-btn primary" type="button">Add to Cart</button>
        <a id="qvViewBtn" class="qv-btn ghost" href="#">View Full Details</a>
      </div>
    </div>
  </div>
</div>

<!-- Chat Button -->
<div id="chatBtn">💬</div>

<!-- Chatbox -->
<div id="chatBox" class="hidden">
  <div class="chat-header">
    <span>Happy Sprays Support</span>
    <div class="chat-actions">
      <button id="expandChat">⤢</button>
      <button id="closeChat">✖</button>
    </div>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="bot-message">Hi! 👋 How can I help you today?</div>
    <div class="chat-suggestions">
      <button class="suggestion">What are your best sellers?</button>
      <button class="suggestion">Do you offer delivery?</button>
      <button class="suggestion">What sizes are available?</button>
      <button class="suggestion">Are the perfumes long lasting?</button>
      <button class="suggestion">Can I chat with a live agent?</button>
    </div>
  </div>
  <div class="chat-footer">
    <input type="text" id="userInput" placeholder="Type a message..." />
    <button id="sendBtn">➤</button>
  </div>
</div>

<!-- Products -->
<h1>Our Perfumes</h1>
<div class="product-grid">
<?php
$count = 0;
foreach($products as $prod){
    // gamitin file_path directly
   $imagePath = !empty($prod['file_path']) ? $prod['file_path'] : "images/default.jpg";

echo "
<div class='product-card scroll-animate'>
  <div class='qv-img-box'>
    <img src='{$imagePath}' alt='".htmlspecialchars($prod['perfume_name'], ENT_QUOTES)."'>
    <button
      class='quick-view-trigger'
      type='button'
      data-id='{$prod['perfume_id']}'
      data-name=\"".htmlspecialchars($prod['perfume_name'], ENT_QUOTES)."\"
      data-price='{$prod['perfume_price']}'
      data-image='{$imagePath}'
      data-description=\"".htmlspecialchars($prod['perfume_desc'], ENT_QUOTES)."\"
    >Quick View</button>
  </div>
  <h2>".htmlspecialchars($prod['perfume_name'])."</h2>
  <p>₱{$prod['perfume_price']}</p>
  <button onclick=\"window.location.href='view_product.php?id={$prod['perfume_id']}'\">View Details</button>
</div>
";


    $count++;
    if ($count % 4 == 0) {
        $poster_img = $posters[(int)(($count/4)-1) % count($posters)];
        $promo_text = "Discover Our Exclusive Fragrances!";
        echo "
        <div class='poster-block scroll-animate'>
            <img src='images/{$poster_img}' alt='Poster'>
            <div class='poster-text'>
                <h2>{$promo_text}</h2>
            </div>
        </div>
        ";
    }
}
?>
</div>


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

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Register this page as a valid navigation point for cart back button
sessionStorage.setItem('lastValidPage', window.location.href);

// Sub nav scroll hide/show
let lastScrollTop = 0;
const subNav = document.getElementById("subNav");
window.addEventListener("scroll", function(){
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    subNav.style.top = (scrollTop > lastScrollTop) ? "-60px" : "60px";
    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
}, false);

// Hero slider
let slides = document.querySelectorAll('.slide'); 
let current = 0;
function showSlide(index){
    slides.forEach(slide => slide.classList.remove('active'));
    slides[index].classList.add('active');
}
setInterval(()=>{
    current = (current + 1) % slides.length;
    showSlide(current);
},5000);
document.querySelector('.prev').addEventListener('click', ()=>{
    current = (current - 1 + slides.length) % slides.length; 
    showSlide(current);
});
document.querySelector('.next').addEventListener('click', ()=>{
    current = (current + 1) % slides.length; 
    showSlide(current);
});

// Scroll Animation
const scrollElements = document.querySelectorAll('.scroll-animate');
const elementInView = (el, offset = 100) => {
    const elementTop = el.getBoundingClientRect().top;
    return elementTop <= (window.innerHeight - offset);
};
const displayScrollElement = (el) => {
    el.classList.add('visible');
};
const handleScrollAnimation = () => {
    scrollElements.forEach(el => {
        if (elementInView(el, 100)) {
            displayScrollElement(el);
        }
    });
};
window.addEventListener('scroll', handleScrollAnimation);
window.addEventListener('load', handleScrollAnimation);

// Search Panel open/close
document.addEventListener("DOMContentLoaded", () => {
    const openBtn = document.getElementById("openSearch");
    const closeBtn = document.getElementById("closeSearch");
    const searchPanel = document.getElementById("searchPanel");

    if(openBtn && closeBtn && searchPanel){
        openBtn.addEventListener("click", () => {
          searchPanel.classList.add("active");
        });
        closeBtn.addEventListener("click", () => {
          searchPanel.classList.remove("active");
        });
    }
});

// Live Search Suggestions
const searchInput = document.getElementById("liveSearch"); 
const suggestionsBox = document.getElementById("suggestions");

if(searchInput){
  searchInput.addEventListener("keyup", function() {
    let query = this.value.trim();
    if (query.length === 0) {
      suggestionsBox.style.display = "none";  
      return;
    }

    fetch("search_suggest.php?q=" + encodeURIComponent(query))
      .then(res => res.json())
      .then(data => {
        suggestionsBox.innerHTML = "";
        if (data.length > 0) {
          data.forEach(item => {
            let div = document.createElement("div");
            div.innerHTML = `
              ${item.image ? `<img src="images/${item.image}" width="40" height="40" style="border-radius:4px;object-fit:cover;">` : ""}
              <span><strong>${item.name}</strong><br><small>₱${item.price}</small></span>
            `;
            div.onclick = () => {
              window.location.href = "view_product.php?id=" + item.id;
            };
            suggestionsBox.appendChild(div);
          });
          suggestionsBox.style.display = "block";
        } else {
          suggestionsBox.style.display = "none";
        }
      });
  });
}

// Quick View + Cart Bubble Logic (FIXED)
(function(){
  const backdrop = document.getElementById('qvBackdrop');
  const closeBtn = backdrop?.querySelector('.qv-close');

  const imgEl   = document.getElementById('qvImage');
  const nameEl  = document.getElementById('qvName');
  const priceEl = document.getElementById('qvPrice');
  const descEl  = document.getElementById('qvDesc');
  const addBtn  = document.getElementById('qvAddToCart');
  const viewBtn = document.getElementById('qvViewBtn');
  const cartCountEl = document.getElementById('cartCount');

  let currentId = null;

  document.querySelectorAll('.quick-view-trigger').forEach(btn => {
    btn.addEventListener('click', () => {
      currentId = btn.dataset.id;

      const imgPath = btn.dataset.image.startsWith('images/') ? btn.dataset.image : 'images/' + btn.dataset.image;
      imgEl.src = imgPath;
      imgEl.alt = btn.dataset.name || '';
      nameEl.textContent = btn.dataset.name;
      priceEl.textContent = "₱" + btn.dataset.price;
      descEl.textContent = btn.dataset.description || "";
      viewBtn.href = "view_product.php?id=" + currentId;

      backdrop.hidden = false;
      setTimeout(() => backdrop.classList.add('show'), 10);

      addBtn.dataset.id = currentId;
      addBtn.dataset.name = btn.dataset.name;
      addBtn.dataset.price = btn.dataset.price;
      addBtn.dataset.image = btn.dataset.image;
    });
  });

  closeBtn?.addEventListener('click', ()=>{
    backdrop.classList.remove('show');
    setTimeout(()=>backdrop.hidden=true, 250);
  });

  function updateCartCount() {
    fetch('cart_count.php')
      .then(res => res.text())
      .then(count => {
        cartCountEl.textContent = count;
      });
  }

  // FIXED: Send correct parameters matching database.php
  addBtn.addEventListener('click', ()=>{
    const fd = new FormData();
    fd.append('add_to_cart','1');
    fd.append('id', addBtn.dataset.id);
    fd.append('name', addBtn.dataset.name);
    fd.append('price', addBtn.dataset.price);
    fd.append('image', addBtn.dataset.image);
    fd.append('qty', 1);  // ADDED THIS

    fetch('cart.php',{
      method:'POST',
      body:fd,
      headers:{'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r=>{
      if(!r.ok) throw new Error('Server error: ' + r.status);
      return r.text();
    })
    .then(tx=>{
      // Clean and normalize the response
      tx = tx.trim().toLowerCase();
      
      console.log('Cart response received:', tx); // Debug logging
      
      // Check for valid responses
      if(tx === 'added' || tx === 'already_exists'){
        // Update cart count first
        updateCartCount();
        
        // Close the modal
        backdrop.classList.remove('show');
        setTimeout(()=>backdrop.hidden=true,250);
        
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
        console.error('Unexpected cart response:', tx);
        console.error('Response length:', tx.length);
        console.error('Response bytes:', Array.from(tx).map(c => c.charCodeAt(0)));
        
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
    }).catch((err)=>{
      console.error('Cart fetch error:', err);
      Swal.fire({
        title: 'Network Error',
        text: 'Could not connect to server. Please check your connection.',
        icon: 'error',
        confirmButtonText: 'OK',
        confirmButtonColor: '#000'
      });
    });
  });

  // Initialize cart count on load
  document.addEventListener('DOMContentLoaded', updateCartCount);
})();

// Chatbot Logic
const chatBtn = document.getElementById("chatBtn");
const chatBox = document.getElementById("chatBox");
const closeChat = document.getElementById("closeChat");
const chatBody = document.getElementById("chatBody");
const userInput = document.getElementById("userInput");
const sendBtn = document.getElementById("sendBtn");
const expandChat = document.getElementById("expandChat");

chatBtn.addEventListener("click", () => {
  chatBox.classList.toggle("hidden");
});

closeChat.addEventListener("click", () => {
  chatBox.classList.add("hidden");
});

sendBtn.addEventListener("click", sendMessage);
userInput.addEventListener("keypress", (e) => {
  if (e.key === "Enter") sendMessage();
});

function sendMessage() {
  const text = userInput.value.trim();
  if (!text) return;
  appendMessage("user", text);
  userInput.value = "";
  handleBotReply(text);
}

function appendMessage(sender, text) {
  const div = document.createElement("div");
  div.className = sender === "bot" ? "bot-message" : "user-message";
  div.textContent = text;
  chatBody.appendChild(div);
  chatBody.scrollTop = chatBody.scrollHeight;
}

function handleBotReply(text) {
  showTyping();

  setTimeout(() => {
    removeTyping();

    let reply = "Sorry, I don't understand.";
    const lower = text.toLowerCase().replace(/[?.!,]/g, "").trim();

    if (lower.includes("best")) {
      reply = "Our best sellers are Amity, Mirth, Quaint, and Gentle 🌸.";
    } 
    else if (lower.includes("deliver")) {
      reply = "Yes! 🚚 For Metro Manila, we can do same-day or 1–2 days delivery. For outside Metro Manila, it usually takes 2–3 days.";
    } 
    else if (lower.includes("size") || lower.includes("sizes")) {
      reply = "We currently offer 30ml bottles. 50ml will be available soon! ✨";
    } 
    else if (lower.includes("lasting") || lower.includes("long lasting")) {
      reply = "Yes! Our perfumes are crafted to last 6–8 hours depending on skin type 🌿.";
    } 
    else if (lower.includes("agent") || lower.includes("live agent") || lower.includes("live")) {
      reply = "You can chat with us directly on Messenger 📩 — just search for *Happy Sprays* or click the Messenger icon on our site.";
    }

    appendMessage("bot", reply);
  }, 600);
}

document.querySelectorAll(".suggestion").forEach(btn => {
  btn.addEventListener("click", () => {
    const text = btn.innerText;
    appendMessage("user", text);
    handleBotReply(text);
  });
});

function showTyping() {
  const typingDiv = document.createElement("div");
  typingDiv.classList.add("bot-message", "typing");
  typingDiv.innerText = "Let me check...";
  typingDiv.id = "typingIndicator";
  chatBody.appendChild(typingDiv);
  chatBody.scrollTop = chatBody.scrollHeight;
}

function removeTyping() {
  const typingDiv = document.getElementById("typingIndicator");
  if (typingDiv) typingDiv.remove();
}

expandChat.addEventListener("click", () => {
  chatBox.classList.toggle("expanded");
});

const snowContainer = document.getElementById('snow-container');
const snowCount = 50; // number of snowflakes

for (let i=0; i<snowCount; i++) {
  const snow = document.createElement('div');
  snow.className = 'snowflake';
  snow.style.left = Math.random() * window.innerWidth + 'px';
  snow.style.animationDuration = (3 + Math.random()*5) + 's';
  snow.style.fontSize = (10 + Math.random()*15) + 'px';
  snow.textContent = '❄';
  snowContainer.appendChild(snow);
}
</script>

</body>
</html>