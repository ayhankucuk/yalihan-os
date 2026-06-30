/**
 * Test Message Module
 * Context7: AI test message functionality
 *
 * SADECE AI Settings sayfası için kullanılır.
 */

import { AIService } from '../services/AIService.js';

export class TestMessage {
    /**
     * Send Test Message
     * AI provider'a test mesajı gönder ve yanıtı göster
     *
     * @returns {Promise<boolean>} İşlem başarılı mı?
     */
    static async sendTestMessage() {
        const messageInput = document.getElementById('test_message');
        const providerSelect = document.getElementById('test_provider_select');
        const responseDiv = document.getElementById('test_response');
        const statusSpan = document.getElementById('test_status');
        const metricsDiv = document.getElementById('test_metrics');

        if (!messageInput || !providerSelect) {
            console.error('Test message elements not found');
            return false;
        }

        const message = messageInput.value.trim();
        const provider = providerSelect.value;

        if (!message) {
            if (window.toast) {
                window.toast.error('❌ Lütfen test mesajı girin!');
            }
            return false;
        }

        if (!provider) {
            if (window.toast) {
                window.toast.error('❌ Lütfen bir provider seçin!');
            }
            return false;
        }

        // Show loading state
        if (statusSpan) {
            statusSpan.className =
                'px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs rounded-full animate-pulse';
            statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
        }

        if (responseDiv) {
            responseDiv.innerHTML = `
                <div class="flex items-center gap-3 text-gray-600 dark:text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl text-purple-500"></i>
                    <span>AI yanıtı bekleniyor...</span>
                </div>
            `;
        }

        if (metricsDiv) {
            metricsDiv.classList.add('hidden');
        }

        const startTime = Date.now();

        try {
            const response = await fetch('/admin/ai-settings/test-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AIService.getCsrfToken(),
                },
                body: JSON.stringify({
                    provider: provider,
                    message: message,
                }),
            });

            const data = await response.json();
            const responseTime = Date.now() - startTime;

            if (data.success) {
                // Success state
                if (statusSpan) {
                    statusSpan.className =
                        'px-3 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs rounded-full';
                    statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Başarılı';
                }

                // Display response
                if (responseDiv) {
                    responseDiv.innerHTML = `
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-purple-500 dark:bg-slate-900">
                            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap dark:text-slate-100 dark:text-white">${data.response || data.message}</p>
                        </div>
                    `;
                }

                // Show metrics
                if (metricsDiv) {
                    metricsDiv.classList.remove('hidden');
                    const responseTimeEl = document.getElementById('test_response_time');
                    const tokensEl = document.getElementById('test_tokens');
                    const costEl = document.getElementById('test_cost');

                    if (responseTimeEl) responseTimeEl.textContent = `${responseTime}ms`;
                    if (tokensEl) tokensEl.textContent = data.tokens || '-';
                    if (costEl) costEl.textContent = data.cost ? `$${data.cost}` : '-';
                }

                // Use Context7 Toast
                if (window.toast) {
                    window.toast.success(
                        `✅ ${provider.toUpperCase()}: Yanıt alındı (${responseTime}ms)`
                    );
                }

                return true;
            } else {
                throw new Error(data.message || 'Bilinmeyen hata');
            }
        } catch (error) {
            console.error('Test message error:', error);

            // Error state
            if (statusSpan) {
                statusSpan.className =
                    'px-3 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs rounded-full';
                statusSpan.innerHTML = '<i class="fas fa-exclamation-circle"></i> Hata';
            }

            if (responseDiv) {
                responseDiv.innerHTML = `
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border-l-4 border-red-500">
                        <p class="text-red-700 dark:text-red-300 font-medium">❌ Hata:</p>
                        <p class="text-red-600 dark:text-red-400 mt-2">${error.message || 'Bağlantı hatası'}</p>
                    </div>
                `;
            }

            // Use Context7 Toast
            if (window.toast) {
                window.toast.error(`❌ ${provider.toUpperCase()}: ${error.message}`);
            }

            return false;
        }
    }

    /**
     * Clear Test Area
     * Test alanını temizle
     */
    static clearTestArea() {
        const messageInput = document.getElementById('test_message');
        const responseDiv = document.getElementById('test_response');
        const statusSpan = document.getElementById('test_status');
        const metricsDiv = document.getElementById('test_metrics');

        if (messageInput) {
            messageInput.value = '';
        }

        if (statusSpan) {
            statusSpan.className =
                'px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full';
            statusSpan.innerHTML = '<i class="fas fa-circle"></i> Henüz Test Edilmedi';
        }

        if (responseDiv) {
            responseDiv.innerHTML = `
                <div class="text-gray-500 text-center py-8 dark:text-slate-500">
                    <i class="fas fa-comment-dots text-4xl mb-3 opacity-30"></i>
                    <p>Test mesajı gönderin ve AI yanıtını görün</p>
                </div>
            `;
        }

        if (metricsDiv) {
            metricsDiv.classList.add('hidden');
        }

        // Use Context7 Toast
        if (window.toast) {
            window.toast.info('🧹 Test alanı temizlendi');
        }
    }
}

export default TestMessage;
