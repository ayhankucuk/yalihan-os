@extends('layouts.frontend')

@section('title', 'AI Emlak Danışmanı — Yalıhan')

@push('styles')
<style>
    .ai-hero {
        background: linear-gradient(135deg, #0A1628 0%, #162440 60%, #1a2d4a 100%);
    }
    .gold-accent { color: #C9A84C; }
    .chat-bubble-ai {
        background: #F8F6F1;
        border-left: 3px solid #C9A84C;
    }
    .chat-bubble-user {
        background: #0A1628;
        color: #F8F6F1;
    }
    .query-chip {
        border: 1px solid rgba(201,168,76,0.4);
        color: #0A1628;
        transition: all 0.2s;
        cursor: pointer;
    }
    .query-chip:hover {
        background: #C9A84C;
        border-color: #C9A84C;
        color: #0A1628;
    }
    .deal-card {
        border-left: 3px solid #C9A84C;
        transition: box-shadow 0.2s;
    }
    .deal-card:hover { box-shadow: 0 4px 16px rgba(10,22,40,0.12); }
    #chat-messages { scroll-behavior: smooth; }
    .typing-dot {
        width: 7px; height: 7px;
        background: #C9A84C;
        border-radius: 50%;
        animation: typing 1.2s infinite;
    }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-8px)} }
</style>
@endpush

@section('content')

