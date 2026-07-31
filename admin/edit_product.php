<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $image_url = $_POST['image_url'];
    $price = $_POST['price'];
    $old_price = empty($_POST['old_price']) ? null : $_POST['old_price'];
    $badge = empty($_POST['badge']) ? null : $_POST['badge'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE products SET category=?, name=?, image_url=?, price=?, old_price=?, badge=?, description=? WHERE id=?");
    $stmt->execute([$category, $name, $image_url, $price, $old_price, $badge, $description, $id]);
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Sản Phẩm - Admin</title>
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
            <h1>Sửa Sản Phẩm #<?php echo $product['id']; ?></h1>
            <a href="products.php" class="btn btn-primary">Quay lại</a>
        </div>

        <div class="form-container">
            <form method="POST">
                <label>Tên sản phẩm</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

                <label>Danh mục</label>
                <select name="category" required>
                    <option value="clothing" <?php echo ($product['category'] == 'clothing') ? 'selected' : ''; ?>>Quần Áo (Clothing)</option>
                    <option value="accessories" <?php echo ($product['category'] == 'accessories') ? 'selected' : ''; ?>>Phụ Kiện (Accessories)</option>
                    <option value="toys" <?php echo ($product['category'] == 'toys') ? 'selected' : ''; ?>>Đồ Chơi & Game (Toys & Games)</option>
                </select>

                <label>Link hình ảnh (URL)</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" required>

                <label>Giá bán ($)</label>
                <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>

                <label>Giá cũ ($)</label>
                <input type="number" step="0.01" name="old_price" value="<?php echo $product['old_price']; ?>">

                <label>Nhãn dán nổi bật (Badge)</label>
                <select name="badge">
                    <option value="" <?php echo ($product['badge'] == '') ? 'selected' : ''; ?>>Không có nhãn</option>
                    <option value="Mới" <?php echo ($product['badge'] == 'Mới') ? 'selected' : ''; ?>>Hàng Mới (Mới)</option>
                    <option value="Giảm giá" <?php echo ($product['badge'] == 'Giảm giá') ? 'selected' : ''; ?>>Đang Giảm Giá (Giảm giá)</option>
                    <option value="Hot" <?php echo ($product['badge'] == 'Hot') ? 'selected' : ''; ?>>Bán Chạy (Hot)</option>
                </select>

                <label>Mô tả ngắn</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px; background:#ffaa00; color:black;">LƯU THAY ĐỔI</button>
            </form>
        </div>
    </div>
</body>
</html>
