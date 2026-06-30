@extends('admin.layouts.admin')

@section('title', 'Takım Performans Raporu')

@section('content')
    <div class="container-fluid">
        <!-- Sayfa Başlığı -->
        <div class="ds-page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-slate-100">Detaylı Performans Tablosu</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.takim-yonetimi.index') }}">Takım
                                    Yönetimi</a></li>
                            <li class="breadcrumb-item active">Performans Raporu</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-auto">
                    <div class="btn-group touch-target-optimized" role="group">
                        <button type="button" class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="window.print()">
                            <i class="fas fa-print"></i> Yazdır
                        </button>
                        <button type="button" class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="inline-flex items-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:text-slate-300"
                            onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="ds-card mb-4">
            <div class="p-6">
                <div class="row">
                    <div class="col-md-3">
                        <label for="tarihAraligi" class="admin-label">Tarih Aralığı</label>
                        <select style="color-scheme: light dark;" id="tarihAraligi" class="admin-input transition-all duration-200" onchange="tarihAraligiDegistir()">
                            <option value="7">Son 7 Gün</option>
                            <option value="30" selected>Son 30 Gün</option>
                            <option value="90">Son 90 Gün</option>
                            <option value="365">Son 1 Yıl</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="takimFiltresi" class="admin-label">Takım Filtresi</label>
                        <select style="color-scheme: light dark;" id="takimFiltresi" class="admin-input transition-all duration-200" onchange="takimFiltresiDegistir()">
                            <option value="">Tüm Takımlar</option>
                            @foreach ($takimlar ?? [] as $takim)
                                <option value="{{ $takim->id }}">{{ $takim->takim_adi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="danismanFiltresi" class="admin-label">Danışman Filtresi</label>
                        <select style="color-scheme: light dark;" id="danismanFiltresi" class="admin-input transition-all duration-200" onchange="danismanFiltresiDegistir()">
                            <option value="">Tüm Danışmanlar</option>
                            @foreach ($danismanlar ?? [] as $danisman)
                                <option value="{{ $danisman->id }}">{{ $danisman->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="admin-label">&nbsp;</label>
                        <div>
                            <button type="button" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none" onclick="raporuYenile()">
                                <i class="fas fa-sync-alt"></i> Raporu Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Genel İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="ds-card">
                    <div class="p-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-primary">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Toplam Takım Üyesi</h6>
                                <h4 class="mb-0">{{ $genelIstatistikler['toplam_uye'] ?? 0 }}</h4>
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
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Tamamlanan Görev</h6>
                                <h4 class="mb-0">{{ $genelIstatistikler['tamamlanan_gorev'] ?? 0 }}</h4>
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
                                <h6 class="mb-0">Ortalama Süre</h6>
                                <h4 class="mb-0">{{ $genelIstatistikler['ortalama_sure'] ?? 0 }} gün</h4>
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
                                <div class="avatar avatar-sm bg-info">
                                    <i class="fas fa-chart-line text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Başarı Oranı</h6>
                                <h4 class="mb-0">{{ $genelIstatistikler['basarı_oranı'] ?? 0 }}%</h4>
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
                        <h5 class="card-title mb-0">Takım Performans Karşılaştırması</h5>
                    </div>
                    <div class="p-6">
                        <canvas id="takimPerformansChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ds-card">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                        <h5 class="card-title mb-0">Aylık Görev Trendi</h5>
                    </div>
                    <div class="p-6">
                        <canvas id="gorevTrendChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Takım Detay Tablosu -->
        <div class="ds-card">
            <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h5 class="card-title mb-0">Takım Detay Performans Raporu</h5>
            </div>
            <div class="p-6">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Takım</th>
                                <th>Üye Sayısı</th>
                                <th>Toplam Görev</th>
                                <th>Tamamlanan</th>
                                <th>Başarı Oranı</th>
                                <th>Ortalama Süre</th>
                                <th>Geciken Görev</th>
                                <th>Performans Skoru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($takimPerformans ?? [] as $performans)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <img src="{{ $performans['avatar'] ?? asset('images/default-team.png') }}"
                                                    alt="Takım Avatar">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $performans['takim_adi'] }}</h6>
                                                <small class="text-muted">{{ $performans['takim_lideri'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $performans['uye_sayisi'] }}</td>
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
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                @if ($performans['performans_skoru'] >= 80)
                                                    <i class="fas fa-star text-warning"></i>
                                                @elseif($performans['performans_skoru'] >= 60)
                                                    <i class="fas fa-star-half-alt text-warning"></i>
                                                @else
                                                    <i class="far fa-star text-muted"></i>
                                                @endif
                                            </div>
                                            <span class="fw-bold">{{ $performans['performans_skoru'] }}/100</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
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

        <!-- Danışman Performans Tablosu -->
        <div class="ds-card mt-4">
            <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h5 class="card-title mb-0">Danışman Performans Detayı</h5>
            </div>
            <div class="p-6">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Danışman</th>
                                <th>Takım</th>
                                <th>Atanan Görev</th>
                                <th>Tamamlanan</th>
                                <th>Başarı Oranı</th>
                                <th>Ortalama Süre</th>
                                <th>Müşteri Memnuniyeti</th>
                                <th>Durum</th>
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
                                                <h6 class="mb-0">{{ $performans['ad'] }} {{ $performans['soyad'] }}
                                                </h6>
                                                <small class="text-muted">{{ $performans['email'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $performans['takim_adi'] }}</td>
                                    <td>{{ $performans['atanan_gorev'] }}</td>
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
                                        <div class="d-flex align-items-center">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= $performans['musteri_memnuniyeti'])
                                                    <i class="fas fa-star text-warning"></i>
                                                @else
                                                    <i class="far fa-star text-muted"></i>
                                                @endif
                                            @endfor
                                            <span class="ms-2">{{ $performans['musteri_memnuniyeti'] }}/5</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($performans['status'] === 'active')
                                            <span class="badge bg-success">Aktif</span>
                                        @elseif($performans['status'] === 'izinli')
                                            <span class="badge bg-warning">İzinli</span>
                                        @else
                                            <span class="badge bg-danger">Pasif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Henüz danışman performans verisi bulunmuyor.
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
            // Takım Performans Karşılaştırması Grafiği
            const ctx1 = document.getElementById('takimPerformansChart').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($takimPerformansGrafik['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Başarı Oranı (%)',
                        data: {!! json_encode($takimPerformansGrafik['basarı_oranı'] ?? []) !!},
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Performans Skoru',
                        data: {!! json_encode($takimPerformansGrafik['performans_skoru'] ?? []) !!},
                        backgroundColor: 'rgba(72, 187, 120, 0.8)',
                        borderColor: 'rgba(72, 187, 120, 1)',
                        borderWidth: 1
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
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Görev Trendi Grafiği
            const ctx2 = document.getElementById('gorevTrendChart').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: {!! json_encode($gorevTrendGrafik['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Oluşturulan Görevler',
                        data: {!! json_encode($gorevTrendGrafik['olusturulan'] ?? []) !!},
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Tamamlanan Görevler',
                        data: {!! json_encode($gorevTrendGrafik['tamamlanan'] ?? []) !!},
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
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

            // Filtre Fonksiyonları
            function tarihAraligiDegistir() {
                const tarihAraligi = document.getElementById('tarihAraligi').value;
                raporuYenile(tarihAraligi);
            }

            function takimFiltresiDegistir() {
                const takimFiltresi = document.getElementById('takimFiltresi').value;
                raporuYenile(null, takimFiltresi);
            }

            function danismanFiltresiDegistir() {
                const danismanFiltresi = document.getElementById('danismanFiltresi').value;
                raporuYenile(null, null, danismanFiltresi);
            }

            function raporuYenile(tarihAraligi = null, takimFiltresi = null, danismanFiltresi = null) {
                const url = new URL(window.location.href);

                if (tarihAraligi) url.searchParams.set('tarih_araligi', tarihAraligi);
                if (takimFiltresi) url.searchParams.set('takim_filtresi', takimFiltresi);
                if (danismanFiltresi) url.searchParams.set('danisman_filtresi', danismanFiltresi);

                window.location.href = url.toString();
            }

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
