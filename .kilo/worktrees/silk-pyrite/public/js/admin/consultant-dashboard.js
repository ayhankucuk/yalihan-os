/**
 * Advanced Consultant Dashboard
 * Modern Analytics & Performance Tracking
 * Chart.js + Modern UI/UX
 */

class ConsultantDashboard {
    constructor(options = {}) {
        this.container = options.container || '#consultant-dashboard';
        this.apiEndpoint = options.apiEndpoint || (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.consultants && window.APIConfig.admin.consultants.dashboard ? window.APIConfig.admin.consultants.dashboard : '/api/admin/consultants/dashboard');
        this.consultantId = options.consultantId || null;
        this.refreshInterval = options.refreshInterval || 300000; // 5 minutes

        this.charts = {};
        this.refreshTimer = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.createDashboardInterface();
        this.attachStyles();
        this.loadDashboardData();
        this.bindEvents();
        this.startAutoRefresh();
    }

    createDashboardInterface() {
        const container = document.querySelector(this.container);
        if (!container) return;

        container.innerHTML = `
            <div class="neo-consultant-dashboard">
                <!-- Dashboard Header -->
                <div class="neo-dashboard-header">
                    <div class="neo-header-content">
                        <div class="neo-consultant-info">
                            <div class="neo-consultant-avatar" id="consultant-avatar">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div class="neo-consultant-details">
                                <h1 class="neo-consultant-name" id="consultant-name">Danışman Adı</h1>
                                <p class="neo-consultant-title" id="consultant-title">Emlak Danışmanı</p>
                            </div>
                        </div>
                        <div class="neo-dashboard-actions">
                            <button class="neo-btn neo-neo-btn neo-btn-secondary" id="refresh-dashboard">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" stroke="currentColor" stroke-width="2"/>
                                    <path d="M21 3v5h-5" stroke="currentColor" stroke-width="2"/>
                                    <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 16H3v5" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Yenile
                            </button>
                            <button class="neo-btn neo-neo-btn neo-btn-primary" id="export-report">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2"/>
                                    <polyline points="7,10 12,15 17,10" stroke="currentColor" stroke-width="2"/>
                                    <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Rapor İndir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards Row -->
                <div class="neo-kpi-grid">
                    <div class="neo-kpi-card neo-kpi-primary">
                        <div class="neo-kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="neo-kpi-content">
                            <div class="neo-kpi-value" id="total-listings">0</div>
                            <div class="neo-kpi-label">Toplam İlan</div>
                            <div class="neo-kpi-change positive" id="listings-change">+0%</div>
                        </div>
                    </div>

                    <div class="neo-kpi-card neo-kpi-success">
                        <div class="neo-kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="neo-kpi-content">
                            <div class="neo-kpi-value" id="active-listings">0</div>
                            <div class="neo-kpi-label">Aktif İlan</div>
                            <div class="neo-kpi-change positive" id="active-change">+0%</div>
                        </div>
                    </div>

                    <div class="neo-kpi-card neo-kpi-warning">
                        <div class="neo-kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/>
                                <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <line x1="20" y1="8" x2="20" y2="14" stroke="currentColor" stroke-width="2"/>
                                <line x1="23" y1="11" x2="17" y2="11" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="neo-kpi-content">
                            <div class="neo-kpi-value" id="total-clients">0</div>
                            <div class="neo-kpi-label">Müşteri Sayısı</div>
                            <div class="neo-kpi-change positive" id="clients-change">+0%</div>
                        </div>
                    </div>

                    <div class="neo-kpi-card neo-kpi-info">
                        <div class="neo-kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <line x1="12" y1="1" x2="12" y2="23" stroke="currentColor" stroke-width="2"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="neo-kpi-content">
                            <div class="neo-kpi-value" id="total-commission">₺0</div>
                            <div class="neo-kpi-label">Bu Ay Komisyon</div>
                            <div class="neo-kpi-change positive" id="commission-change">+0%</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="neo-charts-grid">
                    <!-- Listings Performance Chart -->
                    <div class="neo-chart-card">
                        <div class="neo-chart-header">
                            <h3 class="neo-chart-title">İlan Performansı</h3>
                            <div class="neo-chart-period">
                                <select class="neo-period-select" id="listings-period">
                                    <option value="7">Son 7 Gün</option>
                                    <option value="30" selected>Son 30 Gün</option>
                                    <option value="90">Son 3 Ay</option>
                                </select>
                            </div>
                        </div>
                        <div class="neo-chart-container">
                            <canvas id="listings-chart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Commission Breakdown -->
                    <div class="neo-chart-card">
                        <div class="neo-chart-header">
                            <h3 class="neo-chart-title">Komisyon Dağılımı</h3>
                            <div class="neo-chart-legend" id="commission-legend"></div>
                        </div>
                        <div class="neo-chart-container">
                            <canvas id="commission-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline & Quick Stats -->
                <div class="neo-dashboard-bottom">
                    <!-- Recent Activity -->
                    <div class="neo-activity-card">
                        <div class="neo-activity-header">
                            <h3 class="neo-activity-title">Son Aktiviteler</h3>
                            <button class="neo-btn neo-btn-sm neo-btn-ghost" id="view-all-activities">
                                Tümünü Gör
                            </button>
                        </div>
                        <div class="neo-activity-list" id="activity-list">
                            <!-- Activities will be populated here -->
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="neo-quick-actions-card">
                        <h3 class="neo-quick-actions-title">Hızlı İşlemler</h3>
                        <div class="neo-quick-actions-grid">
                            <button class="neo-quick-action" id="add-listing">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Yeni İlan
                            </button>
                            <button class="neo-quick-action" id="add-client">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                    <line x1="20" y1="8" x2="20" y2="14" stroke="currentColor" stroke-width="2"/>
                                    <line x1="23" y1="11" x2="17" y2="11" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Yeni Müşteri
                            </button>
                            <button class="neo-quick-action" id="schedule-meeting">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                                    <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                                    <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Randevu Oluştur
                            </button>
                            <button class="neo-quick-action" id="view-analytics">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 3v18h18" stroke="currentColor" stroke-width="2"/>
                                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Detaylı Analiz
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div class="neo-loading-overlay" id="dashboard-loading">
                    <div class="neo-loading-content">
                        <div class="neo-loading-spinner-large"></div>
                        <p>Dashboard yükleniyor...</p>
                    </div>
                </div>
            </div>
        `;
    }

