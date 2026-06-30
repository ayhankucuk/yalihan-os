@extends('admin.layouts.admin')

@section('title', 'AI Alıcı Bulucu — Buyer Match Queue')

@push('styles')
    <style>
        :root {
            --bm-bg: #0f172a;
            --bm-surface: #1e293b;
            --bm-accent: #8b5cf6;
            --bm-accent-glow: rgba(139, 92, 246, 0.12);
            --bm-success: #10b981;
            --bm-warning: #f59e0b;
            --bm-danger: #ef4444;
            --bm-text: #f1f5f9;
            --bm-muted: #94a3b8;
            --bm-border: rgba(148, 163, 184, 0.1);
            --bm-radius: 0.75rem;
        }

        .bm-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* ── Header ── */
        .bm-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .bm-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--bm-text);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .bm-live-badge {
            font-size: 0.65rem;
            background: var(--bm-accent);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.05em;
            animation: bm-pulse 2s infinite;
        }

        @keyframes bm-pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4)
            }

            50% {
                box-shadow: 0 0 0 6px rgba(139, 92, 246, 0)
            }
        }

        .bm-subtitle {
            color: var(--bm-muted);
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .bm-controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .bm-action {
            background: var(--bm-accent);
            color: #fff;
            border: none;
            padding: 0.45rem 1.1rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .bm-action:hover {
            background: #7c3aed;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .bm-action.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .bm-action-outline {
            background: transparent;
            border: 1px solid var(--bm-border);
            color: var(--bm-muted);
            padding: 0.45rem 1.1rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .bm-action-outline:hover {
            border-color: var(--bm-accent);
            color: var(--bm-accent);
        }

        /* ── Listing Card ── */
        .bm-listing {
            background: var(--bm-surface);
            border: 1px solid var(--bm-border);
            border-radius: var(--bm-radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .bm-listing-info h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--bm-text);
            margin: 0 0 0.4rem 0;
        }

        .bm-listing-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .bm-listing-meta span {
            font-size: 0.8rem;
            color: var(--bm-muted);
        }

        .bm-listing-meta strong {
            color: var(--bm-text);
        }

        /* ── Stats ── */
        .bm-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .bm-stat {
            background: var(--bm-surface);
            border: 1px solid var(--bm-border);
            border-radius: var(--bm-radius);
            padding: 1rem;
            text-align: center;
        }

        .bm-stat-val {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--bm-accent);
            line-height: 1;
        }

        .bm-stat-lbl {
            font-size: 0.7rem;
            color: var(--bm-muted);
            margin-top: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── Match Feed ── */
        .bm-feed-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--bm-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .bm-feed {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .bm-match {
            background: var(--bm-surface);
            border: 1px solid var(--bm-border);
            border-radius: var(--bm-radius);
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 72px 1fr auto;
            gap: 1.25rem;
            align-items: center;
            transition: all 0.25s;
        }

        .bm-match:hover {
            border-color: var(--bm-accent);
            box-shadow: 0 0 0 1px var(--bm-accent), 0 8px 20px var(--bm-accent-glow);
            transform: translateY(-2px);
        }

        /* Score */
        .bm-score {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .bm-score-num {
            font-size: 1.4rem;
            line-height: 1;
        }

        .bm-score-txt {
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.8;
        }

        .bm-score.s-high {
            background: rgba(16, 185, 129, 0.12);
            color: var(--bm-success);
            border: 2px solid var(--bm-success);
        }

        .bm-score.s-mid {
            background: rgba(245, 158, 11, 0.12);
            color: var(--bm-warning);
            border: 2px solid var(--bm-warning);
        }

        .bm-score.s-low {
            background: rgba(239, 68, 68, 0.12);
            color: var(--bm-danger);
            border: 2px solid var(--bm-danger);
        }

        /* Body */
        .bm-body h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--bm-text);
            margin: 0 0 0.35rem 0;
        }

        .bm-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .bm-meta span {
            font-size: 0.75rem;
            color: var(--bm-muted);
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .bm-intent {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 0.2rem;
            font-weight: 600;
        }

        .bm-intent.i-high {
            background: rgba(16, 185, 129, 0.12);
            color: var(--bm-success);
        }

        .bm-intent.i-medium {
            background: rgba(245, 158, 11, 0.12);
            color: var(--bm-warning);
        }

        .bm-intent.i-low {
            background: rgba(148, 163, 184, 0.12);
            color: var(--bm-muted);
        }

        .bm-reason-bar {
            background: var(--bm-accent-glow);
            border-left: 3px solid var(--bm-accent);
            padding: 0.4rem 0.7rem;
            border-radius: 0 0.35rem 0.35rem 0;
            font-size: 0.8rem;
            color: var(--bm-text);
            line-height: 1.4;
        }

        /* Actions */
        .bm-actions {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .bm-act {
            background: transparent;
            border: 1px solid var(--bm-border);
            color: var(--bm-muted);
            padding: 0.4rem 0.9rem;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            text-align: center;
        }

        .bm-act:hover {
            border-color: var(--bm-accent);
            color: var(--bm-accent);
        }

        .bm-act.primary {
            background: var(--bm-accent);
            border-color: var(--bm-accent);
            color: #fff;
        }

        .bm-act.primary:hover {
            background: #7c3aed;
        }

        /* Empty / Loading */
        .bm-empty {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--bm-muted);
        }

        .bm-empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .bm-loading {
            text-align: center;
            padding: 2.5rem;
            color: var(--bm-muted);
        }

        @keyframes bm-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .bm-spinner {
            display: inline-block;
            animation: bm-spin 1s linear infinite;
        }

        @media (max-width: 768px) {
            .bm-match {
                grid-template-columns: 1fr;
            }

            .bm-actions {
                flex-direction: row;
            }

            .bm-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <div class="bm-page">
        <!-- Header -->
        <div class="bm-header">
            <div>
                <h1>🤝 AI Alıcı Bulucu <span class="bm-live-badge">LIVE</span></h1>
                <p class="bm-subtitle">Buyer Match Engine tarafından desteklenen akıllı alıcı eşleştirme</p>
            </div>
            <div class="bm-controls">
                <button class="bm-action" id="refreshBtn" onclick="loadMatches()">🔄 Yenile</button>
                <a href="{{ route('advisor.opportunities') }}" class="bm-action-outline">← Fırsatlara Dön</a>
            </div>
        </div>

        <!-- Listing Summary -->
        <div class="bm-listing">
            <div class="bm-listing-info">
                <h2 id="listingTitle">{{ $ilan->baslik ?? 'İlan' }}</h2>
                <div class="bm-listing-meta">
                    <span>💰 <strong id="listingPrice">{{ number_format($ilan->fiyat ?? 0, 0, ',', '.') }} ₺</strong></span>
                    <span>🏠 <strong>{{ $ilan->emlak_tipi ?? '' }}</strong></span>
                    <span>📍 {{ $ilan->il?->il_adi ?? '' }} / {{ $ilan->ilce?->ilce_adi ?? '' }}</span>
                    <span>🔑 #{{ $ilan->id }}</span>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="bm-stats">
            <div class="bm-stat">
                <div class="bm-stat-val" id="stTotal">—</div>
                <div class="bm-stat-lbl">Eşleşme</div>
            </div>
            <div class="bm-stat">
                <div class="bm-stat-val" id="stHigh">—</div>
                <div class="bm-stat-lbl">En Yüksek Skor</div>
            </div>
            <div class="bm-stat">
                <div class="bm-stat-val" id="stAvg">—</div>
                <div class="bm-stat-lbl">Ortalama Skor</div>
            </div>
            <div class="bm-stat">
                <div class="bm-stat-val" id="stTime">—</div>
                <div class="bm-stat-lbl">Son Analiz</div>
            </div>
        </div>

        <!-- Match Feed -->
        <div class="bm-feed-title">🎯 Alıcı Eşleşmeleri</div>
        <div class="bm-feed" id="matchFeed">
            <div class="bm-loading"><span class="bm-spinner">⏳</span> Eşleşmeler hesaplanıyor...</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const LISTING_ID = {{ $ilan->id }};
        const API_URL = `/api/advisor/listings/${LISTING_ID}/buyer-matches`;

        async function loadMatches() {
            const feed = document.getElementById('matchFeed');
            const btn = document.getElementById('refreshBtn');

            btn.classList.add('loading');
            btn.innerHTML = '⏳ Hesaplanıyor...';
            feed.innerHTML =
                '<div class="bm-loading"><span class="bm-spinner">⏳</span> Eşleşmeler hesaplanıyor...</div>';

            try {
                const res = await fetch(`${API_URL}?limit=20`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();

                if (!json.success || !json.data?.matches?.length) {
                    feed.innerHTML =
                        `<div class="bm-empty"><div class="bm-empty-icon">🔍</div><h3>Henüz eşleşme bulunamadı</h3><p>Buyer Match Engine bu ilan için uygun alıcı adayı tespit edemedi.</p></div>`;
                    updateStats(0, 0, 0);
                    return;
                }

                const matches = json.data.matches;
                renderFeed(matches);
                updateStats(
                    matches.length,
                    Math.max(...matches.map(m => m.match_score)),
                    Math.round(matches.reduce((s, m) => s + m.match_score, 0) / matches.length)
                );

            } catch (err) {
                feed.innerHTML =
                    `<div class="bm-empty"><div class="bm-empty-icon">⚠️</div><h3>Bağlantı hatası</h3><p>${esc(err.message)}</p></div>`;
            } finally {
                btn.classList.remove('loading');
                btn.innerHTML = '🔄 Yenile';
            }
        }

        function renderFeed(matches) {
            const feed = document.getElementById('matchFeed');
            feed.innerHTML = matches.map((m, i) => {
                const sc = m.match_score >= 70 ? 's-high' : m.match_score >= 45 ? 's-mid' : 's-low';
                const ic = m.intent_signal === 'high' ? 'i-high' : m.intent_signal === 'medium' ? 'i-medium' :
                    'i-low';
                const intentLabel = m.intent_signal === 'high' ? '🔥 Yüksek' : m.intent_signal === 'medium' ?
                    '⚡ Orta' : '💤 Düşük';

                return `<div class="bm-match">
                    <div class="bm-score ${sc}">
                        <span class="bm-score-num">${Math.round(m.match_score)}</span>
                        <span class="bm-score-txt">Skor</span>
                    </div>
                    <div class="bm-body">
                        <h3>${esc(m.buyer_adi)} ${esc(m.buyer_soyadi)}</h3>
                        <div class="bm-meta">
                            <span class="bm-intent ${ic}">${intentLabel}</span>
                            ${m.buyer_telefon ? `<span>📞 ${esc(m.buyer_telefon)}</span>` : ''}
                            ${m.buyer_email ? `<span>📧 ${esc(m.buyer_email)}</span>` : ''}
                        </div>
                        <div class="bm-reason-bar">${esc(m.reason || 'Genel kriterlere uygun eşleşme.')}</div>
                    </div>
                    <div class="bm-actions">
                        <button class="bm-act primary" onclick="event.stopPropagation(); window.location.href='/admin/kisiler/${m.buyer_id}'">👤 Profil</button>
                        <button class="bm-act" onclick="event.stopPropagation(); showBreakdown(${i})">📊 Detay</button>
                    </div>
                </div>`;
            }).join('');

            // Store matches for breakdown view
            window._matches = matches;
        }

        function updateStats(total, highest, avg) {
            document.getElementById('stTotal').textContent = total;
            document.getElementById('stHigh').textContent = highest || '—';
            document.getElementById('stAvg').textContent = avg || '—';
            document.getElementById('stTime').textContent = new Date().toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showBreakdown(idx) {
            const m = window._matches?.[idx];
            if (!m) return;

            const bd = m.breakdown || {};
            const lines = Object.entries(bd)
                .sort((a, b) => b[1] - a[1])
                .map(([k, v]) => `  ${k}: ${v.toFixed(1)}`)
                .join('\n');

            alert(
                `📊 Skor Detayı — ${m.buyer_adi} ${m.buyer_soyadi}\n\nToplam: ${m.match_score}\nIntent: ${m.intent_signal}\n\n${lines}`
            );
        }

        function esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', loadMatches);
    </script>
@endpush
