{{-- 🤖 AI Monitor Widget - Dashboard için mini canlı izleme --}}

<div
    class="overflow-hidden rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-indigo-50 shadow-sm dark:border-purple-800 dark:from-purple-900/20 dark:to-indigo-900/20 dark:shadow-none">
    <!-- Header -->
    <div
        class="border-b border-purple-200 bg-white/50 px-6 py-4 dark:border-purple-800 dark:bg-gray-800/50 dark:bg-slate-900/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-purple-600 to-indigo-600 shadow-md dark:shadow-none">
                    <span class="text-2xl">🤖</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 dark:text-white">AI Sistem</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Canlı İzleme</p>
                </div>
            </div>
            <div id="ai-system-durumu" class="flex items-center gap-2">
                <div class="h-3 w-3 animate-pulse rounded-full bg-gray-400"></div>
                <span class="text-xs text-gray-500">Yükleniyor...</span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-6">
        <!-- Provider Status Grid -->
        <div class="mb-4 grid grid-cols-2 gap-3">
            <!-- DeepSeek -->
            <div class="saglayici-durum-karti rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900"
                id="provider-deepseek">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="durum-isigi h-2 w-2 rounded-full bg-gray-400"></div>
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">DeepSeek</span>
                    </div>
                    <span
                        class="durum-etiketi rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-slate-900">●●●</span>
                </div>
            </div>

            <!-- Ollama -->
            <div class="saglayici-durum-karti rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900"
                id="provider-ollama">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="durum-isigi h-2 w-2 rounded-full bg-gray-400"></div>
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Ollama</span>
                    </div>
                    <span
                        class="durum-etiketi rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-slate-900">●●●</span>
                </div>
            </div>

            <!-- Google Gemini -->
            <div class="saglayici-durum-karti rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900"
                id="provider-google">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="durum-isigi h-2 w-2 rounded-full bg-gray-400"></div>
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Gemini</span>
                    </div>
                    <span
                        class="durum-etiketi rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-slate-900">●●●</span>
                </div>
            </div>

            <!-- OpenAI -->
            <div class="saglayici-durum-karti rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900"
                id="provider-openai">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="durum-isigi h-2 w-2 rounded-full bg-gray-400"></div>
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">OpenAI</span>
                    </div>
                    <span
                        class="durum-etiketi rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-slate-900">●●●</span>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="mb-4 grid grid-cols-3 gap-3">
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div id="ai-total-requests" class="text-2xl font-bold text-purple-600">--</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">İstek</div>
            </div>
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div id="ai-success-rate" class="text-2xl font-bold text-green-600">--%</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Başarı</div>
            </div>
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div id="ai-response-time" class="text-2xl font-bold text-blue-600">--</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">ms</div>
            </div>
        </div>

        <!-- 2D Matrix Status -->
        <div
            class="rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-3 dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎯</span>
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">2D Matrix
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <span id="matrix-fields">--</span> field,
                            <span id="matrix-ai-fields">--</span> AI-powered
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.property_types.index') }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700">
                    Yönet →
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4 flex gap-2">
            <a href="{{ route('admin.ai-settings.index') }}"
                class="flex-1 rounded-lg bg-purple-600 px-4 py-2.5 text-center text-sm text-white transition-colors hover:bg-purple-700">
                ⚙️ Ayarlar
            </a>
            <button onclick="refreshAIMonitor()"
                class="rounded-lg bg-gray-100 px-4 py-2.5 text-sm text-gray-900 transition-colors hover:bg-gray-200 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:hover:bg-gray-600">
                🔄
            </button>
        </div>
    </div>
</div>

