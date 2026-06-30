{{-- Copilot Widget — Context-aware AI assistant panel --}}
<div x-data="copilotWidget()" x-init="init()" class="fixed bottom-24 right-6 z-50" id="copilot-widget">
    {{-- Toggle Button --}}
    <button @click="toggle()"
        class="flex h-14 w-14 items-center justify-center rounded-full shadow-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        :class="open ? 'bg-gray-700 dark:bg-slate-600' : (hasIssues ? 'bg-amber-500 dark:bg-amber-600' :
            'bg-blue-600 dark:bg-blue-500')"
        :aria-expanded="open.toString()" aria-label="Copilot Asistan">
        {{-- Brain icon --}}
        <svg x-show="!open" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
        </svg>
        {{-- Close icon --}}
        <svg x-show="open" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        {{-- Badge for issues --}}
        <span x-show="!open && issueCount > 0" x-text="issueCount"
            class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"></span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="absolute bottom-16 right-0 w-96 rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
        @click.away="open = false">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Copilot</h3>
                <span x-show="contextLabel" x-text="contextLabel"
                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"></span>
            </div>
            <button @click="fetchInsights()" :disabled="loading"
                class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-white"
                aria-label="Yenile">
                <svg class="h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="max-h-[28rem] overflow-y-auto">
            {{-- Loading state --}}
            <template x-if="loading && !result">
                <div class="flex items-center justify-center py-12">
                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
                </div>
            </template>

            {{-- Error state --}}
            <template x-if="error">
                <div class="px-4 py-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-slate-400" x-text="error"></p>
                    <button @click="fetchInsights()" class="mt-2 text-sm text-blue-500 hover:underline">Tekrar
                        Dene</button>
                </div>
            </template>

            {{-- Results --}}
            <template x-if="result && !error">
                <div>
                    {{-- Summary --}}
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-slate-700">
                        <p class="text-sm text-gray-600 dark:text-slate-300" x-text="result.summary"></p>
                    </div>

                    {{-- Predictions bar --}}
                    <template x-if="result.predictions && result.predictions.health_score !== null">
                        <div class="border-b border-gray-100 px-4 py-3 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Sağlık Skoru</span>
                                <span class="text-sm font-bold" :class="scoreColor(result.predictions.health_score)"
                                    x-text="'%' + result.predictions.health_score"></span>
                            </div>
                            <div class="mt-1.5 h-2 w-full rounded-full bg-gray-100 dark:bg-slate-700">
                                <div class="h-2 rounded-full transition-all duration-500"
                                    :class="scoreBarColor(result.predictions.health_score)"
                                    :style="'width: ' + result.predictions.health_score + '%'"></div>
                            </div>
                            <template x-if="result.predictions.deal_probability">
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-slate-400">Satış Olasılığı</span>
                                    <span class="text-xs font-medium"
                                        :class="scoreColor(result.predictions.deal_probability)"
                                        x-text="'%' + result.predictions.deal_probability"></span>
                                </div>
                            </template>
                            <template x-if="result.confidence">
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-slate-400">Güven</span>
                                    <span class="text-xs font-medium" :class="confidenceColor(result.confidence.score)"
                                        x-text="result.confidence.label + ' (%' + result.confidence.score + ')'"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Tab Navigation --}}
                    <div class="flex border-b border-gray-100 dark:border-slate-700">
                        <button @click="tab = 'actions'" class="flex-1 px-4 py-2 text-xs font-medium transition-colors"
                            :class="tab === 'actions'
                                ?
                                'border-b-2 border-green-500 text-green-600 dark:text-green-400' :
                                'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300'">
                            Aksiyonlar
                            <span x-show="copilotActions.length > 0" x-text="'(' + copilotActions.length + ')'"
                                class="ml-1"></span>
                        </button>
                        <button @click="tab = 'insights'" class="flex-1 px-4 py-2 text-xs font-medium transition-colors"
                            :class="tab === 'insights'
                                ?
                                'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300'">
                            Öneriler
                            <span x-show="result.insights?.length > 0" x-text="'(' + (result.insights?.length ?? 0) + ')'"
                                class="ml-1"></span>
                        </button>
                        <button @click="tab = 'audit'" class="flex-1 px-4 py-2 text-xs font-medium transition-colors"
                            :class="tab === 'audit'
                                ?
                                'border-b-2 border-orange-500 text-orange-600 dark:text-orange-400' :
                                'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300'">
                            Denetim
                            <span x-show="result.audit?.total_count > 0" x-text="'(' + (result.audit?.total_count ?? 0) + ')'"
                                class="ml-1"></span>
                        </button>
                    </div>

                    {{-- Actions tab --}}
                    <div x-show="tab === 'actions'">
                        {{-- Auto-run CTA --}}
                        <div class="border-b border-gray-100 px-4 py-3 dark:border-slate-700">
                            <div class="flex items-center gap-2">
                                <button @click="fetchCopilotActions('suggest')" :disabled="actionsLoading"
                                    class="flex-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40">
                                    <span x-show="!actionsLoading">AI Önerilerini Getir</span>
                                    <span x-show="actionsLoading" class="flex items-center justify-center gap-1">
                                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Üretiliyor...
                                    </span>
                                </button>
                                <button @click="fetchCopilotActions('full_generate')" :disabled="actionsLoading"
                                    class="rounded-lg bg-green-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                                    title="Tam İlan Üret">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Action list --}}
                        <div class="divide-y divide-gray-50 dark:divide-slate-700/50">
                            <template x-for="(action, i) in copilotActions" :key="action.id">
                                <div class="px-4 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                    <div class="flex items-start gap-3">
                                        {{-- Confidence indicator --}}
                                        <div class="mt-1 flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-[10px] font-bold border-2"
                                                :class="action.confidence >= 0.7 ?
                                                    'border-green-400 text-green-600 dark:text-green-400' :
                                                    (action.confidence >= 0.5 ?
                                                        'border-yellow-400 text-yellow-600 dark:text-yellow-400' :
                                                        'border-red-400 text-red-600 dark:text-red-400')"
                                                x-text="Math.round(action.confidence * 100)"></div>
                                        </div>
                                        {{-- Content --}}
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                x-text="action.label"></p>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400"
                                                x-text="action.description"></p>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                                                <span x-text="action.target"></span>
                                                <span class="mx-1">→</span>
                                                <span class="font-medium text-gray-600 dark:text-slate-300"
                                                    x-text="typeof action.value === 'string' ? action.value.substring(0, 50) + (action.value.length > 50 ? '…' : '') : action.value"></span>
                                            </p>
                                            {{-- Action buttons --}}
                                            <div class="mt-2 flex items-center gap-2">
                                                <button @click="applySingleAction(action)"
                                                    class="rounded px-2 py-1 text-[11px] font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40 transition-colors">
                                                    Uygula
                                                </button>
                                                <button @click="previewAction(action)"
                                                    class="rounded px-2 py-1 text-[11px] font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition-colors">
                                                    Önizle
                                                </button>
                                                <template x-if="action.alternatives?.length > 0">
                                                    <button @click="showAlternatives(action)"
                                                        class="rounded px-2 py-1 text-[11px] font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 transition-colors">
                                                        Alternatifler (<span
                                                            x-text="action.alternatives.length"></span>)
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Empty state --}}
                        <template x-if="copilotActions.length === 0 && !actionsLoading">
                            <div class="px-4 py-8 text-center">
                                <svg class="mx-auto h-8 w-8 text-gray-300 dark:text-slate-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Henüz aksiyonlar üretilmedi
                                </p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Yukarıdan "AI Önerilerini
                                    Getir" tıklayın</p>
                            </div>
                        </template>

                        {{-- Undo bar --}}
                        <template x-if="undoAvailable">
                            <div class="border-t border-gray-100 px-4 py-2 dark:border-slate-700">
                                <button @click="undoLastAction()"
                                    class="flex w-full items-center justify-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                    Son İşlemi Geri Al
                                </button>
                            </div>
                        </template>

                        {{-- Preview all button --}}
                        <template x-if="copilotActions.length > 1">
                            <div class="border-t border-gray-100 px-4 py-2 dark:border-slate-700">
                                <button @click="previewAllActions()"
                                    class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                    Tümünü Önizle & Uygula
                                </button>
                            </div>
                        </template>
                    </div>{{-- end actions tab --}}

                    {{-- Insights tab --}}
                    <div x-show="tab === 'insights'">
                        {{-- Insights list --}}
                        <div class="divide-y divide-gray-50 dark:divide-slate-700/50">
                            <template x-for="(insight, i) in result.insights" :key="i">
                                <div class="px-4 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                    <div class="flex items-start gap-3">
                                        {{-- Icon --}}
                                        <div class="mt-0.5 flex-shrink-0 rounded-lg p-1.5"
                                            :class="insightBg(insight.tip)">
                                            <svg class="h-4 w-4" :class="insightIconColor(insight.tip)"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <template x-if="insight.tip === 'critical'">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </template>
                                                <template x-if="insight.tip === 'warning'">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                </template>
                                                <template x-if="insight.tip === 'info'">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                </template>
                                                <template x-if="insight.tip === 'tip'">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                                </template>
                                            </svg>
                                        </div>
                                        {{-- Text --}}
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                x-text="insight.title"></p>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400"
                                                x-text="insight.description"></p>
                                            <template x-if="insight.action">
                                                <a :href="insight.action.url"
                                                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                    x-text="insight.action.label + ' →'"></a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Empty state --}}
                        <template x-if="result.insights.length === 0">
                            <div class="px-4 py-8 text-center">
                                <svg class="mx-auto h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Her şey yolunda!</p>
                            </div>
                        </template>
                    </div>{{-- end insights tab --}}

                    {{-- Audit tab --}}
                    <div x-show="tab === 'audit'">
                        <template x-if="result.audit?.findings?.length > 0">
                            <div class="divide-y divide-gray-50 dark:divide-slate-700/50">
                                <template x-for="(finding, i) in result.audit.findings" :key="'a' + i">
                                    <div
                                        class="px-4 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 flex-shrink-0 rounded-lg p-1.5"
                                                :class="auditBg(finding.severity)">
                                                <svg class="h-4 w-4" :class="auditIconColor(finding.severity)"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                        x-text="finding.title"></p>
                                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                                                        :class="auditBg(finding.severity)"
                                                        x-text="auditLabel(finding.severity)"></span>
                                                </div>
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400"
                                                    x-text="finding.description"></p>
                                                <template x-if="finding.fix_url">
                                                    <a :href="finding.fix_url"
                                                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">Düzelt
                                                        →</a>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!result.audit?.findings?.length">
                            <div class="px-4 py-8 text-center">
                                <svg class="mx-auto h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Denetim bulgusu yok</p>
                            </div>
                        </template>
                    </div>{{-- end audit tab --}}

                    {{-- Next Action --}}
                    <template x-if="result.next_action">
                        <div class="border-t border-gray-100 px-4 py-3 dark:border-slate-700">
                            <p
                                class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">
                                Sonraki Adım</p>
                            <a :href="result.next_action.url || '#'"
                                class="group flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 transition-colors hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:hover:bg-blue-900/40">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300"
                                        x-text="result.next_action.title"></p>
                                    <p class="text-xs text-blue-600/70 dark:text-blue-400/70"
                                        x-text="result.next_action.description"></p>
                                </div>
                                <svg class="h-4 w-4 text-blue-500 transition-transform group-hover:translate-x-0.5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-100 px-4 py-2 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-[10px] text-gray-400 dark:text-slate-500"
                    x-text="result?.meta?.duration_ms ? result.meta.duration_ms + 'ms' : ''"></span>
                <span class="text-[10px] text-gray-400 dark:text-slate-500">Yalıhan Copilot v2.0</span>
            </div>
        </div>
    </div>

    {{-- Diff Preview Modal --}}
    @include('admin.components.copilot-diff-modal')
