document.addEventListener('DOMContentLoaded', function () {
    const apiEndpoint = '/api/v1/ai/assistant/query';

    const widgetHtml = `
        <div class="ai-assistant-widget">
            <div class="ai-assistant-chat" id="aiChat">
                <div class="ai-assistant-header">
                    <span>AI Emlak Danışmanı</span>
                    <span style="cursor:pointer" onclick="document.getElementById('aiChat').style.display='none'">✕</span>
                </div>
                <div class="ai-assistant-body" id="chatBody">
                    <div class="ai-message bot">
                        Merhaba 👋 <br> Size nasıl yardımcı olabilirim?
                    </div>
                    <div style="margin-top: 10px">
                        <small>Örnek sorular:</small><br>
                        <span class="ai-suggestion" onclick="sendSuggestion('Bodrumda villa')">Bodrumda villa</span>
                        <span class="ai-suggestion" onclick="sendSuggestion('Kiralık daire')">Kiralık daire</span>
                        <span class="ai-suggestion" onclick="sendSuggestion('10 milyon bütçem var')">10 milyon bütçem var</span>
                    </div>
                </div>
                <div class="ai-assistant-footer">
                    <input type="text" class="ai-assistant-input" id="chatInput" placeholder="Mesajınızı yazın...">
                    <button class="ai-assistant-send" id="sendBtn">➤</button>
                </div>
            </div>
            <div class="ai-assistant-toggle" onclick="toggleChat()">
                <span style="color:white; font-size: 24px;">🤖</span>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', widgetHtml);

    window.toggleChat = function () {
        const chat = document.getElementById('aiChat');
        chat.style.display = chat.style.display === 'flex' ? 'none' : 'flex';
    };

    window.sendSuggestion = function (text) {
        document.getElementById('chatInput').value = text;
        sendMessage();
    };

    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatBody = document.getElementById('chatBody');

    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') sendMessage();
    });

    sendBtn.addEventListener('click', sendMessage);

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        appendMessage('user', text);
        input.value = '';

        try {
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ message: text }),
            });

            const data = await response.json();
            if (data.explanation) {
                appendMessage('bot', data.explanation);

                if (data.results && data.results.length > 0) {
                    appendResults(data.results);
                }
            } else {
                appendMessage('bot', 'Size şu an yardımcı olamıyorum, lütfen tekrar deneyin.');
            }
        } catch (error) {
            console.error('AI Assistant Error:', error);
            appendMessage('bot', 'Sistemle iletişim kurulamadı.');
        }
    }

    function appendMessage(sender, text) {
        const msg = document.createElement('div');
        msg.className = `ai-message ${sender}`;
        msg.innerHTML = text;
        chatBody.appendChild(msg);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function appendResults(results) {
        const container = document.createElement('div');
        container.style.marginTop = '10px';

        results.slice(0, 3).forEach((listing) => {
            const item = document.createElement('div');
            item.style.padding = '8px';
            item.style.background = '#fff';
            item.style.border = '1px solid #ddd';
            item.style.borderRadius = '5px';
            item.style.marginBottom = '5px';
            item.style.fontSize = '12px';
            item.innerHTML = `<strong>${listing.title}</strong><br>${listing.price} TL`;
            container.appendChild(item);
        });

        chatBody.appendChild(container);
        chatBody.scrollTop = chatBody.scrollHeight;
    }
});
