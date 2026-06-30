/**
 * 🔱 MOD-1: İlan Sihirbazı - Ana Entegrasyon Orchestrator
 *
 * Bu script tüm MOD-1 bileşenlerini orkestrayle:
 * 1. WizardCategorySync: Harita + Kategori sinkronizasyonu
 * 2. WizardFormHandler: Form alanları + UPS özellikler
 * 3. POI Engine: Haversine mesafe hesaplama
 * 4. Sealed Fields: Cihaz verisi mühürleme (Bosch/FLIR)
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0 MOD-1 Complete Integration
 */

// [console-zero] removed

// ============================================================================
// 1️⃣ DEPENDENCIES CHECK
// ============================================================================

const MOD1_DEPENDENCIES = {
    Leaflet: typeof L !== 'undefined',
    WizardFormHandler: typeof window.WizardFormHandler !== 'undefined',
    WizardCategorySync: () => document.querySelector('[x-data*="WizardCategorySync"]'),
};

function checkDependencies() {
    const missing = Object.entries(MOD1_DEPENDENCIES).filter(([name, check]) => {
        const isMissing = typeof check === 'function' ? !check() : !check;
        if (isMissing)
            return isMissing;
    });

    return missing.length === 0;
}

if (!checkDependencies()) {

    setTimeout(() => {
        if (checkDependencies()) initMOD1System();
        else { /* [console-zero] bağımlılıklar eksik — sessiz devam */ }
    }, 1000);
} else {
    initMOD1System();
}

// ============================================================================
// 2️⃣ MAIN INITIALIZATION
// ============================================================================

function initMOD1System() {


    // Form handler'ı başlat
    const formHandler = new window.WizardFormHandler(
        document.querySelector('form[data-wizard-form]') || document.querySelector('form')
    );

    // ============================================================================
    // 3️⃣ EVENT LISTENER: Kategori Değişim
    // ============================================================================
    document.addEventListener('wizard:category-changed', async (event) => {
        const { kategoriSlug, kategoriId, features } = event.detail;



        // Form handler'daki kategori değişim metodunu çağır
        if (formHandler) {
            await formHandler.onCategoryChanged({
                kategoriSlug,
                kategoriId,
                features,
            });
        }

        // Old step1-map.js notification (deprecated ama backward compat için)
        dispatchLegacyEvent('category-changed', { kategoriSlug });
    });

    // ============================================================================
    // 4️⃣ EVENT LISTENER: Yayın Tipi Değişim
    // ============================================================================
    document.addEventListener('wizard:publication-type-changed', (event) => {
        const { yayinTipiSlug, yayinTipiId } = event.detail;



        // Form alanlarını toggle et
        if (formHandler) {
            formHandler.onPublicationTypeChanged({
                yayinTipiSlug,
                yayinTipiId,
            });
        }
    });

    // ============================================================================
    // 5️⃣ EVENT LISTENER: Koordinat Mühürleme
    // ============================================================================
    document.addEventListener('wizard:coordinates-sealed', (event) => {
        const { lat, lng, address, sealed, source } = event.detail;



        // Koordinatları form'a mühürle
        if (formHandler) {
            formHandler.onCoordinatesSealed({
                lat,
                lng,
                address,
                sealed,
                source,
            });
        }

        // Legacy event (deprecated)
        dispatchLegacyEvent('coordinates-sealed', { lat, lng, address });
    });

    // ============================================================================
    // 6️⃣ EVENT LISTENER: POI Mühürlü Etiketler
    // ============================================================================
    document.addEventListener('wizard:poi-badges-ready', (event) => {
        const { pois, nearbyPois, totalCount } = event.detail;



        // POI badges'ları render et
        if (formHandler) {
            formHandler.onPoiBadgesReady({
                pois,
                nearbyPois,
                totalCount,
            });
        }

        // POI sidebar'ı güncelle (legacy)
        if (window.updatePoiSidebar && pois) {
            window.updatePoiSidebar(pois);
        }
    });

    // ============================================================================
    // 7️⃣ CIHAZ VERİSİ ENTEGRASYON (Bosch GLM 50-27 CG + FLIR)
    // ============================================================================

    // Bluetooth'tan gelen cihaz verisi dinleyicisi
    window.addEventListener('devicedata:received', async (event) => {
        const { deviceType, measurement, latitude, longitude } = event.detail;



        if (formHandler && latitude && longitude) {
            // Cihaz verisini mühürlü alan olarak kaydet
            formHandler.onCoordinatesSealed({
                lat: latitude,
                lng: longitude,
                address: `${deviceType} ile ölçülen lokasyon`,
                sealed: true, // Cihaz verisi ALWAYS sealed
                source: deviceType, // 'bosch-glm' or 'flir-camera'
            });

            // UI'da cihaz onayı badge'i göster
            formHandler.showSealedBadge(
                `✅ Cihaz Onaylı: ${deviceType} tarafından doğrulanmış koordinatlar`,
                'success'
            );
        }
    });

    // ============================================================================
    // 8️⃣ FORM SUBMISSION HANDLER
    // ============================================================================
    const formEl =
        document.querySelector('form[data-wizard-form]') || document.querySelector('form');
    if (formEl) {
        formEl.addEventListener('submit', (e) => {


            // Form verilerini sealed fields ile birlikte al
            const formData = formHandler.getFormData();


            // Custom submit handler (opsiyonel)
            if (window.handleWizardSubmit) {
                e.preventDefault();
                window.handleWizardSubmit(formData);
            }
        });
    }

    // ============================================================================
    // 9️⃣ LEGACY CODE CLEANUP (Deprecated step1-map.js)
    // ============================================================================



    // Deprecated event'leri block et
    document.addEventListener(
        'wizard-map-marker-moved',
        (e) => {
            // [console-zero] Deprecated event: wizard-map-marker-moved — blocked silently
            e.preventDefault();
            e.stopPropagation();
        },
        true
    );

    // Deprecated global functions'ı override et
    window.fetchWizardNearbyPOIs = function (...args) {
        // [console-zero] Deprecated function stub — silent redirect
        return Promise.resolve([]);
    };

    // [console-zero] Başlatma tamamlandı — server-side log aktif
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Legacy event dispatch (backward compatibility)
 */
function dispatchLegacyEvent(eventName, detail) {
    if (typeof CustomEvent !== 'undefined') {
        document.dispatchEvent(new CustomEvent(`deprecated:${eventName}`, { detail }));
    }
}

/**
 * Form data validation
 */
function validateWizardForm(formData) {
    const errors = [];

    if (!formData.lat || !formData.lng) {
        errors.push('Harita üzerinde konum seçilmelidir');
    }

    if (!formData.alt_kategori_id) {
        errors.push('Kategori seçilmelidir');
    }

    if (!formData.junction_id) {
        errors.push('Yayın tipi seçilmelidir');
    }

    return {
        valid: errors.length === 0,
        errors,
    };
}

/**
 * Alert göster
 */
function showWizardAlert(message, type = 'info') {
    const alert = document.getElementById('form-sealed-alert') || document.createElement('div');
    alert.textContent = message;
    alert.className = `p-4 rounded-lg mb-4 ${type === 'success'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
        : type === 'error'
            ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
            : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
        }`;
    alert.classList.remove('hidden');
}

// Export functions
window.MOD1 = {
    validateForm: validateWizardForm,
    showAlert: showWizardAlert,
    getFormData: () => {
        const handler = document.__wizardFormHandler;
        return handler ? handler.getFormData() : null;
    },
};

// [console-zero] removed
