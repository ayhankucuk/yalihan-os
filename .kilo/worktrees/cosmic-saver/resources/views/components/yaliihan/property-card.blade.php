@props([
    'property' => null,
    'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=250&fit=crop',
    'title' => 'Modern Villa',
    'location' => 'Yalıkavak, Bodrum',
    'price' => '₺8,500,000',
    'pricePeriod' => '',
    'beds' => 4,
    'baths' => 3,
    'area' => 250,
    'badge' => 'sale', // sale, rent, featured
    'badgeText' => 'Satılık',
    'isFavorite' => false,
    'showActions' => true,
    'gallery' => [],
    'virtualTourUrl' => null,
    'mapLocation' => null,
    'detailPayload' => [],
    'contactPayload' => [],
    'shareUrl' => null,
])

@php
    if (filled($property)) {
        $image = data_get($property, 'cover_image', $image);
        $gallery = data_get($property, 'gallery', $gallery);
        $title = data_get($property, 'title', $title);
        $location = data_get($property, 'location', $location);
        $price = data_get($property, 'price_display', $price);
        $pricePeriod = data_get($property, 'price_period', $pricePeriod);
        $beds = data_get($property, 'beds', $beds);
        $baths = data_get($property, 'baths', $baths);
        $area = data_get($property, 'area', $area);
        $badge = data_get($property, 'badge', $badge);
        $badgeText = data_get($property, 'badge_text', $badgeText);
        $virtualTourUrl = data_get($property, 'virtual_tour_url', $virtualTourUrl);
        $mapLocation = data_get($property, 'map_location', $mapLocation);
        $detailPayload = data_get($property, 'detail_payload', $detailPayload);
        $contactPayload = data_get($property, 'contact_payload', $contactPayload);
        $shareUrl = data_get($property, 'share_url', $shareUrl);
    }

    $badgeClasses = [
        'sale' => 'bg-green-500 text-white',
        'rent' => 'bg-blue-500 text-white',
        'featured' => 'bg-yellow-500 text-white',
    ];

    $badgeClass = $badgeClasses[$badge] ?? 'bg-gray-500 dark:bg-gray-400 text-white';
    $hasVirtualTour = filled($virtualTourUrl);
    $galleryItems = is_array($gallery) ? $gallery : [];
    $hasGallery = count($galleryItems) > 0;
    $hasMap = is_array($mapLocation) && !empty($mapLocation['lat']) && !empty($mapLocation['lng']);
    $shareTarget = $shareUrl ?? request()->url();
@endphp

