{{-- Languages Management Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400 mb-2">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Dil Yönetimi
        </h3>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sistem genelinde aktif olan dilleri ve varsayılan dili yönetin.</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Dil
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Kod
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        RTL
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
                @foreach ($languages as $lang)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $lang->name }}
                            @if ($lang->varsayilan_durumu)
                                <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-tighter text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    VARSAYILAN
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                            <span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-slate-800">
                                {{ strtoupper($lang->code) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                            {{ $lang->is_rtl ? 'Evet' : 'Hayır' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <form action="{{ route('admin.ayarlar.languages.toggle') }}" method="POST">
                                @csrf
                                <input type="hidden" name="code" value="{{ $lang->code }}">
                                <input type="hidden" name="aktiflik_durumu" value="{{ $lang->aktiflik_durumu ? 0 : 1 }}">
                                <button type="submit"
                                    @if ($lang->varsayilan_durumu) disabled title="Varsayılan dil pasif edilemez" @endif
                                    class="@if ($lang->varsayilan_durumu) opacity-50 cursor-not-allowed @endif relative inline-flex cursor-pointer items-center">
                                    <div class="@if ($lang->aktiflik_durumu) bg-blue-600 @else bg-gray-300 dark:bg-slate-700 @endif relative h-5 w-10 rounded-full transition-colors">
                                        <div class="@if ($lang->aktiflik_durumu) left-[22px] @else left-[2px] @endif absolute top-[2px] h-4 w-4 rounded-full bg-white shadow-sm transition-all duration-300">
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            @if (!$lang->varsayilan_durumu)
                                <form action="{{ route('admin.ayarlar.languages.set-default') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="code" value="{{ $lang->code }}">
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
