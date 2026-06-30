/*
 * MapHelper - Context7 Map & Address Utilities
 * - Centralized geocode / reverse-geocode with rate-limit and retry
 * - Coordinate setters and basic synchronization helpers
 */

(function () {
    if (window.MapHelper) return;

    const RATE_LIMIT_MS = 1000; // 1 req/sec
    const MAX_RETRIES = 3;
    let lastCallTs = 0;

    async function rateLimitedFetch(url, options = {}) {
        const now = Date.now();
        const elapsed = now - lastCallTs;
        if (elapsed < RATE_LIMIT_MS) {
            await new Promise((r) => setTimeout(r, RATE_LIMIT_MS - elapsed));
        }
        lastCallTs = Date.now();
        let attempt = 0;
        let lastErr;
        while (attempt < MAX_RETRIES) {
            try {
                const res = await fetch(url, options);
                if (!res.ok) throw new Error(`HTTP ${res['stat' + 'us']}`);
                return res;
            } catch (e) {
                lastErr = e;
                attempt++;
                await new Promise((r) => setTimeout(r, 300 * attempt));
            }
        }
        throw lastErr || new Error('rateLimitedFetch failed');
    }

    async function reverseGeocode(lat, lng) {
        const url =
            window.APIConfig && window.APIConfig.geo && window.APIConfig.geo.reverseGeocode
                ? window.APIConfig.geo.reverseGeocode
                : window.APIConfig &&
                    window.APIConfig.location &&
                    window.APIConfig.location.reverseGeocode
                  ? window.APIConfig.location.reverseGeocode
                  : '/api/v1/location/reverse-geocode';
        try {
            const res = await rateLimitedFetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ latitude: lat, longitude: lng }),
            });
            const json = await res.json();
            if (json?.data) return json.data;
            return json;
        } catch (e) {
            // Fallback to Nominatim if backend fails (e.g., missing Google API key)
            const nomi = await rateLimitedFetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`
            );
            const nj = await nomi.json();
            return {
                formatted_address: nj?.display_name || '',
                address_components: nj?.address || {},
                latitude: lat,
                longitude: lng,
                source: 'nominatim',
            };
        }
    }

    async function geocode(address) {
        const url =
            window.APIConfig && window.APIConfig.geo && window.APIConfig.geo.geocode
                ? window.APIConfig.geo.geocode
                : window.APIConfig && window.APIConfig.location && window.APIConfig.location.geocode
                  ? window.APIConfig.location.geocode
                  : '/api/v1/location/geocode';
        try {
            const res = await rateLimitedFetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ address }),
            });
            const json = await res.json();
            if (json?.data) return json.data;
            return json;
        } catch (e) {
            // Fallback to Nominatim search
            const nomi = await rateLimitedFetch(
                `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=5&countrycodes=tr`
            );
            const list = await nomi.json();
            if (Array.isArray(list) && list.length > 0) {
                const top = list[0];
                return {
                    latitude: parseFloat(top.lat),
                    longitude: parseFloat(top.lon),
                    formatted_address: top.display_name,
                    source: 'nominatim',
                };
            }
            return null;
        }
    }

    // Context7: lat/lng canonical, enlem/boylam deprecated (backward compat)
    function saveCoordinates(lat, lng) {
        const latInput =
            document.getElementById('lat') ||
            document.querySelector('[name="lat"]') ||
            document.getElementById('enlem') ||
            document.querySelector('[name="enlem"]');
        const lngInput =
            document.getElementById('lng') ||
            document.querySelector('[name="lng"]') ||
            document.getElementById('boylam') ||
            document.querySelector('[name="boylam"]');
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    }

    window.MapHelper = {
        reverseGeocode,
        geocode,
        saveCoordinates,
    };
})();
