/**
 * 🟢 POI Sealed Badges - Mühürlü Lokasyon Öznitelikleri
 * 
 * Harita koordinatları mühürlendiğinde, çevredeki POI'ler (nokta etkinlikleri)
 * badge olarak gösterilir. Her badge bir "Sealed" mühür taşır.
 * 
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0
 */

export class PoiSealedBadges {
    constructor() {
        this.badges = [];
        this.sealedCoordinates = null;
        this.container = null;
        
        console.log('✅ PoiSealedBadges initialized');
        this.init();
    }

    /**
     * Initialize container ve event listeners
     */
    init() {
        this.container = document.getElementById('poi-badges-container') ||
                        document.querySelector('[data-poi-badges]');
        
        if (!this.container) {
            console.warn('⚠️ POI badges container not found');
            return;
        }

        // Event listeners
        document.addEventListener('wizard:coordinates-sealed', (e) => {
            this.onCoordinatesSealed(e.detail);
        });

        console.log('✅ PoiSealedBadges: Init complete');
    }

    /**
     * Harita koordinatları mühürlendiğinde POI'leri yükle
     */
    async onCoordinatesSealed(detail) {
        const { lat, lng } = detail;

        console.log(`🔒 Coordinates sealed: ${lat}, ${lng}`);

        this.sealedCoordinates = { lat, lng };

        // PoiService API'den POI'leri al
        await this.fetchPois(lat, lng);
    }

