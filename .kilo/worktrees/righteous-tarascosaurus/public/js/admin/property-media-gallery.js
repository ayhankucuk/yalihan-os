/**
 * 🏠 PROPERTY MEDIA GALLERY SYSTEM - NEO DESIGN
 * Modern medya galerisi - 360° viewer, video player, virtual tour
 * Tarih: 19 Ekim 2025
 * Context7 Compliant & Neo Design System
 */

class PropertyMediaGallery {
    constructor(container) {
        this.container =
            typeof container === 'string' ? document.querySelector(container) : container;

        if (!this.container) {
            console.warn('[PropertyMediaGallery] Container not found');
            return;
        }

        this.state = {
            activeTab: 'photos',
            currentImageIndex: 0,
            currentVideoIndex: 0,
            isLightboxOpen: false,
            isFullscreen: false,
            images: [],
            videos: [],
            virtualTours: [],
            floorPlans: [],
            documents: [],
        };

        this.elements = {};
        this.init();
    }

    init() {
        this.render();
        this.bindEvents();
        this.loadSampleData();
        console.log('[PropertyMediaGallery] Initialized successfully');
    }

    render() {
        this.container.innerHTML = `
            <div class="neo-card neo-fade-in">
                <!-- Header with Tabs -->
                <div class="neo-card-header border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="neo-icon-container bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Medya Galerisi</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fotoğraflar, videolar ve sanal turlar</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <button class="neo-btn neo-neo-btn neo-btn-secondary neo-btn-sm" data-action="addMedia">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Medya Ekle
                            </button>
                            <button class="neo-btn neo-btn-accent neo-btn-sm" data-action="fullscreen">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                Tam Ekran
                            </button>
                        </div>
                    </div>

                    <!-- Media Tabs -->
                    <div class="neo-tabs-container">
                        <div class="neo-tabs">
                            <button class="neo-tab neo-tab-active" data-tab="photos">
                                <span class="neo-tab-icon">📸</span>
                                <span class="neo-tab-text">Fotoğraflar</span>
                                <span class="neo-tab-badge" data-count="photos">0</span>
                            </button>
                            <button class="neo-tab" data-tab="videos">
                                <span class="neo-tab-icon">🎥</span>
                                <span class="neo-tab-text">Videolar</span>
                                <span class="neo-tab-badge" data-count="videos">0</span>
                            </button>
                            <button class="neo-tab" data-tab="tours">
                                <span class="neo-tab-icon">🌐</span>
                                <span class="neo-tab-text">Sanal Tur</span>
                                <span class="neo-tab-badge" data-count="tours">0</span>
                            </button>
                            <button class="neo-tab" data-tab="plans">
                                <span class="neo-tab-icon">📐</span>
                                <span class="neo-tab-text">Kat Planları</span>
                                <span class="neo-tab-badge" data-count="plans">0</span>
                            </button>
                            <button class="neo-tab" data-tab="docs">
                                <span class="neo-tab-icon">📄</span>
                                <span class="neo-tab-text">Belgeler</span>
                                <span class="neo-tab-badge" data-count="docs">0</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="neo-card-content">
                    <!-- Photos Tab -->
                    <div class="neo-tab-content neo-tab-active" data-content="photos">
                        <div class="photo-gallery-container">
                            <!-- Main Photo Display -->
                            <div class="main-photo-container neo-glass-effect mb-6 relative group">
                                <div class="main-photo-wrapper aspect-video bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden">
                                    <img class="main-photo w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         src="" alt="Ana Fotoğraf" style="display: none;">
                                    <div class="photo-placeholder flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400">Fotoğraf yükleyin veya sürükleyin</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Photo Navigation -->
                                <div class="photo-nav-overlay absolute inset-0 flex items-center justify-between p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="nav-btn nav-prev neo-btn neo-btn-accent p-3 rounded-full" data-action="prevPhoto">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button class="nav-btn nav-next neo-btn neo-btn-accent p-3 rounded-full" data-action="nextPhoto">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Photo Info Overlay -->
                                <div class="photo-info-overlay absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent p-6 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div class="text-white">
                                        <p class="photo-title text-lg font-semibold mb-1"></p>
                                        <p class="photo-description text-sm opacity-90"></p>
                                        <div class="photo-meta flex items-center space-x-4 mt-2 text-xs opacity-75">
                                            <span class="photo-date"></span>
                                            <span class="photo-size"></span>
                                            <span class="photo-resolution"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo Thumbnails -->
                            <div class="photo-thumbnails-container">
                                <div class="photo-thumbnails flex space-x-3 overflow-x-auto pb-4">
                                    <!-- Thumbnails will be populated here -->
                                </div>
                            </div>

                            <!-- Photo Upload Area -->
                            <div class="photo-upload-area neo-upload-zone mt-6">
                                <div class="upload-zone-content">
                                    <svg class="w-12 h-12 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Fotoğraf Yükle</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Dosyaları sürükle bırak veya seç (JPG, PNG, WebP - Max 10MB)
                                    </p>
                                    <button class="neo-btn neo-neo-btn neo-btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Fotoğraf Seç
                                    </button>
                                </div>
                                <input type="file" class="upload-input" multiple accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- Videos Tab -->
                    <div class="neo-tab-content" data-content="videos">
                        <div class="video-gallery-container">
                            <!-- Video Player -->
                            <div class="video-player-container neo-glass-effect mb-6">
                                <div class="video-wrapper aspect-video bg-gray-900 rounded-xl overflow-hidden relative">
                                    <video class="main-video w-full h-full" controls style="display: none;">
                                        <source src="" type="video/mp4">
                                        Tarayıcınız video oynatmayı desteklemiyor.
                                    </video>
                                    <div class="video-placeholder flex items-center justify-center h-full">
                                        <div class="text-center text-white">
                                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            <p class="text-gray-300">Video yükleyin veya YouTube linki ekleyin</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Video List -->
                            <div class="video-list-container">
                                <div class="video-list grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                    <!-- Video items will be populated here -->
                                </div>
                            </div>

                            <!-- Video Upload Area -->
                            <div class="video-upload-area neo-upload-zone">
                                <div class="upload-zone-content">
                                    <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Video Ekle</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Video dosyası yükle veya YouTube/Vimeo linki ekle (MP4, WebM - Max 100MB)
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                        <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="uploadVideo">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            Video Yükle
                                        </button>
                                        <button class="neo-btn neo-neo-btn neo-btn-secondary" data-action="addVideoLink">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            Link Ekle
                                        </button>
                                    </div>
                                </div>
                                <input type="file" class="video-upload-input" accept="video/*" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- Virtual Tours Tab -->
                    <div class="neo-tab-content" data-content="tours">
                        <div class="virtual-tour-container">
                            <!-- 360° Viewer -->
                            <div class="virtual-tour-viewer neo-glass-effect mb-6">
                                <div class="tour-wrapper aspect-video bg-gray-900 rounded-xl overflow-hidden relative">
                                    <div class="tour-iframe-container w-full h-full">
                                        <iframe class="tour-iframe w-full h-full" src="" frameborder="0" allowfullscreen style="display: none;"></iframe>
                                        <div class="tour-placeholder flex items-center justify-center h-full">
                                            <div class="text-center text-white">
                                                <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                                                </svg>
                                                <p class="text-gray-300">360° sanal tur ekleyin (Matterport, Kuula, vb.)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tour List -->
                            <div class="tour-list-container mb-6">
                                <div class="tour-list grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Tour items will be populated here -->
                                </div>
                            </div>

                            <!-- Add Virtual Tour -->
                            <div class="add-tour-area neo-upload-zone">
                                <div class="upload-zone-content">
                                    <svg class="w-12 h-12 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Sanal Tur Ekle</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Matterport, Kuula, Google Street View veya 360° fotoğraf linklerini ekleyin
                                    </p>
                                    <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="addVirtualTour">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Sanal Tur Ekle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floor Plans Tab -->
                    <div class="neo-tab-content" data-content="plans">
                        <div class="floor-plans-container">
                            <!-- Interactive Floor Plan Viewer -->
                            <div class="floor-plan-viewer neo-glass-effect mb-6">
                                <div class="plan-wrapper bg-white dark:bg-gray-800 rounded-xl p-6">
                                    <div class="plan-display aspect-square bg-gray-50 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                                        <div class="text-center">
                                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400">Kat planı yükleyin</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Floor Plan List -->
                            <div class="floor-plan-list-container mb-6">
                                <div class="floor-plan-list grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <!-- Floor plan items will be populated here -->
                                </div>
                            </div>

                            <!-- Upload Floor Plans -->
                            <div class="floor-plan-upload-area neo-upload-zone">
                                <div class="upload-zone-content">
                                    <svg class="w-12 h-12 text-purple-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Kat Planı Yükle</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        PDF, PNG, JPG formatlarında kat planlarını yükleyin (Max 20MB)
                                    </p>
                                    <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="uploadFloorPlan">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        Plan Yükle
                                    </button>
                                </div>
                                <input type="file" class="floor-plan-upload-input" multiple accept="image/*,.pdf" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div class="neo-tab-content" data-content="docs">
                        <div class="documents-container">
                            <!-- Document List -->
                            <div class="document-list-container mb-6">
                                <div class="document-list space-y-3">
                                    <!-- Document items will be populated here -->
                                </div>
                            </div>

                            <!-- Upload Documents -->
                            <div class="document-upload-area neo-upload-zone">
                                <div class="upload-zone-content">
                                    <svg class="w-12 h-12 text-orange-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Belge Yükle</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Tapu, ruhsat, enerji kimlik belgesi vb. (PDF, DOC, XLS - Max 50MB)
                                    </p>
                                    <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="uploadDocument">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        Belge Yükle
                                    </button>
                                </div>
                                <input type="file" class="document-upload-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox Modal -->
            <div class="lightbox-modal fixed inset-0 bg-black/90 z-50 hidden">
                <div class="lightbox-container h-full flex items-center justify-center p-4">
                    <div class="lightbox-content max-w-6xl max-h-full">
                        <img class="lightbox-image max-w-full max-h-full object-contain" src="" alt="">
                        <div class="lightbox-controls absolute top-4 right-4 flex space-x-2">
                            <button class="lightbox-close neo-btn neo-neo-btn neo-btn-secondary p-2 rounded-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.cacheElements();
    }

    cacheElements() {
        // Tabs
        this.elements.tabs = this.container.querySelectorAll('[data-tab]');
        this.elements.tabContents = this.container.querySelectorAll('[data-content]');

        // Photos
        this.elements.mainPhoto = this.container.querySelector('.main-photo');
        this.elements.photoPlaceholder = this.container.querySelector('.photo-placeholder');
        this.elements.photoThumbnails = this.container.querySelector('.photo-thumbnails');
        this.elements.photoUploadInput = this.container.querySelector('.upload-input');

        // Videos
        this.elements.mainVideo = this.container.querySelector('.main-video');
        this.elements.videoPlaceholder = this.container.querySelector('.video-placeholder');
        this.elements.videoList = this.container.querySelector('.video-list');
        this.elements.videoUploadInput = this.container.querySelector('.video-upload-input');

        // Tours
        this.elements.tourIframe = this.container.querySelector('.tour-iframe');
        this.elements.tourPlaceholder = this.container.querySelector('.tour-placeholder');
        this.elements.tourList = this.container.querySelector('.tour-list');

        // Lightbox
        this.elements.lightboxModal = this.container.querySelector('.lightbox-modal');
        this.elements.lightboxImage = this.container.querySelector('.lightbox-image');
        this.elements.lightboxClose = this.container.querySelector('.lightbox-close');
    }

    bindEvents() {
        // Tab switching
        this.elements.tabs.forEach((tab) => {
            tab.addEventListener('click', (e) => {
                const tabName = e.currentTarget.dataset.tab;
                this.switchTab(tabName);
            });
        });

        // Button actions
        this.container.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            if (action) {
                this.handleAction(action, e);
            }
        });

        // File uploads
        if (this.elements.photoUploadInput) {
            this.elements.photoUploadInput.addEventListener('change', (e) => {
                this.handlePhotoUpload(e.target.files);
            });
        }

        // Drag and drop
        this.setupDragAndDrop();

        // Lightbox close
        if (this.elements.lightboxClose) {
            this.elements.lightboxClose.addEventListener('click', () => {
                this.closeLightbox();
            });
        }

        // ESC key to close lightbox
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.isLightboxOpen) {
                this.closeLightbox();
            }
        });
    }

    switchTab(tabName) {
        // Update active tab
        this.elements.tabs.forEach((tab) => {
            tab.classList.toggle('neo-tab-active', tab.dataset.tab === tabName);
        });

        // Update active content
        this.elements.tabContents.forEach((content) => {
            content.classList.toggle('neo-tab-active', content.dataset.content === tabName);
        });

        this.state.activeTab = tabName;

        // Show fade in animation
        const activeContent = this.container.querySelector(`[data-content="${tabName}"]`);
        if (activeContent) {
            activeContent.style.opacity = '0';
            setTimeout(() => {
                activeContent.style.opacity = '1';
            }, 50);
        }
    }

    handleAction(action, event) {
        switch (action) {
            case 'addMedia':
                this.showAddMediaModal();
                break;
            case 'fullscreen':
                this.toggleFullscreen();
                break;
            case 'prevPhoto':
                this.navigatePhoto(-1);
                break;
            case 'nextPhoto':
                this.navigatePhoto(1);
                break;
            case 'uploadVideo':
                this.elements.videoUploadInput?.click();
                break;
            case 'addVideoLink':
                this.showVideoLinkModal();
                break;
            case 'addVirtualTour':
                this.showVirtualTourModal();
                break;
            case 'uploadFloorPlan':
                this.container.querySelector('.floor-plan-upload-input')?.click();
                break;
            case 'uploadDocument':
                this.container.querySelector('.document-upload-input')?.click();
                break;
        }
    }

    setupDragAndDrop() {
        const uploadAreas = this.container.querySelectorAll('.neo-upload-zone');

        uploadAreas.forEach((area) => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
                area.addEventListener(eventName, this.preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                area.addEventListener(eventName, () => area.classList.add('neo-drag-over'), false);
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                area.addEventListener(
                    eventName,
                    () => area.classList.remove('neo-drag-over'),
                    false
                );
            });

            area.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (area.classList.contains('photo-upload-area')) {
                    this.handlePhotoUpload(files);
                } else if (area.classList.contains('video-upload-area')) {
                    this.handleVideoUpload(files);
                }
            });
        });
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    handlePhotoUpload(files) {
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const imageData = {
                        id: Date.now() + index,
                        src: e.target.result,
                        name: file.name,
                        size: this.formatFileSize(file.size),
                        type: file.type,
                        date: new Date().toLocaleDateString('tr-TR'),
                    };

                    this.state.images.push(imageData);
                    this.renderPhotoThumbnails();

                    // Show first image if none selected
                    if (this.state.images.length === 1) {
                        this.showPhoto(0);
                    }

                    this.updateTabCounts();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    renderPhotoThumbnails() {
        if (!this.elements.photoThumbnails) return;

        const html = this.state.images
            .map(
                (image, index) => `
            <div class="photo-thumbnail neo-glass-effect cursor-pointer ${
                index === this.state.currentImageIndex ? 'ring-2 ring-blue-500' : ''
            }"
                 data-index="${index}" onclick="window.propertyMediaGallery.showPhoto(${index})">
                <img src="${image.src}" alt="${
                    image.name
                }" class="w-16 h-16 object-cover rounded-lg">
            </div>
        `
            )
            .join('');

        this.elements.photoThumbnails.innerHTML = html;
    }

    showPhoto(index) {
        if (index < 0 || index >= this.state.images.length) return;

        const image = this.state.images[index];
        this.state.currentImageIndex = index;

        if (this.elements.mainPhoto && this.elements.photoPlaceholder) {
            this.elements.mainPhoto.src = image.src;
            this.elements.mainPhoto.alt = image.name;
            this.elements.mainPhoto.style.display = 'block';
            this.elements.photoPlaceholder.style.display = 'none';

            // Update photo info
            const titleEl = this.container.querySelector('.photo-title');
            const descEl = this.container.querySelector('.photo-description');
            const dateEl = this.container.querySelector('.photo-date');
            const sizeEl = this.container.querySelector('.photo-size');

            if (titleEl) titleEl.textContent = image.name;
            if (descEl) descEl.textContent = image.description || 'Emlak fotoğrafı';
            if (dateEl) dateEl.textContent = image.date;
            if (sizeEl) sizeEl.textContent = image.size;
        }

        this.renderPhotoThumbnails();

        // Add lightbox click handler
        if (this.elements.mainPhoto) {
            this.elements.mainPhoto.onclick = () => this.openLightbox(image.src);
        }
    }

    navigatePhoto(direction) {
        const newIndex = this.state.currentImageIndex + direction;
        if (newIndex >= 0 && newIndex < this.state.images.length) {
            this.showPhoto(newIndex);
        }
    }

    openLightbox(imageSrc) {
        if (this.elements.lightboxModal && this.elements.lightboxImage) {
            this.elements.lightboxImage.src = imageSrc;
            this.elements.lightboxModal.classList.remove('hidden');
            this.state.isLightboxOpen = true;
            document.body.style.overflow = 'hidden';
        }
    }

    closeLightbox() {
        if (this.elements.lightboxModal) {
            this.elements.lightboxModal.classList.add('hidden');
            this.state.isLightboxOpen = false;
            document.body.style.overflow = 'auto';
        }
    }

    updateTabCounts() {
        const counts = {
            photos: this.state.images.length,
            videos: this.state.videos.length,
            tours: this.state.virtualTours.length,
            plans: this.state.floorPlans.length,
            docs: this.state.documents.length,
        };

        Object.entries(counts).forEach(([key, count]) => {
            const badge = this.container.querySelector(`[data-count="${key}"]`);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    loadSampleData() {
        // Load some sample data for demonstration
        setTimeout(() => {
            // Sample images
            const sampleImages = [
                {
                    id: 1,
                    src: 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800',
                    name: 'Oturma Odası',
                    description: 'Geniş ve ferah oturma alanı',
                    size: '2.3 MB',
                    date: '19.10.2025',
                },
                {
                    id: 2,
                    src: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800',
                    name: 'Mutfak',
                    description: 'Modern mutfak tasarımı',
                    size: '1.8 MB',
                    date: '19.10.2025',
                },
            ];

            this.state.images = sampleImages;
            this.renderPhotoThumbnails();
            this.showPhoto(0);
            this.updateTabCounts();
        }, 1000);
    }

    showAddMediaModal() {
        // Simple implementation - could be enhanced with a proper modal
        alert('Medya ekleme modalı açılacak. Bu özellik geliştirme aşamasında.');
    }

    toggleFullscreen() {
        if (!this.state.isFullscreen) {
            if (this.container.requestFullscreen) {
                this.container.requestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
        this.state.isFullscreen = !this.state.isFullscreen;
    }

    showVideoLinkModal() {
        const url = prompt('YouTube veya Vimeo video linkini girin:');
        if (url) {
            // Process video URL and add to list
            console.log('Video URL added:', url);
        }
    }

    showVirtualTourModal() {
        const url = prompt('Sanal tur linkini girin (Matterport, Kuula, etc.):');
        if (url) {
            // Process tour URL and add to list
            console.log('Virtual tour URL added:', url);
        }
    }

    // Public API
    addImage(imageData) {
        this.state.images.push(imageData);
        this.renderPhotoThumbnails();
        this.updateTabCounts();
    }

    removeImage(imageId) {
        this.state.images = this.state.images.filter((img) => img.id !== imageId);
        this.renderPhotoThumbnails();
        this.updateTabCounts();
    }

    getMediaData() {
        return {
            images: this.state.images,
            videos: this.state.videos,
            virtualTours: this.state.virtualTours,
            floorPlans: this.state.floorPlans,
            documents: this.state.documents,
        };
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    const galleryContainer = document.getElementById('property-media-gallery');
    if (galleryContainer) {
        window.propertyMediaGallery = new PropertyMediaGallery(galleryContainer);
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PropertyMediaGallery;
}
