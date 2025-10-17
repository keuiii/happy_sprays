<?php
session_start();
require_once '../classes/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in']);
        exit;
    }

    $orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $customerId = $_SESSION['customer_id'];

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    // ✅ Verify order belongs to customer
    $stmt = $db->prepare("SELECT order_id, customer_id FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['customer_id'] != $customerId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // ✅ Fetch correct perfume(s) for this order
    $stmt = $db->prepare("
        SELECT 
            p.perfume_id,
            p.perfume_name,
            p.perfume_brand
        FROM order_items oi
        INNER JOIN perfumes p ON oi.perfume_id = p.perfume_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        echo json_encode(['success' => false, 'message' => 'No products found for this order.']);
    } else {
        echo json_encode(['success' => true, 'products' => $products]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
