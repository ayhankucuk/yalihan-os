{{-- STEP 4: PREVIEW & APPROVAL (Konut Satılık) --}}
<div class="space-y-6" x-data="konutSatilikPreviewForm()">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            Önizleme & Onay
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Structured data'yı kontrol edin ve onaylayın</p>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 mb-6 dark:border-slate-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Structured Data Özeti</h4>
        <pre class="bg-gray-100 dark:bg-slate-900 p-4 rounded text-sm overflow-auto" x-text="JSON.stringify(structuredData, null, 2)"></pre>
    </div>

    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h5 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Önemli Uyarı</h5>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    Onayladıktan sonra (mühürlendikten sonra) AI içerik üretimi yapılabilir.
                    Onaylamadan önce tüm verilerin doğru olduğundan emin olun.
                </p>
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-4">
        <button type="button" @click="loadStructuredData"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 dark:text-slate-200">
            Yenile
        </button>
        <button type="button" @click="approveIlan"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            :disabled="isApproving">
            <span x-show="!isApproving">Onayla (Mühür)</span>
            <span x-show="isApproving">Onaylanıyor...</span>
        </button>
    </div>

    <div x-show="approved" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-green-800 dark:text-green-200 font-semibold">İlan onaylandı (mühürlendi)</span>
        </div>
    </div>

    {{-- AI İçerik Üretimi Butonları (Sadece Mühür Sonrası) --}}
    <div x-show="approved" class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">AI İçerik Üretimi</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button type="button" @click="generateTitle"
                :disabled="isGenerating"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isGenerating">AI Başlık Üret</span>
                <span x-show="isGenerating">Üretiliyor...</span>
            </button>
            <button type="button" @click="generateSummary"
                :disabled="isGenerating"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isGenerating">AI Özet Üret</span>
                <span x-show="isGenerating">Üretiliyor...</span>
            </button>
            <button type="button" @click="generateDescription"
                :disabled="isGenerating"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isGenerating">AI Açıklama Üret</span>
                <span x-show="isGenerating">Üretiliyor...</span>
            </button>
            <button type="button" @click="generateSeoMeta"
                :disabled="isGenerating"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isGenerating">AI SEO Meta Üret</span>
                <span x-show="isGenerating">Üretiliyor...</span>
            </button>
        </div>
        <div x-show="aiResult" class="mt-4 p-4 bg-gray-100 dark:bg-slate-900 rounded">
            <pre class="text-sm" x-text="aiResult"></pre>
        </div>
    </div>

    <div x-show="!approved" class="bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            AI içerik üretimi için önce "Onayla (Mühür)" butonuna tıklayın.
        </p>
    </div>
</div>

<script>
function konutSatilikPreviewForm() {
    return {
        structuredData: {},
        approved: false,
        isApproving: false,
        isGenerating: false,
        aiResult: null,
        getIlanId() {
            if (window.ilanId && window.ilanId > 0) {
                return window.ilanId;
            }
            const hiddenInput = document.querySelector('[name="ilan_id"]');
            if (hiddenInput && hiddenInput.value) {
                return hiddenInput.value;
            }
            const urlMatch = window.location.pathname.match(/ilanlar\/(\d+)/);
            if (urlMatch && urlMatch[1]) {
                return urlMatch[1];
            }
            return null;
        },
        async loadStructuredData() {
            const ilanId = this.getIlanId();
            if (!ilanId) {
                // Draft/Create mode - silent skip
                return;
            }

            const response = await fetch(`/admin/ilanlar/${ilanId}`);
            const data = await response.json();
            this.structuredData = data.structured_data || {};
            this.approved = !!data.approved_at;
        },
        async approveIlan() {
            const ilanId = this.getIlanId();
            if (!ilanId) {
                alert('İlan ID bulunamadı');
                return;
            }

            this.isApproving = true;
            try {
                const response = await fetch(`/admin/ilanlar/${ilanId}/structured-data/konut/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();
                if (data.success) {
                    this.approved = true;
                    alert('İlan onaylandı (mühürlendi)');
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                }
            } finally {
                this.isApproving = false;
            }
        },
        async generateTitle() {
            await this.generateAIContent('title');
        },
        async generateSummary() {
            await this.generateAIContent('summary');
        },
        async generateDescription() {
            await this.generateAIContent('description');
        },
        async generateSeoMeta() {
            await this.generateAIContent('seo-meta');
        },
        async generateAIContent(type) {
            if (!this.approved) {
                alert('Önce ilanı onaylamalısınız (mühürlemelisiniz)');
                return;
            }

            const ilanId = this.getIlanId();
            if (!ilanId) {
                alert('İlan ID bulunamadı');
                return;
            }

            this.isGenerating = true;
            this.aiResult = null;

            try {
                const response = await fetch(`/admin/ilanlar/${ilanId}/structured-data/konut/${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();
                if (data.success) {
                    if (type === 'title') {
                        this.aiResult = data.data?.title || JSON.stringify(data.data, null, 2);
                    } else if (type === 'summary') {
                        this.aiResult = data.data?.summary || JSON.stringify(data.data, null, 2);
                    } else if (type === 'description') {
                        this.aiResult = data.data?.description || JSON.stringify(data.data, null, 2);
                    } else if (type === 'seo-meta') {
                        this.aiResult = JSON.stringify(data.data, null, 2);
                    }
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                }
            } catch (error) {
                alert('Hata: ' + error.message);
            } finally {
                this.isGenerating = false;
            }
        },
        init() {
            this.loadStructuredData();
        },
    };
}
</script>
