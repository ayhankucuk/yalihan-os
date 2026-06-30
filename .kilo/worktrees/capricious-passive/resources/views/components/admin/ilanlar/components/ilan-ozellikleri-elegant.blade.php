{{-- 
🎨 İLAN ÖZELLİKLERİ - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY, Dynamic Fields
--}}

<x-admin.ilanlar.components.elegant-form-wrapper
    sectionId="section-fields"
    title="İlan Özellikleri"
    subtitle="Kategoriye özel özellik ve detayları girin"
    badgeNumber="8"
    badgeColor="purple"
    :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' 
                    d=\'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01\' />
            </svg>'"
    glassEffect="true">
    
    {{-- İlan özellikleri mevcut dynamic system ile yüklenecek --}}
    <div id="dynamic-fields-container">
        {{-- Dynamic fields will be loaded here based on category --}}
        @include('admin.ilanlar.components.field-dependencies-dynamic')
    </div>
    
    {{-- Loading State --}}
    <div id="fields-loading" class="hidden">
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto text-purple-600 dark:text-purple-400 animate-spin" 
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" 
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" 
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    Özellikler yükleniyor...
                </p>
            </div>
        </div>
    </div>
    
    {{-- Empty State (No Category Selected) --}}
    <div id="fields-empty-state" class="py-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
            Kategori Seçimi Bekleniyor
        </h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Önce kategori seçin, sonra özellikler otomatik olarak yüklenecektir
        </p>
    </div>
    
    {{-- AI Field Suggestions --}}
    <div class="mt-8 p-5 rounded-xl 
                bg-gradient-to-br from-purple-50 to-pink-50 
                dark:from-purple-900/20 dark:to-pink-900/20
                border border-purple-200 dark:border-purple-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white block dark:text-slate-100">
                        AI Alan Önerileri
                    </span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        Lokasyon ve fiyata göre otomatik özellik önerileri
                    </span>
                </div>
            </div>
            <button type="button" 
                    id="ai-field-suggestion"
                    class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-purple-600 to-pink-600
                           hover:from-purple-700 hover:to-pink-700
                           text-white rounded-lg
                           shadow-md hover:shadow-lg
                           transition-all duration-300
                           hover:scale-105
                           text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Öneriler Al
            </button>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

@push('scripts')
<script>
// İlan Özellikleri - Dynamic Fields
console.log('✅ İlan Özellikleri - Modern UI loaded');
// Mevcut dynamic field system ile entegre edilecek
</script>
@endpush

