<?php
require_once 'd:/Website/db.php';
$pdo->exec("SET NAMES 'utf8mb4'");

$more_products = [
    [
        'category' => 'toys',
        'name' => 'Pokémon Pikachu Interactive Figure',
        'image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '34.95',
        'old_price' => NULL,
        'badge' => 'Hot',
        'description' => 'Mô Hình Pikachu Tương Tác Đèn & Âm Thanh. Sản phẩm chính hãng Pokémon, chi tiết sắc nét, có đèn sáng ở má.'
    ],
    [
        'category' => 'accessories',
        'name' => 'Poké Ball 3D Night Light',
        'image_url' => 'https://images.unsplash.com/photo-1605901302636-61845112df38?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '32.95',
        'old_price' => '45.00',
        'badge' => 'Giảm giá',
        'description' => 'Đèn Cầu Thủy Tinh 3D Poké Ball Đế Xoay LED 7 Màu. Quà tặng tuyệt vời cho fan Pokémon.'
    ],
    [
        'category' => 'toys',
        'name' => 'The Legend of Zelda Master Sword Replica',
        'image_url' => 'https://images.unsplash.com/photo-1593003058933-72ffb853549f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '79.95',
        'old_price' => NULL,
        'badge' => 'Best Seller',
        'description' => 'Bộ Mô Hình Kiếm Master Sword & Khiên Hylian. Tỉ lệ 1:1, chi tiết sơn bóng cao cấp, có kèm đế trưng bày.'
    ],
    [
        'category' => 'toys',
        'name' => 'Genshin Impact Paimon Figure 1/7',
        'image_url' => 'https://images.unsplash.com/photo-1678516104278-df1c536f9037?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '120.00',
        'old_price' => NULL,
        'badge' => 'Mới',
        'description' => 'Mô hình Paimon Genshin Impact tỉ lệ 1/7 cao cấp, chi tiết sống động, màu sắc chuẩn như trong game.'
    ],
    [
        'category' => 'toys',
        'name' => 'Valorant Karambit Prime 2.0 Replica',
        'image_url' => 'https://images.unsplash.com/photo-1628126235206-5260b9ea6441?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '25.95',
        'old_price' => '35.00',
        'badge' => 'Giảm giá',
        'description' => 'Mô hình dao Karambit Prime 2.0 bằng kim loại nguyên khối, sơn tĩnh điện, an toàn không sắc bén.'
    ],
    [
        'category' => 'toys',
        'name' => 'God of War Leviathan Axe Model',
        'image_url' => 'https://images.unsplash.com/photo-1607513746994-51f730a44832?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '150.00',
        'old_price' => NULL,
        'badge' => 'Hot',
        'description' => 'Rìu Leviathan của Kratos tỉ lệ 1:1, có hiệu ứng đèn LED xanh lam mát mắt, chất liệu nhựa ABS cao cấp.'
    ],
    [
        'category' => 'accessories',
        'name' => 'Super Mario Question Block Light',
        'image_url' => 'https://images.unsplash.com/photo-1601053426743-30cc2f01f4c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '24.95',
        'old_price' => NULL,
        'badge' => 'Best Seller',
        'description' => 'Đèn ngủ khối vuông dấu hỏi Super Mario. Bấm vào sẽ phát ra âm thanh nhặt xu quen thuộc trong game.'
    ],
    [
        'category' => 'toys',
        'name' => 'League of Legends Ahri Unlocked Statue',
        'image_url' => 'https://images.unsplash.com/photo-1603366624933-28f09d2243d4?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '85.00',
        'old_price' => NULL,
        'badge' => 'Mới',
        'description' => 'Tượng Ahri Liên Minh Huyền Thoại Unlocked chính hãng Riot Games, đi kèm thẻ chứng nhận bản quyền.'
    ],
    [
        'category' => 'toys',
        'name' => 'Cyberpunk 2077 V & Yaiba Kusanagi Figure',
        'image_url' => 'https://images.unsplash.com/photo-1614210665516-7360216694e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '95.00',
        'old_price' => '120.00',
        'badge' => 'Giảm giá',
        'description' => 'Mô hình nhân vật V cùng siêu xe Yaiba Kusanagi phong cách Cyberpunk, chi tiết tinh xảo.'
    ],
    [
        'category' => 'accessories',
        'name' => 'Minecraft TNT Block Desk Light',
        'image_url' => 'https://images.unsplash.com/photo-1610486755490-c07a93556c4b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '22.95',
        'old_price' => NULL,
        'badge' => 'Hot',
        'description' => 'Đèn bàn khối TNT Minecraft, phát ra âm thanh nổ khi bật, phụ kiện decor góc gaming cực chất.'
    ],
    [
        'category' => 'clothing',
        'name' => 'Super Mario Yoshi Pullover Hoodie',
        'image_url' => 'https://images.unsplash.com/photo-1549463283-793542a27ea8?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '39.95',
        'old_price' => NULL,
        'badge' => 'Mới',
        'description' => 'Áo Hoodie Yoshi tông màu xanh lá, thiết kế unisex, chất nỉ bông ấm áp.'
    ],
    [
        'category' => 'clothing',
        'name' => 'League of Legends Teemo Hat',
        'image_url' => 'https://images.unsplash.com/photo-1533422902700-58079a296bba?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'price' => '19.95',
        'old_price' => NULL,
        'badge' => 'Best Seller',
        'description' => 'Mũ Teemo bông mềm mại, cosplay chuẩn xác tướng Teemo trong Liên Minh Huyền Thoại.'
    ]
];

$stmt = $pdo->prepare("INSERT INTO `products` (`category`, `name`, `image_url`, `price`, `old_price`, `badge`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($more_products as $p) {
    // Check if exists to avoid duplicates
    $check = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $check->execute([$p['name']]);
    if ($check->rowCount() == 0) {
        $stmt->execute([
            $p['category'],
            $p['name'],
            $p['image_url'],
            $p['price'],
            $p['old_price'],
            $p['badge'],
            $p['description']
        ]);
        $count++;
    }
}

echo "Inserted $count more products.";