    attachStyles() {
        const styles = `
            <style>
            .neo-consultant-dashboard {
                background: #f8fafc;
                min-height: 100vh;
                padding: 1.5rem;
                position: relative;
            }

            /* Dashboard Header */
            .neo-dashboard-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 1rem;
                padding: 2rem;
                margin-bottom: 2rem;
                color: white;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }

            .neo-header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .neo-consultant-info {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .neo-consultant-avatar {
                width: 64px;
                height: 64px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(10px);
            }

            .neo-consultant-name {
                font-size: 1.5rem;
                font-weight: 700;
                margin: 0 0 0.25rem 0;
            }

            .neo-consultant-title {
                font-size: 0.9rem;
                margin: 0;
                opacity: 0.9;
            }

            .neo-dashboard-actions {
                display: flex;
                gap: 0.75rem;
            }

            /* KPI Cards */
            .neo-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .neo-kpi-card {
                background: white;
                border-radius: 1rem;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid #f1f5f9;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .neo-kpi-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--kpi-color);
            }

            .neo-kpi-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            }

            .neo-kpi-primary { --kpi-color: #3b82f6; }
            .neo-kpi-success { --kpi-color: #10b981; }
            .neo-kpi-warning { --kpi-color: #f59e0b; }
            .neo-kpi-info { --kpi-color: #8b5cf6; }

            .neo-kpi-icon {
                width: 48px;
                height: 48px;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--kpi-color);
                color: white;
            }

            .neo-kpi-content {
                flex: 1;
            }

            .neo-kpi-value {
                font-size: 1.875rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 0.25rem;
            }

            .neo-kpi-label {
                font-size: 0.875rem;
                color: #6b7280;
                margin-bottom: 0.5rem;
            }

            .neo-kpi-change {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.25rem 0.5rem;
                border-radius: 0.375rem;
                display: inline-block;
            }

            .neo-kpi-change.positive {
                background: #dcfce7;
                color: #16a34a;
            }

            .neo-kpi-change.negative {
                background: #fef2f2;
                color: #dc2626;
            }

            /* Charts */
            .neo-charts-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .neo-chart-card {
                background: white;
                border-radius: 1rem;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid #f1f5f9;
            }

            .neo-chart-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .neo-chart-title {
                font-size: 1.125rem;
                font-weight: 600;
                color: #111827;
                margin: 0;
            }

            .neo-period-select {
                padding: 0.5rem 0.75rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                background: white;
            }

            .neo-chart-container {
                position: relative;
                height: 200px;
            }

            /* Dashboard Bottom */
            .neo-dashboard-bottom {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 1.5rem;
            }

            .neo-activity-card,
            .neo-quick-actions-card {
                background: white;
                border-radius: 1rem;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid #f1f5f9;
            }

            .neo-activity-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }

            .neo-activity-title,
            .neo-quick-actions-title {
                font-size: 1.125rem;
                font-weight: 600;
                color: #111827;
                margin: 0 0 1rem 0;
            }

            .neo-activity-list {
                max-height: 300px;
                overflow-y: auto;
            }

            .neo-activity-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 0;
                border-bottom: 1px solid #f3f4f6;
            }

            .neo-activity-item:last-child {
                border-bottom: none;
            }

            .neo-activity-icon {
                width: 32px;
                height: 32px;
                background: #f3f4f6;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #6b7280;
            }

            .neo-activity-content {
                flex: 1;
            }

            .neo-activity-title {
                font-size: 0.875rem;
                font-weight: 500;
                color: #111827;
                margin: 0 0 0.25rem 0;
            }

            .neo-activity-time {
                font-size: 0.75rem;
                color: #6b7280;
                margin: 0;
            }

            .neo-quick-actions-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .neo-quick-action {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
                padding: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background: white;
                color: #374151;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .neo-quick-action:hover {
                background: #f8fafc;
                border-color: #3b82f6;
                color: #3b82f6;
                transform: translateY(-1px);
            }

            /* Loading Overlay */
            .neo-loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                backdrop-filter: blur(4px);
            }

            .neo-loading-content {
                text-align: center;
                color: #6b7280;
            }

            .neo-loading-spinner-large {
                width: 48px;
                height: 48px;
                border: 4px solid #e5e7eb;
                border-top: 4px solid #3b82f6;
                border-radius: 50%;
                animation: neo-spin 1s linear infinite;
                margin: 0 auto 1rem;
            }

            /* Responsive Design */
            @media (max-width: 1024px) {
                .neo-charts-grid,
                .neo-dashboard-bottom {
                    grid-template-columns: 1fr;
                }

                .neo-header-content {
                    flex-direction: column;
                    gap: 1rem;
                    text-align: center;
                }

                .neo-quick-actions-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 768px) {
                .neo-consultant-dashboard {
                    padding: 1rem;
                }

                .neo-kpi-grid {
                    grid-template-columns: 1fr;
                }

                .neo-dashboard-header {
                    padding: 1.5rem;
                }
            }
            </style>
        `;

        if (!document.querySelector('#neo-consultant-dashboard-styles')) {
            const styleElement = document.createElement('div');
            styleElement.id = 'neo-consultant-dashboard-styles';
            styleElement.innerHTML = styles;
            document.head.appendChild(styleElement);
        }
    }