<script>
    // ═══════════════════════════════════════════════════════════
    // 🤖 AI MONITOR WIDGET - Real-time Status
    // ═══════════════════════════════════════════════════════════

    async function refreshAIMonitor() {
        try {
            // Analytics API (with error handling)
            const analyticsResponse = await fetch('/admin/ai-settings/analytics', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!analyticsResponse.ok) {
                throw new Error('Analytics API failed');
            }

            const analytics = await analyticsResponse.json();

            // Update quick stats
            document.getElementById('ai-total-requests').textContent = analytics.total_requests || 0;
            document.getElementById('ai-success-rate').textContent = Math.round(analytics.success_rate || 0) + '%';
            document.getElementById('ai-response-time').textContent = Math.round(analytics.avg_response_time || 0);

            // Update system status
            const systemStatus = document.getElementById('ai-system-durumu');
            const successRate = analytics.success_rate || 0;

            if (successRate >= 80) {
                systemStatus.innerHTML =
                    '<div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div><span class="text-xs text-green-600 font-medium">Mükemmel</span>';
            } else if (successRate >= 50) {
                systemStatus.innerHTML =
                    '<div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></div><span class="text-xs text-yellow-600 font-medium">İyi</span>';
            } else if (successRate > 0) {
                systemStatus.innerHTML =
                    '<div class="w-3 h-3 bg-orange-500 rounded-full animate-pulse"></div><span class="text-xs text-orange-600 font-medium">Sorunlu</span>';
            } else {
                systemStatus.innerHTML =
                    '<div class="w-3 h-3 bg-gray-400 rounded-full"></div><span class="text-xs text-gray-500">Pasif</span>';
            }

            // Update provider usage
            updateProviderStatus(analytics.provider_usage || {});

            // 2D Matrix stats
            await updateMatrixStats();

        } catch (error) {
            console.error('AI Monitor refresh failed:', error);
            document.getElementById('ai-system-durumu').innerHTML =
                '<div class="w-3 h-3 bg-red-500 rounded-full"></div><span class="text-xs text-red-600">Hata</span>';
        }
    }

    function updateProviderStatus(usage) {
        const providers = ['deepseek', 'ollama', 'google', 'openai'];

        providers.forEach(provider => {
            const card = document.getElementById(`provider-${provider}`);
            if (!card) return;

            const dot = card.querySelector('.durum-isigi');
            const badge = card.querySelector('.durum-etiketi');
            const count = usage[provider] || 0;

            if (count > 0) {
                // Aktif provider (yeşil)
                dot.className = 'durum-isigi w-2 h-2 bg-green-500 rounded-full animate-pulse';
                badge.className =
                    'durum-etiketi text-xs px-2 py-1 bg-green-100 text-green-700 rounded font-medium';
                badge.textContent = count;
            } else {
                // Pasif provider (gri)
                dot.className = 'durum-isigi w-2 h-2 bg-gray-300 rounded-full';
                badge.className = 'durum-etiketi text-xs px-2 py-1 bg-gray-100 text-gray-500 rounded';
                badge.textContent = '—';
            }
        });
    }

    async function updateMatrixStats() {
        try {
            // Get matrix stats from API (with timeout)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);

            const response = await fetch('/api/admin/ai/field-dependency/get-matrix/konut', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error('Matrix API failed');
            }

            const result = await response.json();

            if (result.success && result.matrix) {
                const totalFields = Object.values(result.matrix).reduce((sum, fields) => sum + fields.length, 0);
                const aiFields = Object.values(result.matrix)
                    .flat()
                    .filter(f => f.ai_suggestion).length;

                document.getElementById('matrix-fields').textContent = totalFields;
                document.getElementById('matrix-ai-fields').textContent = aiFields;
            } else {
                // Fallback to known values
                document.getElementById('matrix-fields').textContent = '51';
                document.getElementById('matrix-ai-fields').textContent = '22';
            }
        } catch (error) {
            // Fallback on error
            console.warn('Matrix stats update failed:', error.message);
            document.getElementById('matrix-fields').textContent = '51';
            document.getElementById('matrix-ai-fields').textContent = '22';
        }
    }

    // Auto refresh every 30 seconds
    setInterval(refreshAIMonitor, 30000);

    // Initial load
    document.addEventListener('DOMContentLoaded', () => {
        refreshAIMonitor();
    });
</script>

<style>
    .saglayici-durum-karti {
        transition: all 0.3s ease;
    }

    .saglayici-durum-karti:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    @keyframes pulse-green {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .durum-isigi.bg-green-500 {
        animation: pulse-green 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
