<!-- İlan Kartı Component -->
@props([
    'ilan',
    'showCheckbox' => true,
    'showQuickActions' => true,
    'showPrice' => true,
    'showDansman' => true,
    'compactMode' => false,
])

<div class="modern-listing-card {{ $compactMode ? 'compact' : '' }}">
    @if ($showCheckbox)
        <!-- Selection Checkbox -->
        <div class="absolute top-3 left-3 z-20">
            <input type="checkbox" value="{{ $ilan->id }}"
                class="listing-checkbox w-4 h-4 text-blue-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:bg-slate-900">
        </div>
    @endif

    @if ($showQuickActions)
        <!-- Quick Actions -->
        <div class="listing-quick-actions">
            <div class="flex space-x-2">
                <a href="{{ route('ilanlar.show', $ilan->id) }}" class="islem-butonu islem-butonu-goruntule" title="Görüntüle">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('ilanlar.edit', $ilan->id) }}" class="islem-butonu islem-butonu-duzenle" title="Düzenle">
                    <i class="fas fa-edit"></i>
                </a>
                <button onclick="deleteListing({{ $ilan->id }})" class="islem-butonu islem-butonu-sil"
                    title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Durum Rozetleri -->
    <div class="mb-3">
        @if ($ilan->yayindami)
            <span class="durum-etiketi yayinda">
                <i class="fas fa-eye mr-1"></i> Aktif
            </span>
        @else
            <span class="durum-etiketi pasif">
                <i class="fas fa-eye-slash mr-1"></i> Pasif
            </span>
        @endif

        {{-- Context7: Accessor üzerinden durum erişimi --}}
        @php
            $ilanDurumu = $ilan->yayin_durumu ?? 'Taslak';
        @endphp
        @if ($ilanDurumu == 'satildi')
            <span class="durum-etiketi satildi ml-2">
                <i class="fas fa-handshake mr-1"></i> Satıldı
            </span>
        @elseif ($ilanDurumu == 'kiralandi')
            <span class="durum-etiketi kiraland ml-2">
                <i class="fas fa-key mr-1"></i> Kiralandı
            </span>
        @endif
    </div>

    <!-- Content -->
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2 dark:text-slate-100">
            {{ $ilan->baslik ?? 'Başlık Yok' }}
        </h3>

        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <!-- Konum -->
            <div class="flex items-center">
                <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                <span>{{ $ilan->adres_il ?? 'İl' }} / {{ $ilan->adres_ilce ?? 'İlçe' }}</span>
            </div>

            <!-- Danışman -->
            @if ($showDansman && $ilan->danisman)
                <div class="space-y-2">
                    <div class="flex items-center">
                        <i class="fas fa-user text-gray-400 mr-2"></i>
                        <span>{{ $ilan->danisman->name ?? ($ilan->danisman->ad ?? '') . ' ' . ($ilan->danisman->soyad ?? '') }}</span>
                    </div>
                    @php
                        $danisman = $ilan->danisman;
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
                        <div class="ml-6">
                            <x-frontend.danisman-social-links :danisman="$danisman" size="xs" variant="outline" />
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tarih -->
            <div class="flex items-center">
                <i class="fas fa-calendar text-gray-400 mr-2"></i>
                <span>{{ $ilan->created_at->format('d.m.Y') }}</span>
            </div>

            <!-- İlan Türü -->
            @if ($ilan->ilan_turu)
                <div class="flex items-center">
                    <i class="fas fa-tag text-gray-400 mr-2"></i>
                    <span>{{ $ilan->ilan_turu }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Price -->
    @if ($showPrice && $ilan->fiyat)
        <div class="mb-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TRY' }}
                @if ($ilan->kiralama_turu)
                    @switch($ilan->kiralama_turu)
                        @case('gunluk')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Gün</span>
                        @break
                        @case('haftalik')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Hafta</span>
                        @break
                        @case('aylik')
                        @case('uzun_donem')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Ay</span>
                        @break
                        @case('sezonluk')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Sezon</span>
                        @break
                    @endswitch
                @endif
            </div>
            @if ($ilan->fiyat_tipi)
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $ilan->fiyat_tipi }}</div>
            @endif
        </div>
    @endif

    <!-- Actions -->
    <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-slate-800">
        <div class="flex space-x-2">
            <span class="text-xs text-gray-500">ID: {{ $ilan->id }}</span>
            @if ($ilan->kategori)
                <span class="text-xs text-gray-500">{{ $ilan->kategori->name ?? '' }}</span>
            @endif
        </div>
        <div class="flex space-x-2 items-center">
                {{ $ilan->yayindami ? 'Pasifleştir' : 'Aktifleştir' }}
            <button onclick="duplicateListing({{ $ilan->id }})"
                class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 font-medium">
                Kopyala
            </button>
            {{-- QR Code Button --}}
            <div class="relative" x-data="{ showQR: false }">
                <button @click="showQR = !showQR"
                        class="text-xs text-purple-600 hover:text-purple-800 dark:text-purple-400 font-medium"
                        title="QR Kod">
                    <i class="fas fa-qrcode"></i>
                </button>
                <div x-show="showQR"
                     @click.away="showQR = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 z-50">
                    <x-qr-code-display :ilan="$ilan" :size="'small'" />
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .islem-butonu {
        @apply p-2 rounded-lg text-sm transition-colors;
    }

    .islem-butonu-goruntule {
        @apply bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white;
    }

    .islem-butonu-duzenle {
        @apply bg-green-500 hover:bg-green-600 dark:bg-green-700 dark:hover:bg-green-800 text-white;
    }

    .islem-butonu-sil {
        @apply bg-red-500 hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-800 text-white;
    }

    .durum-etiketi {
        @apply px-2 py-1 rounded-full text-xs font-medium;
    }

    .durum-etiketi.yayinda {
        @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
    }

    .durum-etiketi.pasif {
        @apply bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200;
    }

    .durum-etiketi.satildi {
        @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
    }

    .durum-etiketi.kiraland {
        @apply bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200;
    }

    .modern-listing-card.compact {
        @apply p-3;
    }

    .modern-listing-card.compact h3 {
        @apply text-base;
    }

    .modern-listing-card.compact .text-2xl {
        @apply text-xl;
    }
</style>
