{{-- Description Review Modal --}}
{{-- Human-in-the-Loop: AI draft → Owner review → Approve/Reject → Persist --}}
<div x-data="{
    showReviewModal: false,
    ilanId: null,
    draftStatus: 'taslak',
    draftContent: '',
    originalContent: '',
    editedContent: '',
    isLoading: false,
    error: null,
    draftId: null,
    provider: null,

    init() {
        {{-- Listen for open event from AI buttons --}}
        window.addEventListener('open-description-review', (e) => {
            if (e.detail?.ilanId) {
                this.open(e.detail.ilanId);
            }
        });
    },

    open(ilanId) {
        this.ilanId = ilanId;
        this.showReviewModal = true;
        this.loadDraft(ilanId);
    },

    async loadDraft(ilanId) {
        this.isLoading = true;
        this.error = null;
        try {
            const response = await fetch(`/admin/ilan-ai/draft/${ilanId}`);
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Taslak yüklenemedi');
            }
            this.draftId = data.id;
            this.draftStatus = data.durum;
            this.draftContent = data.draft_content || '';
            this.originalContent = data.original_content || '';
            this.editedContent = this.draftContent;
            this.provider = data.provider;
        } catch (err) {
            this.error = err.message;
        } finally {
            this.isLoading = false;
        }
    },

    async generateDraft(ilanId) {
        this.isLoading = true;
        this.error = null;
        try {
            const response = await fetch(`/admin/ilan-ai/draft/generate/${ilanId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content }
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Taslak oluşturulamadı');
            }
            this.draftId = data.draft_id;
            this.draftContent = '';
            this.editedContent = '';
            this.provider = data.provider;
            this.draftStatus = 'taslak';
            // Reload to get full draft content
            await this.loadDraft(ilanId);
        } catch (err) {
            this.error = err.message;
        } finally {
            this.isLoading = false;
        }
    },

    async approve() {
        if (!this.draftId) return;
        this.isLoading = true;
        this.error = null;
        try {
            const response = await fetch(`/admin/ilan-ai/draft/${this.draftId}/approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content }
            });
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Onaylama başarısız');
            }
            this.draftStatus = 'uygulandi';
            window.toast?.success('Açıklama onaylandı ve uygulandı');
            // Reload page after short delay
            setTimeout(() => location.reload(), 1000);
        } catch (err) {
            this.error = err.message;
        } finally {
            this.isLoading = false;
        }
    },

    async reject() {
        if (!this.draftId) return;
        this.isLoading = true;
        this.error = null;
        try {
            const response = await fetch(`/admin/ilan-ai/draft/${this.draftId}/reject`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                body: JSON.stringify({ note: this.rejectionNote || null })
            });
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Reddetme başarısız');
            }
            this.draftStatus = 'reddedildi';
            window.toast?.info('Taslak reddedildi');
            this.showReviewModal = false;
        } catch (err) {
            this.error = err.message;
        } finally {
            this.isLoading = false;
        }
    },

    get statusBadge() {
        const badges = {
            'taslak': { label: 'Taslak', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
            'onayli': { label: 'Onaylandı', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
            'uygulandi': { label: 'Uygulandı', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' },
            'reddedildi': { label: 'Reddedildi', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }
        };
        return badges[this.draftStatus] || badges['taslak'];
    },

    get canApprove() {
        return this.draftStatus === 'taslak' && this.draftId;
    },

    get canReject() {
        return this.draftStatus === 'taslak' && this.draftId;
    }
}" x-show="showReviewModal" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="showReviewModal = false" @keydown.escape.window="showReviewModal = false">

    <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900" @click.stop>
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Açıklama İnceleme</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">AI tarafından üretilen taslağı inceleyin ve onaylayın</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <template x-if="draftId">
                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusBadge.class" x-text="statusBadge.label"></span>
                </template>
                <template x-if="provider">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-400" x-text="provider"></span>
                </template>
                <button @click="showReviewModal = false" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 dark:hover:bg-slate-800 dark:hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="max-h-[60vh] overflow-y-auto p-6">
            {{-- Error State --}}
            <template x-if="error">
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <p class="text-sm text-red-700 dark:text-red-400" x-text="error"></p>
                </div>
            </template>

            {{-- Loading --}}
            <template x-if="isLoading && !draftContent">
                <div class="flex items-center justify-center py-12">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></div>
                    <span class="ml-3 text-gray-500">Yükleniyor...</span>
                </div>
            </template>

            {{-- No Draft State --}}
            <template x-if="!isLoading && !draftId && !error">
                <div class="py-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-800">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707-.293l2.828-2.828a1 1 0 011.414 0l2.828 2.828a1 1 0 010 1.414L13.414 9l2.828 2.828a1 1 0 010 1.415l2.828 2.828a1 1 0 010 1.414l-2.828 2.828a1 1 0 01-1.414 0L9.586 13l-2.828-2.828a1 1 0 00-1.414 0L4 12.586a1 1 0 010-1.414L7.414 9l-2.828-2.828a1 1 0 00-1.414 0L2.172 6.758a1 1 0 000-1.414L5.586 3 2.758 2.172 0 1.414l2.828 2.828a1 1 0 001.414 0L9 7.414l2.828 2.828a1 1 0 001.414 0L15.586 8.828a1 1 0 000-1.414L13.414 6l2.828-2.828a1 1 0 00-1.414 0L12.414 0" />
                        </svg>
                    </div>
                    <p class="mb-2 text-gray-600 dark:text-slate-400">Henüz AI taslak oluşturulmamış</p>
                    <p class="mb-4 text-sm text-gray-400 dark:text-slate-500">Açıklama oluşturmak için butona tıklayın</p>
                </div>
            </template>

            {{-- Draft Content --}}
            <template x-if="draftContent || editedContent">
                <div class="space-y-6">
                    {{-- Comparison View --}}
                    <template x-if="originalContent && draftContent">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="rounded bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-gray-400">Mevcut Açıklama</span>
                                </div>
                                <p class="whitespace-pre-wrap text-sm text-gray-600 dark:text-slate-400" x-text="originalContent || '(Boş)'"></p>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">AI Taslak</span>
                                </div>
                                <p class="whitespace-pre-wrap text-sm text-emerald-800 dark:text-emerald-300" x-text="draftContent || '(Boş)'"></p>
                            </div>
                        </div>
                    </template>

                    {{-- Editor --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklamayı Düzenle</label>
                        <textarea x-model="editedContent" rows="12" class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm transition-shadow focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Açıklama girin..."></textarea>
                        <p class="text-xs text-gray-400">Açıklamayı onaylamadan önce düzenleyebilirsiniz.</p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <template x-if="draftId && !canApprove">
                    <a :href="`/admin/ilan-ai/draft/${draftId}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Geçmişi görüntüle</a>
                </template>
            </div>
            <div class="flex items-center gap-3">
                <template x-if="isLoading">
                    <span class="text-sm text-gray-400">İşleniyor...</span>
                </template>
                <template x-if="!isLoading && canReject">
                    <button @click="reject()" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">
                        Reddet
                    </button>
                </template>
                <template x-if="!isLoading && canApprove">
                    <button @click="approve()" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition-all hover:bg-emerald-500 hover:shadow-emerald-500/50">
                        Onayla ve Uygula
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
