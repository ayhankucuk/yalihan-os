{{-- Cockpit Vitals Strip --}}
<div class="sticky top-0 z-40 bg-white dark:bg-slate-900 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 px-6 py-3 shadow-sm transition-all duration-300 dark:shadow-none dark:border-slate-700">
    <div class="max-w-[1700px] mx-auto flex items-center justify-between gap-6">

        {{-- 1. Identity Segment --}}
        <div class="flex items-center gap-4 min-w-0">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Referans</span>
                    <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-medium rounded border border-blue-200 dark:border-blue-800">Canlı</span>
                </div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white truncate leading-none mt-1 dark:text-slate-100">
                    {{ $ilan->kisa_referans }}
                </h1>
            </div>

            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>

            <div class="hidden md:flex flex-col">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">İlan Başlığı</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[200px] dark:text-slate-100">
                    {{ $ilan->baslik }}
                </span>
            </div>
        </div>

        {{-- 2. Telemetry Gauges --}}
        <div class="flex items-center gap-8 flex-1 justify-center">
            {{-- Price Gauge --}}
            <div class="flex flex-col items-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fiyat</span>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-green-600 dark:text-green-400 tabular-nums">
                        {{ number_format($ilan->fiyat) }}
                    </span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $ilan->para_birimi }}</span>
                </div>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

            {{-- Performance Gauge --}}
            <div class="flex flex-col items-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Görüntülenme</span>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                        {{ number_format($ilan->goruntulenme ?? 0) }}
                    </span>
                    <div class="flex flex-col">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">kez</span>
                    </div>
                </div>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

            {{-- Quality Gauge --}}
            <div class="flex flex-col items-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Veri Oranı</span>
                <div class="flex items-center gap-2">
                    @php
                        $totalFields = 20;
                        $filledFields = ($ilan->baslik ? 1 : 0) + ($ilan->aciklama ? 1 : 0) + ($ilan->fiyat ? 1 : 0) + ($ilan->il_id ? 1 : 0) + ($ilan->ilce_id ? 1 : 0);
                        $density = round(($filledFields / $totalFields) * 100);
                    @endphp
                    <span class="text-xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">{{ $density }}%</span>
                    <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500" style="width: {{ $density }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Action Deck --}}
        <div class="flex items-center gap-2">
            @if($ilan->drive_folder_name)
                <button @click="copyToClipboard('{{ $ilan->drive_folder_name }}', 'Drive ID Copied')"
                        class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-lg transition-all border border-slate-700"
                        title="Copy Drive ID">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </button>
            @endif

            <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
               class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-all shadow-md dark:shadow-none">
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
               </svg>
               Düzenle
            </a>

            @if($ilan->yayin_durumu === 'Aktif')
                <a href="{{ url('/ilan/' . $ilan->slug) }}" target="_blank"
                   class="p-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 hover:text-emerald-300 rounded-xl transition-all border border-slate-700"
                   title="Public Preview">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>
