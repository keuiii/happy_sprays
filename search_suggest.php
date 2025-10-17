<?php
session_start();
require_once 'classes/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['q']) || trim($_GET['q']) === '') {
        echo json_encode([]);
        exit;
    }

    $db = Database::getInstance();
    $searchQuery = trim($_GET['q']);
    $searchLike = '%' . $searchQuery . '%';

    $results = $db->select("
        SELECT 
            perfume_id AS id, 
            perfume_name AS name, 
            perfume_price AS price, 
            image
        FROM perfumes
        WHERE perfume_name LIKE ? 
           OR perfume_brand LIKE ? 
           OR perfume_desc LIKE ?
        ORDER BY perfume_name ASC
        LIMIT 8
    ", [$searchLike, $searchLike, $searchLike]);

    if (!$results || !is_array($results)) {
        $results = [];
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Search suggest error: ' . $e->getMessage());
    echo json_encode([]);
}
