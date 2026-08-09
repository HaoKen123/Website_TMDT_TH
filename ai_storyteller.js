/**
 * AI Storyteller & Smart Voice Engine - PixelGear Official
 * 3 Nhóm sản phẩm chính:
 * 1. Quần áo & Hoodies (clothing)
 * 2. Phụ kiện Minecraft (accessories)
 * 3. Đồ chơi & Decor (toys)
 */

class AIStoryteller {
    constructor(voiceEngine, appInstance) {
        this.voiceEngine = voiceEngine;
        this.app = appInstance;
        this.chatHistory = [];
        this.typewriterTimer = null;
        this.currentRequestId = 0;
        this.systemPrompt = `
Bạn là Trợ lý ảo bán hàng chuyên nghiệp của thương hiệu đồ chơi & thời trang Minecraft "PixelGear".
Tính cách: Thân thiện, năng động, am hiểu về tựa game Minecraft. Xưng hô là "Tôi" hoặc "PixelBot" và gọi người dùng là "Bạn" hoặc "Quý khách".

3 Nhóm chủ đề chính của cửa hàng PixelGear:
1. Quần áo & Hoodies (clothing): Áo thun Enderman Eyes unisex ($22.95), Áo Hoodie Cáo Fox ($49.95), Áo nỉ Kids Cat Hoodie ($34.95).
2. Phụ kiện Minecraft (accessories): Mũ lưỡi trai Enderman ($19.95), Balo học sinh Creeper Face ($39.95), Đèn ngọn đuốc treo tường ($29.95).
3. Đồ chơi & Decor (toys): Thú nhồi bông Kì Giông Axolotl ($19.95), Gấu bông Rồng Ender Dragon ($24.95).

Quy trình tư vấn:
- Giới thiệu ngắn gọn các món hot nhất hoặc giải đáp thắc mắc của khách.
- Khi khách hỏi phí ship: Hà Nội 20k, HCM/Đà Nẵng 25k-30k. Nhập mã FREESHIP để giảm $2.
- Trả lời bằng JSON hợp lệ:
{
    "response": "Lời thoại phát ra loa cho khách nghe.",
    "actions": [
        { "type": "SHOW_PRODUCT", "productId": 1 },
        { "type": "ADD_TO_CART", "productId": 1, "quantity": 1 }
    ]
}
`;
    }

    cancelPendingRequests() {
        this.currentRequestId++;
        if (this.typewriterTimer) {
            clearTimeout(this.typewriterTimer);
            this.typewriterTimer = null;
        }
    }

