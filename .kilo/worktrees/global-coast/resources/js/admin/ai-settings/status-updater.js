/**
 * İşlem Durumu Güncelleyici Modülü (Status Updater)
 * Context7: AI Settings işlem rozeti (badge) yönetimi
 *
 * SADECE AI Settings sayfası için kullanılır.
 */

export class StatusUpdater {
    /**
     * Durum Rozetini Güncelle (Update Status Badge)
     * Provider işlem rozetini güncelle
     *
     * @param {string} provider - Provider adı
     * @param {string} servis_durumu - İşlem Durumu (success, error, testing)
     * @param {string} message - Status mesajı
     * @param {number} responseTime - Response time (ms)
     */
    static updateDurumRozeti(provider, servis_durumu, message, responseTime = 0) {
        const badge = document.getElementById(`${provider}-islem-rozeti`);
        if (!badge) return;

        let icon, bgClass, textClass, statusText;

        switch (servis_durumu) {
            case 'success':
                icon = 'fa-check-circle';
                bgClass = 'bg-green-100 dark:bg-green-900';
                textClass = 'text-green-700 dark:text-green-300';
                statusText = `✅ Aktif (${responseTime}ms)`;
                break;
            case 'error':
                icon = 'fa-times-circle';
                bgClass = 'bg-red-100 dark:bg-red-900';
                textClass = 'text-red-700 dark:text-red-300';
                statusText = '❌ Hata';
                break;
            case 'testing':
                icon = 'fa-spinner fa-spin';
                bgClass = 'bg-blue-100 dark:bg-blue-900';
                textClass = 'text-blue-700 dark:text-blue-300';
                statusText = '🔄 Test ediliyor...';
                break;
            default:
                icon = 'fa-circle';
                bgClass = 'bg-gray-100 dark:bg-gray-700';
                textClass = 'text-gray-600 dark:text-gray-400';
                statusText = 'Henüz Test Edilmedi';
        }

        badge.className = `px-3 py-1 text-xs font-medium ${bgClass} ${textClass} rounded-full flex items-center gap-1`;
        badge.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${statusText}</span>
        `;

        // Update title tooltip
        badge.title = message;
    }

    /**
     * Tüm Provider Durumlarını Güncelle (Update All Status)
     * Tüm provider'ların durumlarını güncelle
     *
     * @param {Object} durumVerileri - Provider durum verileri
     */
    static updateAllProviderStatus(durumVerileri) {
        Object.keys(durumVerileri).forEach((provider) => {
            const data = durumVerileri[provider];
            this.updateDurumRozeti(
                provider,
                data.servis_durumu || 'unknown',
                data.message || '',
                data.response_time || 0
            );
        });
    }
}

export default StatusUpdater;
