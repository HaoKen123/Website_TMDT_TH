<!-- PixelBot AI Assistant (Steve Minecraft Avatar & Smart SQL Integration) -->
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
        width: 410px;
        max-width: calc(100vw - 30px);
        height: 560px;
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
        max-width: 90%;
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
        padding: 5px 10px;
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
        padding: 5px;
        background: #f0fdf4;
        display: none;
        border-bottom: 1px solid #bbf7d0;
    }

    /* Settings Panel */
    .ai-settings-panel {
        background: #ffffff;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        display: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        font-size: 12px;
        max-height: 320px;
        overflow-y: auto;
    }
    .ai-settings-panel h4 {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .setting-row {
        margin-bottom: 10px;
    }
    .setting-row label {
        display: block;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }
    .setting-row select, .setting-row input[type="text"] {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        outline: none;
        background: #f8fafc;
        box-sizing: border-box;
    }
    .setting-row select:focus, .setting-row input:focus {
        border-color: #15803d;
        background: #fff;
    }
    .setting-row-flex {
        display: flex;
        gap: 8px;
        align-items: center;
    }

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
            height: 84vh;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }
    }
</style>

<!-- Floating Steve Chatbot Button -->
<button class="ai-chat-btn" onclick="toggleAiChat()" title="Trợ lý Steve AI Minecraft">
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
            <button class="ai-header-icon" id="voiceMuteBtn" onclick="toggleSpeechOutput()" title="Bật/Tắt âm thanh">
                <i class="fas fa-volume-up"></i>
            </button>
            <button class="ai-header-icon" id="micBtn" onclick="toggleMic()" title="Nói qua Micro 🎤">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="ai-header-icon" id="settingsToggleBtn" onclick="toggleSettingsPanel()" title="Cài đặt Micro & Giọng đọc AI ⚙️">
                <i class="fas fa-cog"></i>
            </button>
            <button class="ai-header-icon" onclick="toggleAiChat()"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <!-- Cài đặt Bánh Răng (Settings Panel) -->
    <div id="aiSettingsPanel" class="ai-settings-panel">
        <h4>
            <span><i class="fas fa-sliders-h" style="color: #15803d;"></i> Cài đặt Micro & Giọng đọc</span>
            <button onclick="toggleSettingsPanel()" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:14px;">&times;</button>
        </h4>

        <!-- Chọn Thiết bị Micro -->
        <div class="setting-row">
            <label><i class="fas fa-microphone-alt"></i> Thiết bị Micro (Input Device):</label>
            <select id="micDeviceSelect" onchange="saveAudioSettings()">
                <option value="default">Mặc định hệ thống</option>
            </select>
        </div>

        <!-- Chọn Ngôn ngữ nhận diện -->
        <div class="setting-row">
            <label><i class="fas fa-language"></i> Ngôn ngữ nhận diện giọng nói (STT):</label>
            <select id="sttLangSelect" onchange="saveAudioSettings()">
                <option value="vi-VN" selected>🇻🇳 Tiếng Việt (vi-VN)</option>
                <option value="en-US">🇺🇸 English (en-US)</option>
                <option value="ja-JP">🇯🇵 日本語 (ja-JP)</option>
            </select>
        </div>

        <!-- Chọn Giọng đọc AI -->
        <div class="setting-row">
            <label><i class="fas fa-headset"></i> Giọng đọc AI (TTS Voice):</label>
            <div class="setting-row-flex">
                <select id="ttsVoiceSelect" onchange="saveAudioSettings()" style="flex:1;">
                    <option value="">Đang tải danh sách giọng đọc...</option>
                </select>
                <button type="button" onclick="testVoicePlayback()" style="background:#15803d; color:#fff; border:none; padding:6px 10px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer; white-space:nowrap;">
                    🔊 Thử giọng
                </button>
            </div>
        </div>

        <!-- Tốc độ đọc -->
        <div class="setting-row">
            <label><i class="fas fa-tachometer-alt"></i> Tốc độ đọc: <span id="rateLabel" style="color:#15803d; font-weight:700;">1.0x</span></label>
            <input type="range" id="speechRateInput" min="0.7" max="1.5" step="0.1" value="1.0" oninput="updateRateLabel(this.value)" onchange="saveAudioSettings()" style="width:100%;">
        </div>

        <!-- API Key Google Gemini -->
        <div class="setting-row">
            <label><i class="fas fa-key"></i> Gemini API Key (Tùy chọn):</label>
            <input type="text" id="geminiApiKey" placeholder="Dán Gemini API Key tại đây" onchange="saveAudioSettings()">
        </div>
    </div>
    
    <div id="voiceStatus" class="voice-status-indicator">🎙️ Đang lắng nghe giọng nói của bạn...</div>

    <div id="aiChatBody" class="ai-chat-body">
        <div class="chat-msg bot">
            👋 Xin chào! Tôi là <strong>Steve AI – Trợ lý PixelGear</strong>.<br><br>
            Bạn có thể hỏi tôi về thời tiết, thời gian, hoặc chọn danh mục bên dưới để xem gợi ý sản phẩm:
            <div class="ai-quick-prompts">
                <span class="quick-chip" onclick="sendQuickAiMsg('👕 Quần áo & Hoodies')">👕 Quần áo & Hoodies</span>
                <span class="quick-chip" onclick="sendQuickAiMsg('🎒 Phụ kiện Minecraft')">🎒 Phụ kiện Minecraft</span>
                <span class="quick-chip" onclick="sendQuickAiMsg('🧸 Đồ chơi & Gấu bông')">🧸 Đồ chơi & Gấu bông</span>
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
let availableVoices = [];

