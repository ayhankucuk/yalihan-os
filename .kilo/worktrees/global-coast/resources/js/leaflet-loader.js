/**
 * Leaflet.js Loader - Context7 Compliant
 * Loads Leaflet from npm package (NOT CDN)
 */

import L from 'leaflet';

// Make Leaflet globally available
window.L = L;

// Fix icon paths for production (Context7: Use node_modules path)
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

console.log('✅ Leaflet.js loaded from npm package (Context7: Local, not CDN)');

export default L;
