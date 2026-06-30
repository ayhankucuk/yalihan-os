/**
 * API Monitoring Dashboard
 *
 * Request monitoring ve istatistikleri gösteren dashboard
 * Context7: C7-API-MONITORING-2025-12-15
 *
 * @version 1.0.0
 * @since 2025-12-15
 */

/* global window */

class APIMonitoringDashboard {
    constructor(containerId = 'api-monitoring-dashboard') {
        this.container = document.getElementById(containerId);
        this.updateInterval = null;
        this.isVisible = false;
    }

    /**
     * Dashboard'u başlat
     */
    init() {
        if (!this.container) {
            console.warn('API Monitoring Dashboard container bulunamadı');
            return;
        }

        this.render();
        this.startAutoUpdate();
        this.bindEvents();
    }

    /**
     * Dashboard UI'ını render et
     */
    render() {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        📊 API Monitoring Dashboard
                    </h2>
                    <button id="refresh-stats" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Yenile
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Total Requests -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                        <div class="text-sm opacity-90 mb-1">Toplam İstek</div>
                        <div class="text-3xl font-bold" id="stat-total-requests">0</div>
                    </div>

                    <!-- Success Rate -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-4 text-white">
                        <div class="text-sm opacity-90 mb-1">Başarı Oranı</div>
                        <div class="text-3xl font-bold" id="stat-success-rate">0%</div>
                    </div>

                    <!-- Cache Hit Rate -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-4 text-white">
                        <div class="text-sm opacity-90 mb-1">Cache Hit</div>
                        <div class="text-3xl font-bold" id="stat-cache-size">0</div>
                    </div>

                    <!-- Errors -->
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg p-4 text-white">
                        <div class="text-sm opacity-90 mb-1">Hatalar</div>
                        <div class="text-3xl font-bold" id="stat-errors">0</div>
                    </div>
                </div>

                <!-- Recent Requests -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Son İstekler
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Endpoint
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Method
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Zaman
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Durum
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="recent-requests" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Recent requests will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * İstatistikleri güncelle
     */
    updateStats() {
        if (!window.APIHelper) {
            console.warn('APIHelper bulunamadı');
            return;
        }

        const stats = window.APIHelper.getStats();

        // İstatistikleri göster
        document.getElementById('stat-total-requests').textContent = stats.totalRequests || 0;
        document.getElementById('stat-cache-size').textContent = stats.cacheSize || 0;
        document.getElementById('stat-errors').textContent = stats.errors || 0;

        // Başarı oranı hesapla
        const successRate = stats.totalRequests > 0
            ? Math.round((stats.success / stats.totalRequests) * 100)
            : 0;
        document.getElementById('stat-success-rate').textContent = `${successRate}%`;

        // Son istekleri göster
        this.updateRecentRequests();
    }

    /**
     * Son istekleri güncelle
     */
    updateRecentRequests() {
        if (!window.APIHelper || !window.APIHelper.requestLog) {
            return;
        }

        const tbody = document.getElementById('recent-requests');
        if (!tbody) return;

        const recentLogs = window.APIHelper.requestLog.slice(-10).reverse();

        tbody.innerHTML = recentLogs.map(log => {
            const time = new Date(log.timestamp).toLocaleTimeString('tr-TR');
            const isError = log.method === 'ERROR';
            const statusClass = isError
                ? 'text-red-600 dark:text-red-400'
                : 'text-green-600 dark:text-green-400';

            return `
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        ${log.path || 'N/A'}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        <span class="px-2 py-1 text-xs font-medium rounded ${isError ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'}">
                            ${log.method || 'GET'}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        ${time}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm ${statusClass}">
                        ${isError ? '❌ Hata' : '✅ Başarılı'}
                    </td>
                </tr>
            `;
        }).join('');

        if (recentLogs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        Henüz istek yok
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Otomatik güncellemeyi başlat
     */
    startAutoUpdate() {
        this.updateStats();
        this.updateInterval = setInterval(() => {
            if (this.isVisible) {
                this.updateStats();
            }
        }, 5000); // Her 5 saniyede bir güncelle
    }

    /**
     * Otomatik güncellemeyi durdur
     */
    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    /**
     * Event listener'ları bağla
     */
    bindEvents() {
        // Yenile butonu
        const refreshBtn = document.getElementById('refresh-stats');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.updateStats();
            });
        }

        // Visibility API - Sayfa görünürken güncelle
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            if (this.isVisible) {
                this.updateStats();
            }
        });
    }

    /**
     * Dashboard'u temizle
     */
    destroy() {
        this.stopAutoUpdate();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Global erişim
if (typeof window !== 'undefined') {
    window.APIMonitoringDashboard = APIMonitoringDashboard;
}
