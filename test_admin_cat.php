<?php
require_once 'db.php';
try {
    $categories = $pdo->query("
        SELECT c.*, 
        (
            SELECT COUNT(*) FROM products p 
            WHERE p.category = c.slug 
            OR (c.slug = 'clothing' AND p.category IN ('clothing', 'tshirts', 'cosplay'))
            OR (c.slug = 'accessories' AND p.category IN ('accessories', 'hats', 'keychains'))
            OR (c.slug = 'toys' AND p.category IN ('toys', 'toys_models', 'plushies'))
            OR (c.slug = 'decor' AND p.category IN ('decor', 'lights'))
        ) as product_count 
        FROM categories c 
        ORDER BY c.id ASC
    ")->fetchAll();
    echo "Count: " . count($categories) . "\n";
    print_r($categories);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
