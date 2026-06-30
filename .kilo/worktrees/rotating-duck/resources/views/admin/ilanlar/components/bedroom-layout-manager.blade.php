{{-- Bedroom Layout Manager Component --}}
{{-- TatildeKirala/Airbnb "Nerede Uyuyacaksınız" özelliği --}}

<div x-data="bedroomLayoutManager({{ json_encode($ilan->bedroom_layout ?? null) }})"
     class="bg-white dark:bg-slate-900 rounded-xl border-2 border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                🛏️ Yatak Odası Düzeni
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Her odadaki yatak tiplerini belirleyin (TatildeKirala/Airbnb tarzı)
            </p>
        </div>
        <button
            type="button"
            @click="addBedroom()"
            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105">
            ➕ Oda Ekle
        </button>
    </div>

    {{-- Bedrooms List --}}
    <div class="space-y-4 mb-6">
        <template x-for="(bedroom, index) in bedrooms" :key="index">
            <div class="p-5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900/50 hover:border-blue-500 dark:hover:border-blue-400 transition-colors dark:bg-slate-900">
                <div class="flex items-start justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="`${index + 1}. Yatak Odası`"></h4>
                    <button
                        type="button"
                        @click="removeBedroom(index)"
                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-semibold text-sm">
                        ✖ Sil
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Oda İsmi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Oda İsmi (Opsiyonel)
                        </label>
                        <input
                            type="text"
                            x-model="bedroom.room_name"
                            :placeholder="`${index + 1}. Yatak Odası`"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Yatak Tipi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Yatak Tipi
                        </label>
                        <select
                            x-model="bedroom.bed_type"
                            @change="updateCapacity(index)"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="double">🛏️ Çift Kişilik (160x200 cm)</option>
                            <option value="single">🛏️ Tek Kişilik (90x200 cm)</option>
                            <option value="queen">👑 Queen Size (150x200 cm)</option>
                            <option value="king">👑 King Size (180x200 cm)</option>
                            <option value="bunk">🪜 Ranza (2 yatak)</option>
                            <option value="sofa_bed">🛋️ Çekyat</option>
                        </select>
                    </div>

                    {{-- Yatak Sayısı --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Yatak Sayısı
                        </label>
                        <input
                            type="number"
                            x-model.number="bedroom.bed_count"
                            @input="updateCapacity(index)"
                            min="1"
                            max="5"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Kapasite (Otomatik) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Kapasite
                        </label>
                        <div class="flex items-center gap-3">
                            <span class="px-4 py-2.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg font-bold text-lg" x-text="`${bedroom.capacity} kişi`"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">(Otomatik hesaplanır)</span>
                        </div>
                    </div>

                    {{-- Ek Özellikler --}}
                    <div class="col-span-2">
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="bedroom.ensuite_bathroom"
                                    class="w-5 h-5 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">🚿 Kendi Banyosu Var</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="bedroom.balcony"
                                    class="w-5 h-5 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">🌿 Balkon Var</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="bedroom.air_conditioning"
                                    class="w-5 h-5 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">❄️ Klimalı</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Extra Sleeping Areas --}}
    <div class="border-t-2 border-gray-200 dark:border-slate-800 pt-6 mb-6 dark:border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                ➕ Ekstra Yatak Alanları (Opsiyonel)
            </h4>
            <button
                type="button"
                @click="addExtraSleeping()"
                class="px-3 py-1.5 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700 transition-colors">
                + Ekstra Alan Ekle
            </button>
        </div>

        <template x-for="(extra, index) in extraSleeping" :key="index">
            <div class="p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 mb-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <input
                            type="text"
                            x-model="extra.location"
                            placeholder="Konum (Oturma Odası, Salon, etc.)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white text-sm">
                    </div>
                    <div>
                        <select
                            x-model="extra.bed_type"
                            @change="updateExtraCapacity(index)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white text-sm">
                            <option value="sofa_bed">🛋️ Çekyat</option>
                            <option value="single">🛏️ Tek Yatak</option>
                            <option value="mattress">🧘 Şilte/Yatak</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm font-semibold dark:bg-slate-900 dark:text-slate-100" x-text="`${extra.capacity} kişi`"></span>
                        <button
                            type="button"
                            @click="removeExtraSleeping(index)"
                            class="text-red-600 hover:text-red-700 text-sm">
                            ✖
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Summary --}}
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Yatak Odası</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="bedrooms.length"></p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Yatak</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400" x-text="totalBeds"></p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Kapasite</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="`${totalCapacity} kişi`"></p>
            </div>
        </div>
    </div>

    {{-- Hidden Input (JSON data for Laravel) --}}
    <input
        type="hidden"
        name="bedroom_layout"
        :value="JSON.stringify(getLayoutData())">

    <input
        type="hidden"
        name="sleeping_arrangement_notes"
        x-model="notes">

    {{-- Notes (Opsiyonel) --}}
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
            📝 Ek Notlar (Opsiyonel)
        </label>
        <textarea
            x-model="notes"
            rows="2"
            placeholder="Örn: Tüm yataklarda ortopedik yatak, çocuk korkuluğu mevcut..."
            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500"></textarea>
    </div>
