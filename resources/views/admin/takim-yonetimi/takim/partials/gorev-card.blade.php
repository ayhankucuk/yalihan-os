@php
    // Context7: Accessor üzerinden status erişimi
    $gorevDurumu = $gorev->gorev_durumu ?? 'bekliyor';
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
    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:shadow-md dark:border-gray-600 dark:border-slate-700 dark:bg-gray-700 dark:bg-slate-900 dark:shadow-none">
    <!-- Görev Başlığı -->
    <h3 class="mb-2 line-clamp-2 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
        {{ $gorev->baslik }}
    </h3>

    <!-- Atanan Personel -->
    <div class="mb-3 flex items-center gap-2">
        @if ($gorev->danisman)
            <div class="flex items-center gap-2">
                @if ($gorev->danisman->avatar)
                    <img src="{{ asset('storage/' . $gorev->danisman->avatar) }}" alt="{{ $gorev->danisman->name }}"
                        class="h-8 w-8 rounded-full border-2 border-gray-200 object-cover dark:border-gray-600 dark:border-slate-700">
                @else
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-500 text-xs font-semibold text-white">
                        {{ strtoupper(substr($gorev->danisman->name, 0, 1)) }}
                    </div>
                @endif
                <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    {{ $gorev->danisman->name }}
                </span>
            </div>
        @else
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span class="text-sm italic text-gray-500 dark:text-gray-400">Atanmamış</span>
            </div>
        @endif
    </div>

    <!-- Bitiş Tarihi -->
    @if ($gorev->bitis_tarihi)
        <div class="mb-3">
            <div class="flex items-center gap-2 text-xs">
                <svg class="{{ $gecikti ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} h-4 w-4" fill="none"
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
        <span class="{{ $oncelikRenk }} rounded-full px-2 py-1 text-xs font-medium">
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

    <!-- Durum Değiştir Dropdown -->
    <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-600 dark:border-slate-700">
        {{-- Context7: Accessor üzerinden status erişimi --}}
        @php
            $gorevDurumu = $gorev->gorev_durumu ?? 'bekliyor';
        @endphp
        <select onchange="changeStatus({{ $gorev->id }}, this.value)"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
            <option value="bekliyor" {{ in_array($gorevDurumu, ['bekliyor', 'beklemede']) ? 'selected' : '' }}>
                Yapılacaklar</option>
            <option value="devam_ediyor" {{ $gorevDurumu === 'devam_ediyor' ? 'selected' : '' }}>İşlemde</option>
            <option value="tamamlandi" {{ $gorevDurumu === 'tamamlandi' ? 'selected' : '' }}>Tamamlandı</option>
        </select>
    </div>

    <!-- Görev Detay Linki -->
    <div class="mt-3">
        <a href="{{ route('admin.takim-yonetimi.takim.gorevler.show', $gorev->id) }}"
            class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
            Detayları Gör →
        </a>
    </div>
</div>