{{-- Hero Banner --}}
<section class="ai-hero py-16">
    <div class="max-w-5xl mx-auto px-6 md:px-12 text-center">
        <div class="inline-flex items-center gap-2 mb-6 px-4 py-1.5 rounded-full" style="background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.3);">
            <span class="material-symbols-outlined text-sm gold-accent">psychology</span>
            <span class="text-xs font-semibold uppercase tracking-widest gold-accent">Cortex AI Motor</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
            AI Emlak <span class="gold-accent">Danışmanı</span>
        </h1>
        <p class="text-white/70 text-lg max-w-2xl mx-auto">
            Piyasa değerlemesi, yatırım fırsatları, satış stratejisi. Doğal dille sorun — gerçek verilerle yanıt alın.
        </p>

        {{-- Kabiliyetler --}}
        <div class="flex flex-wrap justify-center gap-3 mt-8">
            @foreach([
                ['psychology','Fiyat Değerlemesi'],
                ['trending_up','Piyasa Analizi'],
                ['radar','Yatırım Fırsatı'],
                ['sell','Satış Stratejisi'],
                ['group','Alıcı Eşleştirme'],
                ['favorite','Portföy Sağlığı'],
            ] as [$icon,$label])
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs text-white/80" style="background:rgba(255,255,255,0.08);">
                <span class="material-symbols-outlined text-sm gold-accent">{{ $icon }}</span>
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Chat Interface --}}
<section class="py-12 bg-slate-50">
<div class="max-w-4xl mx-auto px-6 md:px-12">

    {{-- listing_id URL param desteği --}}
    @if(request()->query('listing_id'))
    <div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
        <span class="material-symbols-outlined text-base">link</span>
        İlan #{{ request()->query('listing_id') }} bağlamıyla çalışıyorsunuz.
        Satış stratejisi veya alıcı eşleştirmesi için soru sorabilirsiniz.
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden" style="border:1px solid rgba(10,22,40,0.08);">

        {{-- Chat Mesaj Alanı --}}
        <div id="chat-messages" class="h-[480px] overflow-y-auto p-6 space-y-5">

            {{-- Karşılama mesajı --}}
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background:#0A1628;">
                    <span class="material-symbols-outlined text-sm" style="color:#C9A84C;">psychology</span>
                </div>
                <div class="chat-bubble-ai rounded-2xl rounded-tl-none p-4 max-w-xl">
                    <p class="font-semibold text-sm mb-2" style="color:#0A1628;">Yalıhan AI Danışmanı</p>
                    <p class="text-sm text-slate-700 mb-3">Merhaba! Bodrum Yarımadası ve çevresi için gayrimenkul analizleri yapabilirim. Ne öğrenmek istersiniz?</p>
                    <div class="text-xs text-slate-500 space-y-1">
                        <p>• <em>"Bodrum Bitez'de 500m² arsa kaç TL eder?"</em></p>
                        <p>• <em>"Yalıkavak'ta yatırım fırsatı var mı?"</em></p>
                        <p>• <em>"Piyasa trendi nasıl?"</em></p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Divider --}}
        <div style="border-top:1px solid rgba(10,22,40,0.08);"></div>

        {{-- Hızlı Soru Chipleri --}}
        <div class="px-6 pt-4 pb-2 flex flex-wrap gap-2">
            @foreach([
                'Bodrum Bitez 500m² arsa fiyatı?',
                'Yalıkavak yatırım fırsatı',
                'Piyasa trendi nasıl?',
                'Portföy sağlık analizi',
            ] as $chip)
            <button onclick="useChip(this)"
                    data-query="{{ $chip }}"
                    class="query-chip text-xs px-3 py-1.5 rounded-full bg-white font-medium">
                {{ $chip }}
            </button>
            @endforeach
        </div>

        {{-- Input --}}
        <div class="px-6 pb-6 pt-2">
            <form id="ai-form" class="flex gap-3">
                @csrf
                <input type="hidden" id="listing-id-field" name="listing_id" value="{{ request()->query('listing_id') }}">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">chat</span>
                    <input type="text" id="query-input" name="query"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border text-sm outline-none focus:ring-2 focus:ring-yellow-400 transition-all"
                           style="border-color:rgba(10,22,40,0.15);"
                           placeholder="Gayrimenkul sorunuzu yazın..."
                           autocomplete="off" required>
                </div>
                <button type="submit" id="submit-btn"
                        class="px-5 py-3 rounded-xl font-semibold text-sm flex items-center gap-1.5 transition-all hover:opacity-90"
                        style="background:#0A1628;color:#C9A84C;">
                    <span class="material-symbols-outlined text-base">send</span>
                    <span class="hidden sm:inline">Sor</span>
                </button>
            </form>
            <div class="flex items-center mt-2">
                <p class="text-xs text-slate-400 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">lock</span>
                    Verileriniz güvende. Sorgular yalnızca analiz için kullanılır.
                </p>
                <button onclick="clearHistory()" class="ml-auto text-xs text-slate-400 hover:text-slate-600 underline">
                    Sohbeti Temizle
                </button>
            </div>
        </div>

    </div>

    {{-- Örnek Sorgular Yardım Kutusu --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach([
            ['psychology','Değerleme','Bodrum Türkbükü 200m² daire kaç TL eder?'],
            ['trending_up','Piyasa','Bodrum\'da piyasa trendi nasıl?'],
            ['radar','Fırsat','Yalıkavak\'ta karlı yatırım fırsatı var mı?'],
        ] as [$icon,$title,$example])
        <div onclick="useExample('{{ addslashes($example) }}')"
             class="bg-white rounded-xl p-4 shadow-sm cursor-pointer hover:shadow-md transition-all"
             style="border:1px solid rgba(10,22,40,0.08);">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-lg" style="color:#C9A84C;">{{ $icon }}</span>
                <span class="font-semibold text-sm" style="color:#0A1628;">{{ $title }}</span>
            </div>
            <p class="text-xs text-slate-500 italic">"{{ $example }}"</p>
        </div>
        @endforeach
    </div>

</div>
</section>

@endsection

@push('scripts')
<script>
const QUERY_URL  = "{{ route('public.conversational.query') }}";
const CLEAR_URL  = "{{ route('public.conversational.clear') }}";
const CSRF_TOKEN = "{{ csrf_token() }}";
const LISTING_ID = "{{ request()->query('listing_id', '') }}";

const form     = document.getElementById('ai-form');
const input    = document.getElementById('query-input');
const messages = document.getElementById('chat-messages');
const submitBtn = document.getElementById('submit-btn');

function scrollBottom() {
    messages.scrollTop = messages.scrollHeight;
}

function useChip(btn) {
    input.value = btn.dataset.query;
    input.focus();
}

function useExample(text) {
    input.value = text;
    input.focus();
}

function addUserBubble(text) {
    const div = document.createElement('div');
    div.className = 'flex justify-end';
    div.innerHTML = `
        <div class="chat-bubble-user rounded-2xl rounded-tr-none px-5 py-3 max-w-sm text-sm">
            ${escHtml(text)}
        </div>`;
    messages.appendChild(div);
    scrollBottom();
}

function addTyping() {
    const div = document.createElement('div');
    div.id = 'typing-indicator';
    div.className = 'flex items-start gap-3';
    div.innerHTML = `
        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background:#0A1628;">
            <span class="material-symbols-outlined text-sm" style="color:#C9A84C;">psychology</span>
        </div>
        <div class="chat-bubble-ai rounded-2xl rounded-tl-none p-4">
            <div class="flex gap-1.5 items-center h-5">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>`;
    messages.appendChild(div);
    scrollBottom();
}

function removeTyping() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}

function intentLabel(intent) {
    const map = {
        MARKET_VALUATION:       '📊 Fiyat Değerleme',
        MARKET_INTELLIGENCE:    '📈 Piyasa Analizi',
        INVESTMENT_OPPORTUNITY: '🎯 Yatırım Fırsatı',
        SELLER_PRICING:         '🏷️ Satış Stratejisi',
        LISTING_DIAGNOSTIC:     '🔍 İlan Tanılama',
        OWNER_ACQUISITION:      '🤝 Sahip Analizi',
        BUYER_MATCH:            '👤 Alıcı Eşleştirme',
        PORTFOLIO_HEALTH:       '💼 Portföy Sağlığı',
        UNKNOWN:                '❓ Genel',
    };
    return map[intent] || intent;
}

function dealCardsHtml(payload) {
    if (!payload || !payload.top_deals || !payload.top_deals.length) return '';
    const cards = payload.top_deals.map(d => `
        <a href="${escHtml(d.url || '#')}" target="_blank"
           class="deal-card block bg-white rounded-lg p-3 mt-2 no-underline hover:no-underline">
            <div class="flex justify-between items-start gap-2">
                <p class="font-semibold text-slate-800 text-xs line-clamp-1 flex-1">${escHtml(d.listing_title || 'İlan')}</p>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold whitespace-nowrap flex-shrink-0"
                      style="background:#0A1628;color:#C9A84C;">${escHtml(d.deal_tier || '')}</span>
            </div>
            <p class="text-slate-500 text-xs mt-0.5">${escHtml(d.location || '')}${d.fiyat_formatted ? ' • ' + d.fiyat_formatted + ' ₺' : ''}</p>
            ${d.suggested_action ? `<p class="text-slate-400 text-xs mt-1">${escHtml(d.suggested_action)}</p>` : ''}
        </a>`).join('');
    return `<div class="mt-3 space-y-1">${cards}</div>`;
}

function valuationHtml(payload) {
    if (!payload || !payload.estimated_value) return '';
    const fmt = n => Number(n).toLocaleString('tr-TR');
    return `
        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
            <div class="bg-white rounded-lg p-2">
                <p class="text-slate-400 text-xs">Tahmini</p>
                <p class="font-bold text-slate-800 text-sm">${fmt(payload.estimated_value)} ₺</p>
            </div>
            <div class="bg-white rounded-lg p-2">
                <p class="text-slate-400 text-xs">Alt Sınır</p>
                <p class="font-semibold text-slate-600 text-sm">${fmt(payload.price_range_low)} ₺</p>
            </div>
            <div class="bg-white rounded-lg p-2">
                <p class="text-slate-400 text-xs">Üst Sınır</p>
                <p class="font-semibold text-slate-600 text-sm">${fmt(payload.price_range_high)} ₺</p>
            </div>
        </div>`;
}

function addAiBubble(data) {
    const intent = data.intent_detected || 'UNKNOWN';
    const msg    = escHtml(data.advisor_response || '').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    const extra  = intent === 'INVESTMENT_OPPORTUNITY' ? dealCardsHtml(data.data_payload)
                 : intent === 'MARKET_VALUATION'       ? valuationHtml(data.data_payload)
                 : '';

    const div = document.createElement('div');
    div.className = 'flex items-start gap-3';
    div.innerHTML = `
        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background:#0A1628;">
            <span class="material-symbols-outlined text-sm" style="color:#C9A84C;">psychology</span>
        </div>
        <div class="chat-bubble-ai rounded-2xl rounded-tl-none p-4 max-w-xl flex-1">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-semibold" style="color:#0A1628;">Yalıhan AI</span>
                <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgba(201,168,76,0.15);color:#8a6c1e;">${intentLabel(intent)}</span>
            </div>
            <p class="text-sm text-slate-700">${msg}</p>
            ${extra}
        </div>`;
    messages.appendChild(div);
    scrollBottom();
}

function escHtml(str) {
    return String(str || '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const query = input.value.trim();
    if (!query) return;

    addUserBubble(query);
    input.value = '';
    submitBtn.disabled = true;
    addTyping();

    try {
        const body = new FormData();
        body.append('_token', CSRF_TOKEN);
        body.append('query', query);
        if (LISTING_ID) body.append('listing_id', LISTING_ID);

        const res  = await fetch(QUERY_URL, { method: 'POST', body });
        const data = await res.json();
        removeTyping();
        addAiBubble(data);
    } catch (err) {
        removeTyping();
        addAiBubble({ advisor_response: 'Bir hata oluştu. Lütfen tekrar deneyin.', intent_detected: 'UNKNOWN' });
    } finally {
        submitBtn.disabled = false;
        input.focus();
    }
});

async function clearHistory() {
    try {
        await fetch(CLEAR_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
    } catch (e) { /* silent */ }

    // Karşılama mesajı dışındaki balonları kaldır
    const allBubbles = messages.querySelectorAll('.flex');
    allBubbles.forEach((el, i) => { if (i > 0) el.remove(); });
}
</script>
@endpush
