{{-- AI Widget Component --}}
<div class="ai-widget bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-slate-900 dark:border-slate-700 dark:shadow-none"
     x-data="aiWidget({
        action: '{{ $action ?? 'analyze' }}',
        endpoint: '{{ $endpoint ?? '' }}',
        title: '{{ $title ?? 'AI Analiz' }}',
        icon: '{{ $icon ?? 'fas fa-brain' }}',
        data: @js($data ?? []),
        context: @js($context ?? [])
     })"
     x-init="init()">

    {{-- AI Widget Header --}}
    <div class="ai-widget-header bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="ai-icon w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i :class="icon" class="text-blue-600"></i>
                </div>
                <div>
                    <h3 class="ai-title text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white" x-text="title"></h3>
                    <p class="ai-subtitle text-sm text-gray-600" x-text="getStatusText()"></p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <div class="ai-status-indicator flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full"
                         :class="{
                             'bg-green-500': status === 'success',
                             'bg-yellow-500': status === 'loading',
                             'bg-red-500': status === 'error',
                             'bg-gray-400': status === 'idle'
                         }"></div>
                    <span class="text-xs font-medium text-gray-600" x-text="statusText"></span>
                </div>
                <button @click="toggleExpanded()"
                        class="ai-toggle-btn p-2 text-gray-400 hover:text-gray-600 transition-colors touch-target-optimized">
                    <i class="fas fa-chevron-down transform transition-transform"
                       :class="{ 'rotate-180': expanded }"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- AI Widget Content --}}
    <div class="ai-widget-content" x-show="expanded" x-transition>
        <div class="p-6">
            {{-- Loading State --}}
            <div x-show="status === 'loading'" class="text-center py-8">
                <div class="ai-loading-spinner inline-block w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-600" x-text="loadingMessage"></p>
                <div class="ai-progress mt-4" x-show="progress > 0">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                             :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2" x-text="`${progress}% tamamlandı`"></p>
                </div>
            </div>

            {{-- Success State --}}
            <div x-show="status === 'success'" class="space-y-4">
                <div class="ai-result" x-html="result"></div>
                <div class="ai-metadata flex items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <div class="flex items-center space-x-4">
                        <span x-text="`Provider: ${metadata.provider}`"></span>
                        <span x-text="`Süre: ${metadata.duration}s`"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="copyResult()" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-copy"></i> Kopyala
                        </button>
                        <button @click="regenerate()" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-redo"></i> Yeniden Üret
                        </button>
                    </div>
                </div>
            </div>

            {{-- Error State --}}
            <div x-show="status === 'error'" class="text-center py-8">
                <div class="ai-error-icon w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2 dark:text-slate-100 dark:text-white">AI Hatası</h4>
                <p class="text-gray-600 mb-4" x-text="errorMessage"></p>
                <div class="flex justify-center space-x-3">
                    <button @click="retry()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i> Tekrar Dene
                    </button>
                    <button @click="reportError()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-bug mr-2"></i> Hata Bildir
                    </button>
                </div>
            </div>

            {{-- Idle State --}}
            <div x-show="status === 'idle'" class="text-center py-8">
                <div class="ai-idle-icon w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-slate-900">
                    <i class="fas fa-play text-gray-600 text-xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2 dark:text-slate-100 dark:text-white">AI Analiz</h4>
                <p class="text-gray-600 mb-4">Analiz başlatmak için butona tıklayın</p>
                <button @click="startAnalysis()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-brain mr-2"></i> Analizi Başlat
                </button>
            </div>
        </div>
    </div>

    {{-- AI Widget Footer --}}
    <div class="ai-widget-footer bg-gray-50 px-6 py-3 border-t border-gray-200 dark:bg-slate-900 dark:border-slate-700" x-show="expanded">
        <div class="flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center space-x-4">
                <span x-text="`Son güncelleme: ${lastUpdate}`"></span>
                <span x-show="metadata.provider" x-text="`Provider: ${metadata.provider}`"></span>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="refresh()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button @click="settings()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- AI Widget JavaScript --}}
