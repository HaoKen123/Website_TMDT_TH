<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $old_price = empty($_POST['old_price']) ? null : floatval($_POST['old_price']);
    $badge = empty($_POST['badge']) ? null : $_POST['badge'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url'] ?? '');

    // Handle File Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/products/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_url = 'uploads/products/' . $newFileName;
            } else {
                $error = 'Có lỗi xảy ra khi tải file ảnh lên máy chủ.';
            }
        } else {
            $error = 'Định dạng file ảnh không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP, GIF).';
        }
    }

    if (empty($image_url) && empty($error)) {
        $error = 'Vui lòng tải ảnh lên từ máy tính HOẶC dán đường dẫn (URL) hình ảnh!';
    }

    if (empty($error)) {
        $stock = intval($_POST['stock'] ?? 50);
        $status = intval($_POST['status'] ?? 1);
        $stmt = $pdo->prepare("INSERT INTO products (category, name, image_url, price, old_price, badge, description, stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category, $name, $image_url, $price, $old_price, $badge, $description, $stock, $status]);
        header('Location: products.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Sản Phẩm Mới - Admin PixelGear</title>
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
            <li><a href="coupons.php"><i class="fas fa-ticket-alt"></i> Mã giảm giá & Email</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-plus-circle"></i> Thêm Sản Phẩm Mới</h1>
            <a href="products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #f87171;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container" style="background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
            <form method="POST" enctype="multipart/form-data">
                <label style="font-weight:600;">Tên sản phẩm</label>
                <input type="text" name="name" required placeholder="Ví dụ: Áo Thun Minecraft Enderman Eyes">

                <label style="font-weight:600;">Danh mục chi tiết</label>
                <select name="category" required style="padding:10px; font-size:15px;">
                    <optgroup label="--- QUẦN ÁO (CLOTHING) ---">
                        <option value="tshirts">Áo Thun & Hoodies (T-Shirts & Hoodies)</option>
                        <option value="cosplay">Trang Phục Cosplay & Outfit</option>
                        <option value="clothing">Quần Áo Khác</option>
                    </optgroup>
                    <optgroup label="--- PHỤ KIỆN (ACCESSORIES) ---">
                        <option value="hats">Nón & Phụ Kiện Thời Trang</option>
                        <option value="keychains">Móc Khóa & Trang Sức</option>
                        <option value="accessories">Phụ Kiện Khác</option>
                    </optgroup>
                    <optgroup label="--- ĐỒ CHƠI & DECOR (TOYS & GAMES) ---">
                        <option value="toys_models">Đồ Chơi & Mô Hình Sưu Tầm</option>
                        <option value="decor">Đèn & Đồ Trang Trí Phòng Game</option>
                        <option value="toys">Đồ Chơi Khác</option>
                    </optgroup>
                </select>

                <div style="background:#f8fafc; padding:20px; border-radius:8px; margin:20px 0; border:1px dashed #cbd5e1;">
                    <h4 style="margin-top:0; color:#334155;"><i class="fas fa-image"></i> Hình ảnh sản phẩm (Chọn 1 trong 2 cách):</h4>
                    
                    <label style="font-weight:600; color:#0f172a;"><i class="fas fa-folder-open"></i> Cách 1: Tải ảnh từ máy tính (Browse File)</label>
                    <input type="file" name="image_file" accept="image/*" style="margin-bottom:15px; background:#fff; padding:8px; border:1px solid #cbd5e1; border-radius:6px; width:100%;">

                    <label style="font-weight:600; color:#0f172a;"><i class="fas fa-link"></i> Cách 2: Hoặc Dán Link URL Hình Ảnh</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-top: 15px;">
                    <div>
                        <label style="font-weight:600;">Giá bán ($ USD)</label>
                        <input type="number" step="0.01" id="priceInput" name="price" required placeholder="29.95">
                        <small id="priceVndHint" style="color:#2563eb; font-weight:600; display:block; margin-top:4px;">Tương đương: 0 VNĐ</small>
                    </div>

                    <div>
                        <label style="font-weight:600;">Giá cũ ($ USD) - <span style="font-weight:normal; color:#64748b;">(Nếu có)</span></label>
                        <input type="number" step="0.01" id="oldPriceInput" name="old_price" placeholder="39.95">
                        <small id="oldPriceVndHint" style="color:#64748b; display:block; margin-top:4px;">Tương đương: 0 VNĐ</small>
                    </div>

                    <div>
                        <label style="font-weight:600;">Số lượng tồn kho</label>
                        <input type="number" name="stock" value="50" min="0" required placeholder="50">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:15px;">
                    <div>
                        <label style="font-weight:600;">Nhãn dán nổi bật (Badge)</label>
                        <select name="badge">
                            <option value="">Không có nhãn</option>
                            <option value="Mới">Hàng Mới (Mới)</option>
                            <option value="Giảm giá">Đang Giảm Giá (Giảm giá)</option>
                            <option value="Hot">Bán Chạy (Hot)</option>
                            <option value="Best Seller">Best Seller</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;">Trạng thái sản phẩm</label>
                        <select name="status">
                            <option value="1" selected>Hiển thị trên shop</option>
                            <option value="0">Ẩn khỏi shop</option>
                        </select>
                    </div>
                </div>

                <label style="font-weight:600;">Mô tả sản phẩm</label>
                <textarea name="description" rows="4" placeholder="Nhập chi tiết thông tin sản phẩm..."></textarea>

                <button type="submit" class="btn btn-success" style="width:100%; margin-top:15px; padding:12px; font-size:16px; font-weight:600;"><i class="fas fa-save"></i> LƯU SẢN PHẨM MỚI</button>
            </form>
        </div>
    </div>

    <script>
        const EXCHANGE_RATE = 25400;
        const priceInput = document.getElementById('priceInput');
        const priceVndHint = document.getElementById('priceVndHint');
        const oldPriceInput = document.getElementById('oldPriceInput');
        const oldPriceVndHint = document.getElementById('oldPriceVndHint');

        function formatVnd(usd) {
            if (isNaN(usd) || usd <= 0) return '0 VNĐ';
            return (Math.round(usd * EXCHANGE_RATE)).toLocaleString('vi-VN') + ' VNĐ';
        }

        priceInput.addEventListener('input', () => {
            const usd = parseFloat(priceInput.value);
            priceVndHint.textContent = 'Tương đương: ' + formatVnd(usd);
        });

        oldPriceInput.addEventListener('input', () => {
            const usd = parseFloat(oldPriceInput.value);
            oldPriceVndHint.textContent = 'Tương đương: ' + formatVnd(usd);
        });
    </script>
</body>
</html>
