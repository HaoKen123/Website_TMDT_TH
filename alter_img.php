<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE `products` MODIFY `image_url` MEDIUMTEXT NOT NULL");
    echo "Successfully altered image_url to MEDIUMTEXT!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
