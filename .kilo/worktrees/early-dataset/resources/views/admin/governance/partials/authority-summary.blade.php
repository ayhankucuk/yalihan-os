{{-- Authority Summary --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Authority Snapshot</h3>
            @include('admin.governance.partials._badge', ['value' => $authority['aktiflik_durumu'] ?? 'unknown'])
        </div>
    </div>

    @if (($authority['aktiflik_durumu'] ?? 'missing') === 'healthy')
        <div class="divide-y divide-gray-100 px-6 dark:divide-slate-700/50">
            {{-- Version --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Version</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $authority['version'] ?? '—' }}</span>
            </div>

            {{-- Project --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Project</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $authority['project_name'] ?? '—' }}</span>
            </div>

            {{-- Enforcement Level --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Enforcement</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $authority['enforcement_level'] ?? '—' }}</span>
            </div>

            {{-- Key Count --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Top-Level Keys</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $authority['key_count'] ?? 0 }}</span>
            </div>

            {{-- Size --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Size</span>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ number_format($authority['size_bytes'] ?? 0) }} bytes</span>
            </div>

            {{-- Last Modified --}}
            <div class="flex items-center justify-between py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">Last Modified</span>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ $authority['last_modified'] ? date('Y-m-d H:i:s', $authority['last_modified']) : '—' }}
                </span>
            </div>

            {{-- Keys List --}}
            @if (!empty($authority['top_level_keys']))
                <div class="py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Structure</span>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($authority['top_level_keys'] as $key)
                            <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-slate-700 dark:text-gray-300">
                                {{ $key }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                Authority file {{ $authority['aktiflik_durumu'] ?? 'unavailable' }}
            </p>
            @if ($authority['path'] ?? false)
                <p class="mt-1 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $authority['path'] }}</p>
            @endif
        </div>
    @endif
</div>
