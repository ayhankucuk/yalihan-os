/**
 * Context7 Progressive Loading System
 * Progressive content loading ve lazy loading
 *
 * @version 1.0.0
 * @context7-compliant true
 */

class ProgressiveLoader {
    constructor(options = {}) {
        this.options = {
            threshold: 0.1,
            rootMargin: '50px',
            loadingClass: 'neo-progressive-loading',
            loadedAttribute: 'data-loaded',
            skeletonClass: 'neo-skeleton-container',
            ...options,
        };

        this.observer = null;
        this.init();
    }

    init() {
        // Intersection Observer oluştur
        this.observer = new IntersectionObserver((entries) => this.handleIntersection(entries), {
            threshold: this.options.threshold,
            rootMargin: this.options.rootMargin,
        });

        // Sayfa yüklendiğinde progressive elementleri başlat
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.observeAll());
        } else {
            this.observeAll();
        }
    }

    observeAll() {
        // Tüm progressive loading elementlerini gözlemle
        const elements = document.querySelectorAll(`.${this.options.loadingClass}`);
        elements.forEach((el) => this.observe(el));
    }

    observe(element) {
        if (element.getAttribute(this.options.loadedAttribute) !== 'true') {
            this.observer.observe(element);
        }
    }

    handleIntersection(entries) {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                this.loadContent(entry.target);
            }
        });
    }

    async loadContent(element) {
        const loadUrl = element.getAttribute('data-load-url');
        const loadType = element.getAttribute('data-load-type') || 'html';
        const loadTarget = element.getAttribute('data-load-target') || element;

        if (!loadUrl) {
            console.warn('Progressive loader: data-load-url bulunamadı', element);
            return;
        }

        try {
            // Loading state göster
            this.showLoading(element);

            // İçeriği yükle
            const response = await fetch(loadUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: loadType === 'json' ? 'application/json' : 'text/html',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            let content;
            if (loadType === 'json') {
                content = await response.json();
                this.handleJsonContent(element, content);
            } else {
                content = await response.text();
                this.handleHtmlContent(element, content);
            }

            // Loaded state
            element.setAttribute(this.options.loadedAttribute, 'true');
            this.hideLoading(element);

            // Observer'dan kaldır
            this.observer.unobserve(element);

            // Event dispatch
            element.dispatchEvent(
                new CustomEvent('content-loaded', {
                    detail: { content, url: loadUrl },
                })
            );
        } catch (error) {
            console.error('Progressive loading error:', error);
            this.showError(element, error.message);
        }
    }

    handleHtmlContent(element, html) {
        const targetSelector = element.getAttribute('data-load-target');
        const target = targetSelector ? element.querySelector(targetSelector) || element : element;

        // Skeleton'u kaldır
        const skeleton = element.querySelector(`.${this.options.skeletonClass}`);
        if (skeleton) {
            skeleton.remove();
        }

        // İçeriği ekle
        target.innerHTML = html;
    }

    handleJsonContent(element, data) {
        const renderer = element.getAttribute('data-renderer');

        if (renderer && window[renderer]) {
            // Custom renderer function
            window[renderer](element, data);
        } else {
            // Varsayılan JSON render
            this.defaultJsonRender(element, data);
        }
    }

    defaultJsonRender(element, data) {
        const skeleton = element.querySelector(`.${this.options.skeletonClass}`);
        if (skeleton) {
            skeleton.remove();
        }

        element.innerHTML = `<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-auto">${JSON.stringify(
            data,
            null,
            2
        )}</pre>`;
    }

    showLoading(element) {
        element.classList.add('loading');

        // Skeleton yoksa ekle
        if (!element.querySelector(`.${this.options.skeletonClass}`)) {
            const skeletonType = element.getAttribute('data-skeleton-type') || 'text';
            const skeleton = this.createSkeleton(skeletonType);
            element.appendChild(skeleton);
        }
    }

    hideLoading(element) {
        element.classList.remove('loading');
        element.classList.add('loaded');
    }

    showError(element, message) {
        element.setAttribute(this.options.loadedAttribute, 'error');
        element.innerHTML = `
            <div class="neo-alert neo-alert-error" role="alert">
                <i class="neo-icon neo-icon-alert-circle" aria-hidden="true"></i>
                <div class="flex-1">
                    <p class="font-medium">Yükleme hatası</p>
                    <p class="text-sm opacity-90">${this.escapeHtml(message)}</p>
                </div>
            </div>
        `;
    }

    createSkeleton(type) {
        const skeleton = document.createElement('div');
        skeleton.className = `${this.options.skeletonClass} space-y-3`;

        switch (type) {
            case 'card':
                skeleton.innerHTML = this.getCardSkeleton();
                break;
            case 'table':
                skeleton.innerHTML = this.getTableSkeleton();
                break;
            case 'list':
                skeleton.innerHTML = this.getListSkeleton();
                break;
            default:
                skeleton.innerHTML = this.getTextSkeleton();
        }

        return skeleton;
    }

    getTextSkeleton() {
        return `
            <div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div>
            <div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div>
            <div class="neo-skeleton neo-skeleton-animate w-3/4 h-4 rounded"></div>
        `;
    }

    getCardSkeleton() {
        return `
            <div class="neo-skeleton neo-skeleton-animate w-full h-48 rounded-lg"></div>
            <div class="neo-skeleton neo-skeleton-animate w-3/4 h-6 rounded"></div>
            <div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div>
            <div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div>
        `;
    }

    getTableSkeleton() {
        let rows = '';
        for (let i = 0; i < 5; i++) {
            rows += `
                <tr>
                    <td class="px-6 py-4"><div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div></td>
                    <td class="px-6 py-4"><div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div></td>
                    <td class="px-6 py-4"><div class="neo-skeleton neo-skeleton-animate w-full h-4 rounded"></div></td>
                </tr>
            `;
        }
        return `
            <table class="min-w-full">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    ${rows}
                </tbody>
            </table>
        `;
    }

    getListSkeleton() {
        let items = '';
        for (let i = 0; i < 3; i++) {
            items += `
                <div class="flex items-center gap-3 p-4">
                    <div class="neo-skeleton neo-skeleton-animate w-10 h-10 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="neo-skeleton neo-skeleton-animate w-3/4 h-4 rounded"></div>
                        <div class="neo-skeleton neo-skeleton-animate w-1/2 h-3 rounded"></div>
                    </div>
                </div>
            `;
        }
        return items;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public methods
    reload(element) {
        element.setAttribute(this.options.loadedAttribute, 'false');
        this.observe(element);
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

/**
 * Image Lazy Loader
 * Progressive image loading
 */
class ImageLazyLoader {
    constructor(options = {}) {
        this.options = {
            threshold: 0.01,
            rootMargin: '50px',
            placeholder:
                'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23e5e7eb" width="400" height="300"/%3E%3C/svg%3E',
            ...options,
        };

        this.observer = null;
        this.init();
    }

    init() {
        this.observer = new IntersectionObserver((entries) => this.handleIntersection(entries), {
            threshold: this.options.threshold,
            rootMargin: this.options.rootMargin,
        });

        this.observeAll();
    }

    observeAll() {
        const images = document.querySelectorAll('img[data-src], img[loading="lazy"]');
        images.forEach((img) => this.observe(img));
    }

    observe(img) {
        if (!img.getAttribute('data-loaded')) {
            this.observer.observe(img);
        }
    }

    handleIntersection(entries) {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                this.loadImage(entry.target);
            }
        });
    }

    loadImage(img) {
        const src = img.getAttribute('data-src') || img.src;

        if (!src || img.getAttribute('data-loaded') === 'true') {
            return;
        }

        // Loading state
        img.classList.add('neo-image-loading');

        // Yeni image oluştur (preload için)
        const tempImg = new Image();

        tempImg.onload = () => {
            img.src = src;
            img.setAttribute('data-loaded', 'true');
            img.classList.remove('neo-image-loading');
            img.classList.add('neo-image-loaded');
            this.observer.unobserve(img);

            // Event dispatch
            img.dispatchEvent(
                new CustomEvent('image-loaded', {
                    detail: { src },
                })
            );
        };

        tempImg.onerror = () => {
            img.classList.remove('neo-image-loading');
            img.classList.add('neo-image-error');
            img.src = this.options.placeholder;
            img.alt = 'Görsel yüklenemedi';
        };

        tempImg.src = src;
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

// Global instances
if (typeof window !== 'undefined') {
    window.progressiveLoader = new ProgressiveLoader();
    window.imageLazyLoader = new ImageLazyLoader();

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.progressiveLoader.observeAll();
            window.imageLazyLoader.observeAll();
        });
    } else {
        window.progressiveLoader.observeAll();
        window.imageLazyLoader.observeAll();
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ProgressiveLoader, ImageLazyLoader };
}