</div>
<script>
    function copilotWidget() {
        return {
            open: false,
            loading: false,
            result: null,
            error: null,
            tab: 'insights',
            currentRoute: '{{ \Illuminate\Support\Facades\Route::currentRouteName() ?? '' }}',
            entityId: {{ $copilotEntityId ?? 'null' }},

            // --- Actions state ---
            copilotActions: [],
            actionsLoading: false,
            actionsError: null,
            currentLogId: null,
            undoStack: [],
            showDiffModal: false,
            diffItems: [],

            get undoAvailable() {
                return this.undoStack.length > 0;
            },

            get hasIssues() {
                if (!this.result) return false;
                const insightIssues = this.result.insights?.some(i => i.tip === 'critical' || i.tip ===
                    'warning') || false;
                const auditIssues = (this.result.audit?.critical_count > 0 || this.result.audit?.high_count > 0) ||
                    false;
                return insightIssues || auditIssues;
            },

            get issueCount() {
                if (!this.result) return 0;
                const insightCount = this.result.insights?.filter(i => i.tip === 'critical' || i.tip ===
                    'warning').length || 0;
                const auditCount = (this.result.audit?.critical_count || 0) + (this.result.audit?.high_count || 0);
                return insightCount + auditCount;
            },

            get contextLabel() {
                const labels = {
                    'dashboard': 'Dashboard',
                    'ilan-detail': 'İlan',
                    'ilan-edit': 'İlan Düzenle',
                    'ilan-create': 'Yeni İlan',
                    'ilan-list': 'İlanlar',
                    'crm-detail': 'Kişi',
                    'crm-edit': 'Kişi Düzenle',
                    'crm-list': 'CRM',
                    'crm-dashboard': 'CRM',
                    'talep-detail': 'Talep',
                    'talep-list': 'Talepler',
                    'property-hub': 'Property Hub',
                };
                return labels[this.result?.context?.tip] || '';
            },

            init() {
                this.fetchInsights();
            },

            async toggle() {
                this.open = !this.open;
                if (this.open && !this.result) {
                    await this.fetchInsights();
                }
            },

            async fetchInsights() {
                this.loading = true;
                this.error = null;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const response = await fetch('/admin/copilot/insights', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            route: this.currentRoute,
                            entity_id: this.entityId,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    this.result = await response.json();
                } catch (e) {
                    console.error('Copilot fetch error:', e);
                    this.error = 'Copilot şu an yanıt veremiyor.';
                } finally {
                    this.loading = false;
                }
            },

            scoreColor(score) {
                if (score >= 80) return 'text-green-600 dark:text-green-400';
                if (score >= 60) return 'text-blue-600 dark:text-blue-400';
                if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
                return 'text-red-600 dark:text-red-400';
            },

            scoreBarColor(score) {
                if (score >= 80) return 'bg-green-500';
                if (score >= 60) return 'bg-blue-500';
                if (score >= 40) return 'bg-yellow-500';
                return 'bg-red-500';
            },

            insightBg(tip) {
                return {
                    'critical': 'bg-red-50 dark:bg-red-900/20',
                    'warning': 'bg-yellow-50 dark:bg-yellow-900/20',
                    'info': 'bg-blue-50 dark:bg-blue-900/20',
                    'tip': 'bg-purple-50 dark:bg-purple-900/20',
                } [tip] || 'bg-gray-50 dark:bg-slate-700';
            },

            insightIconColor(tip) {
                return {
                    'critical': 'text-red-500 dark:text-red-400',
                    'warning': 'text-yellow-500 dark:text-yellow-400',
                    'info': 'text-blue-500 dark:text-blue-400',
                    'tip': 'text-purple-500 dark:text-purple-400',
                } [tip] || 'text-gray-500';
            },

            auditBg(severity) {
                return {
                    'critical': 'bg-red-50 dark:bg-red-900/20',
                    'high': 'bg-orange-50 dark:bg-orange-900/20',
                    'medium': 'bg-yellow-50 dark:bg-yellow-900/20',
                    'low': 'bg-blue-50 dark:bg-blue-900/20',
                    'info': 'bg-gray-50 dark:bg-slate-700',
                } [severity] || 'bg-gray-50 dark:bg-slate-700';
            },

            auditIconColor(severity) {
                return {
                    'critical': 'text-red-500 dark:text-red-400',
                    'high': 'text-orange-500 dark:text-orange-400',
                    'medium': 'text-yellow-500 dark:text-yellow-400',
                    'low': 'text-blue-500 dark:text-blue-400',
                    'info': 'text-gray-500 dark:text-gray-400',
                } [severity] || 'text-gray-500';
            },

            auditLabel(severity) {
                return {
                    'critical': 'Kritik',
                    'high': 'Yüksek',
                    'medium': 'Orta',
                    'low': 'Düşük',
                    'info': 'Bilgi',
                } [severity] || severity;
            },

            confidenceColor(score) {
                if (score >= 75) return 'text-green-600 dark:text-green-400';
                if (score >= 50) return 'text-yellow-600 dark:text-yellow-400';
                return 'text-red-600 dark:text-red-400';
            },

            // --- Copilot Actions methods ---

            async fetchCopilotActions(mode = 'suggest') {
                this.actionsLoading = true;
                this.actionsError = null;
                this.tab = 'actions';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    // Build form state from wizard Alpine component if available
                    const formState = this._collectWizardFormState();

                    const response = await fetch('/admin/copilot/actions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            form_state: formState,
                            mode: mode,
                            ilan_id: this.entityId,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'HTTP ' + response.status);

                    this.copilotActions = data.actions || [];
                    this.currentLogId = data.log_id || null;
                } catch (e) {
                    console.error('CopilotActions fetch error:', e);
                    this.actionsError = e.message;
                } finally {
                    this.actionsLoading = false;
                }
            },

            applySingleAction(action) {
                const wizardComp = this._findWizardComponent();
                if (!wizardComp) {
                    alert('Wizard formu bulunamadı. Bu özellik yalnızca ilan wizard sayfasında çalışır.');
                    return;
                }

                const previousValue = this._getNestedValue(wizardComp, action.target);

                // Push to undo stack
                this.undoStack.push({
                    action,
                    previousValue,
                    target: action.target
                });

                // Apply value
                this._setNestedValue(wizardComp, action.target, action.value);

                // Trigger dependency re-evaluation
                window.dispatchEvent(new CustomEvent('copilot:action-applied', {
                    detail: {
                        action,
                        target: action.target,
                        value: action.value
                    },
                }));

                // Visual feedback — remove from list
                this.copilotActions = this.copilotActions.filter(a => a.id !== action.id);
            },

            previewAction(action) {
                const wizardComp = this._findWizardComponent();
                this.diffItems = [{
                    field: action.target,
                    label: action.label,
                    current: wizardComp ? (this._getNestedValue(wizardComp, action.target) ?? '') : '',
                    proposed: action.value,
                    confidence: action.confidence,
                    tip: action.tip,
                    source: action.source || '',
                }];
                this.showDiffModal = true;
            },

            previewAllActions() {
                const wizardComp = this._findWizardComponent();
                this.diffItems = this.copilotActions.map(action => ({
                    field: action.target,
                    label: action.label,
                    current: wizardComp ? (this._getNestedValue(wizardComp, action.target) ?? '') : '',
                    proposed: action.value,
                    confidence: action.confidence,
                    tip: action.tip,
                    source: action.source || '',
                }));
                this.showDiffModal = true;
            },

            applyAllActions() {
                const wizardComp = this._findWizardComponent();
                if (!wizardComp) return;

                for (const action of this.copilotActions) {
                    const previousValue = this._getNestedValue(wizardComp, action.target);
                    this.undoStack.push({
                        action,
                        previousValue,
                        target: action.target
                    });
                    this._setNestedValue(wizardComp, action.target, action.value);
                }

                window.dispatchEvent(new CustomEvent('copilot:all-actions-applied', {
                    detail: {
                        count: this.copilotActions.length
                    },
                }));

                this.copilotActions = [];
                this.showDiffModal = false;
            },

            rejectAllActions() {
                this.copilotActions = [];
                this.showDiffModal = false;
            },

            undoLastAction() {
                if (this.undoStack.length === 0) return;
                const entry = this.undoStack.pop();
                const wizardComp = this._findWizardComponent();
                if (wizardComp) {
                    this._setNestedValue(wizardComp, entry.target, entry.previousValue);
                    window.dispatchEvent(new CustomEvent('copilot:action-undone', {
                        detail: {
                            action: entry.action
                        },
                    }));
                }
            },

            showAlternatives(action) {
                if (!action.alternatives?.length) return;
                // Replace the action value with the first alternative for preview
                this.diffItems = action.alternatives.map((alt, i) => ({
                    field: action.target,
                    label: action.label + ' — Alternatif ' + (i + 1),
                    current: action.value,
                    proposed: alt,
                    confidence: action.confidence * 0.9,
                    tip: action.tip,
                    source: 'alternative',
                }));
                this.showDiffModal = true;
            },

            // --- Helper methods ---

            _collectWizardFormState() {
                const state = {};
                const fieldsToRead = [
                    'ana_kategori_id', 'yayin_tipi_id', 'il_id', 'ilce_id', 'mahalle_id',
                    'baslik', 'aciklama', 'fiyat', 'alan_m2', 'para_birimi', 'features'
                ];

                const wizardComp = this._findWizardComponent();
                if (wizardComp) {
                    for (const field of fieldsToRead) {
                        if (wizardComp[field] !== undefined) {
                            state[field] = wizardComp[field];
                        }
                    }
                }

                return state;
            },

            _findWizardComponent() {
                // Try to find wizard Alpine.js component
                const wizardEl = document.querySelector(
                    '[x-data]#wizard-form, [x-data*="wizardMain"], [x-data*="ilanWizard"]');
                if (wizardEl) {
                    return wizardEl._x_dataStack?.[0] || null;
                }
                return null;
            },

            _getNestedValue(obj, path) {
                const keys = path.split('.');
                let current = obj;
                for (const key of keys) {
                    if (current == null) return undefined;
                    current = current[key];
                }
                return current;
            },

            _setNestedValue(obj, path, value) {
                const keys = path.split('.');
                let current = obj;
                for (let i = 0; i < keys.length - 1; i++) {
                    if (current[keys[i]] == null) current[keys[i]] = {};
                    current = current[keys[i]];
                }
                current[keys[keys.length - 1]] = value;
            },
        };
    }
</script>