// 1. Tải và lọc danh sách giọng đọc TTS từ trình duyệt
function populateVoiceList() {
    if (!synth) return;
    availableVoices = synth.getVoices();
    const select = document.getElementById('ttsVoiceSelect');
    if (!select || availableVoices.length === 0) return;

    select.innerHTML = '';
    const viGroup = document.createElement('optgroup');
    viGroup.label = '🇻🇳 Giọng Tiếng Việt';
    const otherGroup = document.createElement('optgroup');
    otherGroup.label = '🌐 Giọng Ngôn Ngữ Khác';

    const savedVoiceURI = localStorage.getItem('pixelgear_tts_voice_uri') || '';
    let hasViSelected = false;

    availableVoices.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.voiceURI;
        opt.textContent = `${v.name} (${v.lang})`;

        const isVi = (v.lang && (v.lang.toLowerCase().includes('vi') || v.lang.toLowerCase().includes('vn'))) ||
                     (v.name && (v.name.toLowerCase().includes('vietnam') || v.name.toLowerCase().includes('vietnamese') || v.name.toLowerCase().includes('hoaimy') || v.name.toLowerCase().includes('namminh') || v.name.toLowerCase().includes('linh') || v.name.toLowerCase().includes('mai')));

        if (isVi) {
            viGroup.appendChild(opt);
            if (!savedVoiceURI && !hasViSelected) {
                opt.selected = true;
                hasViSelected = true;
            }
        } else {
            otherGroup.appendChild(opt);
        }

        if (savedVoiceURI && v.voiceURI === savedVoiceURI) {
            opt.selected = true;
        }
    });

    if (viGroup.children.length > 0) {
        select.appendChild(viGroup);
    }
    if (otherGroup.children.length > 0) {
        select.appendChild(otherGroup);
    }
}

if (synth) {
    populateVoiceList();
    if (synth.onvoiceschanged !== undefined) {
        synth.onvoiceschanged = populateVoiceList;
    }
}

// 2. Tải danh sách thiết bị Micro
async function populateMicDevices() {
    const micSelect = document.getElementById('micDeviceSelect');
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices || !micSelect) return;

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const audioInputs = devices.filter(d => d.kind === 'audioinput');
        if (audioInputs.length > 0) {
            micSelect.innerHTML = '';
            const savedMicId = localStorage.getItem('pixelgear_mic_id') || 'default';
            audioInputs.forEach((mic, index) => {
                const opt = document.createElement('option');
                opt.value = mic.deviceId || 'default';
                opt.textContent = mic.label || `Microphone ${index + 1}`;
                if (opt.value === savedMicId) opt.selected = true;
                micSelect.appendChild(opt);
            });
        }
    } catch (e) {
        console.log('Chưa được cấp quyền Micro để lấy tên thiết bị:', e);
    }
}

// 3. Khôi phục cấu hình từ LocalStorage
function restoreAudioSettings() {
    const savedSttLang = localStorage.getItem('pixelgear_stt_lang') || 'vi-VN';
    const sttSelect = document.getElementById('sttLangSelect');
    if (sttSelect) sttSelect.value = savedSttLang;

    const savedRate = localStorage.getItem('pixelgear_speech_rate') || '1.0';
    const rateInput = document.getElementById('speechRateInput');
    const rateLabel = document.getElementById('rateLabel');
    if (rateInput) rateInput.value = savedRate;
    if (rateLabel) rateLabel.textContent = `${savedRate}x`;

    const savedKey = localStorage.getItem('pixelgear_gemini_key') || '';
    const keyInput = document.getElementById('geminiApiKey');
    if (keyInput) keyInput.value = savedKey;
}

function updateRateLabel(val) {
    const rateLabel = document.getElementById('rateLabel');
    if (rateLabel) rateLabel.textContent = `${val}x`;
}

