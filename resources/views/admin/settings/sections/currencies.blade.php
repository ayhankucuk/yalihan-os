{{-- Currencies Management Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-green-600 dark:text-green-400 mb-2">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Para Birimi Yönetimi
        </h3>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sistem genelinde kabul edilen para birimlerini yönetin.</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Para Birimi
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Kod
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Sembol
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Durum
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Aksiyon
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                @foreach ($currencies as $curr)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $curr->code }}
                            @if ($curr->varsayilan_durumu)
                                <span class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-tighter text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    VARSAYILAN
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                            <span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-slate-800">
                                {{ strtoupper($curr->code) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-lg text-sm font-bold text-gray-600 dark:text-slate-400">
                            {{ $curr->symbol }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <form action="{{ route('admin.ayarlar.currencies.toggle') }}" method="POST">
                                @csrf
                                <input type="hidden" name="code" value="{{ $curr->code }}">
                                <input type="hidden" name="aktiflik_durumu" value="{{ $curr->aktiflik_durumu ? 0 : 1 }}">
                                <button type="submit"
                                    @if ($curr->varsayilan_durumu || $curr->code === 'TRY') disabled title="TRY veya varsayılan para birimi pasif edilemez" @endif
                                    class="@if ($curr->varsayilan_durumu || $curr->code === 'TRY') opacity-50 cursor-not-allowed @endif relative inline-flex cursor-pointer items-center">
                                    <div class="@if ($curr->aktiflik_durumu) bg-blue-600 @else bg-gray-300 dark:bg-slate-700 @endif relative h-5 w-10 rounded-full transition-colors">
                                        <div class="@if ($curr->aktiflik_durumu) left-[22px] @else left-[2px] @endif absolute top-[2px] h-4 w-4 rounded-full bg-white shadow-sm transition-all duration-300">
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            @if (!$curr->varsayilan_durumu)
                                <form action="{{ route('admin.ayarlar.currencies.set-default') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="code" value="{{ $curr->code }}">
                                    <button type="submit" class="text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        Varsayılan Yap
                                    </button>
                                </form>
                            @else
                                <span class="italic text-gray-400">Aktif</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
