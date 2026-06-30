{{-- AI Chat Widget Component --}}
<div id="ai-chat-widget" class="fixed bottom-6 right-6 z-50">
    {{-- Chat Toggle Button --}}
    <button id="chat-toggle"
        class="group relative bg-gradient-to-r from-blue-600 to-purple-600 text-white
                   rounded-full w-16 h-16 flex items-center justify-center shadow-lg
                   hover:shadow-xl transform hover:scale-105 transition-all duration-300
                   animate-pulse hover:animate-none">
        {{-- AI Icon --}}
        <svg class="w-8 h-8 group-hover:scale-110 transition-transform duration-300" fill="currentColor"
            viewBox="0 0 24 24">
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
        </svg>

        {{-- Notification Badge --}}
        <div
            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full
                    flex items-center justify-center text-xs font-bold animate-bounce">
            AI
        </div>

        {{-- Floating Particles --}}
        <div class="absolute inset-0 rounded-full overflow-hidden">
            <div class="absolute top-2 left-2 w-1 h-1 bg-white/50 rounded-full animate-ping dark:bg-slate-900/50"></div>
            <div class="absolute bottom-3 right-3 w-1 h-1 bg-white/50 rounded-full animate-ping dark:bg-slate-900/50"
                style="animation-delay: 0.5s;"></div>
            <div class="absolute top-4 right-2 w-1 h-1 bg-white/50 rounded-full animate-ping dark:bg-slate-900/50"
                style="animation-delay: 1s;"></div>
        </div>
    </button>

    {{-- Chat Panel --}}
    <div id="chat-panel"
        class="hidden absolute bottom-20 right-0 w-80 h-96 bg-white rounded-2xl shadow-2xl
                border border-gray-200 flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3 dark:bg-slate-900/20">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">AI Asistan</h3>
                    <p class="text-xs text-blue-100">Emlak uzmanınız</p>
                </div>
            </div>
            <button id="chat-close" class="hover:bg-white/20 rounded-full p-1 transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        {{-- Messages Area --}}
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto bg-gray-50 dark:bg-slate-900">
            {{-- Welcome Message --}}
            <div class="mb-4">
                <div class="bg-white rounded-lg p-3 shadow-sm border-l-4 border-blue-500 dark:bg-slate-900 dark:shadow-none">
                    <div class="flex items-start">
                        <div
                            class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800 dark:text-slate-200">
                                Merhaba! Ben Yalıhan Emlak AI asistanıyım. Size nasıl yardımcı olabilirim?
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    class="ai-suggestion bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                                    Satılık ev arıyorum
                                </button>
                                <button
                                    class="ai-suggestion bg-purple-100 text-purple-700 text-xs px-3 py-1 rounded-full hover:bg-purple-200 transition-colors">
                                    Emlak değerlendirme
                                </button>
                                <button
                                    class="ai-suggestion bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full hover:bg-green-200 transition-colors">
                                    Piyasa analizi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-4 border-t border-gray-200 bg-white dark:bg-slate-900 dark:border-slate-700">
            <div class="flex items-center space-x-2">
                <input id="chat-input" type="text" placeholder="Mesajınızı yazın..."
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button id="chat-send"
                    class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full w-10 h-10 flex items-center justify-center hover:shadow-lg transition-all duration-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-center">
                AI destekli anlık yanıtlar
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatToggle = document.getElementById('chat-toggle');
        const chatPanel = document.getElementById('chat-panel');
        const chatClose = document.getElementById('chat-close');
        const chatInput = document.getElementById('chat-input');
        const chatSend = document.getElementById('chat-send');
        const chatMessages = document.getElementById('chat-messages');

        // Toggle chat panel
        chatToggle.addEventListener('click', function() {
            chatPanel.classList.toggle('hidden');
            if (!chatPanel.classList.contains('hidden')) {
                chatInput.focus();
            }
        });

        // Close chat panel
        chatClose.addEventListener('click', function() {
            chatPanel.classList.add('hidden');
        });

        // AI Suggestions
        document.querySelectorAll('.ai-suggestion').forEach(button => {
            button.addEventListener('click', function() {
                const suggestion = this.textContent;
                addMessage(suggestion, 'user');
                handleAIResponse(suggestion);
            });
        });

        // Send message
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message) {
                addMessage(message, 'user');
                chatInput.value = '';
                handleAIResponse(message);
            }
        }

        chatSend.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Add message to chat
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4';

            if (sender === 'user') {
                messageDiv.innerHTML = `
                <div class="flex justify-end">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-3 max-w-xs">
                        <p class="text-sm">${text}</p>
                    </div>
                </div>
            `;
            } else {
                messageDiv.innerHTML = `
                <div class="bg-white rounded-lg p-3 shadow-sm border-l-4 border-blue-500 dark:bg-slate-900 dark:shadow-none">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800 dark:text-slate-200">${text}</p>
                        </div>
                    </div>
                </div>
            `;
            }

            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Handle AI Response (Mock responses for demo)
        function handleAIResponse(userMessage) {
            // Show typing indicator
            setTimeout(() => {
                const responses = {
                    'Satılık ev arıyorum': 'Size uygun satılık evleri bulabilirim. Hangi bölgeyi tercih edersiniz ve bütçeniz ne kadardır?',
                    'Emlak değerlendirme': 'Emlak değerlendirme hizmeti için mülkünüzün detaylarını paylaşabilir misiniz? Konum, m², oda sayısı gibi bilgiler yardımcı olacaktır.',
                    'Piyasa analizi': 'Güncel piyasa analizi için hangi bölge ve emlak türü hakkında bilgi almak istiyorsunuz?'
                };

                let response = responses[userMessage] ||
                    'Anlayabilmek için daha spesifik bilgi verebilir misiniz? Size daha iyi yardımcı olabilmek istiyorum.';

                if (userMessage.toLowerCase().includes('merhaba') || userMessage.toLowerCase().includes(
                        'selam')) {
                    response = 'Merhaba! Size emlak konusunda nasıl yardımcı olabilirim?';
                } else if (userMessage.toLowerCase().includes('fiyat')) {
                    response =
                        'Emlak fiyatları için güncel piyasa verilerini analiz edebilirim. Hangi bölge için fiyat bilgisi istiyorsunuz?';
                } else if (userMessage.toLowerCase().includes('kiralık')) {
                    response =
                        'Kiralık emlak aramanızda size yardımcı olabilirim. Tercih ettiğiniz bölge, bütçe ve özellikler nelerdir?';
                }

                addMessage(response, 'ai');
            }, 1000);
        }
    });
</script>
