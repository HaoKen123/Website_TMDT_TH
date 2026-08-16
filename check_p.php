<?php
require_once 'db.php';
try {
    // Check column type of image_url
    $stmtCol = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    $col = $stmtCol->fetch();
    echo "Column type: " . print_r($col, true) . "\n";

    $stmt = $pdo->query("SELECT id, name, image_url, status FROM products ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "ID: {$r['id']} | Name: {$r['name']} | Status: {$r['status']} | Img: " . substr($r['image_url'], 0, 80) . " (len: " . strlen($r['image_url']) . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
