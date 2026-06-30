// AI Components Index
// Bu dosya tüm AI komponentlerini export eder

import AIChatWidget from './AIChatWidget.vue';
import AIPricePrediction from './AIPricePrediction.vue';
import AIDashboard from './AIDashboard.vue';

// Komponentleri export et
export { AIChatWidget, AIPricePrediction, AIDashboard };

// Vue.js global registration için
export default {
    install(app) {
        app.component('AIChatWidget', AIChatWidget);
        app.component('AIPricePrediction', AIPricePrediction);
        app.component('AIDashboard', AIDashboard);
    },
};

// Komponent bilgileri
export const AI_COMPONENTS = {
    AIChatWidget: {
        name: 'AIChatWidget',
        description: "AI destekli sohbet widget'ı",
        props: {
            autoOpen: { type: Boolean, default: false },
            position: { type: String, default: 'bottom-right' },
        },
        events: {
            'message-sent': 'Mesaj gönderildiğinde tetiklenir',
            'chat-opened': 'Chat açıldığında tetiklenir',
            'chat-closed': 'Chat kapatıldığında tetiklenir',
        },
    },

    AIPricePrediction: {
        name: 'AIPricePrediction',
        description: 'AI destekli fiyat tahmini komponenti',
        props: {
            initialData: { type: Object, default: null },
            showAdvancedOptions: { type: Boolean, default: true },
        },
        events: {
            'prediction-made': 'Fiyat tahmini yapıldığında tetiklenir',
            'form-reset': 'Form sıfırlandığında tetiklenir',
        },
    },

    AIDashboard: {
        name: 'AIDashboard',
        description: 'AI araçları dashboard komponenti',
        props: {
            showStats: { type: Boolean, default: true },
            compactMode: { type: Boolean, default: false },
        },
        events: {
            'open-chat-widget': 'Chat widget açma isteği',
            'open-description-generator': 'Açıklama üretici açma isteği',
            'open-request-analysis': 'Talep analizi açma isteği',
        },
    },
};
