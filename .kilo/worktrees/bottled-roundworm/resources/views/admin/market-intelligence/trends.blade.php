@extends('admin.layouts.admin')

@section('title', 'Piyasa Trendleri - Pazar İstihbaratı')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        Piyasa Trendleri
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Piyasa analizi ve fiyat trend grafikleri</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.market-intelligence.dashboard') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:text-slate-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Ortalama Fiyat -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 hover:shadow-xl transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Fiyat</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">₺0</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            <span class="text-green-600 dark:text-green-400">+0%</span> Bu ay
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Toplam İlan -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 hover:shadow-xl transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İlan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">0</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            <span class="text-green-600 dark:text-green-400">+0</span> Bu hafta
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Aktif İlan -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 hover:shadow-xl transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif İlan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">0</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            <span class="text-blue-600 dark:text-blue-400">%0</span> Toplam içinde
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Ortalama m² Fiyatı -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 hover:shadow-xl transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama m² Fiyatı</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">₺0</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            <span class="text-gray-600 dark:text-gray-400">m² başına</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Fiyat Trend Grafiği -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Fiyat Trendi</h2>
                    <select id="trendPeriod"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                        <option value="7">Son 7 Gün</option>
                        <option value="30" selected>Son 30 Gün</option>
                        <option value="90">Son 90 Gün</option>
                        <option value="365">Son 1 Yıl</option>
                    </select>
                </div>
                <div class="h-64">
                    <canvas id="priceTrendChart"></canvas>
                </div>
            </div>

            <!-- Kategori Dağılımı -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Kategori Dağılımı</h2>
                <div class="h-64">
                    <canvas id="categoryDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Lokasyon Bazlı Analiz -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 mb-8 dark:border-slate-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Lokasyon Bazlı Fiyat Analizi</h2>
            <div class="h-80">
                <canvas id="locationPriceChart"></canvas>
            </div>
        </div>

        <!-- Veri Durumu -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-200 mb-2">Veri Bekleniyor</h3>
                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                        Piyasa trendleri görüntülemek için önce bölge ayarlarını yapılandırın ve veri çekiminin başlamasını
                        bekleyin.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('admin.market-intelligence.settings') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Bölge Ayarlarına Git
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Chart.js -->
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" />
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark mode kontrolü
            const isDarkMode = document.documentElement.classList.contains('dark') || document.body.classList
                .contains('dark');
            const textColor = isDarkMode ? '#e5e7eb' : '#374151';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            // Chart.js global ayarları
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = gridColor;

            // Fiyat Trend Grafiği
            const priceTrendCtx = document.getElementById('priceTrendChart').getContext('2d');
            const priceTrendChart = new Chart(priceTrendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Ortalama Fiyat (₺)',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return '₺' + value.toLocaleString('tr-TR');
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    }
                }
            });

            // Kategori Dağılımı Grafiği
            const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
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
                            'rgb(139, 92, 246)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15
                            }
                        }
                    }
                }
            });

            // Lokasyon Bazlı Fiyat Grafiği
            const locationCtx = document.getElementById('locationPriceChart').getContext('2d');
            const locationChart = new Chart(locationCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Ortalama Fiyat (₺)',
                        data: [],
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return '₺' + value.toLocaleString('tr-TR');
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    }
                }
            });

            // Dark mode değişikliğini izle
            const observer = new MutationObserver(function() {
                const isDark = document.documentElement.classList.contains('dark') || document.body
                    .classList.contains('dark');
                const newTextColor = isDark ? '#e5e7eb' : '#374151';
                const newGridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

                Chart.defaults.color = newTextColor;
                Chart.defaults.borderColor = newGridColor;

                [priceTrendChart, categoryChart, locationChart].forEach(chart => {
                    if (chart.options.scales) {
                        Object.values(chart.options.scales).forEach(scale => {
                            scale.grid.color = newGridColor;
                            scale.ticks.color = newTextColor;
                        });
                    }
                    chart.update('none');
                });
            });

            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });

            // TODO: API'den veri çek ve grafikleri güncelle
            // loadTrendData();
        });
    </script>
@endpush
