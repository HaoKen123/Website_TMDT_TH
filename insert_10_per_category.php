<?php
require_once 'db.php';

echo "<h2>Bắt đầu nạp 10 sản phẩm chi tiết cho mỗi danh mục...</h2>";

$products = [
    // === CATEGORY: CLOTHING (10 SP) ===
    [
        'category' => 'clothing',
        'name' => 'Áo Hoodie Minecraft Creeper Zip-Up Xanh Lá',
        'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=600&q=80',
        'price' => 590000,
        'old_price' => 750000,
        'badge' => 'Hot',
        'description' => "Áo Hoodie nỉ bông cao cấp in họa tiết Creeper 3D sắc nét.\n- Chất liệu: Nỉ chân cua 100% cotton dày dặn, giữ ấm tốt.\n- Khóa kéo kim loại mượt mà, mũ trùm đầu dây rút cá tính.\n- Phong cách unisex phù hợp cho cả nam và nữ."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Thun Steve & Alex Adventure Cotton 100%',
        'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80',
        'price' => 290000,
        'old_price' => 350000,
        'badge' => 'Bán chạy',
        'description' => "Áo thun phong cách mạo hiểm cùng Steve và Alex.\n- Vải cotton 4 chiều thoáng mát, thấm hút mồ hôi cực tốt.\n- Công nghệ in kĩ thuật số DTG sắc nét, không bong tróc khi giặt máy.\n- Form oversize rộng rãi chuẩn phong cách Streetwear."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Khoác Bomber Enderman Eyes Glow-In-The-Dark',
        'image' => 'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=600&q=80',
        'price' => 680000,
        'old_price' => 850000,
        'badge' => 'Mới',
        'description' => "Áo khoác Bomber thêu mắt Enderman dạ quang độc đáo.\n- Phát quang tím ấn tượng trong bóng tối.\n- Chất liệu vải dù 2 lớp chống gió, chống nước nhẹ.\n- Bo rập gấu tay tỉ mỉ, nhiều túi tiện dụng."
    ],
    [
        'category' => 'clothing',
        'name' => 'Bộ Trang Phục Cosplay Giáp Kim Cương Diamond Armor',
        'image' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=600&q=80',
        'price' => 890000,
        'old_price' => 1100000,
        'badge' => 'Chuyên nghiệp',
        'description' => "Bộ Cosplay Diamond Armor cho các buổi lễ hội & Halloween.\n- Bao gồm: Áo nỉ in họa tiết khối kim cương 3D + nón trùm đầu vuông.\n- Chất liệu nỉ xốp nhẹ nhàng, thoáng khí, linh hoạt di chuyển.\n- Màu sắc xanh ngọc cực nét như vừa đúc ra từ game."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Sweater Nỉ Dệt Minecraft Redstone Dust Unisex',
        'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=600&q=80',
        'price' => 450000,
        'old_price' => 520000,
        'badge' => 'Giảm giá',
        'description' => "Áo Sweater nỉ dệt kim họa tiết đá đỏ nổi bật.\n- Dệt sợi nỉ mịn dầy dặn, không xù lông.\n- Thiết kế phong cách retro pixel 8-bit cá tính.\n- Dễ phối đồ cùng quần jean, quần jogger."
    ],
    [
        'category' => 'clothing',
        'name' => 'Quần Jogger Thể Thao Minecraft Block Pattern',
        'image' => 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?auto=format&fit=crop&w=600&q=80',
        'price' => 380000,
        'old_price' => 450000,
        'badge' => '',
        'description' => "Quần Jogger nỉ dầy dặn thêu ô vuông khối đất Minecraft.\n- Thun co giãn 4 chiều vận động thể thao thoải mái.\n- 2 túi hông có khóa kéo an toàn để điện thoại, ví tiền.\n- Bo gấu cá tính gọn gàng."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Thun Nether Portal Graphic Edition',
        'image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=600&q=80',
        'price' => 270000,
        'old_price' => 320000,
        'badge' => '',
        'description' => "Áo thun in họa tiết Cổng Địa Ngục lung linh nhung tím.\n- 100% Cotton Premium mềm mịn.\n- Hình in chuyển nhiệt bám dính siêu bền.\n- Cổ tròn may xích kép không bị nhão cổ."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Khoác Dù Minecraft Weatherproof Chống Nước',
        'image' => 'https://images.unsplash.com/photo-1548883354-7622d03aca27?auto=format&fit=crop&w=600&q=80',
        'price' => 520000,
        'old_price' => 650000,
        'badge' => 'Chống nước',
        'description' => "Áo khoác gió dù 2 lớp chuyên dụng che mưa đi phượt.\n- Vải Poly Micro trượt nước 99%.\n- Nón có thể tháo rời linh hoạt.\n- In logo PixelGear thêu nổi ở tay áo."
    ],
    [
        'category' => 'clothing',
        'name' => 'Bộ Đồ Mặc Nhà Minecraft Mob Fusion Thun Lạnh',
        'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80',
        'price' => 310000,
        'old_price' => 390000,
        'badge' => '',
        'description' => "Bộ quần áo ngủ & mặc nhà họa tiết quái vật Minecraft.\n- Chất thun lạnh co giãn siêu mát tay.\n- Không nhăn, không bai xù khi giặt giặt.\n- Họa tiết Creeper, Zombie, Skeleton tươi sáng."
    ],
    [
        'category' => 'clothing',
        'name' => 'Áo Hoodie Ender Dragon Master Edition Thêu Nổi',
        'image' => 'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?auto=format&fit=crop&w=600&q=80',
        'price' => 720000,
        'old_price' => 890000,
        'badge' => 'Đặc biệt',
        'description' => "Áo Hoodie thêu hình Rồng Ender khổng lồ sau lưng.\n- Mũ trùm rộng 2 lớp dầy dặn.\n- Thêu vi tính hàng triệu mũi chỉ sắc nét.\n- Thiết kế giới hạn dành cho tín đồ Minecraft thực thụ."
    ],

    // === CATEGORY: ACCESSORIES (10 SP) ===
    [
        'category' => 'accessories',
        'name' => 'Balo Học Sinh Minecraft Creeper Chống Nước',
        'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80',
        'price' => 450000,
        'old_price' => 580000,
        'badge' => 'Bán chạy',
        'description' => "Balo đi học thiết kế mặt Creeper xanh cá tính.\n- Ngăn chứa lớn đựng laptop 15.6 inch & sách vở thoải mái.\n- Vải Oxford chống nước, đệm quai đeo đệm êm giảm áp lực vai.\n- Tặng kèm móc khóa Creeper dễ thương."
    ],
    [
        'category' => 'accessories',
        'name' => 'Mũ Lưỡi Trai Minecraft Diamond Sword Thêu Nổi',
        'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=600&q=80',
        'price' => 180000,
        'old_price' => 220000,
        'badge' => 'Hot',
        'description' => "Mũ Cap thời trang thêu nổi Kiếm Kim Cương 3D.\n- Chất liệu kaki cao cấp thoáng mát.\n- Khóa nấc kim loại phía sau dễ dàng tăng giảm size.\n- Form nón chuẩn cứng cáp giữ dáng tốt."
    ],
    [
        'category' => 'accessories',
        'name' => 'Móc Khóa Hợp Kim Khối Kim Cương Diamond Ore 3D',
        'image' => 'https://images.unsplash.com/photo-1605901302636-61845112df38?auto=format&fit=crop&w=600&q=80',
        'price' => 75000,
        'old_price' => 95000,
        'badge' => '',
        'description' => "Móc khóa kim loại xoay 360 độ khối quặng kim cương.\n- Đúc nguyên khối bằng hợp kim kẽm mạ chống gỉ.\n- Trọng lượng đầm tay, đường nét tinh xảo."
    ],
    [
        'category' => 'accessories',
        'name' => 'Ví Tiền Da Minecraft Pixel Leather Wallet',
        'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=600&q=80',
        'price' => 250000,
        'old_price' => 310000,
        'badge' => '',
        'description' => "Ví nam gấp đôi da PU cao cấp in dập nổi họa tiết ô vuông.\n- 8 ngăn chứa thẻ ngân hàng & 2 ngăn chứa tiền mặt rộng rãi.\n- Đường chỉ may thủ công chắc chắn."
    ],
    [
        'category' => 'accessories',
        'name' => 'Khăn Quàng Cổ Nỉ Lông Cừu Minecraft Mob Scarf',
        'image' => 'https://images.unsplash.com/photo-1520903920243-00d872a2d1c9?auto=format&fit=crop&w=600&q=80',
        'price' => 195000,
        'old_price' => 240000,
        'description' => "Khăn len nỉ êm ái giữ ấm mùa đông.\n- Dệt họa tiết các nhân vật Minecraft cực yêu.\n- Không gây ngứa da cổ, phối đồ cực chuẩn."
    ],
    [
        'category' => 'accessories',
        'name' => 'Túi Đeo Chéo Minecraft Redstone Tactical Bag',
        'image' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80',
        'price' => 320000,
        'old_price' => 390000,
        'badge' => 'Mới',
        'description' => "Túi bao tử đeo chéo vai nhỏ gọn năng động.\n- Chống nước nhẹ, chứa vừa iPad mini, điện thoại, sạc dự phòng.\n- Quai đeo điều chỉnh linh hoạt."
    ],
    [
        'category' => 'accessories',
        'name' => 'Đồng Hồ Đeo Tay Cảm Ứng LED Minecraft Digital',
        'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80',
        'price' => 280000,
        'old_price' => 350000,
        'badge' => 'Được yêu thích',
        'description' => "Đồng hồ màn hình LED hiển thị giờ nét căng khi chạm.\n- Dây đeo silicone dẻo êm tay không gây dị ứng.\n- Chống nước chuẩn 3ATM đi mưa rửa tay thoải mái."
    ],
    [
        'category' => 'accessories',
        'name' => 'Bộ 3 Vòng Tay Cao Su Silicone Minecraft Gamers',
        'image' => 'https://images.unsplash.com/photo-1611591475167-be08c7883e82?auto=format&fit=crop&w=600&q=80',
        'price' => 65000,
        'old_price' => 85000,
        'badge' => '',
        'description' => "Set 3 vòng tay dạ quang Creeper, Enderman & Steve.\n- Chất liệu silicone co giãn đàn hồi cao.\n- Phát quang trong đêm rực rỡ."
    ],
    [
        'category' => 'accessories',
        'name' => 'Nón Len Beanie Dệt Kim Minecraft TNT Explosion',
        'image' => 'https://images.unsplash.com/photo-1576871337622-98d48d1cf531?auto=format&fit=crop&w=600&q=80',
        'price' => 160000,
        'old_price' => 200000,
        'badge' => '',
        'description' => "Mũ len trùm đầu ấm áp dệt biểu tượng khối TNT.\n- Chất len vặn thừng co giãn thoải mái cho mọi kích thước đầu.\n- Giữ nhiệt tuyệt vời."
    ],
    [
        'category' => 'accessories',
        'name' => 'Kính Mát Thời Trang Minecraft Pixel 8-Bit Sunglasses',
        'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=600&q=80',
        'price' => 120000,
        'old_price' => 150000,
        'badge' => 'Cực ngầu',
        'description' => "Kính mát hiệu ứng rãnh Pixel 8-bit huyền thoại Thug Life.\n- Mắt kính chống tia UV400 bảo vệ mắt nắng mặt trời.\n- Gọng nhựa ABS dẻo dai khó gãy."
    ],

    // === CATEGORY: TOYS (10 SP) ===
    [
        'category' => 'toys',
        'name' => 'Gấu Bông Minecraft Creeper Plush 30cm',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'price' => 250000,
        'old_price' => 320000,
        'badge' => 'Hot',
        'description' => "Thú bông Creeper 30cm mềm mịn cao cấp.\n- Bông gòn tinh khiết PP 3D chống xẹp.\n- Vải nhung ngắn mềm mại không gây rụng lông an toàn cho bé."
    ],
    [
        'category' => 'toys',
        'name' => 'Gấu Bông Rồng Ender Giant Plush 45cm',
        'image' => 'https://images.unsplash.com/photo-1559715745-e1b34a25e88f?auto=format&fit=crop&w=600&q=80',
        'price' => 390000,
        'old_price' => 480000,
        'badge' => 'Khổng lồ',
        'description' => "Thú bông Rồng Ender cỡ lớn 45cm có cánh uốn cong.\n- Thiết kế sừng và mắt tím thêu sắc nét.\n- Thích hợp ôm ngủ hoặc trưng bày góc gaming."
    ],
    [
        'category' => 'toys',
        'name' => 'Kiếm Bọt Biển Foam Diamond Sword Minecraft 60cm',
        'image' => 'https://images.unsplash.com/photo-1593003058933-72ffb853549f?auto=format&fit=crop&w=600&q=80',
        'price' => 220000,
        'old_price' => 280000,
        'badge' => 'Bán chạy',
        'description' => "Mô hình Kiếm Kim Cương xốp EVA nguyên khối an toàn.\n- Chiều dài 60cm tỉ lệ thực như trong game.\n- Xốp mềm bền bỉ không gây đau khi chơi đối kháng."
    ],
    [
        'category' => 'toys',
        'name' => 'Cúp Bọt Biển Foam Diamond Pickaxe Minecraft 45cm',
        'image' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?auto=format&fit=crop&w=600&q=80',
        'price' => 210000,
        'old_price' => 260000,
        'badge' => '',
        'description' => "Cúp đào đá kim cương chất liệu xốp EVA dầy dặn.\n- Màu xanh ngọc kim cương tươi sáng.\n- Tay cầm vừa vặn chắc chắn cho các bé."
    ],
    [
        'category' => 'toys',
        'name' => 'Bộ Đồ Chơi Lắp Ráp Lego Minecraft Ngôi Nhà Cây 580 Chi Tiết',
        'image' => 'https://images.unsplash.com/photo-1585366119957-e9730b6d0f60?auto=format&fit=crop&w=600&q=80',
        'price' => 550000,
        'old_price' => 690000,
        'badge' => 'Sáng tạo',
        'description' => "Bộ gạch xếp hình Lego Minecraft 580 mảnh ghép.\n- Đi kèm nhân vật Steve, Alex, Creeper, Skeleton.\n- Rèn luyện tư duy không gian và tính kiên nhẫn."
    ],
    [
        'category' => 'toys',
        'name' => 'Mô Hình Steve Giáp Kim Cương Action Figure 15cm',
        'image' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=600&q=80',
        'price' => 290000,
        'old_price' => 360000,
        'badge' => '',
        'description' => "Mô hình khớp cử động Steve mang giáp kim cương.\n- Đi kèm phụ kiện Kiếm Kim Cương & Khối Đất.\n- Khớp tay chân xoay 360 độ tạo nhiều tư thế ngầu."
    ],
    [
        'category' => 'toys',
        'name' => 'Gấu Bông Con Lợn Pig Pink Soft Plush 20cm',
        'image' => 'https://images.unsplash.com/photo-1582234372722-50d7ccc30ebd?auto=format&fit=crop&w=600&q=80',
        'price' => 180000,
        'old_price' => 220000,
        'badge' => 'Dễ thương',
        'description' => "Thú bông Heo Hồng Minecraft siêu kute.\n- Nhung thun co giãn 4 chiều mềm mịn.\n- Món quà tuyệt vời cho các bạn nữ và trẻ em."
    ],
    [
        'category' => 'toys',
        'name' => 'Mô Hình Minecraft Blind Box Hộp Quà Bí Mật',
        'image' => 'https://images.unsplash.com/photo-1607513746994-51f730a44832?auto=format&fit=crop&w=600&q=80',
        'price' => 95000,
        'old_price' => 120000,
        'badge' => 'Bí mật',
        'description' => "Hộp quà ngẫu nhiên chứa 1 nhân vật Minecraft mini.\n- Bộ sưu tập 12 nhân vật hiếm khác nhau.\n- Nhựa sơn bóng tinh xảo sắc nét."
    ],
    [
        'category' => 'toys',
        'name' => 'Thú Bông Sói Thu Phục Tamed Wolf Plush 25cm',
        'image' => 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&w=600&q=80',
        'price' => 230000,
        'old_price' => 290000,
        'badge' => '',
        'description' => "Gấu bông Sói Đã Thu Phục đeo vòng cổ đỏ đáng yêu.\n- Bông gòn êm ái, đường may tỉ mỉ.\n- Người bạn đồng hành không thể thiếu."
    ],
    [
        'category' => 'toys',
        'name' => 'Rìu Bọt Biển Foam Iron Axe Minecraft 40cm',
        'image' => 'https://images.unsplash.com/photo-1628126235206-5260b9ea6441?auto=format&fit=crop&w=600&q=80',
        'price' => 190000,
        'old_price' => 240000,
        'badge' => '',
        'description' => "Mô hình Rìu Sắt xốp EVA màu xám bạc ánh kim.\n- Chất xốp siêu nhẹ, an toàn tuyệt đối cho trẻ nhỏ.\n- Kiểu dáng vuông vức nguyên bản."
    ],

    // === CATEGORY: DECOR (10 SP) ===
    [
        'category' => 'decor',
        'name' => 'Đèn Ngủ Cảm Ứng Minecraft Redstone Ore Light 3 Cấp Độ',
        'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=600&q=80',
        'price' => 380000,
        'old_price' => 480000,
        'badge' => 'Hot',
        'description' => "Đèn ngủ cảm ứng gõ nhẹ đổi 3 mức độ sáng đá đỏ.\n- Pin sạc USB tiện lợi dùng liên tục 12 tiếng.\n- Ánh sáng đỏ ấm dịu mắt giúp ngủ ngon."
    ],
    [
        'category' => 'decor',
        'name' => 'Đèn LED Khối Kim Cương Diamond Ore Night Light',
        'image' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?auto=format&fit=crop&w=600&q=80',
        'price' => 390000,
        'old_price' => 490000,
        'badge' => 'Bán chạy',
        'description' => "Đèn ngủ khối kim cương tỏa ánh sáng xanh ngọc huyền ảo.\n- Thiết kế lập phương 10x10cm chuẩn nguyên bản.\n- Dùng trang trí bàn học & bàn gaming siêu chill."
    ],
    [
        'category' => 'decor',
        'name' => 'Đèn Ngủ Chai Thuốc Độc Potion Bottle 8 Màu Đổi Tự Động',
        'image' => 'https://images.unsplash.com/photo-1540932239986-30128078f3c5?auto=format&fit=crop&w=600&q=80',
        'price' => 420000,
        'old_price' => 520000,
        'badge' => 'Độc lạ',
        'description' => "Đèn ngủ chai phép thuật ma thuật Minecraft.\n- Tự động chuyển đổi 8 màu sắc rực rỡ.\n- Dùng sạc USB hoặc 3 viên pin AAA."
    ],
    [
        'category' => 'decor',
        'name' => 'Đèn Treo Tường Ngọn Đuốc Minecraft Wall Torch Light',
        'image' => 'https://images.unsplash.com/photo-1517991104123-1d56a6e81ed9?auto=format&fit=crop&w=600&q=80',
        'price' => 360000,
        'old_price' => 450000,
        'badge' => 'Cực hot',
        'description' => "Đèn ngọn đuốc Minecraft gắn tường hoặc để bàn.\n- Khớp gập thông minh gắn góc tường phòng ngủ.\n- Ánh sáng vàng ấm cúng phong cách phòng chơi game."
    ],
    [
        'category' => 'decor',
        'name' => 'Đồng Hồ Báo Thức Khối Đất Grass Block Alarm Clock',
        'image' => 'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?auto=format&fit=crop&w=600&q=80',
        'price' => 320000,
        'old_price' => 390000,
        'badge' => '',
        'description' => "Đồng hồ báo thức màn hình kỹ thuật số khối đất Minecraft.\n- Âm thanh báo thức nhạc game Minecraft vui nhộn.\n- Hiển thị ngày tháng & nhiệt độ phòng."
    ],
    [
        'category' => 'decor',
        'name' => 'Thảm Trải Sàn Minecraft Creeper Door Mat 60x40cm',
        'image' => 'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=600&q=80',
        'price' => 190000,
        'old_price' => 250000,
        'badge' => '',
        'description' => "Thảm chùi chân cửa ra vào in mặt Creeper cá tính.\n- Sợi mịn thấm hút nước tốt.\n- Đế cao su ma sát cao chống trơn trượt an toàn."
    ],
    [
        'category' => 'decor',
        'name' => 'Bộ 4 Tranh Canvas Khung Gỗ Minecraft World Art',
        'image' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80',
        'price' => 480000,
        'old_price' => 600000,
        'badge' => 'Sang trọng',
        'description' => "Bộ 4 bức tranh treo tường Canvas khung gỗ chống mốc.\n- In phun kĩ thuật số màu sắc trung thực.\n- Nâng tầm góc máy tính livestream đỉnh cao."
    ],
    [
        'category' => 'decor',
        'name' => 'Gối Tựa Lưng Khối Thuốc Nổ Minecraft TNT Cushion 40cm',
        'image' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=600&q=80',
        'price' => 210000,
        'old_price' => 270000,
        'badge' => '',
        'description' => "Gối tựa lưng vuông 40cm thêu khối TNT đỏ.\n- Bông gòn microfiber êm ái đàn hồi tốt.\n- Vỏ gối có khóa kéo tháo giặt dễ dàng."
    ],
    [
        'category' => 'decor',
        'name' => 'Đèn Uốn Dây Neon LED Khối Đất Grass Block Light',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'price' => 450000,
        'old_price' => 550000,
        'badge' => 'Decor đỉnh',
        'description' => "Đèn Neon LED dẻo uốn hình khối đất Minecraft rực rỡ.\n- Nguồn USB 5V siêu tiết kiệm điện.\n- Phù hợp treo góc tường sống ảo & quay video TikTok."
    ],
    [
        'category' => 'decor',
        'name' => 'Bộ Chăn Ga Gối Đệm Minecraft Biome Cotton 100%',
        'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=600&q=80',
        'price' => 980000,
        'old_price' => 1250000,
        'badge' => 'Mới',
        'description' => "Set chăn ga gối đệm in thế giới Minecraft sinh động.\n- Chất vải Cotton Satin 100% thoáng mát mềm mại.\n- Không phai màu, không xù lông sau nhiều lần giặt."
    ]
];

try {
    $stmt = $pdo->prepare("INSERT INTO products (category, name, image_url, price, old_price, badge, description, stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, 50, 1)");
    
    $count = 0;
    foreach ($products as $p) {
        $stmt->execute([
            $p['category'],
            $p['name'],
            $p['image'],
            $p['price'],
            $p['old_price'],
            $p['badge'],
            $p['description']
        ]);
        $count++;
        echo "<p style='color:green;'>✓ Đã nạp sản phẩm ($count/40): <strong>{$p['name']}</strong> [Danh mục: {$p['category']}]</p>";
    }
    
    echo "<h3 style='color:blue;'>🎉 ĐÃ NẠP THÀNH CÔNG RỰC RỠ 40 SẢN PHẨM (MỖI DANH MỤC 10 SẢN PHẨM CÓ MÔ TẢ CHI TIẾT)!</h3>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi nạp sản phẩm: " . $e->getMessage() . "</p>";
}
