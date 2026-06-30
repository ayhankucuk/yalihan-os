{{-- 
🎨 LOKASYON + HARİTA - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY, Leaflet Integration
--}}

<x-admin.ilanlar.components.elegant-form-wrapper
    sectionId="section-location"
    title="Lokasyon ve Harita"
    subtitle="İlanın konum bilgilerini belirleyin ve haritada işaretleyin"
    badgeNumber="4"
    badgeColor="blue"
    :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' 
                    d=\'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z\' />
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' 
                    d=\'M15 11a3 3 0 11-6 0 3 3 0 016 0z\' />
            </svg>'"
    glassEffect="false">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- İl --}}
        <x-admin.ilanlar.components.elegant-input
            name="il_id"
            type="select"
            label="İl"
            :required="true"
            :floating="true"
            onchange="loadIlceler(this.value)"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path fill-rule=\'evenodd\' d=\'M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' />
                    </svg>'"
            helpText="İli seçin">
            <option value="">-- İl Seçin --</option>
            @if(isset($iller))
                @foreach($iller as $il)
                    <option value="{{ $il->id }}" 
                            {{ old('il_id', $ilan->il_id ?? '') == $il->id ? 'selected' : '' }}>
                        {{ $il->il_adi }}
                    </option>
                @endforeach
            @endif
        </x-admin.ilanlar.components.elegant-input>
        
        {{-- İlçe --}}
        <x-admin.ilanlar.components.elegant-input
            name="ilce_id"
            type="select"
            label="İlçe"
            :required="true"
            :floating="true"
            onchange="loadMahalleler(this.value)"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path fill-rule=\'evenodd\' d=\'M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' />
                    </svg>'"
            helpText="İlçeyi seçin">
            <option value="">-- Önce İl Seçin --</option>
            @if(isset($ilan) && $ilan->ilce)
                <option value="{{ $ilan->ilce->id }}" selected>
                    {{ $ilan->ilce->ilce_adi }}
                </option>
            @endif
        </x-admin.ilanlar.components.elegant-input>
        
        {{-- Mahalle --}}
        <x-admin.ilanlar.components.elegant-input
            name="mahalle_id"
            type="select"
            label="Mahalle"
            :required="false"
            :floating="true"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                      <path fill-rule=\'evenodd\' d=\'M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' />
                    </svg>'"
            helpText="Mahalleyi seçin (opsiyonel)">
            <option value="">-- Önce İlçe Seçin --</option>
            @if(isset($ilan) && $ilan->mahalle)
                <option value="{{ $ilan->mahalle->id }}" selected>
                    {{ $ilan->mahalle->mahalle_adi }}
                </option>
            @endif
        </x-admin.ilanlar.components.elegant-input>
    </div>
    
    {{-- Adres Detayı --}}
    <x-admin.ilanlar.components.elegant-input
        name="adres"
        type="textarea"
        label="Adres Detayı"
        placeholder="Mahalle, sokak, bina no ve diğer adres detaylarını yazın..."
        :value="old('adres', $ilan->adres ?? '')"
        :required="false"
        rows="3"
        :floating="true"
        :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                  <path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\' />
                </svg>'"
        helpText="Detaylı adres bilgisi" />
    
    {{-- Harita Bölümü --}}
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                İnteraktif Harita
            </h3>
            <button type="button" 
                    id="detect-location-btn"
                    class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-blue-600 to-cyan-600
                           hover:from-blue-700 hover:to-cyan-700
                           text-white rounded-lg
                           shadow-md hover:shadow-lg
                           transition-all duration-300
                           hover:scale-105
                           text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Konumumu Bul
            </button>
        </div>
        
        <div class="relative rounded-xl overflow-hidden border-2 border-gray-300 dark:border-gray-600
                    shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div id="map" class="w-full h-96 bg-gray-100 dark:bg-slate-900"></div>
            
            {{-- Map Loading Overlay --}}
            <div id="map-loading" 
                 class="absolute inset-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm
                        flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-blue-600 dark:text-blue-400 animate-spin" 
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" 
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" 
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-3 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Harita yükleniyor...
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Koordinatlar --}}
        <div class="grid grid-cols-2 gap-4 mt-4">
            <x-admin.ilanlar.components.elegant-input
                name="lat"
                type="number"
                label="Enlem (Latitude)"
                placeholder="37.0000"
                :value="old('lat', $ilan->lat ?? '')"
                step="0.0000001"
                :required="false"
                :floating="true"
                :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                          <path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z\' clip-rule=\'evenodd\' />
                        </svg>'"
                helpText="Haritadan otomatik doldurulur" />
            
            <x-admin.ilanlar.components.elegant-input
                name="lng"
                type="number"
                label="Boylam (Longitude)"
                placeholder="27.0000"
                :value="old('lng', $ilan->lng ?? '')"
                step="0.0000001"
                :required="false"
                :floating="true"
                :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                          <path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z\' clip-rule=\'evenodd\' />
                        </svg>'"
                helpText="Haritadan otomatik doldurulur" />
        </div>
        
        {{-- TKGM Auto-Fill (Arsa için) --}}
        <div class="mt-6 p-5 rounded-xl 
                    bg-gradient-to-br from-blue-50 to-cyan-50 
                    dark:from-blue-900/20 dark:to-cyan-900/20
                    border border-blue-200 dark:border-blue-800
                    kategori-specific-section"
                    data-show-for-categories="arsa"
                    style="display: none;">
            <div class="flex items-center gap-3 mb-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">TKGM Otomatik Veri Çekme</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                💡 Ada/Parsel numarası girdiğinizde, TKGM'den otomatik olarak imar statusu ve koordinatlar çekilecektir.
            </p>
            <div class="grid grid-cols-2 gap-3">
                <x-admin.ilanlar.components.elegant-input
                    name="ada_no"
                    type="text"
                    label="Ada No"
                    placeholder="123"
                    :value="old('ada_no', $ilan->ada_no ?? '')"
                    :required="false"
                    :floating="true" />
                
                <x-admin.ilanlar.components.elegant-input
                    name="parsel_no"
                    type="text"
                    label="Parsel No"
                    placeholder="45"
                    :value="old('parsel_no', $ilan->parsel_no ?? '')"
                    :required="false"
                    :floating="true" />
            </div>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Harita initialization - Mevcut location.js ile entegre edilecek
console.log('✅ Lokasyon + Harita - Modern UI loaded');
// TODO: VanillaLocationManager integration
</script>
@endpush

