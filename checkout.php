<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

if (isset($_POST['set_selected_items'])) {
    $selectedItems = json_decode($_POST['selected_items'], true);
    if (is_array($selectedItems)) {
        $_SESSION['selected_cart_items'] = $selectedItems;
    }
    echo json_encode(['success' => true]);
    exit;
}

if (!$db->isUserLoggedIn()) {
    header("Location: customer_login.php?redirect=checkout.php");
    exit;
}

if ($db->isCartEmpty()) {
    header("Location: cart.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $data = [
            'customer_firstname' => $_POST['customer_firstname'] ?? '',
            'customer_lastname' => $_POST['customer_lastname'] ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'name' => trim($_POST['customer_firstname'] . ' ' . $_POST['customer_lastname']),
            'email' => $_POST['customer_email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'street' => $_POST['street'] ?? '',
            'barangay' => $_POST['barangay'] ?? '',
            'city' => $_POST['city'] ?? '',
            'province' => $_POST['province'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'landmark' => $_POST['landmark'] ?? '',
            'latitude' => $_POST['latitude'] ?? '',
            'longitude' => $_POST['longitude'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? ''
        ];
        
        $files = $_FILES;
        
        $orderId = $db->processCheckout($data, $files);
        
        header("Location: order_success.php?order_id=" . $orderId);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$selectedItems = $_SESSION['selected_cart_items'] ?? null;
$customer = $db->getCurrentCustomer();
$cartSummary = $db->getCheckoutSummary($selectedItems);

if (empty($cartSummary['items'])) {
    $_SESSION['checkout_error'] = 'Please select at least one item to checkout.';
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    color: #000;
}

.top-nav {
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 20px;
    text-align: center;
}

.top-nav h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.checkout-wrapper {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
}

.checkout-form {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #000;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #000;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.payment-options {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.payment-option {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    text-align: center;
}

.payment-option:hover {
    border-color: #000;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-option.selected {
    border-color: #000;
    background: #f9f9f9;
}

#gcashProofSection {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.order-summary {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 20px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.item-details { flex: 1; }
.item-name { font-weight: 600; margin-bottom: 5px; }
.item-price { color: #666; font-size: 14px; }

.summary-totals {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #000;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.summary-row.total {
    font-weight: 700;
    font-size: 18px;
}

.place-order-btn {
    width: 100%;
    padding: 15px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
    transition: 0.3s;
}
.place-order-btn:hover { background: #333; }

.error-message {
    background: #ffebee;
    color: #c62828;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid #c62828;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #000;
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover { text-decoration: underline; }

@media (max-width: 768px) {
    .checkout-wrapper { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
}

/* MAP & BUTTON */
#map {
    height: 300px;
    border-radius: 8px;
    margin-top: 10px;
    display: none;
    opacity: 0;
    transition: all 0.5s ease;
}
#map.visible {
    display: block;
    opacity: 1;
    margin-top: 15px;
}
.use-location-btn {
    margin-top: 10px;
    padding: 10px 15px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}
.use-location-btn:hover { background: #333; }
</style>
</head>
<body>

<div class="top-nav">
    <h1>Happy Sprays</h1>
</div>

<div class="container">
    <a href="cart.php" class="back-link">← Back to Cart</a>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="checkout-wrapper">
        <div class="checkout-form">
            <h2 class="section-title">Billing Information</h2>
            
            <form method="POST" action="checkout.php" enctype="multipart/form-data" id="paymentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_firstname">First Name *</label>
                        <input type="text" id="customer_firstname" name="customer_firstname" value="<?= htmlspecialchars($customer['customer_firstname'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_lastname">Last Name *</label>
                        <input type="text" id="customer_lastname" name="customer_lastname" value="<?= htmlspecialchars($customer['customer_lastname'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Email *</label>
                    <input type="email" id="customer_email" name="customer_email" value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($customer['customer_contact'] ?? '') ?>" placeholder="09XX XXX XXXX">
                </div>
                
                <h2 class="section-title" style="margin-top: 30px;">Delivery Address</h2>

                <!-- Button + Hidden Map -->
                <button type="button" id="useMyLocationBtn" class="use-location-btn">📍 Use My Current Location</button>
                <div id="map"></div>

                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <div class="form-group">
                    <label for="street">Street Address *</label>
                    <input type="text" id="street" name="street" value="<?= htmlspecialchars($customer['customer_street'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" value="<?= htmlspecialchars($customer['customer_barangay'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['customer_city'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="province">Province *</label>
                        <input type="text" id="province" name="province" value="<?= htmlspecialchars($customer['customer_province'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code *</label>
                        <input type="text" id="postal_code" name="postal_code" value="<?= htmlspecialchars($customer['customer_postal_code'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="landmark">Landmark / Description</label>
                    <input type="text" id="landmark" name="landmark" placeholder="e.g., Blue gate, near 7-Eleven">
                </div>

                <h2 class="section-title" style="margin-top: 30px;">Payment Method</h2>
                <div class="payment-options">
                    
                    <div class="payment-option" data-payment="gcash">
                        <input type="radio" name="payment_method" value="gcash" id="gcash">
                        <label for="gcash"><strong>GCash</strong><br><small>Pay via GCash</small></label>
                    </div>
                </div>

                <div id="gcashProofSection">
                    <label for="gcash_ref">Upload Proof of Payment *</label>
                    <input type="file" name="gcash_ref" id="gcash_ref" accept="image/*">
                    <small style="display:block;margin-top:5px;color:#666;">
                        Send payment to: Happy Sprays 0945 1038 854 (GCash)
                    </small>
                    <div style="margin-top:15px;text-align:center;">
                        <img src="images/qrgcash.jpg" alt="GCash QR" style="max-width:200px;border:2px solid #ddd;border-radius:8px;">
                    </div>
                </div>

                <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
            </form>
        </div>
        
        <div class="order-summary">
            <h2 class="section-title">Order Summary</h2>
            <?php foreach ($cartSummary['items'] as $id => $item): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-price">₱<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></div>
                    </div>
                    <div>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                </div>
            <?php endforeach; ?>
            <div class="summary-totals">
                <div class="summary-row"><span>Subtotal:</span><span>₱<?= number_format($cartSummary['total'], 2) ?></span></div>
                <div class="summary-row"><span>Shipping:</span><span>FREE</span></div>
                <div class="summary-row total"><span>Total:</span><span>₱<?= number_format($cartSummary['total'], 2) ?></span></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, marker;

function initMap(lat = 14.5995, lng = 120.9842) {
  map = L.map('map').setView([lat, lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(map);
  marker = L.marker([lat, lng], { draggable: true }).addTo(map);
  marker.on('dragend', e => {
    const { lat, lng } = e.target.getLatLng();
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    reverseGeocodeAndFill(lat, lng);
  });
  map.on('click', e => {
    const { lat, lng } = e.latlng;
    marker.setLatLng([lat, lng]);
    reverseGeocodeAndFill(lat, lng);
  });
}

async function reverseGeocodeAndFill(lat, lng) {
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&zoom=18&accept-language=en`);
    const data = await res.json();
    const addr = data.address || {};
    document.getElementById('street').value = [addr.road || '', addr.house_number || ''].filter(Boolean).join(' ');
    document.getElementById('barangay').value = addr.suburb || addr.village || addr.barangay || '';
    document.getElementById('city').value = addr.city || addr.town || addr.municipality || '';
    document.getElementById('province').value = addr.state || '';
    document.getElementById('postal_code').value = addr.postcode || '';
  } catch (err) {
    console.error('Reverse geocode failed', err);
  }
}

document.getElementById('useMyLocationBtn').addEventListener('click', () => {
  const mapDiv = document.getElementById('map');
  mapDiv.classList.add('visible');
  if (!map) initMap();
  if (!navigator.geolocation) return alert('Geolocation not supported.');
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude, longitude } = pos.coords;
    document.getElementById('latitude').value = latitude;
    document.getElementById('longitude').value = longitude;
    map.setView([latitude, longitude], 16);
    marker.setLatLng([latitude, longitude]);
    reverseGeocodeAndFill(latitude, longitude);
  }, err => {
    alert('Unable to retrieve location.');
    console.error(err);
  }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
});

// Payment toggles
const paymentOptions = document.querySelectorAll('.payment-option');
const gcashSection = document.getElementById('gcashProofSection');
const gcashInput = document.getElementById('gcash_ref');
paymentOptions.forEach(opt => {
  opt.addEventListener('click', function(){
    paymentOptions.forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    const radio = this.querySelector('input[type="radio"]');
    radio.checked = true;
    if (radio.value === 'gcash') {
      gcashSection.style.display = 'block';
      gcashInput.required = true;
    } else {
      gcashSection.style.display = 'none';
      gcashInput.required = false;
    }
  });
});
</script>

</body>
</html>
