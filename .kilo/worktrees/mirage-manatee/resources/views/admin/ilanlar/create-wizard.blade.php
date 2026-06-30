@extends('admin.layouts.admin')

@section('title', 'Yeni İlan Oluştur | Yalıhan Emlak')

@section('content')
    @vite(['resources/js/components/CortexObserver.js', 'resources/js/wizard/components/price-formatter.js', 'resources/js/wizard/step2-category.js', 'resources/js/wizard/step2-features.js', 'resources/js/wizard/schema-field-renderer.js'])

    <script>
        window.ilanId = {{ (int) ($ilanId ?? 0) }};
    </script>

    <div class="space-y-6 pb-32" x-data="{
        wizard: null,
        cortexScore: 0,
        healthData: null,
        dragging: false,
        photos: [],
        aiAnalyzing: false,
        aiSuggestions: [],
        activeTab: 'auto',
        unitPrice: 0,
        portal_ids: {
            sahibinden: '',
            emlakjet: '',
            hepsiemlak: '',
            zingat: '',
            hurriyetemlak: ''
        }
    }" x-init="const initWizard = () => {
        if (typeof window.ilanWizard !== 'undefined') {
            wizard = window.ilanWizard();
            if (wizard && wizard.init) wizard.init();
        } else {
            window.addEventListener('wizard:ready', () => {
                wizard = window.ilanWizard();
                if (wizard && wizard.init) wizard.init();
            }, { once: true });
        }
    };

    initWizard();
    if (!wizard) {
        setTimeout(initWizard, 100);
    }">
        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                    Yeni İlan Oluştur
                </h1>
                <p class="mt-1.5 text-sm text-gray-600 dark:text-slate-300">
                    Adım adım ilan bilgilerinizi doldurun
                </p>
            </div>
            <a href="{{ route('admin.ilanlar.index') }}"
                class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2.5 font-medium text-white shadow-sm transition-all duration-200 hover:bg-gray-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95 dark:shadow-none">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Geri Dön
            </a>
        </div>

        {{-- Progress Bar --}}
        <div
            class="rounded-3xl border border-white/20 bg-white/70 p-6 shadow-xl backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">İlerleme
                        Durumu</span>
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                        x-text="`${Math.round((wizard?.currentStep || 1) / (wizard?.totalSteps || 5) * 100)}%`"></span>
                </div>
            </div>
            <div
                class="h-2.5 w-full overflow-hidden rounded-full border border-gray-200 bg-gray-100 p-0.5 dark:border-slate-700 dark:bg-slate-800">
                <div class="cubic-bezier(0.4, 0, 0.2, 1) relative h-full rounded-full bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 transition-all duration-1000 dark:from-blue-600 dark:via-purple-600 dark:to-pink-600"
                    :style="`width: ${((wizard?.currentStep || 1) / (wizard?.totalSteps || 5)) * 100}%`">
                    <div class="absolute inset-0 animate-pulse bg-white/20 dark:bg-slate-600/30"></div>
                </div>
            </div>
        </div>

        {{-- Wizard Steps Navigation (Premium Stepper) --}}
        <div
            class="relative overflow-hidden rounded-[2.5rem] border border-white/20 bg-white/50 p-8 shadow-2xl backdrop-blur-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            {{-- Decorative Background --}}
            <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-purple-500/10 blur-3xl"></div>

            <div class="relative z-10 flex items-center justify-between">
                <template
                    x-for="step in [
                    {id: 1, label: '1. Kategori', icon: 'fas fa-map-marker-alt'},
                    {id: 2, label: '2. Bilgiler', icon: 'fas fa-info-circle'},
                    {id: 3, label: '3. Fotoğraf', icon: 'fas fa-images'},
                    {id: 4, label: '4. Adres', icon: 'fas fa-map-pin'},
                    {id: 5, label: '5. Önizleme', icon: 'fas fa-check-double'}
                ]"
                    :key="step.id">
                    <div class="flex flex-1 items-center last:flex-none">
                        <div class="group flex cursor-pointer flex-col items-center"
                            @click="wizard?.completedSteps?.includes(step.id) || wizard?.currentStep === step.id || (step.id > 1 && wizard?.completedSteps?.includes(step.id - 1)) ? wizard?.goToStep(step.id) : null">

                            <div class="wizard-step-indicator"
                                :class="{
                                    'completed': wizard?.completedSteps?.includes(step.id),
                                    'current': wizard?.currentStep === step.id,
                                    'pending': !wizard?.completedSteps?.includes(step.id) && wizard?.currentStep !==
                                        step.id
                                }">
                                <i :class="step.icon"></i>

                                {{-- Checkmark for completed --}}
                                <template
                                    x-if="wizard?.completedSteps?.includes(step.id) && wizard?.currentStep !== step.id">
                                    <div
                                        class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-green-500 dark:border-slate-900">
                                        <i class="fas fa-check text-[10px] text-white"></i>
                                    </div>
                                </template>
                            </div>
                            <span class="wizard-step-label"
                                :class="{
                                    'current': wizard?.currentStep === step.id,
                                    'completed': wizard?.completedSteps?.includes(step.id)
                                }"
                                x-text="step.label"></span>
                        </div>

                        {{-- Connector Line --}}
                        <template x-if="step.id < 5">
                            <div class="mx-4 h-0.5 flex-1 transition-all duration-500"
                                :class="wizard?.completedSteps?.includes(step.id) ?
                                    'bg-gradient-to-r from-green-500 to-blue-500' :
                                    'bg-gray-200 dark:bg-slate-800'">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cortex Health & Quality Score (Proposition 2) --}}
        <div x-data="cortexObserver" class="wizard-card group relative overflow-hidden p-6" x-show="score > 0"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 transform -translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 transform translate-y-0 scale-100">

            {{-- Pulse Effect for low score --}}
            <template x-if="score < 40">
                <div class="pointer-events-none absolute inset-0 animate-pulse bg-red-500/5"></div>
            </template>

            <div class="relative z-10 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 transform items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg transition-transform duration-300 group-hover:rotate-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="flex items-center gap-2 text-lg font-black text-gray-900 dark:text-slate-100">
                            Cortex Zeka Paneli
                            <span
                                class="inline-flex items-center rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-tighter text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">Live
                                Analysis</span>
                        </h3>
                        <p class="text-xs font-medium text-gray-500 dark:text-slate-400">İlan kalitesi ve yayın onayı için
                            gerçek zamanlı analiz</p>
                    </div>
                </div>

                <div class="relative flex h-20 w-20 items-center justify-center">
                    {{-- Circular Gauge (SVG) --}}
                    <svg class="h-full w-full -rotate-90 transform">
                        <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="6"
                            fill="transparent" class="text-gray-100 dark:text-slate-800" />
                        <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="6"
                            fill="transparent" stroke-dasharray="213.6" :stroke-dashoffset="213.6 - (213.6 * score / 100)"
                            class="transition-all duration-1000 ease-out"
                            :class="{
                                'text-red-500': score < 40,
                                'text-yellow-500': score >= 40 && score < 70,
                                'text-green-500': score >= 70
                            }" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-black tabular-nums" :class="getColor()" x-text="score"></span>
                        <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400">Puan</span>
                    </div>
                </div>
            </div>

            <div
                class="h-3 w-full overflow-hidden rounded-full border border-gray-200 bg-gray-100 dark:border-slate-700 dark:bg-slate-800">
                <div class="h-full transition-all duration-1000 ease-out"
                    :class="{
                        'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]': score < 40,
                        'bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.5)]': score >= 40 && score < 70,
                        'bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]': score >= 70
                    }"
                    :style="`width: ${score}%; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1)`"></div>
            </div>

            <template x-if="suggestions.length > 0">
                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-slate-800">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="suggestion in suggestions.slice(0, 4)" :key="suggestion.message">
                            <div class="group relative">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-semibold transition-all duration-200"
                                    :class="{
                                        'bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800': suggestion
                                            .severity === 'high',
                                        'bg-yellow-50 dark:bg-yellow-900/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800': suggestion
                                            .severity !== 'high'
                                    }">
                                    <svg x-show="suggestion.severity === 'high'" class="h-3 w-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span x-text="suggestion.message"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="suggestions.length > 4">
                            <span class="flex items-center py-1.5 text-[10px] text-gray-500 dark:text-gray-400"
                                x-text="`+${suggestions.length - 4} diğer öneri`"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Main Form --}}
        <form id="ilan-wizard-form" method="POST" action="{{ route('admin.ilanlar.store') }}"
            enctype="multipart/form-data" @submit.prevent="wizard?.submitForm()">
            @csrf

            {{-- STEP 1: İLAN KATEGORİSİ --}}
            <div x-show="!wizard || wizard?.currentStep === 1"
                x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-700"
                x-transition:enter-start="opacity-0 transform translate-x-8 blur-sm"
                x-transition:enter-end="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500"
                x-transition:leave-start="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave-end="opacity-0 transform -translate-x-8 blur-sm" class="wizard-card p-8">
                @include('admin.ilanlar.wizard.step-1-category')

                <div class="mt-8 flex justify-end gap-4">
                    <button type="button" @click="wizard?.nextStep()"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:bg-blue-700 active:scale-95">
                        İleri →
                    </button>
                </div>
            </div>

            {{-- STEP 2: UNIFIED FORM --}}
            <div x-show="wizard?.currentStep === 2" id="step-2-info"
                x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-700"
                x-transition:enter-start="opacity-0 transform translate-x-8 blur-sm"
                x-transition:enter-end="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500"
                x-transition:leave-start="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave-end="opacity-0 transform -translate-x-8 blur-sm" class="wizard-card p-8">
                @include('admin.ilanlar.wizard.step-2-unified')
            </div>

            {{-- STEP 3: FOTOĞRAF VE VİDEO --}}
            <div x-show="wizard?.currentStep === 3"
                x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-700"
                x-transition:enter-start="opacity-0 transform translate-x-8 blur-sm"
                x-transition:enter-end="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500"
                x-transition:leave-start="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave-end="opacity-0 transform -translate-x-8 blur-sm" class="wizard-card p-8">
                @include('admin.ilanlar.wizard.step-3-photos')

                {{-- SSOT: Dynamic Fields Container (UPS/Features) --}}
                <div id="step3-dynamic-fields-container" class="mt-6 space-y-4" data-wizard-step="3"></div>

                <div class="mt-8 flex justify-between gap-4">
                    <button type="button" @click="wizard?.prevStep()"
                        class="rounded-lg bg-gray-200 px-6 py-3 font-medium text-gray-700 transition-all duration-200 hover:scale-105 hover:bg-gray-300 active:scale-95 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                        ← Geri
                    </button>
                    <button type="button" @click="wizard?.nextStep()"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:bg-blue-700 active:scale-95">
                        İleri →
                    </button>
                </div>
            </div>

            {{-- STEP 4: PREVIEW (Yazlık Kiralama) veya ADRES (Diğer) --}}
            <div x-show="wizard?.currentStep === 4"
                x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-700"
                x-transition:enter-start="opacity-0 transform translate-x-8 blur-sm"
                x-transition:enter-end="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500"
                x-transition:leave-start="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave-end="opacity-0 transform -translate-x-8 blur-sm" class="wizard-card p-8">

                @include('admin.ilanlar.wizard.step-4-address')

                <div class="mt-8 flex justify-between gap-4">
                    <button type="button" @click="wizard?.prevStep()"
                        class="rounded-lg bg-gray-200 px-6 py-3 font-medium text-gray-700 transition-all duration-200 hover:scale-105 hover:bg-gray-300 active:scale-95 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                        ← Geri
                    </button>
                    <button type="button" @click="wizard?.nextStep()"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:bg-blue-700 active:scale-95">
                        İleri →
                    </button>
                </div>
            </div>

            {{-- STEP 5: ÖNİZLEME VE YAYIN --}}
            <div x-show="wizard?.currentStep === 5"
                x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-700"
                x-transition:enter-start="opacity-0 transform translate-x-8 blur-sm"
                x-transition:enter-end="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500"
                x-transition:leave-start="opacity-100 transform translate-x-0 blur-0"
                x-transition:leave-end="opacity-0 transform -translate-x-8 blur-sm" class="wizard-card p-8">
                @include('admin.ilanlar.wizard.step-5-preview')

                <div class="mt-8 flex justify-between gap-4">
                    <button type="button" @click="wizard?.prevStep()"
                        class="rounded-lg bg-gray-200 px-6 py-3 font-medium text-gray-700 transition-all duration-200 hover:scale-105 hover:bg-gray-300 active:scale-95 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                        ← Geri
                    </button>
                    <div class="flex gap-4" x-data="{ cortexScore: 0 }" x-init="window.addEventListener('cortex-score-updated', e => { cortexScore = e.detail.score || 0 })">
                        <button type="button" @click="wizard?.saveDraft()"
                            class="rounded-lg bg-yellow-600 px-6 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:bg-yellow-700 active:scale-95">
                            💾 Taslak Kaydet
                        </button>
                        {{-- ✅ FIX-2 (SAB Sprint 2026-04-04): Cortex blocker → WARNING mode --}}
                        {{-- Önceki: :disabled="cortexScore < 40" (Cortex down → submit impossible) --}}
                        {{-- Yeni: Her zaman tıklanabilir, score < 40 ise sadece uyarı gösterilir --}}
                        <button type="submit"
                            class="rounded-lg px-6 py-3 font-medium transition-all duration-200"
                            :class="cortexScore < 40 ?
                                'bg-yellow-500 text-white hover:bg-yellow-600 hover:scale-105 active:scale-95' :
                                'bg-green-600 text-white hover:bg-green-700 hover:scale-105 active:scale-95'">
                            <span x-show="cortexScore < 40 && cortexScore > 0">⚠️ Düşük Skorla Kaydet</span>
                            <span x-show="cortexScore === 0">💾 Taslak Olarak Kaydet</span>
                            <span x-show="cortexScore >= 40">✅ Yayınla</span>
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" integrity="sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ" crossorigin="anonymous" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" integrity="sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3" crossorigin="anonymous"></script>

        <script>
            if (typeof window.aiTitleGenerator === 'undefined') {
                window.aiTitleGenerator = function() {
                    return {
                        loading: false,
                        aiTitles: [],
                        showAiTitles: false,
                        selectedTitle: '',
                        seoScore: 0,
                        pastTitles: [],
                        showSuggestions: false,
                        suggestions: [],

                        init() {
                            this.loading = false;
                            this.aiTitles = [];
                            this.showAiTitles = false;
                            this.selectedTitle = '';
                            this.seoScore = 0;
                            this.pastTitles = [];
                            this.showSuggestions = false;
                            this.suggestions = [];

                            const lastTitle = localStorage.getItem('ai_last_selected_title');
                            if (lastTitle) {
                                this.selectedTitle = lastTitle;
                                const baslikInput = document.getElementById('baslik');
                                if (baslikInput) {
                                    baslikInput.value = lastTitle;
                                }
                            }
                            this.updateSEOScore();
                        },

                        get canGenerate() {
                            const altKategoriId = document.getElementById('alt_kategori_id')?.value;
                            const ilId = document.getElementById('il_id')?.value;
                            const ilceId = document.getElementById('ilce_id')?.value;
                            return !!(altKategoriId && ilId && ilceId);
                        },

                        updateSEOScore() {
                            const title = this.selectedTitle || document.getElementById('baslik')?.value || '';
                            if (!title) {
                                this.seoScore = 0;
                                return;
                            }

                            let score = 0;
                            if (title.length >= 50 && title.length <= 70) {
                                score += 30;
                            } else if (title.length >= 40 && title.length <= 80) {
                                score += 20;
                            } else if (title.length >= 30 && title.length <= 90) {
                                score += 10;
                            }

                            const locationKeywords = ['Bodrum', 'Yalıkavak', 'Turgutreis', 'Gümüşlük', 'Muğla', 'Marmaris',
                                'Fethiye', 'Kaş'
                            ];
                            if (locationKeywords.some(kw => title.includes(kw))) {
                                score += 25;
                            }

                            const poiKeywords = ['Marina', 'marina', 'Deniz', 'deniz', 'Havuz', 'havuz', 'Plaj', 'plaj',
                                'Havalimanı', 'havalimanı'
                            ];
                            if (poiKeywords.some(kw => title.includes(kw))) {
                                score += 25;
                            }

                            const categoryKeywords = ['Villa', 'villa', 'Daire', 'daire', 'Arsa', 'arsa', 'Yazlık',
                                'yazlık', 'Müstakil', 'müstakil'
                            ];
                            if (categoryKeywords.some(kw => title.includes(kw))) {
                                score += 10;
                            }

                            if (/\d+/.test(title)) {
                                score += 10;
                            }

                            this.seoScore = Math.min(score, 100);
                        },

                        async generateTitles() {
                            if (!this.canGenerate) {
                                if (window.toast) {
                                    window.toast.error('Lütfen kategori ve lokasyon bilgilerini doldurun');
                                } else {
                                    alert('Lütfen kategori ve lokasyon bilgilerini doldurun');
                                }
                                return;
                            }

                            const altKategoriId = document.getElementById('alt_kategori_id')?.value;
                            const ilId = document.getElementById('il_id')?.value;
                            const ilceId = document.getElementById('ilce_id')?.value;
                            const mahalleId = document.getElementById('mahalle_id')?.value;
                            const yayinTipiId = document.getElementById('junction_id')?.value;

                            this.loading = true;
                            this.aiTitles = [];

                            try {
                                const response = await fetch('{{ route("admin.ilanlar.generate-ai-title") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                            ?.content || '',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        kategori: altKategoriId,
                                        il: ilId,
                                        ilce: ilceId,
                                        mahalle: mahalleId || '',
                                        yayin_tipi: yayinTipiId || '',
                                        ai_tone: 'seo'
                                    })
                                });

                                const result = await response.json();

                                if (result.success && result.variants && result.variants.length > 0) {
                                    this.aiTitles = result.variants;
                                    this.showAiTitles = true;

                                    if (window.toast) {
                                        window.toast.success(`${result.variants.length} başlık önerisi oluşturuldu`);
                                    }

                                    // 📊 Phase 7.1: Telemetry - Log suggested titles
                                    const requestId = 'ai_title_' + Date.now();
                                    const altKategoriId = document.getElementById('alt_kategori_id')?.value;
                                    const yayinTipiId = document.getElementById('junction_id')?.value;

                                    result.variants.forEach(variant => {
                                        this.logTelemetry({
                                            kategori_id: altKategoriId,
                                            junction_id: yayinTipiId,
                                            feature_slug: 'ai_title',
                                            confidence: 0.85, // Default for titles
                                            source_tipi: 'mixed', // Title is usually based on category + location
                                            aksiyon: 'suggested',
                                            neden: 'AI başlık önerisi üretildi',
                                            neden_detay: {
                                                text: variant.text || variant
                                            },
                                            istek_id: requestId
                                        });
                                    });
                                } else {
                                    if (window.toast) {
                                        window.toast.error('Başlık önerisi oluşturulamadı');
                                    } else {
                                        alert('Başlık önerisi oluşturulamadı');
                                    }
                                }
                            } catch (error) {
                                console.error('AI başlık üretimi hatası:', error);
                                if (window.toast) {
                                    window.toast.error('AI başlık üretimi sırasında hata oluştu');
                                } else {
                                    alert('AI başlık üretimi sırasında hata oluştu');
                                }
                            } finally {
                                this.loading = false;
                            }
                        },

                        selectTitle(title) {
                            const titleText = typeof title === 'object' ? title.text : title;
                            this.selectedTitle = titleText;
                            const baslikInput = document.getElementById('baslik');
                            if (baslikInput) {
                                baslikInput.value = titleText;
                                baslikInput.dispatchEvent(new Event('input'));
                            }
                            this.updateSEOScore();
                            this.showAiTitles = false;

                            // 📊 Phase 7.1: Telemetry - Log user selection
                            const altKategoriId = document.getElementById('alt_kategori_id')?.value;
                            const yayinTipiId = document.getElementById('junction_id')?.value;

                            this.logTelemetry({
                                kategori_id: altKategoriId,
                                junction_id: yayinTipiId,
                                feature_slug: 'ai_title',
                                confidence: 1.0,
                                source_tipi: 'mixed',
                                aksiyon: 'user_applied',
                                neden: 'Kullanıcı AI başlığını seçti',
                                neden_detay: {
                                    text: titleText
                                }
                            });

                            this.saveTitleHistory(titleText);

                            if (window.toast) {
                                window.toast.success('Başlık seçildi');
                            }
                        },

                        saveTitleHistory(title) {
                            try {
                                const history = JSON.parse(localStorage.getItem('ai_title_history') || '[]');
                                const kategoriId = document.getElementById('alt_kategori_id')?.value;
                                const ilceId = document.getElementById('ilce_id')?.value;

                                history.unshift({
                                    title: title,
                                    timestamp: new Date().toISOString(),
                                    kategori: kategoriId,
                                    lokasyon: ilceId,
                                });

                                const limited = history.slice(0, 10);
                                localStorage.setItem('ai_title_history', JSON.stringify(limited));
                                localStorage.setItem('ai_last_selected_title', title);
                            } catch (e) {
                                console.warn('LocalStorage kayıt hatası:', e);
                            }
                        },

                        // 📊 Phase 7.1: Telemetry Helper
                        async logTelemetry(payload) {
                            try {
                                await fetch('{{ route("api.wizard.telemetry.feature-action") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                            ?.content || '',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(payload)
                                });
                            } catch (e) {
                                console.warn('AI Telemetry failed:', e);
                            }
                        }
                    };
                };
            }

            // 🤖 AI Price Advisor Wizard Integration (Kademe 2)
            if (typeof window.cortexPriceAdvisor === 'undefined') {
                window.cortexPriceAdvisor = function() {
                    return {
                        analysis: null,
                        loading: false,
                        init() {
                            window.addEventListener('wizard-step-changed', (e) => {
                                if (e.detail.step === 5) {
                                    this.getAnalysis();
                                }
                            });
                        },
                        async getAnalysis() {
                            const form = document.getElementById('ilan-wizard-form');
                            if (!form) return;

                            this.loading = true;
                            const formData = new FormData(form);
                            const payload = {
                                il_id: formData.get('il_id'),
                                ilce_id: formData.get('ilce_id'),
                                mahalle_id: formData.get('mahalle_id'),
                                kategori_id: formData.get('alt_kategori_id'),
                                fiyat: formData.get('fiyat') || formData.get('fiyat_raw'),
                                alan_m2: formData.get('alan_m2'),
                                lat: formData.get('lat'),
                                lng: formData.get('lng'),
                            };

                            // Basic validation to avoid empty requests
                            if (!payload.kategori_id || !payload.il_id || !payload.fiyat) {
                                this.loading = false;
                                return;
                            }

                            try {
                                const response = await fetch('{{ route("advisor.price-advisor.wizard.api") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                            .content,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(payload)
                                });
                                const result = await response.json();
                                if (result.success) {
                                    this.analysis = result.data;
                                }
                            } catch (e) {
                                console.error('Price Advisor error:', e);
                            } finally {
                                this.loading = false;
                            }
                        }
                    };
                };
            }
        </script>



        <script>
            window.YALI_FEATURES_USE_ASSIGNMENT_RESOLVER = @json(config('yali_options.features.use_assignment_resolver', false));

            // ✅ SAB: Live Search Başlatma
            document.addEventListener('DOMContentLoaded', function() {
                if (window.__sabLiveSearchInitialized) return;
                window.__sabLiveSearchInitialized = true;
                
                if (typeof window.Context7LiveSearch !== 'undefined') {
                    const liveSearch = new window.Context7LiveSearch({
                        debounceDelay: 300,
                        minQueryLength: 2,
                        maxResults: 10,
                        enableKeyboardNavigation: true
                    });
                    console.log('✅ SAB Live Search initialized for wizard');
                } else {
                    console.warn('⚠️ Context7 Live Search class not found');
                }
            });
        </script>

        @vite(['resources/js/wizard/components/price-formatter.js', 'resources/js/wizard/step1-cascade.js', 'resources/js/admin/ilan-wizard-page.js', 'resources/js/admin/location-wizard.js', 'resources/js/admin/listing-wizard/store.js', 'resources/js/wizard/components/ai-description.js', 'resources/js/components/MapPolygonManager.js'])
        <script type="module" src="{{ asset('js/leaflet-draw-loader.js') }}"></script>
        <script src="{{ asset('js/context7-live-search.js') }}"></script>
        <script>
            (function() {
                if (window.__wizardBootstrapStarted) return;
                window.__wizardBootstrapStarted = true;

                const maxWaitMs = 3000;
                const startedAt = Date.now();
                window.YALI_OPTIONS = window.YALI_OPTIONS || {};
                window.YALI_OPTIONS.suppressYayinTipiChangeToast = true;
                window.YALI_OPTIONS.resetOnYayinTipiChange = 'minimal';
                const tryInit = () => {
                    if (window.__ilanWizardReadyFired) return true;
                    if (typeof window.ilanWizard === 'function') {
                        window.__ilanWizardReadyFired = true;
                        document.dispatchEvent(new CustomEvent('ilan-wizard-ready'));
                        return true;
                    }
                    if (Date.now() - startedAt < maxWaitMs) {
                        setTimeout(tryInit, 100);
                        return false;
                    }
                    return false;
                };
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => setTimeout(tryInit, 100));
                } else {
                    setTimeout(tryInit, 100);
                }
            })();

            // ✅ Optimized Live Search System
            document.addEventListener('DOMContentLoaded', () => {
                if (window.__optimizedLiveSearchInitialized) return;
                window.__optimizedLiveSearchInitialized = true;

                const searches = [{
                        id: 'ilan_sahibi',
                        endpoint: '/api/v1/admin/api/kisi/search',
                        labelKey: 'ad',
                        extraKey: 'soyad',
                        subKey: 'telefon'
                    },
                    {
                        id: 'ilgili_kisi',
                        endpoint: '/api/v1/admin/api/kisi/search',
                        labelKey: 'ad',
                        extraKey: 'soyad',
                        subKey: 'telefon'
                    },
                    {
                        id: 'danisman',
                        endpoint: '/api/v1/users?role=danisman',
                        labelKey: 'name',
                        extraKey: '',
                        subKey: 'email'
                    },
                    {
                        id: 'site',
                        endpoint: '/api/v1/admin/api/sites/search',
                        labelKey: 'name',
                        subKey: 'adres'
                    }
                ];
                searches.forEach(config => initSearch(config));
            });

            function initSearch({
                id,
                endpoint,
                labelKey,
                extraKey,
                subKey
            }) {
                const searchInput = document.getElementById(id + '_search');
                const hiddenInput = document.getElementById(id + '_id');
                const resultsDiv = searchInput?.parentElement.nextElementSibling;

                if (!searchInput || !hiddenInput || !resultsDiv) return;

                let timer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(timer);
                    const q = this.value.trim();

                    if (q.length < 2) {
                        resultsDiv.classList.add('hidden');
                        return;
                    }

                    timer = setTimeout(() => {
                        fetch(
                                `${endpoint}${endpoint.includes('?') ? '&' : '?'}q=${encodeURIComponent(q)}&limit=10`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    credentials: 'same-origin'
                                }
                            )
                            .then(r => r.json())
                            .then(data => displayResults(data.data || data, resultsDiv, hiddenInput,
                                searchInput, labelKey, extraKey, subKey))
                            .catch(e => console.error('Search error:', e));
                    }, 300);
                });

                searchInput.addEventListener('keyup', () => {
                    if (!searchInput.value) hiddenInput.value = '';
                });
            }

            function displayResults(items, resultsDiv, hiddenInput, searchInput, labelKey, extraKey, subKey) {
                resultsDiv.innerHTML = '';

                if (!items?.length) {
                    resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 dark:text-gray-400">Sonuç bulunamadı</div>';
                    resultsDiv.classList.remove('hidden');
                    return;
                }

                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className =
                        'p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0';
                    const label = extraKey ? `${item[labelKey]} ${item[extraKey]}` : item[labelKey];
                    div.innerHTML = `
                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">${label}</div>
                        ${item[subKey] ? `<div class="text-xs text-gray-500 dark:text-gray-400">${item[subKey]}</div>` : ''}
                    `;
                    div.onclick = () => {
                        hiddenInput.value = item.id;
                        searchInput.value = label;
                        resultsDiv.classList.add('hidden');
                    };
                    resultsDiv.appendChild(div);
                });
                resultsDiv.classList.remove('hidden');
            }

            // Close all dropdowns on outside click
            document.addEventListener('click', e => {
                if (!e.target.closest('.context7-live-search')) {
                    document.querySelectorAll('.context7-search-results').forEach(d => d.classList.add('hidden'));
                }
            });
        </script>
    @endpush
    @include('admin.ilanlar.components.quick-client-modal')
    @include('admin.ilanlar.components.cortex-observer-widget')
@endsection
