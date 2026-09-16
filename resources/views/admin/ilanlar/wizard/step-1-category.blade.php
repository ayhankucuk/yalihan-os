@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 1: İLAN KATEGORİSİ --}}
<div class="space-y-6">
    {{-- Kategori Sistemi --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 dark:shadow-none dark:border-slate-700">
        <!-- Section Header -->
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800 rounded-t-lg flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-md font-semibold text-sm dark:shadow-none">
                1
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    İlan Kategorisi
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">İlanınızın kategorisini seçin</p>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div>
                <label for="ana_kategori_id" class="wizard-field-label">
                    Ana Kategori <span class="text-red-500">*</span>
                </label>
                <select name="ana_kategori_id" id="ana_kategori_id" required onchange="loadAltKategoriler(this.value)"
                    class="wizard-field">
                    <option value="">Ana Kategori Seçin</option>
                    @foreach ($kategoriler ?? [] as $kategori)
                        @if(is_object($kategori))
                            <option value="{{ $kategori->id }}"
                                data-slug="{{ $kategori->slug ?? strtolower($kategori->name ?? '') }}"
                                {{ old('ana_kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name ?? 'Kategori' }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label for="alt_kategori_id" class="wizard-field-label">
                    Alt Kategori <span class="text-red-500">*</span>
                </label>
                <select name="alt_kategori_id" id="alt_kategori_id" required onchange="loadYayinTipleri(this.value)"
                    disabled class="wizard-field disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">Önce Ana Kategori Seçin</option>
                </select>
            </div>

            <div>
                <label for="yayin_tipi_id" class="wizard-field-label">
                    Yayın Tipi <span class="text-red-500">*</span>
                </label>
                <select name="junction_id" id="junction_id" required disabled
                    @change="Alpine.store('listing') && Alpine.store('listing').fetchConfig($event.target.value)"
                    class="wizard-field disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">Önce Alt Kategori Seçin</option>
                </select>
            </div>
        </div>

        {{-- Kategori Seçimi Bilgilendirmesi --}}
        <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                <strong>İpucu:</strong> Kategori seçimi, ilanınızın hangi özelliklerin gösterileceğini belirler.
                Doğru kategori seçimi, alıcıların ilanınızı daha kolay bulmasını sağlar.
            </p>
        </div>
    </div>

    {{-- veya ayracı --}}
    <div class="relative my-10">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200 dark:border-slate-800 dark:border-slate-700"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-6 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-full text-sm font-bold text-slate-400 uppercase tracking-widest">
                veya
            </span>
        </div>
    </div>

    {{-- Hızlı Seçimler --}}
    @include('admin.ilanlar.components.quick-selections')
</div>

@push('scripts')
<script>
// ✅ Pure JavaScript event listener (no inline onchange)
document.addEventListener('DOMContentLoaded', function() {
    const yayinTipiSelect = document.getElementById('junction_id');

    if (yayinTipiSelect) {
        yayinTipiSelect.addEventListener('change', function() {
            // Dispatch category-changed event
            if (typeof window.dispatchCategoryChangedEvent === 'function') {
                window.dispatchCategoryChangedEvent();
            } else {
                console.warn('⚠️ dispatchCategoryChangedEvent not available yet');
            }
        });
        console.log('✅ Yayın tipi event listener attached');
    }
});
</script>
@endpush
