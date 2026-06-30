{{-- Overview Cards --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

    {{-- Pending Proposals --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ ($overview['pending_count'] ?? 0) > 0 ? 'bg-amber-100 dark:bg-amber-900/50' : 'bg-gray-100 dark:bg-slate-700' }}">
                    <svg class="h-5 w-5 {{ ($overview['pending_count'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                    <p class="text-2xl font-bold {{ ($overview['pending_count'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $overview['pending_count'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Applied Today --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/50">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Applied Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $overview['applied_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Last Pipeline Success --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Pipeline</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $overview['last_pipeline_success'] ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Last Sync --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50">
                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Sync</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $overview['last_sync'] ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Authority Status --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ ($overview['authority_durumu'] ?? '') === 'healthy' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-red-100 dark:bg-red-900/50' }}">
                    <svg class="h-5 w-5 {{ ($overview['authority_durumu'] ?? '') === 'healthy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Authority</p>
                    @include('admin.governance.partials._badge', ['value' => $overview['authority_durumu'] ?? 'unknown'])
                </div>
            </div>
        </div>
    </div>

    {{-- Watcher Status --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ ($overview['watcher_durumu'] ?? '') === 'running' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-gray-100 dark:bg-slate-700' }}">
                    <svg class="h-5 w-5 {{ ($overview['watcher_durumu'] ?? '') === 'running' ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Watcher</p>
                    @include('admin.governance.partials._badge', ['value' => $overview['watcher_durumu'] ?? 'unknown'])
                </div>
            </div>
        </div>
    </div>
</div>
