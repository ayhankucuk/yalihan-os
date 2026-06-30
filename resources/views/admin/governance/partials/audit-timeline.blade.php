{{-- Audit Timeline --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Audit Timeline</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">Last {{ count($audit) }} entries</span>
        </div>
    </div>

    @if (count($audit) > 0)
        <div class="max-h-96 overflow-y-auto">
            <div class="divide-y divide-gray-100 dark:divide-slate-700/50">
                @foreach ($audit as $entry)
                    @php
                        $levelColors = [
                            'SUCCESS' => 'border-green-400 bg-green-50 dark:bg-green-900/10',
                            'ERROR'   => 'border-red-400 bg-red-50 dark:bg-red-900/10',
                            'PROPOSE' => 'border-amber-400 bg-amber-50 dark:bg-amber-900/10',
                            'DECIDE'  => 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/10',
                            'WATCH'   => 'border-blue-400 bg-blue-50 dark:bg-blue-900/10',
                            'ENGINE'  => 'border-purple-400 bg-purple-50 dark:bg-purple-900/10',
                            'INFO'    => 'border-gray-300 bg-gray-50 dark:bg-slate-800',
                            'RAW'     => 'border-gray-200 bg-gray-50 dark:bg-slate-800',
                        ];
                        $color = $levelColors[$entry['level']] ?? $levelColors['RAW'];
                    @endphp
                    <div class="flex items-start gap-3 border-l-2 px-6 py-2.5 {{ $color }}">
                        <div class="flex-shrink-0">
                            @if ($entry['timestamp'])
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $entry['timestamp'] }}</span>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @include('admin.governance.partials._badge', ['value' => strtolower($entry['level'])])
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 dark:text-gray-200 {{ $entry['raw'] ? 'italic' : '' }}">
                                {{ $entry['message'] }}
                            </p>
                            @if ($entry['proposal_id'])
                                <span class="mt-0.5 inline-block font-mono text-xs text-gray-400 dark:text-gray-500">
                                    #{{ $entry['proposal_id'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Audit log unavailable or empty</p>
        </div>
    @endif
</div>
