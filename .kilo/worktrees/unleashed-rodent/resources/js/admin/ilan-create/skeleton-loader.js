// Yalıhan Bekçi - Skeleton Loading System
// Advanced skeleton loading with Context7 design system

class SkeletonLoader {
    constructor() {
        this.skeletonTemplates = new Map();
        this.activeSkeletons = new Set();
        this.init();
    }

    init() {
        this.createSkeletonTemplates();
        this.setupIntersectionObserver();
        this.injectSkeletonCSS();
    }

    // 💫 Skeleton screens (Loading states)
    createSkeletonTemplates() {
        // Form skeleton
        this.skeletonTemplates.set(
            'form',
            `
            <div class="skeleton-form">
                <div class="skeleton-field-group mb-6">
                    <div class="skeleton-label"></div>
                    <div class="skeleton-input"></div>
                </div>
                <div class="skeleton-field-group mb-6">
                    <div class="skeleton-label"></div>
                    <div class="skeleton-select"></div>
                </div>
                <div class="skeleton-field-group mb-6">
                    <div class="skeleton-label"></div>
                    <div class="skeleton-textarea"></div>
                </div>
                <div class="skeleton-buttons flex space-x-4">
                    <div class="skeleton-button"></div>
                    <div class="skeleton-button"></div>
                </div>
            </div>
        `
        );

        // Table skeleton
        this.skeletonTemplates.set(
            'table',
            `
            <div class="skeleton-table">
                <div class="skeleton-table-header">
                    <div class="skeleton-table-row">
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                    </div>
                </div>
                <div class="skeleton-table-body">
                    <div class="skeleton-table-row" x-repeat="5">
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                        <div class="skeleton-cell"></div>
                    </div>
                </div>
            </div>
        `
        );

        // Card skeleton
        this.skeletonTemplates.set(
            'card',
            `
            <div class="skeleton-card">
                <div class="skeleton-image"></div>
                <div class="skeleton-content p-4">
                    <div class="skeleton-title"></div>
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text"></div>
                    <div class="skeleton-meta flex space-x-4 mt-4">
                        <div class="skeleton-badge"></div>
                        <div class="skeleton-badge"></div>
                    </div>
                </div>
            </div>
        `
        );

        // List skeleton
        this.skeletonTemplates.set(
            'list',
            `
            <div class="skeleton-list">
                <div class="skeleton-list-item" x-repeat="6">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title"></div>
                        <div class="skeleton-subtitle"></div>
                    </div>
                    <div class="skeleton-action"></div>
                </div>
            </div>
        `
        );

        // AI Content skeleton
        this.skeletonTemplates.set(
            'ai-content',
            `
            <div class="skeleton-ai-content">
                <div class="skeleton-ai-header flex items-center justify-between mb-4">
                    <div class="skeleton-title"></div>
                    <div class="skeleton-status"></div>
                </div>
                <div class="skeleton-ai-actions grid grid-cols-2 gap-4">
                    <div class="skeleton-button"></div>
                    <div class="skeleton-button"></div>
                    <div class="skeleton-button"></div>
                    <div class="skeleton-button"></div>
                </div>
                <div class="skeleton-ai-results mt-4">
                    <div class="skeleton-content-block"></div>
                </div>
            </div>
        `
        );

        // Map skeleton
        this.skeletonTemplates.set(
            'map',
            `
            <div class="skeleton-map">
                <div class="skeleton-map-container"></div>
                <div class="skeleton-map-controls mt-4">
                    <div class="skeleton-button"></div>
                </div>
            </div>
        `
        );

        // Progress skeleton
        this.skeletonTemplates.set(
            'progress',
            `
            <div class="skeleton-progress">
                <div class="skeleton-progress-bar">
                    <div class="skeleton-progress-fill"></div>
                </div>
                <div class="skeleton-progress-steps flex justify-between mt-2">
                    <div class="skeleton-step"></div>
                    <div class="skeleton-step"></div>
                    <div class="skeleton-step"></div>
                    <div class="skeleton-step"></div>
                </div>
            </div>
        `
        );
    }

