<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');
$userApiKey = trim($input['api_key'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung câu hỏi không được để trống']);
    exit;
}

try {
    $qLower = mb_strtolower($query, 'UTF-8');
    
    // Fetch all active products
    $stmt = $pdo->query("SELECT p.*, COALESCE(c.name, p.category) as category_name FROM products p LEFT JOIN categories c ON (p.category = c.slug OR p.category = c.name) WHERE p.status = 1 ORDER BY p.id ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active coupons
    $stmtCoupons = $pdo->query("SELECT * FROM coupons WHERE status = 'active' ORDER BY id DESC LIMIT 5");
    $coupons = $stmtCoupons->fetchAll(PDO::FETCH_ASSOC);

    // Helper: Render Product Cards HTML for Chatbot
    function renderProductCards($productList, $introText = '') {
        $html = !empty($introText) ? $introText . "<br><br>" : "";
        foreach (array_slice($productList, 0, 4) as $p) {
            $pId = $p['id'];
            $pName = htmlspecialchars(translate_product_name($p['name']), ENT_QUOTES);
            $pPrice = format_price($p['price']);
            $pImg = !empty($p['image_url']) ? $p['image_url'] : 'images/favicon.png';
            $pDesc = htmlspecialchars(mb_substr($p['description'] ?? 'Sản phẩm Minecraft chính hãng PixelGear Store.', 0, 100, 'UTF-8'), ENT_QUOTES);
            $safeSpeakText = addslashes("Sản phẩm {$pName}, giá {$pPrice}. {$pDesc}");

            $html .= "
            <div style='display: flex; gap: 10px; background: #ffffff; padding: 10px; border-radius: 8px; margin-bottom: 8px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.04); align-items: center;'>
                <img src='{$pImg}' style='width: 52px; height: 52px; object-fit: cover; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0;'>
                <div style='flex: 1; min-width: 0;'>
                    <div style='font-weight: 700; color: #0f172a; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;' title='{$pName}'>{$pName}</div>
                    <div style='color: #15803d; font-weight: 800; font-size: 13px; margin-top: 2px;'>{$pPrice}</div>
                    <div style='display: flex; gap: 4px; margin-top: 6px; flex-wrap: wrap;'>
                        <a href='product_detail.php?id={$pId}' target='_blank' style='background: #0284c7; color: #fff; padding: 3px 7px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;'>
                            <i class=\"fas fa-eye\"></i> Xem
                        </a>
                        <button type='button' onclick='addFromChatbot({$pId})' style='background: #15803d; color: #fff; border: none; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;'>
                            <i class=\"fas fa-cart-plus\"></i> Mua
                        </button>
                        <button type='button' onclick=\"speakText('{$safeSpeakText}')\" style='background: #f59e0b; color: #000; border: none; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;'>
                            <i class=\"fas fa-volume-up\"></i> Nghe
                        </button>
                    </div>
                </div>
            </div>";
        }
        return $html;
    }

    // Helper: Render Coupon Cards HTML
    function renderCouponCards($couponList) {
        $html = "🎁 <strong>Danh sách mã giảm giá độc quyền đang có:</strong><br><br>";
        if (empty($couponList)) {
            $html .= "Hiện tại chưa có mã giảm giá mới. Hãy đăng ký nhận tin để nhận mã sớm nhất!";
        } else {
            foreach ($couponList as $c) {
                $code = htmlspecialchars($c['code']);
                $val = ($c['discount_type'] === 'percent') ? (float)$c['discount_value'] . '%' : format_price($c['discount_value']);
                $minOrder = ($c['min_order'] > 0) ? " cho đơn từ " . format_price($c['min_order']) : " cho mọi đơn hàng";
                $html .= "
                <div style='background: #fef3c7; border: 1px dashed #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;'>
                    <div>
                        <strong style='color: #b45309; font-size: 14px;'>{$code}</strong>
                        <div style='font-size: 12px; color: #78350f;'>Giảm <strong>{$val}</strong>{$minOrder}</div>
                    </div>
                    <button type='button' onclick=\"navigator.clipboard.writeText('{$code}'); alert('Đã sao chép mã: {$code}');\" style='background: #15803d; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer;'>Chép</button>
                </div>";
            }
        }
        return $html;
    }

    $aiResponse = "";

    // -------------------------------------------------------------
    // 0. GEMINI API CALL FIRST (If API Key provided by user)
    // -------------------------------------------------------------
    if (!empty($userApiKey)) {
        $contextList = [];
        foreach ($products as $p) {
            $pNameTr = translate_product_name($p['name']);
            $contextList[] = "ID: {$p['id']} | Tên: {$pNameTr} | Giá: {$p['price']} | Danh mục: {$p['category']} | Mô tả: {$p['description']}";
        }
        $contextStr = implode("\n", $contextList);
        $geminiRes = callGeminiAPI($query, $contextStr, $userApiKey);
        if (!empty($geminiRes)) {
            $aiResponse = $geminiRes;
        }
    }

    // -------------------------------------------------------------
    // 1. INTENT: Date, Time & Calendar Questions (e.g. "hôm nay là thứ mấy", "ngày mấy", "mấy giờ rồi")
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $isDateQuery = (
            strpos($qLower, 'hôm nay') !== false ||
            strpos($qLower, 'thứ mấy') !== false ||
            strpos($qLower, 'ngày mấy') !== false ||
            strpos($qLower, 'ngày bao nhiêu') !== false ||
            strpos($qLower, 'mấy giờ') !== false ||
            strpos($qLower, 'bây giờ') !== false ||
            strpos($qLower, 'thời gian') !== false ||
            strpos($qLower, 'năm nay') !== false
        );

        if ($isDateQuery) {
            $days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
            $dayOfWeek = $days[date('w')];
            $currentDate = date('d/m/Y');
            $currentTime = date('H:i');
            $aiResponse = "📅 <strong>Thông tin thời gian hôm nay:</strong><br>• Hôm nay là <strong>{$dayOfWeek}, ngày {$currentDate}</strong>.<br>• Thời gian hiện tại: <strong>{$currentTime}</strong>.<br><br>Chúc bạn một ngày tràn đầy niềm vui và mua sắm thú vị tại PixelGear Store!";
        }
    }

    // -------------------------------------------------------------
    // 2. INTENT: Weather & Casual Daily Questions (e.g. "Thời tiết đẹp k", "thời tiết hôm nay", "mưa không")
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $isWeather = (
            strpos($qLower, 'thời tiết') !== false ||
            strpos($qLower, 'trời mưa') !== false ||
            strpos($qLower, 'trời nắng') !== false ||
            strpos($qLower, 'trời đẹp') !== false ||
            strpos($qLower, 'nhiệt độ') !== false ||
            strpos($qLower, 'nắng không') !== false ||
            strpos($qLower, 'mưa không') !== false
        );

        if ($isWeather) {
            $aiResponse = "☀️ <strong>Dự báo thời tiết hôm nay rất đẹp và dễ chịu!</strong><br><br>Rất thích hợp để diện những mẫu <strong>Áo Hoodie / Áo thun Minecraft</strong> cá tính dạo phố, hoặc sưu tầm các mô hình độc đáo. Bạn có muốn Steve AI gợi ý mẫu đồ chơi hoặc phụ kiện nào đang hot hôm nay không?";
        }
    }

    // -------------------------------------------------------------
    // 3. INTENT: Best / Top / Most Popular Products (e.g. "Sản phẩm nào là ngon nhất", "sản phẩm nào hot nhất", "bán chạy nhất", "nên mua gì")
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $isBestQuery = (
            strpos($qLower, 'ngon nhất') !== false ||
            strpos($qLower, 'tốt nhất') !== false ||
            strpos($qLower, 'hot nhất') !== false ||
            strpos($qLower, 'bán chạy') !== false ||
            strpos($qLower, 'được thích nhất') !== false ||
            strpos($qLower, 'được chuộng') !== false ||
            strpos($qLower, 'nên mua gì') !== false ||
            strpos($qLower, 'gợi ý mua') !== false ||
            strpos($qLower, 'đẹp nhất') !== false ||
            strpos($qLower, 'nổi bật nhất') !== false
        );

        if ($isBestQuery) {
            // Pick featured or top priced/popular products
            $bestProds = array_filter($products, function($p) {
                return (!empty($p['badge']) || in_array($p['id'], [1, 2, 5, 8, 12, 18, 20]));
            });
            if (empty($bestProds)) {
                $bestProds = array_slice($products, 0, 4);
            }
            $aiResponse = renderProductCards(array_values($bestProds), "🔥 <strong>Top những sản phẩm ĐƯỢC YÊU THÍCH & ĐÁNH GIÁ CAO NHẤT tại PixelGear:</strong>");
        }
    }

    // -------------------------------------------------------------
    // 4. INTENT: Greetings, Introduction & Store Info
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $isGreeting = (
            $qLower === 'chào' || $qLower === 'xin chào' || $qLower === 'hello' || $qLower === 'hi' ||
            strpos($qLower, 'chào bạn') !== false || strpos($qLower, 'chào shop') !== false ||
            strpos($qLower, 'bạn là ai') !== false || strpos($qLower, 'bạn tên gì') !== false ||
            strpos($qLower, 'shop ở đâu') !== false || strpos($qLower, 'địa chỉ') !== false ||
            strpos($qLower, 'liên hệ') !== false
        );

        if ($isGreeting) {
            $aiResponse = "👋 <strong>Xin chào! Tôi là Steve AI – Trợ lý bán hàng thông minh của PixelGear Store.</strong><br><br>Tôi có thể giúp bạn:<br>• Tra cứu & tư vấn <strong>🧸 Đồ chơi</strong>, <strong>🎒 Phụ kiện</strong>, <strong>👕 Quần áo</strong>.<br>• Tìm kiếm <strong>🎁 Mã giảm giá</strong> & giải đáp thắc mắc mua hàng.<br><br>Bạn muốn tôi tư vấn món đồ nào hôm nay?";
        }
    }

    // -------------------------------------------------------------
    // 5. INTENT: Thanks & Casual Goodbyes
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $isThanks = (
            strpos($qLower, 'cảm ơn') !== false || strpos($qLower, 'thank') !== false ||
            strpos($qLower, 'tuyệt vời') !== false || strpos($qLower, 'tạm biệt') !== false ||
            $qLower === 'ok' || $qLower === 'oke'
        );

        if ($isThanks) {
            $aiResponse = "😊 Rất vui được hỗ trợ bạn! Chúc bạn có những phút giây mua sắm tuyệt vời tại PixelGear Store!";
        }
    }

    // -------------------------------------------------------------
    // 6. INTENT: Shipping & Delivery
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        if (strpos($qLower, 'phí ship') !== false || strpos($qLower, 'giao hàng') !== false || strpos($qLower, 'vận chuyển') !== false || strpos($qLower, 'ship') !== false) {
            $aiResponse = "🚚 <strong>Chính sách giao hàng & Vận chuyển:</strong><br>• Giao hàng nhanh toàn quốc từ 2 - 4 ngày làm việc.<br>• Phí vận chuyển tiêu chuẩn chỉ từ 20.000₫ - 35.000₫.<br>• Đặc biệt: Nhập mã <strong>FREESHIP</strong> để được miễn phí vận chuyển toàn quốc!";
        }
    }

    // -------------------------------------------------------------
    // 7. INTENT: Coupons & Vouchers
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        if (strpos($qLower, 'mã giảm giá') !== false || strpos($qLower, 'voucher') !== false || strpos($qLower, 'khuyến mãi') !== false || strpos($qLower, 'ưu đãi') !== false || $qLower === 'mã' || $qLower === 'coupon') {
            $aiResponse = renderCouponCards($coupons);
        }
    }

    // -------------------------------------------------------------
    // 8. INTENT: CATEGORY SEARCH (STRICT FILTERING - BẮT BUỘC CHÍNH XÁC)
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        // A. Category: Accessories (Phụ kiện)
        if (strpos($qLower, 'phụ kiện') !== false || strpos($qLower, 'accessories') !== false || strpos($qLower, 'balo') !== false || strpos($qLower, 'mũ') !== false || strpos($qLower, 'nón') !== false || strpos($qLower, 'đèn') !== false || strpos($qLower, 'khiên') !== false || strpos($qLower, 'đồng hồ') !== false) {
            $accProds = array_filter($products, function($p) {
                return (strtolower($p['category']) === 'accessories' || stripos($p['category_name'] ?? '', 'phụ kiện') !== false);
            });
            if (!empty($accProds)) {
                $aiResponse = renderProductCards(array_values($accProds), "🎒 <strong>Gợi ý các Phụ kiện Minecraft độc quyền cho bạn:</strong>");
            }
        }
        // B. Category: Clothing (Quần áo & Hoodies)
        elseif (strpos($qLower, 'quần áo') !== false || strpos($qLower, 'clothing') !== false || strpos($qLower, 'hoodie') !== false || strpos($qLower, 'áo thun') !== false || strpos($qLower, 'áo khoác') !== false || strpos($qLower, 'sweater') !== false || strpos($qLower, 'cosplay') !== false) {
            $clothProds = array_filter($products, function($p) {
                return (strtolower($p['category']) === 'clothing' || stripos($p['category_name'] ?? '', 'quần áo') !== false);
            });
            if (!empty($clothProds)) {
                $aiResponse = renderProductCards(array_values($clothProds), "👕 <strong>Gợi ý các mẫu Quần áo & Hoodies Minecraft cực hot:</strong>");
            }
        }
        // C. Category: Toys (Đồ chơi & Gấu bông)
        elseif (strpos($qLower, 'đồ chơi') !== false || strpos($qLower, 'toys') !== false || strpos($qLower, 'gấu bông') !== false || strpos($qLower, 'thú nhồi bông') !== false || strpos($qLower, 'mô hình') !== false || strpos($qLower, 'figure') !== false || strpos($qLower, 'warden') !== false || strpos($qLower, 'axolotl') !== false || strpos($qLower, 'dragon') !== false || strpos($qLower, 'kiếm') !== false || strpos($qLower, 'nỏ') !== false) {
            $toyProds = array_filter($products, function($p) {
                return (strtolower($p['category']) === 'toys' || stripos($p['category_name'] ?? '', 'đồ chơi') !== false);
            });
            if (!empty($toyProds)) {
                $aiResponse = renderProductCards(array_values($toyProds), "🧸 <strong>Gợi ý Đồ chơi & Thú nhồi bông Minecraft chính hãng:</strong>");
            }
        }
    }

    // -------------------------------------------------------------
    // 9. INTENT: SPECIFIC PRODUCT KEYWORD SEARCH
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        // Explicit product trigger keywords
        $explicitProductTriggers = [
            'áo', 'quần', 'hoodie', 'bomber', 'sweater', 'cosplay', 'mũ', 'nón', 'balo', 'túi', 'đèn', 'khiên', 'đồng hồ',
            'gấu bông', 'thú nhồi bông', 'mô hình', 'figure', 'kiếm', 'nỏ', 'tnt', 'creeper', 'enderman', 'steve', 'warden',
            'axolotl', 'diamond', 'redstone', 'giáp', 'gối', 'thảm', 'mua', 'giá', 'tìm sản phẩm', 'bán gì', 'sản phẩm'
        ];

        $hasProductIntent = false;
        foreach ($explicitProductTriggers as $trigger) {
            if (strpos($qLower, $trigger) !== false) {
                $hasProductIntent = true;
                break;
            }
        }

        if ($hasProductIntent) {
            $stopWords = ['thời', 'tiết', 'đẹp', 'xấu', 'nắng', 'mưa', 'k', 'ko', 'không', 'quá', 'lắm', 'hả', 'nha', 'nhé', 'ơi', 'à', 'được', 'thế', 'sao', 'có', 'là', 'gì', 'cho', 'xem', 'tôi', 'bạn', 'với', 'nào', 'rất', 'hay', 'tốt', 'minecraft', 'pixelgear'];
            $rawWords = preg_split('/\s+/', $qLower);
            $cleanKeywords = [];
            foreach ($rawWords as $w) {
                $w = trim($w);
                if (mb_strlen($w, 'UTF-8') >= 2 && !in_array($w, $stopWords)) {
                    $cleanKeywords[] = $w;
                }
            }

            if (!empty($cleanKeywords)) {
                $matched = [];
                foreach ($products as $p) {
                    $pName = mb_strtolower($p['name'], 'UTF-8');
                    $pDesc = mb_strtolower($p['description'] ?? '', 'UTF-8');
                    
                    $matchScore = 0;
                    foreach ($cleanKeywords as $kw) {
                        if (strpos($pName, $kw) !== false) {
                            $matchScore += 3;
                        } elseif (strpos($pDesc, $kw) !== false) {
                            $matchScore += 1;
                        }
                    }
                    if ($matchScore > 0) {
                        $p['_score'] = $matchScore;
                        $matched[] = $p;
                    }
                }

                if (!empty($matched)) {
                    usort($matched, function($a, $b) { return $b['_score'] - $a['_score']; });
                    $aiResponse = renderProductCards($matched, "🔍 <strong>Đã tìm thấy " . count($matched) . " sản phẩm phù hợp với yêu cầu của bạn:</strong>");
                }
            }
        }
    }

    // -------------------------------------------------------------
    // 10. FINAL CONVERSATIONAL FALLBACK (Nếu không khớp từ khóa nào)
    // -------------------------------------------------------------
    if (empty($aiResponse)) {
        $aiResponse = "💡 <strong>Steve AI sẵn sàng hỗ trợ bạn!</strong><br><br>Bạn có thể hỏi tôi về thông tin thời gian, thời tiết, các sản phẩm hot nhất hoặc chọn nhanh danh mục bên dưới:<br><br>
        • <strong>👕 Quần áo & Hoodies</strong><br>
        • <strong>🎒 Phụ kiện Minecraft</strong><br>
        • <strong>🧸 Đồ chơi & Gấu bông</strong><br>
        • <strong>🎁 Mã giảm giá</strong>";
    }

    echo json_encode([
        'success' => true,
        'reply' => $aiResponse
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý AI: ' . $e->getMessage()]);
}

function callGeminiAPI($userQuery, $catalogContext, $apiKey) {
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);
    
    $prompt = "Bạn là Steve AI - Trợ lý bán hàng thông minh của cửa hàng PixelGear Store.
Danh sách sản phẩm trong kho:
{$catalogContext}

Yêu cầu trả lời:
1. Trả lời bằng tiếng Việt chuẩn, súc tích, tự nhiên, thân thiện.
2. Nếu khách hỏi câu hỏi giao tiếp đời sống (thời gian, thứ ngày, thời tiết, chào hỏi, chúc mừng, hỏi thăm), hãy trả lời ngay ngắn gọn, không spam quảng cáo.
3. Nếu khách hỏi sản phẩm nào ngon/tốt/hot nhất hoặc tìm đồ, hãy gợi ý tên sản phẩm và giá tiền cụ thể.
4. Trả lời dưới dạng văn bản HTML đơn giản (sử dụng <strong>, <br>), không dùng Markdown phức tạp.

Khách hỏi: \"{$userQuery}\"";

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result) {
        $json = json_decode($result, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $txt = $json['candidates'][0]['content']['parts'][0]['text'];
            // Convert any remaining markdown to clean HTML
            $txt = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $txt);
            $txt = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $txt);
            return nl2br($txt);
        }
    }
    return null;
}
?>
