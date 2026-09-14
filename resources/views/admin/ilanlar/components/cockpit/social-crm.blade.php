{{-- 🏢 Cockpit Social/CRM — Segmented Luxury Tabs --}}
@php
    $sahip = $ilan->kisi ?? $ilan->ilanSahibi;
    $danisman = $ilan->danisman;
    $hasSahip = !empty($sahip) && (!empty($sahip->ad) || !empty($sahip->soyad) || !empty($sahip->ad_soyad));
    $sahipAd = $hasSahip ? ($sahip->ad_soyad ?? trim(($sahip->ad ?? '') . ' ' . ($sahip->soyad ?? ''))) : 'Atanmamış';
    $sahipTelefon = $hasSahip ? ($sahip->telefon ?? $sahip->phone ?? null) : null;
    $sahipEposta = $hasSahip ? ($sahip->eposta ?? $sahip->email ?? null) : null;
@endphp

<div x-data="{ crmTab: 'sahip' }" class="space-y-4">
    {{-- Segmented Tab Switcher --}}
    <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700/80">
        <button type="button"
            @click="crmTab = 'sahip'"
            :class="crmTab === 'sahip' 
                ? 'bg-white dark:bg-[#0A1628] text-slate-900 dark:text-[#C9A84C] shadow-sm font-bold border border-slate-200/80 dark:border-[#C9A84C]/30' 
                : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-slate-200 border-transparent'"
            class="flex-1 py-1.5 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5">
            <x-icon name="kullanici" class="w-3.5 h-3.5 text-[#C9A84C]" />
            <span>Mal Sahibi</span>
        </button>

        <button type="button"
            @click="crmTab = 'danisman'"
            :class="crmTab === 'danisman' 
                ? 'bg-white dark:bg-[#0A1628] text-slate-900 dark:text-[#C9A84C] shadow-sm font-bold border border-slate-200/80 dark:border-[#C9A84C]/30' 
                : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-slate-200 border-transparent'"
            class="flex-1 py-1.5 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5">
            <x-icon name="kullanicilar" class="w-3.5 h-3.5 text-[#C9A84C]" />
            <span>Danışman</span>
        </button>

        <button type="button"
            @click="crmTab = 'site'"
            :class="crmTab === 'site' 
                ? 'bg-white dark:bg-[#0A1628] text-slate-900 dark:text-[#C9A84C] shadow-sm font-bold border border-slate-200/80 dark:border-[#C9A84C]/30' 
                : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-slate-200 border-transparent'"
            class="flex-1 py-1.5 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5">
            <x-icon name="bina" class="w-3.5 h-3.5 text-[#C9A84C]" />
            <span>Site / İdari</span>
        </button>
    </div>

    {{-- TAB 1: Mal Sahibi --}}
    <div x-show="crmTab === 'sahip'" x-transition class="space-y-4 pt-1">
        @if($hasSahip)
            <div class="flex items-start gap-4 p-4 bg-gray-50/70 dark:bg-slate-800/50 rounded-xl border border-gray-200/80 dark:border-slate-700/60">
                <div class="w-12 h-12 rounded-xl bg-[#0A1628] border border-[#C9A84C]/40 flex items-center justify-center text-sm font-bold text-[#C9A84C] shrink-0 shadow-sm">
                    {{ mb_substr($sahip->ad ?? $sahipAd, 0, 1) }}{{ mb_substr($sahip->soyad ?? '', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-[#C9A84C] uppercase tracking-wider">Kayıtlı Mal Sahibi</span>
                        <span class="px-1.5 py-0.2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold rounded">Doğrulanmış</span>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5">{{ $sahipAd }}</h4>
                    
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @if($sahipTelefon)
                            <a href="tel:{{ $sahipTelefon }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-slate-900 hover:bg-[#C9A84C]/10 text-xs font-bold text-gray-900 dark:text-white hover:text-[#C9A84C] rounded-lg border border-slate-200 dark:border-slate-700 transition-all shadow-sm">
                                <x-icon name="telefon" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ $sahipTelefon }}</span>
                            </a>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sahipTelefon) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-lg border border-emerald-200 dark:border-emerald-800 transition-all shadow-sm">
                                <span>WhatsApp</span>
                            </a>
                        @endif
                        @if($sahipEposta)
                            <a href="mailto:{{ $sahipEposta }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-slate-900 hover:bg-[#C9A84C]/10 text-xs font-bold text-gray-900 dark:text-white hover:text-[#C9A84C] rounded-lg border border-slate-200 dark:border-slate-700 transition-all shadow-sm">
                                <x-icon name="eposta" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                <span>E-posta</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="p-5 text-center bg-gray-50/60 dark:bg-slate-800/40 rounded-xl border border-dashed border-gray-200 dark:border-slate-800">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 mb-2">
                    <x-icon name="kullanici" class="w-5 h-5 text-slate-400" />
                </div>
                <h5 class="text-xs font-bold text-gray-900 dark:text-white">Mal Sahibi Henüz Atanmamış</h5>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Bu ilanın mal sahibi veya müşteri kaydı ilişkilendirilmemiş.</p>
                <div class="mt-3">
                    <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}#tab-crm" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0A1628] hover:bg-[#112240] text-[#C9A84C] border border-[#C9A84C]/40 text-xs font-bold rounded-lg transition-all shadow-sm">
                        <x-icon name="ekle" class="w-3.5 h-3.5" />
                        <span>Kişi Ata / Düzenle</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- TAB 2: Danışman --}}
    <div x-show="crmTab === 'danisman'" x-transition class="space-y-4 pt-1">
        <div class="flex items-start gap-4 p-4 bg-gray-50/70 dark:bg-slate-800/50 rounded-xl border border-gray-200/80 dark:border-slate-700/60">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0A1628] to-[#112240] border border-[#C9A84C]/50 flex items-center justify-center text-sm font-bold text-[#C9A84C] shrink-0 shadow-sm">
                {{ mb_substr($danisman->name ?? 'YK', 0, 2) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#C9A84C] uppercase tracking-wider">Portföy Temsilcisi</span>
                    <span class="px-1.5 py-0.2 bg-[#C9A84C]/15 text-[#C9A84C] text-[10px] font-bold rounded border border-[#C9A84C]/30">Yetkili</span>
                </div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5">{{ $danisman->name ?? 'Merkez Ofis Danışmanı' }}</h4>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Yalıhan Emlak Bodrum</p>

                <div class="mt-2.5 flex flex-wrap gap-2">
                    @if(!empty($danisman->phone))
                        <a href="tel:{{ $danisman->phone }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-slate-900 text-xs font-bold text-gray-900 dark:text-white rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                            <x-icon name="telefon" class="w-3.5 h-3.5 text-emerald-600" />
                            <span>{{ $danisman->phone }}</span>
                        </a>
                    @endif
                    @if(!empty($danisman->email))
                        <a href="mailto:{{ $danisman->email }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-slate-900 text-xs font-bold text-gray-900 dark:text-white rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                            <x-icon name="eposta" class="w-3.5 h-3.5 text-blue-600" />
                            <span>{{ $danisman->email }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 3: Site & İdari --}}
    <div x-show="crmTab === 'site'" x-transition class="space-y-4 pt-1">
        @if($ilan->site_id && $ilan->site)
            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-xl">
                <div>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Kayıtlı Site / Kompleks</span>
                    <h5 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $ilan->site->ad }}</h5>
                </div>
                <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-emerald-600 dark:text-emerald-400">
                    <x-icon name="bina" class="w-5 h-5" />
                </div>
            </div>
        @else
            <div class="p-4 bg-gray-50/70 dark:bg-slate-800/50 rounded-xl border border-gray-200/80 dark:border-slate-700/60 flex items-center gap-3">
                <div class="p-2.5 bg-slate-200 dark:bg-slate-700 rounded-xl text-slate-600 dark:text-slate-300 shrink-0">
                    <x-icon name="ev" class="w-5 h-5 text-[#C9A84C]" />
                </div>
                <div>
                    <h5 class="text-xs font-bold text-gray-900 dark:text-white">Müstakil / Bağımsız Parsel</h5>
                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Bu gayrimenkul site veya tesis dışı bağımsız parsel statüsündedir.</p>
                </div>
            </div>
        @endif
    </div>
</div>
