// ilan-create-ai.js - AI content generation and analysis functionality

function generateContent() {
    const contentType = document.querySelector('[x-model="contentType"]')?.value || 'description';
    const contentTone = document.querySelector('[x-model="contentTone"]')?.value || 'professional';
    const contentLength = document.querySelector('[x-model="contentLength"]')?.value || 'medium';
    const customInstructions =
        document.querySelector('[x-model="customInstructions"]')?.value || '';
    const selectedAiProvider =
        document.querySelector('[x-model="selectedAiProvider"]')?.value || 'openai';

    // Collect form data for context
    const formData = collectFormDataForAI();

    if (!formData.baslik && contentType === 'title') {
        showNotification('Başlık üretimi için temel bilgiler gerekli', 'warning');
        return;
    }

    showLoading('AI içerik üretiyor...');

    const requestData = {
        type: contentType,
        tone: contentTone,
        length: contentLength,
        instructions: customInstructions,
        provider: selectedAiProvider,
        context: formData,
    };

    // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
    if (!window.APIConfig?.ai?.generate) {
        console.error(
            '❌ APIConfig.ai.generate tanımlı değil! api-config.js yüklü mü kontrol edin.'
        );
        showNotification('API config yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    fetch(window.APIConfig.ai.generate, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
        body: JSON.stringify(requestData),
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                displayGeneratedContent(data.content, contentType);
                updateAIHistory(data.content, contentType);
            } else {
                showNotification(data.message || 'İçerik üretilemedi', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('AI content generation error:', error);
            showNotification('İçerik üretilemedi', 'error');
        });
}

function collectFormDataForAI() {
    const form = document.getElementById('ilan-create-form');
    if (!form) return {};

    const formData = new FormData(form);
    const data = {};

    // Basic info
    data.baslik = formData.get('baslik') || '';
    data.aciklama = formData.get('aciklama') || '';

    // Category info
    data.ana_kategori = document.getElementById('ana_kategori')?.selectedOptions[0]?.text || '';
    data.alt_kategori = document.getElementById('alt_kategori')?.selectedOptions[0]?.text || '';
    data.yayin_tipi = document.getElementById('junction_id')?.selectedOptions[0]?.text || '';

    // Price info
    data.fiyat = formData.get('fiyat') || '';
    data.para_birimi = formData.get('para_birimi') || 'TRY';
    data.metrekare = formData.get('metrekare') || '';

    // Oda sayısı (Context7 uyumlu)
    const odaSayisiElement = document.getElementById('oda_sayisi');
    data.oda_sayisi =
        odaSayisiElement?.selectedOptions?.[0]?.text || formData.get('oda_sayisi') || '';

    // Location info
    data.il = document.getElementById('il_id')?.selectedOptions[0]?.text || '';
    data.ilce = document.getElementById('ilce_id')?.selectedOptions[0]?.text || '';
    data.mahalle = document.getElementById('mahalle_id')?.selectedOptions[0]?.text || '';
    data.cadde_sokak = formData.get('cadde_sokak') || '';

    // Features
    const features = {};
    formData.forEach((value, key) => {
        if (key.startsWith('features[')) {
            const match = key.match(/features\[(\w+)\]\[(\w+)\]/);
            if (match) {
                const category = match[1];
                const feature = match[2];
                if (!features[category]) features[category] = {};
                features[category][feature] = value;
            }
        }
    });
    data.features = features;

    return data;
}

function displayGeneratedContent(content, type) {
    const container = document.querySelector('[x-show="generatedContent"]');
    if (!container) return;

    // Update Alpine data
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.generatedContent = content;
    }

    // Show the container
    container.style.display = 'block';

    // Auto-fill appropriate field if user wants
    if (type === 'title' && !document.getElementById('baslik').value) {
        document.getElementById('baslik').value = content;
    } else if (type === 'description' && !document.getElementById('aciklama').value) {
        document.getElementById('aciklama').value = content;
    }
}

function runAnalysis() {
    const formData = collectFormDataForAI();

    showLoading('İlan analiz ediliyor...');

    // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
    if (!window.APIConfig?.ai?.analyze) {
        console.error(
            '❌ APIConfig.ai.analyze tanımlı değil! api-config.js yüklü mü kontrol edin.'
        );
        showNotification('API config yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    fetch(window.APIConfig.ai.analyze, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
        body: JSON.stringify({ context: formData }),
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                displayAnalysisResults(data.analysis);
            } else {
                showNotification(data.message || 'Analiz yapılamadı', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('AI analysis error:', error);
            showNotification('Analiz yapılamadı', 'error');
        });
}

function displayAnalysisResults(analysis) {
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.analysisResults = analysis;
        window.aiContentManagerInstance.isAnalyzing = false;
    }
}

