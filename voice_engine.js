/**
 * Voice Engine - Web Speech API (Speech Recognition & Speech Synthesis)
 * PixelGear Official
 */

class PixelVoiceEngine {
    constructor() {
        this.synth = window.speechSynthesis;
        this.recognition = null;
        this.isListening = false;
        this.isSpeaking = false;
        this.shouldBeListening = false; // Duy trì Mic liên tục không giới hạn thời gian

        // Custom Callbacks
        this.onCommandCallback = null;
        this.onStateChangeCallback = null;

        // Configuration
        this.speechRate = 1.15;
        this.speechPitch = 1.0;
        this.speechVolume = 1.0;
        this.selectedVoiceURI = null;
        this.selectedMicId = null;

        this.initSpeechRecognition();
        this.initVoices();
    }

    initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            console.warn("Trình duyệt không hỗ trợ Web Speech API Recognition.");
            return;
        }

        this.recognition = new SpeechRecognition();
        this.recognition.lang = 'vi-VN';
        this.recognition.continuous = false;
        this.recognition.interimResults = true;
        this.recognition.maxAlternatives = 1;

        let finalTranscript = '';

        this.recognition.onstart = () => {
            if (this.isSpeaking) {
                console.log("Phát hiện AI đang nói qua loa, ngắt phiên Micro ngay!");
                try { this.recognition.stop(); } catch(e) {}
                return;
            }
            this.isListening = true;
            finalTranscript = '';
            if (this.onStateChangeCallback) this.onStateChangeCallback('LISTENING');
        };

        this.recognition.onresult = (event) => {
            let interimTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            const currentText = (finalTranscript || interimTranscript).trim();
            if (currentText && this.onStateChangeCallback) {
                this.onStateChangeCallback('TRANSCRIPT', currentText);
            }
        };

        this.recognition.onend = () => {
            this.isListening = false;

            const textToProcess = finalTranscript.trim();
            if (textToProcess && this.onCommandCallback) {
                this.onCommandCallback(textToProcess);
            }

            if (this.shouldBeListening && !this.isSpeaking) {
                setTimeout(() => {
                    if (this.shouldBeListening && !this.isListening && !this.isSpeaking) {
                        this.startListening();
                    }
                }, 300);
            } else {
                if (this.onStateChangeCallback) this.onStateChangeCallback('IDLE');
            }
        };

        this.recognition.onerror = (event) => {
            console.error("Lỗi SpeechRecognition:", event.error);
            this.isListening = false;

            if ((event.error === 'no-speech' || event.error === 'network' || event.error === 'aborted') && this.shouldBeListening && !this.isSpeaking) {
                setTimeout(() => {
                    if (this.shouldBeListening && !this.isListening && !this.isSpeaking) {
                        this.startListening();
                    }
                }, 300);
                return;
            }

            if (this.onStateChangeCallback) {
                this.onStateChangeCallback('IDLE');
            }
        };
    }

    initVoices() {
        if (!this.synth) return;
        let voices = [];
        const populateVoiceList = () => {
            voices = this.synth.getVoices();
            if (voices.length > 0) {
                this.loadVoices();
            }
        };
        populateVoiceList();
        if (this.synth.onvoiceschanged !== undefined) {
            this.synth.onvoiceschanged = populateVoiceList;
        }
    }

    loadVoices(filterSearch = '') {
        if (!this.synth) return;
        const voices = this.synth.getVoices();
        const dropdownList = document.getElementById('voice-dropdown-list');
        const dropdownText = document.getElementById('voice-dropdown-text');
        const voiceSelectInput = document.getElementById('voice-select');
        
        if (!dropdownList) return;
        dropdownList.innerHTML = '';

        const viVoices = voices.filter(v => v.lang.includes('vi') || v.lang.includes('VI'));
        const otherVoices = voices.filter(v => !v.lang.includes('vi') && !v.lang.includes('VI'));
        const sortedVoices = [...viVoices, ...otherVoices];

        const savedVoiceURI = localStorage.getItem('pixel_voice_uri');

        sortedVoices.forEach(voice => {
            if (filterSearch && !voice.name.toLowerCase().includes(filterSearch.toLowerCase()) && !voice.lang.toLowerCase().includes(filterSearch.toLowerCase())) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'dropdown-item';
            item.style.padding = '8px 12px';
            item.style.cursor = 'pointer';
            item.style.fontSize = '0.85rem';
            item.style.borderBottom = '1px solid #f0f0f0';
            
            const isVi = voice.lang.includes('vi');
            item.innerHTML = `
                <div style="font-weight:${isVi ? 'bold' : 'normal'}; color:${isVi ? '#15803d' : '#333'}">
                    ${isVi ? '🇻🇳 ' : '🌐 '}${voice.name}
                </div>
                <div style="font-size:0.7rem; color:#888;">${voice.lang}</div>
            `;

            if (savedVoiceURI === voice.voiceURI || (!savedVoiceURI && isVi && !this.selectedVoiceURI)) {
                this.selectedVoiceURI = voice.voiceURI;
                if (dropdownText) dropdownText.innerHTML = `<strong>${isVi ? '🇻🇳 ' : ''}${voice.name}</strong>`;
                if (voiceSelectInput) voiceSelectInput.value = voice.voiceURI;
            }

            item.addEventListener('click', () => {
                this.selectedVoiceURI = voice.voiceURI;
                if (voiceSelectInput) voiceSelectInput.value = voice.voiceURI;
                if (dropdownText) dropdownText.innerHTML = `<strong>${isVi ? '🇻🇳 ' : ''}${voice.name}</strong>`;
                const menu = document.getElementById('voice-dropdown-menu');
                if (menu) menu.style.display = 'none';
                localStorage.setItem('pixel_voice_uri', voice.voiceURI);
            });

            dropdownList.appendChild(item);
        });

        if (!this.selectedVoiceURI && sortedVoices.length > 0) {
            this.selectedVoiceURI = sortedVoices[0].voiceURI;
            if (dropdownText) dropdownText.textContent = sortedVoices[0].name;
        }
    }

    async loadMicrophones() {
        const micSelect = document.getElementById('mic-select');
        if (!micSelect) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioInputs = devices.filter(d => d.kind === 'audioinput');
            
            micSelect.innerHTML = '';
            if (audioInputs.length === 0) {
                micSelect.innerHTML = '<option value="">Không tìm thấy Micro</option>';
                return;
            }

            const savedMicId = localStorage.getItem('pixel_mic_id');

            audioInputs.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Microphone ${index + 1}`;
                if (savedMicId === device.deviceId) {
                    option.selected = true;
                    this.selectedMicId = device.deviceId;
                }
                micSelect.appendChild(option);
            });

            micSelect.onchange = () => {
                this.selectedMicId = micSelect.value;
                localStorage.setItem('pixel_mic_id', micSelect.value);
            };
        } catch (e) {
            console.error("Lỗi khi tải danh sách Micro:", e);
        }
    }

    startListening() {
        if (!this.recognition) {
            if (this.onStateChangeCallback) this.onStateChangeCallback('UNSUPPORTED');
            return;
        }
        if (this.isListening) return;

        this.shouldBeListening = true;
        try {
            this.recognition.start();
        } catch (e) {
            console.error("Lỗi khởi chạy Micro:", e);
        }
    }

    stopListening() {
        this.shouldBeListening = false;
        if (this.recognition && this.isListening) {
            try {
                this.recognition.stop();
            } catch(e) {}
        }
        this.isListening = false;
        if (this.onStateChangeCallback) this.onStateChangeCallback('IDLE');
    }

    speak(text, onEndCallback = null) {
        if (!this.synth) return;

        this.stopListening();
        this.synth.cancel();

        const cleanText = text.replace(/<[^>]*>?/gm, '');
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.rate = parseFloat(this.speechRate) || 1.15;
        utterance.pitch = parseFloat(this.speechPitch) || 1.0;
        utterance.volume = parseFloat(this.speechVolume) || 1.0;

        const voices = this.synth.getVoices();
        if (this.selectedVoiceURI) {
            const voice = voices.find(v => v.voiceURI === this.selectedVoiceURI);
            if (voice) utterance.voice = voice;
        } else {
            const viVoice = voices.find(v => v.lang.includes('vi'));
            if (viVoice) utterance.voice = viVoice;
        }

        utterance.onstart = () => {
            this.isSpeaking = true;
            if (this.onStateChangeCallback) this.onStateChangeCallback('SPEAKING');
        };

        utterance.onend = () => {
            this.isSpeaking = false;
            if (onEndCallback) onEndCallback();
            if (this.shouldBeListening) {
                setTimeout(() => this.startListening(), 400);
            } else {
                if (this.onStateChangeCallback) this.onStateChangeCallback('IDLE');
            }
        };

        utterance.onerror = (e) => {
            console.error("Lỗi đọc giọng nói:", e);
            this.isSpeaking = false;
            if (onEndCallback) onEndCallback();
            if (this.onStateChangeCallback) this.onStateChangeCallback('IDLE');
        };

        this.synth.speak(utterance);
    }
}
