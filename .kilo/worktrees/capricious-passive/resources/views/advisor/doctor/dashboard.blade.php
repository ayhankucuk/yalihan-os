@extends('admin.layouts.admin')

@section('title', 'AI Portfolio Doctor — Dashboard')

@push('styles')
    <style>
        :root {
            --dr-bg: #0f172a;
            --dr-surface: #1e293b;
            --dr-accent: #3b82f6;
            /* Doctor Blue */
            --dr-accent-glow: rgba(59, 130, 246, 0.15);
            --dr-success: #10b981;
            --dr-warning: #f59e0b;
            --dr-danger: #ef4444;
            --dr-text: #f1f5f9;
            --dr-muted: #94a3b8;
            --dr-border: rgba(148, 163, 184, 0.1);
            --dr-radius: 1rem;
        }

        .dr-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
            color: var(--dr-text);
        }

        /* ── Glassmorphism Core ── */
        .dr-glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--dr-border);
            border-radius: var(--dr-radius);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* ── Header ── */
        .dr-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .dr-title-group h1 {
            font-size: 2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
            background: linear-gradient(to right, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dr-title-group p {
            color: var(--dr-muted);
            margin-top: 0.5rem;
            font-size: 1rem;
        }

        /* ── Summary Cards ── */
        .dr-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .dr-stat-card {
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }

        .dr-stat-card:hover {
            transform: translateY(-5px);
        }

        .dr-stat-label {
            font-size: 0.875rem;
            color: var(--dr-muted);
            font-weight: 500;
        }

        .dr-stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        .dr-stat-trend {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            margin-top: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            width: fit-content;
        }

        /* ── List Area ── */
        .dr-list-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dr-list-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .dr-table-container {
            overflow: hidden;
        }

        .dr-listing-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1.5fr;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--dr-border);
            transition: background 0.2s;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .dr-listing-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                padding: 1.5rem;
            }

            .dr-actions {
                text-align: left !important;
                margin-top: 0.5rem;
            }

            .dr-health-score,
            .dr-issues-count {
                display: inline-block;
                margin-right: 1rem;
            }
        }

        .dr-listing-row:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .dr-listing-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .dr-listing-info p {
            font-size: 0.8rem;
            color: var(--dr-muted);
        }

        .dr-health-chip {
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            width: fit-content;
        }

        .dr-health-good {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        .dr-health-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        .dr-health-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .dr-action-btn {
            background: var(--dr-accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .dr-action-btn:hover {
            filter: brightness(1.1);
            box-shadow: 0 4px 12px var(--dr-accent-glow);
        }

        /* ── Loading Pulse ── */
        .dr-skeleton {
            background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
            background-size: 200% 100%;
            animation: dr-loading 1.5s infinite;
            border-radius: 0.5rem;
        }

        @keyframes dr-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }
    </style>


    @section('content')
        <div class="dr-page" x-data="portfolioDoctor()">
            <!-- Header -->
            <header class="dr-header">
                <div class="dr-title-group">
                    <h1><span class="material-symbols-outlined">stethoscope</span> AI Portfolio Doctor</h1>
                    <p>Portföy sağlığınız yapay zeka tarafından anlık olarak izleniyor.</p>
                </div>
                <div class="dr-controls">
                    <button @click="refresh()" class="dr-action-btn" :disabled="loading">
                        <span class="material-symbols-outlined">sync</span>
                        <span x-text="loading ? 'Analiz Ediliyor...' : 'Yeniden Tara'"></span>
                    </button>
                </div>
            </header>

            <!-- Stats Grid -->
            <section class="dr-summary-grid">
                <div class="dr-stat-card dr-glass">
                    <div class="dr-stat-label">Ortalama Portföy Sağlığı</div>
                    <div class="dr-stat-value" x-text="loading ? '...' : (summary.average_health || 0) + '%'"></div>
                    <div class="dr-stat-trend" :class="summary.average_health > 70 ? 'dr-health-good' : 'dr-health-warning'">
                        <i class="fas" :class="summary.average_health > 70 ? 'fa-arrow-up' : 'fa-info-circle'"></i>
                        <span x-text="summary.average_health > 70 ? 'Dengeli' : 'İyileştirme Gerekli'"></span>
                    </div>
                </div>

                <div class="dr-stat-card dr-glass">
                    <div class="dr-stat-label">Kritik İlanlar</div>
                    <div class="dr-stat-value" x-text="loading ? '...' : (summary.critical_issue_count || 0)"></div>
                    <div class="dr-stat-trend"
                        :class="summary.critical_issue_count === 0 ? 'dr-health-good' : 'dr-health-danger'">
                        <i class="fas"
                            :class="summary.critical_issue_count === 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
                        <span x-text="summary.critical_issue_count === 0 ? 'Mükemmel' : 'Acil Müdahale'"></span>
                    </div>
                </div>

                <div class="dr-stat-card dr-glass">
                    <div class="dr-stat-label">Toplam Taranan</div>
                    <div class="dr-stat-value" x-text="loading ? '...' : (summary.total_listings || 0)"></div>
                    <div class="dr-stat-trend" style="background: rgba(255,255,255,0.05); color: var(--dr-muted);">
                        <span class="material-symbols-outlined">search</span>
                        <span>Aktif İlanlar</span>
                    </div>
                </div>
            </section>

            <!-- Problematic Listings -->
            <section>
                <div class="dr-list-header">
                    <h2>Müdahale Gerektiren İlanlar</h2>
                    <div class="bm-live-badge" x-show="!loading">LIVE ANALYSIS</div>
                </div>

                <div class="dr-glass dr-table-container">
                    <!-- Loading State -->
                    <template x-if="loading">
                        <div class="space-y-4 p-8">
                            <div class="dr-skeleton h-16"></div>
                            <div class="dr-skeleton h-16"></div>
                            <div class="dr-skeleton h-16"></div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loading && problematic.length === 0">
                        <div class="p-12 text-center">
                            <div class="mb-4 text-4xl text-emerald-500"><span class="material-symbols-outlined">check_circle</span></div>
                            <h3 class="text-xl font-semibold">Tebrikler!</h3>
                            <p class="text-dr-muted mt-2">Portföyünüzde kritik sağlık sorunu taşıyan ilan bulunmamaktadır.</p>
                        </div>
                    </template>

                    <!-- Data State -->
                    <div x-show="!loading && problematic.length > 0">
                        <template x-for="report in problematic" :key="report.ilan.id">
                            <div class="dr-listing-row">
                                <div class="dr-listing-info">
                                    <h3 x-text="report.ilan.baslik"></h3>
                                    <p x-text="report.ilan.fiyat.toLocaleString() + ' ' + report.ilan.para_birimi"></p>
                                </div>
                                <div class="dr-health-score">
                                    <div class="dr-health-chip" :class="getHealthClass(report.health.overall_health)"
                                        x-text="report.health.overall_health + '% Sağlık'">
                                    </div>
                                </div>
                                <div class="dr-issues-count">
                                    <span class="text-dr-muted text-sm">
                                        <span class="material-symbols-outlined mr-1 text-xs">bug_report</span>
                                        <span x-text="report.health.recommendations.length"></span> Öneri
                                    </span>
                                </div>
                                <div class="dr-actions text-right">
                                    <a :href="'/advisor/listing/' + report.ilan.id + '/diagnostics'" class="dr-action-btn">
                                        <span class="material-symbols-outlined mr-1">biotech</span> Teşhis Raporu
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <script>
            function portfolioDoctor() {
                return {
                    loading: true,
                    summary: {},
                    problematic: [],

                    async init() {
                        await this.refresh();
                    },

                    async refresh() {
                        this.loading = true;
                        try {
                            // Fetch Summary
                            const summaryRes = await fetch('/api/v1/advisor/portfolio/doctor/summary');
                            const summaryData = await summaryRes.json();
                            this.summary = summaryData.data;

                            // Fetch Problematic
                            const probRes = await fetch('/api/v1/advisor/portfolio/doctor/problematic');
                            const probData = await probRes.json();
                            this.problematic = probData.data;
                        } catch (err) {
                            console.error('Doctor failed to scan portfolio:', err);
                        } finally {
                            this.loading = false;
                        }
                    },

                    getHealthClass(score) {
                        if (score >= 80) return 'dr-health-good';
                        if (score >= 40) return 'dr-health-warning';
                        return 'dr-health-danger';
                    }
                }
            }
        </script>
    @endsection
