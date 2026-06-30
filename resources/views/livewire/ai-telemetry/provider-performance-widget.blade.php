{{-- Provider Performance Widget (Widget 3) --}}
<div
    class="h-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
            🚀 Sağlayıcı Performansı
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Son 24 Saat</span>
    </div>

    {{-- Loading State --}}
    @if ($loading)
        <div class="animate-pulse space-y-4">
            <div class="h-8 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-8 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-8 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>

        {{-- Empty State --}}
    @elseif(empty($data))
        <div class="py-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Veri bulunamadı</p>
        </div>

        {{-- Data Display --}}
    @else
        <div class="space-y-4">
            @foreach ($data as $provider)
                <div class="relative">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span
                            class="font-medium uppercase text-gray-700 dark:text-slate-300">{{ $provider['provider'] }}</span>
                        <div class="text-right">
                            <span
                                class="font-bold text-gray-900 dark:text-slate-100">{{ $provider['avg_latency_ms'] }}ms</span>
                            <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">ort. gecikme</span>
                        </div>
                    </div>

                    {{-- Latency Bar --}}
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        @php
                            // Normalize 2000ms as "full bar" for visual scale
                            $width = min(($provider['avg_latency_ms'] / 2000) * 100, 100);
                            $colorClass =
                                $provider['avg_latency_ms'] < 500
                                    ? 'bg-green-500'
                                    : ($provider['avg_latency_ms'] < 1000
                                        ? 'bg-yellow-500'
                                        : 'bg-red-500');
                        @endphp
                        <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ $width }}%"></div>
                    </div>

                    {{-- Sub metrics --}}
                    <div class="mt-1 flex justify-between text-xs">
                        <span
                            class="{{ $provider['success_rate'] >= 99 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                            %{{ $provider['success_rate'] }} Başarı
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            ${{ number_format($provider['cost_usd'], 4) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
