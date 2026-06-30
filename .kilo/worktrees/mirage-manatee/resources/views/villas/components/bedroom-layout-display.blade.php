{{-- Bedroom Layout Display Component (Public View) --}}
{{-- Pure Tailwind + Alpine.js --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

@php
    $bedrooms = $bedrooms ?? [];
    $bedTypes = [
        'double' => ['icon' => '🛏️', 'label' => 'Çift Kişilik Yatak'],
        'single' => ['icon' => '🛌', 'label' => 'Tek Kişilik Yatak'],
        'queen' => ['icon' => '👑', 'label' => 'Queen Yatak'],
        'king' => ['icon' => '♔', 'label' => 'King Yatak'],
        'bunk' => ['icon' => '🏢', 'label' => 'Ranza'],
        'sofa_bed' => ['icon' => '🛋️', 'label' => 'Çekyat/Kanepe'],
    ];

    $totalCapacity = 0;
    foreach ($bedrooms as $bedroom) {
        foreach ($bedroom['beds'] ?? [] as $bed) {
            $capacity = match($bed['type']) {
                'double', 'queen', 'king' => 2,
                'single', 'sofa_bed' => 1,
                'bunk' => 2,
                default => 1
            };
            $totalCapacity += $capacity * ($bed['count'] ?? 1);
        }
        $totalCapacity += ($bedroom['extra_beds'] ?? 0);
    }
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">bed</span>
            Nerede Uyuyacaksınız?
        </h2>
        <div class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-lg font-semibold">
            <span class="material-symbols-outlined mr-2" style="font-size:18px;vertical-align:middle">group</span>
            Toplam {{ $totalCapacity }} Kişi
        </div>
    </div>

    @if(count($bedrooms) > 0)
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($bedrooms as $index => $bedroom)
            <div class="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border-2 border-purple-200 dark:border-purple-700 rounded-xl p-5 hover:shadow-lg transition-shadow">
                {{-- Bedroom Name --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $bedroom['name'] ?? ('Yatak Odası ' . ($index + 1)) }}
                    </h3>
                    @php
                        $roomCapacity = 0;
                        foreach ($bedroom['beds'] ?? [] as $bed) {
                            $capacity = match($bed['type']) {
                                'double', 'queen', 'king' => 2,
                                'single', 'sofa_bed' => 1,
                                'bunk' => 2,
                                default => 1
                            };
                            $roomCapacity += $capacity * ($bed['count'] ?? 1);
                        }
                        $roomCapacity += ($bedroom['extra_beds'] ?? 0);
                    @endphp
                    <span class="text-sm font-semibold px-3 py-1 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 rounded-full">
                        {{ $roomCapacity }} kişi
                    </span>
                </div>

                {{-- Beds --}}
                <div class="space-y-3 mb-4">
                    @foreach($bedroom['beds'] ?? [] as $bed)
                    <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg">
                        <div class="text-3xl">{{ $bedTypes[$bed['type']]['icon'] ?? '🛏️' }}</div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $bedTypes[$bed['type']]['label'] ?? $bed['type'] }}
                            </div>
                            @if(($bed['count'] ?? 1) > 1)
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $bed['count'] }} adet
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Extra Features --}}
                @if(isset($bedroom['ensuite']) && $bedroom['ensuite'])
                <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400 mb-2">
                    <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                    <span>Özel Banyo (Ensuite)</span>
                </div>
                @endif

                @if(isset($bedroom['balcony']) && $bedroom['balcony'])
                <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-400 mb-2">
                    <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                    <span>Balkon</span>
                </div>
                @endif

                @if(isset($bedroom['ac']) && $bedroom['ac'])
                <div class="flex items-center gap-2 text-sm text-cyan-700 dark:text-cyan-400 mb-2">
                    <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                    <span>Klima</span>
                </div>
                @endif

                {{-- Extra Beds --}}
                @if(($bedroom['extra_beds'] ?? 0) > 0)
                <div class="mt-3 pt-3 border-t border-purple-200 dark:border-purple-700">
                    <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        <span class="material-symbols-outlined text-purple-600 mr-1" style="font-size:16px">add_circle</span>
                        <strong>{{ $bedroom['extra_beds'] }}</strong> ekstra yatak (şilte/çekyat)
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Summary Box --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-6">
            <div class="grid md:grid-cols-3 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ count($bedrooms) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yatak Odası</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ collect($bedrooms)->sum(fn($b) => count($b['beds'] ?? [])) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Toplam Yatak</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $totalCapacity }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Maksimum Kapasite</div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <span class="material-symbols-outlined mb-3" style="font-size:48px">bed</span>
            <p>Yatak odası bilgisi mevcut değil</p>
        </div>
    @endif
</div>