    injectSkeletonCSS() {
        const skeletonCSS = `
            /* Skeleton Loading Styles */
            .skeleton-loader {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: skeleton-loading 1.5s infinite;
                border-radius: 4px;
            }

            @keyframes skeleton-loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }

            /* Dark mode support */
            .dark .skeleton-loader {
                background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
                background-size: 200% 100%;
            }

            /* Form skeleton styles */
            .skeleton-form .skeleton-field-group {
                margin-bottom: 1.5rem;
            }

            .skeleton-label {
                width: 120px;
                height: 16px;
                margin-bottom: 8px;
                border-radius: 4px;
            }

            .skeleton-input {
                width: 100%;
                height: 40px;
                border-radius: 6px;
            }

            .skeleton-select {
                width: 100%;
                height: 40px;
                border-radius: 6px;
            }

            .skeleton-textarea {
                width: 100%;
                height: 120px;
                border-radius: 6px;
            }

            .skeleton-button {
                width: 100px;
                height: 36px;
                border-radius: 6px;
            }

            /* Table skeleton styles */
            .skeleton-table {
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
            }

            .skeleton-table-row {
                display: flex;
                padding: 12px;
                border-bottom: 1px solid #f3f4f6;
            }

            .skeleton-table-row:last-child {
                border-bottom: none;
            }

            .skeleton-cell {
                flex: 1;
                height: 20px;
                margin: 0 8px;
                border-radius: 4px;
            }

            .skeleton-table-header .skeleton-cell {
                height: 16px;
                opacity: 0.8;
            }

            /* Card skeleton styles */
            .skeleton-card {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
                background: white;
            }

            .skeleton-image {
                width: 100%;
                height: 200px;
                border-radius: 12px 12px 0 0;
            }

            .skeleton-title {
                width: 70%;
                height: 24px;
                margin-bottom: 12px;
                border-radius: 4px;
            }

            .skeleton-text {
                width: 100%;
                height: 16px;
                margin-bottom: 8px;
                border-radius: 4px;
            }

            .skeleton-text:last-child {
                width: 60%;
            }

            .skeleton-badge {
                width: 60px;
                height: 24px;
                border-radius: 12px;
            }

            /* List skeleton styles */
            .skeleton-list-item {
                display: flex;
                align-items: center;
                padding: 16px;
                border-bottom: 1px solid #f3f4f6;
            }

            .skeleton-list-item:last-child {
                border-bottom: none;
            }

            .skeleton-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                margin-right: 12px;
            }

            .skeleton-content {
                flex: 1;
            }

            .skeleton-subtitle {
                width: 60%;
                height: 14px;
                margin-top: 8px;
                border-radius: 4px;
            }

            .skeleton-action {
                width: 32px;
                height: 32px;
                border-radius: 6px;
            }

            /* AI Content skeleton */
            .skeleton-ai-content {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px;
            }

            .skeleton-ai-header .skeleton-title {
                width: 200px;
                height: 20px;
            }

            .skeleton-status {
                width: 80px;
                height: 20px;
                border-radius: 10px;
            }

            .skeleton-content-block {
                width: 100%;
                height: 100px;
                border-radius: 8px;
            }

            /* Map skeleton */
            .skeleton-map-container {
                width: 100%;
                height: 300px;
                border-radius: 8px;
                border: 1px solid #e5e7eb;
            }

            /* Progress skeleton */
            .skeleton-progress-bar {
                width: 100%;
                height: 8px;
                background: #e5e7eb;
                border-radius: 4px;
                overflow: hidden;
            }

            .skeleton-progress-fill {
                width: 60%;
                height: 100%;
                background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                animation: skeleton-loading 2s infinite;
            }

            .skeleton-step {
                width: 80px;
                height: 12px;
                border-radius: 6px;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .skeleton-table-row {
                    flex-direction: column;
                    gap: 8px;
                }

                .skeleton-cell {
                    margin: 0;
                    width: 100%;
                }

                .skeleton-ai-actions {
                    grid-template-columns: 1fr;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = skeletonCSS;
        document.head.appendChild(style);
    }

    // Show skeleton for specific element
    showSkeleton(element, type = 'form', duration = null) {
        if (!element) return;

        const skeletonId = `skeleton-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        element.dataset.skeletonId = skeletonId;
        this.activeSkeletons.add(skeletonId);

        // Store original content
        if (!element.dataset.originalContent) {
            element.dataset.originalContent = element.innerHTML;
        }

        // Show skeleton
        const skeletonTemplate =
            this.skeletonTemplates.get(type) || this.skeletonTemplates.get('form');
        element.innerHTML = skeletonTemplate;
        element.classList.add('skeleton-active');

        // Auto-hide if duration specified
        if (duration) {
            setTimeout(() => {
                this.hideSkeleton(element);
            }, duration);
        }

        return skeletonId;
    }

