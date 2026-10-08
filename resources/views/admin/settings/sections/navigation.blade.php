{{-- Navigation Settings Section --}}
<div class="space-y-6">
    <x-admin.form-alert type="info" title="İlan Navigasyon Özellikleri">
        İlanlar arasında gezinme, önceki/sonraki ilan ve benzer ilanlar özellikleri.
    </x-admin.form-alert>

    <div class="space-y-6">
        <x-admin.form-field label="Navigasyon Özelliğini Aktif Et" name="navigation_durumu"
            hint="İlan navigasyon özelliğini tüm sistemde aktif/pasif yapar">
            <x-admin.toggle
                name="navigation_durumu"
                :checked="($settings['navigation_durumu'] ?? 'true') == 'true' || ($settings['navigation_durumu'] ?? true) === true"
            />
        </x-admin.form-field>

        <x-admin.form-field label="Varsayılan Navigasyon Modu" name="navigation_default_mode"
            hint="Önceki/sonraki ilan gösterim yöntemi">
            <x-admin.select name="navigation_default_mode">
                <option value="default" {{ ($settings['navigation_default_mode'] ?? 'default') == 'default' ? 'selected' : '' }}>
                    Varsayılan (Tüm ilanlar)
                </option>
                <option value="category" {{ ($settings['navigation_default_mode'] ?? '') == 'category' ? 'selected' : '' }}>
                    Kategori Bazlı
                </option>
                <option value="location" {{ ($settings['navigation_default_mode'] ?? '') == 'location' ? 'selected' : '' }}>
                    Konum Bazlı
                </option>
            </x-admin.select>
        </x-admin.form-field>

        <x-admin.form-field label="Benzer İlanlar Gösterim Sayısı" name="navigation_similar_limit"
            hint="Benzer ilanlar bölümünde gösterilecek ilan sayısı (1-12 arası)">
            <x-admin.input
                type="number"
                name="navigation_similar_limit"
                min="1"
                max="12"
                step="1"
                :value="old('navigation_similar_limit', $settings['navigation_similar_limit'] ?? '4')"
            />
        </x-admin.form-field>

        <x-admin.form-field label="Benzer İlanlar Bölümünü Göster" name="navigation_show_similar"
            hint="İlan detay sayfalarında benzer ilanlar bölümü gösterilir">
            <x-admin.toggle
                name="navigation_show_similar"
                :checked="($settings['navigation_show_similar'] ?? 'true') == 'true' || ($settings['navigation_show_similar'] ?? true) === true"
            />
        </x-admin.form-field>
    </div>
</div>
