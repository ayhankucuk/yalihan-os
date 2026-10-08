{{-- Event Vocabulary — all Hermes event types with usage stats --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Olay Sözlüğü</h3>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Tüm Hermes olay türleri ve çalışma istatistikleri</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Olay Adı</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Açıklama</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Toplam</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Başarısız</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Son Çalışma</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Handler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($vocabulary as $eventName => $info)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40 transition-colors">
                        <td class="px-4 py-3">
                            <code class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $eventName }}</code>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $info['description'] }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $info['total'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($info['failed'] > 0)
                                <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ $info['failed'] }}</span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $info['last_at'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($info['has_handlers'])
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">✓</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-slate-700 dark:text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Henüz kayıtlı olay yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
