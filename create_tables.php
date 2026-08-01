<?php
require_once 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review (product_id, user_id)
    )");
    
    echo "<h2 style='color: #10b981; text-align: center;'>✓ Table 'product_reviews' created successfully</h2>";
    echo "<p style='text-align: center; color: #6b7280;'>You can now add customer reviews on product detail pages</p>";
} catch (PDOException $e) {
    echo "<p style='color: #dc2626; text-align: center;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_id INT NOT NULL,
        quantity INT DEFAULT 0,
        label_vn VARCHAR(50) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (size_id) REFERENCES sizes(size_id),
        UNIQUE KEY unique_product_size (product_id, size_id)
    )");
    
    echo "<p style='text-align: center; color: #6b7280;'>✓ Table 'product_sizes' also created successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color: #f59e0b; text-align: center;'>⚠ Warning: 'product_sizes' table already exists or failed</p>";
}
?>
