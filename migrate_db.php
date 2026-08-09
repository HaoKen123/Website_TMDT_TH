<?php
require_once 'db.php';

echo "<h2>Bắt đầu nâng cấp Cơ sở dữ liệu (Database Migration)...</h2>";

try {
    // 1. Nâng cấp bảng users
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 0: Blocked'");
        echo "<p style='color:green;'>✓ Đã thêm cột 'status' vào bảng users</p>";
    }
    
    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'customer' COMMENT 'customer, staff, admin'");
        echo "<p style='color:green;'>✓ Đã thêm cột 'role' vào bảng users</p>";
    }

    // 2. Nâng cấp bảng products
    $prodColumns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('stock', $prodColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 50");
        echo "<p style='color:green;'>✓ Đã thêm cột 'stock' vào bảng products</p>";
    }

    if (!in_array('status', $prodColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Hiển thị, 0: Ẩn'");
        echo "<p style='color:green;'>✓ Đã thêm cột 'status' vào bảng products</p>";
    }

    // 3. Tạo bảng categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p style='color:green;'>✓ Đã khởi tạo bảng 'categories'</p>";

    // Chèn danh mục mặc định nếu rỗng
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
        $pdo->exec("INSERT INTO categories (name, slug) VALUES 
            ('Quần áo & Hoodies', 'clothing'),
            ('Phụ kiện Minecraft', 'accessories'),
            ('Đồ chơi & Gấu bông', 'toys'),
            ('Đèn & Vật dụng', 'decor')
        ");
        echo "<p style='color:green;'>✓ Đã thêm dữ liệu danh mục mặc định</p>";
    }

    // 4. Tạo bảng comments
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating INT DEFAULT 5,
        comment TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p style='color:green;'>✓ Đã khởi tạo bảng 'comments'</p>";

    // Thêm bình luận mẫu nếu chưa có
    $commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    if ($commentCount == 0) {
        $pdo->exec("INSERT INTO comments (product_id, user_name, rating, comment, status) VALUES 
            (1, 'Nguyễn Văn An', 5, 'Mũ lưỡi trai Minecraft rất đẹp, vải mịn, thêu sắc nét!', 'approved'),
            (2, 'Trần Thị Bích', 5, 'Áo thun cotton thoáng mát, in hình Enderman cực chất.', 'approved'),
            (5, 'Lê Hoàng Cường', 4, 'Balo chắc chắn, chống nước tốt, bé nhà mình rất thích.', 'approved')
        ");
        echo "<p style='color:green;'>✓ Đã thêm dữ liệu bình luận mẫu</p>";
    }

    // 5. Tạo bảng shipping_fees
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipping_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        province VARCHAR(255) NOT NULL UNIQUE,
        fee DECIMAL(10,2) NOT NULL DEFAULT 30000.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p style='color:green;'>✓ Đã khởi tạo bảng 'shipping_fees'</p>";

    $shipCount = $pdo->query("SELECT COUNT(*) FROM shipping_fees")->fetchColumn();
    if ($shipCount == 0) {
        $pdo->exec("INSERT INTO shipping_fees (province, fee) VALUES 
            ('Hà Nội', 20000),
            ('TP. Hồ Chí Minh', 25000),
            ('Đà Nẵng', 30000),
            ('Hải Phòng', 25000),
            ('Cần Thơ', 30000),
            ('Tỉnh/Thành khác', 35000)
        ");
        echo "<p style='color:green;'>✓ Đã thêm dữ liệu phí vận chuyển mặc định</p>";
    }

    echo "<h3 style='color:blue;'>🎉 Nâng cấp CSDL hoàn tất 100%!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Lỗi nâng cấp CSDL: " . $e->getMessage() . "</h3>";
}
?>
