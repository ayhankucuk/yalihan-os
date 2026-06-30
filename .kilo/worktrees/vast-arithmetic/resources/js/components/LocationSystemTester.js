/**
 * 🧪 Context7 Location System Test Script
 * Test all advanced location endpoints and LocationManager functionality
 */

// Test configuration
const TEST_CONFIG = {
    baseUrl: '/api/location',
    testAddress: 'Bodrum Marina, Muğla',
    testCoordinates: {
        lat: 37.0344,
        lng: 27.4305,
    },
    testProvinceId: 48, // Muğla
    testDistrictId: 1, // Example district ID
    testRadius: 5,
};

class LocationSystemTester {
    constructor() {
        this.results = [];
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = `[${timestamp}] ${message}`;

        console.log(
            `%c${logEntry}`,
            type === 'success'
                ? 'color: green'
                : type === 'error'
                  ? 'color: red'
                  : type === 'warning'
                    ? 'color: orange'
                    : 'color: blue'
        );

        this.results.push({ timestamp, message, type });
    }

    async makeRequest(endpoint, options = {}) {
        const url = `${TEST_CONFIG.baseUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
        };

        try {
            const response = await fetch(url, {
                ...defaultOptions,
                ...options,
            });
            const data = await response.json();

            if (response.ok) {
                this.log(`✅ ${endpoint} - Success`, 'success');
                return { success: true, data };
            } else {
                this.log(`❌ ${endpoint} - Failed: ${data.message || 'Unknown error'}`, 'error');
                return { success: false, error: data.message };
            }
        } catch (error) {
            this.log(`❌ ${endpoint} - Network Error: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    async testBasicEndpoints() {
        this.log('🏛️ Testing Basic Location Endpoints', 'info');

        // Test provinces
        const provinces = await this.makeRequest('/iller');
        if (provinces.success) {
            this.log(`Found ${provinces.data.length} provinces`, 'success');
        }

        // Test districts
        const districts = await this.makeRequest(`/districts/${TEST_CONFIG.testProvinceId}`);
        if (districts.success) {
            this.log(
                `Found ${districts.data.length} districts for province ${TEST_CONFIG.testProvinceId}`,
                'success'
            );
        }

        // Test neighborhoods
        if (districts.success && districts.data.length > 0) {
            const firstDistrictId = districts.data[0].id;
            const neighborhoods = await this.makeRequest(`/neighborhoods/${firstDistrictId}`);
            if (neighborhoods.success) {
                this.log(
                    `Found ${neighborhoods.data.length} neighborhoods for district ${firstDistrictId}`,
                    'success'
                );
            }
        }
    }

    async testAdvancedEndpoints() {
        this.log('🌐 Testing Advanced Location Endpoints', 'info');

        // Test geocoding
        const geocodeResult = await this.makeRequest('/geocode', {
            method: 'POST',
            body: JSON.stringify({ address: TEST_CONFIG.testAddress }),
        });

        if (geocodeResult.success) {
            this.log(`Geocoding successful for: ${TEST_CONFIG.testAddress}`, 'success');
            this.log(
                `Coordinates: ${geocodeResult.data.coordinates?.latitude}, ${geocodeResult.data.coordinates?.longitude}`,
                'info'
            );
        }

        // Test reverse geocoding
        const reverseGeocodeResult = await this.makeRequest('/reverse-geocode', {
            method: 'POST',
            body: JSON.stringify({
                latitude: TEST_CONFIG.testCoordinates.lat,
                longitude: TEST_CONFIG.testCoordinates.lng,
            }),
        });

        if (reverseGeocodeResult.success) {
            this.log('Reverse geocoding successful', 'success');
            this.log(`Address: ${reverseGeocodeResult.data.formatted_address}`, 'info');
        }

        // Test nearby search
        const nearbyResult = await this.makeRequest(
            `/nearby/${TEST_CONFIG.testCoordinates.lat}/${TEST_CONFIG.testCoordinates.lng}/${TEST_CONFIG.testRadius}`
        );

        if (nearbyResult.success) {
            this.log(`Found ${nearbyResult.data.length} nearby locations`, 'success');
        }

        // Test address validation
        const validationResult = await this.makeRequest('/validate-address', {
            method: 'POST',
            body: JSON.stringify({
                il_id: TEST_CONFIG.testProvinceId,
                ilce_id: TEST_CONFIG.testDistrictId,
                address: TEST_CONFIG.testAddress,
            }),
        });

        if (validationResult.success) {
            this.log('Address validation successful', 'success');
            this.log(
                `Validation result: ${JSON.stringify(validationResult.data.validation_result)}`,
                'info'
            );
        }
    }

    async testLocationManager() {
        this.log('📍 Testing LocationManager Integration', 'info');

        if (typeof LocationManager === 'undefined') {
            this.log('❌ LocationManager not found - Module not loaded', 'error');
            return;
        }

        try {
            const locationManager = new LocationManager({
                provinceSelect: '#test-province',
                districtSelect: '#test-district',
                neighborhoodSelect: '#test-neighborhood',
                enableGeocoding: true,
                enableReverseGeocoding: true,
                enableNearbySearch: true,
                enableAddressValidation: true,
            });

            this.log('✅ LocationManager instance created successfully', 'success');

            // Test geocoding via LocationManager
            try {
                const geocodeResult = await locationManager.geocode(TEST_CONFIG.testAddress);
                if (geocodeResult) {
                    this.log('✅ LocationManager geocoding successful', 'success');
                }
            } catch (error) {
                this.log(`❌ LocationManager geocoding failed: ${error.message}`, 'error');
            }

            // Test reverse geocoding via LocationManager
            try {
                const reverseResult = await locationManager.reverseGeocode(
                    TEST_CONFIG.testCoordinates.lat,
                    TEST_CONFIG.testCoordinates.lng
                );
                if (reverseResult) {
                    this.log('✅ LocationManager reverse geocoding successful', 'success');
                }
            } catch (error) {
                this.log(`❌ LocationManager reverse geocoding failed: ${error.message}`, 'error');
            }
        } catch (error) {
            this.log(`❌ LocationManager initialization failed: ${error.message}`, 'error');
        }
    }

    async runAllTests() {
        this.log('🚀 Starting Context7 Location System Tests', 'info');
        this.log('='.repeat(60), 'info');

        try {
            await this.testBasicEndpoints();
            await this.testAdvancedEndpoints();
            await this.testLocationManager();

            this.log('='.repeat(60), 'info');
            this.log('🎉 All tests completed!', 'success');

            // Summary
            const successCount = this.results.filter((r) => r.type === 'success').length;
            const errorCount = this.results.filter((r) => r.type === 'error').length;
            const warningCount = this.results.filter((r) => r.type === 'warning').length;

            this.log(
                `📊 Summary: ${successCount} success, ${errorCount} errors, ${warningCount} warnings`,
                'info'
            );

            return {
                success: errorCount === 0,
                results: this.results,
                summary: { successCount, errorCount, warningCount },
            };
        } catch (error) {
            this.log(`💥 Test suite failed: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    // Create test elements for LocationManager testing
    createTestElements() {
        const testContainer = document.createElement('div');
        testContainer.id = 'location-test-container';
        testContainer.style.display = 'none';
        testContainer.innerHTML = `
            <select id="test-province"></select>
            <select id="test-district"></select>
            <select id="test-neighborhood"></select>
            <div id="test-map"></div>
        `;
        document.body.appendChild(testContainer);
    }

    // Clean up test elements
    cleanupTestElements() {
        const testContainer = document.getElementById('location-test-container');
        if (testContainer) {
            testContainer.remove();
        }
    }
}

// Global test runner
window.testLocationSystem = async function () {
    const tester = new LocationSystemTester();
    tester.createTestElements();

    try {
        const results = await tester.runAllTests();
        return results;
    } finally {
        tester.cleanupTestElements();
    }
};

// Auto-run tests if in development mode
if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
    console.log(
        '🧪 Context7 Location System Tester loaded - Run window.testLocationSystem() to start tests'
    );
}

export default LocationSystemTester;
