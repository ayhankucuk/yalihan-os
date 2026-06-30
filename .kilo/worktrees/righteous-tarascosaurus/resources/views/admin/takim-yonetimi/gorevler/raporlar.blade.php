@extends('admin.layouts.admin')

@section('title', 'Görev Raporları')

@section('content')
    <div class="container-fluid">
        <!-- Sayfa Başlığı -->
        <div class="ds-page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Görev Raporları</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('admin.takim.gorevler.index') }}">Görevler</a></li>
                            <li class="breadcrumb-item active">Raporlar</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-auto">
                    <div class="btn-group touch-target-optimized" role="group">
                        <button type="button"
                            class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="window.print()">
                            <i class="fas fa-print mr-2"></i> Yazdır
                        </button>
                        <button type="button"
                            class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="exportToPDF()">
                            <i class="fas fa-file-pdf mr-2"></i> PDF
                        </button>
                        <button type="button"
                            class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="exportToExcel()">
                            <i class="fas fa-file-excel mr-2"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="ds-card">
                    <div class="p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-primary">
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Toplam Görev</h6>
                                <h4 class="mb-0">{{ $stats['toplam_gorev'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ds-card">
                    <div class="p-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-success">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Tamamlanan</h6>
                                <h4 class="mb-0">{{ $stats['tamamlanan_gorev'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ds-card">
                    <div class="p-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-warning">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Devam Eden</h6>
                                <h4 class="mb-0">{{ $stats['devam_eden_gorev'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ds-card">
                    <div class="p-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-danger">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Geciken</h6>
                                <h4 class="mb-0">{{ $stats['geciken_gorev'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafikler -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="ds-card">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                        <h5 class="card-title mb-0">Görev Durumu Dağılımı</h5>
                    </div>
                    <div class="p-4">
                        <canvas id="gorevDurumChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ds-card">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                        <h5 class="card-title mb-0">Aylık Görev Trendi</h5>
                    </div>
                    <div class="p-4">
                        <canvas id="gorevTrendChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performans Tablosu -->
        <div class="ds-card">
            <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h5 class="card-title mb-0">Danışman Performans Raporu</h5>
            </div>
            <div class="p-4">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Danışman</th>
                                <th>Toplam Görev</th>
                                <th>Tamamlanan</th>
                                <th>Başarı Oranı</th>
                                <th>Ortalama Süre</th>
                                <th>Geciken Görev</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($danismanPerformans ?? [] as $performans)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <img src="{{ $performans['avatar'] ?? asset('images/default-avatar.png') }}"
                                                    alt="Avatar">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $performans['ad'] }}</h6>
                                                <small class="text-muted">{{ $performans['email'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $performans['toplam_gorev'] }}</td>
                                    <td>{{ $performans['tamamlanan_gorev'] }}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ $performans['basarı_oranı'] }}%">
                                                {{ $performans['basarı_oranı'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $performans['ortalama_sure'] }} gün</td>
                                    <td>
                                        <span class="badge bg-danger">{{ $performans['geciken_gorev'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Henüz performans verisi bulunmuyor.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
        <script>
            // Görev Durumu Dağılımı Grafiği
            const ctx1 = document.getElementById('gorevDurumChart').getContext('2d');
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Tamamlanan', 'Devam Eden', 'Beklemede', 'Geciken'],
                    datasets: [{
                        data: [
                            {{ $stats['tamamlanan_gorev'] ?? 0 }},
                            {{ $stats['devam_eden_gorev'] ?? 0 }},
                            {{ $stats['beklemede_gorev'] ?? 0 }},
                            {{ $stats['geciken_gorev'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#17a2b8',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Görev Trendi Grafiği
            const ctx2 = document.getElementById('gorevTrendChart').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendData['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Oluşturulan Görevler',
                        data: {!! json_encode($trendData['olusturulan'] ?? []) !!},
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Tamamlanan Görevler',
                        data: {!! json_encode($trendData['tamamlanan'] ?? []) !!},
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Export Functions
            function exportToPDF() {
                window.print();
            }

            function exportToExcel() {
                // Excel export functionality
                alert('Excel export özelliği yakında eklenecek!');
            }
        </script>
    @endpush
