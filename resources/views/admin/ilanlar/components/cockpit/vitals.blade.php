{{-- Cockpit Vitals Strip --}}
<div class="sticky top-0 z-40 bg-white/95 dark:bg-[#0A1628]/95 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 px-6 py-3 shadow-sm transition-all duration-300">
    <div class="max-w-[1700px] mx-auto flex items-center justify-between gap-6">

        {{-- 1. Identity Segment --}}
        <div class="flex items-center gap-4 min-w-0">
            <a href="{{ route('admin.ilanlar.index') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-[#C9A84C] bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors shrink-0"
               title="İlanlar Listesine Dön">
                <x-icon name="sol-ok" class="w-3.5 h-3.5" />
                <span>İlanlar</span>
            </a>

            <div class="h-8 w-px bg-gray-200 dark:bg-slate-700 hidden sm:block"></div>

            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Referans</span>
                    @php
                        $durumVal = is_object($ilan->yayin_durumu) ? $ilan->yayin_durumu->value : (string)$ilan->yayin_durumu;
                        $durumVal = strtolower($durumVal);
                    @endphp
                    @if(in_array($durumVal, ['yayinda', 'aktif', 'active']))
                        <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-bold rounded-full border border-emerald-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Yayında
                        </span>
                    @elseif(in_array($durumVal, ['beklemede', 'pending']))
                        <span class="px-2 py-0.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-bold rounded-full border border-blue-500/30">
                            Beklemede
                        </span>
                    @elseif(in_array($durumVal, ['pasif', 'passive']))
                        <span class="px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold rounded-full border border-amber-500/30">
                            Pasif
                        </span>
                    @elseif(in_array($durumVal, ['arsiv', 'archived']))
                        <span class="px-2 py-0.5 bg-slate-500/10 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-full border border-slate-500/30">
                            Arşiv
                        </span>
                    @else
                        <span class="px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold rounded-full border border-amber-500/30">
                            Taslak
                        </span>
                    @endif
                </div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white truncate leading-none mt-1">
                    {{ $ilan->referans_no ?: ($ilan->kisa_referans ?: '#' . $ilan->id) }}
                </h1>
            </div>

            <div class="h-8 w-px bg-gray-200 dark:bg-slate-700 hidden md:block"></div>

            <div class="hidden md:flex flex-col">
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400">İlan Başlığı</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate max-w-[240px]">
                    {{ $ilan->baslik }}
                </span>
            </div>
        </div>

        {{-- 2. Telemetry Gauges --}}
        <div class="flex items-center gap-8 flex-1 justify-center">
            {{-- Price Gauge --}}
            <div class="flex flex-col items-center">
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Fiyat</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                        {{ number_format($ilan->fiyat) }}
                    </span>
                    <span class="text-xs font-bold text-gray-500 dark:text-slate-400">{{ $ilan->para_birimi }}</span>
                </div>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-slate-700 hidden sm:block"></div>

            {{-- Performance Gauge --}}
            <div class="hidden sm:flex flex-col items-center">
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Görüntülenme</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-bold text-[#C9A84C] tabular-nums">
                        {{ number_format($ilan->goruntulenme ?? 0) }}
                    </span>
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">kez</span>
                </div>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-slate-700 hidden md:block"></div>

            {{-- Quality Gauge --}}
            <div class="hidden md:flex flex-col items-center">
                <span class="text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Veri Doluluğu</span>
                <div class="flex items-center gap-2">
                    @php
                        $totalFields = 20;
                        $filledFields = ($ilan->baslik ? 1 : 0) + ($ilan->aciklama ? 1 : 0) + ($ilan->fiyat ? 1 : 0) + ($ilan->il_id ? 1 : 0) + ($ilan->ilce_id ? 1 : 0) + ($ilan->fotograflar->count() > 0 ? 3 : 0);
                        $density = min(100, round(($filledFields / 8) * 100));
                    @endphp
                    <span class="text-xl font-bold text-amber-500 dark:text-amber-400 tabular-nums">{{ $density }}%</span>
                    <div class="w-12 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-[#C9A84C]" style="width: {{ $density }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Action Deck --}}
        <div class="flex items-center gap-2">
            @if($ilan->drive_folder_name)
                <button @click="copyToClipboard('{{ $ilan->drive_folder_name }}', 'Drive ID Kopyalandı')"
                        class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-[#C9A84C] rounded-lg transition-all border border-slate-200 dark:border-slate-700"
                        title="Drive ID Kopyala">
                    <x-icon name="kopyala" class="w-4 h-4" />
                </button>
            @endif

            @if(in_array($durumVal, ['yayinda', 'aktif', 'active']))
                <button @click="togglePublish()" :disabled="processing"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold rounded-lg border border-amber-500/30 transition-all">
                    <x-icon name="kalkan" class="w-3.5 h-3.5" />
                    <span>Pasife Al</span>
                </button>
            @else
                <button @click="publishViaGate()" :disabled="processing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-all shadow-sm shadow-emerald-900/20">
                    <x-icon name="onay" class="w-3.5 h-3.5" />
                    <span>Yayına Al</span>
                </button>
            @endif

            <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#C9A84C] hover:bg-[#b8953d] text-[#0A1628] text-xs font-bold rounded-lg transition-all shadow-sm hover:shadow">
               <x-icon name="duzenle" class="w-3.5 h-3.5 text-[#0A1628]" />
               Düzenle
            </a>

            @if(in_array($durumVal, ['yayinda', 'aktif', 'active']) && $ilan->slug)
                <a href="{{ url('/ilan/' . $ilan->slug) }}" target="_blank"
                   class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-emerald-600 dark:text-emerald-400 rounded-lg transition-all border border-slate-200 dark:border-slate-700"
                   title="Sitede Önizle">
                    <x-icon name="dis-baglanti" class="w-4 h-4" />
                </a>
            @endif
        </div>
    </div>
</div>