</div>

@push('scripts')
<script>
function bedroomLayoutManager(initialData = null) {
    return {
        bedrooms: initialData?.bedrooms || [
            { room: 1, room_name: '', bed_type: 'double', bed_count: 1, capacity: 2, ensuite_bathroom: false, balcony: false, air_conditioning: false }
        ],
        extraSleeping: initialData?.extra_sleeping || [],
        notes: initialData?.notes || '',

        get totalBeds() {
            const bedroomBeds = this.bedrooms.reduce((sum, b) => sum + b.bed_count, 0);
            const extraBeds = this.extraSleeping.reduce((sum, e) => sum + (e.count || 1), 0);
            return bedroomBeds + extraBeds;
        },

        get totalCapacity() {
            const bedroomCap = this.bedrooms.reduce((sum, b) => sum + b.capacity, 0);
            const extraCap = this.extraSleeping.reduce((sum, e) => sum + e.capacity, 0);
            return bedroomCap + extraCap;
        },

        addBedroom() {
            this.bedrooms.push({
                room: this.bedrooms.length + 1,
                room_name: '',
                bed_type: 'double',
                bed_count: 1,
                capacity: 2,
                ensuite_bathroom: false,
                balcony: false,
                air_conditioning: false
            });
        },

        removeBedroom(index) {
            if (this.bedrooms.length > 1) {
                this.bedrooms.splice(index, 1);
                // Oda numaralarını yeniden düzenle
                this.bedrooms.forEach((b, i) => b.room = i + 1);
            } else {
                window.toast?.('En az 1 yatak odası olmalı', 'warning');
            }
        },

        updateCapacity(index) {
            const bedroom = this.bedrooms[index];
            const bedCount = bedroom.bed_count || 1;

            // Yatak tipine göre kişi kapasitesi
            const capacityPerBed = {
                'double': 2,
                'single': 1,
                'queen': 2,
                'king': 2,
                'bunk': 2, // Ranza = 2 kişi
                'sofa_bed': 1
            };

            bedroom.capacity = (capacityPerBed[bedroom.bed_type] || 1) * bedCount;
        },

        addExtraSleeping() {
            this.extraSleeping.push({
                location: 'Oturma Odası',
                bed_type: 'sofa_bed',
                count: 1,
                capacity: 1
            });
        },

        removeExtraSleeping(index) {
            this.extraSleeping.splice(index, 1);
        },

        updateExtraCapacity(index) {
            const extra = this.extraSleeping[index];

            const capacityMap = {
                'sofa_bed': 1,
                'single': 1,
                'mattress': 1
            };

            extra.capacity = capacityMap[extra.bed_type] || 1;
        },

        getLayoutData() {
            return {
                bedrooms: this.bedrooms,
                extra_sleeping: this.extraSleeping,
                total_capacity: this.totalCapacity,
                total_bedrooms: this.bedrooms.length,
                notes: this.notes
            };
        }
    }
}
</script>
@endpush
