<!-- PixelBot AI Assistant & Voice Engine - PixelGear Official -->
<style>
    .ai-chat-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #15803d 0%, #047857 100%);
        color: #ffffff;
        border: 3px solid #f59e0b;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        z-index: 9999;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ai-chat-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 12px 28px rgba(0,0,0,0.4);
    }
    .ai-chat-modal {
        position: fixed;
        bottom: 95px;
        right: 25px;
        width: 380px;
        max-width: calc(100vw - 30px);
        height: 520px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 12px 35px rgba(0,0,0,0.25);
        border: 2px solid #cbd5e1;
        z-index: 9999;
        display: none;
        flex-direction: column;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
    }
    .ai-chat-header {
        background: #15803d;
        color: #ffffff;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ai-chat-header .title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
    }
    .ai-chat-header .controls {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .ai-header-icon {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #ffffff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: background 0.2s;
    }
    .ai-header-icon:hover, .ai-header-icon.active {
        background: #f59e0b;
        color: #000;
    }
    .ai-chat-body {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .chat-msg {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.5;
        word-wrap: break-word;
    }
    .chat-msg.bot {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .chat-msg.user {
        background: #15803d;
        color: #ffffff;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }
    .ai-quick-prompts {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    .quick-chip {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.15s;
    }
    .quick-chip:hover {
        background: #15803d;
        color: #ffffff;
        border-color: #15803d;
    }
    .ai-chat-footer {
        padding: 10px 12px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .ai-chat-footer input {
        flex: 1;
        padding: 9px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        font-size: 13px;
        outline: none;
        margin-bottom: 0;
    }
    .ai-chat-footer button {
        background: #15803d;
        color: #fff;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .voice-status-indicator {
        font-size: 11px;
        color: #15803d;
        font-weight: 600;
        text-align: center;
        padding: 2px;
        display: none;
    }
    .api-key-box {
        background: #fffbebfb;
        border: 1px solid #fef08a;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 8px;
        font-size: 12px;
        display: none;
    }
    .api-key-box input {
        width: 100%;
        padding: 6px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        margin-top: 4px;
    }
</style>

<!-- Floating Toggle Button -->
<button class="ai-chat-btn" onclick="toggleAiChat()" title="Trợ lý AI PixelBot">
    <i class="fas fa-robot"></i>
</button>

<!-- Chat Modal Window -->
<div id="aiChatModal" class="ai-chat-modal">
    <div class="ai-chat-header">
        <div class="title">
            <i class="fas fa-robot" style="color: #f59e0b;"></i>
            <span>PixelBot AI Voice</span>
        </div>
        <div class="controls">
            <button class="ai-header-icon" id="voiceMuteBtn" onclick="toggleSpeechOutput()" title="Bật/Tắt giọng đọc AI (Loa)">
                <i class="fas fa-volume-up"></i>
            </button>
            <button class="ai-header-icon" id="micBtn" onclick="toggleMic()" title="Nói qua Micro 🎤">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="ai-header-icon" onclick="toggleApiKeyBox()" title="Cấu hình Gemini API Key">
                <i class="fas fa-key"></i>
            </button>
            <button class="ai-header-icon" onclick="toggleAiChat()"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <!-- Optional API Key Input -->
    <div id="apiKeyBox" class="api-key-box">
        <strong>🔑 Cấu hình Gemini API Key (Tùy chọn):</strong>
        <input type="text" id="geminiApiKey" placeholder="Dán Gemini API Key tại đây (Để trống dùng AI mẫu)" onchange="saveApiKey()">
    </div>
    
    <div id="voiceStatus" class="voice-status-indicator">🎙️ Đang lắng nghe giọng nói của bạn...</div>

    <div id="aiChatBody" class="ai-chat-body">
        <div class="chat-msg bot">
            👋 Xin chào! Tôi là <strong>PixelBot AI</strong> - Trợ lý mua hàng bằng Giọng nói & Trí tuệ nhân tạo của PixelGear.<br>
            Bạn có thể <strong>gõ câu hỏi</strong> hoặc bấm nút 🎤 <strong>Micro</strong> để nói trực tiếp với tôi!
            <div class="ai-quick-prompts">
                <span class="quick-chip" onclick="sendQuickAiMsg('🔥 Sản phẩm bán chạy')">🔥 Sản phẩm hot</span>
                <span class="quick-chip" onclick="sendQuickAiMsg('🎁 Gợi ý quà tặng')">🎁 Quà tặng</span>
                <span class="quick-chip" onclick="sendQuickAiMsg('👕 Áo thun & Hoodie')">👕 Quần áo</span>
                <span class="quick-chip" onclick="sendQuickAiMsg('🚚 Phí giao hàng')">🚚 Phí ship</span>
            </div>
        </div>
    </div>

    <form class="ai-chat-footer" onsubmit="handleAiSubmit(event)">
        <input type="text" id="aiInput" placeholder="Hỏi AI hoặc bấm Micro 🎤 để nói..." autocomplete="off">
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>

<script>
let isSpeechEnabled = true;
let isListening = false;
let recognition = null;
let synth = window.speechSynthesis;

// Product Map Data for Instant Pattern Matching & AI Recommendations
const pixelgearProducts = [
    { id: 1, name: "Mũ lưỡi trai Minecraft Enderman", price: 19.95, category: "accessories", keywords: ["mũ", "nón", "enderman hat", "mũ lưỡi trai"], url: "product_detail.php?id=1" },
    { id: 2, name: "Áo thun Enderman Eyes unisex", price: 22.95, category: "clothing", keywords: ["áo thun", "tshirt", "áo phông", "enderman eyes"], url: "product_detail.php?id=2" },
    { id: 3, name: "Áo nỉ Kids Cat Hoodie", price: 34.95, category: "clothing", keywords: ["áo nỉ", "hoodie trẻ em", "áo mèo", "cat hoodie"], url: "product_detail.php?id=3" },
    { id: 4, name: "Áo Hoodie Fox Adult", price: 49.95, category: "clothing", keywords: ["hoodie người lớn", "áo cáo", "fox hoodie"], url: "product_detail.php?id=4" },
    { id: 5, name: "Balo học sinh Creeper Face", price: 39.95, category: "accessories", keywords: ["balo", "chống nước", "creeper backpack"], url: "product_detail.php?id=5" },
    { id: 6, name: "Đèn ngọn đuốc Minecraft treo tường", price: 29.95, category: "decor", keywords: ["đèn", "ngọn đuốc", "torch light", "treo tường"], url: "product_detail.php?id=6" },
    { id: 7, name: "Thú nhồi bông Axolotl 12-inch", price: 19.95, category: "toys", keywords: ["gấu bông", "thú nhồi bông", "axolotl", "kì giông"], url: "product_detail.php?id=7" },
    { id: 8, name: "Gấu bông Rồng Ender Dragon 15-inch", price: 24.95, category: "toys", keywords: ["rồng ender", "ender dragon", "gấu bông rồng"], url: "product_detail.php?id=8" }
];

// Initialize Speech Recognition
function initVoiceEngine() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'vi-VN';
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onstart = function() {
            isListening = true;
            document.getElementById('voiceStatus').style.display = 'block';
            document.getElementById('micBtn').classList.add('active');
        };

        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            document.getElementById('aiInput').value = transcript;
            handleAiSubmit(new Event('submit'));
        };

        recognition.onerror = function() {
            stopMic();
        };

        recognition.onend = function() {
            stopMic();
        };
    }
}
initVoiceEngine();

function toggleMic() {
    if (!recognition) {
        alert('Trình duyệt của bạn chưa hỗ trợ nhận diện giọng nói (Vui lòng dùng Chrome hoặc Edge).');
        return;
    }
    if (isListening) {
        recognition.stop();
        stopMic();
    } else {
        try {
            recognition.start();
        } catch(e) {}
    }
}

function stopMic() {
    isListening = false;
    document.getElementById('voiceStatus').style.display = 'none';
    document.getElementById('micBtn').classList.remove('active');
}

function toggleSpeechOutput() {
    isSpeechEnabled = !isSpeechEnabled;
    const btn = document.getElementById('voiceMuteBtn');
    if (isSpeechEnabled) {
        btn.innerHTML = '<i class="fas fa-volume-up"></i>';
        btn.style.color = '#ffffff';
    } else {
        btn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        btn.style.color = '#cbd5e1';
        if (synth) synth.cancel();
    }
}

function speakText(text) {
    if (!isSpeechEnabled || !synth) return;
    synth.cancel(); // Stop any active speech
    
    // Clean HTML tags for speech synthesizer
    const cleanText = text.replace(/<[^>]*>?/gm, '');
    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.lang = 'vi-VN';
    utterance.rate = 1.1;
    synth.speak(utterance);
}

function toggleApiKeyBox() {
    const box = document.getElementById('apiKeyBox');
    box.style.display = (box.style.display === 'block') ? 'none' : 'block';
}

function saveApiKey() {
    const key = document.getElementById('geminiApiKey').value.trim();
    localStorage.setItem('pixelgear_gemini_key', key);
}

// Load saved key
if (localStorage.getItem('pixelgear_gemini_key')) {
    document.getElementById('geminiApiKey').value = localStorage.getItem('pixelgear_gemini_key');
}

function toggleAiChat() {
    const modal = document.getElementById('aiChatModal');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
        if (synth) synth.cancel();
    } else {
        modal.style.display = 'flex';
        document.getElementById('aiInput').focus();
    }
}

function sendQuickAiMsg(text) {
    document.getElementById('aiInput').value = text;
    handleAiSubmit(new Event('submit'));
}

async function handleAiSubmit(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('aiInput');
    const msg = input.value.trim();
    if (!msg) return;

    const chatBody = document.getElementById('aiChatBody');
    
    // User Message
    const userDiv = document.createElement('div');
    userDiv.className = 'chat-msg user';
    userDiv.textContent = msg;
    chatBody.appendChild(userDiv);
    
    input.value = '';
    chatBody.scrollTop = chatBody.scrollHeight;

    // Thinking indicator
    const thinkingDiv = document.createElement('div');
    thinkingDiv.className = 'chat-msg bot';
    thinkingDiv.id = 'aiThinking';
    thinkingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PixelBot AI đang suy nghĩ...';
    chatBody.appendChild(thinkingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    const apiKey = document.getElementById('geminiApiKey').value.trim();
    let responseText = "";

    if (apiKey) {
        try {
            responseText = await fetchGeminiAi(msg, apiKey);
        } catch (err) {
            responseText = "⚠️ <strong>Lỗi kết nối Gemini API:</strong> " + (err.message || 'Vui lòng kiểm tra lại API Key.');
        }
    } else {
        await new Promise(r => setTimeout(r, 400));
        responseText = processMockAi(msg);
    }

    // Remove thinking
    const thinkEl = document.getElementById('aiThinking');
    if (thinkEl) thinkEl.remove();

    // Render Bot Message
    const botDiv = document.createElement('div');
    botDiv.className = 'chat-msg bot';
    botDiv.innerHTML = responseText;
    chatBody.appendChild(botDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    // Speak out loud
    speakText(responseText);
}

async function fetchGeminiAi(query, key) {
    const models = ['gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash'];
    const sysPrompt = `Bạn là Trợ lý AI bán hàng PixelBot của cửa hàng đồ chơi & thời trang Minecraft PixelGear. Hãy trả lời ngắn gọn, thân thiện bằng tiếng Việt và gợi ý đúng sản phẩm. Các sản phẩm cửa hàng có:
1. Mũ lưỡi trai Minecraft Enderman ($19.95) - link: product_detail.php?id=1
2. Áo thun Enderman Eyes unisex ($22.95) - link: product_detail.php?id=2
3. Áo nỉ Kids Cat Hoodie ($34.95) - link: product_detail.php?id=3
4. Áo Hoodie Fox Adult ($49.95) - link: product_detail.php?id=4
5. Balo học sinh Creeper Face ($39.95) - link: product_detail.php?id=5
6. Đèn ngọn đuốc Minecraft treo tường ($29.95) - link: product_detail.php?id=6
7. Thú nhồi bông Axolotl ($19.95) - link: product_detail.php?id=7
8. Gấu bông Rồng Ender Dragon ($24.95) - link: product_detail.php?id=8`;

    let lastError = null;

    for (const model of models) {
        try {
            const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${key}`;
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{ role: "user", parts: [{ text: sysPrompt + "\nKhách hỏi: " + query }] }]
                })
            });

            const data = await res.json();

            if (data.error) {
                lastError = data.error.message || "API Key không hợp lệ";
                continue;
            }

            if (data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts[0]) {
                let text = data.candidates[0].content.parts[0].text;
                
                // Format Markdown to HTML
                text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
                text = text.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" style="color:#15803d; font-weight:bold;">$1</a>');
                text = text.replace(/\n/g, '<br>');
                
                return text;
            }
        } catch (e) {
            lastError = e.message;
        }
    }

    return `⚠️ <strong>Thông báo Gemini API:</strong> ${lastError || 'Không thể kết nối Gemini API'}. Vui lòng kiểm tra lại API Key trên Google AI Studio.`;
}

function processMockAi(rawQuery) {
    const q = rawQuery.toLowerCase();
    
    // Check if query matches any specific product keywords
    for (const p of pixelgearProducts) {
        if (p.keywords.some(k => q.includes(k))) {
            return `🛍️ <strong>Tôi tìm thấy sản phẩm bạn quan tâm:</strong><br>` +
                   `• <a href="${p.url}" style="color:#15803d; font-weight:700; font-size:14px;">${p.name}</a> ($${p.price.toFixed(2)})<br>` +
                   `👉 <a href="${p.url}" style="color:#2563eb; font-weight:600;">Bấm vào đây để xem chi tiết & mua ngay!</a>`;
        }
    }

    if (q.includes('hot') || q.includes('bán chạy') || q.includes('nổi bật')) {
        return '🔥 <strong>Top 3 sản phẩm Bán Chạy Nhất:</strong><br>' +
               '1. <a href="product_detail.php?id=1" style="color:#15803d; font-weight:700;">Mũ lưỡi trai Minecraft Enderman</a> ($19.95)<br>' +
               '2. <a href="product_detail.php?id=2" style="color:#15803d; font-weight:700;">Áo thun Enderman Eyes unisex</a> ($22.95)<br>' +
               '3. <a href="product_detail.php?id=7" style="color:#15803d; font-weight:700;">Thú nhồi bông Axolotl</a> ($19.95)<br>' +
               '👉 <a href="products.php" style="color:#2563eb; font-weight:600;">Xem toàn bộ danh mục sản phẩm</a>';
    }

    if (q.includes('quà') || q.includes('tặng') || q.includes('gợi ý')) {
        return '🎁 <strong>Gợi ý quà tặng độc đáo từ AI PixelBot:</strong><br>' +
               '• <em>Cho bạn bè / Người lớn:</em> <a href="product_detail.php?id=4" style="color:#15803d; font-weight:700;">Áo Hoodie Cáo Minecraft Fox</a><br>' +
               '• <em>Cho học sinh / trẻ em:</em> <a href="product_detail.php?id=5" style="color:#15803d; font-weight:700;">Balo Creeper Face chống nước</a><br>' +
               '• <em>Decor phòng game:</em> <a href="product_detail.php?id=6" style="color:#15803d; font-weight:700;">Đèn Ngọn Đuốc Minecraft Treo Tường</a>';
    }

    if (q.includes('ship') || q.includes('phí') || q.includes('giao hàng')) {
        return '🚚 <strong>Chính sách giao hàng & Phí ship PixelGear:</strong><br>' +
               '• Hà Nội: 20.000 VNĐ (~$0.8)<br>' +
               '• TP. HCM / Hải Phòng: 25.000 VNĐ (~$1.0)<br>' +
               '• Đà Nẵng / Cần Thơ: 30.000 VNĐ (~$1.2)<br>' +
               '💡 Nhập mã ưu đãi <strong>FREESHIP</strong> tại bước thanh toán để giảm ngay $2.00!';
    }

    if (q.includes('áo') || q.includes('quần') || q.includes('hoodie')) {
        return '👕 <strong>Danh mục Quần Áo & Hoodies:</strong><br>' +
               'Chất liệu 100% Cotton thoáng mát, chuẩn Minecraft Official. Xem tại <a href="products.php?category=clothing" style="color:#15803d; font-weight:700;">Danh mục Quần Áo</a>!';
    }

    return `🤖 PixelBot AI đã lắng nghe câu hỏi <em>"${rawQuery.replace(/</g, "&lt;")}"</em> của bạn!<br>` +
           `Bạn có thể tham khảo đầy đủ các sản phẩm thời trang, gấu bông và phụ kiện game Minecraft tại <a href="products.php" style="color:#15803d; font-weight:700;">Cửa hàng PixelGear</a> hoặc nhập mã <strong>WELCOME15</strong> để giảm 15%!`;
}
</script>
