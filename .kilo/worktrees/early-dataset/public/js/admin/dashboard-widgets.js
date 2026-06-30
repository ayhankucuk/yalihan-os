/**
 * Dashboard Widgets Manager
 *
 * Merkezi widget yönetim sistemi
 * - Real-time güncelleme (polling)
 * - Skeleton loaders
 * - Error handling
 * - Dark mode support
 *
 * Context7 Compliant
 * - Vanilla JS only (no jQuery)
 * - Tailwind CSS only (no Bootstrap/Neo Design)
 * - Centralized API config
 *
 * @global window
 * @global document
 * @global fetch
 * @global setTimeout
 * @global setInterval
 * @global clearInterval
 * @global console
 * @global URL
 */

class DashboardWidgetsManager {
    constructor() {
        this.widgets = new Map();
        this.updateIntervals = new Map();
        this.defaultInterval = 30 * 60 * 1000; // 30 dakika
        this.init();
    }

    init() {
        // Tüm widget'ları bul ve kaydet
        this.registerWidgets();

        // Auto-refresh başlat
        this.startAutoRefresh();

        // Visibility API ile sayfa görünürken güncelle
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshAllWidgets();
            }
        });
    }

    registerWidgets() {
        // Opportunity Board Widget
        const opportunityWidget = document.getElementById('opportunity-board-widget');
        if (opportunityWidget) {
            // ✅ Context7: Merkezi API config kullan (fallback kaldırıldı)
            const getEndpoint = () => {
                if (!window.APIConfig?.intelligence?.opportunities) {
                    console.error('❌ Intelligence opportunities endpoint not configured');
                    throw new Error('API endpoint yapılandırması eksik: intelligence.opportunities');
                }
                const fn = window.APIConfig.intelligence.opportunities;
                return typeof fn === 'function' ? fn(5) : fn;
            };

            this.widgets.set('opportunity-board', {
                element: opportunityWidget,
                endpoint: getEndpoint(),
                interval: 30 * 60 * 1000, // 30 dakika
                render: this.renderOpportunityBoard.bind(this),
            });

            // İlk yükleme
            setTimeout(() => this.refreshWidget('opportunity-board'), 500);
        }

        // Market Analysis Widget
        const marketAnalysisWidget = document.getElementById('market-analysis-widget');
        if (marketAnalysisWidget) {
            // ✅ Context7: Merkezi API config kullan (fallback kaldırıldı)
            const getEndpoint = () => {
                if (window.APIConfig?.marketAnalysis?.regional) {
                    const fn = window.APIConfig.marketAnalysis.regional;
                    return typeof fn === 'function' ? fn() : fn;
                }
                if (window.APIConfig?.marketAnalysis?.analysis) {
                    const fn = window.APIConfig.marketAnalysis.analysis;
                    return typeof fn === 'function' ? fn(48) : fn;
                }
                console.error('❌ Market analysis endpoint not configured');
                throw new Error('API endpoint yapılandırması eksik: marketAnalysis');
            };

            this.widgets.set('market-analysis', {
                element: marketAnalysisWidget,
                endpoint: getEndpoint(),
                interval: 60 * 60 * 1000, // 1 saat
                render: this.renderMarketAnalysis.bind(this),
                params: { il_id: 48 }, // Default Muğla
            });

            // İlk yükleme
            setTimeout(() => this.refreshWidget('market-analysis'), 1000);
        }
    }

    startAutoRefresh() {
        this.widgets.forEach((widget, key) => {
            const intervalId = setInterval(() => {
                this.refreshWidget(key);
            }, widget.interval || this.defaultInterval);

            this.updateIntervals.set(key, intervalId);
        });
    }

    async refreshWidget(widgetKey) {
        const widget = this.widgets.get(widgetKey);
        if (!widget) return;

        // Skeleton loader göster
        this.showSkeleton(widget.element);

        try {
            const endpoint = widget.endpoint;
            const url = this.buildUrl(endpoint, widget.params);

            // ✅ API Helper kullan (merkezi yönetim)
            let data;
            if (window.APIHelper) {
                const result = await window.APIHelper.request(url, {
                    method: 'GET',
                }, {
                    showLoading: false, // Widget kendi loading'ini yönetiyor
                });
                data = result.data || result;
            } else {
                // Fallback: Eski kod
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                data = await response.json();
            }

            if (data.success) {
                // API response format: { success: true, data: { opportunities: [...] } }
                const widgetData = data.data || data;
                widget.render(widget.element, widgetData);
                this.showLastUpdated(widget.element);
            } else {
                throw new Error(data.message || 'Veri alınamadı');
            }
        } catch (error) {
            console.error(`Widget refresh error (${widgetKey}):`, error);
            this.showError(widget.element, error.message);
        }
    }

    async refreshAllWidgets() {
        const promises = Array.from(this.widgets.keys()).map((key) => this.refreshWidget(key));
        await Promise.allSettled(promises);
    }

    buildUrl(endpoint, params = {}) {
        if (typeof endpoint === 'function') {
            return endpoint(params);
        }

        const url = new URL(endpoint, window.location.origin);
        Object.entries(params || {}).forEach(([key, value]) => {
            url.searchParams.append(key, value);
        });
        return url.toString();
    }

    showSkeleton(element) {
        if (!element) return;

        const widgetId = element.id;
        let skeleton = '';

        // Widget-specific skeleton loaders
        if (widgetId === 'opportunity-board-widget') {
            skeleton = `
                <div class="space-y-3">
                    ${Array.from(
                        { length: 3 },
                        () => `
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 animate-pulse">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                                    <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                                </div>
                                <div class="h-6 w-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="bg-gray-100 dark:bg-gray-700 rounded p-2">
                                    <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2 mb-2"></div>
                                    <div class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mb-1"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700 rounded p-2">
                                    <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2 mb-2"></div>
                                    <div class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mb-1"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                                </div>
                            </div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        </div>
                    `
                    ).join('')}
                </div>
            `;
        } else if (widgetId === 'market-analysis-widget') {
            skeleton = `
                <div class="space-y-4 animate-pulse">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-2/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-2/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2 mb-2"></div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full mb-1"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-1/4"></div>
                    </div>
                </div>
            `;
        } else {
            // Generic skeleton
            skeleton = `
                <div class="animate-pulse space-y-4">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                </div>
            `;
        }

        const content = element.querySelector('.widget-content');
        if (content) {
            content.innerHTML = skeleton;
        }
    }

    showError(element, message) {
        if (!element) return;

        const widgetKey = element.id.replace('-widget', '');
        const errorHtml = `
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                    <svg class="w-8 h-8 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Hata Oluştu</p>
                <p class="text-xs text-red-600 dark:text-red-400 mb-4">${this.escapeHtml(message)}</p>
                <button onclick="window.dashboardWidgets?.refreshWidget('${widgetKey}')"
                        class="inline-flex items-center px-4 py-2 text-xs font-medium bg-red-100 hover:bg-red-200 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg transition-all duration-200 hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Tekrar Dene
                </button>
            </div>
        `;

        const content = element.querySelector('.widget-content');
        if (content) {
            content.innerHTML = errorHtml;
        }
    }

    showLastUpdated(element) {
        if (!element) return;

        const lastUpdated = element.querySelector('.last-updated');
        if (lastUpdated) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
            lastUpdated.innerHTML = `
                <span class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Son güncelleme: ${timeStr}
                </span>
            `;
        }
    }

    renderOpportunityBoard(element, data) {
        // API response format: { opportunities: [...] } veya direkt array
        const opportunities = data.opportunities || (Array.isArray(data) ? data : []);
        const content = element.querySelector('.widget-content');

        if (!content) return;

        if (opportunities.length === 0) {
            content.innerHTML = `
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm font-semibold">Şu an fırsat yoktur</p>
                </div>
            `;
            return;
        }

        // İlk 3 fırsatı göster (dashboard widget için)
        const topOpportunities = opportunities.slice(0, 3);

        const html = topOpportunities
            .map((opp) => {
                const priorityColors = {
                    ACIL: 'border-red-500 bg-red-50 dark:bg-red-900/20',
                    YÜKSEK: 'border-orange-500 bg-orange-50 dark:bg-orange-900/20',
                    ORTA: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
                    default: 'border-gray-500',
                };

                const badgeColors = {
                    ACIL: 'bg-red-500 text-white',
                    YÜKSEK: 'bg-orange-500 text-white',
                    ORTA: 'bg-yellow-500 text-white',
                    default: 'bg-gray-500 text-white',
                };

                return `
                <div class="opportunity-card bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 shadow-md
                            transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1
                            ${priorityColors[opp.priority_level] || priorityColors.default}
                            animate-fade-in">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">${this.escapeHtml(opp.kisi_adi || 'N/A')}</h4>
                            ${opp.talep_baslik ? `<p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">${this.escapeHtml(opp.talep_baslik)}</p>` : ''}
                            <div class="flex items-center mt-2 space-x-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Action Score:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    ${opp.action_score || 0}/100
                                </span>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ml-2 flex-shrink-0 ${badgeColors[opp.priority_level] || badgeColors.default}">
                            ${opp.priority_level || 'DÜŞÜK'}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Match</p>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">${opp.match_score || 0}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-500 ease-out shadow-sm dark:shadow-none"
                                     style="width: ${Math.min(opp.match_score || 0, 100)}%"></div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Churn</p>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">${opp.churn_risk || 0}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                                <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full transition-all duration-500 ease-out shadow-sm dark:shadow-none"
                                     style="width: ${Math.min(opp.churn_risk || 0, 100)}%"></div>
                            </div>
                        </div>
                    </div>

                    <a href="/admin/kisiler/${opp.kisi_id}"
                       class="block w-full text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg
                              transition-all duration-200 hover:scale-105 active:scale-95 shadow-sm hover:shadow-md
                              dark:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            Detaylar
                        </span>
                    </a>
                </div>
            `;
            })
            .join('');

        content.innerHTML = `
            <div class="space-y-3">
                ${html}
            </div>
        `;

        // Add fade-in animation to cards
        const cards = content.querySelectorAll('.opportunity-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    renderMarketAnalysis(element, data) {
        const content = element.querySelector('.widget-content');
        if (!content) return;

        const analysis = data.analysis || data || {};
        const summary = analysis.summary || {};
        const hotspots = analysis.hotspots || [];

        let hotspotsHtml = '';
        if (hotspots.length > 0) {
            hotspotsHtml = `
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Top Hotspots</p>
                    <div class="space-y-2">
                        ${hotspots
                            .slice(0, 3)
                            .map(
                                (hotspot, index) => `
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center space-x-2 min-w-0 flex-1">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-bold flex-shrink-0">
                                        ${index + 1}
                                    </span>
                                    <span class="text-xs font-medium text-gray-900 dark:text-white truncate">${this.escapeHtml(hotspot.mahalle_adi || 'N/A')}</span>
                                </div>
                                <span class="text-xs font-bold text-green-600 dark:text-green-400 ml-2 flex-shrink-0">+${hotspot.growth_rate || 0}%</span>
                            </div>
                        `
                            )
                            .join('')}
                    </div>
                </div>
            `;
        }

        content.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm hover:shadow-md transition-all duration-200 dark:shadow-none">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Ortalama Fiyat</p>
                            <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            ₺${(summary.avg_unit_price || 0).toLocaleString('tr-TR')}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm hover:shadow-md transition-all duration-200 dark:shadow-none">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Satış Hızı</p>
                            <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            ${summary.avg_days_to_sell || 0} <span class="text-sm font-normal text-gray-500 dark:text-gray-400">gün</span>
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm dark:shadow-none">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                        <span class="text-xs font-bold text-green-600 dark:text-green-400">${(summary.conversion_rate || 0).toFixed(1)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5 mb-1">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-2.5 rounded-full transition-all duration-500 ease-out shadow-sm dark:shadow-none"
                             style="width: ${Math.min(summary.conversion_rate || 0, 100)}%"></div>
                    </div>
                </div>

                ${hotspotsHtml}
            </div>
        `;

        // Add fade-in animation with stagger effect
        const cards = content.querySelectorAll('div[class*="bg-white"], div[class*="bg-gray"]');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 120);
        });
    }

    destroy() {
        // Tüm interval'ları temizle
        this.updateIntervals.forEach((intervalId) => clearInterval(intervalId));
        this.updateIntervals.clear();
        this.widgets.clear();
    }
}

// Global instance
let dashboardWidgets;

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        dashboardWidgets = new DashboardWidgetsManager();
    });
} else {
    dashboardWidgets = new DashboardWidgetsManager();
}

// Export for manual refresh
window.dashboardWidgets = dashboardWidgets;
