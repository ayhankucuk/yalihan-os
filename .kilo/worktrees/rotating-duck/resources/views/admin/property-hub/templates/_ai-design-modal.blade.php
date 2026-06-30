{{-- AI ile Tasarla — 4-Step Vanilla JS Wizard Modal --}}

<div id="ai-design-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" aria-hidden="true">
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
        <div
            class="flex items-start justify-between border-b border-slate-200 dark:border-slate-700 bg-blue-50 dark:bg-blue-900/20 px-6 py-4">
            <div>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">AI ile Tasarla</h3>
                <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">Şablon yapısını yapay zeka ile tasarla</p>
            </div>

            <button type="button" id="close-ai-design-modal"
                class="rounded-md p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300">
                ✕
            </button>
        </div>

        <div class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-6 py-4">
            <div class="grid grid-cols-4 gap-3 text-sm">
                <div class="ai-design-step rounded-lg bg-blue-100 dark:bg-blue-900/30 px-3 py-2 font-medium text-blue-700 dark:text-blue-300"
                    data-step="1">
                    1. Senaryo
                </div>
                <div class="ai-design-step rounded-lg bg-slate-100 dark:bg-slate-700 px-3 py-2 font-medium text-slate-500 dark:text-slate-400"
                    data-step="2">
                    2. Hedef
                </div>
                <div class="ai-design-step rounded-lg bg-slate-100 dark:bg-slate-700 px-3 py-2 font-medium text-slate-500 dark:text-slate-400"
                    data-step="3">
                    3. Üretim
                </div>
                <div class="ai-design-step rounded-lg bg-slate-100 dark:bg-slate-700 px-3 py-2 font-medium text-slate-500 dark:text-slate-400"
                    data-step="4">
                    4. Önizleme
                </div>
            </div>
        </div>

        <div class="max-h-[72vh] overflow-y-auto px-6 py-6">
            {{-- STEP 1 --}}
            <div class="ai-design-panel" data-panel="1">
                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Bir senaryo seç</h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">AI tasarımın ne için kullanılacağını seç.
                    </p>
                </div>

                <div id="ai-design-scenario-grid" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @php
                        $scenarios = [
                            [
                                'key' => 'improve_existing',
                                'title' => 'Mevcut Şablonu İyileştir',
                                'desc' => 'Var olan yapıyı optimize eder.',
                            ],
                            [
                                'key' => 'short_term_rental_optimize',
                                'title' => 'Günlük Kiralama Optimize Et',
                                'desc' => 'Kısa dönem kiralama odaklı alanları güçlendirir.',
                            ],
                            [
                                'key' => 'seo_optimized',
                                'title' => 'SEO Odaklı Tasarla',
                                'desc' => 'Arama ve filtreleme için güçlü alanları öne çıkarır.',
                            ],
                            [
                                'key' => 'fast_entry_minimal',
                                'title' => 'Hızlı Giriş İçin Sadeleştir',
                                'desc' => 'Az alanla hızlı ilan girişi için optimize eder.',
                            ],
                        ];
                    @endphp

                    @foreach ($scenarios as $scenario)
                        <button type="button"
                            class="ai-scenario-card rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-left hover:border-blue-400 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                            data-scenario="{{ $scenario['key'] }}">
                            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $scenario['title'] }}</div>
                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $scenario['desc'] }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- STEP 2 --}}
            <div class="ai-design-panel hidden" data-panel="2">
                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hedefi tanımla</h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">AI'ya kısa bir yön ve öncelik ver.</p>
                </div>

                <div class="space-y-5">
                    <div>
                        <label for="ai-design-prompt"
                            class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Kısa açıklama
                        </label>
                        <textarea id="ai-design-prompt" rows="5"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900"
                            placeholder="Örn: Günlük kiralama için hızlı giriş, filtrelenebilirlik ve konfor alanlarını öne çıkar."></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                            <input type="checkbox" id="toggle-seo-focus"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-blue-600">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-200">SEO odaklı</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">Arama ve keşif için önemli
                                    alanları öne çıkar.</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                            <input type="checkbox" id="toggle-fast-entry"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-blue-600">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-200">Hızlı giriş odaklı</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">Az ama etkili alanlarla sade
                                    yapı öner.</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                            <input type="checkbox" id="toggle-premium-mode"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-blue-600">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-200">Premium yapı</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">Daha zengin ve detaylı alan
                                    yapısı öner.</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                            <input type="checkbox" id="toggle-strong-required"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-blue-600">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-200">Zorunlu alanları artır</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">Daha sıkı veri kalitesi için
                                    öneri üret.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div class="ai-design-panel hidden" data-panel="3">
                <div class="py-16 text-center" id="ai-design-spinner">
                    <div
                        class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600">
                    </div>
                    <div class="text-lg font-medium text-slate-800 dark:text-slate-200">AI tasarım üretiyor...</div>
                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Mevcut şablon analiz ediliyor, yeni yapı hazırlanıyor.
                    </div>
                </div>

                <div id="ai-design-progress" class="hidden space-y-3">
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-300">Pipeline durumu</div>
                    <div class="grid grid-cols-6 gap-2 text-xs">
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="normalize">Normalize</div>
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="audit">Audit</div>
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="fix">Fix</div>
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="execution">Execution</div>
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="verification">Verify</div>
                        <div class="design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400"
                            data-progress-step="govern">Govern</div>
                    </div>

                    <div id="ai-design-run-badge"
                        class="inline-flex rounded-full bg-slate-100 dark:bg-slate-700 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                        queued
                    </div>
                </div>
            </div>

            {{-- STEP 4 --}}
            <div class="ai-design-panel hidden" data-panel="4">
                <div id="ai-design-error"
                    class="hidden rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="font-medium text-red-700 dark:text-red-400">Tasarım üretilemedi</div>
                    <div id="ai-design-error-text" class="mt-1 text-sm text-red-600 dark:text-red-300"></div>
                </div>

                <div id="ai-design-blocked"
                    class="hidden rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="font-medium text-red-700 dark:text-red-400">Tasarım uygulanamaz</div>
                    <div id="ai-design-blocked-text" class="mt-1 text-sm text-red-600 dark:text-red-300">
                        Governance katmanı bu tasarımın uygulanmasını engelledi.
                    </div>
                </div>

                <div id="ai-design-caution"
                    class="hidden rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="font-medium text-yellow-700 dark:text-yellow-400">⚠ Dikkatli uygulayın</div>
                    <div id="ai-design-caution-text" class="mt-1 text-sm text-yellow-600 dark:text-yellow-300">
                        AI tasarımı uygulanabilir ancak bazı sinyaller zayıf.
                    </div>
                </div>

                <div id="ai-design-preview" class="hidden">
                    <div class="mb-5 flex items-start justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900 dark:text-slate-100">AI Tasarım Önizlemesi
                            </h4>
                            <p id="ai-design-summary" class="mt-1 text-sm text-slate-500 dark:text-slate-400"></p>
                        </div>

                        <div id="ai-design-decision-badge"
                            class="rounded-full bg-slate-100 dark:bg-slate-700 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                            Bekleniyor
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            <div
                                class="rounded-xl border-l-4 border-l-emerald-500 border border-slate-200 dark:border-slate-700 dark:border-l-emerald-500 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">🟢 Eklenecek
                                        Alanlar</span>
                                    <span id="design-add-count"
                                        class="rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">0</span>
                                </div>
                                <div id="design-add-list" class="space-y-2"></div>
                            </div>

                            <div
                                class="rounded-xl border-l-4 border-l-amber-500 border border-slate-200 dark:border-slate-700 dark:border-l-amber-500 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-semibold text-amber-700 dark:text-amber-400">🟡 Zorunlu
                                        Yapılacaklar</span>
                                    <span id="design-required-count"
                                        class="rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">0</span>
                                </div>
                                <div id="design-required-list" class="space-y-2"></div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="rounded-xl border-l-4 border-l-slate-400 border border-slate-200 dark:border-slate-700 dark:border-l-slate-500 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">⚪ Opsiyonel
                                        Kalacaklar</span>
                                    <span id="design-optional-count"
                                        class="rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-600 dark:text-slate-400">0</span>
                                </div>
                                <div id="design-optional-list" class="space-y-2"></div>
                            </div>

                            <div
                                class="rounded-xl border-l-4 border-l-red-500 border border-slate-200 dark:border-slate-700 dark:border-l-red-500 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-semibold text-red-700 dark:text-red-400">🔴 Kaldırma
                                        Adayları</span>
                                    <span id="design-remove-count"
                                        class="rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">0</span>
                                </div>
                                <div id="design-remove-list" class="space-y-2"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Success + Rollback panel (shown after apply) --}}
                    <div id="ai-design-success"
                        class="mt-6 hidden rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-medium text-emerald-700 dark:text-emerald-400">✓ Tasarım uygulandı
                                </div>
                                <div id="ai-design-success-text"
                                    class="mt-1 text-sm text-emerald-600 dark:text-emerald-300"></div>
                            </div>
                            <button type="button" id="ai-design-rollback-btn"
                                class="rounded-lg border border-red-300 dark:border-red-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                Geri Al
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <div class="mb-3 font-semibold text-slate-800 dark:text-slate-200">Uygulama Seçenekleri</div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label
                                class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                <input type="radio" name="design_apply_mode" value="full" class="mt-1 h-4 w-4"
                                    checked>
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">Tam tasarımı uygula
                                    </div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Eklemeler, zorunlular ve
                                        yapı önerileri dahil.</div>
                                </div>
                            </label>

                            <label
                                class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                <input type="radio" name="design_apply_mode" value="add_only"
                                    class="mt-1 h-4 w-4">
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">Sadece eklemeleri
                                        uygula</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Mevcut yapıyı korur, sadece
                                        yeni alanları ekler.</div>
                                </div>
                            </label>

                            <label
                                class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                <input type="radio" name="design_apply_mode" value="required_only"
                                    class="mt-1 h-4 w-4">
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">Sadece zorunluları
                                        uygula</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Veri kalitesine odaklanır.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-6 py-4">
            <button type="button" id="ai-design-back-btn"
                class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                disabled>
                Geri
            </button>

            <div class="flex items-center gap-3">
                <button type="button" id="ai-design-history-btn"
                    class="hidden rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700"
                    title="Geçmiş uygulamalar">
                    📋 Geçmiş
                </button>

                <button type="button" id="ai-design-cancel-btn"
                    class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                    Vazgeç
                </button>

                <button type="button" id="ai-design-next-btn"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Devam
                </button>

                <button type="button" id="ai-design-apply-btn"
                    class="hidden rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Tasarımı Uygula
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Backend contract config --}}
<script>
    window.templateAiDesignConfig = {
        startUrl: @json(route('admin.property-hub.templates.ai-design.start')),
        pollUrlTemplate: @json(url('admin/property-hub/templates/ai-design/__RUN_ID__/poll')),
        applyUrl: @json(route('admin.property-hub.templates.ai-design.apply')),
        rollbackUrlTemplate: @json(url('admin/property-hub/templates/ai-design/__AUDIT_ID__/rollback')),
        historyUrl: @json(route('admin.property-hub.templates.ai-design.history')),
    };
