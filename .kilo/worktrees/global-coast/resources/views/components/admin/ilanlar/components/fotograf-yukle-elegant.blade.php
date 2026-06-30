{{-- 
🎨 FOTOĞRAF YÜKLEME - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY, Drag & Drop
--}}

<x-admin.ilanlar.components.elegant-form-wrapper
    sectionId="section-photos"
    title="Fotoğraf Yönetimi"
    subtitle="İlan fotoğraflarını yükleyin ve düzenleyin"
    badgeNumber="7"
    badgeColor="pink"
    :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
              <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' 
                    d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\' />
            </svg>'"
    glassEffect="false">
    
    {{-- Upload Area --}}
    <div class="relative">
        <div id="photo-drop-zone"
             class="relative border-2 border-dashed border-gray-300 dark:border-gray-600
                    rounded-xl p-12
                    bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900
                    hover:border-pink-500 dark:hover:border-pink-400
                    hover:bg-pink-50 dark:hover:bg-pink-900/10
                    transition-all duration-300
                    cursor-pointer
                    group">
            <input type="file" 
                   id="photo-upload-input"
                   name="photos[]"
                   multiple
                   accept="image/*"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            
            <div class="text-center pointer-events-none">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 
                            group-hover:text-pink-500 dark:group-hover:text-pink-400
                            group-hover:scale-110
                            transition-all duration-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    Fotoğrafları Sürükle-Bırak
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    veya <span class="text-pink-600 dark:text-pink-400 font-semibold">buraya tıklayın</span>
                </p>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-500">
                    📸 JPG, PNG, WEBP - Maksimum 10MB
                </p>
            </div>
        </div>
    </div>
    
    {{-- Preview Grid --}}
    <div id="photo-preview-grid" class="mt-8 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 hidden">
        {{-- Photos will be dynamically added here --}}
    </div>
    
    {{-- Existing Photos (Edit mode) --}}
    @if(isset($ilan) && $ilan->fotograflar && count($ilan->fotograflar) > 0)
    <div class="mt-8">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4 dark:text-slate-300">
            Mevcut Fotoğraflar ({{ count($ilan->fotograflar) }})
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($ilan->fotograflar as $foto)
            <div class="group relative rounded-xl overflow-hidden 
                        border-2 border-gray-200 dark:border-gray-700
                        hover:border-pink-500 dark:hover:border-pink-400
                        transition-all duration-300
                        hover:scale-105 hover:shadow-xl"
                 data-photo-id="{{ $foto->id }}">
                <img src="{{ $foto->file_path }}" 
                     alt="İlan fotoğrafı"
                     class="w-full h-32 object-cover">
                
                {{-- Overlay Actions --}}
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100
                            flex items-center justify-center gap-2
                            transition-opacity duration-300">
                    <button type="button" 
                            onclick="setFeaturedPhoto({{ $foto->id }})"
                            class="p-2 bg-yellow-500 hover:bg-yellow-600 rounded-lg
                                   text-white text-xs font-bold
                                   transition-all duration-200 hover:scale-110"
                            title="Vitrin Yap">
                        ⭐
                    </button>
                    <button type="button" 
                            onclick="deletePhoto({{ $foto->id }})"
                            class="p-2 bg-red-500 hover:bg-red-600 rounded-lg
                                   text-white text-xs font-bold
                                   transition-all duration-200 hover:scale-110"
                            title="Sil">
                        🗑️
                    </button>
                </div>
                
                {{-- Featured Badge --}}
                @if($foto->one_cikan)
                <div class="absolute top-2 left-2 px-2 py-1 
                            bg-yellow-500 text-white text-xs font-bold rounded-lg
                            shadow-lg">
                    ⭐ VİTRİN
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    {{-- AI Photo Optimization --}}
    <div class="mt-8 p-5 rounded-xl 
                bg-gradient-to-br from-pink-50 to-rose-50 
                dark:from-pink-900/20 dark:to-rose-900/20
                border border-pink-200 dark:border-pink-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white block dark:text-slate-100">
                        AI Fotoğraf Optimizasyonu
                    </span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        Otomatik sıralama ve vitrin önerisi
                    </span>
                </div>
            </div>
            <button type="button" 
                    id="ai-photo-optimize"
                    class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-pink-600 to-rose-600
                           hover:from-pink-700 hover:to-rose-700
                           text-white rounded-lg
                           shadow-md hover:shadow-lg
                           transition-all duration-300
                           hover:scale-105
                           text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                AI Optimize Et
            </button>
        </div>
    </div>
    
    {{-- Upload Stats --}}
    <div class="mt-6 flex items-center justify-between text-sm">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span id="photo-count">0 fotoğraf yüklendi</span>
        </div>
        <div class="text-gray-500 dark:text-gray-500 text-xs">
            💡 İlk fotoğraf otomatik olarak vitrin fotoğrafı olur
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

@push('scripts')
<script>
// Photo Upload Manager - Modern UI
console.log('✅ Fotoğraf Yükleme - Modern UI loaded');
// TODO: Photo upload functionality integration
</script>
@endpush

