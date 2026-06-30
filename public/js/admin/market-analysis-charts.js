/**
 * Market Analysis Charts
 *
 * Chart.js entegrasyonu ile market analizi grafikleri
 * - Fiyat trend grafiği (line chart)
 * - Kategori dağılım grafiği (doughnut chart)
 * - Lokasyon bazlı fiyat karşılaştırması (bar chart)
 *
 * Context7 Compliant:
 * - Chart.js 4.4.0
 * - Dark mode support
 * - Responsive design
 * - Tailwind CSS only
 */

class MarketAnalysisCharts {
    constructor() {
        this.charts = new Map();
        this.isDark = false;
        this.init();
    }

    init() {
        // Dark mode kontrolü
        this.checkDarkMode();
        this._updatingThemes = false; // Sonsuz döngüyü önle

        // Dark mode değişikliklerini dinle
        const observer = new MutationObserver(() => {
            if (this._updatingThemes) return; // Zaten güncelleniyorsa atla
            const wasDark = this.isDark;
            this.checkDarkMode();
            // Sadece gerçekten değiştiyse güncelle
            if (wasDark !== this.isDark) {
                this.updateChartThemes();
            }
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Chart.js yüklenmesini bekle
        this.waitForChartJS().then(() => {
            this.setupChartDefaults();
            this.createCharts();
        });
    }

    checkDarkMode() {
        this.isDark = document.documentElement.classList.contains('dark');
    }

    waitForChartJS(maxAttempts = 50) {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const checkChart = setInterval(() => {
                attempts++;
                if (typeof Chart !== 'undefined') {
                    clearInterval(checkChart);
                    console.log('✅ Chart.js loaded');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkChart);
                    console.error('❌ Chart.js failed to load');
                    reject(new Error('Chart.js yüklenemedi'));
                }
            }, 100);
        });
    }

    setupChartDefaults() {
        const textColor = this.isDark ? '#e5e7eb' : '#374151';
        const gridColor = this.isDark ? '#374151' : '#e5e7eb';
        const borderColor = this.isDark ? '#4b5563' : '#d1d5db';

        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = borderColor;
        Chart.defaults.backgroundColor = this.isDark ? '#1f2937' : '#ffffff';

        if (Chart.defaults.plugins) {
            Chart.defaults.plugins.legend = Chart.defaults.plugins.legend || {};
            Chart.defaults.plugins.legend.labels = Chart.defaults.plugins.legend.labels || {};
            Chart.defaults.plugins.legend.labels.color = textColor;
        }
    }

    updateChartThemes() {
        // Sonsuz döngüyü önle
        if (this._updatingThemes) return;
        this._updatingThemes = true;

        try {
            const textColor = this.isDark ? '#e5e7eb' : '#374151';
            const gridColor = this.isDark ? '#374151' : '#e5e7eb';
            const borderColor = this.isDark ? '#4b5563' : '#d1d5db';

            // Chart.defaults değiştirmek sonsuz döngüye neden olabilir, sadece mevcut chart'ları güncelle
            // Chart.defaults.color = textColor;
            // Chart.defaults.borderColor = borderColor;

            // Mevcut chart'ları güncelle
            this.charts.forEach((chart) => {
                if (chart && chart.options && !chart._updating) {
                    chart._updating = true;
                    try {
                        if (chart.options.scales) {
                            Object.values(chart.options.scales).forEach((scale) => {
                                if (scale) {
                                    scale.ticks = scale.ticks || {};
                                    scale.ticks.color = textColor;
                                    scale.grid = scale.grid || {};
                                    scale.grid.color = gridColor;
                                }
                            });
                        }
                        if (chart.options.plugins && chart.options.plugins.legend) {
                            chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                            chart.options.plugins.legend.labels.color = textColor;
                        }
                        chart.update('none');
                    } finally {
                        chart._updating = false;
                    }
                }
            });
        } finally {
            this._updatingThemes = false;
        }
    }

    createCharts() {
        // Fiyat Trend Grafiği
        this.createPriceTrendChart();

        // Kategori Dağılım Grafiği
        this.createCategoryDistributionChart();

        // Lokasyon Bazlı Fiyat Karşılaştırması
        this.createLocationPriceChart();
    }

    createPriceTrendChart() {
        const canvas = document.getElementById('market-price-trend-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const textColor = this.isDark ? '#e5e7eb' : '#374151';
        const gridColor = this.isDark ? '#374151' : '#e5e7eb';

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ortalama Fiyat',
                    data: [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: this.isDark ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: this.isDark ? '#1f2937' : '#ffffff',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return new Intl.NumberFormat('tr-TR', {
                                    style: 'currency',
                                    currency: 'TRY',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });

        this.charts.set('priceTrend', chart);
    }

    createCategoryDistributionChart() {
        const canvas = document.getElementById('market-category-distribution-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const textColor = this.isDark ? '#e5e7eb' : '#374151';

        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                        'rgb(14, 165, 233)'
                    ],
                    borderWidth: 2,
                    borderColor: this.isDark ? '#1f2937' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            font: {
                                size: 11
                            },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: this.isDark ? '#1f2937' : '#ffffff',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: this.isDark ? '#374151' : '#d1d5db',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        this.charts.set('categoryDistribution', chart);
    }

    createLocationPriceChart() {
        const canvas = document.getElementById('market-location-price-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const textColor = this.isDark ? '#e5e7eb' : '#374151';
        const gridColor = this.isDark ? '#374151' : '#e5e7eb';

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ortalama Fiyat',
                    data: [],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: this.isDark ? '#1f2937' : '#ffffff',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return new Intl.NumberFormat('tr-TR', {
                                    style: 'currency',
                                    currency: 'TRY',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return new Intl.NumberFormat('tr-TR', {
                                    style: 'currency',
                                    currency: 'TRY',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        this.charts.set('locationPrice', chart);
    }

    updatePriceTrendChart(data) {
        const chart = this.charts.get('priceTrend');
        if (!chart || !data) return;
        chart.data.labels = data.labels || [];
        chart.data.datasets[0].data = data.values || [];
        chart.update('active');
    }

    updateCategoryDistributionChart(data) {
        const chart = this.charts.get('categoryDistribution');
        if (!chart || !data) return;
        chart.data.labels = data.labels || [];
        chart.data.datasets[0].data = data.values || [];
        chart.update('active');
    }

    updateLocationPriceChart(data) {
        const chart = this.charts.get('locationPrice');
        if (!chart || !data) return;
        chart.data.labels = data.labels || [];
        chart.data.datasets[0].data = data.values || [];
        chart.update('active');
    }

    destroy() {
        this.charts.forEach((chart) => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts.clear();
    }
}

// Global instance
window.marketAnalysisCharts = new MarketAnalysisCharts();

