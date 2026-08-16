<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');
$userApiKey = trim($input['api_key'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung câu hỏi không được để trống']);
    exit;
}

try {
    // 1. Fetch full product catalog from SQL
    $stmt = $pdo->query("SELECT p.*, COALESCE(c.name, p.category) as category_name FROM products p LEFT JOIN categories c ON (p.category = c.slug OR p.category = c.name) ORDER BY p.id ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch active coupons from SQL
    $stmtCoupons = $pdo->query("SELECT code, discount_type, discount_value FROM coupons WHERE status = 'active' ORDER BY id DESC LIMIT 5");
    $coupons = $stmtCoupons->fetchAll(PDO::FETCH_ASSOC);

    $qLower = mb_strtolower($query, 'UTF-8');
    
    // Check if user requested auto quick view
    $isQuickViewRequested = (
        strpos($qLower, 'xem nhanh') !== false || 
        strpos($qLower, 'mở') !== false || 
        strpos($qLower, 'xem chi tiết') !== false ||
        strpos($qLower, 'cho xem') !== false
    );

    $matchedProducts = [];
    $autoQuickViewId = null;

    // Direct keyword match in MySQL products
    foreach ($products as $p) {
        $pName = mb_strtolower($p['name'], 'UTF-8');
        $pDesc = mb_strtolower($p['description'] ?? '', 'UTF-8');
        $pCat  = mb_strtolower($p['category_name'] ?? '', 'UTF-8');

        // Extract main words from query
        $words = array_filter(explode(' ', $qLower), function($w) {
            return strlen($w) > 2 && !in_array($w, ['cho', 'xem', 'tôi', 'bạn', 'có', 'không', 'nào', 'với', 'giá', 'bao', 'nhiêu']);
        });

        $matched = false;
        if (strpos($pName, $qLower) !== false || strpos($pDesc, $qLower) !== false) {
            $matched = true;
        } else {
            foreach ($words as $w) {
                if (strpos($pName, $w) !== false || strpos($pCat, $w) !== false) {
                    $matched = true;
                    break;
                }
            }
        }

        if ($matched) {
            $matchedProducts[] = $p;
            if ($isQuickViewRequested && $autoQuickViewId === null) {
                $autoQuickViewId = $p['id'];
            }
        }
    }

    // Prepare System Context for AI
    $productContextList = [];
    foreach ($products as $p) {
        $img = !empty($p['image_url']) ? $p['image_url'] : 'images/products/default.jpg';
        $price = '$' . number_format($p['price'], 2);
        $productContextList[] = "ID: {$p['id']} | Tên: {$p['name']} | Giá: {$price} | Danh mục: {$p['category_name']} | Mô tả: {$p['description']} | Ảnh: {$img}";
    }
    $contextText = implode("\n", $productContextList);

    $couponText = "Mã giảm giá đang có: WELCOME15 (Giảm 15%), FREESHIP (Miễn phí ship).";
    if (!empty($coupons)) {
        $cList = array_map(function($c) { 
            $val = ($c['discount_type'] === 'percent') ? (float)$c['discount_value'] . '%' : '$' . (float)$c['discount_value'];
            return "{$c['code']} (Giảm {$val})"; 
        }, $coupons);
        $couponText .= " Khác: " . implode(", ", $cList);
    }

    // Call Gemini API if Key is provided or try public endpoint
    $aiResponseText = "";
    if (!empty($userApiKey)) {
        $aiResponseText = callGeminiAPI($query, $contextText, $couponText, $userApiKey);
    }

    if (empty($aiResponseText)) {
        // SQL Smart Fallback Engine
        if (!empty($matchedProducts)) {
            $aiResponseText = "🔍 <strong>Đã tìm thấy " . count($matchedProducts) . " sản phẩm phù hợp:</strong><br><br>";
            foreach (array_slice($matchedProducts, 0, 4) as $p) {
                $priceStr = '$' . number_format($p['price'], 2);
                $imgSrc = !empty($p['image_url']) ? $p['image_url'] : 'images/products/default.jpg';
                $safeName = htmlspecialchars($p['name'], ENT_QUOTES);
                $safeDesc = htmlspecialchars(mb_substr($p['description'] ?? 'Sản phẩm chính hãng PixelGear.', 0, 100, 'UTF-8') . '...', ENT_QUOTES);

                $aiResponseText .= "
                <div style='display:flex; gap:10px; background:#f1f5f9; padding:10px; border-radius:8px; margin-bottom:10px; border:1px solid #cbd5e1;'>
                    <img src='{$imgSrc}' style='width:55px; height:55px; object-fit:cover; border-radius:6px;'>
                    <div style='flex:1;'>
                        <div style='font-weight:700; color:#0f172a; font-size:13px;'>{$safeName}</div>
                        <div style='color:#059669; font-weight:700; font-size:13px;'>{$priceStr}</div>
                        <div style='display:flex; gap:6px; margin-top:6px;'>
                            <button onclick='window.openQuickViewModal({$p['id']})' style='background:#15803d; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer;'>👁️ Xem Nhanh</button>
                            <button onclick=\"speakText('Sản phẩm {$safeName}. Giá {$priceStr}. Mô tả: {$safeDesc}')\" style='background:#0284c7; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer;'>🔊 Đọc Mô Tả</button>
                        </div>
                    </div>
                </div>";
            }
            $aiResponseText .= "💡 Bấm <strong>👁️ Xem Nhanh</strong> để xem chi tiết ngay trên trang.";
        } elseif (strpos($qLower, 'ship') !== false || strpos($qLower, 'giao hàng') !== false) {
            $aiResponseText = "🚚 <strong>Chính sách giao hàng PixelGear:</strong><br>• Phí ship toàn quốc từ $1.0 - $1.5.<br>• Nhập mã <strong>FREESHIP</strong> để được miễn phí vận chuyển!";
        } elseif (strpos($qLower, 'mã') !== false || strpos($qLower, 'voucher') !== false || strpos($qLower, 'khuyến mãi') !== false) {
            $aiResponseText = "🎁 <strong>Mã giảm giá hiện có:</strong><br>• <strong>WELCOME15</strong>: Giảm 15% cho đơn hàng đầu tiên.<br>• <strong>FREESHIP</strong>: Miễn phí vận chuyển toàn quốc!";
        } else {
            $aiResponseText = "💡 Xin chào! Bạn có thể gõ tên sản phẩm (VD: <em>'Áo thun'</em>, <em>'Balo'</em>, <em>'Mũ'</em>) hoặc <em>'Mã giảm giá'</em> để nhận ưu đãi từ PixelGear.";
        }
    }

    echo json_encode([
        'success' => true,
        'reply' => $aiResponseText,
        'auto_quick_view_id' => $autoQuickViewId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL SQL: ' . $e->getMessage()]);
}

function callGeminiAPI($userQuery, $catalogContext, $couponContext, $apiKey) {
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);
    
    $prompt = "Bạn là Trợ lý AI bán hàng Steve Minecraft của PixelGear Store. Dưới đây là danh sách toàn bộ sản phẩm hiện có trong CSDL SQL thực tế:\n{$catalogContext}\n{$couponContext}\n\nHãy trả lời câu hỏi của khách bằng tiếng Việt thân thiện, giới thiệu chuẩn tên sản phẩm, giá và chèn nút Xem Nhanh dạng [QUICKVIEW:ID].\nKhách hỏi: {$userQuery}";

    $payload = [
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result) {
        $json = json_decode($result, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $json['candidates'][0]['content']['parts'][0]['text'];
            $text = preg_replace('/\[QUICKVIEW:(\d+)\]/', '<button onclick="window.openQuickViewModal($1)" style="background:#15803d; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-size:11px; cursor:pointer;">👁️ Xem Nhanh</button>', $text);
            return nl2br($text);
        }
    }
    return null;
}
?>
