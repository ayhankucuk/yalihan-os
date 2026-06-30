{{-- Section 10: AI İçerik Yardımcısı --}}
<div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 flex items-center mb-2">
            <span class="bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">10</span>
            🤖 AI Yardımcısı
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            İlan başlığı ve açıklaması için AI desteği (Opsiyonel)
        </p>
    </div>

    <div x-data="{
        loading: false,
        message: '',
        async generateTitle() {
            this.loading = true;
            this.message = '';
            try {
                // Basit AI başlık üretimi
                const response = await fetch('/api/admin/ai/suggest-title', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        category: document.getElementById('ana_kategori')?.value,
                        location: document.getElementById('il_id')?.value
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.title) {
                        document.getElementById('baslik').value = data.title;
                        this.message = 'Başlık oluşturuldu!';
                    }
                }
            } catch (error) {
                console.error('AI Error:', error);
            } finally {
                this.loading = false;
            }
        },
        async generateDescription() {
            this.loading = true;
            this.message = '';
            try {
                // Basit AI açıklama üretimi
                const response = await fetch('/api/admin/ai/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        title: document.getElementById('baslik')?.value,
                        category: document.getElementById('ana_kategori')?.value,
                        price: document.getElementById('fiyat')?.value
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.description) {
                        document.getElementById('aciklama').value = data.description;
                        this.message = 'Açıklama oluşturuldu!';
                    }
                }
            } catch (error) {
                console.error('AI Error:', error);
            } finally {
                this.loading = false;
            }
        }
    }" class="space-y-4">

        {{-- Minimal AI Butonları --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- AI Başlık --}}
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-heading text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-slate-200">Başlık Öner</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">AI ile çekici başlık</p>
                    </div>
                </div>
                <button type="button" @click="generateTitle()" :disabled="loading"
                    class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-magic mr-1"></i>Başlık Oluştur
                </button>
            </div>

            {{-- AI Açıklama --}}
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-file-alt text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-slate-200">Açıklama Üret</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">AI ile detaylı açıklama</p>
                    </div>
                </div>
                <button type="button" @click="generateDescription()" :disabled="loading"
                    class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-magic mr-1"></i>Açıklama Oluştur
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" x-transition class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-blue-600 dark:text-blue-400 mr-3"></i>
                <p class="text-sm text-blue-800 dark:text-blue-200">AI çalışıyor, lütfen bekleyin...</p>
            </div>
        </div>

        {{-- Success Message --}}
        <div x-show="message" x-transition class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-3"></i>
                <p class="text-sm text-green-800 dark:text-green-200" x-text="message"></p>
            </div>
        </div>
    </div>
</div>
