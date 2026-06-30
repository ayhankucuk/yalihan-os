{{--
    Opportunity Board Widget

    Context7 Standard: C7-WIDGET-OPPORTUNITY-BOARD-2025-12-05
    Yalıhan Bekçi: Temiz, düzenli, karmaşık kod yok

    Top fırsatları gösteren dashboard widget'ı
--}}

<div
    class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 overflow-hidden transition-all duration-200 hover:shadow-xl dark:border-slate-700">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white dark:text-white/90" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">🎯 Acil Fırsatlar</h3>
                    <p class="text-sm text-white/80">Action Score'a göre sıralanmış</p>
                </div>
            </div>
            <button type="button" id="opportunity-widget-refresh"
                class="w-8 h-8 bg-white/20 dark:bg-white/10 hover:bg-white/30 dark:hover:bg-white/20 rounded-lg flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 backdrop-blur-sm"
                title="Yenile">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="p-6">
        {{-- Loading State --}}
        <div id="opportunity-widget-loading" class="hidden">
            <div class="flex items-center justify-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Yükleniyor...</span>
            </div>
        </div>

        {{-- Error State --}}
        <div id="opportunity-widget-error" class="hidden">
            <div
                class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                <p class="text-sm text-red-700 dark:text-red-300">Fırsatlar yüklenirken hata oluştu</p>
                <button type="button" onclick="loadOpportunities()"
                    class="mt-2 text-sm text-red-600 dark:text-red-400 hover:underline">
                    Tekrar Dene
                </button>
            </div>
        </div>

        {{-- Opportunities List --}}
        <div id="opportunity-widget-list" class="space-y-3">
            {{-- Will be populated by JavaScript --}}
        </div>

        {{-- Empty State --}}
        <div id="opportunity-widget-empty" class="hidden text-center py-8">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium">Şu an fırsat yok</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Yeni ilanlar eklendikçe fırsatlar burada görünecek
            </p>
        </div>

        {{-- Last Updated Timestamp --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <p id="opportunity-widget-last-updated" class="text-xs text-gray-500 dark:text-gray-400">
                    Son güncelleme: Yükleniyor...
                </p>
                <a href="{{ route('admin.intelligence.opportunities') }}"
                    class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200">
                    Tümünü Gör →
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            'use strict';

            const WIDGET_ID = 'opportunity-widget';
            const LIMIT = 5;
            const CACHE_KEY = 'opportunity_widget_data';
            const CACHE_TTL = 5 * 60 * 1000; // 5 dakika
            const POLLING_INTERVAL = 3 * 60 * 60 * 1000; // 3 saat (10800000 ms)

            /**
             * Priority badge renkleri
             */
            const PRIORITY_COLORS = {
                'ACIL': {
                    bg: 'bg-red-500',
                    text: 'text-white',
                    border: 'border-red-500',
                    light: 'bg-red-50 dark:bg-red-900/20'
                },
                'YÜKSEK': {
                    bg: 'bg-orange-500',
                    text: 'text-white',
                    border: 'border-orange-500',
                    light: 'bg-orange-50 dark:bg-orange-900/20'
                },
                'ORTA': {
                    bg: 'bg-yellow-500',
                    text: 'text-white',
                    border: 'border-yellow-500',
                    light: 'bg-yellow-50 dark:bg-yellow-900/20'
                },
                'DÜŞÜK': {
                    bg: 'bg-gray-500',
                    text: 'text-white',
                    border: 'border-gray-500',
                    light: 'bg-gray-50 dark:bg-gray-800'
                }
            };

            /**
             * Cache kontrolü
             */
            function getCachedData() {
                try {
                    const cached = localStorage.getItem(CACHE_KEY);
                    if (!cached) return null;

                    const data = JSON.parse(cached);
                    const now = Date.now();

                    if (now - data.timestamp < CACHE_TTL) {
                        return data.opportunities;
                    }

                    localStorage.removeItem(CACHE_KEY);
                    return null;
                } catch (e) {
                    return null;
                }
            }

            /**
             * Cache'e kaydet
             */
            function setCachedData(opportunities) {
                try {
                    localStorage.setItem(CACHE_KEY, JSON.stringify({
                        opportunities: opportunities,
                        timestamp: Date.now()
                    }));
                } catch (e) {
                    // Ignore storage errors
                }
            }

            /**
             * State yönetimi
             */
            function showState(state) {
                const states = ['loading', 'error', 'empty', 'list'];
                states.forEach(s => {
                    const el = document.getElementById(`${WIDGET_ID}-${s}`);
                    if (el) el.classList.add('hidden');
                });

                const targetEl = document.getElementById(`${WIDGET_ID}-${state}`);
                if (targetEl) targetEl.classList.remove('hidden');
            }

            /**
             * Opportunity card HTML oluştur (İyileştirilmiş)
             */
            function createOpportunityCard(opp) {
                const colors = PRIORITY_COLORS[opp.priority_level] || PRIORITY_COLORS['DÜŞÜK'];
                const matchScore = Math.min(opp.match_score || 0, 100);
                const churnRisk = Math.min(opp.churn_risk || 0, 100);
                const actionScore = Math.round(opp.action_score || 0);

                // Action Score için gradient renk
                let scoreGradient = 'from-blue-500 to-blue-600';
                if (actionScore >= 80) scoreGradient = 'from-green-500 to-green-600';
                else if (actionScore >= 60) scoreGradient = 'from-blue-500 to-blue-600';
                else if (actionScore >= 40) scoreGradient = 'from-yellow-500 to-yellow-600';
                else scoreGradient = 'from-red-500 to-red-600';

                return `
            <div class="group bg-white dark:bg-gray-700 rounded-xl p-3 sm:p-4 border-l-4 ${colors.border}
                        shadow-sm hover:shadow-xl transition-all duration-300
                        hover:scale-[1.01] sm:hover:scale-[1.02] hover:-translate-y-0.5 sm:hover:-translate-y-1
                        ${colors.light} border border-gray-200 dark:border-gray-600">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                            ${escapeHtml(opp.kisi_adi || 'Bilinmeyen')}
                        </h4>
                        ${opp.talep_baslik ? `<p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">${escapeHtml(opp.talep_baslik)}</p>` : ''}
                    </div>
                    <span class="ml-2 px-3 py-1 rounded-full text-xs font-bold ${colors.bg} ${colors.text}
                                 whitespace-nowrap shadow-md hover:shadow-lg transition-shadow">
                        ${escapeHtml(opp.priority_level || 'DÜŞÜK')}
                    </span>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Action Score</span>
                        <span class="text-xs text-gray-500 dark:text-gray-500">/100</span>
                    </div>
                    <div class="relative">
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3 mb-2 overflow-hidden">
                            <div class="bg-gradient-to-r ${scoreGradient} h-3 rounded-full transition-all duration-500 ease-out"
                                 style="width: ${actionScore}%">
                                <div class="h-full w-full bg-white/20 dark:bg-white/10 animate-pulse"></div>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">${actionScore}</span>
                            <div class="flex items-center gap-1">
                                ${actionScore >= 80 ? '<span class="text-green-500">⭐</span>' : ''}
                                ${actionScore >= 60 && actionScore < 80 ? '<span class="text-blue-500">✓</span>' : ''}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 mb-3 sm:mb-4">
                    <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-3 border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Match Score</p>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400">${Math.round(matchScore)}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full transition-all duration-500"
                                 style="width: ${matchScore}%"></div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-3 border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Churn Risk</p>
                            <span class="text-xs font-bold text-red-600 dark:text-red-400">${Math.round(churnRisk)}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                            <div class="bg-gradient-to-r from-red-400 to-red-600 h-2 rounded-full transition-all duration-500"
                                 style="width: ${churnRisk}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <a
                        href="/admin/kisiler/${opp.kisi_id}"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800
                               text-white text-xs sm:text-sm font-medium px-3 py-2 sm:py-2.5 rounded-lg text-center
                               transition-all duration-200 hover:scale-105 active:scale-95 shadow-md hover:shadow-lg
                               touch-manipulation">
                        👤 Müşteri
                    </a>
                    ${opp.top_match && opp.top_match.ilan_id ? `
                                <a
                                    href="/admin/ilanlar/${opp.top_match.ilan_id}"
                                    class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800
                                           text-white text-xs sm:text-sm font-medium px-3 py-2 sm:py-2.5 rounded-lg text-center
                                           transition-all duration-200 hover:scale-105 active:scale-95 shadow-md hover:shadow-lg
                                           touch-manipulation">
                                    🏠 İlan
                                </a>
                            ` : ''}
                </div>
            </div>
        `;
            }

            /**
             * HTML escape
             */
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            /**
             * Fırsatları yükle
             */
            function loadOpportunities(forceRefresh = false) {
                // Cache kontrolü
                if (!forceRefresh) {
                    const cached = getCachedData();
                    if (cached && cached.length > 0) {
                        renderOpportunities(cached);
                        return;
                    }
                }

                showState('loading');

                const url = window.APIConfig.intelligence.opportunities(LIMIT);

                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data && data.data.opportunities) {
                            const opportunities = data.data.opportunities;
                            setCachedData(opportunities);
                            renderOpportunities(opportunities);
                        } else {
                            throw new Error(data.message || 'Veri formatı hatalı');
                        }
                    })
                    .catch(error => {
                        console.error('Opportunity widget error:', error);
                        showState('error');
                    });
            }

            /**
             * Fırsatları render et
             */
            function renderOpportunities(opportunities) {
                const listEl = document.getElementById(`${WIDGET_ID}-list`);
                if (!listEl) return;

                if (!opportunities || opportunities.length === 0) {
                    showState('empty');
                    updateLastUpdated();
                    return;
                }

                listEl.innerHTML = opportunities.map(opp => createOpportunityCard(opp)).join('');
                showState('list');
                updateLastUpdated();
            }

            /**
             * Son güncelleme zamanını göster
             */
            function updateLastUpdated() {
                const lastUpdatedEl = document.getElementById(`${WIDGET_ID}-last-updated`);
                if (!lastUpdatedEl) return;

                const now = new Date();
                const timeStr = now.toLocaleTimeString('tr-TR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                lastUpdatedEl.textContent = `Son güncelleme: ${timeStr}`;
            }

            /**
             * Event listeners ve polling
             */
            let pollingInterval = null;

            function init() {
                const refreshBtn = document.getElementById(`${WIDGET_ID}-refresh`);
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => loadOpportunities(true));
                }

                // İlk yükleme
                loadOpportunities();

                // 3 saatte bir otomatik güncelleme
                pollingInterval = setInterval(() => {
                    loadOpportunities(true);
                }, POLLING_INTERVAL);

                // Sayfa görünürken güncelle (Visibility API)
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && pollingInterval) {
                        // Sayfa görünür olduğunda cache'i kontrol et ve gerekirse güncelle
                        const cached = getCachedData();
                        if (!cached || cached.length === 0) {
                            loadOpportunities(true);
                        }
                    }
                });

                // Global function (backward compatibility)
                window.loadOpportunities = loadOpportunities;
            }

            // DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endpush
