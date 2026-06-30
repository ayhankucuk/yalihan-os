{{-- Governance Runtime Strip — SAB Pipeline Status (Reusable across governance pages) --}}
<div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/50 mb-6">
    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mr-1">SAB Pipeline</span>

    {{-- Watcher --}}
    <div class="flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <span class="text-xs text-gray-600 dark:text-gray-300">Watcher</span>
        @include('admin.governance.partials._badge', ['value' => $runtimeStrip['watcher_durumu'] ?? 'unknown'])
    </div>

    <span class="text-gray-300 dark:text-gray-600">·</span>

    {{-- Pending --}}
    <div class="flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="text-xs text-gray-600 dark:text-gray-300">Bekleyen</span>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ ($runtimeStrip['pending_count'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' }}">
            {{ $runtimeStrip['pending_count'] ?? 0 }}
        </span>
    </div>

    <span class="text-gray-300 dark:text-gray-600">·</span>

    {{-- Today Applied --}}
    <div class="flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span class="text-xs text-gray-600 dark:text-gray-300">Bugün</span>
        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-gray-400">
            {{ $runtimeStrip['applied_today'] ?? 0 }}
        </span>
    </div>

    <span class="text-gray-300 dark:text-gray-600">·</span>

    {{-- Authority --}}
    <div class="flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span class="text-xs text-gray-600 dark:text-gray-300">Authority</span>
        @include('admin.governance.partials._badge', ['value' => $runtimeStrip['authority_durumu'] ?? 'unknown'])
    </div>

    <span class="text-gray-300 dark:text-gray-600">·</span>

    {{-- Last Sync --}}
    <div class="flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
        </svg>
        <span class="text-xs text-gray-600 dark:text-gray-300">Sync</span>
        @if ($runtimeStrip['last_sync'] ?? false)
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $runtimeStrip['last_sync'] }}</span>
        @else
            @include('admin.governance.partials._badge', ['value' => 'unknown'])
        @endif
    </div>
</div>
