<?php
// faq.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FAQ - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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

.container {
  max-width: 900px;
  margin: 150px auto 60px;
  padding: 0 20px;
}
h1 {
  text-align: center;
  font-family: 'Playfair Display', serif;
  font-size: 42px;
  margin-bottom: 40px;
}

.faq-item {
  border-bottom: 1px solid #ddd;
  margin-bottom: 15px;
}
.faq-question {
  cursor: pointer;
  padding: 15px;
  font-weight: 600;
  position: relative;
}
.faq-question::after {
  content: '+';
  position: absolute;
  right: 20px;
  font-size: 20px;
  transition: transform 0.3s;
}
.faq-item.active .faq-question::after {
  transform: rotate(45deg);
}
.faq-answer {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s ease, padding 0.4s ease;
  padding: 0 15px;
  color: #333;
}
.faq-item.active .faq-answer {
  max-height: 300px;
  padding: 15px;
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

<div class="container">
  <h1>Frequently Asked Questions</h1>

  <div class="faq-item">
    <div class="faq-question">What makes Happy Sprays perfumes unique?</div>
    <div class="faq-answer">
      Our perfumes are crafted with carefully selected notes designed to last long and suit every personality, while remaining affordable.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">How long is the delivery time?</div>
    <div class="faq-answer">
      For Metro Manila, delivery usually takes <strong>1–2 days</strong>.  
      For outside Metro Manila, it may take <strong>2–4 days</strong>.  
      We aim to deliver your perfumes as quickly as possible!
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">How can I contact you directly?</div>
    <div class="faq-answer">
      You can message us directly via <a href="#">Facebook Messenger</a> or <a href="#">Instagram</a>. We’d be happy to assist you with any concerns or orders!
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">What sizes are available?</div>
    <div class="faq-answer">
      Currently, we offer <strong>30ml</strong> bottles.  
      <strong>50ml</strong> bottles will be available soon — stay tuned!
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">What is the oil concentration of your perfumes?</div>
    <div class="faq-answer">
      Our perfumes are made with <strong>25% oil base</strong>, ensuring a long-lasting scent that stays with you throughout the day.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">Does it come with packaging?</div>
    <div class="faq-answer">
      Yes! Each perfume comes with its own packaging, making it perfect for gifting or personal use.
    </div>
  </div>
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

<script>
// accordion toggle
const faqs = document.querySelectorAll(".faq-item");
faqs.forEach(faq => {
  faq.querySelector(".faq-question").addEventListener("click", () => {
    faq.classList.toggle("active");
  });
});
</script>

</body>
</html>