    // Hide skeleton and restore content
    hideSkeleton(element) {
        if (!element) return;

        const skeletonId = element.dataset.skeletonId;
        if (skeletonId) {
            this.activeSkeletons.delete(skeletonId);
        }

        // Restore original content
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
            delete element.dataset.originalContent;
        }

        element.classList.remove('skeleton-active');
        delete element.dataset.skeletonId;
    }

    // Show skeleton with loading promise
    async showSkeletonUntil(element, promise, type = 'form') {
        const skeletonId = this.showSkeleton(element, type);

        try {
            await promise;
        } finally {
            this.hideSkeleton(element);
        }
    }

    // Setup intersection observer for lazy skeleton loading
    setupIntersectionObserver() {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const skeletonType = element.dataset.skeletonType || 'form';
                        const skeletonDuration = parseInt(element.dataset.skeletonDuration) || null;

                        if (element.dataset.skeletonAuto === 'true') {
                            this.showSkeleton(element, skeletonType, skeletonDuration);
                        }
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '50px',
            }
        );

        // Observe elements with skeleton attributes
        document.querySelectorAll('[data-skeleton-auto="true"]').forEach((el) => {
            observer.observe(el);
        });
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            // Skeleton directive
            Alpine.directive('skeleton', (el, { expression }, { evaluateLater, effect }) => {
                const evaluate = evaluateLater(expression);
                let options = {};

                effect(() => {
                    evaluate((value) => {
                        if (typeof value === 'object') {
                            options = value;
                        } else if (typeof value === 'string') {
                            options = { type: value };
                        }
                    });
                });

                // Show skeleton on mount
                if (options.showOnMount !== false) {
                    this.showSkeleton(el, options.type || 'form');
                }
            });

            // Skeleton store
            Alpine.store('skeleton', {
                show(element, type = 'form', duration = null) {
                    return window.skeletonLoader.showSkeleton(element, type, duration);
                },

                hide(element) {
                    window.skeletonLoader.hideSkeleton(element);
                },

                async showUntil(element, promise, type = 'form') {
                    return window.skeletonLoader.showSkeletonUntil(element, promise, type);
                },
            });
        });
    }

    // Predefined skeleton show methods
    showFormSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'form', duration);
    }

    showTableSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'table', duration);
    }

    showCardSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'card', duration);
    }

    showListSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'list', duration);
    }

    showAIContentSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'ai-content', duration);
    }

    showMapSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'map', duration);
    }

    showProgressSkeleton(element, duration = null) {
        return this.showSkeleton(element, 'progress', duration);
    }

    // Cleanup all active skeletons
    cleanup() {
        this.activeSkeletons.forEach((skeletonId) => {
            const element = document.querySelector(`[data-skeleton-id="${skeletonId}"]`);
            if (element) {
                this.hideSkeleton(element);
            }
        });
        this.activeSkeletons.clear();
    }

    // Get active skeleton count
    getActiveSkeletonCount() {
        return this.activeSkeletons.size;
    }
}

// Global instance
window.skeletonLoader = new SkeletonLoader();

// Auto-setup Alpine integration
window.skeletonLoader.setupAlpineIntegration();

// Export for module usage
export default SkeletonLoader;
