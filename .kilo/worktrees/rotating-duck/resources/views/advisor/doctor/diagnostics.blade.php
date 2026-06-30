@extends('admin.layouts.admin')

@section('title', 'AI Listing Diagnostics — ' . $ilan->baslik)

@push('styles')
    <style>
        :root {
            --dg-bg: #0f172a;
            --dg-surface: #1e293b;
            --dg-accent: #3b82f6;
            --dg-text: #f1f5f9;
            --dg-muted: #94a3b8;
            --dg-border: rgba(148, 163, 184, 0.1);
            --dg-radius: 1rem;
            --dg-success: #10b981;
            --dg-warning: #f59e0b;
            --dg-danger: #ef4444;
        }

        .dg-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            color: var(--dg-text);
        }

        .dg-glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid var(--dg-border);
            border-radius: var(--dg-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* ── Header ── */
        .dg-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .dg-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .dg-breadcrumb {
            color: var(--dg-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        /* ── Breakdown Grid ── */
        .dg-breakdown {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .dg-gauge-card {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .dg-gauge-outer {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 1rem;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
        }

        .dg-gauge-value {
            font-size: 2.5rem;
            font-weight: 800;
        }

        /* ── Detail Scores ── */
        .dg-score-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--dg-border);
        }

        .dg-score-row:last-child {
            border: none;
        }

        .dg-score-label {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dg-progress-bg {
            width: 120px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            overflow: hidden;
        }

        .dg-progress-fill {
            height: 100%;
            border-radius: 999px;
        }

        /* ── Treatment Plan ── */
        .dg-treatment-item {
            display: flex;
            gap: 1.25rem;
            padding: 1.5rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 1rem;
            border-left: 4px solid transparent;
        }

        .dg-treatment-priority-high {
            border-left-color: var(--dg-danger);
        }

        .dg-treatment-priority-medium {
            border-left-color: var(--dg-warning);
        }

        .dg-treatment-icon {
            font-size: 1.5rem;
            padding-top: 0.25rem;
        }

        .dg-treatment-content h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .dg-treatment-content p {
            color: var(--dg-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .dg-impact-badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            margin-top: 0.75rem;
            display: inline-block;
        }
    </style>


    @section('content')
        <div class="dg-page" x-data="diagnosticsHandler()">
            <!-- Breadcrumb -->
            <div class="dg-breadcrumb">
                <a href="{{ route('advisor.portfolio-doctor') }}" class="hover:text-white">AI Portfolio Doctor</a> / Teşhis Raporu
            </div>

            <!-- Header -->
            <header class="dg-header">
                <div>
                    <h1>{{ $ilan->baslik }}</h1>
                    <p class="text-dg-muted mt-1">{{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }} •
                        {{ $ilan->altKategori->name ?? 'Gayrimenkul' }}</p>
                </div>
                <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}" class="dr-action-btn">
                    <span class="material-symbols-outlined mr-1">edit</span> İlanı Düzenle
                </a>
            </header>

            <!-- Results Area -->
            <template x-if="loading">
                <div class="dg-glass py-20 text-center">
                    <div class="text-dg-accent mb-4 animate-spin text-4xl"><span class="material-symbols-outlined">progress_activity</span></div>
                    <h3 class="text-xl font-bold">Analiz Ediliyor...</h3>
                    <p class="text-dg-muted mt-2">Cortex AI tüm sinyalleri işleyerek teşhis raporu oluşturuyor.</p>
                </div>
            </template>

            <div x-show="!loading" style="display: none;">
                <!-- Core Health & Breakdown -->
                <div class="dg-breakdown" x-show="!loading">
                    <!-- Gauge Card -->
                    <div class="dg-glass dg-gauge-card">
                        <div class="dg-gauge-outer"
                            :style="'border: 8px solid ' + getHealthColor(report.health.overall_health)">
                            <div class="dg-gauge-value" :style="'color: ' + getHealthColor(report.health.overall_health)"
                                x-text="report.health.overall_health + '%'">
                            </div>
                        </div>
                        <h3 class="text-xl font-bold" x-text="report.health.overall_health >= 70 ? 'SAĞLIKLI' : 'RİSKLİ'"></h3>
                        <p class="text-dg-muted mt-2 text-sm" x-text="report.diagnosis"></p>
                    </div>

                    <!-- Scores Detail -->
                    <div class="dg-glass">
                        <h3 class="mb-6 text-lg font-bold">Metrik Kırılımı</h3>

                        <div class="dg-score-row">
                            <div class="dg-score-label"><span class="material-symbols-outlined text-blue-400">trending_up</span> Market Dengesi</div>
                            <div class="flex items-center gap-4">
                                <div class="dg-progress-bg">
                                    <div class="dg-progress-fill bg-blue-500"
                                        :style="'width:' + report.health.scores.market.score + '%'"></div>
                                </div>
                                <span class="whitespace-nowrap font-bold"
                                    x-text="report.health.scores.market.score + '%'"></span>
                            </div>
                        </div>

                        <div class="dg-score-row">
                            <div class="dg-score-label"><span class="material-symbols-outlined text-emerald-400">storage</span> Veri Kalitesi</div>
                            <div class="flex items-center gap-4">
                                <div class="dg-progress-bg">
                                    <div class="dg-progress-fill bg-emerald-500"
                                        :style="'width:' + report.health.scores.quality.score + '%'"></div>
                                </div>
                                <span class="whitespace-nowrap font-bold"
                                    x-text="report.health.scores.quality.score + '%'"></span>
                            </div>
                        </div>

                        <div class="dg-score-row">
                            <div class="dg-score-label"><span class="material-symbols-outlined text-amber-400">search</span> SEO & Görünürlük</div>
                            <div class="flex items-center gap-4">
                                <div class="dg-progress-bg">
                                    <div class="dg-progress-fill bg-amber-500"
                                        :style="'width:' + report.health.scores.seo.score + '%'"></div>
                                </div>
                                <span class="whitespace-nowrap font-bold" x-text="report.health.scores.seo.score + '%'"></span>
                            </div>
                        </div>

                        <div class="dg-score-row">
                            <div class="dg-score-label"><span class="material-symbols-outlined text-purple-400">handshake</span> Eşleşme Potansiyeli
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="dg-progress-bg">
                                    <div class="dg-progress-fill bg-purple-500"
                                        :style="'width:' + report.health.scores.match.score + '%'"></div>
                                </div>
                                <span class="whitespace-nowrap font-bold"
                                    x-text="report.health.scores.match.score + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treatment Plan -->
                <section class="dg-glass">
                    <h3 class="mb-6 text-xl font-bold"><span class="material-symbols-outlined text-dg-accent mr-2">medical_information</span> Tedavi Planı &
                        Aksiyonlar</h3>

                    <template x-if="report.treatment_plan.length === 0">
                        <div class="text-dg-muted py-8 text-center">
                            <p>Bu ilan için kritik bir aksiyon saptanmadı. Genel performansı takip edin.</p>
                        </div>
                    </template>

                    <template x-for="action in report.treatment_plan" :key="action.action_id">
                        <div class="dg-treatment-item"
                            :class="action.priority === 'high' ? 'dg-treatment-priority-high' :
                                'dg-treatment-priority-medium'">
                            <div class="dg-treatment-icon">
                                <i class="fas" :class="getIcon(action.action_id)"></i>
                            </div>
                            <div class="dg-treatment-content">
                                <h4 x-text="action.title"></h4>
                                <p x-text="action.description"></p>
                                <div class="dg-impact-badge" x-text="'Etki: ' + action.impact"></div>
                            </div>
                        </div>
                    </template>
                </section>
            </div>
        </div>

        <script>
            function diagnosticsHandler() {
                return {
                    loading: true,
                    report: {},
                    ilanId: {{ $ilan->id }},

                    async init() {
                        try {
                            const res = await fetch('/api/advisor/portfolio/doctor/diagnostics/' + this.ilanId);
                            const data = await res.json();
                            this.report = data.data;
                        } catch (err) {
                            console.error('Diagnostics failed:', err);
                        } finally {
                            this.loading = false;
                        }
                    },

                    getHealthColor(score) {
                        if (score >= 80) return '#10b981';
                        if (score >= 40) return '#f59e0b';
                        return '#ef4444';
                    },

                    getIcon(id) {
                        if (id.includes('market')) return 'fa-tag text-blue-400';
                        if (id.includes('quality')) return 'fa-check-double text-emerald-400';
                        if (id.includes('seo')) return 'fa-rocket text-amber-400';
                        return 'fa-lightbulb text-purple-400';
                    }
                }
            }
        </script>
    @endsection
