<?php
// about.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About Us - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background:#fff;
  color:#000;
  line-height:1.6;
}

.top-nav {
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  background: #fff;
  border-bottom: 1px solid #eee;
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: 'Playfair Display', serif;
  font-size: 22px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 2px;
  z-index: 1000;
}
.top-nav .logo { flex: 1; text-align: center; }

.sub-nav {
  position: fixed;
  top: 60px; left: 0;
  width: 100%;
  background: #fff;
  border-bottom: 1px solid #ccc;
  text-align: center;
  padding: 12px 0;
  z-index: 999;
  font-family: 'Playfair Display', serif;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 1px;
}
.sub-nav a {
  margin: 0 20px;
  text-decoration: none;
  color: #000;
  font-size: 16px;
}
.sub-nav a:hover { color:#555; }


.hero {
  height: 70vh;
  background: url('poster1.png') center/cover no-repeat;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #fff;
}
.hero h1 {
  font-size: 48px;
  font-family: 'Playfair Display', serif;
  background: rgba(0,0,0,0.5);
  padding: 20px 40px;
  border-radius: 10px;
}

.section {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 10%;
  gap: 40px;
}
.section:nth-child(even) { flex-direction: row-reverse; }
.section img {
  width: 400px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  transition: transform 0.4s ease;
  cursor: pointer;
}
.section img:hover {
  transform: scale(1.1);
}

.section-text {
  flex: 1;
}
.section-text h2 {
  font-size: 32px;
  margin-bottom: 20px;
  font-family: 'Playfair Display', serif;
}
.section-text p {
  font-size: 16px;
  color: #333;
}

/* Modal Image Viewer */
#imageModal {
  display:none;
  position:fixed;
  z-index:2000;
  left:0; top:0;
  width:100%; height:100%;
  background:rgba(0,0,0,0.8);
  align-items:center;
  justify-content:center;
}
#imageModal img {
  max-width:90%;
  max-height:90%;
  border-radius:10px;
  box-shadow:0 4px 15px rgba(0,0,0,0.5);
  animation: zoomIn 0.4s ease;
}
@keyframes zoomIn {
  from { transform:scale(0.8); opacity:0; }
  to { transform:scale(1); opacity:1; }
}
#imageModal span {
  position:absolute;
  top:20px; right:30px;
  font-size:30px;
  color:#fff;
  cursor:pointer;
}

/* Footer */
footer {
    background: #000;
    border-top: 1px solid #000;
    padding: 40px 20px;
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
</style>
</head>
<body>

<!-- Navbar -->
<div class="top-nav">
  <div class="logo">Happy Sprays</div>
</div>
<div class="sub-nav">
  <a href="index.php">Home</a>
  <a href="index.php?gender=Male">For Him</a>
  <a href="index.php?gender=Female">For Her</a>
  <a href="contact.php">Contact</a>
</div>

<!-- Hero Section -->
<div class="hero">
  <h1 data-aos="fade-up">About Happy Sprays</h1>
</div>

<!-- Sections -->
<div class="section" data-aos="fade-right">
  <img src="images/ssabout.png" alt="Our Story" onclick="openModal(this)">
  <div class="section-text">
    <h2>Our Story</h2>
    <p>Happy Sprays started with a passion for scents that bring joy and confidence. Each fragrance is carefully crafted to suit every personality.</p>
  </div>
</div>

<div class="section" data-aos="fade-left">
  <img src="images/ssabout1.png" alt="Mission" onclick="openModal(this)">
  <div class="section-text">
    <h2>Our Mission</h2>
    <p>To provide high-quality perfumes that are both affordable and long-lasting, bringing happiness with every spray.</p>
  </div>
</div>

<div class="section" data-aos="fade-right">
  <img src="images\ssabout2.png" alt="Vision" onclick="openModal(this)">
  <div class="section-text">
    <h2>Our Vision</h2>
    <p>To become a trusted name in the fragrance industry, recognized for innovation and excellence worldwide.</p>
  </div>
</div>

<!-- Modal for full image -->
<div id="imageModal" onclick="closeModal()">
  <span>&times;</span>
  <img id="modalImg">
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

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({ duration: 1000, once: true });

  function openModal(img) {
    document.getElementById('imageModal').style.display = 'flex';
    document.getElementById('modalImg').src = img.src;
  }
  function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
  }
</script>
</body>
</html>
