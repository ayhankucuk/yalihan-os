{{-- STEP 2: UNIFIED FORM HANDLER --}}
<div x-show="wizard?.currentStep === 2" id="step-2-universal-container" x-data="wizardStep2Component()" x-cloak class="space-y-6">

    {{-- CORE FIELDS: Başlık, Fiyat, Açıklama (CRITICAL - Always Required) --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-8 space-y-6">
        <div class="flex items-center gap-3 mb-4">
            <div
                class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Temel İlan Bilgileri</h4>
                <p class="text-xs text-blue-600 dark:text-blue-400">Başlık zorunlu, açıklama opsiyonel; fiyat gösterim moduna göre zorunlu</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Başlık --}}
            <div class="lg:col-span-2">
                <label for="baslik"
                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    İlan Başlığı <span class="text-red-500">*</span>
                </label>
                <input type="text" id="baslik" name="baslik" maxlength="200"
                    placeholder="Örn: Deniz Manzaralı 3+1 Lüks Daire"
                    class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    value="{{ old('baslik') }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">En az 10, en fazla 200 karakter</p>
            </div>

            {{-- Fiyat --}}
            <div>
                <label for="fiyat"
                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Fiyat <span class="text-red-500">*</span>
                </label>
                <input type="number" id="fiyat" name="fiyat" min="0" step="1"
                    placeholder="Örn: 4500000"
                    class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    value="{{ old('fiyat') }}">
            </div>

            {{-- Para Birimi --}}
            <div>
                <label for="para_birimi"
                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Para Birimi
                </label>
                <select id="para_birimi" name="para_birimi"
                    class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
                    <option value="TRY" {{ old('para_birimi', 'TRY') === 'TRY' ? 'selected' : '' }}>₺ TRY</option>
                    <option value="USD" {{ old('para_birimi') === 'USD' ? 'selected' : '' }}>$ USD</option>
                    <option value="EUR" {{ old('para_birimi') === 'EUR' ? 'selected' : '' }}>€ EUR</option>
                    <option value="GBP" {{ old('para_birimi') === 'GBP' ? 'selected' : '' }}>£ GBP</option>
                </select>
            </div>

            {{-- Açıklama --}}
            @php
                $aiDefaults = [
                    'zorunlu_alanlar' => [],
                    'opsiyonel_alanlar' => [],
                    'validasyon_kurallari' => (object) [],
                    'ui_ipuclari' => (object) [],
                ];
            @endphp
            <div class="lg:col-span-2" x-data="aiDescriptionGenerator()" x-init='setAiResult(@json($aiResult ?? $aiDefaults))'>
                <div class="flex justify-between items-end mb-2">
                    <label for="aciklama"
                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        İlan Açıklaması
                    </label>
                    <button type="button" @click="openAiModal()"
                        :class="{ 'opacity-50 grayscale cursor-allowed': !
                        isContextValid(), 'hover:from-purple-700 hover:to-indigo-700': isContextValid() }"
                        :title="!isContextValid() ? 'AI kullanmak için Kategori, İl, İlçe ve m² girmelisiniz' :
                            'İlan açıklamasını yapay zeka ile oluştur'"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-purple-600 to-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 dark:shadow-none">
                        <svg class="w-3.5 h-3.5 mr-1.5" :class="{ 'animate-pulse': isContextValid() }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        AI ile Oluştur
                    </button>
                </div>

                <textarea id="aciklama" name="aciklama" rows="6" maxlength="5000"
                    placeholder="İlanınızın detaylı açıklamasını buraya yazın. Konum, özellikler, çevre hakkında bilgi verin..."
                    class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 resize-y dark:text-slate-100">{{ old('aciklama') }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opsiyonel. Girerseniz minimum 50 karakter önerilir.</p>

                {{-- AI Modal/Preview Area --}}
                <div x-show="aiModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                        <div x-show="aiModalOpen" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"
                            aria-hidden="true"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                            aria-hidden="true">&#8203;</span>

                        <div x-show="aiModalOpen" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                            <div>
                                <div
                                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900">
                                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                        id="modal-title">
                                        AI Açıklama Sihirbazı
                                    </h3>

                                    {{-- Loading State --}}
                                    <div x-show="aiLoading" class="py-8">
                                        <div class="flex justify-center mb-4">
                                            <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                                            Mükemmel bir açıklama hazırlanıyor...
                                        </p>
                                        <p class="text-xs text-indigo-500 mt-2 font-medium transition-all duration-300"
                                            x-text="loadingMessages[loadingStep]"></p>
                                    </div>

                                    {{-- Error State --}}
                                    <div x-show="!aiLoading && aiError"
                                        class="py-4 text-red-600 dark:text-red-400 text-sm">
                                        <p x-text="aiError"></p>
                                        <button @click="generateDescription()"
                                            class="mt-2 text-indigo-600 hover:text-indigo-500 underline">Tekrar
                                            Dene</button>
                                    </div>

                                    {{-- Result State --}}
                                    <div x-show="!aiLoading && !aiError && generatedDescription" class="mt-2 text-left">
                                        <div
                                            class="bg-gray-50 dark:bg-slate-900 p-4 rounded-md border border-gray-200 dark:border-slate-800 max-h-60 overflow-y-auto dark:border-slate-700">
                                            <p class="text-sm text-gray-600 dark:text-slate-200 whitespace-pre-line"
                                                x-text="generatedDescription"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="button" @click="acceptDescription()"
                                    x-show="!aiLoading && generatedDescription"
                                    class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm dark:shadow-none">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Kullan
                                </button>
                                <button type="button" @click="generateDescription()"
                                    x-show="!aiLoading && generatedDescription"
                                    class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Yeniden Üret
                                </button>
                                <button type="button" @click="closeModal()"
                                    x-show="aiLoading || (!generatedDescription && !aiLoading)"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-span-2 sm:text-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-300">
                                    İptal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 0. Kişi Bilgileri (Global) --}}
    @include('admin.ilanlar.partials.stable._kisi-secimi', ['wizardMode' => true])

    {{-- 0.1 Site/Apartman Bilgileri (Konut Only) --}}
    <div x-show="['konut_satilik', 'konut_kiralik', 'gunluk_kiralik'].includes(currentForm)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0">
        @include('admin.ilanlar.components.site-apartman-context7', ['wizardMode' => true])
    </div>

    {{-- Dynamic Header --}}
    <div class="mb-6" x-show="currentForm !== 'default'">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100" x-text="title"></h3>
        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="subtitle"></p>
    </div>

    {{-- SCHEMA-DRIVEN FIELDS (replaces 5 hardcoded form templates) --}}
    <div x-show="currentForm !== 'default'" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0">
        @include('admin.ilanlar.wizard.step2-schema')
    </div>

    {{-- Default/Fallback (no category selected) --}}
    <div x-show="currentForm === 'default'" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-700 p-8">
            <p class="text-gray-600 dark:text-gray-400">
                Lütfen kategori seçimi için Step 1'e dönün.
            </p>
        </div>
    </div>

    {{-- 7. Anahtar Bilgileri (Konut Only) --}}
    <div x-show="['konut_satilik', 'konut_kiralik', 'gunluk_kiralik'].includes(currentForm)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0">
        @include('admin.ilanlar.components.key-management', ['wizardMode' => true])
    </div>

    {{-- Portal Bilgileri (Global) --}}
    <div x-show="currentForm !== 'default'">
        @include('admin.ilanlar.components.portal-ids')
    </div>

    {{-- Navigation Buttons --}}
    <div class="flex justify-between gap-4 mt-8">
        <button type="button" @click="wizard?.prevStep()"
            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 hover:scale-105 active:scale-95 transition-all duration-200 font-medium dark:text-slate-300">
            ← Geri
        </button>
        <button type="button"
            @click="
            const form = document.getElementById('ilan-wizard-form');
            if (wizard?.currentStep === 2 && form) {
               // 1. Find visible required fields
               const stepContainer = document.querySelector('#step-2-universal-container');
               const invalidFields = [];

               // Check required inputs that are visible
               const requiredInputs = stepContainer.querySelectorAll('input[required]:not([disabled]), textarea[required]:not([disabled]), select[required]:not([disabled])');

               requiredInputs.forEach(input => {
                   if (!input.offsetParent) return; // Skip invisible
                   if (!input.value || input.value.trim() === '') {
                       invalidFields.push(input);
                       // Add red ring
                       input.classList.add('ring-2', 'ring-red-500', 'border-red-500');
                       // Remove ring on input
                       input.addEventListener('input', function() {
                           this.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
                       }, { once: true });
                   }
               });

               if (invalidFields.length > 0) {
                   // Shake effect
                   stepContainer.classList.add('animate-shake');
                   setTimeout(() => stepContainer.classList.remove('animate-shake'), 500);

                   // Toast
                   if (window.toast) {
                       window.toast.error(`Lütfen ${invalidFields.length} zorunlu alanı doldurun.`);
                   } else {
                       alert('Lütfen zorunlu alanları doldurun.');
                   }

                   // Focus first invalid
                   invalidFields[0].focus();
                   return;
               }
            }
            wizard?.nextStep()
        "
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 transition-all duration-200 font-medium">
            İleri →
        </button>
    </div>
</div>
