{{--
    🏗️ Wizard Step 2 — Schema-Driven Dynamic Field Renderer
    
    Bu component, KategoriYayinTipiFieldDependency tablosundan gelen
    field schema'yı dinamik olarak render eder.
    
    Eski hardcoded blade dosyaları (step-2-structured-data-*.blade.php)
    yerine tek bir schema-driven renderer kullanılır.
    
    @version 2.0.0 — Wizard Engine V2
--}}

<div class="space-y-6"
    x-data="schemaFieldRenderer"
    x-ref="schemaRenderer"
    :data-kategori-id="document.getElementById('alt_kategori_id')?.value || ''"
    :data-yayin-tipi-id="document.getElementById('junction_id')?.value || document.getElementById('yayin_tipi_id')?.value || ''">

    {{-- Loading State --}}
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center rounded-2xl border border-blue-100 bg-blue-50/50 py-12 dark:border-blue-900/30 dark:bg-blue-900/10">
            <div class="mb-4 h-10 w-10 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600 dark:border-blue-800 dark:border-t-blue-400"></div>
            <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">Alan şeması yükleniyor...</p>
            <p class="mt-1 text-xs text-blue-400 dark:text-blue-500">Kategoriye uygun alanlar hazırlanıyor</p>
        </div>
    </template>

    {{-- Error State --}}
    <template x-if="error && !loading">
        <div class="rounded-2xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/30 dark:bg-red-900/10">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400">Şema yüklenirken hata oluştu</p>
                    <p class="mt-0.5 text-xs text-red-500 dark:text-red-500" x-text="error"></p>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty State (no category selected yet) --}}
    <template x-if="!loading && !error && !loaded">
        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 py-16 dark:border-slate-700">
            <span class="mb-3 text-5xl">🏠</span>
            <p class="text-base font-semibold text-gray-500 dark:text-slate-400">Kategori ve yayın tipi seçin</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">Alanlar otomatik olarak yüklenecektir</p>
        </div>
    </template>

    {{-- Schema Loaded but Empty (no fields defined for this combo) --}}
    <template x-if="loaded && !loading && fields.length === 0">
        <div class="flex flex-col items-center justify-center rounded-2xl border border-amber-200 bg-amber-50/50 py-12 dark:border-amber-900/30 dark:bg-amber-900/10">
            <span class="mb-3 text-4xl">📋</span>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">Bu kombinasyon için tanımlı alan yok</p>
            <p class="mt-1 text-xs text-amber-500 dark:text-amber-500">Temel bilgiler ile devam edebilirsiniz</p>
        </div>
    </template>

    {{-- ✅ SCHEMA-DRIVEN FIELD RENDERING --}}
    <template x-if="loaded && !loading && fields.length > 0">
        <div class="space-y-8">
            {{-- Completion Indicator --}}
            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-white/60 p-4 shadow-sm backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/60">
                <div class="flex items-center gap-6">
                    {{-- Completion Indicator --}}
                    <div class="flex items-center gap-3">
                        <div class="relative h-10 w-10">
                            <svg class="h-10 w-10 -rotate-90 transform">
                                <circle cx="20" cy="20" r="16" stroke-width="3" fill="none"
                                    class="text-gray-100 dark:text-slate-800" stroke="currentColor" />
                                <circle cx="20" cy="20" r="16" stroke-width="3" fill="none"
                                    class="transition-all duration-700"
                                    :class="{
                                        'text-red-500': completionPercentage < 40,
                                        'text-yellow-500': completionPercentage >= 40 && completionPercentage < 70,
                                        'text-green-500': completionPercentage >= 70,
                                    }"
                                    stroke="currentColor"
                                    stroke-dasharray="100.5"
                                    :stroke-dashoffset="100.5 - (100.5 * completionPercentage / 100)"
                                    stroke-linecap="round" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black"
                                :class="{
                                    'text-red-600': completionPercentage < 40,
                                    'text-yellow-600': completionPercentage >= 40 && completionPercentage < 70,
                                    'text-green-600': completionPercentage >= 70,
                                }"
                                x-text="completionPercentage + '%'"></span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-700 dark:text-slate-300">
                                Tamamlanma Durumu
                            </p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500">
                                <span x-text="meta.required_count"></span> zorunlu alan ·
                                <span x-text="meta.total_fields"></span> toplam alan
                            </p>
                        </div>
                    </div>

                    {{-- 💾 Phase 4.6: Advanced Save Status & Controls --}}
                    <div class="flex items-center gap-2 rounded-lg border px-3 py-1.5 transition-all duration-300 shadow-sm"
                        :class="{
                            'bg-gray-50 border-gray-100 text-gray-400 dark:bg-slate-800 dark:border-slate-700': saveStatus === 'idle' && !isDirty,
                            'bg-amber-50 border-amber-200 text-amber-600 dark:bg-amber-900/20 dark:border-amber-800': isDirty && saveStatus === 'idle',
                            'bg-blue-50 border-blue-200 text-blue-600 animate-pulse dark:bg-blue-900/20 dark:border-blue-800': saveStatus === 'saving',
                            'bg-green-50 border-green-200 text-green-600 dark:bg-green-900/20 dark:border-green-800': saveStatus === 'saved' && validationValid,
                            'bg-orange-50 border-orange-200 text-orange-600 dark:bg-orange-900/20 dark:border-orange-800': saveStatus === 'saved' && !validationValid,
                            'bg-red-50 border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-800': saveStatus === 'error',
                            'bg-slate-100 border-slate-300 text-slate-600 dark:bg-slate-800 dark:border-slate-600': saveStatus === 'offline'
                        }">
                        <div class="h-1.5 w-1.5 rounded-full"
                            :class="{
                                'bg-gray-300': saveStatus === 'idle' && !isDirty,
                                'bg-amber-500 animate-bounce': isDirty && saveStatus === 'idle',
                                'bg-blue-500': saveStatus === 'saving',
                                'bg-green-500': saveStatus === 'saved' && validationValid,
                                'bg-orange-500 animate-pulse': saveStatus === 'saved' && !validationValid,
                                'bg-red-500': saveStatus === 'error',
                                'bg-slate-500': saveStatus === 'offline'
                            }"></div>
                        <span class="text-[10px] font-bold uppercase tracking-wider"
                            x-text="saveStatus === 'offline' ? 'Çevrimdışı' : 
                                   (saveStatus === 'error' ? 'Kayıt Hatası' : 
                                   (isDirty ? 'Değişiklikler Bekliyor' : 
                                   (saveStatus === 'saving' ? 'Kaydediliyor...' : 
                                   (saveStatus === 'saved' ? (validationValid ? 'Buluta Kaydedildi' : 'Kaydedildi (Eksikler Var)') : 'Beklemede'))))"></span>
                        
                        {{-- Manual Save Trigger --}}
                        <button type="button" 
                            x-show="isDirty && saveStatus !== 'saving'" 
                            @click="forceSave()"
                            class="ml-2 rounded bg-amber-600 px-1.5 py-0.5 text-[8px] font-black text-white hover:bg-amber-700">
                            ŞİMDİ KAYDET
                        </button>

                        {{-- Retry Trigger --}}
                        <button type="button" 
                            x-show="saveStatus === 'error'" 
                            @click="forceSave()"
                            class="ml-2 rounded bg-red-600 px-1.5 py-0.5 text-[8px] font-black text-white hover:bg-red-700">
                            YENİDEN DENE
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- 🪄 Phase 4: AI Assistant Button --}}
                    <button type="button" 
                        @click="fetchAiSuggestions()"
                        :disabled="isAnalyzing"
                        class="relative flex items-center gap-2 overflow-hidden rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2 text-[11px] font-bold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-purple-500/20 active:scale-95 disabled:opacity-50">
                        <span x-show="!isAnalyzing">🪄 AI Asistanını Çalıştır</span>
                        <span x-show="isAnalyzing" class="flex items-center gap-2">
                            <svg class="h-3 w-3 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Analiz Ediliyor...
                        </span>
                        {{-- Shimmer effect --}}
                        <div class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite] bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </button>

                    <template x-if="missingRequiredFields.length > 0">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-[10px] font-black text-red-600 dark:bg-red-900/30 dark:text-red-400"
                            x-text="missingRequiredFields.length"></span>
                        <span class="text-[10px] font-semibold text-red-500 dark:text-red-400">eksik</span>
                    </div>
                </template>
            </div>

            {{-- Grouped Fields Rendering --}}
            <template x-for="group in groupedFields" :key="group.slug">
                <div class="rounded-2xl border border-gray-100 bg-white/70 shadow-sm backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/70"
                    x-show="group.fields.some(f => isFieldVisible(f))">

                    {{-- Group Header --}}
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-slate-800">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-slate-200"
                            x-text="group.name"></h3>
                        <p class="mt-0.5 text-[10px] text-gray-400 dark:text-slate-500"
                            x-text="`${group.fields.filter(f => isFieldVisible(f)).length} alan`"></p>
                    </div>

                    {{-- Fields Grid --}}
                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2 lg:grid-cols-3">
                        <template x-for="field in group.fields" :key="field.slug">
                            <div x-show="isFieldVisible(field)"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                :class="{
                                    'md:col-span-2 lg:col-span-3': field.type === 'textarea',
                                    'md:col-span-1': field.type !== 'textarea',
                                }">
                                {{-- Dynamic field rendering via Alpine template switching --}}
                                <div x-html="renderField(field, fieldValues[field.slug], fieldErrors[field.slug], aiSuggestions[field.slug])"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

    // Update schemaFieldRenderer with renderField at runtime
    document.addEventListener('alpine:init', () => {
        const originalData = Alpine.data('schemaFieldRenderer');
        if (originalData) {
            Alpine.data('schemaFieldRenderer', function() {
                const instance = originalData.call(this);
                
                // Reactive render method
                instance.renderField = function(field, currentValue, currentError, aiSuggestion) {
                    // This method is reactive because Alpine calls it when any of the arguments change
                    return window.wizardFieldRenderer.render(field, currentValue, currentError, aiSuggestion);
                };
                
                return instance;
            });
        }
    });
