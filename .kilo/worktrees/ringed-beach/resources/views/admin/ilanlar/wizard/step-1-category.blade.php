@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 1: İLAN KATEGORİSİ --}}
<div class="space-y-6">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            İlan Kategorisi
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">İlanınızın kategorisini seçin</p>
    </div>

    {{-- Kategori Sistemi --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
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
                    @change="Alpine.store('listing').fetchConfig($event.target.value)"
                    class="wizard-field disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">Önce Alt Kategori Seçin</option>
                </select>
            </div>
        </div>

        {{-- Kategori Seçimi Bilgilendirmesi --}}
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-300">
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
            <span class="px-6 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-full text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-transform hover:scale-110 dark:border-slate-700">
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
