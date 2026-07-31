<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $image_url = $_POST['image_url'];
    $price = $_POST['price'];
    $old_price = empty($_POST['old_price']) ? null : $_POST['old_price'];
    $badge = empty($_POST['badge']) ? null : $_POST['badge'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO products (category, name, image_url, price, old_price, badge, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$category, $name, $image_url, $price, $old_price, $badge, $description]);
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Sản Phẩm - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Thêm Sản Phẩm Mới</h1>
            <a href="products.php" class="btn btn-primary">Quay lại</a>
        </div>

        <div class="form-container">
            <form method="POST">
                <label>Tên sản phẩm</label>
                <input type="text" name="name" required>

                <label>Danh mục</label>
                <select name="category" required>
                    <option value="clothing">Quần Áo (Clothing)</option>
                    <option value="accessories">Phụ Kiện (Accessories)</option>
                    <option value="toys">Đồ Chơi & Game (Toys & Games)</option>
                </select>

                <label>Link hình ảnh (URL)</label>
                <input type="text" name="image_url" required>

                <label>Giá bán ($)</label>
                <input type="number" step="0.01" name="price" required>

                <label>Giá cũ ($) - Để trống nếu không giảm giá</label>
                <input type="number" step="0.01" name="old_price">

                <label>Nhãn dán nổi bật (Badge)</label>
                <select name="badge">
                    <option value="">Không có nhãn</option>
                    <option value="Mới">Hàng Mới (Mới)</option>
                    <option value="Giảm giá">Đang Giảm Giá (Giảm giá)</option>
                    <option value="Hot">Bán Chạy (Hot)</option>
                </select>

                <label>Mô tả ngắn</label>
                <textarea name="description" rows="4"></textarea>

                <button type="submit" class="btn btn-success" style="width:100%; margin-top:10px;">LƯU SẢN PHẨM</button>
            </form>
        </div>
    </div>
</body>
</html>
