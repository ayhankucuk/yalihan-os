{{-- Pending Proposals Table --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Proposals</h3>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/50 dark:text-amber-400">
                {{ count($pending) }}
            </span>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.governance.dashboard') }}" class="mt-3 flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search..."
                   class="rounded-md border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 dark:placeholder-gray-400">
            <select name="action" class="rounded-md border-gray-300 px-3 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200">
                <option value="">All Actions</option>
                @foreach (['append', 'update', 'merge', 'delete'] as $act)
                    <option value="{{ $act }}" {{ ($filters['action'] ?? '') === $act ? 'selected' : '' }}>{{ $act }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                Filter
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        @if (count($pending) > 0)
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Risk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Engine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($pending as $p)
                        <tr class="{{ $p['parse_error'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-gray-700 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::limit($p['id'], 30) }}
                                @if ($p['parse_error'])
                                    <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs text-red-700 dark:bg-red-900/50 dark:text-red-400">invalid</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-200">{{ $p['target'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @include('admin.governance.partials._badge', ['value' => $p['action']])
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @include('admin.governance.partials._badge', ['value' => $p['risk']])
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $p['engine'] }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-600 dark:text-gray-400" title="{{ $p['reason'] }}">
                                {{ \Illuminate\Support\Str::limit($p['reason'], 50) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No pending proposals</p>
            </div>
        @endif
    </div>
</div>
