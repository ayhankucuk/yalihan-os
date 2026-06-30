{{-- 🎨 Section 2: Kategori Sistemi (Context7 Optimized) --}}
<div
    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 dark:shadow-none dark:border-slate-700">
    <!-- Section Header -->
    <div
        class="px-5 py-3 border-b border-gray-200 dark:border-gray-700
                bg-gradient-to-r from-gray-50 to-white
                dark:from-gray-800 dark:to-gray-800
                rounded-t-lg
                flex items-center gap-3 mb-4">
        <div
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-md font-semibold text-sm dark:shadow-none">
            2
        </div>
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Kategori Sistemi
            </h2>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">İlanınızın kategori ve yayın tipini seçin</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Ana Kategori - Enhanced --}}
        <div class="group">
            <label for="ana_kategori"
                class="text-sm font-medium text-gray-900 dark:text-white mb-1.5 flex items-center gap-2 dark:text-slate-100">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-semibold">
                    1
                </span>
                Ana Kategori
                <span class="text-red-500 font-semibold">*</span>
            </label>
            <div class="relative">
                <select name="ana_kategori_id" id="ana_kategori" required data-context7-field="ana_kategori_id"
                    onchange="loadAltKategoriler(this.value); safeDispatchCategoryChanged();"
                    @error('ana_kategori_id') aria-invalid="true" aria-describedby="ana_kategori_id-error" data-error="true" @enderror
                    class="w-full px-4 py-2.5 text-base
                           border border-gray-300 dark:border-gray-600
                           rounded-lg
                           bg-white dark:bg-gray-900
                           text-black dark:text-white
                           focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:focus:border-green-400
                           transition-all duration-200
                           hover:border-gray-400 dark:hover:border-gray-500
                           cursor-pointer
                           focus:shadow-md
                           appearance-none"
                    style="color-scheme: light dark;">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500">Kategori Seçin...</option>
                    @foreach ($anaKategoriler as $kategori)
                        <option value="{{ $kategori->id }}" data-slug="{{ $kategori->slug }}"
                            class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            {{ old('ana_kategori_id', $ilan->ana_kategori_id ?? null) == $kategori->id ? 'selected' : '' }}>
                            {{ $kategori->name }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-green-500 transition-colors"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            @error('ana_kategori_id')
                <div id="ana_kategori_id-error" role="alert" aria-live="assertive"
                    class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-4 py-2.5 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Alt Kategori - Enhanced --}}
        <div class="group" x-data="{ loading: false }">
            <label for="alt_kategori"
                class="text-sm font-medium text-gray-900 dark:text-white mb-1.5 flex items-center gap-2 dark:text-slate-100">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-semibold">
                    2
                </span>
                Alt Kategori
                <span class="text-red-500 font-semibold">*</span>
            </label>
            <div class="relative">
                <select name="alt_kategori_id" id="alt_kategori" required data-context7-field="alt_kategori_id"
                    data-default="{{ old('alt_kategori_id', $ilan->alt_kategori_id ?? null) }}"
                    onchange="loadYayinTipleri(this.value); safeDispatchCategoryChanged();"
                    @error('alt_kategori_id') aria-invalid="true" aria-describedby="alt_kategori_id-error" data-error="true" @enderror
                    class="w-full px-4 py-2.5 text-base
                           border border-gray-300 dark:border-gray-600
                           rounded-lg
                           bg-white dark:bg-gray-900
                           text-black dark:text-white
                           focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:focus:border-green-400
                           transition-all duration-200
                           hover:border-gray-400 dark:hover:border-gray-500
                           cursor-pointer
                           focus:shadow-md
                           disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60
                           appearance-none"
                    style="color-scheme: light dark;">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500">Önce ana kategori seçin...
                    </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-green-500 transition-colors"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            @error('alt_kategori_id')
                <div id="alt_kategori_id-error" role="alert" aria-live="assertive"
                    class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-4 py-2.5 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Yayın Tipi - Enhanced --}}
        <div class="group">
            <label for="yayin_tipi_id"
                class="text-sm font-medium text-gray-900 dark:text-white mb-1.5 flex items-center gap-2 dark:text-slate-100">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-semibold">
                    3
                </span>
                Yayın Tipi
                <span class="text-red-500 font-semibold">*</span>
            </label>
            <div class="relative">
                <select name="junction_id" id="junction_id" required data-context7-field="junction_id"
                    data-default="{{ old('junction_id', $ilan->junction_id ?? null) }}"
                    onchange="safeDispatchCategoryChanged(); IlanCreateCategories.loadTypeBasedFields();"
                    @error('yayin_tipi_id') aria-invalid="true" aria-describedby="yayin_tipi_id-error" data-error="true" @enderror
                    class="w-full px-4 py-2.5 text-base
                           border border-gray-300 dark:border-gray-600
                           rounded-lg
                           bg-white dark:bg-gray-900
                           text-black dark:text-white
                           focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:focus:border-green-400
                           transition-all duration-200
                           hover:border-gray-400 dark:hover:border-gray-500
                           cursor-pointer
                           focus:shadow-md
                           disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60
                           appearance-none"
                    style="color-scheme: light dark;">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500">Önce alt kategori seçin...
                    </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-green-500 transition-colors"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <p id="yayin-tip-hint" class="mt-2 text-xs text-orange-600 dark:text-orange-400 hidden">Yayın tipi
                    seçilmedi; bazı kategori alanları gösterilmeyebilir.</p>
            </div>
            @error('yayin_tipi_id')
                <div id="yayin_tipi_id-error" role="alert" aria-live="assertive"
                    class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-4 py-2.5 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    {{-- Category Flow Indicator - NEW! --}}
    <div class="mt-4 flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 rounded-full">
            <span class="w-2 h-2 rounded-full bg-green-500" id="ana-kategori-indicator"></span>
            <span>Ana Kategori</span>
        </div>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 rounded-full">
            <span class="w-2 h-2 rounded-full bg-gray-300" id="alt-kategori-indicator"></span>
            <span>Alt Kategori</span>
        </div>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 rounded-full">
            <span class="w-2 h-2 rounded-full bg-gray-300" id="yayin-tipi-indicator"></span>
            <span>Yayın Tipi</span>
        </div>
    </div>

    {{-- 🆕 Kategori Dinamik Alanlar (Context7 v3.4.0 - Kural #66) --}}
    @include('admin.ilanlar.partials.stable._kategori-dinamik-alanlar')
</div>

{{-- Kategori Cascade JavaScript - categories.js modülü kullanılacak --}}
{{-- Sadece inline event listener'lar için minimal kod --}}
<script>
    console.log('✅ Kategori sistemi - categories.js modülü kullanılıyor');

    // ✅ Debounce mekanizması - Aynı event'in tekrar dispatch edilmesini önler
    window._categoryChangedDebounceTimer = null;
    window._lastCategoryChangedDetail = null;
    window._lastDispatchedEventKey = null;

    window.safeDispatchCategoryChanged = function(force = false) {
        // Debounce: Son 300ms içinde aynı event dispatch edildiyse, yeni dispatch etme
        const kategoriSelect = document.getElementById('ana_kategori');
        const yayinSelect = document.getElementById('junction_id');

        if (!kategoriSelect) return;

        let slug = '';
        if (kategoriSelect && kategoriSelect.selectedIndex >= 0) {
            const opt = kategoriSelect.options[kategoriSelect.selectedIndex];
            slug = (opt && opt.getAttribute('data-slug')) || '';
        }
        const yayinId = yayinSelect ? yayinSelect.value : null;
        const kategoriId = kategoriSelect.value;

        // ✅ SAB: Yayın tipi slug'ını al
        let yayinTipiSlug = '';
        let yayinTipiName = '';
        if (yayinSelect && yayinSelect.selectedIndex >= 0 && yayinId) {
            const yayinOpt = yayinSelect.options[yayinSelect.selectedIndex];
            yayinTipiSlug = (yayinOpt && yayinOpt.getAttribute('data-slug')) || yayinOpt?.text?.toLowerCase() || '';
            yayinTipiName = yayinOpt?.text || '';
        }

        // ✅ SAB: Standardize edilmiş event formatı - Hem ID hem slug gönder
        const detail = {
            category: {
                id: kategoriId,
                slug: slug,
                parent_slug: slug,
                name: kategoriSelect.options[kategoriSelect.selectedIndex]?.text || ''
            },
            yayinTipi: {
                id: yayinId,
                slug: yayinTipiSlug,
                name: yayinTipiName
            },
            // ✅ Backward compatibility: Eski format desteği
            yayinTipiId: yayinId
        };

        // Duplicate kontrolü - Aynı kategori ve yayın tipi seçilmişse atla
        if (!force && window._lastCategoryChangedDetail) {
            const last = window._lastCategoryChangedDetail;
            if (last.category?.id === detail.category?.id &&
                last.yayinTipiId === detail.yayinTipiId) {
                console.log('⏭️ Aynı kategori/yayın tipi, event dispatch atlandı');
                return;
            }
        }

        // Mevcut debounce timer'ı temizle
        if (window._categoryChangedDebounceTimer) {
            clearTimeout(window._categoryChangedDebounceTimer);
            window._categoryChangedDebounceTimer = null;
        }

        // Son detail'i kaydet
        window._lastCategoryChangedDetail = JSON.parse(JSON.stringify(detail));

        const dispatchEvent = function() {
            const ready = !!document.getElementById('fields-empty-state') || !!document.getElementById(
                'fields-content');
            if (window.featuresSystem && typeof window.featuresSystem.invalidateAll === 'function') {
                window.featuresSystem.invalidateAll();
            }
            if (ready) {
                console.log('✅ category-changed event dispatch ediliyor:', detail);
                // ✅ Duplicate kontrolü - Event zaten dispatch edildiyse tekrar etme
                const eventKey = detail.category?.id + '-' + (detail.yayinTipiId || 'null');
                if (window._lastDispatchedEventKey === eventKey) {
                    console.log('⏭️ Aynı event zaten dispatch edildi, atlanıyor');
                    window._categoryChangedDebounceTimer = null;
                    return;
                }
                window._lastDispatchedEventKey = eventKey;
                window.dispatchEvent(new CustomEvent('category-changed', {
                    detail: detail
                }));
                window._categoryChangedDebounceTimer = null;
                return;
            }

            // Ready değilse, 200ms sonra tekrar dene (max 5 deneme = 1 saniye)
            let attempts = 0;
            const maxAttempts = 5;
            const checkInterval = setInterval(function() {
                attempts++;
                const ok = !!document.getElementById('fields-empty-state') || !!document.getElementById(
                    'fields-content');
                if (ok) {
                    clearInterval(checkInterval);
                    console.log('✅ category-changed event dispatch ediliyor (retry):', detail);
                    // ✅ Duplicate kontrolü
                    const eventKey = detail.category?.id + '-' + (detail.yayinTipiId || 'null');
                    if (window._lastDispatchedEventKey !== eventKey) {
                        window._lastDispatchedEventKey = eventKey;
                        window.dispatchEvent(new CustomEvent('category-changed', {
                            detail: detail
                        }));
                    }
                    window._categoryChangedDebounceTimer = null;
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    console.warn('⚠️ Fields container bulunamadı, event dispatch edilemedi');
                    window._categoryChangedDebounceTimer = null;
                }
            }, 200);
        };

        // Debounce: 300ms bekle
        window._categoryChangedDebounceTimer = setTimeout(dispatchEvent, 300);
    };

    document.addEventListener('DOMContentLoaded', function() {
        var tryAutoYayin = function() {
            var s = document.getElementById('junction_id');
            if (!s) return false;
            var opts = Array.prototype.filter.call(s.options || [], function(o) {
                return o.value && o.value !== '';
            });
            if (opts.length === 1) {
                s.value = opts[0].value;
                var ev = document.createEvent('HTMLEvents');
                ev.initEvent('change', true, false);
                s.dispatchEvent(ev);
                return true;
            }
            return false;
        };
        var attempts = 0;
        var iv = setInterval(function() {
            attempts++;
            if (tryAutoYayin() || attempts > 10) {
                clearInterval(iv);
            }
        }, 200);

        var selectEl = document.getElementById('junction_id');
        var hintEl = document.getElementById('yayin-tip-hint');
        var updateHint = function() {
            if (!hintEl || !selectEl) return;
            hintEl.classList.toggle('hidden', !!selectEl.value);
        };
        if (selectEl) {
            selectEl.addEventListener('change', updateHint);
            updateHint();
        }
    });
</script>
