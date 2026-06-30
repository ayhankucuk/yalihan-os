@php
    $items = $ilanlar ?? collect();
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
    @forelse($items as $ilan)
        <div class="group bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden hover:shadow-2xl hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-500 hover:-translate-y-2 dark:border-slate-700">

            {{-- İÇERİK BÖLÜMÜ --}}
            <div class="p-6 space-y-4">

                {{-- BAŞLIK (Elegant Typography) --}}
                <div class="mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white leading-tight tracking-tight dark:text-slate-100">
                        <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                           class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300 hover:underline decoration-2 underline-offset-4">
                            {{ $ilan->baslik }}
                        </a>
                    </h3>
                </div>

                {{-- FOTOĞRAF (Elegant Shadow & Border) --}}
                @php
                    $kapaFoto = null;
                    try {
                        $kapaFoto = $ilan->kapak_fotografi ?? optional($ilan->fotograflar)->first();
                    } catch (\Exception $e) {
                        $kapaFoto = null;
                    }
                    $fotoUrl = $kapaFoto ? \Illuminate\Support\Facades\Storage::url($kapaFoto->dosya_yolu) : null;
                @endphp

                <div class="relative h-56 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-800 dark:to-gray-900 rounded-xl overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-500">
                    <a href="{{ route('admin.ilanlar.show', $ilan->id) }}">
                        @if($fotoUrl)
                            <img src="{{ $fotoUrl }}"
                                 alt="{{ $ilan->baslik }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                                <svg class="w-20 h-20 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-xs font-medium uppercase tracking-wider">Fotoğraf Yok</span>
                            </div>
                        @endif
                    </a>

                    {{-- Ref + Fiyat Badge (Elegant Glassmorphism) --}}
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-xs font-bold shadow-xl backdrop-blur-md border border-white/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Ref: {{ $ilan->kisa_referans ?? str_pad($ilan->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div class="absolute top-3 right-3">
                        <span class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl text-base font-black shadow-xl backdrop-blur-md border border-white/20">
                            {{ number_format($ilan->fiyat) }} <span class="text-sm font-semibold">{{ $ilan->para_birimi }}</span>
                        </span>
                    </div>
                </div>

                {{-- AÇIKLAMA NOTU (Elegant Card Style) --}}
                @if($ilan->aciklama)
                    <div class="relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                        <div class="pl-4 pr-2">
                            <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400 italic font-light">
                                <span class="font-semibold text-blue-700 dark:text-blue-400 not-italic">Not:</span>
                                {{ Str::limit($ilan->aciklama, 150) }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Lokasyon + Site (Elegant Badges) --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($ilan->site)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-400 rounded-lg font-semibold text-xs border border-blue-200/50 dark:border-blue-800/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $ilan->site->name }}
                        </span>
                    @endif

                    @if($ilan->ilce && $ilan->il)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg font-medium text-xs dark:text-slate-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $ilan->ilce->ilce_adi }} / {{ $ilan->il->il_adi }}
                        </span>
                    @endif
                </div>

                {{-- İlan Sahibi (Elegant) --}}
                @if($ilan->ilanSahibi)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-xs shadow-lg">
                            {{ substr($ilan->ilanSahibi->ad, 0, 1) }}{{ substr($ilan->ilanSahibi->soyad, 0, 1) }}
                        </div>
                        <span class="font-medium">{{ $ilan->ilanSahibi->ad }} {{ $ilan->ilanSahibi->soyad }}</span>
                    </div>
                @endif

                {{-- Durum + Butonlar (Elegant Action Bar) --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    @php
                        $yayinDurumu = $ilan->yayin_durumu ?? 'Taslak';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full shadow-md
                        {{ $yayinDurumu === 'Aktif' || $yayinDurumu === 1 ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}
                        {{ $yayinDurumu === 'Pasif' || $yayinDurumu === 0 ? 'bg-gradient-to-r from-gray-400 to-gray-500 text-white' : '' }}
                        {{ $yayinDurumu === 'Taslak' ? 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white' : '' }}
                        {{ $yayinDurumu === 'Beklemede' ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white' : '' }}">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="10" cy="10" r="4"></circle>
                        </svg>
                        {{ is_numeric($yayinDurumu) ? ($yayinDurumu == 1 ? 'Aktif' : 'Pasif') : $yayinDurumu }}
                    </span>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-xs font-bold rounded-xl hover:from-blue-700 hover:to-blue-800 hover:scale-110 hover:shadow-xl active:scale-95 transition-all duration-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Düzenle
                        </a>
                        <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 text-white text-xs font-bold rounded-xl hover:from-gray-700 hover:to-gray-800 hover:scale-110 hover:shadow-xl active:scale-95 transition-all duration-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Detay
                        </a>
                    </div>
                </div>

                {{-- Görüntülenme (Elegant Progress) --}}
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span class="font-medium">Görüntülenme</span>
                        </div>
                        <span class="font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ number_format($ilan->goruntulenme ?? 0) }}</span>
                    </div>
                    <div class="relative w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                        <div class="absolute inset-0 bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 transition-all duration-700 ease-out shadow-lg"
                             style="width: {{ min(100, max(5, (int) ($ilan->goruntulenme ?? 0) / 10)) }}%"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-20">
            <div class="inline-flex flex-col items-center gap-4 text-gray-600 dark:text-gray-400">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-800 dark:to-gray-900 flex items-center justify-center shadow-xl">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xl font-bold mb-1">Kayıt Bulunamadı</p>
                    <p class="text-sm">Arama kriterlerinize uygun ilan bulunamadı</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@push('styles')
<style>
    /* Elegant Shimmer Animation */
    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }

    .animate-shimmer {
        animation: shimmer 2s infinite;
    }

    /* Line clamp utilities */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
