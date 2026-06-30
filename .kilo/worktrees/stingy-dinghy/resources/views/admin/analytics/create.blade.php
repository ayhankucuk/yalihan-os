@extends('admin.layouts.admin')

@section('title', 'Yeni Analitik Raporu Oluştur')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    Yeni Analitik Raporu
                </h1>
                <p class="text-lg text-gray-600 mt-2">Özel analitik raporu oluşturun</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-8 dark:shadow-none dark:border-slate-700">
        <form action="{{ route('admin.analytics.store') }}" method="POST" id="analyticsForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sol Kolon -->
                <div class="space-y-6">
                    <!-- Rapor Adı -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                            Rapor Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
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
                                <option value="{{ $key }}" {{ old('report_type') == $key ? 'selected' : '' }}>
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
                                <option value="{{ $key }}" {{ old('date_range') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('date_range')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="views"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('views', old('metrics', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Görüntülemeler</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="users"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('users', old('metrics', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Kullanıcılar</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="conversions"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('conversions', old('metrics', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Dönüşümler</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="revenue"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('revenue', old('metrics', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Gelir</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="bounce_rate"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array('bounce_rate', old('metrics', [])) ? 'checked' : '' }}>
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
                            placeholder="Rapor hakkında kısa açıklama...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    İptal
                </a>
                <button type="submit"
                        id="analytics-create-submit-btn"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        onsubmit="const btn = document.getElementById('analytics-create-submit-btn'); const icon = document.getElementById('analytics-create-submit-icon'); const text = document.getElementById('analytics-create-submit-text'); const spinner = document.getElementById('analytics-create-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Oluşturuluyor...'; }">
                    <svg id="analytics-create-submit-icon" class="fas fa-save mr-2"></svg>
                    <svg id="analytics-create-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="analytics-create-submit-text">Raporu Oluştur</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            // Context7: Improved loading state with proper error handling
            document.getElementById('analyticsForm').addEventListener('submit', function() {
                const submitBtn = document.getElementById('analytics-create-submit-btn');
                const icon = document.getElementById('analytics-create-submit-icon');
                const text = document.getElementById('analytics-create-submit-text');
                const spinner = document.getElementById('analytics-create-submit-spinner');

                if (submitBtn && icon && text && spinner) {
                    submitBtn.disabled = true;
                    icon.classList.add('hidden');
                    spinner.classList.remove('hidden');
                    text.textContent = 'Oluşturuluyor...';
                }

                // Re-enable after 10 seconds as fallback (in case of error)
                setTimeout(() => {
                    if (submitBtn && icon && text && spinner) {
                        submitBtn.disabled = false;
                        icon.classList.remove('hidden');
                        spinner.classList.add('hidden');
                        text.textContent = 'Raporu Oluştur';
                    }
                }, 10000);
            });
        </script>
    @endpush
@endsection
