<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        // Format product data for frontend
        $formatted = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'old_price' => $product['old_price'] ?? null,
            'image_url' => $product['image_url'] ?: 'https://placehold.co/400x400/22B573/FFFFFF?text=Product',
            'description' => $product['description'] ?? 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền. Kích thước chuẩn, chất liệu bền bỉ, màu sắc tươi sáng theo phong cách Minecraft đặc trưng. Phù hợp cho người chơi muốn sở hữu đồ周边 chính hãng.'
        ];
        echo json_encode(['success' => true, 'product' => $formatted]);
    } else {
        // If product not found, use mock data for demo
        $mockProduct = [
            'id' => $id,
            'name' => 'Minecraft Demo Product ' . $id,
            'price' => 29.99,
            'old_price' => 39.99,
            'image_url' => 'https://placehold.co/400x400/5DD9D9/FFFFFF?text=Product+' . $id,
            'description' => 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền. Kích thước chuẩn, chất liệu bền bỉ, màu sắc tươi sáng theo phong cách Minecraft đặc trưng. Phù hợp cho người chơi muốn sở hữu đồ周边 chính hãng.'
        ];
        echo json_encode(['success' => true, 'product' => $mockProduct]);
    }
} catch (Exception $e) {
    // Database unavailable - use mock data
    $mockProduct = [
        'id' => $id,
        'name' => 'Minecraft Product ' . $id,
        'price' => 29.99,
        'image_url' => 'https://placehold.co/400x400/22B573/FFFFFF?text=Product',
        'description' => 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền. Kích thước chuẩn, chất liệu bền bỉ, màu sắc tươi sáng theo phong cách Minecraft đặc trưng. Phù hợp cho người chơi muốn sở hữu đồ周边 chính hãng.'
    ];
    echo json_encode(['success' => true, 'product' => $mockProduct]);
}
