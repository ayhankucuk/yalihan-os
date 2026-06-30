<!-- Lokasyon Seçimi - Unified Component -->
<!-- ✅ FIXED: location-selector-unified uncommented (2025-12-27) -->
<x-unified-location-selector :selected-country="1" :selected-province="$ilan->il_id ?? (old('il_id') ?? '')" :selected-district="$ilan->ilce_id ?? (old('ilce_id') ?? '')" :selected-neighborhood="$ilan->mahalle_id ?? (old('mahalle_id') ?? '')" :countries="$ulkeler ?? []"
    :required="true" :show-country="false" :show-neighborhood="true" grid-cols="grid-cols-1 md:grid-cols-3" name-prefix=""
    class="mb-6" />
