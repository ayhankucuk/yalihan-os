/**
 * Analytics Module
 * Context7: AI usage analytics
 *
 * SADECE AI Settings sayfası için kullanılır.
 */

import { AIService } from '../services/AIService.js';

export class Analytics {
    /**
     * Load and Display Analytics
     * AI kullanım istatistiklerini yükle ve göster
     *
     * @returns {Promise<Object>} Analytics data
     */
    static async loadAnalytics() {
        try {
            // ORTAK CORE kullanılıyor!
            const analyticsData = await AIService.getAnalytics();

            if (analyticsData.success) {
                this.displayAnalytics(analyticsData.data);
                return analyticsData.data;
            } else {
                throw new Error(analyticsData.message || 'Analytics yüklenemedi');
            }
        } catch (error) {
            console.error('Load analytics error:', error);

            // Use Context7 Toast
            if (window.toast) {
                window.toast.error('❌ Analytics yüklenemedi');
            }

            return null;
        }
    }

    /**
     * Display Analytics
     * Analytics verilerini sayfada göster
     *
     * @param {Object} data - Analytics data
     */
    static displayAnalytics(data) {
        if (!data) return;

        // Update total requests
        const totalRequestsEl = document.getElementById('total_requests');
        if (totalRequestsEl && data.total_requests !== undefined) {
            totalRequestsEl.textContent = data.total_requests.toLocaleString();
        }

        // Update success rate
        const successRateEl = document.getElementById('success_rate');
        if (successRateEl && data.success_rate !== undefined) {
            successRateEl.textContent = `${data.success_rate}%`;
        }

        // Update average response time
        const avgResponseTimeEl = document.getElementById('avg_response_time');
        if (avgResponseTimeEl && data.avg_response_time !== undefined) {
            avgResponseTimeEl.textContent = `${data.avg_response_time}ms`;
        }

        // Update total cost
        const totalCostEl = document.getElementById('total_cost');
        if (totalCostEl && data.total_cost !== undefined) {
            totalCostEl.textContent = `$${data.total_cost}`;
        }

        // Update provider usage chart
        if (data.provider_usage) {
            this.updateProviderUsageChart(data.provider_usage);
        }
    }

    /**
     * Update Provider Usage Chart
     * Provider kullanım grafiğini güncelle
     *
     * @param {Object} providerUsage - Provider usage data
     */
    static updateProviderUsageChart(providerUsage) {
        const chartContainer = document.getElementById('provider_usage_chart');
        if (!chartContainer) return;

        // Simple bar chart visualization
        let html = '<div class="space-y-2">';

        Object.keys(providerUsage).forEach((provider) => {
            const usage = providerUsage[provider];
            const percentage = usage.percentage || 0;
            const count = usage.count || 0;

            html += `
                <div class="flex items-center gap-3">
                    <div class="w-24 text-xs font-medium text-gray-600 dark:text-slate-400">${provider}</div>
                    <div class="flex-1 bg-gray-200 rounded-full h-6 relative dark:bg-slate-700">
                        <div class="bg-purple-500 h-6 rounded-full flex items-center justify-end pr-2" style="width: ${percentage}%">
                            <span class="text-xs text-white font-medium">${count}</span>
                        </div>
                    </div>
                    <div class="w-16 text-xs text-right text-gray-600 dark:text-slate-400">${percentage}%</div>
                </div>
            `;
        });

        html += '</div>';
        chartContainer.innerHTML = html;
    }

    /**
     * Refresh Analytics
     * Analytics verilerini yenile
     *
     * @returns {Promise<Object>} Analytics data
     */
    static async refreshAnalytics() {
        const refreshBtn = document.getElementById('refresh_analytics');

        if (refreshBtn) {
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yenileniyor...';
            refreshBtn.disabled = true;
        }

        const data = await this.loadAnalytics();

        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-sync"></i> Yenile';
            refreshBtn.disabled = false;
        }

        return data;
    }
}

export default Analytics;