function saveAudioSettings() {
    const voiceSelect = document.getElementById('ttsVoiceSelect');
    if (voiceSelect && voiceSelect.value) {
        localStorage.setItem('pixelgear_tts_voice_uri', voiceSelect.value);
    }

    const sttSelect = document.getElementById('sttLangSelect');
    if (sttSelect) {
        localStorage.setItem('pixelgear_stt_lang', sttSelect.value);
        if (recognition) recognition.lang = sttSelect.value;
    }

    const micSelect = document.getElementById('micDeviceSelect');
    if (micSelect) {
        localStorage.setItem('pixelgear_mic_id', micSelect.value);
    }

    const rateInput = document.getElementById('speechRateInput');
    if (rateInput) {
        localStorage.setItem('pixelgear_speech_rate', rateInput.value);
    }

    const keyInput = document.getElementById('geminiApiKey');
    if (keyInput) {
        localStorage.setItem('pixelgear_gemini_key', keyInput.value.trim());
    }
}

function testVoicePlayback() {
    speakText('Xin chào bạn! Tôi là Steve AI, trợ lý bán hàng chính hãng PixelGear Store.');
}

function toggleSettingsPanel() {
    const panel = document.getElementById('aiSettingsPanel');
    const btn = document.getElementById('settingsToggleBtn');
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
        btn.classList.remove('active');
    } else {
        panel.style.display = 'block';
        btn.classList.add('active');
        populateVoiceList();
        populateMicDevices();
    }
}

// 4. Phát âm giọng đọc tiếng Việt theo đúng Voice đã chọn
function speakText(text) {
    if (!isSpeechEnabled || !synth) return;
    synth.cancel();

    const cleanText = text.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&');
    const utterance = new SpeechSynthesisUtterance(cleanText);
    
    // Đọc theo tốc độ lưu
    const savedRate = parseFloat(localStorage.getItem('pixelgear_speech_rate')) || 1.0;
    utterance.rate = savedRate;

    // Tìm voice được chọn hoặc voice tiếng Việt
    const savedVoiceURI = localStorage.getItem('pixelgear_tts_voice_uri') || '';
    const voices = synth.getVoices();
    
    let chosenVoice = null;
    if (savedVoiceURI) {
        chosenVoice = voices.find(v => v.voiceURI === savedVoiceURI);
    }

    if (!chosenVoice) {
        // Tự động chọn voice tiếng Việt
        chosenVoice = voices.find(v => (v.lang && (v.lang.toLowerCase().includes('vi') || v.lang.toLowerCase().includes('vn'))) || (v.name && (v.name.toLowerCase().includes('vietnam') || v.name.toLowerCase().includes('vietnamese') || v.name.toLowerCase().includes('hoaimy') || v.name.toLowerCase().includes('namminh') || v.name.toLowerCase().includes('linh') || v.name.toLowerCase().includes('mai'))));
    }

    if (chosenVoice) {
        utterance.voice = chosenVoice;
        utterance.lang = chosenVoice.lang || 'vi-VN';
    } else {
        utterance.lang = 'vi-VN';
    }

    synth.speak(utterance);
}

// 5. Thêm giỏ hàng nhanh từ thẻ sản phẩm Chatbot
window.addFromChatbot = async function(productId) {
    try {
        const res = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId + '&quantity=1'
        });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            const countBadges = document.querySelectorAll('.cart-count');
            countBadges.forEach(b => b.textContent = data.cart_count);
            const toast = document.getElementById('toast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }
            speakText('Đã thêm sản phẩm vào giỏ hàng thành công!');
        } else if (data.message) {
            alert(data.message);
        }
    } catch (err) {
        console.error('Lỗi thêm giỏ hàng:', err);
    }
};

// 6. Nhận diện giọng nói Micro
function initVoiceEngine() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        const savedSttLang = localStorage.getItem('pixelgear_stt_lang') || 'vi-VN';
        recognition.lang = savedSttLang;
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
        alert('Trình duyệt của bạn chưa hỗ trợ nhận diện giọng nói (Khuyên dùng Chrome/Edge).');
        return;
    }
    if (isListening) {
        recognition.stop();
        stopMic();
    } else {
        const savedSttLang = localStorage.getItem('pixelgear_stt_lang') || 'vi-VN';
        recognition.lang = savedSttLang;
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

function toggleAiChat() {
    const modal = document.getElementById('aiChatModal');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
        if (synth) synth.cancel();
    } else {
        modal.style.display = 'flex';
        document.getElementById('aiInput').focus();
        populateVoiceList();
        restoreAudioSettings();
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
    thinkingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Steve AI đang xử lý...';
    chatBody.appendChild(thinkingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    const apiKey = localStorage.getItem('pixelgear_gemini_key') || '';
    let responseText = "";

    try {
        const res = await fetch('ai_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: msg, api_key: apiKey })
        });
        const data = await res.json();
        if (data.success) {
            responseText = data.reply;
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

    // Speak out loud in Vietnamese
    speakText(responseText);
}

document.addEventListener('DOMContentLoaded', () => {
    restoreAudioSettings();
    populateVoiceList();
});
</script>
