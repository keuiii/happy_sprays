<?php
session_start();
require_once 'classes/database.php';
$db = Database::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['customer_id'] ?? 0; // 0 if guest

    // ===== Basic Info =====
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $street   = trim($_POST['street'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal   = trim($_POST['postal'] ?? '');
    $address  = $street . ', ' . $city . ', ' . $province . ' ' . $postal;
    $payment  = trim($_POST['payment']);

    // ===== Shipping Fee =====
    $shipping_fee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0.00;

    // ===== GCash Proof Upload =====
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

    // ===== Compute Subtotal and Total =====
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $total_amount = $subtotal + $shipping_fee;

    // ===== Insert into orders =====
    $params = [
        $customer_id, 
        $name, 
        $email, 
        $address, 
        $payment, 
        $total_amount, 
        $shipping_fee,
        $gcash_proof
    ];

    $sql = "INSERT INTO orders 
            (customer_id, customer_name, email, address, payment_method, total_amount, shipping_fee, gcash_proof, o_created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $order_id = $db->insert($sql, $params);

    // ===== Insert Order Items =====
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

    // ===== Clear Cart and Redirect =====
    unset($_SESSION['cart']);
    header("Location: receipt.php?order_id=$order_id");
    exit;
}
?>