function updateAIHistory(content, type) {
    const historyItem = {
        title: getContentTitle(content, type),
        content: content,
        type: type,
        date: new Date().toLocaleString('tr-TR'),
        timestamp: Date.now(),
    };

    // Add to history
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.contentHistory.unshift(historyItem);
        // Keep only last 10 items
        if (window.aiContentManagerInstance.contentHistory.length > 10) {
            window.aiContentManagerInstance.contentHistory.pop();
        }
    }

    // Save to localStorage
    saveAIHistoryToStorage(historyItem);
}

function getContentTitle(content, type) {
    const titles = {
        title: 'Başlık Önerisi',
        description: 'Açıklama Önerisi',
        features: 'Özellik Önerisi',
        seo: 'SEO Metni Önerisi',
    };

    return titles[type] || 'İçerik Önerisi';
}

function saveAIHistoryToStorage(item) {
    try {
        const history = JSON.parse(localStorage.getItem('aiContentHistory') || '[]');
        history.unshift(item);
        // Keep only last 20 items
        if (history.length > 20) {
            history.splice(20);
        }
        localStorage.setItem('aiContentHistory', JSON.stringify(history));
    } catch (error) {
        console.error('AI history save error:', error);
    }
}

function loadAIHistoryFromStorage() {
    try {
        return JSON.parse(localStorage.getItem('aiContentHistory') || '[]');
    } catch (error) {
        console.error('AI history load error:', error);
        return [];
    }
}

function copyContent() {
    const contentElement = document.querySelector('[x-html="generatedContent"]');
    if (!contentElement) return;

    const content = contentElement.textContent || contentElement.innerText;

    navigator.clipboard
        .writeText(content)
        .then(() => {
            showNotification('İçerik panoya kopyalandı', 'success');
        })
        .catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = content;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('İçerik panoya kopyalandı', 'success');
        });
}

function applyContent() {
    const contentElement = document.querySelector('[x-html="generatedContent"]');
    if (!contentElement) return;

    const content = contentElement.textContent || contentElement.innerText;
    const contentType = document.querySelector('[x-model="contentType"]')?.value || 'description';

    let targetField;
    switch (contentType) {
        case 'title':
            targetField = document.getElementById('baslik');
            break;
        case 'description':
            targetField = document.getElementById('aciklama');
            break;
        default:
            showNotification('Bu içerik türü için otomatik uygulama mevcut değil', 'warning');
            return;
    }

    if (targetField) {
        targetField.value = content;
        showNotification('İçerik uygulandı', 'success');

        // Trigger change event for validation
        targetField.dispatchEvent(new Event('input'));
    }
}

function refreshAISuggestions() {
    // Clear current suggestions
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.aiSuggestions = [];
    }

    // Generate new suggestions
    generateAISuggestions();
}

function generateAISuggestions() {
    const formData = collectFormDataForAI();

    // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
    if (!window.APIConfig?.ai?.suggest) {
        console.error(
            '❌ APIConfig.ai.suggest tanımlı değil! api-config.js yüklü mü kontrol edin.'
        );
        showNotification('API config yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    fetch(window.APIConfig.ai.suggest, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
        body: JSON.stringify({ context: formData }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && window.aiContentManagerInstance) {
                window.aiContentManagerInstance.aiSuggestions = data.suggestions;
            }
        })
        .catch((error) => {
            console.error('AI suggestions error:', error);
        });
}

function applySuggestion(suggestion) {
    if (!suggestion || !suggestion.value) return;

    // Apply the suggested price
    const priceInput = document.querySelector('input[name="fiyat"]');
    if (priceInput) {
        priceInput.value = suggestion.value;
        priceInput.dispatchEvent(new Event('input'));
        showNotification('Fiyat önerisi uygulandı', 'success');
    }
}

function reuseContent(item) {
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.generatedContent = item.content;
    }
}

function favoriteContent(item) {
    if (window.aiContentManagerInstance) {
        const favorite = { ...item, favoritedAt: new Date().toISOString() };
        window.aiContentManagerInstance.favoriteContents.unshift(favorite);

        // Keep only last 10 favorites
        if (window.aiContentManagerInstance.favoriteContents.length > 10) {
            window.aiContentManagerInstance.favoriteContents.pop();
        }
    }
}

function applyFavorite(item) {
    applyContentFromItem(item);
}

function removeFavorite(item) {
    if (window.aiContentManagerInstance) {
        const index = window.aiContentManagerInstance.favoriteContents.findIndex(
            (fav) => fav.timestamp === item.timestamp
        );
        if (index > -1) {
            window.aiContentManagerInstance.favoriteContents.splice(index, 1);
        }
    }
}

