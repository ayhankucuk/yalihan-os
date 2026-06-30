{{-- Applied Proposal History --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Applied History</h3>
            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/50 dark:text-green-400">
                {{ count($history) }}
            </span>
        </div>
    </div>

    @if (count($history) > 0)
        <div class="max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="sticky top-0 bg-gray-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Engine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Applied</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($history as $h)
                        <tr class="{{ $h['parse_error'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="whitespace-nowrap px-4 py-2.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::limit($h['id'], 25) }}
                                @if ($h['parse_error'])
                                    <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs text-red-700 dark:bg-red-900/50 dark:text-red-400">invalid</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-900 dark:text-gray-200">{{ $h['target'] }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm">
                                @include('admin.governance.partials._badge', ['value' => $h['action']])
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400">{{ $h['engine'] }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $h['timestamp'] ? date('Y-m-d H:i', $h['timestamp']) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No applied proposals</p>
        </div>
    @endif
</div>
