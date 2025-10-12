<?php
header('Content-Type: application/json');

require_once 'classes/database.php';
$db = Database::getInstance();

// Get product ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Query product details
// ⚠️ Kung wala kang column na `description`, palitan mo ng `scent AS description`
$product = $db->fetch("SELECT id, name, price, description, image FROM perfumes WHERE id = ? LIMIT 1", [$id]);

if ($product) {
    echo json_encode([
        "error" => false,
        "id" => $product['id'],
        "name" => $product['name'],
        "price" => $product['price'],
        "description" => $product['description'],
        "image" => $product['image']
    ]);
} else {
    echo json_encode([
        "error" => true,
        "message" => "Product not found"
    ]);
}
?>