@push('scripts')
<script>
function aiWidget(config = {}) {
    return {
        // Configuration
        action: config.action || 'analyze',
        endpoint: config.endpoint || '',
        title: config.title || 'AI Analiz',
        icon: config.icon || 'fas fa-brain',
        data: config.data || {},
        context: config.context || {},

        // State
        status: 'idle', // idle, loading, success, error
        expanded: true,
        progress: 0,
        result: '',
        errorMessage: '',
        loadingMessage: 'AI analiz ediliyor...',
        metadata: {},
        lastUpdate: '',

        // Computed
        get statusText() {
            const texts = {
                'idle': 'Hazır',
                'loading': 'Analiz ediliyor',
                'success': 'Tamamlandı',
                'error': 'Hata oluştu'
            };
            return texts[this.status] || 'Bilinmiyor';
        },

        // Methods
        init() {
            this.lastUpdate = new Date().toLocaleString('tr-TR');
            if (this.autoStart) {
                this.startAnalysis();
            }
        },

        async startAnalysis() {
            this.status = 'loading';
            this.progress = 0;
            this.loadingMessage = 'AI analiz ediliyor...';

            // Simulate progress
            const progressInterval = setInterval(() => {
                if (this.progress < 90) {
                    this.progress += Math.random() * 20;
                }
            }, 200);

            try {
                const response = await this.makeRequest();
                this.progress = 100;
                clearInterval(progressInterval);

                setTimeout(() => {
                    this.status = 'success';
                    this.result = this.formatResult(response.data);
                    this.metadata = response.metadata || {};
                    this.lastUpdate = new Date().toLocaleString('tr-TR');
                }, 500);

            } catch (error) {
                clearInterval(progressInterval);
                this.status = 'error';
                this.errorMessage = error.message || 'Bilinmeyen hata oluştu';
                this.lastUpdate = new Date().toLocaleString('tr-TR');
            }
        },

        async makeRequest() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(this.endpoint || '/api/admin/ai/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: this.action,
                    data: this.data,
                    context: this.context
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            return await response.json();
        },

        formatResult(data) {
            if (typeof data === 'string') {
                return data;
            }

            if (Array.isArray(data)) {
                return '<ul class="space-y-2">' +
                       data.map(item => `<li class="flex items-start space-x-2">
                           <i class="fas fa-check text-green-500 mt-1"></i>
                           <span>${item}</span>
                       </li>`).join('') +
                       '</ul>';
            }

            return JSON.stringify(data, null, 2);
        },

        async regenerate() {
            await this.startAnalysis();
        },

        async retry() {
            await this.startAnalysis();
        },

        copyResult() {
            navigator.clipboard.writeText(this.result.replace(/<[^>]*>/g, ''));
            this.showToast('Sonuç kopyalandı', 'success');
        },

        refresh() {
            this.startAnalysis();
        },

        toggleExpanded() {
            this.expanded = !this.expanded;
        },

        settings() {
            // Open AI settings modal
            window.dispatchEvent(new CustomEvent('open-ai-settings'));
        },

        reportError() {
            // Open error report modal
            window.dispatchEvent(new CustomEvent('report-ai-error', {
                detail: {
                    action: this.action,
                    error: this.errorMessage,
                    context: this.context
                }
            }));
        },

        showToast(message, type = 'info') {
            // Show toast notification
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { message, type }
            }));
        }
    }
}
</script>
@endpush

{{-- AI Widget Styles --}}
@push('styles')
<style>
.ai-widget {
    transition: all 0.3s ease;
}

.ai-loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.ai-result {
    line-height: 1.6;
}

.ai-result ul {
    list-style: none;
    padding: 0;
}

.ai-result li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.ai-result li:last-child {
    border-bottom: none;
}

.ai-metadata {
    font-size: 0.75rem;
    color: #6b7280;
}

.ai-widget-footer {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}
</style>
@endpush
