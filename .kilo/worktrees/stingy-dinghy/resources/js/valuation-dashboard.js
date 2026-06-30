/**
 * 🏗️ Context7 Valuation Dashboard JavaScript
 * Version: 1.0.0 - Enterprise Edition
 * Context7 Standard: C7-VALUATION-DASHBOARD-JS-2025-01-30
 */

class ValuationDashboard {
    constructor() {
        this.apiBaseUrl = (window.APIConfig && window.APIConfig.valuation && window.APIConfig.valuation.base) ? window.APIConfig.valuation.base : '/api/valuation';
        this.tkgmApiUrl = (window.APIConfig && window.APIConfig.tkgm) ? window.APIConfig.tkgm.parselSorgu.replace('/parsel-sorgu','') : '/api/v1/tkgm';
        this.isLoading = false;
        this.init();
    }

    /**
     * Initialize dashboard
     */
    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.startRealTimeUpdates();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="refresh"]')) {
                this.refreshDashboard();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshDashboard();
            }
        });
    }

    /**
     * Load dashboard data
     */
    async loadDashboardData() {
        try {
            this.showLoading();

            const [statistics, recentValuations, marketTrends] = await Promise.all([
                this.getStatistics(),
                this.getRecentValuations(),
                this.getMarketTrends(),
            ]);

            this.updateStatistics(statistics);
            this.updateRecentValuations(recentValuations);
            this.updateMarketTrends(marketTrends);
        } catch (error) {
            console.error('Dashboard data loading error:', error);
            this.showError('Dashboard verileri yüklenirken hata oluştu');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Get statistics data
     */
    async getStatistics() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/statistics`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Statistics fetch error:', error);
            return this.getDefaultStatistics();
        }
    }

    /**
     * Get recent valuations
     */
    async getRecentValuations() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/recent`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Recent valuations fetch error:', error);
            return { data: [] };
        }
    }

    /**
     * Get market trends
     */
    async getMarketTrends() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/market-trends?period=6`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Market trends fetch error:', error);
            return { data: [] };
        }
    }

    /**
     * Update statistics cards
     */
    updateStatistics(data) {
        if (!data.success) {
            data = this.getDefaultStatistics();
        }

        const stats = data.data || data;

        // Update total valuations
        document.getElementById('total-valuations').textContent = stats.total_valuations || 0;
        document.getElementById('total-valuations-change').textContent =
            `+${stats.total_valuations_change || 0}%`;

        // Update average value
        document.getElementById('average-value').textContent = this.formatCurrency(
            stats.average_value || 0
        );
        document.getElementById('average-value-change').textContent =
            `+${stats.average_value_change || 0}%`;

        // Update total value
        document.getElementById('total-value').textContent = this.formatCurrency(
            stats.total_value || 0
        );
        document.getElementById('total-value-change').textContent =
            `+${stats.total_value_change || 0}%`;

        // Update success rate
        document.getElementById('success-rate').textContent = `${stats.success_rate || 0}%`;
        document.getElementById('success-rate-change').textContent =
            `+${stats.success_rate_change || 0}%`;
    }

    /**
     * Update recent valuations list
     */
    updateRecentValuations(data) {
        const container = document.getElementById('recent-valuations');

        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-slate-500">Henüz değerleme yapılmamış</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.data
            .map(
                (valuation) => `
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg dark:bg-slate-950">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            Ada: ${valuation.ada}, Parsel: ${valuation.parsel}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-slate-500">
                            ${valuation.il}, ${valuation.ilce}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        ${this.formatCurrency(valuation.calculated_value)}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-slate-500">
                        ${this.formatDate(valuation.created_at)}
                    </p>
                </div>
            </div>
        `
            )
            .join('');
    }

    /**
     * Update market trends
     */
    updateMarketTrends(data) {
        const container = document.getElementById('market-trends');

        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-slate-500">Market trend verisi bulunamadı</p>
                </div>
            `;
            return;
        }

        const trendData = data.data;
        const trendPercentage = data.trend_percentage || 0;
        const trendDirection = data.trend_direction || 'stable';

        container.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">6 Aylık Trend</span>
                    <span class="text-sm font-semibold ${trendDirection === 'up' ? 'text-green-600 dark:text-green-400' : trendDirection === 'down' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'}">
                        ${trendDirection === 'up' ? '↗' : trendDirection === 'down' ? '↘' : '→'} ${Math.abs(trendPercentage)}%
                    </span>
                </div>
                <div class="space-y-2">
                    ${trendData
                        .slice(0, 3)
                        .map(
                            (trend) => `
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-slate-400">${trend.period}</span>
                            <span class="text-gray-900 dark:text-white dark:text-slate-100">${this.formatCurrency(trend.avg_price)}</span>
                        </div>
                    `
                        )
                        .join('')}
                </div>
            </div>
        `;
    }

    /**
     * Get default statistics
     */
    getDefaultStatistics() {
        return {
            success: true,
            data: {
                total_valuations: 0,
                total_valuations_change: 0,
                average_value: 0,
                average_value_change: 0,
                total_value: 0,
                total_value_change: 0,
                success_rate: 0,
                success_rate_change: 0,
            },
        };
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    /**
     * Format date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    /**
     * Show loading overlay
     */
    showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
        this.isLoading = true;
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
        this.isLoading = false;
    }

    /**
     * Show error message
     */
    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className =
            'fixed top-4 right-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-6 py-4 rounded-lg shadow-lg z-50';
        toast.innerHTML = `
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        // Remove toast after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    /**
     * Refresh dashboard
     */
    async refreshDashboard() {
        if (this.isLoading) return;

        await this.loadDashboardData();
    }

    /**
     * Start real-time updates
     */
    startRealTimeUpdates() {
        // Update dashboard every 5 minutes
        setInterval(() => {
            if (!this.isLoading) {
                this.loadDashboardData();
            }
        }, 300000); // 5 minutes
    }
}

// Global functions for quick actions
function openParcelSearch() {
    window.location.href = '/admin/valuation/parcel-search';
}

function openValuation() {
    window.location.href = '/admin/valuation/calculate';
}

function openReports() {
    window.location.href = '/admin/valuation/reports';
}

function openAnalytics() {
    window.location.href = '/admin/valuation/analytics';
}

function viewAllValuations() {
    window.location.href = '/admin/valuation/history';
}

function viewMarketTrends() {
    window.location.href = '/admin/valuation/market-trends';
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ValuationDashboard();
});
