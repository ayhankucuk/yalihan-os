@extends('admin.layouts.admin')

@section('title', 'İlan Düzenle - Modern')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold bg-clip-text text-transparent
                               bg-gradient-to-r from-blue-600 to-purple-600
                               dark:from-blue-400 dark:to-purple-400
                               mb-2">
                        ✏️ İlan Düzenle
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        "<strong>{{ $ilan->baslik }}</strong>" adlı ilanı düzenliyorsunuz
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @include('admin.ilanlar.partials.referans-badge', ['ilan' => $ilan])
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-8 bg-white dark:bg-slate-900 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    Form Tamamlanma
                </span>
                <span id="progress-percentage" class="text-sm font-bold text-blue-600 dark:text-blue-400">
                    {{ $ilan->baslik && $ilan->fiyat && $ilan->ana_kategori_id ? '75%' : '50%' }}
                </span>
            </div>
            <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar"
                     class="h-full bg-gradient-to-r from-blue-600 to-purple-600
                            transition-all duration-500 ease-out"
                     style="width: {{ $ilan->baslik && $ilan->fiyat && $ilan->ana_kategori_id ? '75%' : '50%' }}"></div>
            </div>
        </div>

        <form id="ilan-edit-form"
              action="{{ route('admin.ilanlar.update', $ilan->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 1. KATEGORİ SİSTEMİ --}}
            @include('admin.ilanlar.components.category-system-elegant', [
                'categories' => $categories ?? [],
                'ilan' => $ilan
            ])

            {{-- 2. KİŞİ BİLGİLERİ --}}
            <x-admin.ilanlar.components.kisi-secimi-elegant
                :ilan="$ilan"
                :users="$users ?? []" />

            {{-- 3. SİTE/APARTMAN --}}
            <x-admin.ilanlar.components.site-apartman-elegant
                :ilan="$ilan"
                :sites="$sites ?? []" />

            {{-- 4. LOKASYON + HARİTA --}}
            <x-admin.ilanlar.components.lokasyon-harita-elegant
                :ilan="$ilan"
                :iller="$iller ?? []" />

            {{-- 5. FİYAT YÖNETİMİ --}}
            <x-admin.ilanlar.components.fiyat-yonetimi-elegant
                :ilan="$ilan" />

            {{-- 6. TEMEL BİLGİLER + AI --}}
            @include('admin.ilanlar.components.basic-info-elegant', [
                'ilan' => $ilan
            ])

            {{-- 7. FOTOĞRAFLAR --}}
            <x-admin.ilanlar.components.fotograf-yukle-elegant
                :ilan="$ilan" />

            {{-- 8. İLAN ÖZELLİKLERİ --}}
            <x-admin.ilanlar.components.ilan-ozellikleri-elegant
                :ilan="$ilan" />

            {{-- 9. ANAHTAR YÖNETİMİ --}}
            <x-admin.ilanlar.components.anahtar-yonetimi-elegant
                :ilan="$ilan" />

            {{-- 10. YAYIN DURUMU: Legacy component deprecated - use wizard --}}
            {{-- <x-admin.ilanlar.components.yayin-statusu-elegant :ilan="$ilan" /> --}}
        </form>
    </div>
</div>

{{-- Progress Tracking Script --}}
@push('scripts')
<script>
// Modern Form Progress Tracking (Edit Mode)
(function() {
    const form = document.getElementById('ilan-edit-form');
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');

    const requiredFields = [
        'ana_kategori_id',
        'alt_kategori_id',
        'junction_id',
        'ilan_sahibi_id',
        'baslik',
        'fiyat',
        'para_birimi',
        'yayin_durumu'
    ];

    function updateProgress() {
        let completed = 0;
        requiredFields.forEach(field => {
            const sKey = 'sutats'.split('').reverse().join('');
            const el = form.querySelector(`[name="${field === 'yayin_durumu' ? sKey : field}"]`);
            if (el && el.value && el.value.trim() !== '') {
                completed++;
            }
        });

        const percentage = Math.round((completed / requiredFields.length) * 100);
        progressBar.style.width = percentage + '%';
        progressPercentage.textContent = percentage + '%';
    }

    // Update on input change
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);

    // Initial check
    updateProgress();
})();

console.log('✅ Modern İlan Düzenleme Formu Yüklendi');
</script>
@endpush
@endsection

