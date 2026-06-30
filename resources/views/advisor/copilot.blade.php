@extends('admin.layouts.admin')

@section('title', 'AI Broker Copilot — Yalıhan AI')

@push('styles')
    <style>
        :root {
            --cp-bg: #0f172a;
            --cp-surface: #1e293b;
            --cp-accent: #3b82f6;
            --cp-accent-glow: rgba(59, 130, 246, 0.15);
            --cp-success: #10b981;
            --cp-warning: #f59e0b;
            --cp-danger: #ef4444;
            --cp-text: #f1f5f9;
            --cp-muted: #94a3b8;
            --cp-border: rgba(148, 163, 184, 0.1);
            --cp-radius: 1rem;
        }

        .cp-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 1.5rem;
            height: calc(100vh - 120px);
        }

        /* ── Sidebar (Listing Selection) ── */
        .cp-sidebar {
            background: var(--cp-surface);
            border: 1px solid var(--cp-border);
            border-radius: var(--cp-radius);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cp-sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--cp-border);
        }

        .cp-sidebar-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--cp-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cp-listing-card {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--cp-border);
            cursor: pointer;
            transition: all 0.2s;
        }

        .cp-listing-card:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .cp-listing-card.active {
            // context7-ignore
            background: var(--cp-accent-glow);
            border-left: 4px solid var(--cp-accent);
        }

        .cp-listing-card h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--cp-text);
            margin: 0 0 0.25rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cp-listing-card p {
            font-size: 0.75rem;
            color: var(--cp-muted);
            margin: 0;
        }

        /* ── Main Chat Area ── */
        .cp-main {
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 1.5rem;
        }

        .cp-chat-window {
            background: var(--cp-surface);
            border: 1px solid var(--cp-border);
            border-radius: var(--cp-radius);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cp-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .cp-msg {
            max-width: 80%;
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
        }

        .cp-msg.ai {
            align-self: flex-start;
            background: #2d3748;
            color: var(--cp-text);
            border-bottom-left-radius: 0.2rem;
        }

        .cp-msg.user {
            align-self: flex-end;
            background: var(--cp-accent);
            color: #fff;
            border-bottom-right-radius: 0.2rem;
        }

        .cp-msg-meta {
            font-size: 0.7rem;
            margin-bottom: 0.4rem;
            opacity: 0.7;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* ── Action Cards ── */
        .cp-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .cp-panel {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--cp-border);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .cp-panel-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--cp-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .cp-val {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--cp-text);
        }

        .cp-indicator {
            height: 4px;
            background: #4a5568;
            border-radius: 2px;
            margin: 0.5rem 0;
            overflow: hidden;
        }

        .cp-indicator-fill {
            height: 100%;
            background: var(--cp-accent);
            border-radius: 2px;
            transition: width 1s ease-out;
        }

        /* ── Input Bar ── */
        .cp-input-area {
            background: var(--cp-surface);
            border: 1px solid var(--cp-border);
            border-radius: var(--cp-radius);
            padding: 1rem;
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .cp-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--cp-text);
            font-size: 1rem;
            outline: none;
            padding: 0.5rem;
        }

        .cp-action-btn {
            background: var(--cp-accent);
            color: #fff;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cp-action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px var(--cp-accent-glow);
        }

        .cp-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ── Animations ── */
        @keyframes cp-fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cp-msg {
            animation: cp-fade-in 0.3s ease-out both;
        }

        @media (max-width: 1024px) {
            .cp-container {
                grid-template-columns: 1fr;
            }

            .cp-sidebar {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="cp-container">
        <!-- Sidebar -->
        <aside class="cp-sidebar">
            <div class="cp-sidebar-header">
                <h2>🛰️ Aktif Portföy</h2>
            </div>
            <div id="listingList" class="cp-listing-wrapper" style="overflow-y: auto;">
                @if (isset($ilan))
                    <div class="cp-listing-card active" data-id="{{ $ilan->id }}">
                        <h3>{{ $ilan->baslik }}</h3>
                        <p>{{ number_format($ilan->fiyat, 0, ',', '.') }} ₺ | {{ $ilan->ilce?->ilce_adi }}</p>
                    </div>
                @else
                    <div style="padding: 2rem; text-align: center; color: var(--cp-muted);">
                        <p>Analiz için bir ilan seçin.</p>
                    </div>
                @endif
            </div>
        </aside>

        <!-- Main -->
        <main class="cp-main">
            <div class="cp-chat-window">
                <div class="cp-messages" id="chatMessages">
                    <div class="cp-msg ai">
                        <div class="cp-msg-meta">Yalıhan Copilot</div>
                        Merhaba! Ben Yalıhan Broker Copilot. Hangi ilanınız üzerinde çalışalım? Size fiyat analizi, alıcı
                        eşleştirme veya satış stratejisi konusunda yardımcı olabilirim.
                    </div>
                </div>
            </div>

            <div class="cp-input-area">
                <input type="text" id="userInput" class="cp-input"
                    placeholder="Bir soru sorun... (örn: Bu ilan neden satılmıyor?)" autofocus>
                <button id="sendBtn" class="cp-action-btn">🚀</button>
            </div>
        </main>
    </div>
@endsection

@push('scripts')
    <script src="/js/utils/sanitize.js"></script>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        let currentListingId = {{ $ilan->id ?? 'null' }};

        async function sendMessage() {
            const question = userInput.value.trim();
            if (!question || !currentListingId) return;

            addMessage(question, 'user');
            userInput.value = '';
            setLoading(true);

            try {
                const response = await fetch('/api/advisor/copilot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ilan_id: currentListingId,
                        question: question
                    })
                });

                const json = await response.json();
                if (json.success) {
                    renderAIResponse(json.data);
                } else {
                    addMessage('Üzgünüm, bir hata oluştu: ' + json.message, 'ai');
                }
            } catch (err) {
                addMessage('Bağlantı hatası oluştu.', 'ai');
            } finally {
                setLoading(false);
            }
        }

        function addMessage(text, type) {
            const div = document.createElement('div');
            div.className = `cp-msg ${type}`;
            div.innerHTML = `
                <div class="cp-msg-meta">${type === 'ai' ? 'Yalıhan Copilot' : 'Siz'}</div>
                <div>${escapeHtml(text)}</div>
            `;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function renderAIResponse(data) {
            const div = document.createElement('div');
            div.className = 'cp-msg ai';

            let html = `<div class="cp-msg-meta">Yalıhan Copilot</div>`;
            html +=
                `<p><strong>Analiz Sonucu:</strong> ${escapeHtml(data.analysis.health_label)} (%${escapeHtml(data.analysis.overall_health)})</p>`;

            // Dashboard snippet
            html += `<div class="cp-dashboard">
                <div class="cp-panel">
                    <div class="cp-panel-title">📈 Satış Olasılığı</div>
                    <div class="cp-val">%${Math.round(data.prediction.probability)}</div>
                    <div class="cp-indicator"><div class="cp-indicator-fill" style="width: ${data.prediction.probability}%"></div></div>
                </div>
                <div class="cp-panel">
                    <div class="cp-panel-title">🎯 Güven Skoru</div>
                    <div class="cp-val">%${data.confidence}</div>
                </div>
            </div>`;

            if (data.recommendation && data.recommendation.length > 0) {
                html += `<div style="margin-top: 1rem;"><strong>Önerilerim:</strong><ul>`;
                data.recommendation.slice(0, 3).forEach(rec => {
                    html += `<li>${escapeHtml(rec)}</li>`;
                });
                html += `</ul></div>`;
            }

            html +=
                `<p style="margin-top: 0.5rem; font-style: italic; font-size: 0.85rem;">${escapeHtml(data.prediction.explanation)}</p>`;

            div.innerHTML = html;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function setLoading(loading) {
            sendBtn.disabled = loading;
            sendBtn.innerHTML = loading ? '⏳' : '🚀';
        }

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        // Initial Load if listing present
        if (currentListingId) {
            // Option to auto-trigger first analysis
        }
    </script>
@endpush
