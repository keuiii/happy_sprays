<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

$searchQuery = trim($_GET['q']);
$searchLike = "%" . $searchQuery . "%";

try {
    $results = $db->select(
        "SELECT perfume_id as id, perfume_name as name, perfume_price as price, image 
         FROM perfumes 
         WHERE perfume_name LIKE ? OR perfume_brand LIKE ? OR perfume_descr LIKE ?
         LIMIT 5",
        [$searchLike, $searchLike, $searchLike]
    );
    
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
?>