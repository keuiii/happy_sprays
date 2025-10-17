<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../classes/database.php';
header('Content-Type: application/json');

try {
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
    $perfumeId = isset($_POST['perfume_id']) ? intval($_POST['perfume_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $customerId = $_SESSION['customer_id'];

    // Validation
    if ($orderId <= 0 || $perfumeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order or product']);
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit;
    }
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Please write a comment']);
        exit;
    }

    $db = Database::getInstance();

    // Verify the order belongs to this customer
    $order = $db->fetch("
        SELECT o.order_id, o.customer_id, o.order_status, o.o_created_at as created_at
        FROM orders o
        WHERE o.order_id = ?
    ", [$orderId]);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    if ($order['customer_id'] != $customerId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Check if order is completed
    if (strtolower($order['order_status']) !== 'completed') {
        echo json_encode(['success' => false, 'message' => 'Only completed orders can be reviewed']);
        exit;
    }

    // Check 7-day limit from order creation date
    $completedDate = new DateTime($order['created_at']);
    $now = new DateTime();
    $daysDiff = $now->diff($completedDate)->days;
    if ($daysDiff > 7) {
        echo json_encode(['success' => false, 'message' => 'Review period has expired (7 days limit)']);
        exit;
    }

    // Verify the product is in the order
    $orderProducts = $db->getOrderProducts($orderId);
    $productFound = false;
    foreach ($orderProducts as $product) {
        if (isset($product['perfume_id']) && $product['perfume_id'] == $perfumeId) {
            $productFound = true;
            break;
        }
    }
    if (!$productFound) {
        echo json_encode(['success' => false, 'message' => 'Product not found in this order']);
        exit;
    }

    // Check if already reviewed
    if ($db->hasReviewed($orderId, $customerId, $perfumeId)) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        exit;
    }

    // Add review record
    $result = $db->addReview($orderId, $customerId, $perfumeId, $rating, $comment);
    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    $reviewId = $result['review_id'] ?? null;

    // ✅ Handle optional image upload
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/reviews/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES['review_image']['tmp_name'];
        $originalName = basename($_FILES['review_image']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newFileName = uniqid('review_', true) . '.' . $ext;
        $filePath = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $filePath)) {
            // Save image info in DB
            $conn = $db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO images (perfume_id, order_id, customer_id, file_name, file_path, uploaded_at, image_type)
                VALUES (?, ?, ?, ?, ?, NOW(), 'review')
            ");
            $stmt->execute([$perfumeId, $orderId, $customerId, $newFileName, 'uploads/reviews/' . $newFileName]);
        } else {
            error_log("❌ Failed to move uploaded image");
        }
    }

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);

} catch (Exception $e) {
    error_log("Submit review error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
