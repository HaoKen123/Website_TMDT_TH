<?php
// Create product_reviews table for product detail page
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    
    echo "✓ Table 'product_reviews' created successfully\n";
} catch (PDOException $e) {
    echo "✗ Error creating 'product_reviews' table: " . $e->getMessage() . "\n";
}

// Create product_sizes table for clothing size selection
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    
    echo "✓ Table 'product_sizes' created successfully\n";
} catch (PDOException $e) {
    echo "✗ Error creating 'product_sizes' table: " . $e->getMessage() . "\n";
}

// Insert sample product sizes for clothing products
try {
    $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_id, quantity, label_vn) 
                          SELECT id, 1, 50, 'S' FROM products WHERE category = 'clothing' AND id NOT IN (
                              SELECT product_id FROM product_sizes LIMIT 1
                          )");
    $stmt->execute();
    echo "✓ Sample sizes inserted for clothing products\n";
} catch (PDOException $e) {
    echo "✗ Error inserting sample sizes: " . $e->getMessage() . "\n";
}

echo "\nSetup completed!\n";
?>
