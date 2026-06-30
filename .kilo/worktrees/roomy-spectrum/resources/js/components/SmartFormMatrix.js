/**
 * Smart Forms Matrix Component (Alpine.js)
 *
 * Excel-like matrix UI for managing feature visibility per publication type.
 * Rows = Features, Columns = Publication Types
 * Cells = Visibility checkbox + Required toggle
 *
 * @example
 * <div x-data="smartFormMatrix({{ $kategori->id }})">
 *   <!-- Matrix renders here -->
 * </div>
 *
 * Context7 Compliance:
 * - ✅ No forbidden keywords (uses `is_visible`, `visibility_state`)
 * - ✅ const/let only (no var)
 * - ✅ Tailwind CSS with dark mode
 * - ✅ Fail-safe error handling
 */

/**
 * Creates Smart Forms Matrix Alpine.js component
 *
 * @param {number} kategoriId - Category ID to load matrix for
 * @returns {Object} Alpine.js component data
 */
export default function smartFormMatrix(kategoriId) {
    return {
        // === DATA STATE ===
        loading: true,
        saving: false,

        matrix: {}, // { featureId: { yayinTipiId: { is_visible: bool, is_required: bool } } }
        features: [], // [ { id, adi, kod, kategori_adi } ]
        yayinTipleri: [], // [ { id, yayin_tipi, kategori_adi } ]

        error: null,
        lastSaveTime: null,

        // === LIFECYCLE ===
        async init() {
            try {
                this.loading = true;
                this.error = null;

                await this.loadMatrix();

                this.loading = false;
                this.showToast('Matrix yüklendi', 'success');
            } catch (error) {
                console.error('❌ Smart Forms Matrix init error:', error);
                this.error = error.message || 'Matrix yüklenirken hata oluştu';
                this.loading = false;
                this.showToast(this.error, 'error');
            }
        },

        // === API METHODS ===

        /**
         * Load matrix data from API
         */
        async loadMatrix() {
            const response = await fetch(`/api/v1/admin/smart-form/matrix/${kategoriId}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`API request failed`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Matrix yüklenemedi');
            }

            // Structure: { matrix, features, yayin_tipleri }
            this.matrix = result.data.matrix || {};
            this.features = result.data.features || [];
            this.yayinTipleri = result.data.yayin_tipleri || [];

            console.log('✅ Matrix loaded:', {
                features: this.features.length,
                yayinTipleri: this.yayinTipleri.length,
                matrixSize: Object.keys(this.matrix).length,
            });
        },

        /**
         * Update visibility for a specific feature-publication pair
         *
         * @param {number} yayinTipiId - Publication type ID
         * @param {number} featureId - Feature ID
         * @param {string} field - 'is_visible' or 'is_required'
         * @param {boolean} value - New value
         */
        async updateVisibility(yayinTipiId, featureId, field, value) {
            try {
                this.saving = true;

                // Optimistic update
                const oldValue = this.matrix[featureId]?.[yayinTipiId]?.[field];
                if (!this.matrix[featureId]) this.matrix[featureId] = {};
                if (!this.matrix[featureId][yayinTipiId]) {
                    this.matrix[featureId][yayinTipiId] = { is_visible: true, is_required: false };
                }
                this.matrix[featureId][yayinTipiId][field] = value;

                // Send to API
                const response = await fetch(`/api/v1/admin/smart-form/update-visibility`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                    body: JSON.stringify({
                        kategori_id: kategoriId,
                        junction_id: yayinTipiId,
                        feature_id: featureId,
                        [field]: value,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`Update request failed`);
                }

                const result = await response.json();

                if (!result.success) {
                    // Rollback on failure
                    this.matrix[featureId][yayinTipiId][field] = oldValue;
                    throw new Error(result.message || 'Güncelleme başarısız');
                }

                this.lastSaveTime = new Date().toLocaleTimeString('tr-TR');
                this.showToast('Kaydedildi', 'success');
            } catch (error) {
                console.error('❌ Update visibility error:', error);
                this.showToast(error.message || 'Kaydetme hatası', 'error');
            } finally {
                this.saving = false;
            }
        },

        // === UI HELPERS ===

        /**
         * Get visibility state for a specific cell
         *
         * @param {number} featureId - Feature ID
         * @param {number} yayinTipiId - Publication type ID
         * @returns {Object} { is_visible, is_required }
         */
        getCellState(featureId, yayinTipiId) {
            return (
                this.matrix[featureId]?.[yayinTipiId] || {
                    is_visible: true,
                    is_required: false,
                }
            );
        },

        /**
         * Toggle visibility checkbox
         */
        async toggleVisibility(yayinTipiId, featureId, event) {
            const checked = event.target.checked;
            await this.updateVisibility(yayinTipiId, featureId, 'is_visible', checked);

            // If hiding, automatically unset required
            if (!checked) {
                const cellState = this.getCellState(featureId, yayinTipiId);
                if (cellState.is_required) {
                    await this.updateVisibility(yayinTipiId, featureId, 'is_required', false);
                }
            }
        },

        /**
         * Toggle required checkbox
         */
        async toggleRequired(yayinTipiId, featureId, event) {
            const checked = event.target.checked;
            await this.updateVisibility(yayinTipiId, featureId, 'is_required', checked);

            // If making required, automatically make visible
            if (checked) {
                const cellState = this.getCellState(featureId, yayinTipiId);
                if (!cellState.is_visible) {
                    await this.updateVisibility(yayinTipiId, featureId, 'is_visible', true);
                }
            }
        },

        /**
         * Get summary statistics
         */
        getSummary() {
            const summary = {
                total_features: this.features.length,
                total_yayin_tipleri: this.yayinTipleri.length,
                visible_count: 0,
                required_count: 0,
                hidden_count: 0,
            };

            this.features.forEach((feature) => {
                this.yayinTipleri.forEach((yayinTipi) => {
                    const cellState = this.getCellState(feature.id, yayinTipi.id);
                    if (cellState.is_visible) {
                        summary.visible_count++;
                        if (cellState.is_required) {
                            summary.required_count++;
                        }
                    } else {
                        summary.hidden_count++;
                    }
                });
            });

            return summary;
        },

        /**
         * Show toast notification
         */
        showToast(message, type = 'info') {
            const event = new CustomEvent('show-toast', {
                detail: { message, type },
            });
            window.dispatchEvent(event);

            // Fallback: console log
            const emoji = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            console.log(`${emoji} ${message}`);
        },

        /**
         * Get CSRF token
         */
        getCsrfToken() {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');
            if (!token) {
                console.warn('⚠️ CSRF token not found');
            }
            return token || '';
        },
    };
}

// Expose globally for inline usage
if (typeof window !== 'undefined') {
    window.smartFormMatrix = smartFormMatrix;
}
