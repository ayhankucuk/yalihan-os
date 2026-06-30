// Frontend (public) JS entry — yalnızca Alpine.js + collapse.
// Admin/wizard modülleri DAHİL DEĞİL; vitrin sayfaları hafif kalsın diye ayrıldı.
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;

function startAlpine() {
    if (!window.__alpineStarted && typeof Alpine !== 'undefined') {
        window.__alpineStarted = true;
        Alpine.start();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startAlpine);
} else {
    startAlpine();
}
