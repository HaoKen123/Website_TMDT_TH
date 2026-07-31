<?php
require_once 'd:/Website/db.php';
try {
    $sql = file_get_contents('d:/Website/seed_real_products.sql');
    $pdo->exec($sql);
    echo "Import success!";
} catch (Exception $e) {
    echo "Import failed: " . $e->getMessage();
}