    /**
     * PoiService API'den POI'leri çek
     */
    async fetchPois(lat, lng, radius = 2) {
        try {
            const response = await fetch('/api/v1/location/poi-distances', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    lat,
                    lng,
                    radius_km: radius,
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.badges = data.pois || [];

            console.log('✅ POIs fetched:', this.badges);

            // UI'de render et
            this.render();

        } catch (error) {
            console.error('❌ Failed to fetch POIs:', error);
            this.showError('POI veriler alınamadı');
        }
    }

    /**
     * POI badges'ı UI'de render et
     */
    render() {
        if (!this.container) return;

        // Container'ı temizle
        this.container.innerHTML = '';

        if (this.badges.length === 0) {
            this.container.innerHTML = `
                <div class="text-center py-4 text-gray-500 dark:text-slate-500">
                    <p class="text-sm">Yakınında POI bulunamadı</p>
                </div>
            `;
            return;
        }

        // Badges'ı kategorilere göre grupla
        const groupedByCategory = this.groupByCategory();

        Object.entries(groupedByCategory).forEach(([category, pois]) => {
            const categoryTitle = document.createElement('div');
            categoryTitle.className = 'mb-3';
            
            const title = document.createElement('h4');
            title.className = 'text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider';
            title.textContent = this.getCategoryLabel(category);
            categoryTitle.appendChild(title);

            const badgesRow = document.createElement('div');
            badgesRow.className = 'flex flex-wrap gap-2';

            pois.forEach(poi => {
                const badge = this.createBadge(poi, category);
                badgesRow.appendChild(badge);
            });

            categoryTitle.appendChild(badgesRow);
            this.container.appendChild(categoryTitle);
        });

        // Dispatch event
        document.dispatchEvent(new CustomEvent('wizard:poi-badges-rendered', {
            detail: {
                badges: this.badges,
                count: this.badges.length,
            }
        }));

        console.log('✅ POI badges rendered');
    }

    /**
     * Kategoriyle POI'leri grupla
     */
    groupByCategory() {
        return this.badges.reduce((acc, poi) => {
            const category = poi.category || 'other';
            if (!acc[category]) {
                acc[category] = [];
            }
            acc[category].push(poi);
            return acc;
        }, {});
    }

    /**
     * Kategori etiketini al
     */
    getCategoryLabel(category) {
        const labels = {
            'transportation': '🚌 Ulaşım',
            'health': '🏥 Sağlık',
            'education': '📚 Eğitim',
            'shopping': '🛒 Alışveriş',
            'entertainment': '🎭 Eğlence',
            'park': '🌳 Park',
            'water': '🌊 Su',
            'other': '📍 Diğer',
        };
        return labels[category] || category;
    }

    /**
     * POI badge HTML'i oluştur
     */
    createBadge(poi, category) {
        const badge = document.createElement('div');
        badge.className = `
            px-3 py-2 rounded-full text-xs font-medium
            border-2 border-transparent
            transition-all duration-300
            cursor-pointer
            hover:scale-110
            dark:text-white
        `;

        // Kategoriye göre renk
        const colors = {
            'transportation': 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
            'health': 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
            'education': 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
            'shopping': 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
            'entertainment': 'bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-700',
            'park': 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
            'water': 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-700',
            'other': 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700',
        };

        badge.className += '' + (colors[category] || colors['other']);

        // Badge içeriği
        const distance = poi.distance_km < 1 
            ? `${Math.round(poi.distance_km * 1000)}m`
            : `${poi.distance_km.toFixed(1)}km`;

        badge.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="font-bold">${this.getEmoji(category)}</span>
                <span>${poi.name || poi.title}</span>
                <span class="opacity-75">${distance}</span>
                <span class="ml-1 text-xs opacity-50">🔒</span>
            </div>
        `;

        // Click handler - badge bilgisini göster
        badge.addEventListener('click', () => {
            this.showBadgeDetail(poi);
        });

        // Sealed metadata ekle
        badge.dataset.poiId = poi.id;
        badge.dataset.distance = poi.distance_km;
        badge.dataset.category = category;
        badge.dataset.sealed = 'true';
        badge.setAttribute('aria-label', `${poi.name} - ${distance} uzaklıkta`);

        return badge;
    }

    /**
     * Emoji al kategoriye göre
     */
    getEmoji(category) {
        const emojis = {
            'transportation': '🚌',
            'health': '🏥',
            'education': '📚',
            'shopping': '🛒',
            'entertainment': '🎭',
            'park': '🌳',
            'water': '🌊',
            'other': '📍',
        };
        return emojis[category] || '📍';
    }

    /**
     * Badge detaylarını modal'da göster
     */
    showBadgeDetail(poi) {
        const modal = document.createElement('div');
        modal.className = `
            fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50
            transition-all duration-300
        `;

        const content = document.createElement('div');
        content.className = `
            bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4
            shadow-2xl dark:shadow-2xl
        `;

        content.innerHTML = `
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        ${poi.name || poi.title}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1 dark:text-slate-500">
                        ${this.getCategoryLabel(poi.category)}
                    </p>
                </div>
                <span class="text-2xl">${this.getEmoji(poi.category)}</span>
            </div>

            <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded dark:bg-slate-950">
                    <span class="text-sm text-gray-600 dark:text-slate-400">Mesafe:</span>
                    <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        ${poi.distance_km < 1 
                            ? `${Math.round(poi.distance_km * 1000)}m`
                            : `${poi.distance_km.toFixed(2)}km`
                        }
                    </span>
                </div>

                ${poi.address ? `
                <div class="flex items-start gap-2">
                    <span class="text-sm text-gray-600 dark:text-slate-400">Adres:</span>
                    <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">${poi.address}</span>
                </div>
                ` : ''}

                <div class="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs text-blue-700 dark:text-blue-300 font-medium">Bu lokasyon mühürlenmişdir</span>
                </div>
            </div>

            <button onclick="this.closest('[role=dialog]').remove()"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Kapat
            </button>
        `;

        modal.appendChild(content);
        modal.setAttribute('role', 'dialog');
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        document.body.appendChild(modal);
    }

    /**
     * Error message göster
     */
    showError(message) {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-sm text-red-700 dark:text-red-300">⚠️ ${message}</p>
            </div>
        `;
    }

    /**
     * Sealed badges'ları form'a ekle
     */
    addToFormData(formData) {
        if (!this.badges.length) return;

        // POI data'sını hidden field olarak ekle
        const poiDataField = document.createElement('input');
        poiDataField.type = 'hidden';
        poiDataField.name = 'sealed_poi_data';
        poiDataField.value = JSON.stringify(this.badges);

        // Coordinates data
        const coordField = document.createElement('input');
        coordField.type = 'hidden';
        coordField.name = 'sealed_coordinates';
        coordField.value = JSON.stringify(this.sealedCoordinates);

        const form = document.querySelector('form[data-wizard-form]');
        if (form) {
            form.appendChild(poiDataField);
            form.appendChild(coordField);
        }
    }
}

// Export as window global
if (typeof window !== 'undefined') {
    window.PoiSealedBadges = PoiSealedBadges;
}

export default PoiSealedBadges;
