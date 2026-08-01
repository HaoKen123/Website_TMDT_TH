<?php
// Sample size data for clothing products
// Run this after the tables are created
try {
    $pdo->exec("INSERT INTO product_sizes (product_id, size_id, quantity, label_vn) 
                SELECT id, 1, 50, 'S' FROM products WHERE category = 'clothing' AND id NOT IN (
                    SELECT product_id FROM product_sizes LIMIT 1
                )");
    
    echo "<h2 style='color: #10b981; text-align: center;'>✓ Sample sizes inserted for clothing products</h2>";
    echo "<p style='text-align: center; color: #6b7280;'>Sizes S will be available for all clothing products</p>";
} catch (PDOException $e) {
    echo "<p style='color: #f59e0b; text-align: center;'>⚠ Insert failed or table already has data</p>";
}
?>
