// Favoriler ve Karşılaştırma Sistemi
// Global JavaScript fonksiyonları

// Header'daki sayaçları güncelle
function updateHeaderCounts() {
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    const compareList = JSON.parse(localStorage.getItem('compare-list') || '[]');

    const favoritesCount = document.getElementById('favorites-count');
    const compareCount = document.getElementById('compare-count');

    if (favoritesCount) {
        favoritesCount.textContent = favorites.length;
        favoritesCount.style.opacity = favorites.length > 0 ? '1' : '0';
    }

    if (compareCount) {
        compareCount.textContent = compareList.length;
        compareCount.style.opacity = compareList.length > 0 ? '1' : '0';
    }
}

// Favoriler listesini göster
function showFavorites() {
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');

    if (favorites.length === 0) {
        showNotification('Henüz favori ilanınız yok', 'info');
        return;
    }

    // Modal oluştur
    const modal = createModal('Favorilerim', generateFavoritesHTML(favorites));
    document.body.appendChild(modal);

    // Favorilerden kaldırma fonksiyonu
    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-favorite')) {
            const propertyId = parseInt(e.target.dataset.propertyId);
            removeFavorite(propertyId);
            modal.remove();
            showFavorites(); // Listeyi yeniden göster
        }
    });
}

// Karşılaştırma listesini göster
function showCompareList() {
    const compareList = JSON.parse(localStorage.getItem('compare-list') || '[]');

    if (compareList.length === 0) {
        showNotification('Karşılaştırma listeniz boş', 'info');
        return;
    }

    // Modal oluştur
    const modal = createModal('Karşılaştırma Listesi', generateCompareHTML(compareList));
    document.body.appendChild(modal);

    // Karşılaştırmadan kaldırma fonksiyonu
    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-compare')) {
            const propertyId = parseInt(e.target.dataset.propertyId);
            removeFromCompare(propertyId);
            modal.remove();
            showCompareList(); // Listeyi yeniden göster
        }
    });
}

// Favorilerden kaldır
function removeFavorite(propertyId) {
    let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    favorites = favorites.filter((id) => id !== propertyId);
    localStorage.setItem('favorites', JSON.stringify(favorites));
    updateHeaderCounts();

    // Sayfadaki butonları güncelle
    const btn = document.querySelector(`[data-property-id="${propertyId}"].favorite-btn`);
    if (btn) {
        const icon = btn.querySelector('i');
        icon.classList.remove('text-red-500');
        icon.classList.add('text-gray-400');
        btn.classList.remove('border-red-300', 'bg-red-50');
        btn.classList.add('border-gray-200 dark:border-slate-700');
    }

    showNotification('Favorilerden çıkarıldı', 'info');
}

// Karşılaştırmadan kaldır
function removeFromCompare(propertyId) {
    let compareList = JSON.parse(localStorage.getItem('compare-list') || '[]');
    compareList = compareList.filter((id) => id !== propertyId);
    localStorage.setItem('compare-list', JSON.stringify(compareList));
    updateHeaderCounts();

    // Sayfadaki butonları güncelle
    const btn = document.querySelector(`[data-property-id="${propertyId}"].compare-btn`);
    if (btn) {
        const icon = btn.querySelector('i');
        icon.classList.remove('text-blue-500');
        icon.classList.add('text-gray-400');
        btn.classList.remove('border-blue-300', 'bg-blue-50');
        btn.classList.add('border-gray-200 dark:border-slate-700');
    }

    showNotification('Karşılaştırmadan çıkarıldı', 'info');
}

// Modal oluştur
function createModal(title, content) {
    const modal = document.createElement('div');
    modal.className =
        'fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-full overflow-y-auto dark:bg-slate-900">
            <div class="flex items-center justify-between p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100">${title}</h2>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                ${content}
            </div>
        </div>
    `;

    // Dışarı tıklayınca kapat
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });

    return modal;
}

// Favoriler HTML'i oluştur
function generateFavoritesHTML(favorites) {
    if (favorites.length === 0) {
        return '<p class="text-gray-500 text-center py-8">Henüz favori ilanınız yok.</p>';
    }

    let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';

    favorites.forEach((propertyId) => {
        html += `
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-medium text-gray-900 dark:text-slate-100">İlan #${propertyId}</h3>
                    <button class="remove-favorite text-red-500 hover:text-red-700" data-property-id="${propertyId}">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
                <div class="space-y-2">
                    <a href="/ilanlar/${propertyId}" class="text-primary-600 hover:text-primary-700 text-sm">
                        Detayları Gör <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        `;
    });

    html += '</div>';
    return html;
}

// Karşılaştırma HTML'i oluştur
function generateCompareHTML(compareList) {
    if (compareList.length === 0) {
        return '<p class="text-gray-500 text-center py-8">Karşılaştırma listeniz boş.</p>';
    }

    let html = `
        <div class="mb-4 text-center">
            <p class="text-gray-600">Karşılaştırma listenizde ${compareList.length} ilan var</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    `;

    compareList.forEach((propertyId) => {
        html += `
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-medium text-gray-900 dark:text-slate-100">İlan #${propertyId}</h3>
                    <button class="remove-compare text-blue-500 hover:text-blue-700" data-property-id="${propertyId}">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
                <div class="space-y-2">
                    <a href="/ilanlar/${propertyId}" class="text-primary-600 hover:text-primary-700 text-sm">
                        Detayları Gör <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        `;
    });

    html += '</div>';

    if (compareList.length >= 2) {
        html += `
            <div class="mt-6 text-center">
                <button onclick="startComparison()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-balance-scale mr-2"></i>
                    Karşılaştırmaya Başla
                </button>
            </div>
        `;
    }

    return html;
}

// Karşılaştırmaya başla
function startComparison() {
    const compareList = JSON.parse(localStorage.getItem('compare-list') || '[]');
    if (compareList.length < 2) {
        showNotification('En az 2 ilan seçmelisiniz', 'warning');
        return;
    }

    // Karşılaştırma sayfasına yönlendir
    const compareUrl = `/karsilastir?ilanlar=${compareList.join(',')}`;
    window.open(compareUrl, '_blank');
}

// Bildirim sistemi (global)
function showNotification(message, type = 'info') {
    // Notification container oluştur (yoksa)
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }

    // Notification element
    const notification = document.createElement('div');
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500',
    };

    notification.className = `${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
    notification.textContent = message;

    container.appendChild(notification);

    // Animasyon
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Otomatik kaldırma
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Sayfa yüklendiğinde header sayaçlarını güncelle
document.addEventListener('DOMContentLoaded', function () {
    updateHeaderCounts();
});
