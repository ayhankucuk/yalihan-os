{{-- Context7 Voice Search Button Component --}}
<div x-data="voiceSearchComponent()" class="voice-search-container">
    <button @click="toggleListening()"
        :class="{
            'voice-search-btn': true,
            'listening': isListening,
            'processing': isProcessing
        }"
        :disabled="isProcessing"
        class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50">
        <svg x-show="!isListening" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z">
            </path>
        </svg>

        <svg x-show="isListening" class="w-5 h-5 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 1a3 3 0 013 3v8a3 3 0 01-6 0V4a3 3 0 013-3z"></path>
        </svg>

        <span x-text="buttonText"></span>
    </button>

    {{-- Voice Search Results --}}
    <div x-show="showResults" x-transition class="voice-search-results mt-4 p-4 bg-white rounded-lg shadow-lg border dark:bg-slate-900">
        <h3 class="text-lg font-semibold mb-2">🎤 Sesli Arama Sonuçları</h3>

        <div x-show="transcript" class="mb-3">
            <p class="text-sm text-gray-600">Tanınan metin:</p>
            <p class="font-medium" x-text="transcript"></p>
        </div>

        <div x-show="isProcessing" class="flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-orange-500 mr-2"></div>
            <span class="text-sm text-gray-600">AI analiz ediyor...</span>
        </div>

        <div x-show="searchResults.length > 0 && !isProcessing" class="space-y-2">
            <p class="text-sm font-medium text-green-600">
                <span x-text="searchResults.length"></span> sonuç bulundu
            </p>
            <div class="space-y-2">
                <template x-for="result in searchResults" :key="result.id">
                    <div class="p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <h4 class="font-medium" x-text="result.title"></h4>
                        <p class="text-sm text-gray-600" x-text="result.location"></p>
                        <p class="text-sm font-semibold text-orange-600" x-text="result.price"></p>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="error" class="text-red-600 text-sm mt-2">
            <p x-text="error"></p>
        </div>
    </div>
</div>

<script>
    function voiceSearchComponent() {
        return {
            isListening: false,
            isProcessing: false,
            transcript: '',
            searchResults: [],
            error: '',
            showResults: false,
            recognition: null,

            get buttonText() {
                if (this.isProcessing) return 'İşleniyor...';
                if (this.isListening) return 'Dinleniyor...';
                return 'Sesli Arama';
            },

            init() {
                // Web Speech API kontrolü
                if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                    this.error = 'Tarayıcınız sesli komutları desteklemiyor';
                    return;
                }

                this.setupSpeechRecognition();
            },

            setupSpeechRecognition() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new SpeechRecognition();

                this.recognition.lang = 'tr-TR';
                this.recognition.continuous = false;
                this.recognition.interimResults = false;
                this.recognition.maxAlternatives = 1;

                this.recognition.onstart = () => {
                    this.isListening = true;
                    this.error = '';
                    this.showResults = false;
                };

                this.recognition.onresult = (event) => {
                    this.transcript = event.results[0][0].transcript;
                    this.processVoiceCommand(this.transcript);
                };

                this.recognition.onerror = (event) => {
                    this.error = this.getErrorMessage(event.error);
                    this.isListening = false;
                    this.isProcessing = false;
                };

                this.recognition.onend = () => {
                    this.isListening = false;
                };
            },

            toggleListening() {
                if (this.isListening) {
                    this.recognition.stop();
                } else {
                    this.recognition.start();
                }
            },

            async processVoiceCommand(command) {
                this.isProcessing = true;
                this.showResults = true;
                this.error = '';

                try {
                    const response = await fetch('/api/ai/public/voice-test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            command: command
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.searchResults = data.results.properties || [];
                        this.showNotification('Sesli arama başarılı!', 'success');
                    } else {
                        this.error = data.message || 'Sesli arama başarısız';
                    }
                } catch (error) {
                    this.error = 'Sesli arama hatası: ' + error.message;
                    console.error('Voice search error:', error);
                } finally {
                    this.isProcessing = false;
                }
            },

            getErrorMessage(error) {
                const errorMessages = {
                    'no-speech': 'Konuşma algılanamadı. Lütfen tekrar deneyin.',
                    'audio-capture': 'Mikrofon erişimi sağlanamadı.',
                    'not-allowed': 'Mikrofon izni verilmedi.',
                    'network': 'Ağ hatası. İnternet bağlantınızı kontrol edin.',
                    'aborted': 'Sesli arama iptal edildi.',
                    'language-not-supported': 'Türkçe dil desteği bulunamadı.'
                };

                return errorMessages[error] || 'Bilinmeyen hata: ' + error;
            },

            showNotification(message, type) {
                // Neo notification system
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700 fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700-${type} fixed top-4 right-4 z-50`;
                notification.innerHTML = `
                <div class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700-content bg-white p-4 rounded-lg shadow-lg border-l-4 ${
                    type === 'success' ? 'border-green-500' :
                    type === 'error' ? 'border-red-500' : 'border-blue-500'
                }">
                    <div class="flex items-center">
                        <i class="fas fa-${
                            type === 'success' ? 'check-circle text-green-500' :
                            type === 'error' ? 'exclamation-circle text-red-500' :
                            'info-circle text-blue-500'
                        } mr-2"></i>
                        <span class="text-gray-800 dark:text-slate-200">${message}</span>
                    </div>
                </div>
            `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        }
    }
</script>

<style>
    .voice-search-container {
        position: relative;
    }

    .voice-search-btn.listening {
        animation: pulse 2s infinite;
        background-color: #ea580c;
    }

    .voice-search-btn.processing {
        background-color: #9ca3af;
        cursor: not-allowed;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .voice-search-results {
        max-height: 400px;
        overflow-y: auto;
    }

    .fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700 {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>