</script>

{{-- AI Design Wizard: modal + stepper + polling + preview --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var config = window.templateAiDesignConfig || {};
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        var openBtn = document.getElementById('open-ai-design-btn');
        var modal = document.getElementById('ai-design-modal');
        var closeBtn = document.getElementById('close-ai-design-modal');
        var cancelBtn = document.getElementById('ai-design-cancel-btn');
        var backBtn = document.getElementById('ai-design-back-btn');
        var nextBtn = document.getElementById('ai-design-next-btn');
        var applyBtn = document.getElementById('ai-design-apply-btn');

        var panels = Array.from(document.querySelectorAll('.ai-design-panel'));
        var stepTabs = Array.from(document.querySelectorAll('.ai-design-step'));
        var scenarioCards = Array.from(document.querySelectorAll('.ai-scenario-card'));

        var progressBox = document.getElementById('ai-design-progress');
        var spinnerBox = document.getElementById('ai-design-spinner');
        var runBadge = document.getElementById('ai-design-run-badge');

        var errorBox = document.getElementById('ai-design-error');
        var errorText = document.getElementById('ai-design-error-text');
        var blockedBox = document.getElementById('ai-design-blocked');
        var blockedText = document.getElementById('ai-design-blocked-text');
        var cautionBox = document.getElementById('ai-design-caution');
        var cautionText = document.getElementById('ai-design-caution-text');
        var previewBox = document.getElementById('ai-design-preview');
        var summaryText = document.getElementById('ai-design-summary');
        var decisionBadge = document.getElementById('ai-design-decision-badge');

        var addList = document.getElementById('design-add-list');
        var requiredList = document.getElementById('design-required-list');
        var optionalList = document.getElementById('design-optional-list');
        var removeList = document.getElementById('design-remove-list');

        var addCount = document.getElementById('design-add-count');
        var requiredCount = document.getElementById('design-required-count');
        var optionalCount = document.getElementById('design-optional-count');
        var removeCount = document.getElementById('design-remove-count');

        var successBox = document.getElementById('ai-design-success');
        var successText = document.getElementById('ai-design-success-text');
        var rollbackBtn = document.getElementById('ai-design-rollback-btn');
        var historyBtn = document.getElementById('ai-design-history-btn');

        var promptInput = document.getElementById('ai-design-prompt');

        var currentStep = 1;
        var currentRunId = null;
        var pollingTimer = null;
        var selectedScenario = null;
        var latestDesignPayload = null;
        var latestDecision = null;
        var lastAuditId = null;

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            stopPolling();
            resetState();
        }

        function resetState() {
            currentStep = 1;
            currentRunId = null;
            selectedScenario = null;
            latestDesignPayload = null;
            latestDecision = null;
            lastAuditId = null;

            promptInput.value = '';
            document.getElementById('toggle-seo-focus').checked = false;
            document.getElementById('toggle-fast-entry').checked = false;
            document.getElementById('toggle-premium-mode').checked = false;
            document.getElementById('toggle-strong-required').checked = false;

            scenarioCards.forEach(function(card) {
                card.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
            });

            errorBox.classList.add('hidden');
            blockedBox.classList.add('hidden');
            cautionBox.classList.add('hidden');
            previewBox.classList.add('hidden');
            progressBox.classList.add('hidden');
            if (successBox) successBox.classList.add('hidden');
            if (spinnerBox) spinnerBox.classList.remove('hidden');

            addList.innerHTML = '';
            requiredList.innerHTML = '';
            optionalList.innerHTML = '';
            removeList.innerHTML = '';

            summaryText.textContent = '';
            errorText.textContent = '';
            if (addCount) addCount.textContent = '0';
            if (requiredCount) requiredCount.textContent = '0';
            if (optionalCount) optionalCount.textContent = '0';
            if (removeCount) removeCount.textContent = '0';
            decisionBadge.textContent = 'Bekleniyor';
            decisionBadge.className =
                'rounded-full bg-slate-100 dark:bg-slate-700 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-400';

            document.querySelectorAll('.design-progress-step').forEach(function(el) {
                el.className =
                    'design-progress-step rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-2 text-center text-slate-600 dark:text-slate-400';
            });

            goToStep(1);
        }

        function goToStep(step) {
            currentStep = step;

            panels.forEach(function(panel) {
                panel.classList.toggle('hidden', Number(panel.dataset.panel) !== step);
            });

            stepTabs.forEach(function(tab) {
                var tabStep = Number(tab.dataset.step);
                tab.classList.remove(
                    'bg-blue-100', 'text-blue-700', 'dark:bg-blue-900/30', 'dark:text-blue-300',
                    'bg-green-100', 'text-green-700', 'dark:bg-green-900/30', 'dark:text-green-300',
                    'bg-slate-100', 'text-slate-500', 'dark:bg-slate-700', 'dark:text-slate-400'
                );

                if (tabStep === step) {
                    tab.classList.add('bg-blue-100', 'text-blue-700', 'dark:bg-blue-900/30',
                        'dark:text-blue-300');
                } else if (tabStep < step) {
                    tab.classList.add('bg-green-100', 'text-green-700', 'dark:bg-green-900/30',
                        'dark:text-green-300');
                } else {
                    tab.classList.add('bg-slate-100', 'text-slate-500', 'dark:bg-slate-700',
                        'dark:text-slate-400');
                }
            });

            backBtn.disabled = step === 1;
            nextBtn.classList.toggle('hidden', step >= 3);
            applyBtn.classList.toggle('hidden', step !== 4);
            if (historyBtn) historyBtn.classList.toggle('hidden', step !== 4);
        }

        function stopPolling() {
            if (pollingTimer) {
                clearTimeout(pollingTimer);
                pollingTimer = null;
            }
        }

        function setProgressStepState(stepName, state) {
            var el = document.querySelector('[data-progress-step="' + stepName + '"]');
            if (!el) return;

            el.classList.remove(
                'bg-slate-100', 'bg-blue-100', 'bg-green-100', 'bg-red-100',
                'text-slate-600', 'text-blue-700', 'text-green-700', 'text-red-700',
                'dark:bg-slate-700', 'dark:bg-blue-900/30', 'dark:bg-green-900/30', 'dark:bg-red-900/30',
                'dark:text-slate-400', 'dark:text-blue-300', 'dark:text-green-300', 'dark:text-red-300'
            );

            if (state === 'running') {
                el.classList.add('bg-blue-100', 'text-blue-700', 'dark:bg-blue-900/30', 'dark:text-blue-300');
            } else if (state === 'completed') {
                el.classList.add('bg-green-100', 'text-green-700', 'dark:bg-green-900/30',
                    'dark:text-green-300');
            } else if (state === 'failed' || state === 'blocked') {
                el.classList.add('bg-red-100', 'text-red-700', 'dark:bg-red-900/30', 'dark:text-red-300');
            } else {
                el.classList.add('bg-slate-100', 'text-slate-600', 'dark:bg-slate-700', 'dark:text-slate-400');
            }
        }

        function updateProgress(data) {
            progressBox.classList.remove('hidden');
            if (spinnerBox) spinnerBox.classList.add('hidden');
            runBadge.textContent = data.durum || 'running';

            var steps = Array.isArray(data.steps) ? data.steps : [];

            steps.forEach(function(step) {
                setProgressStepState(step.step_name, step.durum);
            });
        }

        function renderDecision(decision) {
            latestDecision = decision;

            if (decision === 'proceed') {
                decisionBadge.className =
                    'rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-xs font-medium text-green-700 dark:text-green-300';
                decisionBadge.textContent = 'PROCEED';
                applyBtn.disabled = false;
                applyBtn.classList.remove('hidden');
                blockedBox.classList.add('hidden');
                cautionBox.classList.add('hidden');
            } else if (decision === 'block') {
                decisionBadge.className =
                    'rounded-full bg-red-100 dark:bg-red-900/30 px-3 py-1 text-xs font-medium text-red-700 dark:text-red-300';
                decisionBadge.textContent = 'BLOCK';
                applyBtn.disabled = true;
                applyBtn.classList.add('hidden');
                blockedBox.classList.remove('hidden');
                cautionBox.classList.add('hidden');
            } else {
                // proceed_with_caution or unknown
                decisionBadge.className =
                    'rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-3 py-1 text-xs font-medium text-yellow-700 dark:text-yellow-300';
                decisionBadge.textContent = 'CAUTION';
                applyBtn.disabled = false;
                applyBtn.classList.remove('hidden');
                blockedBox.classList.add('hidden');
                cautionBox.classList.remove('hidden');
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function renderList(container, items, emptyText, countEl) {
            container.innerHTML = '';

            var count = Array.isArray(items) ? items.length : 0;
            if (countEl) countEl.textContent = String(count);

            if (!Array.isArray(items) || items.length === 0) {
                container.innerHTML = '<div class="text-sm text-slate-400 dark:text-slate-500">' + escapeHtml(
                    emptyText) + '</div>';
                return;
            }

            items.forEach(function(item) {
                var row = document.createElement('div');
                row.className = 'rounded-lg border border-slate-200 dark:border-slate-700 p-3';

                var name = escapeHtml(item.name || item.slug || 'Alan');
                var slug = escapeHtml(item.slug || '');
                var type = escapeHtml(item.type || item.mode || 'field');
                var reason = item.reason ?
                    '<div class="mt-2 text-sm text-slate-600 dark:text-slate-400">' + escapeHtml(item
                        .reason) + '</div>' : '';

                row.innerHTML =
                    '<div class="flex items-center justify-between gap-3">' +
                    '<div>' +
                    '<div class="font-medium text-slate-800 dark:text-slate-200">' + name + '</div>' +
                    '<div class="mt-1 text-xs text-slate-500 dark:text-slate-400">' + slug + '</div>' +
                    '</div>' +
                    '<div class="rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs text-slate-600 dark:text-slate-400">' +
                    type + '</div>' +
                    '</div>' +
                    reason;

                container.appendChild(row);
            });
        }

        function renderPreview(result, decision, decisionReason) {
            latestDesignPayload = result;

            var design = result?.design || {};

            summaryText.textContent = result?.summary || decisionReason || 'Tasarım hazırlandı.';
            renderDecision(decision);

            // Update caution/blocked reason text from governance
            if (decisionReason) {
                if (decision === 'block') {
                    blockedText.textContent = decisionReason;
                } else if (decision !== 'proceed') {
                    cautionText.textContent = decisionReason;
                }
            }

            renderList(addList, design.add || [], 'Ek önerisi yok.', addCount);
            renderList(requiredList, design.make_required || [], 'Zorunlu önerisi yok.', requiredCount);
            renderList(optionalList, design.keep_optional || [], 'Opsiyonel liste yok.', optionalCount);
            renderList(removeList, design.remove_candidates || [], 'Kaldırma adayı yok.', removeCount);

            errorBox.classList.add('hidden');
            previewBox.classList.remove('hidden');
        }

        function showError(message) {
            previewBox.classList.add('hidden');
            blockedBox.classList.add('hidden');
            cautionBox.classList.add('hidden');
            errorBox.classList.remove('hidden');
            errorText.textContent = message || 'Tasarım üretilemedi.';
            applyBtn.classList.add('hidden');
        }

        function collectPayload() {
            return {
                kategori_id: Number(openBtn.dataset.kategoriId),
                yayin_tipi_id: Number(openBtn.dataset.yayinTipiId),
                scope: openBtn.dataset.scope || 'master',
                scenario: selectedScenario,
                user_prompt: promptInput.value.trim(),
                toggles: {
                    seo_focus: document.getElementById('toggle-seo-focus').checked,
                    fast_entry: document.getElementById('toggle-fast-entry').checked,
                    premium_mode: document.getElementById('toggle-premium-mode').checked,
                    strong_required: document.getElementById('toggle-strong-required').checked,
                }
            };
        }

        async function startDesignPipeline() {
            try {
                var response = await fetch(config.startUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(collectPayload()),
                });

                var data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'AI tasarım pipeline başlatılamadı.');
                }

                currentRunId = data.run_id;
                goToStep(3);
                pollDesignRun();
            } catch (error) {
                goToStep(4);
                showError(error.message || 'Başlatma sırasında hata oluştu.');
            }
        }

        async function pollDesignRun() {
            if (!currentRunId) return;

            var url = (config.pollUrlTemplate || '').replace('__RUN_ID__', String(currentRunId));

            try {
                var response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                var data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Pipeline sonucu okunamadı.');
                }

                updateProgress(data);

                if (data.durum === 'completed') {
                    goToStep(4);
                    renderPreview(data.result || {}, data.decision, data.decision_reason);
                    return;
                }

                if (data.durum === 'failed' || data.durum === 'halted') {
                    goToStep(4);
                    showError(data.decision_reason || 'Pipeline tamamlanamadı.');
                    return;
                }

                pollingTimer = setTimeout(pollDesignRun, 1500);
            } catch (error) {
                goToStep(4);
                showError(error.message || 'Polling sırasında hata oluştu.');
            }
        }

        async function applyDesign() {
            if (!latestDesignPayload) return;

            var selectedMode = document.querySelector('input[name="design_apply_mode"]:checked')?.value ||
                'full';

            try {
                applyBtn.disabled = true;
                applyBtn.textContent = 'Uygulanıyor...';

                var payload = {
                    kategori_id: Number(openBtn.dataset.kategoriId),
                    yayin_tipi_id: Number(openBtn.dataset.yayinTipiId),
                    scope: openBtn.dataset.scope || 'master',
                    apply_mode: selectedMode,
                    design_payload: latestDesignPayload,
                };

                var response = await fetch(config.applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                var data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'Tasarım uygulanamadı.');
                }

                // Show success panel with rollback option
                lastAuditId = data.audit_id || null;
                applyBtn.classList.add('hidden');

                if (successBox) {
                    successBox.classList.remove('hidden');
                    successText.textContent = data.message || 'Tasarım başarıyla uygulandı.';

                    if (rollbackBtn && lastAuditId) {
                        rollbackBtn.classList.remove('hidden');
                    } else if (rollbackBtn) {
                        rollbackBtn.classList.add('hidden');
                    }
                }

                // Auto-reload after 4 seconds unless user clicks rollback
                setTimeout(function() {
                    if (!successBox.classList.contains('hidden')) {
                        window.location.reload();
                    }
                }, 4000);
            } catch (error) {
                applyBtn.disabled = false;
                applyBtn.textContent = 'Tasarımı Uygula';
                showError(error.message || 'Uygulama sırasında hata oluştu.');
            }
        }

        async function rollbackDesign() {
            if (!lastAuditId) return;

            var url = (config.rollbackUrlTemplate || '').replace('__AUDIT_ID__', String(lastAuditId));

            try {
                rollbackBtn.disabled = true;
                rollbackBtn.textContent = 'Geri alınıyor...';

                var response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                var data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'Geri alma başarısız.');
                }

                successText.textContent = data.message || 'Değişiklikler geri alındı.';
                rollbackBtn.classList.add('hidden');

                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } catch (error) {
                rollbackBtn.disabled = false;
                rollbackBtn.textContent = 'Geri Al';
                showError(error.message || 'Geri alma sırasında hata oluştu.');
            }
        }

        async function loadHistory() {
            var yayinTipiId = Number(openBtn.dataset.yayinTipiId);
            if (!yayinTipiId) return;

            var url = config.historyUrl + '?yayin_tipi_id=' + yayinTipiId;

            try {
                var response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    },
                });

                var data = await response.json();

                if (!response.ok || !data.ok) return;

                var audits = data.audits || [];
                if (audits.length === 0) {
                    alert('Bu şablon için henüz uygulama geçmişi yok.');
                    return;
                }

                var lines = audits.map(function(a) {
                    var date = new Date(a.created_at).toLocaleString('tr-TR');
                    var status = a.rolled_back ? ' [GERİ ALINDI]' : '';
                    var changes = a.changes || {};
                    var added = (changes.added || []).length;
                    var required = (changes.made_required || []).length;
                    return date + ' — ' + (a.user_name || '?') + ' — ' +
                        a.apply_mode + ' — +' + added + ' eklendi, ' + required + ' zorunlu' +
                        status;
                });

                alert('Son uygulamalar:\n\n' + lines.join('\n'));
            } catch (error) {
                // Silent fail — history is non-critical
            }
        }

        // Scenario card click
        scenarioCards.forEach(function(card) {
            card.addEventListener('click', function() {
                selectedScenario = this.dataset.scenario;

                scenarioCards.forEach(function(c) {
                    c.classList.remove('border-blue-500', 'bg-blue-50',
                        'dark:bg-blue-900/30');
                });
                this.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
            });
        });

        // Open
        openBtn?.addEventListener('click', function() {
            resetState();
            openModal();
        });

        // Close
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        // Back
        backBtn?.addEventListener('click', function() {
            if (currentStep > 1 && currentStep < 3) {
                goToStep(currentStep - 1);
            }
        });

        // Next / Start
        nextBtn?.addEventListener('click', function() {
            if (currentStep === 1) {
                if (!selectedScenario) {
                    alert('Lütfen bir senaryo seç.');
                    return;
                }
                goToStep(2);
                return;
            }

            if (currentStep === 2) {
                startDesignPipeline();
            }
        });

        // Apply
        applyBtn?.addEventListener('click', applyDesign);

        // Rollback
        rollbackBtn?.addEventListener('click', rollbackDesign);

        // History
        historyBtn?.addEventListener('click', loadHistory);

        // Backdrop click
        modal?.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
