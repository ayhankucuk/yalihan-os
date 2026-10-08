{{-- Pricing Settings Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Fiyatlandırma Ayarları
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Fiyat gösterim ve hesaplama ayarları.</p>
    </div>

    <x-admin.form-field label="Fiyat Yuvarlama" name="price_rounding" hint="Fiyatların nasıl yuvarlanacağını seçin">
        <x-admin.select name="price_rounding">
            <option value="none" {{ ($settings['price_rounding'] ?? 'none') == 'none' ? 'selected' : '' }}>
                Yuvarlama Yok
            </option>
            <option value="nearest_1000" {{ ($settings['price_rounding'] ?? '') == 'nearest_1000' ? 'selected' : '' }}>
                En Yakın 1.000
            </option>
            <option value="nearest_10000" {{ ($settings['price_rounding'] ?? '') == 'nearest_10000' ? 'selected' : '' }}>
                En Yakın 10.000
            </option>
        </x-admin.select>
    </x-admin.form-field>
</div>
