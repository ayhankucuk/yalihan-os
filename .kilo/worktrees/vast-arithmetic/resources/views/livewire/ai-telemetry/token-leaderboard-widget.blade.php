{{-- Token Leaderboard Widget (Widget 5) --}}
<div
    class="h-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
            🏆 En Çok Tüketenler
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Son 7 Gün</span>
    </div>

    {{-- Loading State --}}
    @if ($loading)
        <div class="animate-pulse space-y-3">
            @for ($i = 0; $i < 5; $i++)
                <div class="h-10 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
            @endfor
        </div>

        {{-- Empty State --}}
    @elseif(empty($data))
        <div class="py-12 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Veri bulunamadı</p>
        </div>

        {{-- Data Table --}}
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th
                            class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Endpoint</th>
                        <th
                            class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Provider</th>
                        <th
                            class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            İstek</th>
                        <th
                            class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Token</th>
                        <th
                            class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Maliyet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($data as $row)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td
                                class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900 dark:text-slate-100">
                                {{ Str::limit($row['endpoint_adi'], 20) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                <span
                                    class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $row['provider_adi'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($row['request_count']) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-3 py-2 text-right font-mono text-sm text-gray-900 dark:text-slate-100">
                                {{ number_format($row['total_tokens']) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-gray-500 dark:text-gray-400">
                                ${{ number_format($row['total_cost'], 4) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
