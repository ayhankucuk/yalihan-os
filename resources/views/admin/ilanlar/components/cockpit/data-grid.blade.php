{{-- 📦 Cockpit Data Grid (Technical Features Matrix) --}}
@php
    $categories = $ilan->ozellikler->groupBy('category');
    
    // Core structural & zoning fields
    $coreFields = [];
    if (!empty($ilan->alan_m2) || !empty($ilan->m2_brut)) {
        $coreFields['Alan'] = number_format($ilan->alan_m2 ?: $ilan->m2_brut, 0, ',', '.') . ' m²';
    }
    if (!empty($ilan->m2_net)) {
        $coreFields['Net Alan'] = number_format($ilan->m2_net, 0, ',', '.') . ' m²';
    }
    if (!empty($ilan->imar_statusu) || !empty($ilan->imar_durumu)) {
        $coreFields['İmar Durumu'] = $ilan->imar_statusu ?: $ilan->imar_durumu;
    }
    if (!empty($ilan->kaks)) {
        $coreFields['Emsal (KAKS)'] = (string) $ilan->kaks;
    }
    if (!empty($ilan->taks)) {
        $coreFields['Taban Alanı (TAKS)'] = (string) $ilan->taks;
    }
    if (!empty($ilan->ada_no)) {
        $coreFields['Ada No'] = (string) $ilan->ada_no;
    }
    if (!empty($ilan->parsel_no)) {
        $coreFields['Parsel No'] = (string) $ilan->parsel_no;
    }
    if (!empty($ilan->tapu_durumu)) {
        $coreFields['Tapu Durumu'] = (string) $ilan->tapu_durumu;
    }
    if (!empty($ilan->oda_sayisi)) {
        $coreFields['Oda Sayısı'] = (string) $ilan->oda_sayisi;
    }
    if (!empty($ilan->bina_yasi)) {
        $coreFields['Bina Yaşı'] = (string) $ilan->bina_yasi;
    }
    if (!empty($ilan->kat_sayisi)) {
        $coreFields['Kat Sayısı'] = (string) $ilan->kat_sayisi;
    }
    if (!empty($ilan->isinma_tipi)) {
        $coreFields['Isınma Tipi'] = (string) $ilan->isinma_tipi;
    }
    if (isset($ilan->altyapi_elektrik) && $ilan->altyapi_elektrik) {
        $coreFields['Elektrik Altyapısı'] = 'Var';
    }
    if (isset($ilan->altyapi_su) && $ilan->altyapi_su) {
        $coreFields['Su Altyapısı'] = 'Var';
    }
    if (isset($ilan->altyapi_dogalgaz) && $ilan->altyapi_dogalgaz) {
        $coreFields['Doğalgaz Altyapısı'] = 'Var';
    }
    if (isset($ilan->yola_cephesi) && ($ilan->yola_cephesi == '1' || $ilan->yola_cephesi === 1)) {
        $coreFields['Yola Cephe'] = 'Var';
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    {{-- 1. Temel Yapısal & İmar Özellikleri --}}
    @if(count($coreFields) > 0)
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm hover:border-[#C9A84C]/40 transition-all">
            <div class="px-4 py-3 bg-gray-50/70 dark:bg-slate-800/50 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <x-icon name="ev" class="w-4 h-4 text-[#C9A84C]" />
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">Temel & İmar Bilgileri</h4>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 bg-[#C9A84C]/15 text-[#C9A84C] rounded border border-[#C9A84C]/30">{{ count($coreFields) }} Parametre</span>
            </div>

            <div class="p-4 space-y-2.5">
                @foreach($coreFields as $label => $val)
                    <div class="flex items-center justify-between gap-4 py-1 border-b border-gray-100 dark:border-slate-800/60 last:border-none">
                        <span class="text-xs font-medium text-gray-600 dark:text-slate-400">{{ $label }}</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-slate-100 tabular-nums">
                            @if($val === 'Var')
                                <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded text-[11px] font-bold border border-emerald-200 dark:border-emerald-800">Var</span>
                            @else
                                {{ $val }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 2. Dinamik Kategori Özellikleri --}}
    @forelse($categories as $categoryName => $features)
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm hover:border-[#C9A84C]/40 transition-all">
            <div class="px-4 py-3 bg-gray-50/70 dark:bg-slate-800/50 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <x-icon name="liste" class="w-4 h-4 text-[#C9A84C]" />
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $categoryName ?: 'Detay Özellikler' }}</h4>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400">{{ $features->count() }} Özellik</span>
            </div>

            <div class="p-4 space-y-2.5">
                @foreach($features as $feature)
                    <div class="flex items-center justify-between gap-4 py-1 border-b border-gray-100 dark:border-slate-800/60 last:border-none">
                        <span class="text-xs font-medium text-gray-600 dark:text-slate-400 truncate">{{ $feature->name }}</span>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($feature->pivot->value === '1' || $feature->pivot->value === 'on')
                                <div class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[11px] font-bold rounded border border-emerald-200 dark:border-emerald-800">
                                    Var
                                </div>
                            @elseif($feature->pivot->value)
                                <span class="text-xs font-bold text-gray-900 dark:text-slate-100 tabular-nums">
                                    {{ $feature->pivot->value }}
                                </span>
                            @else
                                <span class="text-xs font-medium text-gray-400 dark:text-gray-600 italic">Yok</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        @if(count($coreFields) === 0)
            <div class="col-span-full py-16 flex flex-col items-center justify-center border-2 border-dashed border-gray-200 dark:border-slate-800 rounded-xl">
                <x-icon name="liste" class="w-10 h-10 text-gray-400 mb-3" />
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400">Bu ilan için detay özellik verisi bulunamadı.</p>
            </div>
        @endif
    @endforelse
</div>