    async loadDashboardData() {
        try {
            this.showLoading();

            // ✅ API Helper kullan (merkezi yönetim)
            const endpoint = this.apiEndpoint + (this.consultantId ? `/${this.consultantId}` : '');
            let data;
            if (window.APIHelper) {
                const result = await window.APIHelper.request(endpoint, {
                    method: 'GET',
                }, {
                    showLoading: false, // Kendi loading'ini yönetiyor
                });
                data = result.data || result;
            } else {
                // Fallback: Eski kod
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                data = await response.json();
            }

            if (data.success) {
                this.updateDashboard(data.data);
                this.initializeCharts(data.data.charts);
                this.updateActivity(data.data.activities);
            } else {
                this.showError(data.message || 'Dashboard verileri yüklenemedi');
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
            this.showError('Bağlantı hatası oluştu');
        } finally {
            this.hideLoading();
        }
    }

    updateDashboard(data) {
        // Update consultant info
        if (data.consultant) {
            document.getElementById('consultant-name').textContent = data.consultant.name;
            document.getElementById('consultant-title').textContent = data.consultant.title;
        }

        // Update KPI values
        const kpis = data.kpis || {};
        document.getElementById('total-listings').textContent = kpis.total_listings || 0;
        document.getElementById('active-listings').textContent = kpis.active_listings || 0;
        document.getElementById('total-clients').textContent = kpis.total_clients || 0;
        document.getElementById('total-commission').textContent = this.formatCurrency(
            kpis.total_commission || 0
        );

        // Update changes
        this.updateKpiChange('listings-change', kpis.listings_change);
        this.updateKpiChange('active-change', kpis.active_change);
        this.updateKpiChange('clients-change', kpis.clients_change);
        this.updateKpiChange('commission-change', kpis.commission_change);
    }

    updateKpiChange(elementId, change) {
        const element = document.getElementById(elementId);
        if (!element || change === undefined) return;

        const isPositive = change >= 0;
        element.textContent = `${isPositive ? '+' : ''}${change}%`;
        element.className = `neo-kpi-change ${isPositive ? 'positive' : 'negative'}`;
    }

    initializeCharts(chartData) {
        // Listings Performance Chart
        this.createListingsChart(chartData.listings);

        // Commission Breakdown Chart
        this.createCommissionChart(chartData.commission);
    }

    createListingsChart(data) {
        const ctx = document.getElementById('listings-chart');
        if (!ctx || !data) return;

        if (this.charts.listings) {
            this.charts.listings.destroy();
        }

        this.charts.listings = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Yeni İlanlar',
                        data: data.new_listings || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Aktif İlanlar',
                        data: data.active_listings || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    createCommissionChart(data) {
        const ctx = document.getElementById('commission-chart');
        if (!ctx || !data) return;

        if (this.charts.commission) {
            this.charts.commission.destroy();
        }

        this.charts.commission = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.values || [],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });

        // Update legend
        this.updateCommissionLegend(data);
    }

