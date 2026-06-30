@extends('admin.layouts.admin')

@section('title', 'Kategori İstatistikleri')

@push('styles')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
    <style>
        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .mini-chart {
            height: 200px;
        }
    </style>
@endpush

@section('content')
    <div class="content-header mb-6">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <h1 class="admin-h1">Kategori İstatistikleri</h1>
                <div class="flex space-x-2">
                    <!-- Geri Dön -->
                    <a href="{{ route('admin.ilan-kategorileri.index') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Geri Dön
                    </a>
                    <!-- Export -->
                    <a href="{{ route('admin.ilan-kategorileri.export') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export
                    </a>
                    <!-- Yenile -->
                    <button onclick="window.location.reload()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Genel İstatistikler -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Toplam Kategori -->
        <div
            class="stat-card bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14-4H3m16 8H1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Kategori</h3>
                    <p class="admin-h1">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        <!-- Aktif Kategoriler -->
        <div
            class="stat-card bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif Kategoriler</h3>
                    <p class="stat-card-value text-green-600 dark:text-green-400">{{ number_format($stats['aktif'] ?? 0) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $stats['total'] > 0 ? round((($stats['aktif'] ?? 0) / $stats['total']) * 100, 1) : 0 }}%</p>
                </div>
            </div>
        </div>

        <!-- Pasif Kategoriler -->
        <div
            class="stat-card bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pasif Kategoriler</h3>
                    <p class="stat-card-value text-red-600 dark:text-red-400">{{ number_format($stats['pasif'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $stats['total'] > 0 ? round((($stats['pasif'] ?? 0) / $stats['total']) * 100, 1) : 0 }}%</p>
                </div>
            </div>
        </div>

        <!-- İlan Sayısı -->
        @if (isset($stats['ilan_stats']))
            <div
                class="stat-card bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam İlan</h3>
                        <p class="stat-card-value text-purple-600 dark:text-purple-400">
                            {{ number_format($stats['ilan_stats']['total_ilanlar']) }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($stats['ilan_stats']['kategorili_ilanlar']) }}
                            kategorili</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Seviye Dağılımı -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Seviye Dağılımı</h3>
            <div class="chart-container mini-chart">
                <canvas id="levelChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($stats['by_level'] as $level => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            @switch($level)
                                @case(0)
                                    Ana Kategoriler
                                @break

                                @case(1)
                                    Alt Kategoriler
                                @break

                                @case(2)
                                    Yayın Tipleri
                                @break

                                @default
                                    Seviye {{ $level }}
                                @break
                            @endswitch
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Aktif/Pasif Dağılımı -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Aktif/Pasif Dağılımı</h3>
            <div class="chart-container mini-chart">
                <canvas id="aktiflikChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Aktif Kategoriler</span>
                    <span
                        class="text-sm font-medium text-green-600 dark:text-green-400">{{ number_format($stats['aktif'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pasif Kategoriler</span>
                    <span
                        class="text-sm font-medium text-red-600 dark:text-red-400">{{ number_format($stats['pasif'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if (isset($stats['ilan_stats']))
        <!-- İlan İstatistikleri -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- En Popüler Ana Kategoriler -->
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">En Popüler Ana Kategoriler</h3>
                <div class="space-y-3">
                    @forelse($stats['top_ana_kategoriler'] as $index => $kategori)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-lg">
                            <div class="flex items-center">
                                <span
                                    class="w-8 h-8 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium mr-3">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $kategori['kategori_adi'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $kategori['kategori_id'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($kategori['ilan_sayisi']) }}</p>
                                <p class="text-xs text-gray-500">ilan</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz veri bulunmuyor.</p>
                    @endforelse
                </div>
            </div>

            <!-- En Popüler Alt Kategoriler -->
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">En Popüler Alt Kategoriler</h3>
                <div class="space-y-3">
                    @forelse($stats['top_alt_kategoriler'] as $index => $kategori)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-lg">
                            <div class="flex items-center">
                                <span
                                    class="w-8 h-8 flex items-center justify-center bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-full text-sm font-medium mr-3">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $kategori['kategori_adi'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $kategori['kategori_id'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ number_format($kategori['ilan_sayisi']) }}</p>
                                <p class="text-xs text-gray-500">ilan</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz veri bulunmuyor.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kategori Dağılımı Grafiği -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategorilere Göre İlan Dağılımı</h3>
            <div class="chart-container">
                <canvas id="categoryDistributionChart"></canvas>
            </div>
        </div>
    @endif

    <!-- Hızlı İşlemler -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Hızlı İşlemler</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.ilan-kategorileri.create') }}"
                class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 dark:hover:border-blue-400 transition-colors group">
                <div class="text-center">
                    <svg class="w-8 h-8 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 mx-auto mb-2"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400">
                        Yeni Kategori</p>
                </div>
            </a>

            <a href="{{ route('admin.ilan-kategorileri.index') }}"
                class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-green-500 dark:hover:border-green-400 transition-colors group">
                <div class="text-center">
                    <svg class="w-8 h-8 text-gray-400 group-hover:text-green-500 dark:group-hover:text-green-400 mx-auto mb-2"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-green-500 dark:group-hover:text-green-400">
                        Kategorileri Yönet</p>
                </div>
            </a>

            <button onclick="window.location.reload()"
                class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-purple-500 dark:hover:border-purple-400 transition-colors group">
                <div class="text-center">
                    <svg class="w-8 h-8 text-gray-400 group-hover:text-purple-500 dark:group-hover:text-purple-400 mx-auto mb-2"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-purple-500 dark:group-hover:text-purple-400">
                        Verileri Yenile</p>
                </div>
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js global ayarları
            Chart.defaults.responsive = true;
            Chart.defaults.maintainAspectRatio = false;

            // Dark mode kontrolü
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#e5e7eb' : '#374151';
            const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

            // Seviye Dağılımı Chart
            const levelCtx = document.getElementById('levelChart').getContext('2d');
            new Chart(levelCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        @foreach ($stats['by_level'] as $level => $count)
                            @switch($level)
                                @case(0)
                                'Ana Kategoriler',
                                @break

                                @case(1)
                                'Alt Kategoriler',
                                @break

                                @case(2)
                                'Yayın Tipleri',
                                @break

                                @default
                                    'Seviye {{ $level }}',
                                    @break
                            @endswitch
                        @endforeach
                    ],
                    datasets: [{
                        data: [
                            @foreach ($stats['by_level'] as $level => $count)
                                {{ $count }},
                            @endforeach
                        ],
                        backgroundColor: [
                            '#3B82F6', // Blue
                            '#10B981', // Green
                            '#F59E0B', // Yellow
                            '#EF4444', // Red
                            '#8B5CF6', // Purple
                        ],
                        borderWidth: 2,
                        borderColor: isDarkMode ? '#1F2937' : '#FFFFFF'
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Aktif/Pasif Chart
            const aktiflikCtx = document.getElementById('aktiflikChart').getContext('2d');
            new Chart(aktiflikCtx, {
                type: 'pie',
                data: {
                    labels: ['Aktif', 'Pasif'],
                    datasets: [{
                        data: [{{ $stats['aktif'] ?? 0 }}, {{ $stats['pasif'] ?? 0 }}],
                        backgroundColor: ['#10B981', '#EF4444'],
                        borderWidth: 2,
                        borderColor: isDarkMode ? '#1F2937' : '#FFFFFF'
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            @if (isset($stats['kategori_dagilimi']))
                // Kategori Dağılımı Chart
                const distributionCtx = document.getElementById('categoryDistributionChart').getContext('2d');
                new Chart(distributionCtx, {
                    type: 'bar',
                    data: {
                        labels: [
                            @foreach ($stats['kategori_dagilimi'] as $kategori)
                                '{{ addslashes($kategori['kategori_adi']) }}',
                            @endforeach
                        ],
                        datasets: [{
                            label: 'İlan Sayısı',
                            data: [
                                @foreach ($stats['kategori_dagilimi'] as $kategori)
                                    {{ $kategori['ilan_sayisi'] }},
                                @endforeach
                            ],
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: textColor
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            x: {
                                ticks: {
                                    color: textColor,
                                    maxRotation: 45
                                },
                                grid: {
                                    color: gridColor
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            @endif
        });
    </script>
@endpush
