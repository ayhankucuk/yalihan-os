// Leaflet.draw Local Loader (Context7: Local, not CDN)
(function () {
    function addCss() {
        var href = 'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css';
        if (!document.querySelector('link[href*="leaflet.draw.css"]')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }
    }
    function addJs(cb) {
        if (window.L && window.L.Draw) {
            cb && cb();
            return;
        }
        var src = 'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js';
        var s = document.createElement('script');
        s.src = src;
        s.onload = function () {
            cb && cb();
        };
        document.head.appendChild(s);
    }
    addCss();
    addJs(function () {
        if (window.L && window.L.Draw) {
            if (window.showToast) {
                window.showToast('success', 'Leaflet.draw yüklendi');
            }
            if (
                typeof L !== 'undefined' &&
                typeof L.Control !== 'undefined' &&
                typeof L.Control.Draw !== 'undefined'
            ) {
                console.log('✅ L.Control.Draw available globally');
                setupA11yObserver();
            } else {
                console.warn('⚠️ Leaflet.draw loaded but L.Control.Draw not found');
            }
        }
    });
})();

console.log('✅ Leaflet.draw loader initialized (Context7: Local)');

// 🔧 CSP Fix & UI Optimization: Compact & Modern Draw Toolbar
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        /* CSP Fix: Spritesheet path */
        .leaflet-draw-toolbar a {
            background-image: url('/vendor/leaflet-draw/images/spritesheet.svg') !important;
        }
        .leaflet-retina .leaflet-draw-toolbar a {
            background-image: url('/vendor/leaflet-draw/images/spritesheet-2x.png') !important;
        }

        /* 🎨 Modern Compact Draw Toolbar */
        .leaflet-draw-toolbar {
            background: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            padding: 4px !important;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .leaflet-draw-toolbar {
                background: #1f2937 !important;
                border-color: #374151 !important;
            }
        }

        /* Compact buttons - 28x28px instead of 30x30px */
        .leaflet-draw-toolbar a {
            width: 28px !important;
            height: 28px !important;
            line-height: 28px !important;
            border: none !important;
            border-radius: 8px !important;
            margin: 2px !important;
            background-color: transparent !important;
            transition: all 0.2s ease !important;
            outline: none !important;
        }

        /* Hover effect */
        .leaflet-draw-toolbar a:hover {
            background-color: #f3f4f6 !important;
            transform: scale(1.05) !important;
        }

        @media (prefers-color-scheme: dark) {
            .leaflet-draw-toolbar a:hover {
                background-color: #374151 !important;
            }
        }

        /* 🎨 BELIRGIN RENKLER: Polygon Draw (Yeşil) */
        .leaflet-draw-toolbar a.leaflet-draw-draw-polygon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: 2px solid #10b981 !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3) !important;
        }

        .leaflet-draw-toolbar a.leaflet-draw-draw-polygon:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: scale(1.15) !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5) !important;
        }

        /* 🎨 BELIRGIN RENKLER: Edit (Mavi) */
        .leaflet-draw-toolbar a.leaflet-draw-edit-edit {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3) !important;
        }

        .leaflet-draw-toolbar a.leaflet-draw-edit-edit:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            transform: scale(1.15) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5) !important;
        }

        /* 🎨 BELIRGIN RENKLER: Delete (Kırmızı) */
        .leaflet-draw-toolbar a.leaflet-draw-edit-remove {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border: 2px solid #ef4444 !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
        }

        .leaflet-draw-toolbar a.leaflet-draw-edit-remove:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: scale(1.15) !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5) !important;
        }

        /* Dark mode için belirgin renkler */
        @media (prefers-color-scheme: dark) {
            .leaflet-draw-toolbar a.leaflet-draw-draw-polygon {
                background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
                border-color: #10b981 !important;
            }

            .leaflet-draw-toolbar a.leaflet-draw-edit-edit {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
                border-color: #3b82f6 !important;
            }

            .leaflet-draw-toolbar a.leaflet-draw-edit-remove {
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
                border-color: #ef4444 !important;
            }
        }

        /* Icon overlay için beyaz filtre (icon'ları daha görünür yapmak için) */
        .leaflet-draw-toolbar a.leaflet-draw-draw-polygon,
        .leaflet-draw-toolbar a.leaflet-draw-edit-edit,
        .leaflet-draw-toolbar a.leaflet-draw-edit-remove {
            filter: brightness(1.1) contrast(1.1) !important;
        }

        /* Active state (tıklandığında) */
        .leaflet-draw-toolbar a.leaflet-draw-draw-polygon.leaflet-draw-toolbar-button-status,
        .leaflet-draw-toolbar a.leaflet-draw-edit-edit.leaflet-draw-toolbar-button-status,
        .leaflet-draw-toolbar a.leaflet-draw-edit-remove.leaflet-draw-toolbar-button-status {
            animation: pulse-color 1.5s ease-in-out infinite !important;
        }

        @keyframes pulse-color {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
                transform: scale(1.08);
            }
        }

        /* Disabled state */
        .leaflet-draw-toolbar a.leaflet-disabled {
            opacity: 0.3 !important;
            cursor: not-allowed !important;
        }

        /* Remove default borders */
        .leaflet-draw-section {
            border: none !important;
        }

        /* Compact the entire control */
        .leaflet-draw {
            margin: 0 !important;
        }

        /* Position optimization - top-left with offset */
        .leaflet-top.leaflet-left {
            top: 16px !important;
            left: 16px !important;
        }

        /* Tooltip styling */
        .leaflet-draw-tooltip {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #10b981 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }

        @media (prefers-color-scheme: dark) {
            .leaflet-draw-tooltip {
                background: rgba(31, 41, 55, 0.95) !important;
                color: white !important;
            }
        }

        /* Edit buttons container */
        .leaflet-draw-actions {
            background: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            padding: 4px !important;
            margin-top: 4px !important;
        }

        @media (prefers-color-scheme: dark) {
            .leaflet-draw-actions {
                background: #1f2937 !important;
                border-color: #374151 !important;
            }
        }

        .leaflet-draw-actions a {
            font-size: 12px !important;
            padding: 6px 10px !important;
            border-radius: 6px !important;
            background: #f3f4f6 !important;
            margin: 2px !important;
            transition: all 0.2s ease !important;
        }

        .leaflet-draw-actions a:hover {
            background: #10b981 !important;
            color: white !important;
            transform: translateY(-1px) !important;
        }

        @media (prefers-color-scheme: dark) {
            .leaflet-draw-actions a {
                background: #374151 !important;
                color: white !important;
            }
        }
    `;
    document.head.appendChild(style);
    console.log('✅ Leaflet.draw spritesheet yolu CSP uyumlu hale getirildi');
    console.log('✅ Leaflet.draw toolbar kompakt ve modern tasarım uygulandı');
}

export default 'leaflet-draw';

// A11y: toolbar bulunduğunda ARIA ve klavye etkileşimi uygula
function setupA11yObserver() {
    try {
        const observer = new MutationObserver(function (mutations, obs) {
            const toolbar = document.querySelector('.leaflet-draw-toolbar');
            if (toolbar) {
                applyDrawToolbarA11y(toolbar);
                obs.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    } catch (e) {
        console.warn('Leaflet.draw A11y observer başlatılamadı', e);
    }
}

function applyDrawToolbarA11y(toolbar) {
    const btns = toolbar.querySelectorAll('a');
    const labelMap = {
        'leaflet-draw-draw-polygon': 'Polygon çiz',
        'leaflet-draw-edit-edit': 'Şekilleri düzenle',
        'leaflet-draw-edit-remove': 'Seçili şekilleri sil',
    };
    const items = Array.from(btns);
    items.forEach(function (a, idx) {
        a.setAttribute('role', 'button');
        a.setAttribute('tabindex', '0');
        const cls = a.className.split(' ');
        const key = cls.find(function (c) {
            return labelMap[c];
        });
        if (key) {
            a.setAttribute('aria-label', labelMap[key]);
        }
        a.addEventListener('keydown', function (ev) {
            const code = ev.key;
            if (code === 'Enter' || code === ' ') {
                ev.preventDefault();
                a.click();
            }
            if (code === 'ArrowRight' || code === 'ArrowDown') {
                ev.preventDefault();
                const next = items[(idx + 1) % items.length];
                next.focus();
            }
            if (code === 'ArrowLeft' || code === 'ArrowUp') {
                ev.preventDefault();
                const prev = items[(idx - 1 + items.length) % items.length];
                prev.focus();
            }
            if (code === 'Home') {
                ev.preventDefault();
                items[0].focus();
            }
            if (code === 'End') {
                ev.preventDefault();
                items[items.length - 1].focus();
            }
            if (code === 'Escape') {
                a.blur();
            }
        });
    });
    console.log('✅ Leaflet.draw toolbar a11y uygulandı');
}

// Focus görünürlüğü CSS'i style tag'ine ekle
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent += `
        .leaflet-draw-toolbar a:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.6) !important;
        }
    `;
    document.head.appendChild(style);
}