    updateCommissionLegend(data) {
        const legend = document.getElementById('commission-legend');
        if (!legend || !data.labels) return;

        legend.innerHTML = data.labels
            .map(
                (label, index) => `
            <div class="neo-legend-item">
                <div class="neo-legend-color" style="background: ${
                    ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'][index]
                }"></div>
                <span class="neo-legend-label">${label}</span>
                <span class="neo-legend-value">${this.formatCurrency(data.values[index])}</span>
            </div>
        `
            )
            .join('');
    }

    updateActivity(activities) {
        const activityList = document.getElementById('activity-list');
        if (!activityList || !activities) return;

        activityList.innerHTML = activities
            .map(
                (activity) => `
            <div class="neo-activity-item">
                <div class="neo-activity-icon">
                    ${this.getActivityIcon(activity.type)}
                </div>
                <div class="neo-activity-content">
                    <p class="neo-activity-title">${activity.title}</p>
                    <p class="neo-activity-time">${this.formatTime(activity.created_at)}</p>
                </div>
            </div>
        `
            )
            .join('');
    }

    getActivityIcon(type) {
        const icons = {
            listing_created:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/></svg>',
            client_added:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>',
            meeting_scheduled:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/></svg>',
            default:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>',
        };
        return icons[type] || icons.default;
    }

    bindEvents() {
        // Refresh button
        document.getElementById('refresh-dashboard')?.addEventListener('click', () => {
            this.loadDashboardData();
        });

        // Export report
        document.getElementById('export-report')?.addEventListener('click', () => {
            this.exportReport();
        });

        // Quick actions
        document.getElementById('add-listing')?.addEventListener('click', () => {
            window.location.href = '/admin/ilanlar/create';
        });

        document.getElementById('add-client')?.addEventListener('click', () => {
            // Open client modal or navigate to client page
            console.log('Add client clicked');
        });

        // Period selectors
        document.getElementById('listings-period')?.addEventListener('change', (e) => {
            this.updateListingsChart(e.target.value);
        });
    }

    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadDashboardData();
        }, this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    showLoading() {
        document.getElementById('dashboard-loading').style.display = 'flex';
    }

    hideLoading() {
        document.getElementById('dashboard-loading').style.display = 'none';
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(amount);
    }

    formatTime(timestamp) {
        return new Intl.RelativeTimeFormat('tr', { numeric: 'auto' }).format(
            Math.floor((new Date(timestamp) - new Date()) / (1000 * 60 * 60 * 24)),
            'day'
        );
    }

    exportReport() {
        // Implement report export functionality
        console.log('Exporting dashboard report...');
    }

    destroy() {
        this.stopAutoRefresh();

        // Destroy charts
        Object.values(this.charts).forEach((chart) => {
            if (chart) chart.destroy();
        });

        // Clean up DOM
        const container = document.querySelector(this.container);
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('#consultant-dashboard')) {
        window.consultantDashboard = new ConsultantDashboard({
            container: '#consultant-dashboard',
        });
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConsultantDashboard;
}
