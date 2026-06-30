{{-- Live Activity Widget (Widget 6) --}}
<div class="h-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
    wire:poll.5s.visible="fetchRecentActivity">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="flex items-center text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
            <span class="relative mr-2 flex h-3 w-3">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
            </span>
            Canlı Aktivite
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Son 10 İşlem</span>
    </div>

    {{-- Loading State --}}
    @if ($loading && empty($activities))
        <div class="animate-pulse space-y-3">
            @for ($i = 0; $i < 5; $i++)
                <div class="h-8 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
            @endfor
        </div>

        {{-- Empty State --}}
    @elseif(empty($activities))
        <div class="py-12 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Henüz aktivite yok</p>
        </div>

        {{-- Activity Feed --}}
    @else
        <div class="space-y-3">
            @foreach ($activities as $activity)
                {{-- Validating keys exist --}}
                <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0 dark:border-slate-800"
                    wire:key="log-{{ $activity['id'] }}">
                    <div class="flex items-center space-x-3">
                        <span class="font-mono text-xs text-gray-400">{{ $activity['time'] }}</span>

                        <span
                            class="{{ isset($activity['aktiflik_kodu']) && $activity['aktiflik_kodu'] >= 400 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }} rounded px-2 py-0.5 text-xs font-medium">
                            {{ $activity['aktiflik_kodu'] ?? '200' }}
                        </span>

                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $activity['endpoint'] }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-3">
                        <span
                            class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-slate-800 dark:text-slate-200">
                            {{ $activity['provider'] }}
                        </span>

                        <span class="w-12 text-right text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity['latency'] }}ms
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