<div
    class="property-card bg-white dark:bg-slate-900 rounded-3xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 dark:border-slate-800 relative group transform hover:-translate-y-2">
    <!-- Property Image -->
    <div class="relative h-72 overflow-hidden">
        <img src="{{ $image }}" alt="{{ $title }}"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

        <!-- Gradient Overlay -->
        <div
            class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
        </div>

        <!-- Badge -->
        <div
            class="absolute top-5 left-5 {{ $badgeClass }} px-4 py-2 rounded-2xl text-sm font-bold shadow-lg backdrop-blur-sm">
            {{ $badgeText }}
        </div>

        <!-- Cortex Yatırım Özeti Overlay -->
        <div class="absolute top-5 left-1/2 -translate-x-1/2 bg-black/45 text-white px-4 py-2 rounded-2xl text-xs shadow-lg backdrop-blur-md"
             id="cortex-summary-chip"
             data-price="{{ str_replace(['₺', ',', '.'], '', $price) }}"
             data-area="{{ (int) $area }}"
             data-monthly-rent="{{ data_get($property,'monthly_rent') }}"
             data-daily-price="{{ data_get($property,'daily_price') }}"
             data-seasonal-price="{{ data_get($property,'seasonal_price') }}">
            <span class="font-semibold">Cortex Analizi:</span>
            <span id="cortex-summary-text">Hesaplanıyor…</span>
        </div>

        <!-- Favorite Button -->
        <div class="absolute top-5 right-5 w-12 h-12 bg-white dark:bg-slate-900/90 bg-opacity-90 backdrop-blur-md rounded-2xl flex items-center justify-center cursor-pointer hover:bg-red-500 hover:text-white transition-all duration-300 shadow-lg"
            data-role="favorite">
            @if ($isFavorite)
                <span class="text-red-500 text-xl">❤️</span>
            @else
                <span class="text-gray-600 dark:text-slate-200 text-xl">🤍</span>
            @endif
        </div>

        <!-- Action Overlay -->
        @if ($showActions)
            <div
                class="absolute bottom-5 left-5 right-5 opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500">
                <div class="flex gap-3">
                    <button
                        class="flex-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3 rounded-2xl hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white transition-all duration-300 text-center shadow-lg font-medium {{ $hasVirtualTour ? '' : 'opacity-40 pointer-events-none cursor-not-allowed' }}"
                        data-role="virtual-tour"
                        data-virtual-tour="{{ $hasVirtualTour ? e($virtualTourUrl) : '' }}"
                        title="360° Sanal Tur"
                        aria-disabled="{{ $hasVirtualTour ? 'false' : 'true' }}">
                        <div class="text-lg mb-1">🔄</div>
                        <div class="text-xs">Sanal Tur</div>
                    </button>
                    <button
                        class="flex-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3 rounded-2xl hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white transition-all duration-300 text-center shadow-lg font-medium {{ $hasGallery ? '' : 'opacity-40 pointer-events-none cursor-not-allowed' }}"
                        data-role="gallery"
                        data-gallery='@json($galleryItems)'
                        title="Fotoğraf Galerisi"
                        aria-disabled="{{ $hasGallery ? 'false' : 'true' }}">
                        <div class="text-lg mb-1">📸</div>
                        <div class="text-xs">Galeri</div>
                    </button>
                    <button
                        class="flex-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3 rounded-2xl hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white transition-all duration-300 text-center shadow-lg font-medium {{ $hasMap ? '' : 'opacity-40 pointer-events-none cursor-not-allowed' }}"
                        data-role="map"
                        data-location='@json($mapLocation)'
                        title="Haritada Göster"
                        aria-disabled="{{ $hasMap ? 'false' : 'true' }}">
                        <div class="text-lg mb-1">🗺️</div>
                        <div class="text-xs">Harita</div>
                    </button>
                    <button
                        class="flex-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3 rounded-2xl hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white transition-all duration-300 text-center shadow-lg font-medium"
                        data-role="share"
                        data-share-url="{{ e($shareTarget) }}"
                        data-share-title="{{ e($title) }}"
                        data-share-text="{{ e('Bu harika portföyü inceleyin!') }}"
                        title="Paylaş">
                        <div class="text-lg mb-1">📤</div>
                        <div class="text-xs">Paylaş</div>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Property Content -->
    <div class="p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-3 line-clamp-2 leading-tight">{{ $title }}</h3>
        <p class="text-gray-600 dark:text-slate-200 mb-6 flex items-center text-lg">
            <span class="text-blue-500 dark:text-blue-400 mr-2">📍</span> {{ $location }}
        </p>

        <!-- Property Details -->
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-2xl hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors duration-300">
                <div class="text-3xl mb-2">🛏️</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Yatak</div>
                <div class="font-bold text-gray-900 dark:text-slate-100 text-xl">{{ $beds }}</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-2xl hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors duration-300">
                <div class="text-3xl mb-2">🚿</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Banyo</div>
                <div class="font-bold text-gray-900 dark:text-slate-100 text-xl">{{ $baths }}</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-2xl hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors duration-300">
                <div class="text-3xl mb-2">📐</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">m²</div>
                <div class="font-bold text-gray-900 dark:text-slate-100 text-xl">{{ $area }}</div>
            </div>
        </div>

        <!-- Price -->
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-6">
            <span class="price-display" data-price="{{ str_replace(['₺', ',', '.'], '', $price) }}">
                {{ $price }}
            </span>
            @if ($pricePeriod)
                <span class="text-xl text-gray-500 dark:text-gray-400 font-normal">{{ $pricePeriod }}</span>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button
                class="flex-1 border-2 border-blue-500 dark:border-blue-400 text-blue-500 dark:text-blue-400 py-3 px-6 rounded-2xl hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600 dark:hover:text-white transition-all duration-300 font-semibold text-lg"
                data-role="property-detail"
                data-detail='@json($detailPayload)'>
                Detayları Gör
            </button>
            <button
                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 text-white py-3 px-6 rounded-2xl hover:from-blue-600 hover:to-blue-700 dark:hover:from-blue-700 dark:hover:to-blue-800 transition-all duration-300 font-semibold text-lg shadow-lg hover:shadow-xl"
                data-role="contact"
                data-contact='@json($contactPayload)'>
                İletişime Geç
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        const cardRoot = document.currentScript?.closest('.property-card');
        if (!cardRoot) {
            return;
        }
        const chip = cardRoot.querySelector('#cortex-summary-chip');
        if (chip) {
            const price = parseFloat(chip.dataset.price || '0');
            const area = parseFloat(chip.dataset.area || '0');
            const rent = parseFloat(chip.dataset.monthlyRent || '0');
            const daily = parseFloat(chip.dataset.dailyPrice || '0');
            const seasonal = parseFloat(chip.dataset.seasonalPrice || '0');
            const txtEl = chip.querySelector('#cortex-summary-text');
            const nf = new Intl.NumberFormat('tr-TR');
            let parts = [];
            if (price > 0 && area > 0) {
                const pm2 = Math.round(price / area);
                parts.push(`₺/m² ${nf.format(pm2)}`);
            }
            if (price > 0 && rent > 0) {
                const months = Math.round(price / rent);
                const years = (months / 12).toFixed(1);
                parts.push(`Amortisman ${months} ay (${years} yıl)`);
            }
            if (seasonal > 0 || daily > 0) {
                const seasonDays = 90;
                const occ = 0.7;
                const annual = seasonal > 0 ? seasonal : (daily > 0 ? Math.round(daily * seasonDays * occ) : 0);
                if (annual > 0 && price > 0) {
                    const years = (price / annual).toFixed(1);
                    parts.push(`ROI ${years} yıl`);
                }
            }
            txtEl.textContent = parts.length ? parts.join(' • ') : 'Veri bekleniyor';
        }

        cardRoot.querySelectorAll('[data-role="favorite"]').forEach(btn => {
            btn.addEventListener('click', () => handleFavorite(btn));
        });

        cardRoot.querySelectorAll('[data-role="virtual-tour"]').forEach(btn => {
            const url = btn.dataset.virtualTour;
            if (!url) {
                return;
            }
            btn.addEventListener('click', () => openModalWithData('virtualTour', url));
        });

        cardRoot.querySelectorAll('[data-role="gallery"]').forEach(btn => {
            const gallery = btn.dataset.gallery;
            if (!gallery || gallery === '[]') {
                return;
            }
            btn.addEventListener('click', () => openModalWithData('gallery', gallery));
        });

        cardRoot.querySelectorAll('[data-role="map"]').forEach(btn => {
            const location = btn.dataset.location;
            if (!location) {
                return;
            }
            btn.addEventListener('click', () => openModalWithData('map', location));
        });

        cardRoot.querySelectorAll('[data-role="property-detail"]').forEach(btn => {
            const detail = btn.dataset.detail;
            if (!detail) {
                return;
            }
            btn.addEventListener('click', () => openModalWithData('propertyDetail', detail));
        });

        cardRoot.querySelectorAll('[data-role="share"]').forEach(btn => {
            btn.addEventListener('click', () => handleShare(btn));
        });

        cardRoot.querySelectorAll('[data-role="contact"]').forEach(btn => {
            btn.addEventListener('click', () => handleContact(btn));
        });

        function handleFavorite(element) {
            const span = element.querySelector('span');
            if (!span) return;
            const isFavorited = span.textContent === '❤️';
            span.textContent = isFavorited ? '🤍' : '❤️';
            span.className = isFavorited ? 'text-gray-600 dark:text-gray-300 text-xl' : 'text-red-500 text-xl';
            dispatchToast(isFavorited ? 'Favorilerden çıkarıldı' : 'Favorilere eklendi', 'success');
        }

        function handleShare(button) {
            const shareUrl = button?.dataset?.shareUrl || window.location.href;
            const shareTitle = button?.dataset?.shareTitle || 'Yalıhan Emlak Portföyü';
            const shareText = button?.dataset?.shareText || 'Bu harika portföyü inceleyin!';
            if (navigator.share) {
                navigator.share({
                    title: shareTitle,
                    text: shareText,
                    url: shareUrl
                });
            } else {
                navigator.clipboard?.writeText(shareUrl).then(() => {
                    dispatchToast('Paylaşım linki kopyalandı!', 'success');
                }).catch(() => dispatchToast('Paylaşım linki: ' + shareUrl, 'info'));
            }
        }

        function handleContact(button) {
            try {
                const payload = button?.dataset?.contact ? JSON.parse(button.dataset.contact) : {};
                if (payload?.id && window.openPropertyContactModal) {
                    window.openPropertyContactModal(payload);
                    return;
                }
                if (payload?.contact_url) {
                    window.location.href = payload.contact_url;
                    return;
                }
            } catch (error) {
                console.error('Context7: contactProperty parse error', error);
            }
            dispatchToast('İletişim formu açılıyor...', 'success');
        }

        function openModalWithData(modalId, payload) {
            if (typeof window.openYaliihanModal === 'function') {
                window.openYaliihanModal(modalId, payload);
            } else {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                }
            }
        }

        function dispatchToast(message, type = 'success') {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
                return;
            }
            const toast = document.createElement('div');
            const borderClass = type === 'success'
                ? 'border-green-500'
                : (type === 'info' ? 'border-blue-500' : 'border-red-500');
            toast.className=`fixed top-4 right-4 bg-white dark:bg-slate-900 rounded-lg p-4 shadow-lg border-l-4 ${borderClass} z-50 transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => document.body.contains(toast) && document.body.removeChild(toast), 300);
            }, 3000);
        }
    })();
</script>
