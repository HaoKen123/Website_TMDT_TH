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

    // Handle Base64 Pasted Image (Ctrl + V)
    if (!empty($_POST['image_base64']) && strpos($_POST['image_base64'], 'data:image/') === 0) {
        $base64Str = $_POST['image_base64'];
        $parts = explode(',', $base64Str);
        if (count($parts) === 2) {
            $rawBase64 = $parts[1];
            $decoded = base64_decode($rawBase64);
            if ($decoded !== false) {
                $ext = 'png';
                if (strpos($parts[0], 'jpeg') !== false || strpos($parts[0], 'jpg') !== false) $ext = 'jpg';
                elseif (strpos($parts[0], 'webp') !== false) $ext = 'webp';
                elseif (strpos($parts[0], 'gif') !== false) $ext = 'gif';

                $newFileName = 'prod_paste_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $uploadFileDir = dirname(__DIR__) . '/uploads/products/';
                if (!is_dir($uploadFileDir)) {
                    @mkdir($uploadFileDir, 0777, true);
                }
                
                $saved = @file_put_contents($uploadFileDir . $newFileName, $decoded);
                if ($saved !== false) {
                    $image_url = 'uploads/products/' . $newFileName;
                } else {
                    // Fallback to direct Base64 Data URI if file write is restricted
                    $image_url = $base64Str;
                }
            }
        }
    }

    // Handle File Upload if provided
    if (empty($image_url) && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $uploadFileDir = dirname(__DIR__) . '/uploads/products/';
            
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0777, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;

            if (@move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_url = 'uploads/products/' . $newFileName;
            } else {
                // If move failed, read and save base64
                $fileContent = @file_get_contents($fileTmpPath);
                if ($fileContent) {
                    $image_url = 'data:image/' . $fileExtension . ';base64,' . base64_encode($fileContent);
                } else {
                    $error = 'Có lỗi xảy ra khi tải file ảnh lên máy chủ.';
                }
            }
        } else {
            $error = 'Định dạng file ảnh không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP, GIF).';
        }
    }

    if (empty($image_url) && empty($error)) {
        $error = 'Vui lòng dán ảnh (Ctrl+V), tải file ảnh từ máy tính HOẶC dán đường dẫn (URL) hình ảnh!';
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
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
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

                <label style="font-weight:600;">Danh mục sản phẩm</label>
                <?php
                $db_categories = [];
                try {
                    $db_categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}

                if (empty($db_categories)) {
                    $db_categories = [
                        ['slug' => 'clothing', 'name' => 'Quần áo & Hoodies'],
                        ['slug' => 'accessories', 'name' => 'Phụ kiện Minecraft'],
                        ['slug' => 'toys', 'name' => 'Đồ chơi & Gấu bông'],
                        ['slug' => 'decor', 'name' => 'Đèn & Vật dụng']
                    ];
                }
                ?>
                <select name="category" required style="padding:12px; font-size:15px; border-radius:6px; border:1px solid #cbd5e1; width:100%; margin-bottom:15px;">
                    <?php foreach ($db_categories as $catItem): ?>
                        <option value="<?php echo htmlspecialchars($catItem['slug']); ?>">
                            <?php echo htmlspecialchars($catItem['name']); ?> (Mã: <?php echo htmlspecialchars($catItem['slug']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="background:#f8fafc; padding:20px; border-radius:8px; margin:20px 0; border:2px dashed #94a3b8; text-align:center;" id="pasteDropArea">
                    <h4 style="margin-top:0; color:#1e293b; font-size:16px;">
                        <i class="fas fa-magic" style="color:#15803d;"></i> Hình ảnh sản phẩm (Hỗ trợ Dán phím tắt <strong>Ctrl + V</strong> siêu tốc)
                    </h4>

                    <!-- Paste & Drop Target Zone -->
                    <div id="pasteIndicator" style="padding:15px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; margin-bottom:12px; cursor:pointer;" onclick="document.getElementById('imageFileInput').click()">
                        <div style="font-size:32px; color:#15803d; margin-bottom:6px;"><i class="fas fa-clipboard-check"></i></div>
                        <div style="font-weight:700; color:#0f172a; font-size:15px;">
                            Bấm <kbd style="background:#e2e8f0; color:#0f172a; padding:3px 8px; border-radius:4px; border:1px solid #94a3b8; font-family:monospace; font-weight:800;">Ctrl + V</kbd> bất kỳ đâu để DÁN ẢNH trực tiếp
                        </div>
                        <div style="font-size:13px; color:#64748b; margin-top:4px;">
                            Hoặc kéo thả file ảnh vào đây / bấm để chọn ảnh từ máy tính
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="file" id="imageFileInput" name="image_file" accept="image/*" style="display:none;">
                    <input type="hidden" id="imageBase64" name="image_base64">

                    <!-- Image Preview Container -->
                    <div id="imagePreviewBox" style="display:none; margin:15px auto; max-width:320px; background:#fff; padding:12px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.08); border:1px solid #e2e8f0;">
                        <div style="font-size:13px; color:#15803d; font-weight:700; margin-bottom:8px;" id="previewStatus">
                            <i class="fas fa-check-circle"></i> Đã chọn ảnh thành công!
                        </div>
                        <img id="previewImg" src="" style="max-width:100%; height:180px; object-fit:contain; border-radius:6px; border:1px solid #cbd5e1; display:block; margin:0 auto;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; font-size:12px; color:#64748b;">
                            <span id="previewInfo"></span>
                            <button type="button" onclick="clearPastedImage()" style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:4px 8px; border-radius:4px; cursor:pointer; font-weight:600;">
                                <i class="fas fa-trash"></i> Xóa ảnh
                            </button>
                        </div>
                    </div>

                    <div style="margin-top:12px; text-align:left;">
                        <label style="font-weight:600; color:#334155; font-size:13px;"><i class="fas fa-link"></i> Hoặc nhập đường dẫn (URL) hình ảnh Online:</label>
                        <input type="text" id="imageUrlInput" name="image_url" placeholder="https://example.com/image.jpg" style="margin-top:4px;">
                    </div>
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

        // ==========================================
        // CTRL + V (PASTE) & DRAG DROP IMAGE HANDLER
        // ==========================================
        const fileInput = document.getElementById('imageFileInput');
        const base64Input = document.getElementById('imageBase64');
        const urlInput = document.getElementById('imageUrlInput');
        const previewBox = document.getElementById('imagePreviewBox');
        const previewImg = document.getElementById('previewImg');
        const previewStatus = document.getElementById('previewStatus');
        const previewInfo = document.getElementById('previewInfo');
        const dropArea = document.getElementById('pasteDropArea');

        function handleImageFile(file, sourceLabel = 'Tải lên từ máy') {
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const base64 = e.target.result;
                base64Input.value = base64;
                previewImg.src = base64;
                previewBox.style.display = 'block';
                previewStatus.innerHTML = `<i class="fas fa-check-circle"></i> ${sourceLabel}`;
                previewInfo.textContent = `${(file.size / 1024).toFixed(1)} KB (${file.type.split('/')[1].toUpperCase()})`;

                // Assign to file input if possible
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                } catch (err) {}
            };
            reader.readAsDataURL(file);
        }

        function clearPastedImage() {
            fileInput.value = '';
            base64Input.value = '';
            previewImg.src = '';
            previewBox.style.display = 'none';
        }

        // Global Paste Listener (Ctrl + V anywhere on page)
        window.addEventListener('paste', (e) => {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            let imageFound = false;

            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    handleImageFile(blob, '📋 Đã DÁN ẢNH thành công từ Clipboard (Ctrl + V)!');
                    imageFound = true;
                    e.preventDefault();
                    break;
                }
            }

            // If text pasted into URL input, update preview
            if (!imageFound) {
                const pastedText = e.clipboardData.getData('text');
                if (pastedText && (pastedText.startsWith('http://') || pastedText.startsWith('https://')) && /\.(jpg|jpeg|png|webp|gif)/i.test(pastedText)) {
                    previewImg.src = pastedText;
                    previewBox.style.display = 'block';
                    previewStatus.innerHTML = '<i class="fas fa-link"></i> Đã nạp ảnh từ đường dẫn URL!';
                    previewInfo.textContent = 'Online Image Link';
                }
            }
        });

        // File Input Change
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                handleImageFile(fileInput.files[0], '📁 Đã chọn file ảnh từ máy tính!');
            }
        });

        // URL Input Input
        urlInput.addEventListener('input', () => {
            const val = urlInput.value.trim();
            if (val && (val.startsWith('http://') || val.startsWith('https://'))) {
                previewImg.src = val;
                previewBox.style.display = 'block';
                previewStatus.innerHTML = '<i class="fas fa-link"></i> Xem trước từ Link URL:';
                previewInfo.textContent = 'URL Image';
            }
        });

        // Drag & Drop
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropArea.style.borderColor = '#15803d';
                dropArea.style.background = '#f0fdf4';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropArea.style.borderColor = '#94a3b8';
                dropArea.style.background = '#f8fafc';
            }, false);
        });

        dropArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                handleImageFile(files[0], '📥 Đã thả file ảnh vào thành công!');
            }
        });
    </script>
</body>
</html>
