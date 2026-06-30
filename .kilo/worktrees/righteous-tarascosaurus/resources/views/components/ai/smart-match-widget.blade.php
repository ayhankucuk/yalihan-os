{{-- Smart Match Widget Component --}}
{{-- Context7: Akıllı İlan Eşleştirme Widget'ı - SmartPropertyMatcherAI entegrasyonu --}}
@props(['talepId'])

<div class="smart-match-widget bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 overflow-hidden dark:border-slate-700"
     x-data="smartMatchWidget({{ $talepId ?? 'null' }})"
     x-init="init()"
     x-cloak>

    {{-- Widget Header --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-magic text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        Akıllı Eşleştirme
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="statusText"></p>
                </div>
            </div>
            <button @click="refresh()"
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                    :disabled="loading">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
            </button>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="p-6">
        <div class="space-y-4">
            {{-- Skeleton Loader --}}
            <div class="animate-pulse space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                </div>
            </div>

            {{-- AI Analiz Ediyor Mesajı --}}
            <div class="flex items-center justify-center space-x-2 text-blue-600 dark:text-blue-400 mt-4">
                <i class="fas fa-brain animate-pulse"></i>
                <span class="text-sm font-medium">AI analiz ediyor...</span>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && matches.length === 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="p-8 text-center">
        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-slate-900">
            <i class="fas fa-search text-gray-400 dark:text-gray-500 text-2xl"></i>
        </div>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            Henüz uygun ilan bulunamadı
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Kriterlerinize uygun ilan bulunamadı. Lütfen arama kriterlerinizi genişletin.
        </p>
        <button @click="refresh()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                       text-white rounded-lg transition-all duration-200 text-sm font-medium">
            Tekrar Dene
        </button>
    </div>

    {{-- Matches List --}}
    <div x-show="!loading && matches.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="p-6 space-y-4">

        {{-- Results Count --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="matches.length"></span> ilan bulundu
            </p>
        </div>

        {{-- Match Cards --}}
        <div class="grid grid-cols-1 gap-4">
            <template x-for="(match, index) in matches" :key="index">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg
                           hover:shadow-md dark:hover:shadow-lg transition-all duration-200 overflow-hidden
                           hover:border-blue-300 dark:hover:border-blue-600">

                    {{-- Card Header --}}
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 relative dark:border-slate-700">
                        {{-- Score Badge --}}
                        <div class="absolute top-4 right-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm
                                        transition-all duration-200"
                                 :class="{
                                     'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': match.score >= 80,
                                     'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400': match.score >= 50 && match.score < 80,
                                     'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': match.score < 50
                                 }">
                                <span x-text="Math.round(match.score)"></span>
                            </div>
                        </div>

                        {{-- İlan Başlığı --}}
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white pr-16 mb-2 dark:text-slate-100"
                            x-text="match.baslik || match.title"></h4>

                        {{-- Lokasyon --}}
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-map-marker-alt"></i>
                            <span x-text="match.location || 'Lokasyon belirtilmemiş'"></span>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-4 space-y-3">
                        {{-- Fiyat --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Fiyat</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                <span x-text="formatPrice(match.price)"></span>
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-1"
                                      x-text="match.para_birimi || 'TRY'"></span>
                            </span>
                        </div>

                        {{-- Score Breakdown --}}
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded dark:bg-slate-900">
                                <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100"
                                     x-text="Math.round(match.breakdown.location)"></div>
                                <div class="text-gray-600 dark:text-gray-400">Konum</div>
                            </div>
                            <div class="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded dark:bg-slate-900">
                                <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100"
                                     x-text="Math.round(match.breakdown.price)"></div>
                                <div class="text-gray-600 dark:text-gray-400">Fiyat</div>
                            </div>
                            <div class="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded dark:bg-slate-900">
                                <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100"
                                     x-text="Math.round(match.breakdown.features)"></div>
                                <div class="text-gray-600 dark:text-gray-400">Özellik</div>
                            </div>
                        </div>

                        {{-- Reasons --}}
                        <div x-show="match.reasons && match.reasons.length > 0" class="mt-3">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(reason, reasonIndex) in match.reasons" :key="reasonIndex">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-check-circle mr-1 text-xs"></i>
                                        <span x-text="reason"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:border-slate-700">
                        <a :href="'/admin/ilanlar/' + match.id"
                           target="_blank"
                           class="w-full flex items-center justify-center space-x-2 px-4 py-2
                                  bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                                  text-white rounded-lg transition-all duration-200 text-sm font-medium
                                  hover:scale-105 active:scale-95">
                            <span>İlana Git</span>
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    </div>
                </div>
            </template>
        </div>
        <div class="mt-6" x-show="!feedbackSubmitted" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="flex flex-col sm:flex-row items-center sm:justify-between gap-3 p-4 bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-slate-800 rounded-lg dark:bg-slate-900 dark:border-slate-700">
                <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Eşleştirme önerisini nasıl buldunuz?</div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="submitFeedback('positive', 5)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-green-500 transition-all duration-200">👍 Harika</button>
                    <button type="button" @click="submitFeedback('neutral', 3)" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200">😐 Orta</button>
                    <button type="button" @click="submitFeedback('negative', 1)" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-red-500 transition-all duration-200">👎 Alakasız</button>
                </div>
            </div>
        </div>
        <div class="mt-6" x-show="feedbackSubmitted" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-800 dark:text-blue-300">Geri bildiriminiz için teşekkürler.</div>
        </div>
    </div>

    {{-- Error State --}}
    <div x-show="error"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="p-6">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center space-x-2">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400"></i>
                <p class="text-sm text-red-800 dark:text-red-300" x-text="error"></p>
            </div>
        </div>
    </div>
</div>

<script>
function smartMatchWidget(talepId) {
    return {
        talepId: talepId,
        matches: [],
        loading: false,
        error: null,
        statusText: 'Hazırlanıyor...',
        sessionLogId: null,
        feedbackSubmitted: false,

        init() {
            this.fetchMatches();
        },

        async fetchMatches() {
            this.loading = true;
            this.error = null;
            this.statusText = 'AI analiz ediyor...';

            try {
                const response = await fetch('/api/v1/admin/ai/find-matches', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        talep_id: this.talepId
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success && data.data && data.data.matches) {
                    this.matches = data.data.matches;
                    this.statusText = `${this.matches.length} ilan bulundu`;
                    this.sessionLogId = data.data.logId || data.logId || data.session_log_id || null;
                } else {
                    this.matches = [];
                    this.statusText = 'Sonuç bulunamadı';
                }
            } catch (err) {
                console.error('Smart match error:', err);
                this.error = 'Eşleştirme sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                this.statusText = 'Hata oluştu';
                this.matches = [];
            } finally {
                this.loading = false;
            }
        },

        refresh() {
            this.fetchMatches();
        },

        formatPrice(price) {
            if (!price) return 'Belirtilmemiş';
            return new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        },
        async submitFeedback(type, score) {
            if (!this.sessionLogId || this.feedbackSubmitted) return;
            try {
                const response = await fetch(`/api/ai/feedback/${this.sessionLogId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        feedback_type: type,
                        score: score
                    })
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                this.feedbackSubmitted = true;
            } catch (e) {
                this.error = 'Geri bildirim gönderilirken bir hata oluştu.';
            }
        }
    };
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
