/**
 * AI Settings - Core Entry Point
 * Context7: Modular AI Settings implementation
 *
 * SADECE AI Settings sayfası için kullanılır.
 * Tüm AI Settings modüllerini import eder ve window'a export eder.
 */

import { AIService } from '../services/AIService.js';
import { StatusUpdater } from './status-updater.js';
import { ProviderTest } from './provider-test.js';
import { TestMessage } from './test-message.js';
import { Analytics } from './analytics.js';

/**
 * Initialize AI Settings
 * Sayfa yüklendiğinde çalışacak initialization fonksiyonu
 */
function initializeAISettings() {
    console.log('🤖 AI Settings initialized (Modular + Hybrid)');
    console.log('✅ Modules loaded:');
    console.log('  - AIService (Shared Core)');
    console.log('  - StatusUpdater');
    console.log('  - ProviderTest');
    console.log('  - TestMessage');
    console.log('  - Analytics');

    // Auto-load analytics if page has analytics container
    const analyticsContainer = document.getElementById('ai_analytics_container');
    if (analyticsContainer) {
        Analytics.loadAnalytics().catch((err) => {
            console.error('Analytics auto-load failed:', err);
        });
    }

    // Auto-load provider status
    ProviderTest.refreshProviderStatus().catch((err) => {
        console.error('Provider status auto-load failed:', err);
    });

    console.log('🎉 AI Settings ready!');
}

// Export to window for global access (backward compatibility)
window.AIService = AIService;
window.StatusUpdater = StatusUpdater;
window.ProviderTest = ProviderTest;
window.TestMessage = TestMessage;
window.Analytics = Analytics;

// Export individual functions for easy access
window.testProvider = (provider, btn) => ProviderTest.testSingleProvider(provider, btn);
window.testAllProviders = () => ProviderTest.testAllProviders();
window.refreshProviderStatus = () => ProviderTest.refreshProviderStatus();
window.sendTestMessage = () => TestMessage.sendTestMessage();
window.clearTestArea = () => TestMessage.clearTestArea();
window.updateStatusBadge = (provider, status, message, responseTime) =>
    StatusUpdater.updateStatusBadge(provider, status, message, responseTime);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAISettings);
} else {
    initializeAISettings();
}

// Export for module usage
export { AIService, StatusUpdater, ProviderTest, TestMessage, Analytics, initializeAISettings };
