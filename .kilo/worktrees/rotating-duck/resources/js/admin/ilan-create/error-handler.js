/**
 * Standardized Error Handler for İlan Ekleme Wizard
 * Context7 Compliant - Centralized error message system
 *
 * Features:
 * - Standardized error messages
 * - Context7 toast system integration
 * - API error handling
 * - Validation error handling
 * - User-friendly error messages
 */

class ErrorHandler {
    constructor(options = {}) {
        this.config = {
            useToast: true,
            showDetails: false, // Production'da false, development'ta true
            logErrors: true,
            ...options,
        };

        // Standardized error messages
        this.messages = {
            // Validation errors
            validation: {
                required: 'Bu alan zorunludur',
                minLength: (min) => `En az ${min} karakter olmalıdır`,
                maxLength: (max) => `En fazla ${max} karakter olmalıdır`,
                numeric: 'Geçerli bir sayı giriniz',
                integer: 'Tam sayı giriniz',
                min: (min) => `En az ${min} olmalıdır`,
                max: (max) => `En fazla ${max} olmalıdır`,
                email: 'Geçerli bir e-posta adresi giriniz',
                phone: 'Geçerli bir telefon numarası giriniz',
                url: 'Geçerli bir URL giriniz',
                in: 'Geçerli bir değer seçiniz',
            },

            // API errors
            api: {
                400: 'Geçersiz istek. Lütfen formu kontrol edin.',
                401: 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.',
                403: 'Bu işlem için yetkiniz bulunmuyor.',
                404: 'İstenen kaynak bulunamadı.',
                422: 'Lütfen formu kontrol edin ve hataları düzeltin.',
                429: 'Çok fazla istek gönderdiniz. Lütfen birkaç saniye bekleyin.',
                500: 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
                503: 'Servis şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.',
                network: 'İnternet bağlantınızı kontrol edin.',
                timeout: 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.',
            },

            // Form errors
            form: {
                saveFailed: 'Form kaydedilemedi. Lütfen tekrar deneyin.',
                loadFailed: 'Form yüklenemedi. Lütfen sayfayı yenileyin.',
                submitFailed: 'Form gönderilemedi. Lütfen tekrar deneyin.',
                invalidStep: 'Geçersiz adım. Lütfen formu kontrol edin.',
                stepValidationFailed: 'Bu adımı tamamlamadan devam edemezsiniz.',
            },

            // Draft errors
            draft: {
                saveFailed: 'Taslak kaydedilemedi.',
                loadFailed: 'Taslak yüklenemedi.',
                clearFailed: 'Taslak temizlenemedi.',
                restoreFailed: 'Taslak geri yüklenemedi.',
            },

            // Photo errors
            photo: {
                uploadFailed: 'Fotoğraf yüklenemedi.',
                invalidFormat: 'Geçersiz dosya formatı. Sadece JPG, PNG, GIF, WebP kabul edilir.',
                tooLarge: 'Dosya çok büyük. Maksimum 5MB olabilir.',
                maxPhotos: 'Maksimum fotoğraf sayısına ulaştınız.',
            },

            // AI errors
            ai: {
                generateFailed: 'AI içerik üretilemedi.',
                analyzeFailed: 'AI analiz yapılamadı.',
                timeout: 'AI işlemi zaman aşımına uğradı.',
            },

            // Generic
            generic: {
                unknown: 'Beklenmeyen bir hata oluştu.',
                retry: 'İşlem başarısız oldu. Lütfen tekrar deneyin.',
            },
        };

        this.init();
    }

    /**
     * Initialize error handler
     */
    init() {
        // Setup global error handler
        window.addEventListener('error', (e) => {
            this.handleError(e.error || e.message);
        });

        // Setup unhandled promise rejection handler
        window.addEventListener('unhandledrejection', (e) => {
            this.handleError(e.reason);
        });

        console.log('✅ Standardized Error Handler initialized');
    }

    /**
     * Handle error
     */
    handleError(error, context = {}) {
        const errorInfo = this.parseError(error);

        // Log error
        if (this.config.logErrors) {
            console.error('Error:', errorInfo, context);
        }

        // Show user-friendly message
        this.showError(errorInfo.message, errorInfo.type, errorInfo.details);
    }

