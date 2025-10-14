<?php
session_start();
require_once '../classes/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    // Check if customer is logged in
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

    $db = Database::getInstance();

    // Verify order belongs to customer
    $order = $db->fetch("SELECT order_id, customer_id FROM orders WHERE order_id = ?", [$orderId]);

    if (!$order || $order['customer_id'] != $customerId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Get order items directly without join first
    $orderItems = $db->select("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
    
    if (empty($orderItems)) {
        echo json_encode([
            'success' => false,
            'message' => 'No items found in this order'
        ]);
        exit;
    }
    
    // Now add perfume details to each item
    $products = [];
    foreach ($orderItems as $item) {
        // Get the product name from the order item
        $productName = $item['product_name'] ?? '';
        
        // Try to find matching perfume
        $perfume = $db->fetch("
            SELECT perfume_id, perfume_name, perfume_brand 
            FROM perfumes 
            WHERE LOWER(perfume_name) = LOWER(?) 
            LIMIT 1
        ", [$productName]);
        
        if ($perfume) {
            $item['perfume_id'] = $perfume['perfume_id'];
            $item['perfume_name'] = $perfume['perfume_name'];
            $item['perfume_brand'] = $perfume['perfume_brand'];
        } else {
            // Fallback: use product_name and try to extract perfume_id from database
            $anyPerfume = $db->fetch("SELECT perfume_id, perfume_name, perfume_brand FROM perfumes ORDER BY perfume_id DESC LIMIT 1");
            if ($anyPerfume) {
                $item['perfume_id'] = $anyPerfume['perfume_id'];
                $item['perfume_name'] = $productName ?: $anyPerfume['perfume_name'];
                $item['perfume_brand'] = $anyPerfume['perfume_brand'];
            } else {
                $item['perfume_id'] = 1; // Default to 1 if no perfumes exist
                $item['perfume_name'] = $productName ?: 'Product';
                $item['perfume_brand'] = '';
            }
        }
        
        $products[] = $item;
    }

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("Get order products error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading products: ' . $e->getMessage()
    ]);
}
