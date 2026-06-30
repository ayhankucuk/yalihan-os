{{--
🎨 KATEGORI SISTEMİ - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper
    sectionId="section-category"
    title="Kategori Sistemi"
    subtitle="İlanınızın kategori ve yayın tipini seçin"
    badgeNumber="1"
    badgeColor="green"
    :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                    d=\'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z\' />
            </svg>'"
    glassEffect="true">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Ana Kategori --}}
        <x-admin.ilanlar.components.elegant-input
            name="ana_kategori_id"
            type="select"
            label="Ana Kategori"
            :required="true"
            :floating="true"
            onchange="loadAltKategoriler(this.value); safeDispatchCategoryChanged();"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path d=\'M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z\' />
                    </svg>'"
            helpText="İlanın ana kategorisini seçin">
            <option value="">-- Seçin --</option>
            @foreach ($categories ?? [] as $category)
                <option value="{{ $category->id }}"
                        data-slug="{{ $category->slug }}"
                        {{ old('ana_kategori_id', $ilan->ana_kategori_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-admin.ilanlar.components.elegant-input>

        {{-- Alt Kategori --}}
        <x-admin.ilanlar.components.elegant-input
            name="alt_kategori_id"
            type="select"
            label="Alt Kategori"
            :required="true"
            :floating="true"
            onchange="loadYayinTipleri(); safeDispatchCategoryChanged();"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path fill-rule=\'evenodd\' d=\'M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z\' clip-rule=\'evenodd\' />
                    </svg>'"
            helpText="Ana kategoriye bağlı alt kategoriyi seçin">
            <option value="">-- Önce Ana Kategori Seçin --</option>
            @if (isset($ilan) && $ilan->altKategori)
                <option value="{{ $ilan->altKategori->id }}" selected>
                    {{ $ilan->altKategori->name }}
                </option>
            @endif
        </x-admin.ilanlar.components.elegant-input>

        {{-- Yayın Tipi --}}
        <x-admin.ilanlar.components.elegant-input
            name="junction_id"
            type="select"
            label="Yayın Tipi"
            :required="true"
            :floating="true"
            onchange="safeDispatchCategoryChanged();"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path fill-rule=\'evenodd\' d=\'M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z\' clip-rule=\'evenodd\' />
                    </svg>'"
            helpText="Satılık, Kiralık vb. yayın tipini seçin">
            <option value="">-- Önce Alt Kategori Seçin --</option>
            @if (isset($ilan) && $ilan->yayinTipi)
                <option value="{{ $ilan->yayinTipi->id }}" selected>
                    {{ $ilan->yayinTipi->name }}
                </option>
            @endif
        </x-admin.ilanlar.components.elegant-input>
    </div>

    {{-- AI Category Detection Button --}}
    <div class="mt-6 pt-6 border-t border-gray-200/50 dark:border-gray-700/50">
        <button type="button"
                id="ai-category-detect"
                class="group inline-flex items-center gap-3 px-6 py-3.5
                       bg-gradient-to-r from-purple-600 to-pink-600
                       hover:from-purple-700 hover:to-pink-700
                       text-white rounded-xl
                       shadow-lg shadow-purple-500/30
                       hover:shadow-xl hover:shadow-purple-500/40
                       transition-all duration-300
                       hover:scale-105
                       font-semibold">
            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>AI ile Kategori Tespit Et</span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1
                         bg-white/10 dark:bg-slate-800/40 rounded-lg text-xs font-bold">
                ⚡ BETA
            </span>
        </button>
        <p class="mt-3 text-xs text-gray-600 dark:text-gray-400">
            💡 İlan başlığınızı yazarsanız, AI otomatik olarak kategoriyi tespit edebilir
        </p>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

@push('scripts')
<script>
// AI Category Detection (Placeholder)
document.getElementById('ai-category-detect')?.addEventListener('click', function() {
    const baslik = document.getElementById('baslik')?.value;
    if (!baslik) {
        alert('❌ Lütfen önce ilan başlığını girin!');
        return;
    }
    // TODO: AI kategori tespit API çağrısı
    console.log('🤖 AI Kategori Tespiti:', baslik);
});
</script>
@endpush

