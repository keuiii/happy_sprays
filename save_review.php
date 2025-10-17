<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance()->getConnection();

header('Content-Type: application/json');

try {
    // ✅ Use your class/session logic
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
    } elseif (isset($_SESSION['admin_id'])) {
        $customer_id = $_SESSION['admin_id'];
    } else {
        throw new Exception('You must be logged in to submit a review.');
    }

    $order_id   = intval($_POST['order_id']);
    $perfume_id = intval($_POST['perfume_id']);
    $rating     = intval($_POST['rating']);
    $comment    = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        throw new Exception('Please select a valid rating.');
    }

    // Insert review
    $stmt = $db->prepare("
        INSERT INTO reviews (order_id, customer_id, perfume_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$order_id, $customer_id, $perfume_id, $rating, $comment]);

    $review_id = $db->lastInsertId();

    // Handle multiple image uploads
    if (!empty($_FILES['review_images']['name'][0])) {
        $targetDir = 'uploads/reviews/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        foreach ($_FILES['review_images']['tmp_name'] as $index => $tmpName) {
            $fileName = time() . '_' . basename($_FILES['review_images']['name'][$index]);
            $targetPath = $targetDir . $fileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $stmtImg = $db->prepare("
                    INSERT INTO images (perfume_id, order_id, customer_id, file_name, file_path, image_type)
                    VALUES (?, ?, ?, ?, ?, 'review')
                ");
                $stmtImg->execute([$perfume_id, $order_id, $customer_id, $fileName, $targetPath]);
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
