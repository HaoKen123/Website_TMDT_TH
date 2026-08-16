<!-- PixelBot AI Assistant (Steve Minecraft Avatar & Live SQL Integration) -->
<style>
    .ai-chat-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 110px;
        height: 55px;
        background: transparent;
        border: none;
        box-shadow: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: transform 0.2s ease;
        padding: 0;
    }
    .ai-chat-btn img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .ai-chat-btn:hover {
        transform: scale(1.1);
    }
    .ai-chat-modal {
        position: fixed;
        bottom: 95px;
        right: 25px;
        width: 390px;
        max-width: calc(100vw - 30px);
        height: 530px;
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
        padding: 12px 16px;
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
    .ai-chat-header .title img {
        width: 32px;
        height: 32px;
        border: none;
    }
    .ai-chat-header .controls {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .ai-header-icon {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #ffffff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
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
        max-width: 88%;
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
        margin-top: 10px;
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
        padding: 4px;
        background: #f0fdf4;
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
    @media (max-width: 768px) {
        .ai-chat-btn {
            bottom: 15px;
            right: 15px;
            width: 90px;
            height: 50px;
        }
        .ai-chat-modal {
            right: 0;
            bottom: 0;
            left: 0;
            width: 100vw;
            max-width: 100vw;
            height: 82vh;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }
    }
</style>

<!-- Floating Steve Chatbot Button -->
<button class="ai-chat-btn" onclick="toggleAiChat()" title="Trợ lý Steve & Creeper AI Minecraft">
    <img src="images/steve_head.png?v=<?php echo time(); ?>" alt="Steve AI Avatar">
</button>

<!-- Chat Modal Window -->
<div id="aiChatModal" class="ai-chat-modal">
    <div class="ai-chat-header">
        <div class="title">
            <img src="images/steve_head.png?v=<?php echo time(); ?>" alt="Steve Head">
            <span>Trợ lý AI PixelGear</span>
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
        <input type="text" id="geminiApiKey" placeholder="Dán Gemini API Key tại đây (Để trống dùng AI CSDL SQL)" onchange="saveApiKey()">
    </div>
    
    <div id="voiceStatus" class="voice-status-indicator">🎙️ Đang lắng nghe giọng nói của bạn...</div>

    <div id="aiChatBody" class="ai-chat-body">
        <div class="chat-msg bot">
            👋 Xin chào! Tôi là <strong>Trợ lý AI của PixelGear</strong>.<br><br>
            Tôi có thể tìm kiếm sản phẩm nhanh chóng, hỗ trợ đọc mô tả và gợi ý mã giảm giá phù hợp cho bạn!
            <div class="ai-quick-prompts">
                <?php
                // Fetch dynamic categories for AI chips
                try {
                    global $pdo;
                    $stmtAiCat = $pdo->query("SELECT * FROM categories WHERE status = 1 LIMIT 3");
                    $aiCats = $stmtAiCat->fetchAll(PDO::FETCH_ASSOC);
                    $icon_map = ['👕', '🎒', '🧸', '🎮', '🧢', '🎁'];
                    $i = 0;
                    foreach ($aiCats as $c) {
                        $icon = $icon_map[$i % count($icon_map)];
                        $catName = htmlspecialchars($c['name']);
                        echo "<span class=\"quick-chip\" onclick=\"sendQuickAiMsg('{$icon} Tìm {$catName}')\">{$icon} {$catName}</span> ";
                        $i++;
                    }
                } catch (Exception $e) {}
                ?>
                <span class="quick-chip" onclick="sendQuickAiMsg('🎁 Mã giảm giá')">🎁 Mã giảm giá</span>
            </div>
        </div>
    </div>

    <form class="ai-chat-footer" onsubmit="handleAiSubmit(event)">
        <input type="text" id="aiInput" placeholder="Hỏi Steve AI hoặc bấm Micro 🎤 để nói..." autocomplete="off">
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>

<script>
let isSpeechEnabled = true;
let isListening = false;
let recognition = null;
let synth = window.speechSynthesis;

// Global Quick View Launcher for Chatbot
window.openQuickViewModal = async function(productId) {
    const quickViewModal = document.getElementById('quickViewModal');
    const qvTitle = document.getElementById('qvTitle');
    const qvPrice = document.getElementById('qvPrice');
    const qvMainImg = document.getElementById('qvMainImg');
    const qvThumb1 = document.getElementById('qvThumb1');
    const qvDescription = document.getElementById('qvDescription');
    const qvQtyInput = document.getElementById('qvQtyInput');

    if (!quickViewModal) {
        window.location.href = 'product_detail.php?id=' + productId;
        return;
    }

    try {
        const response = await fetch(`get_product.php?id=${productId}`);
        const data = await response.json();

        if (data.success) {
            const p = data.product;
            if (qvTitle) qvTitle.textContent = p.name;
            if (qvPrice) qvPrice.textContent = p.price_formatted || `$${parseFloat(p.price).toFixed(2)}`;
            if (qvMainImg) qvMainImg.src = p.image_url;
            if (qvThumb1) qvThumb1.src = p.image_url;
            if (qvDescription) qvDescription.textContent = p.description || 'Sản phẩm Minecraft chính hãng.';
            if (qvQtyInput) qvQtyInput.value = 1;

            quickViewModal.classList.add('active');

            if (window.speakText && p.description) {
                speakText(`Đang mở xem nhanh sản phẩm ${p.name}. Mô tả: ${p.description}`);
            }
        }
    } catch (err) {
        console.error('Lỗi Quick View:', err);
    }
};

// Speech Recognition Engine
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

        recognition.onerror = function() { stopMic(); };
        recognition.onend = function() { stopMic(); };
    }
}
initVoiceEngine();

function toggleMic() {
    if (!recognition) {
        if (window.showCustomNotice) showCustomNotice('Trình duyệt chưa hỗ trợ nhận diện giọng nói (Khuyên dùng Chrome/Edge).', 'warning');
        return;
    }
    if (isListening) {
        recognition.stop();
        stopMic();
    } else {
        try { recognition.start(); } catch(e) {}
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
    synth.cancel();
    const cleanText = text.replace(/<[^>]*>?/gm, '');
    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.lang = 'vi-VN';
    utterance.rate = 1.05;
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

if (localStorage.getItem('pixelgear_gemini_key')) {
    const k = localStorage.getItem('pixelgear_gemini_key');
    const el = document.getElementById('geminiApiKey');
    if (el) el.value = k;
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
    
    // Render User Message
    const userDiv = document.createElement('div');
    userDiv.className = 'chat-msg user';
    userDiv.textContent = msg;
    chatBody.appendChild(userDiv);
    
    input.value = '';
    chatBody.scrollTop = chatBody.scrollHeight;

    // Thinking Indicator
    const thinkingDiv = document.createElement('div');
    thinkingDiv.className = 'chat-msg bot';
    thinkingDiv.id = 'aiThinking';
    thinkingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Steve AI đang truy vấn CSDL SQL...';
    chatBody.appendChild(thinkingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    const apiKey = localStorage.getItem('pixelgear_gemini_key') || '';
    let responseText = "";
    let autoQuickViewId = null;

    try {
        const res = await fetch('ai_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: msg, api_key: apiKey })
        });
        const data = await res.json();
        if (data.success) {
            responseText = data.reply;
            autoQuickViewId = data.auto_quick_view_id;
        } else {
            responseText = '⚠️ ' + data.message;
        }
    } catch (err) {
        responseText = '⚠️ Có lỗi kết nối tới Server AI CSDL.';
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

    // Auto Trigger Quick View if requested!
    if (autoQuickViewId) {
        setTimeout(() => {
            window.openQuickViewModal(autoQuickViewId);
        }, 800);
    }
}
</script>
