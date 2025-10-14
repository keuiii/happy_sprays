<?php
session_start();
require_once '../classes/database.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$customerId = $_SESSION['customer_id'];

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$db = Database::getInstance();

// Verify the order belongs to this customer and is in 'Out for Delivery' or 'Received' status
$order = $db->fetch("SELECT order_id, customer_id, order_status FROM orders WHERE order_id = ?", [$orderId]);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if ($order['customer_id'] != $customerId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Allow confirmation only if order is 'Out for Delivery' or 'Received'
$currentStatus = strtolower($order['order_status']);
if (!in_array($currentStatus, ['out for delivery', 'received'])) {
    echo json_encode(['success' => false, 'message' => 'Order cannot be confirmed at this stage']);
    exit;
}

// Update the order status to 'Completed'
$result = $db->updateOrderStatus($orderId, 'Completed');

if ($result['success']) {
    echo json_encode([
        'success' => true, 
        'message' => 'Order confirmed as delivered successfully!',
        'order_id' => $orderId
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => $result['message'] ?? 'Failed to update order status'
    ]);
}
