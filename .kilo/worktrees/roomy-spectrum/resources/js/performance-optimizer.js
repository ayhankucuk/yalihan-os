/**
 * Yalıhan Emlak - Frontend Performance Optimizer
 * Sayfa yükleme hızını ve kullanıcı deneyimini artırır
 */

class PerformanceOptimizer {
    constructor() {
        this.observers = new Map();
        this.lazyImages = [];
        this.debounceTimers = new Map();
        this.init();
    }

    init() {
        this.setupLazyLoading();
        this.setupIntersectionObserver();
        this.setupDebouncing();
        this.setupImageOptimization();
        this.setupScrollOptimization();
        this.setupFormOptimization();
        this.setupCacheOptimization();
    }

    /**
     * Lazy loading kurulumu
     */
    setupLazyLoading() {
        // Lazy loading için Intersection Observer
        this.imageObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        this.loadImage(img);
                        this.imageObserver.unobserve(img);
                    }
                });
            },
            {
                rootMargin: '50px 0px',
                threshold: 0.1,
            }
        );

        // Lazy loading için img tag'lerini bul
        document.querySelectorAll('img[data-src]').forEach((img) => {
            this.imageObserver.observe(img);
        });
    }

    /**
     * Resim yükleme
     */
    loadImage(img) {
        const src = img.dataset.src;
        if (src) {
            img.src = src;
            img.classList.remove('lazy');
            img.classList.add('loaded');

            // Loading animasyonu
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease-in';

            img.onload = () => {
                img.style.opacity = '1';
            };
        }
    }

    /**
     * Intersection Observer kurulumu
     */
    setupIntersectionObserver() {
        // Sayfa elementleri için Intersection Observer
        this.elementObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        this.elementObserver.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: '100px 0px',
                threshold: 0.1,
            }
        );

        // Animasyon için elementleri bul
        document.querySelectorAll('.animate-on-scroll').forEach((el) => {
            this.elementObserver.observe(el);
        });
    }

    /**
     * Debouncing kurulumu
     * ✅ DUPLICATE REMOVED: debounce/throttle zaten global.js'de tanımlı
     * Burada sadece timer yönetimi için Map kullanılıyor
     */
    setupDebouncing() {
        // Debounce ve throttle global.js'de tanımlı, burada sadece timer yönetimi
        // Eğer global.js yüklenmemişse fallback olarak tanımla
        if (!window.debounce) {
            window.debounce = (func, wait) => {
                return (...args) => {
                    clearTimeout(this.debounceTimers.get(func));
                    const timer = setTimeout(() => func.apply(this, args), wait);
                    this.debounceTimers.set(func, timer);
                };
            };
        }

        if (!window.throttle) {
            window.throttle = (func, limit) => {
                let inThrottle;
                return (...args) => {
                    if (!inThrottle) {
                        func.apply(this, args);
                        inThrottle = true;
                        setTimeout(() => (inThrottle = false), limit);
                    }
                };
            };
        }
    }

    /**
     * Resim optimizasyonu
     */
    setupImageOptimization() {
        // Resim boyutlarını optimize et
        document.querySelectorAll('img').forEach((img) => {
            if (img.complete) {
                this.optimizeImage(img);
            } else {
                img.addEventListener('load', () => this.optimizeImage(img));
            }
        });

        // WebP desteği kontrol et
        this.checkWebPSupport();
    }

    /**
     * Resim optimize et
     */
    optimizeImage(img) {
        const container = img.parentElement;
        if (container && container.offsetWidth > 0) {
            const containerWidth = container.offsetWidth;
            const imgWidth = img.naturalWidth;

            if (imgWidth > containerWidth * 2) {
                img.style.width = '100%';
                img.style.height = 'auto';
            }
        }
    }

    /**
     * WebP desteği kontrol et
     */
    checkWebPSupport() {
        const webP = new Image();
        webP.onload = webP.onerror = () => {
            const isSupported = webP.height === 2;
            if (isSupported) {
                document.documentElement.classList.add('webp');
            } else {
                document.documentElement.classList.add('no-webp');
            }
        };
        webP.src =
            'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    }

    /**
     * Scroll optimizasyonu
     */
    setupScrollOptimization() {
        let ticking = false;

        const updateScroll = () => {
            // Scroll event'lerini optimize et
            const scrollTop = window.pageYOffset;
            const scrollDirection = scrollTop > (this.lastScrollTop || 0) ? 'down' : 'up';

            // Header'ı gizle/göster
            this.handleHeaderVisibility(scrollTop, scrollDirection);

            // Progress bar güncelle
            this.updateScrollProgress(scrollTop);

            this.lastScrollTop = scrollTop;
            ticking = false;
        };

        const requestTick = () => {
            if (!ticking) {
                requestAnimationFrame(updateScroll);
                ticking = true;
            }
        };

        window.addEventListener('scroll', requestTick, { passive: true });
    }

    /**
     * Header görünürlüğü
     */
    handleHeaderVisibility(scrollTop, direction) {
        const header = document.querySelector('.navbar, .header');
        if (header) {
            if (scrollTop > 100) {
                if (direction === 'down') {
                    header.classList.add('header-hidden');
                } else {
                    header.classList.remove('header-hidden');
                }
            } else {
                header.classList.remove('header-hidden');
            }
        }
    }

    /**
     * Scroll progress bar
     */
    updateScrollProgress(scrollTop) {
        const progressBar = document.querySelector('.scroll-progress');
        if (progressBar) {
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            progressBar.style.width = `${progress}%`;
        }
    }

    /**
     * Form optimizasyonu
     */
    setupFormOptimization() {
        // Form validation optimizasyonu
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
            form.addEventListener('input', this.handleFormInput.bind(this));
        });
    }

    /**
     * Form submit handler
     */
    handleFormSubmit(event) {
        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
        }
    }

    /**
     * Form input handler
     */
    handleFormInput(event) {
        const input = event.target;
        const formGroup = input.closest('.form-group');

        if (formGroup) {
            // Real-time validation
            this.validateInput(input);
        }
    }

    /**
     * Input validation
     */
    validateInput(input) {
        const value = input.value.trim();
        const type = input.type;
        const required = input.hasAttribute('required');

        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (required && !value) {
            isValid = false;
            errorMessage = 'Bu alan zorunludur';
        }

        // Email validation
        if (type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Geçerli bir email adresi girin';
        }

        // Phone validation
        if (type === 'tel' && value && !this.isValidPhone(value)) {
            isValid = false;
            errorMessage = 'Geçerli bir telefon numarası girin';
        }

        this.showInputValidation(input, isValid, errorMessage);
    }

    /**
     * Email validation
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Phone validation
     */
    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Input validation göster
     */
    showInputValidation(input, isValid, errorMessage) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;

        let errorElement = formGroup.querySelector('.error-message');

        if (!isValid) {
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message text-red-500 text-sm mt-1';
                formGroup.appendChild(errorElement);
            }
            errorElement.textContent = errorMessage;
            input.classList.add('border-red-500');
            input.classList.remove('border-green-500');
        } else {
            if (errorElement) {
                errorElement.remove();
            }
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');
        }
    }

    /**
     * Cache optimizasyonu
     */
    setupCacheOptimization() {
        // Service Worker kayıt
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker
                .register('/sw.js')
                .then((registration) => {
                    console.log('Service Worker registered:', registration);
                })
                .catch((error) => {
                    console.log('Service Worker registration failed:', error);
                });
        }

        // LocalStorage optimizasyonu
        this.optimizeLocalStorage();
    }

    /**
     * LocalStorage optimize et
     */
    optimizeLocalStorage() {
        // Eski verileri temizle
        const keys = Object.keys(localStorage);
        const now = Date.now();

        keys.forEach((key) => {
            if (key.startsWith('yalihan_')) {
                try {
                    const data = JSON.parse(localStorage.getItem(key));
                    if (data.expires && data.expires < now) {
                        localStorage.removeItem(key);
                    }
                } catch (e) {
                    // Invalid data, remove it
                    localStorage.removeItem(key);
                }
            }
        });
    }

    /**
     * Performans metrikleri
     */
    getPerformanceMetrics() {
        const navigation = performance.getEntriesByType('navigation')[0];
        const paint = performance.getEntriesByType('paint');

        return {
            dns: navigation.domainLookupEnd - navigation.domainLookupStart,
            tcp: navigation.connectEnd - navigation.connectStart,
            ttfb: navigation.responseStart - navigation.requestStart,
            domLoad: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
            windowLoad: navigation.loadEventEnd - navigation.loadEventStart,
            firstPaint: paint.find((entry) => entry.name === 'first-paint')?.startTime || 0,
            firstContentfulPaint:
                paint.find((entry) => entry.name === 'first-contentful-paint')?.startTime || 0,
        };
    }

    /**
     * Performans raporu
     */
    generatePerformanceReport() {
        const metrics = this.getPerformanceMetrics();
        const report = {
            timestamp: new Date().toISOString(),
            metrics: metrics,
            userAgent: navigator.userAgent,
            connection: navigator.connection
                ? {
                      effectiveType: navigator.connection.effectiveType,
                      downlink: navigator.connection.downlink,
                      rtt: navigator.connection.rtt,
                  }
                : null,
        };

        // Raporu localStorage'a kaydet
        localStorage.setItem(
            'yalihan_performance_report',
            JSON.stringify({
                ...report,
                expires: Date.now() + 24 * 60 * 60 * 1000, // 24 saat
            })
        );

        return report;
    }
}

// Global instance
window.performanceOptimizer = new PerformanceOptimizer();

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceOptimizer;
}
