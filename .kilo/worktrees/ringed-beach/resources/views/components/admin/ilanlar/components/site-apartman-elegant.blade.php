{{-- 
🎨 SİTE/APARTMAN - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper
    sectionId="section-site"
    title="Site / Apartman Bilgileri"
    subtitle="İlanın ait olduğu site veya apartman bilgilerini girin"
    badgeNumber="3"
    badgeColor="cyan"
    :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' 
                    d=\'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4\' />
            </svg>'"
    glassEffect="false"
    class="kategori-specific-section"
    data-show-for-categories="konut"
    style="display: none;">
    
    <div class="space-y-6">
        {{-- Site Seç / Ekle --}}
        <x-admin.ilanlar.components.elegant-input
            name="site_id"
            type="select"
            label="Site / Apartman"
            :required="false"
            :floating="true"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\' />
                    </svg>'"
            helpText="İlanın ait olduğu site veya apartmanı seçin">
            <option value="">-- Site/Apartman Yok --</option>
            @if(isset($sites))
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" 
                            {{ old('site_id', $ilan->site_id ?? '') == $site->id ? 'selected' : '' }}>
                        {{ $site->name }}
                    </option>
                @endforeach
            @endif
        </x-admin.ilanlar.components.elegant-input>
        
        {{-- Blok/Kat Bilgileri --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-admin.ilanlar.components.elegant-input
                name="blok_adi"
                type="text"
                label="Blok Adı"
                placeholder="Örn: A Blok"
                :value="old('blok_adi', $ilan->blok_adi ?? '')"
                :required="false"
                :floating="true"
                :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                          <path fill-rule=\'evenodd\' d=\'M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z\' clip-rule=\'evenodd\' />
                        </svg>'"
                helpText="Varsa blok adını girin" />
            
            <x-admin.ilanlar.components.elegant-input
                name="kat"
                type="number"
                label="Kat"
                placeholder="Kaçıncı kat"
                :value="old('kat', $ilan->kat ?? '')"
                :required="false"
                :floating="true"
                :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                          <path fill-rule=\'evenodd\' d=\'M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z\' clip-rule=\'evenodd\' />
                        </svg>'"
                helpText="İlanın bulunduğu kat" />
        </div>
        
        {{-- Site Özellikleri (Quick Summary) --}}
        <div class="p-5 rounded-xl 
                    bg-gradient-to-br from-cyan-50 to-blue-50 
                    dark:from-cyan-900/20 dark:to-blue-900/20
                    border border-cyan-200 dark:border-cyan-800">
            <div class="flex items-center gap-3 mb-3">
                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Site Bilgisi</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                💡 Site seçili değilse, konum bilgilerinden otomatik olarak tespit edilecektir.
            </p>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