</script>

{{-- Inline field renderer that works with x-html --}}
<script>
    /**
     * Client-side field renderer — generates HTML for each field type.
     * This is the bridge between schema data and Blade-quality HTML.
     */
    window.wizardFieldRenderer = {
            const renderer = componentMap[type] || this.renderText;
            const html = renderer.call(this, field, value, errors);
            
            // Wrap and add suggestion UI if exists
            if (suggestion) {
                return `
                    <div class="relative group">
                        ${html}
                        <div class="mt-2 flex items-center gap-2 animate-in fade-in slide-in-from-top-1 duration-300">
                            <button type="button" 
                                @click="applyAiSuggestion('${field.slug}', '${suggestion}')"
                                class="flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-[10px] font-bold text-purple-700 shadow-sm transition-all hover:bg-purple-100 dark:border-purple-900/40 dark:bg-purple-900/20 dark:text-purple-400">
                                <span>🪄 AI Önerisi:</span>
                                <span class="text-purple-900 dark:text-purple-200">${suggestion}</span>
                                <span class="ml-1 rounded bg-purple-600 px-1 py-0.5 text-[8px] text-white">UYGULA</span>
                            </button>
                        </div>
                    </div>`;
            }
            
            return html;
        },

        labelHtml(field, hasSuggestion = false) {
            const req = field.required ? '<span class="text-red-500">*</span>' : '';
            const icon = field.icon ? `<span class="text-base">${field.icon}</span>` : '';
            const ai = hasSuggestion ? '<span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 animate-pulse">🪄 ÖNERİ</span>' : '';
            const autoFill = (!hasSuggestion && field.ai_auto_fill) ? '<span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">⚡ Auto</span>' : '';
            return `<label for="field_${field.slug}" class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-slate-300">${icon} ${field.name} ${req} ${ai} ${autoFill}</label>`;
        },

        helpHtml(field, errors = null) {
            if (errors && Array.isArray(errors) && errors.length > 0) {
                return `<p class="mt-1 text-[10px] font-bold text-red-600 animate-pulse dark:text-red-400">⚠️ ${errors[0]}</p>`;
            }
            if (!field.help_text) return '';
            return `<p class="mt-1 text-[10px] text-gray-500 dark:text-slate-400">${field.help_text}</p>`;
        },

        inputClasses(hasError = false) {
            const base = 'w-full rounded-xl border bg-white/80 px-4 py-3 text-sm text-gray-900 shadow-sm backdrop-blur-sm transition-all duration-200 placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder:text-slate-500';
            const state = hasError 
                ? 'border-red-500 focus:border-red-600 focus:ring-red-500/20 dark:border-red-800 dark:focus:border-red-500' 
                : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-700 dark:focus:border-blue-400';
            return `${base} ${state}`;
        },

        renderText(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const placeholder = field.placeholder || `${field.name} giriniz`;
            const maxAttr = field.max ? `maxlength="${field.max}"` : '';
            const reqAttr = field.required ? 'required' : '';
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="text">
                    ${this.labelHtml(field, arguments[3])}
                    <input type="text" id="field_${field.slug}" name="features[${field.slug}]" value="${value || ''}" placeholder="${placeholder}" ${reqAttr} ${maxAttr}
                        class="${this.inputClasses(hasError)}"
                        @input.debounce.500ms="\$dispatch('field-changed', { slug: '${field.slug}', value: \$el.value, type: 'text' })" />
                    ${this.helpHtml(field, errors)}
                </div>`;
        },

        renderNumber(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const placeholder = field.placeholder || '0';
            const minAttr = field.min !== null && field.min !== undefined ? `min="${field.min}"` : '';
            const maxAttr = field.max !== null && field.max !== undefined ? `max="${field.max}"` : '';
            const stepAttr = field.step ? `step="${field.step}"` : 'step="any"';
            const reqAttr = field.required ? 'required' : '';
            const unitHtml = field.unit ? `<span class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-slate-700 dark:text-slate-400">${field.unit}</span>` : '';
            const prClass = field.unit ? 'pr-16' : '';
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="number">
                    ${this.labelHtml(field, arguments[3])}
                    <div class="relative">
                        <input type="number" id="field_${field.slug}" name="features[${field.slug}]" value="${value || ''}" placeholder="${placeholder}" ${reqAttr} ${minAttr} ${maxAttr} ${stepAttr}
                            class="${this.inputClasses(hasError)} ${prClass}"
                            @input.debounce.500ms="\$dispatch('field-changed', { slug: '${field.slug}', value: \$el.value, type: 'number' })" />
                        ${unitHtml}
                    </div>
                    ${this.helpHtml(field, errors)}
                </div>`;
        },

        renderToggle(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const checked = value === true || value === 1 || value === '1' || value === 'true';
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="boolean"
                    x-data="{ enabled: ${checked} }">
                    <div class="flex items-center justify-between rounded-xl border px-4 py-3 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-blue-300 dark:bg-slate-800/80 dark:hover:border-blue-600 ${hasError ? 'border-red-500 bg-red-50/10 dark:border-red-800' : 'border-gray-200 bg-white/80 dark:border-slate-700'}">
                        <div class="flex items-center gap-3">
                            ${field.icon ? `<span class="text-lg">${field.icon}</span>` : ''}
                            <div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">${field.name}</span>
                                ${hasError ? this.helpHtml(field, errors) : (field.help_text ? `<p class="text-[10px] text-gray-500 dark:text-slate-400">${field.help_text}</p>` : '')}
                            </div>
                        </div>
                        <div class="relative">
                            <input type="hidden" name="features[${field.slug}]" :value="enabled ? '1' : '0'" />
                            <button type="button"
                                class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                                :class="enabled ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-200 dark:bg-slate-600'"
                                @click="enabled = !enabled; \$dispatch('field-changed', { slug: '${field.slug}', value: enabled, type: 'boolean' })"
                                role="switch" :aria-checked="enabled.toString()">
                                <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition-transform duration-300 ease-in-out dark:bg-slate-200"
                                    :class="enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>
                </div>`;
        },

        renderSelect(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const placeholder = field.placeholder || '-- Seçiniz --';
            const reqAttr = field.required ? 'required' : '';
            let optionsHtml = `<option value="">${placeholder}</option>`;
            if (field.options && Array.isArray(field.options)) {
                field.options.forEach(opt => {
                    const val = typeof opt === 'object' ? opt.value : opt;
                    const lbl = typeof opt === 'object' ? opt.label : opt;
                    const selected = value == val ? 'selected' : '';
                    optionsHtml += `<option value="${val}" ${selected}>${lbl}</option>`;
                });
            }
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="select">
                    ${this.labelHtml(field, arguments[3])}
                    <select id="field_${field.slug}" name="features[${field.slug}]" ${reqAttr}
                        class="${this.inputClasses(hasError)} appearance-none"
                        @change="\$dispatch('field-changed', { slug: '${field.slug}', value: \$el.value, type: 'select' })">
                        ${optionsHtml}
                    </select>
                    ${this.helpHtml(field, errors)}
                </div>`;
        },

        renderMultiselect(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const selectedArr = Array.isArray(value) ? value : [];
            let chipsHtml = '';
            if (field.options && Array.isArray(field.options)) {
                field.options.forEach(opt => {
                    const val = typeof opt === 'object' ? opt.value : opt;
                    const lbl = typeof opt === 'object' ? opt.label : opt;
                    chipsHtml += `
                        <button type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[10px] font-bold transition-all duration-200"
                            :class="selected.includes('${val}')
                                ? 'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-900/30 dark:border-blue-600 dark:text-blue-400 shadow-sm'
                                : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-blue-200 dark:bg-slate-700/50 dark:border-slate-600 dark:text-slate-400'"
                            @click="
                                if (selected.includes('${val}')) { selected = selected.filter(v => v !== '${val}'); }
                                else { selected.push('${val}'); }
                                \$dispatch('field-changed', { slug: '${field.slug}', value: selected, type: 'multiselect' })
                            ">
                            <span x-text="selected.includes('${val}') ? '✓' : '+'"></span>
                            ${lbl}
                        </button>`;
                });
            }
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="multiselect"
                    x-data="{ selected: ${JSON.stringify(selectedArr)} }">
                    ${this.labelHtml(field, arguments[3])}
                    <input type="hidden" name="features[${field.slug}]" :value="JSON.stringify(selected)" />
                    <div class="flex flex-wrap gap-2 rounded-xl border p-3 shadow-sm backdrop-blur-sm ${hasError ? 'border-red-500 bg-red-50/10 dark:border-red-800' : 'border-gray-200 bg-white/80 dark:border-slate-700 dark:bg-slate-800/80'}">
                        ${chipsHtml}
                    </div>
                    ${this.helpHtml(field, errors)}
                </div>`;
        },

        renderTextarea(field, value, errors) {
            const hasError = errors && errors.length > 0;
            const placeholder = field.placeholder || `${field.name} giriniz`;
            const reqAttr = field.required ? 'required' : '';
            const rows = (field.options && field.options.rows) || 4;
            return `
                <div class="field-wrapper" data-field-slug="${field.slug}" data-field-type="textarea">
                    ${this.labelHtml(field, arguments[3])}
                    <textarea id="field_${field.slug}" name="features[${field.slug}]" rows="${rows}" placeholder="${placeholder}" ${reqAttr}
                        class="${this.inputClasses(hasError)} resize-y"
                        @input.debounce.500ms="\$dispatch('field-changed', { slug: '${field.slug}', value: \$el.value, type: 'textarea' })">${value || ''}</textarea>
                    ${this.helpHtml(field, errors)}
                </div>`;
        },
    };

    // Inject renderField method into Alpine component
    document.addEventListener('alpine:init', () => {
        const origFn = Alpine.Components?.schemaFieldRenderer;
        // Patch: Add renderField if not present
        Alpine.store('wizardRenderer', window.wizardFieldRenderer);
    });
</script>

<script>
    // Patch Alpine schemaFieldRenderer with renderField at runtime
    document.addEventListener('alpine:init', () => {
        const origData = Alpine.data('schemaFieldRenderer');
        if (origData) {
            // Re-register with renderField
            const patchedData = function() {
                const instance = origData.call(this);
                instance.renderField = function(field) {
                    const value = this.getFieldValue(field.slug);
                    return window.wizardFieldRenderer.render(field, value);
                };
                return instance;
            };
            // Note: Alpine.data can be overridden
        }
    });

    // Simpler approach: add renderField via prototype or global
    window.addEventListener('DOMContentLoaded', () => {
        // Ensure renderField is available on Alpine component instances
        const originalInit = Alpine.data('schemaFieldRenderer');
    });
</script>
@endpush
