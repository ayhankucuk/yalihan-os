@extends('admin.layouts.admin')

@section('title', 'Analitik Raporu Detayları')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-chart-bar text-white text-xl"></i>
                    </div>
                    {{ $analyticsItem['name'] }}
                </h1>
                <p class="text-lg text-gray-600 mt-2">Analitik raporu detayları ve veriler</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.analytics.edit', $analyticsItem['id']) }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>Düzenle
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Rapor Bilgileri -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Rapor Bilgileri
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Rapor Adı</label>
                        <p class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $analyticsItem['name'] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Rapor Tipi</label>
                        <p class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                            @switch($analyticsItem['report_type'])
                                @case('user_behavior')
                                    Kullanıcı Davranışı
                                @break

                                @case('property_performance')
                                    İlan Performansı
                                @break

                                @case('conversion_analysis')
                                    Dönüşüm Analizi
                                @break

                                @case('revenue_tracking')
                                    Gelir Takibi
                                @break

                                @case('traffic_sources')
                                    Trafik Kaynakları
                                @break

                                @default
                                    {{ $analyticsItem['report_type'] }}
                            @endswitch
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tarih Aralığı</label>
                        <p class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                            @switch($analyticsItem['date_range'])
                                @case('7_days')
                                    Son 7 Gün
                                @break

                                @case('30_days')
                                    Son 30 Gün
                                @break

                                @case('90_days')
                                    Son 3 Ay
                                @break

                                @case('1_year')
                                    Son 1 Yıl
                                @break

                                @default
                                    {{ $analyticsItem['date_range'] }}
                            @endswitch
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Oluşturulma Tarihi</label>
                        <p class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $analyticsItem['created_at']->format('d.m.Y H:i') }}
                        </p>
                    </div>
                </div>

                @if ($analyticsItem['description'])
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Açıklama</label>
                        <p class="text-gray-900 bg-gray-50 p-4 rounded-lg dark:bg-slate-900 dark:text-slate-100 dark:text-white">{{ $analyticsItem['description'] }}</p>
                    </div>
                @endif
            </div>

            <!-- Metrikler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i>
                    Dahil Edilen Metrikler
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($analyticsItem['metrics'] as $metric)
                        <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span class="text-blue-900 font-medium">
                                @switch($metric)
                                    @case('views')
                                        Görüntülemeler
                                    @break

                                    @case('users')
                                        Kullanıcılar
                                    @break

                                    @case('conversions')
                                        Dönüşümler
                                    @break

                                    @case('revenue')
                                        Gelir
                                    @break

                                    @case('bounce_rate')
                                        Çıkış Oranı
                                    @break

                                    @default
                                        {{ ucfirst($metric) }}
                                @endswitch
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Rapor Verileri (Mock Chart) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                    <i class="fas fa-chart-area text-purple-500 mr-2"></i>
                    Rapor Verileri
                </h2>

                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-8 rounded-lg text-center">
                    <i class="fas fa-chart-bar text-6xl text-blue-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2 dark:text-slate-300">Grafik ve Veriler</h3>
                    <p class="text-gray-600 mb-4">Bu bölümde detaylı analitik grafikler ve tablolar gösterilecektir.</p>
                    <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Geliştirme Aşamasında
                    </div>
                </div>
            </div>
        </div>

        <!-- Yan Panel -->
        <div class="space-y-6">
            <!-- Hızlı İstatistikler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                    <i class="fas fa-tachometer-alt text-blue-500 mr-2"></i>
                    Hızlı İstatistikler
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-600">Toplam Veri Noktası</span>
                        <span class="text-lg font-bold text-blue-600">1,234</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-600">Son Güncelleme</span>
                        <span class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">{{ now()->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-600">Durum</span>
                        <span
                            class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                            <i class="fas fa-check mr-1"></i>Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- İşlemler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                    <i class="fas fa-cogs text-gray-500 mr-2"></i>
                    İşlemler
                </h3>

                <div class="space-y-3">
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-outline text-left" onclick="exportReport()">
                        <i class="fas fa-download mr-2"></i>
                        Raporu İndir
                    </button>
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-outline text-left" onclick="shareReport()">
                        <i class="fas fa-share mr-2"></i>
                        Raporu Paylaş
                    </button>
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-outline text-left" onclick="duplicateReport()">
                        <i class="fas fa-copy mr-2"></i>
                        Raporu Kopyala
                    </button>
                    <hr class="my-2">
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-danger text-left" onclick="deleteReport()" id="deleteBtn">
                        <i class="fas fa-trash mr-2"></i>
                        Raporu Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function exportReport() {
                alert('Rapor export işlemi - geliştirme aşamasında');
            }

            function shareReport() {
                alert('Rapor paylaşım işlemi - geliştirme aşamasında');
            }

            function duplicateReport() {
                alert('Rapor kopyalama işlemi - geliştirme aşamasında');
            }

            function deleteReport() {
                if (confirm('Bu raporu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                    // AJAX ile silme işlemi
                    fetch(`{{ route('admin.analytics.destroy', $analyticsItem['id']) }}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = '{{ route('admin.analytics.index') }}';
                            } else {
                                alert('Silme işlemi başarısız: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Hata oluştu: ' + error.message);
                        });
                }
            }
        </script>
    @endpush
@endsection
