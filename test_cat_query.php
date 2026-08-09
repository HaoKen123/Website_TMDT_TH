<?php
require_once 'db.php';
try {
    $rows = $pdo->query("SELECT * FROM categories")->fetchAll();
    echo "Categories count: " . count($rows) . "\n";
    print_r($rows);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
