---
name: blade-alpine-runtime-guardian
description: Laravel Blade ve Alpine.js yaşam döngüsünü proaktif denetler; JS hatalarını, Alpine scope sorunlarını ve Blade/JS sözleşme uçlaklarını kod yazarken yakalar.
---

# Blade Alpine Runtime Guardian

Blade render çıktısı ile tarayıcı çalışma zamanı arasındaki sözleşmeyi denetler. CSS/görsel tasarım incelemesi yapmaz.

---

## Ne Zaman Kullanılır

- `resources/views/**/*.blade.php` içinde `<script>`, `@push`, `@include`, `x-data`, `x-init`, `x-on`, `x-model`, `:required` veya `:class` değiştiğinde
- `resources/js/**/*.js` içindeki fonksiyonlar Blade inline ifadelerinden çağrıldığında
- Leaflet, Chart.js veya Alpine component başlatıldığında
- Browser testi `ReferenceError`, `Unexpected token`, `already initialized` veya `Failed to load resource` ürettiğinde

---

## Zorunlu Denetimler

### 1. Script Bütünlüğü
```blade
{{-- ❌ YASAK: @push/@endpush scope dışına taşma --}}
@push('scripts')
<script>
function initMap() {
    // Bu kapanış başka bir Blade bloğuna taşabilir
}
</script>
@endpush

{{-- ✅ ZORUNLU: Kapalı birimi doğrula --}}
@push('scripts')
<script>
function initMap() { /* ... */ }
</script>
@endpush
```

### 2. Alpine Inline İfade Parse Hataları
```blade
{{-- ❌ RİSK: Tek tırnak, template literal veya çok satırlı ifade çakışması --}}
<x-input :required="$item->field === 'açıklama'">

{{-- ✅ GÜVENLİ: Parse edilebilir ifade, parantez içinde --}}
:x-data="{ show: {{ $show }}, items: {{ Js::encode($items) }} }"
```

### 3. Global Fonksiyon Bağlama
```js
// ❌ YASAK: Module export tek başına yeterli değil
export function initWizard() { /* ... */ }

// ✅ ZORUNLU: Inline Alpine handler için window üzerinde bağla
window.initWizard = function() { /* ... */ };
// veya
document.addEventListener('alpine:init', () => {
    Alpine.data('wizard', () => ({ /* ... */ }));
});
```

### 4. Alpine Scope Değişken Tanımlığı
```blade
{{-- ❌ YASAK: Tanımsız değişkeni fallback ile gizleme --}}
<div x-show="undefinedVar"></div>

{{-- ✅ ZORUNLU: Tanımsız değişkeni testte görünür bırak --}}
<div x-show="typeof items !== 'undefined' ? items.length : false"></div>
```

### 5. DOM Bağlı Kütüphanelerde Idempotent Başlatma
```js
// ❌ YASAK: Her çağrıda yeni instance
const map = L.map(el).setView([lat, lng], 13);

// ✅ ZORUNLU: Mevcut instance'ı kontrol et
if (!el._leaflet_id) {
    const map = L.map(el).setView([lat, lng], 13);
}
```

### 6. CDN/Async Script Sırası
```blade
{{-- ❌ YASAK: Global beklemeden kullanma --}}
<script>
    window.L.Icon.Default.imagePath = '/images/'; // L henüz yok
</script>

{{-- ✅ ZORUNLU: Async/defer durumunda window.L kontrolü --}}
<script>
    window.waitForLeaflet = setInterval(() => {
        if (window.L) {
            window.L.Icon.Default.imagePath = '/images/';
            clearInterval(waitForLeaflet);
        }
    }, 50);
</script>
```

### 7. Route URL Mutabakatı
```js
// ❌ YASAK: Regex ile href karşılaştırma
assert(link.href.match(/\/ilanlar\/\d+/));

// ✅ ZORUNLU: Normalize edilmiş path karşılaştırması
assert.strictEqual(new URL(link.href).pathname, '/ilanlar/123');
```

---

## Browser Test Kanıt Toplama

Playwright testlerinde şunları topla:

```js
// Console error yakala
page.on('console', msg => {
    if (msg.type() === 'error') errors.push(msg.text());
});

// Network hatası yakala
page.on('requestfailed', req => {
    failures.push({ url: req.url(), failure: req.failure()?.errorText });
});

// 4xx/5xx yakala (app origin)
page.on('response', res => {
    if (res.url().startsWith(BASE_URL) && res.status() >= 400) {
        httpErrors.push({ status: res.status(), url: res.url() });
    }
});
```

**Release-blocking hatalar:**
- `ReferenceError`
- Alpine expression hatası
- `Unexpected token`
- HTTP 500 (app origin)

---

## Sınırlar

- CDN veya üçüncü taraf ağ hatasını ancak URL/ekran/kullanıcı akışıyla ilgisiz olduğu kanıtlanırsa istisna olarak kaydet
- Kaynağı belirsiz console/network hatasını filtreleme; `UNKNOWN` olarak raporla
- Bu yetenek migration, seed, production deploy veya canlı veri düzeltme yetkisi vermez