    async getAIResponse(userText, reqId) {
        const apiKeyInput = document.getElementById('gemini-api-key') || document.getElementById('geminiApiKey');
        const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';

        const indicator = document.getElementById('ai-processing');
        if (indicator) indicator.classList.add('show');

        this.chatHistory.push({ role: "user", parts: [{ text: userText }] });

        let endpoint = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${apiKey}`;

        if (!apiKey) {
            if (indicator) indicator.classList.remove('show');
            return this.mockAIResponse(userText);
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    systemInstruction: { parts: [{ text: this.systemPrompt }] },
                    contents: this.chatHistory,
                    generationConfig: {
                        temperature: 0.7,
                        responseMimeType: "application/json"
                    }
                })
            });

            if (!response.ok) throw new Error("Lỗi kết nối Gemini API");
            
            const data = await response.json();
            const aiText = data.candidates[0].content.parts[0].text;
            
            this.chatHistory.push({ role: "model", parts: [{ text: aiText }] });

            if (indicator) indicator.classList.remove('show');

            if (reqId !== this.currentRequestId) return null;

            try {
                let jsonStr = aiText;
                if (jsonStr.includes('```json')) {
                    jsonStr = jsonStr.split('```json')[1].split('```')[0].trim();
                } else if (jsonStr.includes('```')) {
                    jsonStr = jsonStr.split('```')[1].split('```')[0].trim();
                }
                return JSON.parse(jsonStr);
            } catch (e) {
                return { response: aiText, actions: [] };
            }

        } catch (error) {
            if (indicator) indicator.classList.remove('show');
            if (reqId !== this.currentRequestId) return null;
            return this.mockAIResponse(userText);
        }
    }

    mockAIResponse(rawText) {
        const text = rawText.toLowerCase().trim();
        let resp = "";
        let actions = [];

        if (!text || text.length < 2) {
            return { response: "", actions: [] };
        }

        let qty = 1;
        const numMatch = text.match(/(\d+)\s*(cái|chiếc|áo|mũ|balo|gấu|hộp|bộ)?/);
        if (numMatch) {
            qty = parseInt(numMatch[1]) || 1;
        }

        const productMap = [
            { id: 1, keywords: ["mũ", "nón", "enderman hat", "lưỡi trai"], name: "Mũ lưỡi trai Minecraft Enderman", price: 19.95 },
            { id: 2, keywords: ["áo thun", "tshirt", "enderman eyes", "áo phông"], name: "Áo thun Enderman Eyes unisex", price: 22.95 },
            { id: 3, keywords: ["áo nỉ", "cat hoodie", "áo mèo", "trẻ em"], name: "Áo nỉ Kids Cat Hoodie", price: 34.95 },
            { id: 4, keywords: ["hoodie cáo", "fox hoodie", "áo hoodie người lớn"], name: "Áo Hoodie Fox Adult", price: 49.95 },
            { id: 5, keywords: ["balo", "creeper backpack", "cặp học sinh"], name: "Balo học sinh Creeper Face", price: 39.95 },
            { id: 6, keywords: ["đèn", "ngọn đuốc", "torch light", "treo tường"], name: "Đèn ngọn đuốc Minecraft treo tường", price: 29.95 },
            { id: 7, keywords: ["gấu bông axolotl", "thú nhồi bông axolotl", "kì giông"], name: "Thú nhồi bông Axolotl 12-inch", price: 19.95 },
            { id: 8, keywords: ["rồng ender", "ender dragon", "gấu bông rồng"], name: "Gấu bông Rồng Ender Dragon", price: 24.95 }
        ];

        let matchedProduct = null;
        for (const p of productMap) {
            if (p.keywords.some(k => text.includes(k))) {
                matchedProduct = p;
                break;
            }
        }

        if (matchedProduct) {
            resp = `PixelBot đã tìm thấy ${matchedProduct.name} giá $${matchedProduct.price.toFixed(2)}. Bạn bấm xem chi tiết hoặc thêm vào giỏ nhé!`;
            actions.push({ type: "SHOW_PRODUCT", productId: matchedProduct.id });
            return new Promise(resolve => setTimeout(() => resolve({ response: resp, actions: actions }), 150));
        }

        if (text.includes("hot") || text.includes("bán chạy") || text.includes("nổi bật")) {
            resp = "Top 3 sản phẩm hot nhất PixelGear là Mũ lưỡi trai Enderman, Áo thun Enderman Eyes và Thú nhồi bông Axolotl. Bạn muốn xem món nào ạ?";
            return new Promise(resolve => setTimeout(() => resolve({ response: resp, actions: [] }), 150));
        }

        if (text.includes("quà") || text.includes("tặng") || text.includes("gợi ý")) {
            resp = "Gợi ý quà tặng tuyệt vời từ PixelBot: Balo Creeper chống nước cho các bé hoặc Áo Hoodie Fox Minecraft cho bạn bè. Bạn thích món nào?";
            return new Promise(resolve => setTimeout(() => resolve({ response: resp, actions: [] }), 150));
        }

        if (text.includes("ship") || text.includes("phí") || text.includes("giao hàng")) {
            resp = "Phí giao hàng nội thành Hà Nội là 20k, các tỉnh thành khác từ 25k-30k. Nhập mã FREESHIP tại bước thanh toán để giảm ngay $2 phí ship nhé!";
            return new Promise(resolve => setTimeout(() => resolve({ response: resp, actions: [] }), 150));
        }

        resp = `PixelBot AI đã lắng nghe câu hỏi của bạn. Bạn xem đầy đủ danh mục đồ chơi và thời trang Minecraft tại cửa hàng PixelGear hoặc nhập mã WELCOME15 để giảm 15% nhé!`;
        return new Promise(resolve => setTimeout(() => resolve({ response: resp, actions: [] }), 150));
    }
}
