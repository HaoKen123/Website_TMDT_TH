<?php
require 'db.php';
$stmt = $pdo->query('SELECT category, name FROM products');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($products as $p) {
    echo $p['category'] . ' | ' . $p['name'] . "\n";
}
?>
