{{-- Featured Properties Section --}}
<section class="ds-section bg-gradient-to-b from-gray-50 to-white">
    <div class="ds-container">
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <div class="ds-badge ds-badge-success mb-6 px-6 py-3 text-base">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                </svg>
                Öne Çıkan İlanlar
            </div>

            <h2 class="text-4xl md:text-5xl font-bold mb-6 ds-text-gradient">
                Premium Emlak Seçenekleri
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                En yeni ve en çok ilgi gören emlak ilanlarımızı keşfedin.
                <span class="font-semibold text-emerald-600">Her bütçeye uygun seçenekler</span> sizleri bekliyor.
            </p>
        </div>

        {{-- Properties Grid --}}
        <div class="ds-grid-3">
            @foreach ($oneKanIlanlar as $ilan)
                <div class="ds-card-hover overflow-hidden group ds-fade-in">
                    {{-- Image Container --}}
                    <div class="relative overflow-hidden aspect-w-16 aspect-h-10">
                        <img src="{{ $ilan->kapak_fotografi_url ?? asset('images/default-property.jpg') }}"
                            alt="{{ $ilan->baslik }}"
                            class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-500">

                        {{-- Price Tag --}}
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-xl shadow-lg dark:bg-slate-900/90">
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TRY' }}
                                    @if ($ilan->kiralama_turu)
                                        @switch($ilan->kiralama_turu)
                                            @case('gunluk')
                                                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/Gün</span>
                                            @break
                                            @case('haftalik')
                                                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/Hafta</span>
                                            @break
                                            @case('aylik')
                                            @case('uzun_donem')
                                                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/Ay</span>
                                            @break
                                            @case('sezonluk')
                                                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/Sezon</span>
                                            @break
                                        @endswitch
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Property Type Badge --}}
                        <div class="absolute top-4 left-4">
                            <div class="bg-emerald-500 text-white px-3 py-1 rounded-lg text-sm font-medium">
                                {{ $ilan->yayinlama_tipi ?? $ilan->emlak_turu }}
                            </div>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        <h3
                            class="text-xl font-bold mb-2 text-gray-800 line-clamp-2 group-hover:text-blue-600 transition-colors dark:text-slate-200">
                            {{ $ilan->baslik }}
                        </h3>

                        <p class="text-gray-600 mb-4 line-clamp-2">
                            {{ $ilan->aciklama }}
                        </p>

                        {{-- Property Features --}}
                        <div class="flex items-center gap-4 mb-4 text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-bed mr-2 text-blue-500"></i>
                                <span>{{ $ilan->oda_sayisi }} Oda</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-ruler-combined mr-2 text-blue-500"></i>
                                <span>{{ $ilan->metrekare }}m²</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                <span>{{ $ilan->adres_ilce ?? $ilan->adres_il }}</span>
                            </div>
                        </div>

                        {{-- Bottom Section --}}
                        <div class="pt-4 border-t border-gray-100 dark:border-slate-800">
                            <div class="flex items-center justify-between mb-3">
                                {{-- Agent Info --}}
                                <div class="flex items-center">
                                    @php
                                        $danisman = $ilan->danisman ?? null;
                                        $avatarUrl = $danisman && $danisman->profile_photo_path
                                            ? asset('storage/' . $danisman->profile_photo_path)
                                            : asset('images/default-avatar.png');
                                    @endphp
                                    <img src="{{ $avatarUrl }}"
                                        alt="{{ $danisman->name ?? 'Danışman' }}"
                                        class="w-10 h-10 rounded-full object-cover mr-3">
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200">
                                            {{ $danisman->name ?? 'Emlak Danışmanı' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Emlak Danışmanı</p>
                                    </div>
                                </div>

                                {{-- View Details Button --}}
                                <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl font-medium hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                    Detaylar
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>

                            {{-- Social Media Links --}}
                            @if($danisman)
                                @php
                                    $hasSocialMedia = !empty($danisman->instagram_profile) ||
                                                     !empty($danisman->linkedin_profile) ||
                                                     !empty($danisman->facebook_profile) ||
                                                     !empty($danisman->twitter_profile) ||
                                                     !empty($danisman->youtube_channel) ||
                                                     !empty($danisman->tiktok_profile) ||
                                                     !empty($danisman->whatsapp_number) ||
                                                     !empty($danisman->telegram_username) ||
                                                     !empty($danisman->website);
                                @endphp
                                @if($hasSocialMedia)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Takip Et:</span>
                                        <x-frontend.danisman-social-links :danisman="$danisman" size="xs" variant="outline" />
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- View All Button --}}
        <div class="text-center mt-16">
            <a href="{{ route('ilanlar.index') }}" class="ds-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg px-10 py-4 text-lg dark:shadow-none">
                Tüm İlanları Görüntüle
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
        </div>
    </div>
</section>
