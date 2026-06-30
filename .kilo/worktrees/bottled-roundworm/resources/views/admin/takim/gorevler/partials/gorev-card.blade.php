@php
    $gorevDurumu = $gorev->islem_statusu ?? 'bekliyor';
    $gecikti = $gorev->bitis_tarihi && $gorev->bitis_tarihi < now() && $gorevDurumu !== 'tamamlandi';
    $oncelikRenkleri = [
        'acil' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
        'yuksek' => 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200',
        'normal' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
        'dusuk' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
    ];
    $oncelikRenk = $oncelikRenkleri[$gorev->oncelik] ?? $oncelikRenkleri['normal'];
@endphp

<div
    class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 dark:shadow-none dark:bg-slate-900 dark:border-slate-700">
    <!-- Görev Başlığı -->
    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 line-clamp-2 dark:text-slate-100">
        {{ $gorev->baslik }}
    </h3>

    <!-- Atanan Personel (Avatar + İsim) -->
    <div class="flex items-center gap-2 mb-3">
        @if ($gorev->danisman)
            <div class="flex items-center gap-2">
                @if ($gorev->danisman->avatar)
                    <img src="{{ asset('storage/' . $gorev->danisman->avatar) }}" alt="{{ $gorev->danisman->name }}"
                        class="w-8 h-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 dark:border-slate-700">
                @else
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-semibold">
                        {{ strtoupper(substr($gorev->danisman->name, 0, 1)) }}
                    </div>
                @endif
                <span class="text-sm text-gray-700 dark:text-slate-200 font-medium dark:text-slate-300">
                    {{ $gorev->danisman->name }}
                </span>
            </div>
        @else
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400 italic">Atanmamış</span>
            </div>
        @endif
    </div>

    <!-- Bitiş Tarihi -->
    @if ($gorev->bitis_tarihi)
        <div class="mb-3">
            <div class="flex items-center gap-2 text-xs">
                <svg class="w-4 h-4 {{ $gecikti ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                <span
                    class="{{ $gecikti ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-600 dark:text-gray-400' }}">
                    {{ $gorev->bitis_tarihi->format('d.m.Y') }}
                    @if ($gecikti)
                        <span class="ml-1">(Gecikti)</span>
                    @endif
                </span>
            </div>
        </div>
    @endif

    <!-- Öncelik Badge -->
    <div class="mb-3">
        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $oncelikRenk }}">
            @if ($gorev->oncelik === 'acil')
                🚨 Acil
            @elseif($gorev->oncelik === 'yuksek')
                ⬆️ Yüksek
            @elseif($gorev->oncelik === 'normal')
                📋 Normal
            @else
                ⬇️ Düşük
            @endif
        </span>
    </div>

    <!-- İşlem Menüsü (Sağ Alt) -->
    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 dark:border-slate-700">
        <div class="flex items-center justify-end gap-2">
            @if (in_array($gorevDurumu, ['bekliyor', 'beklemede']))
                <!-- Yapılacaklar → İşlemde -->
                <button onclick="advanceStatus({{ $gorev->id }}, 'devam_ediyor')"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md dark:from-blue-700 dark:to-purple-700 dark:shadow-none">
                    Başla
                </button>
            @elseif($gorevDurumu === 'devam_ediyor')
                <!-- İşlemde → Tamamlandı -->
                <button onclick="advanceStatus({{ $gorev->id }}, 'tamamlandi')"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                    Tamamla
                </button>
            @endif

            <!-- Statü Seç Dropdown (Küçük menü) -->
            <div class="relative">
                <select onchange="changeStatus({{ $gorev->id }}, this.value)"
                    class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 appearance-none pr-8 dark:text-slate-100">
                    <option value="bekliyor"
                        {{ in_array($gorevDurumu, ['bekliyor', 'beklemede']) ? 'selected' : '' }}>
                        Yapılacaklar</option>
                    <option value="devam_ediyor" {{ $gorevDurumu === 'devam_ediyor' ? 'selected' : '' }}>İşlemde
                    </option>
                    <option value="tamamlandi" {{ $gorevDurumu === 'tamamlandi' ? 'selected' : '' }}>Tamamlandı
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Görev Detay Linki -->
    <div class="mt-3">
        <a href="{{ route('admin.takim.gorevler.show', $gorev->id) }}"
            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
            Detayları Gör →
        </a>
    </div>
</div>
