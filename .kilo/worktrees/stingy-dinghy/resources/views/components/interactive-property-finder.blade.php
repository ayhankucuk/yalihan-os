{{-- ========================================
     INTERACTIVE PROPERTY FINDER
     Tab-based filtreleme sistemi
     ======================================== --}}

<div class="bg-white rounded-3xl shadow-2xl p-8 max-w-6xl mx-auto dark:bg-slate-900" x-data="{
    activeTab: 'satilik',
    selectedSubcategory: '',
    location: '',
    checkInDate: '',
    checkOutDate: '',
    guests: 2,
    results: [],
    loading: false,
    showResults: false,
    showSubcategories: false,
    subcategories: {
        'satilik': [
            { id: 'daire', name: 'Daire', icon: 'apartment', color: 'blue' },
            { id: 'villa', name: 'Villa', icon: 'home', color: 'green' },
            { id: 'arsa', name: 'Arsa', icon: 'park', color: 'brown' },
            { id: 'isyeri', name: 'İşyeri', icon: 'store', color: 'purple' }
        ],
        'kiralik': [
            { id: 'daire', name: 'Daire', icon: 'apartment', color: 'blue' },
            { id: 'villa', name: 'Villa', icon: 'home', color: 'green' },
            { id: 'ofis', name: 'Ofis', icon: 'work', color: 'gray' },
            { id: 'dukkkan', name: 'Dükkan', icon: 'store', color: 'orange' }
        ],
        'yazlik': [
            { id: 'villa', name: 'Yazlık Villa', icon: 'beach_access', color: 'cyan', fields: ['havuz', 'deniz_mesafe', 'klima', 'wifi'] },
            { id: 'apart', name: 'Apart Daire', icon: 'apartment', color: 'blue', fields: ['klima', 'wifi', 'otopark'] },
            { id: 'bungalov', name: 'Bungalov', icon: 'home', color: 'green', fields: ['bahce', 'barbekü', 'wifi'] },
            { id: 'studio', name: 'Studio', icon: 'bed', color: 'pink', fields: ['klima', 'wifi'] }
        ]
    },
    categoryFields: {
        'villa': {
            'havuz': { name: 'Havuz', icon: 'pool', type: 'checkbox' },
            'deniz_mesafe': { name: 'Denize Mesafe', icon: 'water', type: 'select', options: ['0-100m', '100-300m', '300-500m', '500m+'] },
            'klima': { name: 'Klima', icon: 'ac_unit', type: 'checkbox' },
            'wifi': { name: 'WiFi', icon: 'wifi', type: 'checkbox' }
        },
        'apart': {
            'klima': { name: 'Klima', icon: 'ac_unit', type: 'checkbox' },
            'wifi': { name: 'WiFi', icon: 'wifi', type: 'checkbox' },
            'otopark': { name: 'Otopark', icon: 'directions_car', type: 'checkbox' }
        },
        'bungalov': {
            'bahce': { name: 'Bahçe', icon: 'eco', type: 'checkbox' },
            'barbekü': { name: 'Barbekü', icon: 'local_fire_department', type: 'checkbox' },
            'wifi': { name: 'WiFi', icon: 'wifi', type: 'checkbox' }
        },
        'studio': {
            'klima': { name: 'Klima', icon: 'ac_unit', type: 'checkbox' },
            'wifi': { name: 'WiFi', icon: 'wifi', type: 'checkbox' }
        }
    },
    priceRanges: {
        'satilik': [
            { value: '0-500000', label: '500.000 ₺\'ye kadar' },
            { value: '500000-1000000', label: '500.000 - 1.000.000 ₺' },
            { value: '1000000-2000000', label: '1.000.000 - 2.000.000 ₺' },
            { value: '2000000-5000000', label: '2.000.000 - 5.000.000 ₺' },
            { value: '5000000+', label: '5.000.000 ₺ üzeri' }
        ],
        'kiralik': [
            { value: '0-5000', label: '5.000 ₺\'ye kadar' },
            { value: '5000-10000', label: '5.000 - 10.000 ₺' },
            { value: '10000-20000', label: '10.000 - 20.000 ₺' },
            { value: '20000-50000', label: '20.000 - 50.000 ₺' },
            { value: '50000+', label: '50.000 ₺ üzeri' }
        ]
    },
    searchProperties() {
        this.loading = true;
        this.showResults = false;

        // API çağrısı simülasyonu
        setTimeout(() => {
            this.results = [
                { id: 1, title: 'Bodrum Merkez\'de Lüks Villa', price: '2.500.000', type: 'Villa', location: 'Bodrum Merkez', image: '/images/default-property.jpg' },
                { id: 2, title: 'Deniz Manzaralı Daire', price: '850.000', type: 'Daire', location: 'Bitez', image: '/images/default-property.jpg' },
                { id: 3, title: 'Yatırımlık Arsa', price: '1.200.000', type: 'Arsa', location: 'Yalıkavak', image: '/images/default-property.jpg' }
            ];
            this.loading = false;
            this.showResults = true;
        }, 1500);
    }
}">

    <!-- Header -->
    <div class="text-center mb-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">
            <span class="material-symbols-outlined text-blue-600 mr-3">search</span>
            Akıllı Emlak Bulucu
        </h2>
        <p class="text-lg text-gray-600">Hayalinizdeki mülkü bulmak için filtreleri kullanın</p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex justify-center mb-8">
        <div class="bg-gray-100 rounded-2xl p-2 inline-flex dark:bg-slate-900">
            <button @click="activeTab = 'satilik'; selectedSubcategory = ''; showSubcategories = false"
                :class="activeTab === 'satilik' ? 'bg-white text-blue-600 shadow-lg' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 rounded-xl font-bold text-lg transition-all duration-300 flex items-center">
                <span class="material-symbols-outlined mr-3 text-xl">label</span>
                Satılık
            </button>
            <button @click="activeTab = 'kiralik'; selectedSubcategory = ''; showSubcategories = false"
                :class="activeTab === 'kiralik' ? 'bg-white text-green-600 shadow-lg' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 rounded-xl font-bold text-lg transition-all duration-300 flex items-center">
                <span class="material-symbols-outlined mr-3 text-xl">key</span>
                Kiralık
            </button>
            <button @click="activeTab = 'yazlik'; selectedSubcategory = ''; showSubcategories = false"
                :class="activeTab === 'yazlik' ? 'bg-white text-cyan-600 shadow-lg' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 rounded-xl font-bold text-lg transition-all duration-300 flex items-center">
                <span class="material-symbols-outlined mr-3 text-xl">beach_access</span>
                Yazlık Kiralık
            </button>
        </div>
    </div>

    <!-- Subcategory Selection - Büyük ve Kolay Tıklanabilir -->
    <div class="mb-12">
        <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center dark:text-slate-100 dark:text-white">
            <span x-show="activeTab === 'satilik'">🏠 Satılık Emlak Türü Seçin</span>
            <span x-show="activeTab === 'kiralik'">🔑 Kiralık Emlak Türü Seçin</span>
            <span x-show="activeTab === 'yazlik'">🏖️ Yazlık Türü Seçin</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="subcategory in subcategories[activeTab]" :key="subcategory.id">
                <button @click="selectedSubcategory = subcategory.id; showSubcategories = true"
                    :class="selectedSubcategory === subcategory.id ?
                        'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-2xl scale-105 ring-4 ring-blue-200' :
                        'bg-white text-gray-700 hover:bg-gray-50 hover:shadow-xl border-2 border-gray-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700'"
                    class="p-8 rounded-3xl transition-all duration-300 transform hover:scale-105 cursor-pointer">
                    <div class="text-center">
                        <span class="material-symbols-outlined mb-4 block" style="font-size:3rem" x-text="subcategory.icon"></span>
                        <div class="text-xl font-bold" x-text="subcategory.name"></div>
                        <div class="text-sm mt-2 opacity-75" x-show="selectedSubcategory !== subcategory.id">👆 Tıklayın
                        </div>
                        <div class="text-sm mt-2 font-bold" x-show="selectedSubcategory === subcategory.id">✅ Seçildi
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    <!-- Category Specific Fields - Yazlık için özel alanlar -->
    <div x-show="selectedSubcategory && activeTab === 'yazlik'" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        class="mb-12 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-3xl p-8 border border-cyan-200">

        <h4 class="text-2xl font-bold text-cyan-800 mb-6 text-center">
            🏖️ Yazlık Özel Özellikleri
        </h4>

        <!-- Yazlık Villa için özel alanlar -->
        <div x-show="selectedSubcategory === 'villa'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Havuz -->
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-cyan-200 hover:border-cyan-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-cyan-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-cyan-600 mr-3">pool</span>
                    <span class="text-lg font-semibold">Havuz İstiyorum</span>
                </label>

                <!-- Klima -->
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-cyan-200 hover:border-cyan-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-cyan-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-cyan-600 mr-3">ac_unit</span>
                    <span class="text-lg font-semibold">Klima İstiyorum</span>
                </label>

                <!-- WiFi -->
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-cyan-200 hover:border-cyan-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-cyan-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-cyan-600 mr-3">wifi</span>
                    <span class="text-lg font-semibold">WiFi İstiyorum</span>
                </label>

                <!-- Denize Mesafe -->
                <div class="p-4 bg-white rounded-2xl border-2 border-cyan-200 dark:bg-slate-900">
                    <label class="block text-lg font-semibold text-cyan-800 mb-3">
                        <span class="material-symbols-outlined text-2xl text-cyan-600 mr-3">water</span>
                        Denize Mesafe
                    </label>
                    <select class="w-full p-3 border border-cyan-300 rounded-xl text-lg">
                        <option value="">Önemli değil</option>
                        <option value="0-100">0-100 metre</option>
                        <option value="100-300">100-300 metre</option>
                        <option value="300-500">300-500 metre</option>
                        <option value="500+">500+ metre</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Apart Daire için özel alanlar -->
        <div x-show="selectedSubcategory === 'apart'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-blue-200 hover:border-blue-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-blue-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-blue-600 mr-3">ac_unit</span>
                    <span class="text-lg font-semibold">Klima</span>
                </label>

                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-blue-200 hover:border-blue-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-blue-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-blue-600 mr-3">wifi</span>
                    <span class="text-lg font-semibold">WiFi</span>
                </label>

                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-blue-200 hover:border-blue-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-blue-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-blue-600 mr-3">directions_car</span>
                    <span class="text-lg font-semibold">Otopark</span>
                </label>
            </div>
        </div>

        <!-- Bungalov için özel alanlar -->
        <div x-show="selectedSubcategory === 'bungalov'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-green-200 hover:border-green-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-green-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-green-600 mr-3">eco</span>
                    <span class="text-lg font-semibold">Bahçe</span>
                </label>

                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-green-200 hover:border-green-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-green-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-green-600 mr-3">local_fire_department</span>
                    <span class="text-lg font-semibold">Barbekü</span>
                </label>

                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-green-200 hover:border-green-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-green-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-green-600 mr-3">wifi</span>
                    <span class="text-lg font-semibold">WiFi</span>
                </label>
            </div>
        </div>

        <!-- Studio için özel alanlar -->
        <div x-show="selectedSubcategory === 'studio'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-pink-200 hover:border-pink-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-pink-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-pink-600 mr-3">ac_unit</span>
                    <span class="text-lg font-semibold">Klima</span>
                </label>

                <label
                    class="flex items-center p-4 bg-white rounded-2xl border-2 border-pink-200 hover:border-pink-400 cursor-pointer transition-all dark:bg-slate-900">
                    <input type="checkbox" class="w-6 h-6 text-pink-600 rounded mr-4">
                    <span class="material-symbols-outlined text-2xl text-pink-600 mr-3">wifi</span>
                    <span class="text-lg font-semibold">WiFi</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Yazlık için Tarih ve Misafir Seçimi -->
    <div x-show="activeTab === 'yazlik'" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        class="mb-12 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-3xl p-8 border border-yellow-200">

        <h4 class="text-2xl font-bold text-orange-800 mb-6 text-center">
            📅 Tatil Tarihleri ve Misafir Sayısı
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Giriş Tarihi -->
            <div class="space-y-2">
                <label class="block text-lg font-semibold text-orange-800">
                    <span class="material-symbols-outlined text-xl mr-2">event_available</span>
                    Giriş Tarihi
                </label>
                <input type="date" x-model="checkInDate"
                    class="w-full p-4 text-lg border-2 border-orange-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>

            <!-- Çıkış Tarihi -->
            <div class="space-y-2">
                <label class="block text-lg font-semibold text-orange-800">
                    <span class="material-symbols-outlined text-xl mr-2">event_busy</span>
                    Çıkış Tarihi
                </label>
                <input type="date" x-model="checkOutDate"
                    class="w-full p-4 text-lg border-2 border-orange-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>

            <!-- Misafir Sayısı -->
            <div class="space-y-2">
                <label class="block text-lg font-semibold text-orange-800">
                    <span class="material-symbols-outlined text-xl mr-2">group</span>
                    Misafir Sayısı
                </label>
                <select x-model="guests"
                    class="w-full p-4 text-lg border-2 border-orange-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="1">1 Kişi</option>
                    <option value="2">2 Kişi</option>
                    <option value="3">3 Kişi</option>
                    <option value="4">4 Kişi</option>
                    <option value="5">5 Kişi</option>
                    <option value="6">6 Kişi</option>
                    <option value="8">8 Kişi</option>
                    <option value="10">10+ Kişi</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Filters Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Location Filter -->
        <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300">Konum</label>
            <div class="relative">
                <input type="text" x-model="location" placeholder="Şehir, ilçe veya mahalle..."
                    class="w-full px-4 py-3 pl-12 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all dark:bg-slate-900">
                <span class="material-symbols-outlined absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">location_on</span>
            </div>
        </div>

        <!-- Price Range Filter -->
        <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300">Fiyat Aralığı</label>
            <select x-model="priceRange"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all dark:bg-slate-900">
                <option value="">Fiyat seçin</option>
                <template x-for="range in priceRanges[activeTab]" :key="range.value">
                    <option :value="range.value" x-text="range.label"></option>
                </template>
            </select>
        </div>

        <!-- Room Count Filter (only for konut) -->
        <div class="space-y-2" x-show="propertyType === 'konut'">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300">Oda Sayısı</label>
            <select x-model="roomCount"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all dark:bg-slate-900">
                <option value="">Oda sayısı</option>
                <option value="1+1">1+1</option>
                <option value="2+1">2+1</option>
                <option value="3+1">3+1</option>
                <option value="4+1">4+1</option>
                <option value="5+">5+ oda</option>
            </select>
        </div>

        <!-- Area Filter (for arsa) -->
        <div class="space-y-2" x-show="propertyType === 'arsa'">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300">Arsa Büyüklüğü</label>
            <select
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all dark:bg-slate-900">
                <option value="">Alan seçin</option>
                <option value="0-500">500 m²'ye kadar</option>
                <option value="500-1000">500 - 1.000 m²</option>
                <option value="1000-2000">1.000 - 2.000 m²</option>
                <option value="2000+">2.000 m² üzeri</option>
            </select>
        </div>
    </div>

    <!-- Search Button -->
    <div class="text-center mb-8">
        <button @click="searchProperties()" :disabled="loading"
            :class="loading ? 'bg-gray-400 cursor-not-allowed' :
                'bg-gradient-to-r from-blue-600 to-purple-600 hover:shadow-xl hover:scale-105'"
            class="px-12 py-4 text-white font-bold rounded-2xl transition-all duration-300 transform">
            <template x-if="loading">
                <div class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Aranıyor...
                </div>
            </template>
            <template x-if="!loading">
                <div class="flex items-center">
                    <span class="material-symbols-outlined mr-3">search</span>
                    Emlak Ara
                </div>
            </template>
        </button>
    </div>

    <!-- Results Section -->
    <div x-show="showResults" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0" class="border-t border-gray-200 pt-8 dark:border-slate-700">

        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                <span x-text="results.length"></span> İlan Bulundu
            </h3>
            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <span class="material-symbols-outlined">filter_list</span>
                <span>Filtreler status</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <template x-for="property in results" :key="property.id">
                <div
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 border border-gray-100 dark:bg-slate-900 dark:border-slate-800">
                    <div class="relative">
                        <img :src="property.image" :alt="property.title"
                            class="w-full h-48 object-cover rounded-t-2xl">
                        <div
                            class="absolute top-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                            <span x-text="property.type"></span>
                        </div>
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm p-2 rounded-full dark:bg-slate-900/90">
                            <span class="material-symbols-outlined text-gray-400 hover:text-red-500 cursor-pointer transition-colors">favorite</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h4 class="font-bold text-lg text-gray-900 mb-2 dark:text-slate-100 dark:text-white" x-text="property.title"></h4>
                        <div class="flex items-center text-gray-600 mb-3">
                            <span class="material-symbols-outlined mr-2">location_on</span>
                            <span x-text="property.location"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-2xl font-bold text-blue-600">
                                <span x-text="property.price"></span> ₺
                            </div>
                            <button
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <span class="material-symbols-outlined mr-1">visibility</span>
                                Detay
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        <div class="p-4">
            <div class="text-3xl font-bold text-blue-600 mb-2">500+</div>
            <div class="text-gray-600">Aktif İlan</div>
        </div>
        <div class="p-4">
            <div class="text-3xl font-bold text-green-600 mb-2">1200+</div>
            <div class="text-gray-600">Mutlu Müşteri</div>
        </div>
        <div class="p-4">
            <div class="text-3xl font-bold text-purple-600 mb-2">15</div>
            <div class="text-gray-600">Yıl Deneyim</div>
        </div>
        <div class="p-4">
            <div class="text-3xl font-bold text-orange-600 mb-2">50+</div>
            <div class="text-gray-600">Uzman Danışman</div>
        </div>
    </div>
</div>
