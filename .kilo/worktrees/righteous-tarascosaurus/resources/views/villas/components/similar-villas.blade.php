{{-- Similar Villas Section Component --}}
{{-- Pure Tailwind --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 dark:text-slate-100">
        <i class="fas fa-home text-blue-600 dark:text-blue-400"></i>
        Benzer Villalar
    </h2>

    @if($villas && $villas->count() > 0)
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($villas as $villa)
            <a href="{{ route('villas.show', $villa->id) }}"
               class="group block bg-gray-50 dark:bg-slate-900 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                {{-- Villa Image --}}
                <div class="relative aspect-[4/3] overflow-hidden">
                    @if($villa->featuredPhoto)
                    <img src="{{ $villa->featuredPhoto->getImageUrl() }}"
                         alt="{{ $villa->baslik }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         loading="lazy">
                    @else
                    <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-home text-4xl text-gray-400"></i>
                    </div>
                    @endif

                    {{-- Price Badge --}}
                    @if($villa->gunluk_fiyat)
                    <div class="absolute top-3 right-3 px-3 py-1.5 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-lg dark:bg-slate-900/95">
                        <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            ₺{{ number_format($villa->gunluk_fiyat, 0) }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">/ gece</div>
                    </div>
                    @endif
                </div>

                {{-- Villa Info --}}
                <div class="p-4">
                    {{-- Location --}}
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <i class="fas fa-map-marker-alt mr-1 text-blue-600"></i>
                        <span>{{ $villa->il->il_adi ?? '' }}</span>
                    </div>

                    {{-- Title --}}
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                        {{ $villa->baslik }}
                    </h3>

                    {{-- Quick Stats --}}
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        @if($villa->maksimum_misafir)
                        <div class="flex items-center gap-1">
                            <i class="fas fa-users text-xs"></i>
                            <span>{{ $villa->maksimum_misafir }}</span>
                        </div>
                        @endif

                        @if($villa->oda_sayisi)
                        <div class="flex items-center gap-1">
                            <i class="fas fa-bed text-xs"></i>
                            <span>{{ $villa->oda_sayisi }}</span>
                        </div>
                        @endif

                        @if($villa->banyo_sayisi)
                        <div class="flex items-center gap-1">
                            <i class="fas fa-bath text-xs"></i>
                            <span>{{ $villa->banyo_sayisi }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Rating (if available) --}}
                    @if($villa->rating ?? false)
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="flex items-center text-yellow-500">
                            <i class="fas fa-star text-xs"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($villa->rating, 1) }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $villa->review_count ?? 0 }} yorum)</span>
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>

        {{-- View All Button --}}
        <div class="text-center mt-8">
            <a href="{{ route('villas.index') }}"
               class="inline-block px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <i class="fas fa-th-large mr-2"></i>
                Tüm Villaları Gör
            </a>
        </div>
    @else
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <i class="fas fa-home text-4xl mb-3"></i>
            <p>Benzer villa bulunamadı</p>
        </div>
    @endif
</div>
