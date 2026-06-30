// ilan-create.js - Main entry point for ilan create functionality

// Import all modules
import './ilan-create/core.js';
import './ilan-create/categories.js';
import './ilan-create/location.js';
import './ilan-create/ai.js';
import './ilan-create/photos.js';
import './ilan-create/portals.js';
import './ilan-create/price.js';
import './ilan-create/fields.js';
import './ilan-create/crm.js';
import './ilan-create/publication.js';
import './ilan-create/key-manager.js';
import { FeaturesAI } from './ilan-create/features-ai.js'; // ← YENİ: AI-powered features

// Export FeaturesAI globally for easy access
window.FeaturesAI = FeaturesAI;

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
    console.log('İlan Create initialized');

    // Initialize all components
    initializeIlanCreate();
});

function initializeIlanCreate() {
    // Check if all required modules are loaded
    const requiredModules = [
        'IlanCreateCore',
        'IlanCreateCategories',
        'IlanCreateLocation',
        'IlanCreateAI',
        'IlanCreatePhotos',
    ];

    const missingModules = requiredModules.filter((module) => !window[module]);

    if (missingModules.length > 0) {
        console.error('Missing modules:', missingModules);
        showNotification('Bazı modüller yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    // Initialize form validation
    if (window.IlanCreateCore && window.IlanCreateCore.initializeValidation) {
        window.IlanCreateCore.initializeValidation();
    }

    // Initialize category system
    if (window.IlanCreateCategories && window.IlanCreateCategories.initializeCategories) {
        window.IlanCreateCategories.initializeCategories();
    }

    // Initialize location/map system
    // Context7: Location system initialized by location-map.blade.php Alpine.js component
    // No need to call initializeLocation() here
    console.log('📍 Location system: Using Alpine.js locationManager() from blade component');

    // Initialize AI system
    if (window.IlanCreateAI && window.IlanCreateAI.initializeAI) {
        window.IlanCreateAI.initializeAI();
    }

    // Initialize photo system
    if (window.IlanCreatePhotos && window.IlanCreatePhotos.initializePhotos) {
        window.IlanCreatePhotos.initializePhotos();
    }

    console.log('All İlan Create modules initialized successfully');
}

// Utility function for notifications (fallback)
function showNotification(message, type = 'info') {
    if (window.IlanCreateCore && window.IlanCreateCore.showNotification) {
        window.IlanCreateCore.showNotification(message, type);
    } else {
        // Fallback notification
        alert(message);
    }
}

// Export main initialization function
window.initializeIlanCreate = initializeIlanCreate;
