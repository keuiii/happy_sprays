<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'classes/database.php';

// Fallback PDO connection (only if $db handle not available)
try {
    $dsn = "mysql:host=mysql.hostinger.com;dbname=u425676266_happy_sprays;charset=utf8mb4";
    $pdo = new PDO($dsn, 'u425676266_jows', 'GIAjanda9', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'db_connection_failed']);
    exit;
}

// Use $pdo for queries
try {
    $stmt = $pdo->prepare("
    SELECT 
        p.perfume_id AS id,
        p.perfume_name AS name,
        p.perfume_brand AS brand,
        p.perfume_price AS price,
        p.perfume_ml AS ml,
        p.sex,
        p.perfume_desc AS description,
        i.file_path AS image,
        COALESCE(SUM(oi.order_quantity), 0) AS total_sold
    FROM perfumes p
    LEFT JOIN order_items oi ON p.perfume_id = oi.perfume_id
    LEFT JOIN images i ON p.perfume_id = i.perfume_id AND i.image_type = 'perfume'
    WHERE p.perfume_price > 0
    GROUP BY p.perfume_id
    ORDER BY total_sold DESC
    LIMIT 4
");

    $stmt->execute();
    $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // cast price to float
    foreach ($perfumes as &$p) {
        $p['price'] = isset($p['price']) ? (float)$p['price'] : 0;
        $p['image'] = $p['image'] ?? '';
    }

    echo json_encode($perfumes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
