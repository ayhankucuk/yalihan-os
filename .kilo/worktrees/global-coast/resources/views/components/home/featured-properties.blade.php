@props(['properties' => collect()])
{{-- Öne Çıkan İlanlar — Premium Card Grid
     FA=0 | material-symbols=0 | ds-*=0
--}}

<section class="py-20 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section Header --}}
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-full px-5 py-2 mb-5">
                <x-icon name="yildiz" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Öne Çıkan İlanlar</span>
            </div>

            <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                Premium Emlak
                <span style="background:linear-gradient(90deg,#2563EB,#0891b2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                    Seçenekleri
                </span>
            </h2>
            <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                En yeni ve en çok ilgi gören emlak ilanlarımızı keşfedin.
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">Her bütçeye uygun seçenekler</span> sizleri bekliyor.
            </p>
        </div>

        {{-- Properties Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
            @forelse ($properties as $ilan)
                @php
                    $danisman   = $ilan->danisman ?? null;
                    $avatarUrl  = $danisman && ($danisman->profile_photo_path ?? null)
                        ? asset('storage/' . $danisman->profile_photo_path)
                        : asset('images/default-avatar.png');
                    $kapakUrl   = $ilan->kapak_fotografi_url ?? asset('images/default-property.jpg');
                    $tipStr     = strtolower($ilan->yayinTipi?->yayin_tipi ?? $ilan->yayinlama_tipi ?? '');
                    $badgeBg    = $tipStr === 'kiralik' ? 'bg-amber-500' : 'bg-emerald-500';
                    $badgeLabel = $tipStr === 'kiralik' ? 'KİRALIK' : 'SATILIK';
                @endphp

                <article class="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-slate-100 dark:border-slate-700">

                    {{-- Görsel --}}
                    <div class="relative overflow-hidden h-56">
                        <img src="{{ $kapakUrl }}"
                             alt="{{ $ilan->baslik }}"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                        {{-- Fiyat badge --}}
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-3 py-1.5 rounded-xl shadow-md">
                                <span class="text-base font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($ilan->fiyat ?? 0, 0, ',', '.') }}
                                    {{ $ilan->para_birimi ?? 'TRY' }}
                                    @if($ilan->kiralama_turu ?? null)
                                        <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/
                                            @switch($ilan->kiralama_turu)
                                                @case('gunluk')   Gün    @break
                                                @case('haftalik') Hafta  @break
                                                @case('aylik')    Ay     @break
                                                @case('uzun_donem') Ay   @break
                                                @case('sezonluk') Sezon  @break
                                                @default          Dönem
                                            @endswitch
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Tür badge --}}
                        <div class="absolute top-4 left-4">
                            <span class="{{ $badgeBg }} text-white text-[10px] font-bold px-2.5 py-1 rounded-full tracking-wider uppercase">
                                {{ $badgeLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- İçerik --}}
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1.5 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            {{ $ilan->baslik }}
                        </h3>

                        @if($ilan->aciklama ?? null)
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 leading-relaxed">
                            {{ $ilan->aciklama }}
                        </p>
                        @endif

                        {{-- Özellikler --}}
                        <div class="flex items-center gap-4 mb-4 text-sm text-slate-600 dark:text-slate-300">
                            @if($ilan->oda_sayisi ?? null)
                            <div class="flex items-center gap-1.5">
                                <x-icon name="yatak" class="w-4 h-4 text-blue-500" />
                                <span>{{ $ilan->oda_sayisi }} Oda</span>
                            </div>
                            @endif
                            @if(($ilan->net_m2 ?? null) || ($ilan->metrekare ?? null))
                            <div class="flex items-center gap-1.5">
                                <x-icon name="alan" class="w-4 h-4 text-blue-500" />
                                <span>{{ $ilan->net_m2 ?? $ilan->metrekare }}m²</span>
                            </div>
                            @endif
                            @php
                                $ilceAdi = $ilan->ilce?->ilce_adi ?? ($ilan->adres_ilce ?? null);
                                $ilAdi   = $ilan->il?->il_adi   ?? ($ilan->adres_il   ?? null);
                                $konumStr = $ilceAdi ?? $ilAdi ?? null;
                            @endphp
                            @if($konumStr)
                            <div class="flex items-center gap-1.5">
                                <x-icon name="konum" class="w-4 h-4 text-blue-500" />
                                <span class="truncate max-w-[100px]">{{ $konumStr }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Alt bölüm --}}
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-3">
                                {{-- Danışman --}}
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $avatarUrl }}"
                                         alt="{{ $danisman->name ?? 'Danışman' }}"
                                         class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-600">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-white leading-tight">
                                            {{ $danisman->name ?? 'Emlak Danışmanı' }}
                                        </p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500">Danışman</p>
                                    </div>
                                </div>

                                {{-- Detay butonu --}}
                                <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-sm font-semibold rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                    Detaylar
                                    <x-icon name="sag-ok" class="w-4 h-4" />
                                </a>
                            </div>

                            {{-- Sosyal medya --}}
                            @if($danisman)
                                @php
                                    $hasSocial = !empty($danisman->instagram_profile)
                                              || !empty($danisman->linkedin_profile)
                                              || !empty($danisman->facebook_profile)
                                              || !empty($danisman->twitter_profile)
                                              || !empty($danisman->youtube_channel)
                                              || !empty($danisman->tiktok_profile)
                                              || !empty($danisman->whatsapp_number)
                                              || !empty($danisman->telegram_username)
                                              || !empty($danisman->website);
                                @endphp
                                @if($hasSocial)
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Takip Et:</span>
                                        <x-frontend.danisman-social-links :danisman="$danisman" size="xs" variant="outline" />
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </article>

            @empty
                <div class="col-span-full py-16 text-center">
                    <x-icon name="ev" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                    <p class="text-slate-400 dark:text-slate-500 text-sm">Şu an öne çıkan ilan bulunmuyor.</p>
                </div>
            @endforelse
        </div>

        {{-- Tümünü Gör --}}
        <div class="text-center mt-14">
            <a href="{{ route('ilanlar.index') }}"
               class="inline-flex items-center gap-2 px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-base rounded-xl shadow-md shadow-blue-600/20 hover:shadow-blue-600/30 transition-all duration-200 active:scale-[0.98]">
                Tüm İlanları Görüntüle
                <x-icon name="sag-ok" class="w-5 h-5" />
            </a>
        </div>

    </div>
</section>
