@extends('admin.layouts.admin')

@section('title', 'AI Fırsat Avcısı — Opportunity Inbox')

@push('styles')
    <style>
        :root {
            --oi-bg: #0f172a;
            --oi-surface: #1e293b;
            --oi-accent: #3b82f6;
            --oi-accent-glow: rgba(59, 130, 246, 0.12);
            --oi-success: #10b981;
            --oi-warning: #f59e0b;
            --oi-danger: #ef4444;
            --oi-text: #f1f5f9;
            --oi-muted: #94a3b8;
            --oi-border: rgba(148, 163, 184, 0.1);
            --oi-radius: 0.75rem;
        }

        .oi-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* ── Header ── */
        .oi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .oi-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--oi-text);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .oi-live-badge {
            font-size: 0.65rem;
            background: var(--oi-success);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.05em;
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4)
            }

            50% {
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0)
            }
        }

        .oi-subtitle {
            color: var(--oi-muted);
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .oi-controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .oi-select {
            background: var(--oi-surface);
            border: 1px solid var(--oi-border);
            color: var(--oi-text);
            padding: 0.45rem 0.9rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .oi-btn {
            background: var(--oi-accent);
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

        .oi-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .oi-btn.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* ── Stats ── */
        .oi-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .oi-stat {
            background: var(--oi-surface);
            border: 1px solid var(--oi-border);
            border-radius: var(--oi-radius);
            padding: 1rem;
            text-align: center;
        }

        .oi-stat-val {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--oi-accent);
            line-height: 1;
        }

        .oi-stat-lbl {
            font-size: 0.7rem;
            color: var(--oi-muted);
            margin-top: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── 3 Panel Layout ── */
        .oi-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.25rem;
        }

        @media (max-width: 1024px) {
            .oi-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ── Feed (Left) ── */
        .oi-feed {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .oi-feed-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--oi-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .oi-item {
            background: var(--oi-surface);
            border: 1px solid var(--oi-border);
            border-radius: var(--oi-radius);
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 72px 1fr auto;
            gap: 1.25rem;
            align-items: center;
            transition: all 0.25s;
            cursor: pointer;
        }

        .oi-item:hover {
            border-color: var(--oi-accent);
            box-shadow: 0 0 0 1px var(--oi-accent), 0 8px 20px var(--oi-accent-glow);
            transform: translateY(-2px);
        }

        /* Score */
        .oi-score {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .oi-score-num {
            font-size: 1.4rem;
            line-height: 1;
        }

        .oi-score-txt {
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.8;
        }

        .oi-score.s-high {
            background: rgba(16, 185, 129, 0.12);
            color: var(--oi-success);
            border: 2px solid var(--oi-success);
        }

        .oi-score.s-mid {
            background: rgba(245, 158, 11, 0.12);
            color: var(--oi-warning);
            border: 2px solid var(--oi-warning);
        }

        .oi-score.s-low {
            background: rgba(239, 68, 68, 0.12);
            color: var(--oi-danger);
            border: 2px solid var(--oi-danger);
        }

        /* Body */
        .oi-body h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--oi-text);
            margin: 0 0 0.4rem 0;
        }

        .oi-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.6rem;
        }

        .oi-meta span {
            font-size: 0.75rem;
            color: var(--oi-muted);
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .oi-meta strong {
            color: var(--oi-text);
        }

        .oi-reason-bar {
            background: var(--oi-accent-glow);
            border-left: 3px solid var(--oi-accent);
            padding: 0.4rem 0.7rem;
            border-radius: 0 0.35rem 0.35rem 0;
            font-size: 0.8rem;
            color: var(--oi-text);
            line-height: 1.4;
        }

        /* Trend */
        .oi-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 0.2rem;
            font-weight: 600;
        }

        .oi-trend.t-up {
            background: rgba(16, 185, 129, 0.12);
            color: var(--oi-success);
        }

        .oi-trend.t-down {
            background: rgba(239, 68, 68, 0.12);
            color: var(--oi-danger);
        }

        .oi-trend.t-flat {
            background: rgba(148, 163, 184, 0.12);
            color: var(--oi-muted);
        }

        /* Actions */
        .oi-actions {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .oi-act {
            background: transparent;
            border: 1px solid var(--oi-border);
            color: var(--oi-muted);
            padding: 0.4rem 0.9rem;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            text-align: center;
        }

        .oi-act:hover {
            border-color: var(--oi-accent);
            color: var(--oi-accent);
        }

        .oi-act.primary {
            background: var(--oi-accent);
            border-color: var(--oi-accent);
            color: #fff;
        }

        .oi-act.primary:hover {
            background: #2563eb;
        }

        /* ── Sidebar (Right) ── */
        .oi-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .oi-panel {
            background: var(--oi-surface);
            border: 1px solid var(--oi-border);
            border-radius: var(--oi-radius);
            padding: 1.25rem;
        }

        .oi-panel-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--oi-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Trend Panel */
        .oi-trend-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--oi-border);
        }

        .oi-trend-row:last-child {
            border-bottom: none;
        }

        .oi-trend-region {
            font-size: 0.8rem;
            color: var(--oi-text);
        }

        .oi-trend-dir {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Insight Panel */
        .oi-insight-item {
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--oi-border);
        }

        .oi-insight-item:last-child {
            border-bottom: none;
        }

        .oi-insight-text {
            font-size: 0.8rem;
            color: var(--oi-text);
            line-height: 1.4;
        }

        .oi-insight-time {
            font-size: 0.65rem;
            color: var(--oi-muted);
            margin-top: 0.2rem;
        }

        /* Empty / Loading */
        .oi-empty {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--oi-muted);
        }

        .oi-empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .oi-loading {
            text-align: center;
            padding: 2.5rem;
            color: var(--oi-muted);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .oi-spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @media (max-width: 768px) {
            .oi-item {
                grid-template-columns: 1fr;
            }

            .oi-actions {
                flex-direction: row;
            }

            .oi-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <div class="oi-page">
        <!-- Header -->
        <div class="oi-header">
            <div>
                <h1>🎯 AI Fırsat Avcısı <span class="oi-live-badge">LIVE</span></h1>
                <p class="oi-subtitle">Opportunity Engine tarafından desteklenen otomatik fırsat tespiti — proj_listings
                    projection</p>
            </div>
            <div class="oi-controls">
                <select class="oi-select" id="minScoreFilter">
                    <option value="60">Skor ≥ 60</option>
                    <option value="70">Skor ≥ 70</option>
                    <option value="80">Skor ≥ 80</option>
                    <option value="40">Tümü (≥ 40)</option>
                </select>
                <button class="oi-btn" id="refreshBtn" onclick="loadOpportunities()">🔄 Yenile</button>
            </div>
        </div>

        <!-- Stats -->
        <div class="oi-stats">
            <div class="oi-stat">
                <div class="oi-stat-val" id="stTotal">—</div>
                <div class="oi-stat-lbl">Toplam Fırsat</div>
            </div>
            <div class="oi-stat">
                <div class="oi-stat-val" id="stHigh">—</div>
                <div class="oi-stat-lbl">En Yüksek Skor</div>
            </div>
            <div class="oi-stat">
                <div class="oi-stat-val" id="stAvg">—</div>
                <div class="oi-stat-lbl">Ortalama Skor</div>
            </div>
            <div class="oi-stat">
                <div class="oi-stat-val" id="stActive">—</div>
                <div class="oi-stat-lbl">Aktif Portföy</div>
            </div>
            <div class="oi-stat">
                <div class="oi-stat-val" id="stTime">—</div>
                <div class="oi-stat-lbl">Son Tarama</div>
            </div>
        </div>

        <!-- 3-Panel Layout -->
        <div class="oi-layout">
            <!-- Panel 1: Opportunity Feed -->
            <div>
                <div class="oi-feed-title">📋 Opportunity Feed</div>
                <div class="oi-feed" id="feedGrid">
                    <div class="oi-loading"><span class="oi-spinner">⏳</span> Fırsatlar yükleniyor...</div>
                </div>
            </div>

            <!-- Sidebar: Panel 2 + Panel 3 -->
            <div class="oi-sidebar">
                <!-- Panel 2: Trend Panel -->
                <div class="oi-panel">
                    <div class="oi-panel-title">📈 Bölge Trendleri</div>
                    <div id="trendPanel">
                        <div class="oi-loading"><span class="oi-spinner">⏳</span></div>
                    </div>
                </div>

                <!-- Panel 3: Insight Panel -->
                <div class="oi-panel">
                    <div class="oi-panel-title">💡 AI Insight</div>
                    <div id="insightPanel">
                        <div class="oi-loading"><span class="oi-spinner">⏳</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const API = '/api/advisor';

        async function loadOpportunities() {
            const grid = document.getElementById('feedGrid');
            const btn = document.getElementById('refreshBtn');
            const minScore = document.getElementById('minScoreFilter').value;

            btn.classList.add('loading');
            btn.innerHTML = '⏳ Yükleniyor...';
            grid.innerHTML = '<div class="oi-loading"><span class="oi-spinner">⏳</span> Fırsatlar yükleniyor...</div>';

            try {
                const res = await fetch(`${API}/opportunities?min_score=${minScore}&limit=50`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();

                if (!json.success || !json.data?.opportunities?.length) {
                    grid.innerHTML =
                        `<div class="oi-empty"><div class="oi-empty-icon">🔍</div><h3>Henüz fırsat tespit edilmedi</h3><p>Opportunity Engine aktif olarak portföyü tarıyor.</p></div>`;
                    updateStats(0, 0, 0, json.data?.meta?.stats);
                    renderTrendPanel([]);
                    renderInsightPanel([]);
                    return;
                }

                const ops = json.data.opportunities;
                renderFeed(ops);
                updateStats(
                    ops.length,
                    Math.max(...ops.map(o => o.opportunity_score)),
                    Math.round(ops.reduce((s, o) => s + o.opportunity_score, 0) / ops.length),
                    json.data.meta.stats
                );
                renderTrendPanel(ops);
                renderInsightPanel(ops);

            } catch (err) {
                grid.innerHTML =
                    `<div class="oi-empty"><div class="oi-empty-icon">⚠️</div><h3>Bağlantı hatası</h3><p>${esc(err.message)}</p></div>`;
            } finally {
                btn.classList.remove('loading');
                btn.innerHTML = '🔄 Yenile';
            }
        }

        function renderFeed(ops) {
            const grid = document.getElementById('feedGrid');
            grid.innerHTML = ops.map(o => {
                const sc = o.opportunity_score >= 80 ? 's-high' : o.opportunity_score >= 60 ? 's-mid' : 's-low';
                const tc = o.trend === 'rising' ? 't-up' : o.trend === 'declining' ? 't-down' : 't-flat';
                const ti = o.trend === 'rising' ? '📈' : o.trend === 'declining' ? '📉' : '➡️';
                const price = o.fiyat ? new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: 'TRY',
                    maximumFractionDigits: 0
                }).format(o.fiyat) : '—';
                return `<div class="oi-item" data-ilan="${o.ilan_id}">
            <div class="oi-score ${sc}"><span class="oi-score-num">${o.opportunity_score}</span><span class="oi-score-txt">Skor</span></div>
            <div class="oi-body">
                <h3>${esc(o.baslik)}</h3>
                <div class="oi-meta">
                    <span>💰 <strong>${price}</strong></span>
                    ${o.trend ? `<span class="oi-trend ${tc}">${ti} ${o.trend}</span>` : ''}
                    ${o.gecen_gun_sayisi !== undefined ? `<span>📅 ${o.gecen_gun_sayisi} gün</span>` : ''}
                </div>
                <div class="oi-reason-bar">${esc(o.reason || o.explanation || 'Fırsat analizi mevcut')}</div>
            </div>
            <div class="oi-actions">
                <button class="oi-act primary" onclick="event.stopPropagation(); window.location.href='/admin/ilanlar/${o.ilan_id}'">📋 İlanı Gör</button>
                <button class="oi-act" onclick="event.stopPropagation(); showDetail(${o.ilan_id})">🔍 Detay</button>
                <button class="oi-act" onclick="event.stopPropagation(); window.location.href='/api/v1/ai/buyer-matches/${o.ilan_id}'">🤝 Alıcılar</button>
            </div>
        </div>`;
            }).join('');
        }

        function renderTrendPanel(ops) {
            const panel = document.getElementById('trendPanel');
            if (!ops.length) {
                panel.innerHTML = '<p style="font-size:0.8rem;color:var(--oi-muted)">Veri bekleniyor...</p>';
                return;
            }

            // Bölge bazlı trend aggregation
            const regionMap = {};
            ops.forEach(o => {
                const key = o.il_id || 'bilinmiyor';
                if (!regionMap[key]) regionMap[key] = {
                    count: 0,
                    rising: 0,
                    declining: 0
                };
                regionMap[key].count++;
                if (o.trend === 'rising') regionMap[key].rising++;
                if (o.trend === 'declining') regionMap[key].declining++;
            });

            const rows = Object.entries(regionMap).sort((a, b) => b[1].count - a[1].count).slice(0, 6);
            panel.innerHTML = rows.map(([region, data]) => {
                const dir = data.rising > data.declining ? 'rising' : data.declining > data.rising ? 'declining' :
                    'stable';
                const dirIcon = dir === 'rising' ? '📈' : dir === 'declining' ? '📉' : '➡️';
                const dirColor = dir === 'rising' ? 'var(--oi-success)' : dir === 'declining' ? 'var(--oi-danger)' :
                    'var(--oi-muted)';
                return `<div class="oi-trend-row">
            <span class="oi-trend-region">Bölge ${region}</span>
            <span class="oi-trend-dir" style="color:${dirColor}">${dirIcon} ${data.count} fırsat</span>
        </div>`;
            }).join('');
        }

        function renderInsightPanel(ops) {
            const panel = document.getElementById('insightPanel');
            if (!ops.length) {
                panel.innerHTML = '<p style="font-size:0.8rem;color:var(--oi-muted)">Veri bekleniyor...</p>';
                return;
            }

            const insights = [];

            // Top signal analysis
            const avgScore = ops.reduce((s, o) => s + o.opportunity_score, 0) / ops.length;
            const highOps = ops.filter(o => o.opportunity_score >= 80);

            if (highOps.length > 0) {
                insights.push({
                    text: `${highOps.length} yüksek skorlu fırsat tespit edildi (≥80). Erken hareket önerilir.`,
                    time: 'Şimdi'
                });
            }

            if (avgScore >= 70) {
                insights.push({
                    text: `Portföy ortalaması güçlü (${Math.round(avgScore)}). Pazar fırsat dönemi sinyali veriyor.`,
                    time: 'Şimdi'
                });
            }

            const freshOps = ops.filter(o => o.gecen_gun_sayisi <= 3);
            if (freshOps.length > 0) {
                insights.push({
                    text: `${freshOps.length} yeni ilan son 3 gün içinde girildi. Taze fırsatlar mevcut.`,
                    time: 'Son 3 gün'
                });
            }

            if (insights.length === 0) {
                insights.push({
                    text: 'Opportunity Engine aktif olarak portföyü izliyor. Yeni sinyaller üretildiğinde burada görünecek.',
                    time: 'Aktif'
                });
            }

            panel.innerHTML = insights.map(i => `
        <div class="oi-insight-item">
            <div class="oi-insight-text">${esc(i.text)}</div>
            <div class="oi-insight-time">🕐 ${esc(i.time)}</div>
        </div>
    `).join('');
        }

        function updateStats(total, highest, avg, apiStats) {
            document.getElementById('stTotal').textContent = total;
            document.getElementById('stHigh').textContent = highest || '—';
            document.getElementById('stAvg').textContent = avg || '—';
            document.getElementById('stActive').textContent = apiStats?.total_active_listings ?? '—';
            document.getElementById('stTime').textContent = new Date().toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function showDetail(ilanId) {
            try {
                const res = await fetch(`${API}/opportunities/${ilanId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (json.success) {
                    const d = json.data;
                    const signalList = d.signals ? Object.keys(d.signals).map(k =>
                        `• ${k}: ${JSON.stringify(d.signals[k])}`).join('\n') : 'Sinyal yok';
                    alert(
                        `🎯 Fırsat Detay\n\n📊 Skor: ${d.opportunity_score}\n📌 ${d.reason}\n\n📡 Sinyaller:\n${signalList}`);
                }
            } catch (err) {
                console.error('Detail error:', err);
            }
        }

        function esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        document.getElementById('minScoreFilter').addEventListener('change', loadOpportunities);
        document.addEventListener('DOMContentLoaded', loadOpportunities);
    </script>
@endpush
