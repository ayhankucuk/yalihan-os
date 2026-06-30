{{-- Error Rate Widget (Widget 4) --}}
<div
    class="h-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
            ⚠️ Hata Analizi
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Son 24 Saat</span>
    </div>

    {{-- Loading State --}}
    @if ($loading)
        <div class="flex h-40 animate-pulse items-center justify-center">
            <div class="h-24 w-24 rounded-full border-4 border-gray-200 dark:border-slate-700 dark:border-slate-800">
            </div>
        </div>

        {{-- Data Display --}}
    @elseif(isset($data))
        <div class="flex flex-col items-center">
            {{-- Donut Chart / Durum --}}
            <div class="relative mb-4 h-32 w-32">
                @if ($data['total_errors'] == 0)
                    {{-- Success State --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-green-500">
                        <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium">Hata Yok</span>
                    </div>
                @else
                    {{-- CSS Conic Gradient Donut --}}
                    @php
                        $rate = min($data['error_rate_percent'], 100);
                        $color = $rate > 5 ? '#EF4444' : '#F59E0B'; // Red or Amber
                    @endphp
                    <div class="h-full w-full rounded-full"
                        style="background: conic-gradient({{ $color }} {{ $rate }}%, #E5E7EB 0%);"></div>
                    {{-- Inner Circle for Donut Effect --}}
                    <div
                        class="absolute inset-2 flex flex-col items-center justify-center rounded-full bg-white dark:bg-slate-900">
                        <span
                            class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">%{{ $data['error_rate_percent'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Hata</span>
                    </div>
                @endif
            </div>

            {{-- Recent Errors List --}}
            <div class="mt-2 w-full">
                <h4 class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Son
                    Hatalar</h4>
                @if (isset($data['recent_errors']) && count($data['recent_errors']) > 0)
                    <div class="space-y-2">
                        @foreach ($data['recent_errors'] as $error)
                            <div
                                class="rounded border border-gray-100 bg-gray-50 p-2 text-xs dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex justify-between font-medium">
                                    <span class="text-red-600 dark:text-red-400">{{ $error['durum_kodu'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $error['provider'] }}</span>
                                </div>
                                <div class="mt-1 truncate text-gray-600 dark:text-slate-200"
                                    title="{{ $error['error_message'] }}">
                                    {{ Str::limit($error['error_message'], 30) }}
                                </div>
                                <div class="mt-1 text-right text-gray-400 dark:text-gray-500">
                                    {{ $error['count'] }} kez
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-xs italic text-gray-400 dark:text-gray-500">Yakın zamanda hata kaydı yok.
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="py-8 text-center text-gray-500">Veri yok</div>
    @endif
</div>
