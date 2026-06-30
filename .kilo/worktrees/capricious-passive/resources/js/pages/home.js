export function initHome() {
    (function () {
        const BODY = document.body;

        const parsePayload = (payload) => {
            if (typeof payload === 'string') {
                try {
                    return JSON.parse(payload);
                } catch (error) {
                    return payload;
                }
            }
            return payload;
        };

        const formatVirtualTourUrl = (url) => {
            if (!url || typeof url !== 'string') {
                return '';
            }

            const trimmed = url.trim();
            if (trimmed.includes('watch?v=')) {
                return trimmed.replace('watch?v=', 'embed/');
            }
            if (trimmed.includes('youtu.be/')) {
                return trimmed.replace('youtu.be/', 'youtube.com/embed/');
            }
            return trimmed;
        };

        const showModal = (modal) => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            BODY.classList.add('overflow-hidden');
        };

        const hideModal = (modal) => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');

            const openModals = document.querySelectorAll('.fixed.inset-0.flex');
            if (openModals.length === 0) {
                BODY.classList.remove('overflow-hidden');
            }
        };

        const resetVirtualTourModal = (modal) => {
            const iframe = modal.querySelector('[data-role="virtual-tour-iframe"]');
            const placeholder = modal.querySelector('[data-role="virtual-tour-placeholder"]');
            if (iframe) {
                iframe.src = '';
                iframe.classList.add('hidden');
            }
            placeholder?.classList.remove('hidden');
        };

        const resetGalleryModal = (modal) => {
            const grid = modal.querySelector('[data-role="gallery-grid"]');
            const placeholder = modal.querySelector('[data-role="gallery-placeholder"]');
            if (grid) {
                grid.innerHTML = '';
            }
            placeholder?.classList.remove('hidden');
        };

        const resetMapModal = (modal) => {
            const frame = modal.querySelector('[data-role="map-frame"]');
            const placeholder = modal.querySelector('[data-role="map-placeholder"]');
            if (frame) {
                frame.src = '';
                frame.classList.add('hidden');
            }
            placeholder?.classList.remove('hidden');
        };

        const resetPropertyDetailModal = (modal) => {
            modal.querySelector('#propertyDetailTitle').textContent = 'İlan Detayları';
            const price = modal.querySelector('[data-role="detail-price"]');
            const location = modal.querySelector('[data-role="detail-location"]');
            const description = modal.querySelector('[data-role="detail-description"]');
            const features = modal.querySelector('[data-role="detail-features"]');
            const featuresPlaceholder = modal.querySelector('[data-role="detail-features-placeholder"]');
            const link = modal.querySelector('[data-role="detail-link"]');

            if (price) price.textContent = '';
            if (location) location.textContent = 'Lokasyon bilgisi yakında eklenecek.';
            if (description) description.textContent = 'Bu portföy için açıklama hazırlanıyor. Danışmanlarımız en kısa sürede içeriği güncelleyecek.';
            if (features) features.innerHTML = '';
            if (featuresPlaceholder) featuresPlaceholder.classList.remove('hidden');
            if (link) {
                link.classList.remove('hidden');
                link.setAttribute('href', '/portfolio');
            }
        };

        const updateVirtualTourModal = (modal, payload) => {
            const iframe = modal.querySelector('[data-role="virtual-tour-iframe"]');
            const placeholder = modal.querySelector('[data-role="virtual-tour-placeholder"]');
            const url = typeof payload === 'string' ? payload : payload?.url ?? '';
            const embedUrl = formatVirtualTourUrl(url);

            if (embedUrl && iframe) {
                iframe.src = embedUrl;
                iframe.classList.remove('hidden');
                placeholder?.classList.add('hidden');
            } else {
                placeholder?.classList.remove('hidden');
                iframe?.classList.add('hidden');
            }
        };

        const updateGalleryModal = (modal, payload) => {
            const grid = modal.querySelector('[data-role="gallery-grid"]');
            const placeholder = modal.querySelector('[data-role="gallery-placeholder"]');
            const images = Array.isArray(payload) ? payload : Array.isArray(payload?.gallery) ? payload.gallery : [];

            if (!grid) {
                return;
            }

            grid.innerHTML = '';

            if (images.length === 0) {
                placeholder?.classList.remove('hidden');
                return;
            }

            placeholder?.classList.add('hidden');

            images.forEach((image) => {
                if (!image?.url) {
                    return;
                }

                const figure = document.createElement('figure');
                figure.className = 'relative overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800';

                const img = document.createElement('img');
                img.src = image.url;
                img.alt = image.alt || 'Portföy görseli';
                img.loading = 'lazy';
                img.className = 'w-full h-full object-cover';

                figure.appendChild(img);
                grid.appendChild(figure);
            });
        };

        const updateMapModal = (modal, payload) => {
            const frame = modal.querySelector('[data-role="map-frame"]');
            const placeholder = modal.querySelector('[data-role="map-placeholder"]');
            const address = modal.querySelector('[data-role="map-address"]');

            const data = typeof payload === 'object' && !Array.isArray(payload) ? payload : {};
            const lat = data?.lat ?? data?.latitude;
            const lng = data?.lng ?? data?.longitude;
            const locationText = data?.content || data?.title || 'Konum bilgisi yakında eklenecek.';

            if (address) {
                address.textContent = locationText;
            }

            if (lat && lng && frame) {
                const src = `https://www.google.com/maps?q=${lat},${lng}&hl=tr&z=15&output=embed`;
                frame.src = src;
                frame.classList.remove('hidden');
                placeholder?.classList.add('hidden');
            } else {
                placeholder?.classList.remove('hidden');
                frame?.classList.add('hidden');
            }
        };

        const updatePropertyDetailModal = (modal, payload) => {
            const data = typeof payload === 'object' && !Array.isArray(payload) ? payload : {};
            const title = data?.title || 'İlan Detayları';
            const price = modal.querySelector('[data-role="detail-price"]');
            const location = modal.querySelector('[data-role="detail-location"]');
            const description = modal.querySelector('[data-role="detail-description"]');
            const features = modal.querySelector('[data-role="detail-features"]');
            const featuresPlaceholder = modal.querySelector('[data-role="detail-features-placeholder"]');
            const link = modal.querySelector('[data-role="detail-link"]');

            modal.querySelector('#propertyDetailTitle').textContent = title;
            if (price) price.textContent = data?.price || '';
            if (location) location.textContent = data?.location || 'Lokasyon bilgisi yakında eklenecek.';
            if (description) description.textContent = data?.description || 'Bu portföy için açıklama hazırlanıyor. Danışmanlarımız en kısa sürede içeriği güncelleyecek.';

            if (features) {
                features.innerHTML = '';
                const featureList = Array.isArray(data?.features) ? data.features : [];
                if (featureList.length === 0) {
                    featuresPlaceholder?.classList.remove('hidden');
                } else {
                    featuresPlaceholder?.classList.add('hidden');
                    featureList.forEach((feature) => {
                        if (!feature?.label || !feature?.value) {
                            return;
                        }
                        const item = document.createElement('li');
                        item.className = 'rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between text-sm dark:border-slate-700';
                        item.innerHTML = `<span class="text-gray-500 dark:text-slate-500">${feature.label}</span><span class="font-semibold text-gray-900 dark:text-slate-100">${feature.value}</span> dark:text-slate-100`;
                        features.appendChild(item);
                    });
                }
            }

            if (link) {
                if (data?.link) {
                    link.setAttribute('href', data.link);
                    link.classList.remove('hidden');
                } else {
                    link.classList.add('hidden');
                }
            }
        };

        window.openYaliihanModal = function (modalId, payload) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            const data = parsePayload(payload);

            switch (modalId) {
                case 'virtualTour':
                    updateVirtualTourModal(modal, data);
                    break;
                case 'gallery':
                    updateGalleryModal(modal, data);
                    break;
                case 'map':
                    updateMapModal(modal, data);
                    break;
                case 'propertyDetail':
                    updatePropertyDetailModal(modal, data);
                    break;
            }

            showModal(modal);
        };

        window.closeModal = function (modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            switch (modalId) {
                case 'virtualTour':
                    resetVirtualTourModal(modal);
                    break;
                case 'gallery':
                    resetGalleryModal(modal);
                    break;
                case 'map':
                    resetMapModal(modal);
                    break;
                case 'propertyDetail':
                    resetPropertyDetailModal(modal);
                    break;
            }

            hideModal(modal);
        };

        const showToast = (message, type = 'success') => {
            if (!message) {
                return;
            }

            const toast = document.createElement('div');
            const background = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';

            toast.className = `fixed top-4 right-4 ${background} text-white rounded-2xl p-4 shadow-2xl z-50 transform translate-x-full transition-transform duration-300 max-w-sm`;
            toast.innerHTML = `<div class="flex items-center"><span class="text-2xl mr-3">${icon}</span><span class="font-medium">${message}</span></div>`;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);

            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        };

        window.showToast = showToast;

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const activeModal = document.querySelector('.fixed.inset-0.flex');
                if (activeModal?.id) {
                    window.closeModal(activeModal.id);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
             // Anchor Smooth Scroll
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
                    const href = link.getAttribute('href');
                    if (!href || href.length <= 1) {
                        return;
                    }
                    const targetId = href.substring(1);
                    const target = document.getElementById(targetId);
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // Intersection Observer for Animation
            try {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-fade-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.15,
                    rootMargin: '0px 0px -40px 0px'
                });

                document.querySelectorAll('.property-card').forEach((card) => observer.observe(card));
            } catch (error) {
                console.error('IntersectionObserver error', error);
            }
        });
    })();
}
