{{-- Request Volume Widget (Widget 2) --}}
<div
    class="h-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
            📊 İstek Hacmi
        </h3>
        <div class="flex space-x-2">
            <span
                class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                Toplam
            </span>
        </div>
    </div>

    {{-- Loading State --}}
    @if ($loading)
        <div class="animate-pulse space-y-4">
            <div class="h-40 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex justify-between">
                <div class="h-4 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                <div class="h-4 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
            </div>
        </div>

        {{-- Empty State --}}
    @elseif(empty($data))
        <div class="flex h-40 flex-col items-center justify-center text-center">
            <svg class="mb-2 h-8 w-8 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Görüntülenecek veri yok</p>
        </div>

        {{-- Data Display (SVG Chart) --}}
    @else
        <div class="relative h-48 w-full">
            {{-- Simple Bar Chart Representation --}}
            <div class="absolute inset-0 flex items-end justify-between space-x-1 pt-6">
                @php
                    $max = collect($data)->max('requests') ?: 1;
                @endphp

                @foreach ($data as $point)
                    @php
                        $height = ($point['requests'] / $max) * 100;
                    @endphp
                    <div class="group relative w-full rounded-t bg-blue-100 dark:bg-blue-900"
                        style="height: {{ $height }}%">
                        <div class="absolute bottom-0 w-full rounded-t bg-blue-500 transition-all hover:bg-blue-600 dark:bg-blue-400"
                            style="height: 100%"></div>

                        {{-- Tooltip --}}
                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">
                            {{ \Carbon\Carbon::parse($point['tarih_saat'])->format('H:i') }}<br>
                            {{ $point['requests'] }} İstek
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- X-Axis Labels --}}
            <div class="absolute bottom-0 w-full border-t border-gray-200 dark:border-slate-800"></div>
        </div>
        <div class="mt-2 flex justify-between text-xs text-gray-400 dark:text-gray-500">
            <span>{{ \Carbon\Carbon::parse($data[0]['tarih_saat'])->format('d M H:i') }}</span>
            <span>{{ \Carbon\Carbon::parse(end($data)['tarih_saat'])->format('d M H:i') }}</span>
        </div>
    @endif
</div>
