<?php
session_start();
require_once 'db.php';

echo "=== DATABASE CHECK ===\n\n";

// Test connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Total products: " . $result['count'] . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Show first 3 products
try {
    $stmt = $pdo->query("SELECT id, name, price, image_url FROM products LIMIT 3");
    $products = $stmt->fetchAll();
    echo "First 3 products:\n";
    foreach ($products as $p) {
        echo "- ID: {$p['id']}, Name: {$p['name']}, Price: {$p['price']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>