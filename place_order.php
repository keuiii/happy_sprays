<?php
session_start();
require_once 'classes/database.php';
$db = Database::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['customer_id'] ?? 0; // 0 if guest
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    // Address fields should be combined (as in checkout.php): street, city, province, postal
    $street   = trim($_POST['street'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal   = trim($_POST['postal'] ?? '');
    $address  = $street . ', ' . $city . ', ' . $province . ' ' . $postal;
    $payment  = trim($_POST['payment']);

    $gcash_proof = NULL;
    if ($payment === "gcash" && isset($_FILES['gcash_ref']) && $_FILES['gcash_ref']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }

        $filename = time() . "_" . basename($_FILES['gcash_ref']['name']);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['gcash_ref']['tmp_name'], $targetFile)) {
            $gcash_proof = $filename;
        }
    }

    $grand_total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $grand_total += $item['price'] * $item['quantity'];
    }

    // Insert into orders
    $params = [
        $customer_id, $name, $email, $address, $payment, $grand_total, $gcash_proof
    ];
    $sql = "INSERT INTO orders (customer_id, customer_name, email, address, payment_method, total_amount, gcash_proof, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $order_id = $db->insert($sql, $params);

    // Insert order items
    foreach ($_SESSION['cart'] as $item) {

        $pname = $item['name'];
        $qty   = intval($item['quantity']);
        $price = floatval($item['price']);
        $image = $item['image'];

        $db->insert(
            "INSERT INTO order_items (order_id, product_name, quantity, price, image)
             VALUES (?, ?, ?, ?, ?)",
            [$order_id, $pname, $qty, $price, $image]
        );
    }

    unset($_SESSION['cart']);
    header("Location: receipt.php?order_id=$order_id");
    exit;
}
?>