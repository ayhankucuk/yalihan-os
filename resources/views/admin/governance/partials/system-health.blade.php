{{-- System Health Panel --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">System Health</h3>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-slate-700">
        {{-- Watcher --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Watcher</p>
                    @if ($health['watcher']['last_event_at'] ?? false)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last: {{ $health['watcher']['last_event_at'] }}</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => $health['watcher']['aktiflik_durumu'] ?? 'unknown'])
        </div>

        {{-- Pipeline --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Pipeline</p>
                    @if ($health['pipeline']['last_success_at'] ?? false)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last success: {{ $health['pipeline']['last_success_at'] }}</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">No recent success</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => ($health['pipeline']['last_success_at'] ?? null) ? 'healthy' : 'unknown'])
        </div>

        {{-- Sync --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Drive Sync</p>
                    @if ($health['sync']['last_sync_at'] ?? false)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last: {{ $health['sync']['last_sync_at'] }}</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">No sync data</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => ($health['sync']['last_sync_at'] ?? null) ? 'healthy' : 'unknown'])
        </div>

        {{-- Proposals --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Proposals</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $health['proposals']['pending_count'] ?? 0 }} pending · {{ $health['proposals']['applied_count'] ?? 0 }} applied
                    </p>
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => ($health['proposals']['pending_count'] ?? 0) > 0 ? 'pending' : 'healthy'])
        </div>

        {{-- Audit Log --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Audit Log</p>
                    @if ($health['audit_log']['size_bytes'] ?? 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($health['audit_log']['size_bytes']) }} bytes</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => $health['audit_log']['aktiflik_durumu'] ?? 'missing'])
        </div>

        {{-- Authority --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Authority</p>
                    @if ($health['authority']['version'] ?? false)
                        <p class="text-xs text-gray-500 dark:text-gray-400">v{{ $health['authority']['version'] }}</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => $health['authority']['aktiflik_durumu'] ?? 'missing'])
        </div>

        {{-- Decisions Log --}}
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-slate-700">
                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Decisions Log</p>
                    @if ($health['decisions_log']['size_bytes'] ?? 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($health['decisions_log']['size_bytes']) }} bytes</p>
                    @endif
                </div>
            </div>
            @include('admin.governance.partials._badge', ['value' => $health['decisions_log']['aktiflik_durumu'] ?? 'missing'])
        </div>
    </div>
</div>
