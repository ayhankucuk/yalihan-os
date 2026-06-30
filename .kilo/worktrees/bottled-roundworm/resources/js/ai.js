// AI Module JavaScript
import { createApp } from 'vue';
import { AIChatWidget, AIPricePrediction, AIDashboard } from './components/AI';

// AI Dashboard App
const aiDashboardApp = createApp({
    components: {
        AIDashboard,
    },
    methods: {
        openChatWidget() {
            if (window.aiChatApp && window.aiChatApp.$refs.chatWidget) {
                window.aiChatApp.$refs.chatWidget.toggleChat();
            }
        },
        openDescriptionGenerator() {
            const modal = new bootstrap.Modal(document.getElementById('descriptionModal'));
            modal.show();
        },
        openRequestAnalysis() {
            const modal = new bootstrap.Modal(document.getElementById('requestAnalysisModal'));
            modal.show();
        },
    },
});

// AI Chat App
const aiChatApp = createApp({
    components: {
        AIChatWidget,
    },
});

// AI Price Prediction App
const aiPricePredictionApp = createApp({
    components: {
        AIPricePrediction,
    },
    methods: {
        onPredictionMade(data) {
            console.log('Prediction made:', data);
            // Başarılı tahmin bildirimi
            this.showSuccessToast('Fiyat tahmini başarıyla tamamlandı!');
        },
        showSuccessToast(message) {
            const toast = document.createElement('div');
            toast.className =
                'toast align-items-center text-white bg-success border-0 position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 3000);
        },
    },
});

// AI Service Class
class AIService {
    constructor() {
        this.baseUrl = '/api/ai';
        this.token = document.querySelector('meta[name="api-token"]')?.content;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    async makeRequest(endpoint, data = null, method = 'GET') {
        const config = {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        };

        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (this.csrfToken) {
            config.headers['X-CSRF-TOKEN'] = this.csrfToken;
        }

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, config);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AI Service Error:', error);
            throw error;
        }
    }

    // Chat Methods
    async sendChatMessage(message, context = null) {
        return await this.makeRequest(
            '/chat',
            {
                message,
                context,
            },
            'POST'
        );
    }

    // Price Prediction Methods
    async predictPrice(propertyData) {
        return await this.makeRequest(
            '/predict-price',
            {
                property_data: propertyData,
            },
            'POST'
        );
    }

    // Description Generation Methods
    async generateDescription(propertyData, style = 'professional') {
        return await this.makeRequest(
            '/generate-description',
            {
                property_data: propertyData,
                style,
            },
            'POST'
        );
    }

    // Request Analysis Methods
    async analyzeRequest(rawText, limit = 10) {
        return await this.makeRequest(
            '/analyze-request',
            {
                raw_text: rawText,
                limit,
            },
            'POST'
        );
    }

    // Status Methods
    async getStatus() {
        return await this.makeRequest('/status');
    }

    async getStats() {
        return await this.makeRequest('/stats');
    }
}

// AI Utilities
class AIUtils {
    static formatPrice(price) {
        if (!price) return 'Belirtilmemiş';
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(price);
    }

    static formatDate(date) {
        return new Intl.DateTimeFormat('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(date));
    }

    static copyToClipboard(text) {
        return navigator.clipboard
            .writeText(text)
            .then(() => {
                this.showToast('Panoya kopyalandı!', 'success');
            })
            .catch((err) => {
                console.error('Kopyalama hatası:', err);
                this.showToast('Kopyalama başarısız oldu.', 'error');
            });
    }

    static showToast(message, type = 'info') {
        const bgClass =
            {
                success: 'bg-success',
                error: 'bg-danger',
                warning: 'bg-warning',
                info: 'bg-info',
            }[type] || 'bg-info';

        const icon =
            {
                success: 'fas fa-check',
                error: 'fas fa-times',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle',
            }[type] || 'fas fa-info-circle';

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${bgClass} border-0 position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="${icon} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 3000);
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    }

    static validatePropertyData(data) {
        const errors = [];

        if (!data.il) errors.push('İl seçimi zorunludur');
        if (!data.ilce) errors.push('İlçe seçimi zorunludur');
        if (!data.tur) errors.push('Emlak türü seçimi zorunludur');
        if (!data.kategori) errors.push('Kategori seçimi zorunludur');
        if (!data.metrekare || data.metrekare <= 0)
            errors.push('Geçerli bir metrekare değeri giriniz');

        return {
            isValid: errors.length === 0,
            errors,
        };
    }

    static extractContactInfo(text) {
        const phoneRegex = /(?:\+90|0)?\s*[5][0-9]{2}\s*[0-9]{3}\s*[0-9]{2}\s*[0-9]{2}/g;
        const emailRegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

        const phones = text.match(phoneRegex) || [];
        const emails = text.match(emailRegex) || [];

        return {
            phones: [...new Set(phones)],
            emails: [...new Set(emails)],
        };
    }

    static highlightKeywords(text, keywords) {
        if (!keywords || keywords.length === 0) return text;

        const regex = new RegExp(`(${keywords.join('|')})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
}

// AI Event Manager
class AIEventManager {
    constructor() {
        this.events = {};
    }

    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    }

    off(event, callback) {
        if (!this.events[event]) return;

        const index = this.events[event].indexOf(callback);
        if (index > -1) {
            this.events[event].splice(index, 1);
        }
    }

    emit(event, data) {
        if (!this.events[event]) return;

        this.events[event].forEach((callback) => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in event callback for ${event}:`, error);
            }
        });
    }
}

// Initialize AI Module
document.addEventListener('DOMContentLoaded', () => {
    // Mount Vue apps if elements exist
    const aiDashboardElement = document.getElementById('aiDashboardApp');
    if (aiDashboardElement) {
        window.aiDashboardApp = aiDashboardApp.mount('#aiDashboardApp');
    }

    const aiChatElement = document.getElementById('aiChatApp');
    if (aiChatElement) {
        window.aiChatApp = aiChatApp.mount('#aiChatApp');
    }

    const aiPricePredictionElement = document.getElementById('pricePredictionApp');
    if (aiPricePredictionElement) {
        window.aiPricePredictionApp = aiPricePredictionApp.mount('#pricePredictionApp');
    }

    // Initialize global AI service
    window.aiService = new AIService();
    window.aiUtils = AIUtils;
    window.aiEventManager = new AIEventManager();

    // Global AI status check
    if (typeof checkAIStatus === 'function') {
        checkAIStatus();
    }
});

// Export for module usage
export { AIService, AIUtils, AIEventManager, aiDashboardApp, aiChatApp, aiPricePredictionApp };