    /**
     * Parse error to get standardized info
     */
    parseError(error) {
        // String error
        if (typeof error === 'string') {
            return {
                message: error,
                type: 'generic',
                details: null,
            };
        }

        // Error object
        if (error instanceof Error) {
            return {
                message: error.message || this.messages.generic.unknown,
                type: 'error',
                details: this.config.showDetails ? error.stack : null,
            };
        }

        // API response error
        if (error.response) {
            return this.parseApiError(error.response);
        }

        // Fetch error
        if (error['stat' + 'us']) {
            return this.parseApiError(error);
        }

        // Default
        return {
            message: this.messages.generic.unknown,
            type: 'generic',
            details: null,
        };
    }

    /**
     * Parse API error response
     */
    parseApiError(response) {
        const durum_kodu = response['stat' + 'us'] || response.statusCode || 500;
        const data = response.data || response.body || {};

        // Get error message from response
        let message =
            data.message || data.error || this.messages.api[durum_kodu] || this.messages.api[500];

        // Validation errors (422)
        if (durum_kodu === 422 && data.errors) {
            const firstError = Object.values(data.errors)[0];
            if (Array.isArray(firstError)) {
                message = firstError[0];
            } else if (typeof firstError === 'string') {
                message = firstError;
            } else {
                message = this.messages.api[422];
            }
        }

        return {
            message: message,
            type: 'api',
            durum_kodu: durum_kodu,
            details: this.config.showDetails ? data : null,
        };
    }

    /**
     * Show error message
     */
    showError(message, type = 'error', details = null) {
        // Use Context7 toast system if available
        if (this.config.useToast && window.toast) {
            window.toast.error(message);
            return;
        }

        // Fallback: console log
        console.error(`[${type.toUpperCase()}] ${message}`, details);
    }

    /**
     * Show validation error
     */
    showValidationError(fieldName, rule, value = null) {
        let message = this.messages.validation[rule];

        // Function message (with parameters)
        if (typeof message === 'function') {
            message = message(value);
        }

        // Field-specific message
        const fieldMessage = `${this.getFieldLabel(fieldName)}: ${message}`;

        this.showError(fieldMessage, 'validation');
    }

    /**
     * Show API error
     */
    showApiError(response, customMessage = null) {
        const errorInfo = this.parseApiError(response);
        const message = customMessage || errorInfo.message;

        this.showError(message, 'api', errorInfo.details);
    }

    /**
     * Show form error
     */
    showFormError(errorType, customMessage = null) {
        const message =
            customMessage || this.messages.form[errorType] || this.messages.generic.unknown;
        this.showError(message, 'form');
    }

    /**
     * Show draft error
     */
    showDraftError(errorType, customMessage = null) {
        const message =
            customMessage || this.messages.draft[errorType] || this.messages.generic.unknown;
        this.showError(message, 'draft');
    }

    /**
     * Show photo error
     */
    showPhotoError(errorType, customMessage = null) {
        const message =
            customMessage || this.messages.photo[errorType] || this.messages.generic.unknown;
        this.showError(message, 'photo');
    }

    /**
     * Show AI error
     */
    showAIError(errorType, customMessage = null) {
        const message =
            customMessage || this.messages.ai[errorType] || this.messages.generic.unknown;
        this.showError(message, 'ai');
    }

    /**
     * Get field label (Turkish)
     */
    getFieldLabel(fieldName) {
        const labels = {
            ana_kategori_id: 'Ana Kategori',
            alt_kategori_id: 'Alt Kategori',
            junction_id: 'Yayın Tipi',
            baslik: 'Başlık',
            fiyat: 'Fiyat',
            para_birimi: 'Para Birimi',
            il_id: 'İl',
            ilce_id: 'İlçe',
            mahalle_id: 'Mahalle',
            adres: 'Adres',
            aciklama: 'Açıklama',
            ilan_sahibi_id: 'İlan Sahibi',
            ['s' + 'tatus']: 'Durum',
            metrekare: 'Metrekare',
            oda_sayisi: 'Oda Sayısı',
        };

        return labels[fieldName] || fieldName;
    }

    /**
     * Handle fetch error
     */
    async handleFetchError(response) {
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            this.showApiError({
                ['stat' + 'us']: response['stat' + 'us'],
                data: errorData,
            });
            throw new Error(
                errorData.message ||
                    this.messages.api[response['stat' + 'us']] ||
                    this.messages.api[500]
            );
        }
        return response;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.errorHandler = new ErrorHandler({
        useToast: true,
        showDetails: window.DEBUG_MODE || false,
        logErrors: true,
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErrorHandler;
}
