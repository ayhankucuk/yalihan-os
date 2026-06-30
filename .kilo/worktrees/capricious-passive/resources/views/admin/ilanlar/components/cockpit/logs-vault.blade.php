{{-- 📜 Cockpit Logs & Vault (History/Private Data) --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Price History Log --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Fiyat Geçmişi</h3>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Son 10 Değişiklik</span>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                @forelse($ilan->fiyatGecmisi as $log)
                    <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg relative overflow-hidden dark:border-slate-700">
                        <div class="w-1.5 h-full absolute left-0 top-0 bg-{{ $log->old_price > $log->new_price ? 'green' : 'red' }}-500"></div>

                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white tabular-nums dark:text-slate-100">
                                    {{ number_format($log->new_price) }} ₺
                                </span>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at?->format('d M, H:i') ?? '-' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ number_format($log->old_price) }} ₺'den değişti • Sebep: {{ $log->change_reason ?: 'Manuel Düzenleme' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <span class="text-sm font-medium">Fiyat değişikliği yok</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Keys & Private Notes --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden flex flex-col dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Güvenli Bilgiler</h3>
        </div>

        <div class="p-6 space-y-6 flex-1">
            {{-- Key Management --}}
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg text-red-600 dark:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    </div>
                    <span class="text-xs font-semibold text-red-600 dark:text-red-400">Anahtar Bilgileri</span>
                </div>

                @if($ilan->anahtar_teslim_yeri)
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-900 dark:text-white dark:text-slate-100">Konum: {{ $ilan->anahtar_teslim_yeri }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded border border-red-200 dark:border-red-800 whitespace-nowrap">AKTİF</span>
                    </div>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">Merkezi kasada kayıtlı anahtar yok.</p>
                @endif
            </div>

            {{-- Private Comments --}}
            <div class="flex-1">
                <span class="text-xs font-semibold text-gray-700 dark:text-slate-200 block mb-2 dark:text-slate-300">Özel Notlar (Sadece Admin)</span>
                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 h-full min-h-[120px] dark:border-slate-700">
                    <p class="text-xs text-gray-700 dark:text-slate-200 leading-relaxed italic dark:text-slate-300">
                        {{ $ilan->owner_private_notes ?: 'Bu ilan için özel not kaydedilmemiş.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
