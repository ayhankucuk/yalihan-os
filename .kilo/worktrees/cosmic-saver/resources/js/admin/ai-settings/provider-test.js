/**
 * Provider Test Module
 * Context7: AI provider testing functionality
 *
 * SADECE AI Settings sayfası için kullanılır.
 */

import { AIService } from '../services/AIService.js';
import { StatusUpdater } from './status-updater.js';

export class ProviderTest {
    /**
     * Test Single Provider
     * Tek bir provider'ı test et
     *
     * @param {string} provider - Provider adı
     * @param {HTMLElement} btn - Test button elementi
     * @returns {Promise<boolean>} Test başarılı mı?
     */
    static async testSingleProvider(provider, btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test ediliyor...';
        btn.disabled = true;

        // Update status to testing
        StatusUpdater.updateStatusBadge(provider, 'testing', 'Test ediliyor...', 0);

        try {
            // ORTAK CORE kullanılıyor!
            const result = await AIService.testProvider(provider);

            if (result.success) {
                StatusUpdater.updateStatusBadge(
                    provider,
                    'success',
                    result.message,
                    result.response_time
                );

                // Use Context7 Toast
                if (window.toast) {
                    window.toast.success(`✅ ${provider.toUpperCase()}: ${result.message}`);
                }

                return true;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error(`Provider test error (${provider}):`, error);
            StatusUpdater.updateStatusBadge(provider, 'error', error.message, 0);

            // Use Context7 Toast
            if (window.toast) {
                window.toast.error(`❌ ${provider.toUpperCase()}: ${error.message}`);
            }

            return false;
        } finally {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    /**
     * Test All Providers
     * Tüm provider'ları sırayla test et
     *
     * @param {Array<string>} providers - Provider listesi
     * @returns {Promise<Object>} Test sonuçları
     */
    static async testAllProviders(
        providers = ['anythingllm', 'openai', 'gemini', 'claude', 'deepseek', 'ollama']
    ) {
        const results = {};
        const testBtn = document.getElementById('testAllProviders');

        if (testBtn) {
            const originalHTML = testBtn.innerHTML;
            testBtn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i><span class="font-medium">Test Ediliyor...</span>';
            testBtn.disabled = true;

            for (const provider of providers) {
                const btn = document.querySelector(`.btn-test[data-provider="${provider}"]`);
                if (btn) {
                    results[provider] = await this.testSingleProvider(provider, btn);
                    // Rate limiting: Wait between tests
                    await AIService.delay(500);
                }
            }

            testBtn.innerHTML = originalHTML;
            testBtn.disabled = false;

            // Use Context7 Toast
            if (window.toast) {
                window.toast.info('🎉 Tüm testler tamamlandı!');
            }
        }

        return results;
    }

    /**
     * Refresh Provider Status
     * Provider durumlarını yenile
     *
     * @returns {Promise<Object>} Provider status data
     */
    static async refreshProviderStatus() {
        const refreshBtn = document.getElementById('refreshStatus');

        if (refreshBtn) {
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i><span class="font-medium">Yenileniyor...</span>';
            refreshBtn.disabled = true;
        }

        try {
            // ORTAK CORE kullanılıyor!
            const statusData = await AIService.getProviderStatus();

            // Update all provider status badges
            StatusUpdater.updateAllProviderStatus(statusData);

            // Use Context7 Toast
            if (window.toast) {
                window.toast.success('✅ Durum bilgileri güncellendi!');
            }

            return statusData;
        } catch (error) {
            console.error('Refresh provider status error:', error);

            // Use Context7 Toast
            if (window.toast) {
                window.toast.error('❌ Durum güncellenemedi!');
            }

            return {};
        } finally {
            if (refreshBtn) {
                refreshBtn.innerHTML =
                    '<i class="fas fa-sync"></i><span class="font-medium">Durumu Yenile</span>';
                refreshBtn.disabled = false;
            }
        }
    }
}

export default ProviderTest;