function applyContentFromItem(item) {
    if (window.aiContentManagerInstance) {
        window.aiContentManagerInstance.generatedContent = item.content;
    }

    // Apply to appropriate field based on type
    let targetField;
    switch (item.type) {
        case 'title':
            targetField = document.getElementById('baslik');
            break;
        case 'description':
            targetField = document.getElementById('aciklama');
            break;
    }

    if (targetField) {
        targetField.value = item.content;
        targetField.dispatchEvent(new Event('input'));
        showNotification('Favori içerik uygulandı', 'success');
    }
}

// Alpine.js data function for AI content manager
window.aiContentManager = function () {
    return {
        selectedAiProvider: 'openai',
        providers: [
            { value: 'openai', name: 'OpenAI GPT-4', available: false },
            { value: 'anthropic', name: 'Anthropic Claude', available: false },
            { value: 'google', name: 'Google Gemini', available: false },
            { value: 'local', name: 'Yerel AI', available: false },
        ],
        aiHealthChecked: false,
        availableProviders: 0,
        totalProviders: 4,
        contentType: 'description',
        contentTone: 'professional',
        contentLength: 'medium',
        customInstructions: '',
        generatedContent: '',
        analysisResults: [],
        isAnalyzing: false,
        aiSuggestions: [],
        contentHistory: loadAIHistoryFromStorage(),
        favoriteContents: [],

        init() {
            // Load favorites from localStorage
            this.loadFavorites();

            // Generate initial suggestions if we have enough data
            this.$nextTick(() => {
                setTimeout(() => this.generateAISuggestions(), 2000);
            });
        },

        // Check AI provider health (Context7 v3.5.0)
        async checkAIHealth() {
            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('ai.health');

                if (result.success && result.data) {
                    this.aiHealthChecked = true;
                    this.availableProviders = result.data.available_count;
                    this.totalProviders = result.data.total_count;

                    // Update providers availability
                    Object.entries(result.data.providers).forEach(([key, provider]) => {
                        const providerObj = this.providers.find((p) => p.value === key);
                        if (providerObj) {
                            providerObj.available = provider.available;
                            providerObj.reason = provider.reason;
                        }
                    });

                    // Auto-select first available provider
                    const firstAvailable = this.providers.find((p) => p.available);
                    if (firstAvailable) {
                        this.selectedAiProvider = firstAvailable.value;
                    }

                    console.log(
                        '✅ AI Health Check:',
                        `${data.available_count}/${data.total_count} providers available`
                    );
                }
            } catch (error) {
                console.error('❌ AI Health Check failed:', error);
                this.aiHealthChecked = false;
            }
        },

        generateContent() {
            generateContent();
        },

        runAnalysis() {
            this.isAnalyzing = true;
            runAnalysis();
        },

        refreshAISuggestions() {
            refreshAISuggestions();
        },

        copyContent() {
            copyContent();
        },

        applyContent() {
            applyContent();
        },

        applySuggestion(suggestion) {
            applySuggestion(suggestion);
        },

        reuseContent(item) {
            reuseContent(item);
        },

        favoriteContent(item) {
            favoriteContent(item);
        },

        applyFavorite(item) {
            applyFavorite(item);
        },

        removeFavorite(item) {
            removeFavorite(item);
        },

        loadFavorites() {
            try {
                const favorites = JSON.parse(localStorage.getItem('aiFavoriteContents') || '[]');
                this.favoriteContents = favorites;
            } catch (error) {
                console.error('Favorites load error:', error);
            }
        },

        saveFavorites() {
            try {
                localStorage.setItem('aiFavoriteContents', JSON.stringify(this.favoriteContents));
            } catch (error) {
                console.error('Favorites save error:', error);
            }
        },

        generateAISuggestions() {
            generateAISuggestions();
        },
    };
};

// Initialize AI functionality
// Initialize AI functionality
function initializeAI() {
    console.log('AI module initialized');

    // Bind Price Suggestion Button
    const priceBtn = document.getElementById('ai-price-suggestion');
    if (priceBtn) {
        priceBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Dispatch event to price manager
            document.dispatchEvent(new CustomEvent('trigger-ai-price'));
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Store Alpine instance reference
    document.addEventListener('alpine:init', () => {
        window.aiContentManagerInstance = Alpine.store('aiContentManager');
    });
});

// Export functions for use in other modules
window.IlanCreateAI = {
    initializeAI,
    generateContent,
    runAnalysis,
    copyContent,
    applyContent,
    aiContentManager: window.aiContentManager,
};
