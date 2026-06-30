/**
 * AI Register - AdminAIService Global Export
 * Context7: AI Settings sayfası için AdminAIService'i window'a export eder
 */

import { AIService } from './services/AIService.js';

// ✅ AdminAIService'i window'a export et (backward compatibility)
if (typeof window !== 'undefined') {
    window.AdminAIService = AIService;

    // DOM yüklendiğinde provider durumunu otomatik yükle
    document.addEventListener('DOMContentLoaded', () => {
        const durumEl = document.getElementById('ai-saglayici-durumu');
        if (
            durumEl &&
            window.AdminAIService &&
            typeof window.AdminAIService.getProviderStatus === 'function'
        ) {
            window.AdminAIService.getProviderStatus()
                .then((res) => {
                    if (res && res.success) {
                        const d = res.data || res;
                        durumEl.textContent =
                            'Sağlayıcı: ' + (d.provider || '-') + ' • Model: ' + (d.model || '-');
                    } else {
                        durumEl.textContent = 'Durum alınamadı';
                    }
                })
                .catch(() => {
                    if (durumEl) durumEl.textContent = 'Durum alınamadı';
                });
        }
    });
}

export default AIService;
