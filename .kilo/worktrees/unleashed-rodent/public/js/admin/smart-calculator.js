/**
 * Smart Calculator - Alpine.js Component
 * EmlakPro Smart Calculator sistemi
 */

function smartCalculator() {
    return {
        // State
        selectedType: null,
        inputs: {},
        result: null,
        calculationTypes: {},
        history: [],
        favorites: [],
        showHistoryModal: false,
        showFavoritesModal: false,
        loading: false,

        // Initialize
        init() {
            this.loadCalculationTypes();
            this.loadHistory();
            this.loadFavorites();
        },

        // Load calculation types
        async loadCalculationTypes() {
            try {
                if (window.ApiAdapter) {
                    const res = await window.ApiAdapter.get('/calculator/types');
                    this.calculationTypes = res.data || {};
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    const result = await window.APIHelper?.request('admin.calculator.types');

                    if (result.success && result.data) {
                        this.calculationTypes = result.data;
                    } else {
                        const message = result.message || 'Hesaplama türleri yüklenemedi';
                        this.showMessage(message, 'error');
                    }
                }
            } catch (error) {
                console.error('Calculation types load error:', error);
                this.showMessage('Hesaplama türleri yüklenemedi', 'error');
            }
        },

        // Select calculation type
        selectCalculationType(type) {
            this.selectedType = type;
            this.inputs = {};
            this.result = null;
            this.setDefaultInputs(type);

            // Context-aware toast notification
            const theme = this.getCalculationTheme(type);
            this.showThemeToast(theme, type);
        },

        // Get calculation types by theme
        getCalculationTypesByTheme(theme) {
            const themeMapping = {
                basic: ['price_per_sqm', 'price_per_room', 'basic_calculation'],
                location: ['location_based', 'district_analysis', 'neighborhood_comparison'],
                features: ['feature_analysis', 'property_features', 'amenity_calculation'],
                media: ['advanced_calculation', 'investment_analysis', 'market_analysis'],
                system: ['system_calculation', 'admin_tools', 'bulk_calculation'],
            };

            const types = themeMapping[theme] || [];
            const result = {};

            types.forEach((type) => {
                if (this.calculationTypes[type]) {
                    result[type] = this.calculationTypes[type];
                }
            });

            return result;
        },

        // Get calculation theme
        getCalculationTheme(type) {
            const themeMapping = {
                price_per_sqm: 'basic',
                price_per_room: 'basic',
                basic_calculation: 'basic',
                location_based: 'location',
                district_analysis: 'location',
                neighborhood_comparison: 'location',
                feature_analysis: 'features',
                property_features: 'features',
                amenity_calculation: 'features',
                advanced_calculation: 'media',
                investment_analysis: 'media',
                market_analysis: 'media',
                system_calculation: 'system',
                admin_tools: 'system',
                bulk_calculation: 'system',
            };

            return themeMapping[type] || 'basic';
        },

        // Show theme-aware toast
        showThemeToast(theme, type) {
            const typeName = this.calculationTypes[type] || type;

            switch (theme) {
                case 'basic':
                    subtleVibrantToast.basic.info('Temel Hesaplama', `${typeName} seçildi`);
                    break;
                case 'location':
                    subtleVibrantToast.location.info('Konum Analizi', `${typeName} seçildi`);
                    break;
                case 'features':
                    subtleVibrantToast.features.info('Özellik Analizi', `${typeName} seçildi`);
                    break;
                case 'media':
                    subtleVibrantToast.media.info('Gelişmiş Analiz', `${typeName} seçildi`);
                    break;
                case 'system':
                    subtleVibrantToast.system.info('Sistem Hesaplaması', `${typeName} seçildi`);
                    break;
            }
        },

        // Get form theme class
        getFormThemeClass(type) {
            const theme = this.getCalculationTheme(type);
            return `subtle-vibrant-${theme}`;
        },

        // Get form title class
        getFormTitleClass(type) {
            const theme = this.getCalculationTheme(type);
            const colorMap = {
                basic: 'text-blue-800 dark:text-blue-200',
                location: 'text-green-800 dark:text-green-200',
                features: 'text-purple-800 dark:text-purple-200',
                media: 'text-orange-800 dark:text-orange-200',
                system: 'text-gray-800 dark:text-gray-200',
            };
            return colorMap[theme] || 'text-blue-800 dark:text-blue-200';
        },

        // Get calculate button class
        getCalculateButtonClass(type) {
            const theme = this.getCalculationTheme(type);
            const buttonMap = {
                basic: 'bg-blue-500 hover:bg-blue-600 text-white',
                location: 'bg-green-500 hover:bg-green-600 text-white',
                features: 'bg-purple-500 hover:bg-purple-600 text-white',
                media: 'bg-orange-500 hover:bg-orange-600 text-white',
                system: 'bg-gray-500 hover:bg-gray-600 text-white',
            };
            return buttonMap[theme] || 'bg-blue-500 hover:bg-blue-600 text-white';
        },

        // Show success toast
        showSuccessToast(theme, typeName) {
            switch (theme) {
                case 'basic':
                    subtleVibrantToast.basic.success(
                        'Hesaplama Tamamlandı',
                        `${typeName} başarıyla hesaplandı`
                    );
                    break;
                case 'location':
                    subtleVibrantToast.location.success(
                        'Konum Analizi',
                        `${typeName} analizi tamamlandı`
                    );
                    break;
                case 'features':
                    subtleVibrantToast.features.success(
                        'Özellik Analizi',
                        `${typeName} analizi tamamlandı`
                    );
                    break;
                case 'media':
                    subtleVibrantToast.media.success(
                        'Gelişmiş Analiz',
                        `${typeName} analizi tamamlandı`
                    );
                    break;
                case 'system':
                    subtleVibrantToast.system.success(
                        'Sistem Hesaplaması',
                        `${typeName} tamamlandı`
                    );
                    break;
            }
        },

        // Show error toast
        showErrorToast(theme, typeName, error) {
            switch (theme) {
                case 'basic':
                    subtleVibrantToast.basic.error('Hesaplama Hatası', `${typeName}: ${error}`);
                    break;
                case 'location':
                    subtleVibrantToast.location.error(
                        'Konum Analizi Hatası',
                        `${typeName}: ${error}`
                    );
                    break;
                case 'features':
                    subtleVibrantToast.features.error(
                        'Özellik Analizi Hatası',
                        `${typeName}: ${error}`
                    );
                    break;
                case 'media':
                    subtleVibrantToast.media.error(
                        'Gelişmiş Analiz Hatası',
                        `${typeName}: ${error}`
                    );
                    break;
                case 'system':
                    subtleVibrantToast.system.error(
                        'Sistem Hesaplaması Hatası',
                        `${typeName}: ${error}`
                    );
                    break;
            }
        },

        // Set default inputs
        setDefaultInputs(type) {
            switch (type) {
                case 'price_per_sqm':
                    this.inputs = { metrekare: '', birim_fiyat: '' };
                    break;
                case 'price_per_room':
                    this.inputs = { oda_sayisi: '', oda_basi_fiyat: '' };
                    break;
                case 'mortgage_loan':
                    this.inputs = {
                        kredi_tutari: '',
                        vade: '',
                        faiz_orani: '',
                    };
                    break;
                case 'roi_calculation':
                    this.inputs = {
                        yatirim_tutari: '',
                        yillik_gelir: '',
                        yillik_gider: 0,
                    };
                    break;
                case 'vat_calculation':
                    this.inputs = { kdvsiz_fiyat: '', kdv_orani: 18 };
                    break;
                case 'sales_commission':
                    this.inputs = { satis_fiyati: '', komisyon_orani: 3 };
                    break;
                case 'taks_calculation':
                    this.inputs = { arsa_alani: '', taks_orani: '' };
                    break;
            }
        },

        // Check if can calculate
        canCalculate() {
            if (!this.selectedType) return false;
            const requiredFields = this.getRequiredFields(this.selectedType);
            return requiredFields.every((field) => {
                const value = this.inputs[field];
                return value !== '' && value !== null && value !== undefined && value > 0;
            });
        },

        // Get required fields
        getRequiredFields(type) {
            const requiredFields = {
                price_per_sqm: ['metrekare', 'birim_fiyat'],
                price_per_room: ['oda_sayisi', 'oda_basi_fiyat'],
                mortgage_loan: ['kredi_tutari', 'vade', 'faiz_orani'],
                roi_calculation: ['yatirim_tutari', 'yillik_gelir'],
                vat_calculation: ['kdvsiz_fiyat'],
                sales_commission: ['satis_fiyati'],
                taks_calculation: ['arsa_alani', 'taks_orani'],
            };
            return requiredFields[type] || [];
        },

        // Calculate
        async calculate() {
            if (!this.canCalculate()) {
                this.showMessage('Lütfen tüm gerekli alanları doldurun', 'warning');
                return;
            }

            this.loading = true;

            try {
                if (window.ApiAdapter) {
                    const res = await window.ApiAdapter.post('/calculator/calculate', {
                        type: this.selectedType,
                        inputs: this.inputs,
                    });
                    this.result = res.data;
                    this.showMessage('Hesaplama başarıyla tamamlandı!', 'success');
                    const theme = this.getCalculationTheme(this.selectedType);
                    const typeName = this.calculationTypes[this.selectedType] || this.selectedType;
                    this.showSuccessToast(theme, typeName);
                    this.loadHistory();
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    const result = await window.APIHelper?.request('admin.calculator.calculate', {
                        method: 'POST',
                        body: JSON.stringify({ type: this.selectedType, inputs: this.inputs }),
                    });

                    if (result.success && result.data) {
                        this.result = result.data;
                        this.showMessage('Hesaplama başarıyla tamamlandı!', 'success');
                        const theme = this.getCalculationTheme(this.selectedType);
                        const typeName =
                            this.calculationTypes[this.selectedType] || this.selectedType;
                        this.showSuccessToast(theme, typeName);
                        this.loadHistory();
                    } else {
                        const message = result.message || 'Hesaplama hatası';
                        this.showMessage(message, 'error');
                        const theme = this.getCalculationTheme(this.selectedType);
                        const typeName =
                            this.calculationTypes[this.selectedType] || this.selectedType;
                        this.showErrorToast(theme, typeName, data.error || data.message);
                    }
                }
            } catch (error) {
                console.error('Calculation error:', error);
                this.showMessage('Hesaplama sırasında bir hata oluştu', 'error');
            } finally {
                this.loading = false;
            }
        },

        // Get calculation icon
        getCalculationIcon(type) {
            const icons = {
                price_per_sqm: 'fas fa-ruler-combined text-blue-600',
                price_per_room: 'fas fa-home text-green-600',
                mortgage_loan: 'fas fa-university text-purple-600',
                roi_calculation: 'fas fa-chart-line text-green-600',
                vat_calculation: 'fas fa-receipt text-red-600',
                sales_commission: 'fas fa-handshake text-purple-600',
                taks_calculation: 'fas fa-drafting-compass text-orange-600',
            };
            return icons[type] || 'fas fa-calculator text-gray-600';
        },

        // Get result label
        getResultLabel(key) {
            const labels = {
                formatted_toplam_fiyat: 'Toplam Fiyat',
                formatted_metrekare_basi: 'Metrekare Başına',
                formatted_aylik_taksit: 'Aylık Taksit',
                formatted_toplam_odeme: 'Toplam Ödeme',
                formatted_net_gelir: 'Net Gelir',
                formatted_roi: 'ROI',
                formatted_kdv_tutari: 'KDV Tutarı',
                formatted_komisyon_tutari: 'Komisyon Tutarı',
            };
            return labels[key] || key.replace('formatted_', '').replace(/_/g, ' ').toUpperCase();
        },

        // Show message
        showMessage(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                type === 'success'
                    ? 'bg-green-500 text-white'
                    : type === 'error'
                      ? 'bg-red-500 text-white'
                      : type === 'warning'
                        ? 'bg-yellow-500 text-white'
                        : 'bg-blue-500 text-white'
            }`;

            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${
                        type === 'success'
                            ? 'check'
                            : type === 'error'
                              ? 'exclamation-triangle'
                              : type === 'warning'
                                ? 'exclamation'
                                : 'info-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        },

        // Load history
        async loadHistory() {
            try {
                if (window.ApiAdapter) {
                    const res = await window.ApiAdapter.get('/calculator/history', {
                        per_page: 50,
                        page: 1,
                    });
                    this.history = res.data || [];
                    this.historyMeta = res.meta || null;
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    // History endpoint string, query parametreleri ekleniyor
                    const historyUrl = window.APIConfig?.admin?.calculator?.history
                        ? `${window.APIConfig.admin.calculator.history}?per_page=50&page=1`
                        : null;

                    if (!historyUrl) {
                        console.error('❌ History endpoint tanımlı değil');
                        return;
                    }

                    const response = await window.APIHelper?.safeFetch(historyUrl, {
                        method: 'GET',
                    });

                    if (response.ok) {
                        const result = await window.APIHelper?.handleResponse(response);
                        if (result.success && result.data) {
                            this.history = Array.isArray(result.data)
                                ? result.data
                                : result.data.data || [];
                            this.historyMeta = result.data.meta || result.meta || null;
                        }
                    }
                }
            } catch (error) {
                console.error('History load error:', error);
            }
        },

        // Load favorites
        async loadFavorites() {
            try {
                if (window.ApiAdapter) {
                    const res = await window.ApiAdapter.get('/calculator/favorites');
                    this.favorites = res.data || [];
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    const result = await window.APIHelper?.request('admin.calculator.favorites');

                    if (result.success && result.data) {
                        this.favorites = Array.isArray(result.data) ? result.data : [];
                    }
                }
            } catch (error) {
                console.error('Favorites load error:', error);
            }
        },

        // Show history
        showHistory() {
            this.showHistoryModal = true;
        },

        // Show favorites
        showFavorites() {
            this.showFavoritesModal = true;
        },

        // Save to favorites
        async saveToFavorites() {
            if (!this.result) {
                this.showMessage('Önce bir hesaplama yapın', 'warning');
                return;
            }

            const name = prompt('Favori adı giriniz:');
            if (!name) return;

            const description = prompt('Açıklama (opsiyonel):') || '';

            try {
                if (window.ApiAdapter) {
                    await window.ApiAdapter.post('/calculator/favorites', {
                        type: this.selectedType,
                        name,
                        inputs: this.inputs,
                        description,
                    });
                    this.showMessage('Favori hesaplama kaydedildi!', 'success');
                    this.loadFavorites();
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    const result = await window.APIHelper?.request('admin.calculator.favorites', {
                        method: 'POST',
                        body: JSON.stringify({
                            type: this.selectedType,
                            name,
                            inputs: this.inputs,
                            description,
                        }),
                    });

                    if (result.success) {
                        this.showMessage('Favori hesaplama kaydedildi!', 'success');
                        this.loadFavorites();
                    } else {
                        const message = result.message || 'Favori kaydedilemedi';
                        this.showMessage(message, 'error');
                    }
                }
            } catch (error) {
                console.error('Save favorite error:', error);
                this.showMessage('Favori kaydedilirken bir hata oluştu', 'error');
            }
        },

        // Share result
        shareResult() {
            if (!this.result) {
                this.showMessage('Önce bir hesaplama yapın', 'warning');
                return;
            }

            const resultText = this.generateResultText();

            if (navigator.share) {
                navigator.share({
                    title: 'Smart Calculator Sonucu',
                    text: resultText,
                });
            } else {
                navigator.clipboard.writeText(resultText).then(() => {
                    this.showMessage('Sonuç panoya kopyalandı!', 'success');
                });
            }
        },

        // Generate result text
        generateResultText() {
            let text = `🧮 Smart Calculator Sonucu\n`;
            text += `Hesaplama Türü: ${this.calculationTypes[this.selectedType]}\n\n`;

            text += `Giriş Değerleri:\n`;
            Object.entries(this.inputs).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) {
                    text += `• ${key}: ${value}\n`;
                }
            });

            text += `\nSonuçlar:\n`;
            Object.entries(this.result).forEach(([key, value]) => {
                if (key.startsWith('formatted_')) {
                    const label = this.getResultLabel(key);
                    text += `• ${label}: ${value}\n`;
                }
            });

            return text;
        },

        // Reset calculation
        resetCalculation() {
            this.result = null;
            this.inputs = {};
            if (this.selectedType) {
                this.setDefaultInputs(this.selectedType);
            }
        },

        // Load from history
        loadFromHistory(item) {
            this.selectedType = item.calculation_type;
            this.inputs = item.input_data;
            this.result = item.result_data;
            this.showHistoryModal = false;
            this.showMessage('Hesaplama geçmişten yüklendi!', 'success');
        },

        // Load from favorites
        loadFromFavorites(item) {
            this.selectedType = item.calculation_type;
            this.inputs = item.input_data;
            this.result = null;
            this.showFavoritesModal = false;
            this.showMessage('Favori hesaplama yüklendi!', 'success');
        },

        // Remove favorite
        async removeFavorite(id) {
            if (!confirm('Bu favori hesaplamayı silmek istediğinizden emin misiniz?')) {
                return;
            }

            try {
                if (window.ApiAdapter) {
                    await window.ApiAdapter.delete(`/calculator/favorites/${id}`);
                    this.showMessage('Favori hesaplama silindi!', 'success');
                    this.loadFavorites();
                } else {
                    // ✅ API Helper kullan (merkezi yönetim)
                    // Favorites DELETE endpoint string, id URL'e ekleniyor
                    const deleteUrl = window.APIConfig?.admin?.calculator?.favorites
                        ? `${window.APIConfig.admin.calculator.favorites}/${id}`
                        : null;

                    if (!deleteUrl) {
                        console.error('❌ Favorites DELETE endpoint tanımlı değil');
                        this.showMessage('API config yüklenemedi. Sayfayı yenileyin.', 'error');
                        return;
                    }

                    const response = await window.APIHelper?.safeFetch(deleteUrl, {
                        method: 'DELETE',
                    });

                    if (response.ok) {
                        const result = await window.APIHelper?.handleResponse(response);
                        if (result.success) {
                            this.showMessage('Favori hesaplama silindi!', 'success');
                            this.loadFavorites();
                        } else {
                            const message = result.message || 'Favori silinemedi';
                            this.showMessage(message, 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('Remove favorite error:', error);
                this.showMessage('Favori silinirken bir hata oluştu', 'error');
            }
        },
    };
}
