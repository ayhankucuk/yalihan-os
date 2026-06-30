@extends('admin.layouts.admin')

@section('title', 'Analitik Raporu Düzenle')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    Analitik Raporu Düzenle
                </h1>
                <p class="text-lg text-gray-600 mt-2">{{ $analyticsItem['name'] }} - Rapor ayarlarını değiştirin</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.analytics.show', $analyticsItem['id']) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-8 dark:shadow-none dark:border-slate-700">
        <form action="{{ route('admin.analytics.update', $analyticsItem['id']) }}" method="POST" id="analyticsEditForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sol Kolon -->
                <div class="space-y-6">
                    <!-- Rapor Adı -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                            Rapor Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                            value="{{ old('name', $analyticsItem['name']) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Örn: Aylık Performans Raporu" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rapor Tipi -->
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                            Rapor Tipi <span class="text-red-500">*</span>
                        </label>
                        <select style="color-scheme: light dark;" id="report_type" name="report_type" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Rapor tipi seçin</option>
                            @foreach ($reportTypes as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('report_type', $analyticsItem['report_type']) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('report_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tarih Aralığı -->
                    <div>
                        <label for="date_range" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                            Tarih Aralığı <span class="text-red-500">*</span>
                        </label>
                        <select style="color-scheme: light dark;" id="date_range" name="date_range" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Tarih aralığı seçin</option>
                            @foreach ($dateRanges as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('date_range', $analyticsItem['date_range']) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('date_range')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mevcut Bilgi Kutusu -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-blue-900 mb-1">Mevcut Rapor Bilgileri</h4>
                                <p class="text-sm text-blue-800">
                                    Oluşturulma: {{ $analyticsItem['created_at']->format('d.m.Y H:i') }}<br>
                                    Durum: Aktif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="space-y-6">
                    <!-- Metrikler -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3 dark:text-slate-300">
                            Dahil Edilecek Metrikler <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            @php
                                $currentMetrics = old('metrics', $analyticsItem['metrics']);
                            @endphp

                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="views"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('views', $currentMetrics) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Görüntülemeler</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="users"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('users', $currentMetrics) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Kullanıcılar</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="conversions"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('conversions', $currentMetrics) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Dönüşümler</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="revenue"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('revenue', $currentMetrics) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Gelir</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="bounce_rate"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('bounce_rate', $currentMetrics) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Çıkış Oranı</span>
                            </label>
                        </div>
                        @error('metrics')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Açıklama -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                            Açıklama
                        </label>
                        <textarea id="description" name="description" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Rapor hakkında kısa açıklama...">{{ old('description', $analyticsItem['description'] ?? '') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Uyarı Kutusu -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-1 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-amber-900 mb-1">Dikkat!</h4>
                                <p class="text-sm text-amber-800">
                                    Rapor ayarlarını değiştirmek mevcut verileri etkileyebilir.
                                    Değişiklikleri kaydetmeden önce lütfen kontrol edin.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.analytics.show', $analyticsItem['id']) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    İptal
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none" id="submitBtn">
                    <i class="fas fa-save mr-2"></i>
                    Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.getElementById('analyticsEditForm').addEventListener('submit', function() {
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
            });

            // Form değişiklik takibi
            let originalFormData = new FormData(document.getElementById('analyticsEditForm'));
            let hasChanges = false;

            document.getElementById('analyticsEditForm').addEventListener('input', function() {
                hasChanges = true;
            });

            // Sayfa kapatılırken uyarı
            window.addEventListener('beforeunload', function(e) {
                if (hasChanges) {
                    e.preventDefault();
                    e.returnValue =
                    'Kaydedilmemiş değişiklikleriniz var. Sayfayı kapatmak istediğinizden emin misiniz?';
                }
            });

            // Form submit edildiğinde uyarıyı kaldır
            document.getElementById('analyticsEditForm').addEventListener('submit', function() {
                hasChanges = false;
            });
        </script>
    @endpush
@endsection
