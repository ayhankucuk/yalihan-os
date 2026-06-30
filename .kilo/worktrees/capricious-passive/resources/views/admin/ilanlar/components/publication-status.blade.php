{{-- Yayın Durumu Ayarları (Edit Sayfası İçin - Context7 Compliant) --}}
<div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
        <span class="bg-cyan-100 dark:bg-cyan-900 text-cyan-600 dark:text-cyan-400 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
            <i class="fas fa-broadcast-tower"></i>
        </span>
        📢 Gelişmiş Yayın Ayarları
    </h2>

    <div x-data="publicationManager()" class="space-y-6">
        {{-- İlan Durumu Özeti --}}
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-xl"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Mevcut Durum</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Durum: <span class="font-semibold">{{ $ilan->yayin_durumu ?? 'Taslak' }}</span> |
                            Öncelik: <span class="font-semibold">{{ $ilan->oncelik ?? 'normal' }}</span>
                        </p>
                    </div>
                </div>
                @if(isset($ilan->yayin_baslangic) && $ilan->yayin_baslangic)
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Yayın: {{ \Carbon\Carbon::parse($ilan->yayin_baslangic)->format('d.m.Y H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Öncelik Seviyesi --}}
        <div class="mb-6">
            <label for="oncelik" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                <span class="block text-sm font-medium text-gray-900 dark:text-white mb-2-text dark:text-slate-100">Öncelik Seviyesi</span>
                <span class="text-xs text-gray-500 ml-2">(İlan sıralamasında öncelik)</span>
            </label>
            <select  name="oncelik" id="oncelik" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                <option value="dusuk" {{ (old('oncelik', $ilan->oncelik ?? 'normal') == 'dusuk') ? 'selected' : '' }}>⬇️ Düşük</option>
                <option value="normal" {{ (old('oncelik', $ilan->oncelik ?? 'normal') == 'normal') ? 'selected' : '' }}>⭐ Normal</option>
                <option value="yuksek" {{ (old('oncelik', $ilan->oncelik ?? 'normal') == 'yuksek') ? 'selected' : '' }}>⭐⭐ Yüksek</option>
                <option value="acil" {{ (old('oncelik', $ilan->oncelik ?? 'normal') == 'acil') ? 'selected' : '' }}>🔥 Acil</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Yüksek öncelikli ilanlar arama sonuçlarında üstte görünür
            </p>
        </div>

        {{-- Yayın Tarihleri --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="mb-6">
                <label for="yayin_baslangic" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    <span class="block text-sm font-medium text-gray-900 dark:text-white mb-2-text dark:text-slate-100">Yayın Başlangıç Tarihi</span>
                    <i class="fas fa-calendar-alt text-gray-400 ml-1"></i>
                </label>
                <input type="datetime-local" name="yayin_baslangic" id="yayin_baslangic"
                    value="{{ old('yayin_baslangic', isset($ilan->yayin_baslangic) ? \Carbon\Carbon::parse($ilan->yayin_baslangic)->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Boş bırakılırsa hemen yayınlanır
                </p>
            </div>

            <div class="mb-6">
                <label for="yayin_bitis" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    <span class="block text-sm font-medium text-gray-900 dark:text-white mb-2-text dark:text-slate-100">Yayın Bitiş Tarihi</span>
                    <i class="fas fa-calendar-times text-gray-400 ml-1"></i>
                </label>
                <input type="datetime-local" name="yayin_bitis" id="yayin_bitis"
                    value="{{ old('yayin_bitis', isset($ilan->yayin_bitis) ? \Carbon\Carbon::parse($ilan->yayin_bitis)->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Boş bırakılırsa süresiz yayında kalır
                </p>
            </div>
        </div>

        {{-- Otomatik Yayın Yönetimi --}}
        <div class="bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-slate-200 mb-4 flex items-center">
                <i class="fas fa-robot mr-2 text-cyan-600"></i>
                Otomatik Yayın Yönetimi
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Otomatik Aktif Etme (Context7: auto_publish_enabled) --}}
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" name="auto_publish_enabled" id="auto_publish_enabled"
                            {{ old('auto_publish_enabled', $ilan->auto_publish_enabled ?? false) ? 'checked' : '' }}
                            class="mt-1 rounded focus:ring-cyan-500 text-cyan-600">
                        <div>
                            <label for="auto_publish_enabled" class="text-sm font-medium text-gray-900 dark:text-white cursor-pointer dark:text-slate-100">
                                Otomatik Aktif Etme
                            </label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                İlan onaylandığında otomatik olarak yayınlanır
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Otomatik Pasif Etme (Context7: auto_disabled - FIXED) --}}
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" name="auto_disabled" id="auto_disabled"
                            {{ old('auto_disabled', $ilan->auto_disabled ?? false) ? 'checked' : '' }}
                            class="mt-1 rounded focus:ring-cyan-500 text-cyan-600">
                        <div>
                            <label for="auto_disabled" class="text-sm font-medium text-gray-900 dark:text-white cursor-pointer dark:text-slate-100">
                                Otomatik Pasif Etme
                            </label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Bitiş tarihinde otomatik olarak pasif olur
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Yayın İstatistikleri (Sadece Edit'te) --}}
        @if(isset($ilan->id))
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:border-slate-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center dark:text-slate-100">
                <i class="fas fa-chart-line mr-2 text-purple-500"></i>
                Yayın İstatistikleri
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $ilan->goruntulenme ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Görüntülenme</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600">{{ $ilan->favori_sayisi ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Favori</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-orange-600">{{ $ilan->talep_sayisi ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Talep</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-600">
                        @if($ilan->created_at)
                            {{ \Carbon\Carbon::parse($ilan->created_at)->diffInDays(now()) }}
                        @else
                            0
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">Gün Önce</p>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- Context7 Note:
    - auto_publish_enabled (NOT auto_status) ✅ FIXED
    - auto_disabled (NOT otomatik_pasif) ✅ FIXED
    - This component now ONLY for EDIT page
    - CREATE page uses inline Section 9 with accordion
--}}
