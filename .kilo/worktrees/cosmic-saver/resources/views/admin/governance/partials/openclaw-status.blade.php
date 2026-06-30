<div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-gray-200 px-4 py-3 dark:border-slate-800">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">OpenClaw (Agent Governance)</h3>
    </div>
    <div class="p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Status Strip --}}
            <div class="col-span-full mb-2">
                <div class="flex items-center justify-between rounded-md px-3 py-2 {{ $health['openclaw']['enabled'] ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' }}">
                    <span class="text-xs font-bold uppercase tracking-wider">Gate Status</span>
                    <span class="text-sm font-medium">{{ $health['openclaw']['enabled'] ? 'ENABLED' : 'DISABLED' }}</span>
                </div>
            </div>

            {{-- 24h Summary --}}
            <div class="rounded-md border border-gray-100 p-3 dark:border-slate-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Requests (24h)</p>
                <h4 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $health['openclaw']['stats_24h']['total_requests'] }}</h4>
            </div>

            <div class="rounded-md border border-gray-100 p-3 dark:border-slate-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">Blocked / Violation</p>
                <h4 class="mt-1 text-lg font-bold {{ $health['openclaw']['stats_24h']['violation_count'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                    {{ $health['openclaw']['stats_24h']['blocked_count'] }} / {{ $health['openclaw']['stats_24h']['violation_count'] }}
                </h4>
            </div>

            {{-- Excluded Registry --}}
            <div class="col-span-full mt-2 rounded-md border border-gray-100 p-3 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Excluded Services Registry</p>
                        <h4 class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $health['openclaw']['excluded_count'] }} Services
                        </h4>
                    </div>
                    @if($health['openclaw']['stale_excluded_count'] > 0)
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            {{ $health['openclaw']['stale_excluded_count'] }} Overdue
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Registry Healthy
                        </span>
                    @endif
                </div>
            </div>

            {{-- Configuration --}}
            <div class="col-span-full">
                <div class="flex items-center gap-2 text-[11px] text-gray-400">
                    <span>Mode: {{ $health['openclaw']['proposal_only'] ? 'Proposal-Only' : 'Execute' }}</span>
                    <span>•</span>
                    <span>Review: 2026-07-01</span>
                </div>
            </div>
        </div>
    </div>
</div>
