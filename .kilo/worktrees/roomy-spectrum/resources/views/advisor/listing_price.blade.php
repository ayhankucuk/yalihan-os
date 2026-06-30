@extends('admin.layouts.admin')

@section('title', 'AI Fiyat Danışmanı — Yalıhan AI')

@push('styles')
    <style>
        :root {
            --pa-bg: #0f172a;
            --pa-surface: #1e293b;
            --pa-accent: #3b82f6;
            --pa-accent-glow: rgba(59, 130, 246, 0.15);
            --pa-success: #10b981;
            --pa-warning: #f59e0b;
            --pa-danger: #ef4444;
            --pa-text: #f1f5f9;
            --pa-muted: #94a3b8;
            --pa-border: rgba(148, 163, 184, 0.1);
            --pa-radius: 1rem;
        }

        .pa-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .pa-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .pa-title-area h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--pa-text);
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.025em;
        }

        .pa-title-area p {
            color: var(--pa-muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .pa-badge {
            background: var(--pa-accent-glow);
            color: var(--pa-accent);
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* ── Main Dashboard ── */
        .pa-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
            align-items: start;
        }

        .pa-main-card {
            background: var(--pa-surface);
            border: 1px solid var(--pa-border);
            border-radius: var(--pa-radius);
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .pa-price-hero {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 1px solid var(--pa-border);
            margin-bottom: 2rem;
        }

        .pa-hero-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--pa-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
        }

        .pa-hero-value {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--pa-text);
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .pa-hero-value span {
            font-size: 1.5rem;
            opacity: 0.6;
            margin-left: 0.5rem;
        }

        .pa-hero-range {
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.75rem 1.5rem;
            border-radius: 3rem;
            border: 1px solid var(--pa-border);
        }

        .pa-range-item {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .pa-range-val {
            font-weight: 700;
            color: var(--pa-text);
        }

        .pa-range-label {
            font-size: 0.7rem;
            color: var(--pa-muted);
            text-transform: uppercase;
        }

        /* ── Metrics Grid ── */
        .pa-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .pa-metric-panel {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--pa-border);
            padding: 1.25rem;
            border-radius: 0.75rem;
            text-align: center;
        }

        .pa-metric-icon {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .pa-metric-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--pa-muted);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .pa-metric-val {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--pa-text);
        }

        /* ── Insights ── */
        .pa-insights {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .pa-panel {
            background: var(--pa-surface);
            border: 1px solid var(--pa-border);
            border-radius: var(--pa-radius);
            padding: 1.5rem;
        }

        .pa-panel-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--pa-text);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .pa-explanation-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .pa-explanation-item {
            display: flex;
            gap: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
            color: var(--pa-text);
        }

        .pa-explanation-dot {
            width: 8px;
            height: 8px;
            background: var(--pa-accent);
            border-radius: 50%;
            margin-top: 0.4rem;
            flex-shrink: 0;
        }

        .pa-skeleton {
            background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        @media (max-width: 992px) {
            .pa-grid {
                grid-template-columns: 1fr;
            }

            .pa-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="pa-container">
        <!-- Header -->
        <header class="pa-header">
            <div class="pa-title-area">
                <div class="pa-badge">💎 Decision Augmentation — Phase 19</div>
                <h1>AI Fiyat Danışmanı</h1>
                <p>{{ $ilan->baslik }}</p>
            </div>
            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}" class="pa-badge"
                style="background: var(--pa-surface); color: var(--pa-muted); text-decoration: none;">
                🔙 İlana Dön
            </a>
        </header>

        <!-- Loader State -->
        <div id="paLoader" class="pa-main-card" style="text-align: center; padding: 4rem;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🧠</div>
            <h2 style="color: var(--pa-text);">AI Piyasa Verilerini Analiz Ediyor...</h2>
            <p style="color: var(--pa-muted);">Market intelligence, rakip haritası ve satış tahminleri orkestre ediliyor.
            </p>
        </div>

        <!-- Dashboard Content -->
        <div id="paContent" class="pa-grid" style="display: none;">
            <!-- Left Side: Pricing Breakdown -->
            <div>
                <div class="pa-main-card">
                    <div class="pa-price-hero">
                        <div class="pa-hero-label">Önerilen İdeal Fiyat</div>
                        <div class="pa-hero-value" id="valRecommended">0 <span>₺</span></div>

                        <div class="pa-hero-range">
                            <div class="pa-range-item">
                                <span class="pa-range-label">Tahmini Değer</span>
                                <span class="pa-range-val" id="valEstimate">0 ₺</span>
                            </div>
                            <div style="width: 1px; height: 20px; background: var(--pa-border);"></div>
                            <div class="pa-range-item">
                                <span class="pa-range-label">Piyasa Aralığı</span>
                                <span class="pa-range-val" id="valRange">0 - 0 ₺</span>
                            </div>
                        </div>
                    </div>

                    <div class="pa-metrics">
                        <div class="pa-metric-panel">
                            <span class="pa-metric-icon">📍</span>
                            <div class="pa-metric-title">Piyasa Konumu</div>
                            <div class="pa-metric-val" id="valPosition">-</div>
                        </div>
                        <div class="pa-metric-panel">
                            <span class="pa-metric-icon">⏱️</span>
                            <div class="pa-metric-title">Tahmini Satış</div>
                            <div class="pa-metric-val" id="valSaleDays">- Gün</div>
                        </div>
                        <div class="pa-metric-panel">
                            <span class="pa-metric-icon">🎯</span>
                            <div class="pa-metric-title">Güven Skoru</div>
                            <div class="pa-metric-val" id="valConfidence">%0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Insights -->
            <div class="pa-insights">
                <div class="pa-panel">
                    <div class="pa-panel-title">💡 Karar Gerekçesi</div>
                    <ul class="pa-explanation-list" id="valExplanation">
                        <!-- Items injected by JS -->
                    </ul>
                </div>

                <div class="pa-panel">
                    <div class="pa-panel-title">🛡️ Piyasa Sinyali</div>
                    <div id="valSignalArea"
                        style="padding: 1rem; border-radius: 0.5rem; text-align: center; font-weight: 800;">
                        -
                    </div>
                    <p style="font-size: 0.8rem; color: var(--pa-muted); margin-top: 1rem; line-height: 1.4;">
                        Bu tahmin, mahalle bazlı TKGM trendleri, son 90 gündeki benzer ilanlar ve makroekonomik mevsimsel
                        veriler kullanılarak üretilmiştir.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ilanId = {{ $ilan->id }};
            const loader = document.getElementById('paLoader');
            const content = document.getElementById('paContent');

            // Format Currency
            const fmt = (val) => new Intl.NumberFormat('tr-TR', {
                maximumFractionDigits: 0
            }).format(val);

            // Fetch Analysis
            fetch(`/api/advisor/listings/${ilanId}/price-advisor`)
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        const data = json.data;

                        // Update UI
                        document.getElementById('valRecommended').innerHTML = fmt(data.recommended_price) +
                            ' <span>₺</span>';
                        document.getElementById('valEstimate').innerText = fmt(data.price_estimate) + ' ₺';
                        document.getElementById('valRange').innerText = fmt(data.price_range.min) + ' - ' + fmt(
                            data.price_range.max) + ' ₺';

                        const posMap = {
                            'below_market': {
                                text: 'Piyasa Altı',
                                color: 'var(--pa-success)'
                            },
                            'above_market': {
                                text: 'Piyasa Üstü',
                                color: 'var(--pa-danger)'
                            },
                            'fair_market': {
                                text: 'Piyasa Bandında',
                                color: 'var(--pa-accent)'
                            },
                            'neutral': {
                                text: 'Nötr',
                                color: 'var(--pa-muted)'
                            }
                        };
                        const pos = posMap[data.market_position] || posMap.neutral;
                        document.getElementById('valPosition').innerText = pos.text;
                        document.getElementById('valPosition').style.color = pos.color;

                        document.getElementById('valSaleDays').innerText = data.predicted_sale_days + ' Gün';
                        document.getElementById('valConfidence').innerText = '%' + Math.round(data.confidence *
                            100);

                        // Explanation List
                        const explanationList = document.getElementById('valExplanation');
                        explanationList.innerHTML = '';
                        data.explanation.forEach(text => {
                            const li = document.createElement('li');
                            li.className = 'pa-explanation-item';
                            li.innerHTML = `<span class="pa-explanation-dot"></span><div>${text}</div>`;
                            explanationList.appendChild(li);
                        });

                        // Signal Area
                        const signalMap = {
                            'STRONG BUY': {
                                bg: 'rgba(16, 185, 129, 0.1)',
                                color: '#10b981',
                                text: 'GÜÇLÜ AL / SATIŞ ZOR'
                            },
                            'BUY': {
                                bg: 'rgba(59, 130, 246, 0.1)',
                                color: '#3b82f6',
                                text: 'ALIM FIRSATI'
                            },
                            'WAIT': {
                                bg: 'rgba(245, 158, 11, 0.1)',
                                color: '#f59e0b',
                                text: 'FİYAT DÜZELTMESİ BEKLENİYOR'
                            },
                            'SELL': {
                                bg: 'rgba(239, 68, 68, 0.1)',
                                color: '#ef4444',
                                text: 'SATIŞ ÖNERİSİ'
                            },
                            'NEUTRAL': {
                                bg: 'rgba(148, 163, 184, 0.1)',
                                color: '#94a3b8',
                                text: 'STABİL PİYASA'
                            }
                        };
                        const sig = signalMap[data.meta.forecast_signal] || signalMap.NEUTRAL;
                        const sigArea = document.getElementById('valSignalArea');
                        sigArea.innerText = sig.text;
                        sigArea.style.backgroundColor = sig.bg;
                        sigArea.style.color = sig.color;

                        // Show Content
                        loader.style.display = 'none';
                        content.style.display = 'grid';
                    } else {
                        alert('Hata: ' + json.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Analiz yüklenirken teknik bir hata oluştu.');
                });
        });
    </script>
@endpush
