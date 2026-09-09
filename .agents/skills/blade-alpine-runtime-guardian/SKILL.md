---
name: blade-alpine-runtime-guardian
description: Laravel Blade ve Alpine/Vanilla JS yaşam döngüsünü proaktif olarak denetler; script bütünlüğü, inline ifade parse hataları, global bağlama ve duplicate component başlatmalarını düzeltmeden önce yakalar.
---

# Blade Alpine Runtime Guardian

Bu yetenek, Blade render çıktısı ile tarayıcı çalışma zamanı arasındaki sözleşmeyi denetler. CSS veya görsel tasarım incelemesi yapmaz; hedefi kullanıcı etkileşimini bozan sessiz JavaScript ve ağ hatalarını kod yazıldığı anda görünür kılmaktır.

## Ne zaman kullanılır

- `resources/views/**/*.blade.php` içinde `<script>`, `@push`, `@include`, `x-data`, `x-init`, `x-on`, `x-model`, `:required` veya `:class` değiştiğinde
- `resources/js/**/*.js` içindeki fonksiyonlar Blade inline ifadelerinden çağrıldığında
- Leaflet, Chart.js, Alpine component veya benzeri DOM bağlı bir parça yeniden başlatılabildiğinde
- Bir browser testi `ReferenceError`, `Unexpected token`, `already initialized` veya `Failed to load resource` ürettiğinde

## Zorunlu denetimler

1. Blade çıktısında açılan/kapanan `<script>` etiketlerini, `@push`/`@endpush` ve `@include` sınırlarını kontrol et. Inline script kapanışı başka bir Blade bloğuna taşmamalı.
2. Alpine inline ifadelerini JavaScript olarak parse edilebilir kabul etme; HTML niteliği içindeki tek/çift tırnak, template literal ve `const`/çok satırlı ifade çakışmalarını render edilmiş HTML üzerinde kontrol et.
3. Blade tarafından çağrılan her fonksiyonun gerçekten global olması gereken durumda `window` üzerinde bağlandığını doğrula. ES module export'u tek başına inline Alpine handler için yeterli değildir.
4. Her `x-data` kapsamındaki değişkenlerin (`x-model`, `x-show`, `:required`, `x-text`) aynı kapsamda tanımlandığını kontrol et. Tanımsız değişkeni fallback ile gizleme; testte `pageerror` olarak görünür bırak.
5. DOM bağlı kütüphanelerde idempotent başlatma uygula. Leaflet için mevcut instance/container (`_leaflet_id` dahil), Alpine component için mevcut state ve event listener temizliğini doğrula.
6. Async script/module sırasını açıkça belirle. CDN kütüphanesi hazır olmadan module veya inline kod `window.L` gibi bir globali kullanmamalı; timeout sonrası sessiz başarı verme.
7. Blade `route()` çıktısının mutlak URL olabileceğini varsay. Browser testleri href mutabakatını regex ile değil normalize edilmiş path karşılaştırmasıyla doğrulamalı: `new URL(link.href).pathname` → beklenen pattern.

## Kanıt ve test

- Önce render edilmiş HTML ve asset sırasını incele; yalnız kaynak dosyasına bakarak tarayıcı sertifikası verme.
- Playwright'ta `console`, `pageerror`, `requestfailed` ve uygulama origin'li 4xx/5xx response olaylarını topla. `ReferenceError`, Alpine expression hatası ve `Unexpected token` release-blocking'dir.
- Harita/chart gibi bileşenler için yalnız DOM görünürlüğünü yeterli sayma; gerçek instance'ın başlatıldığını ve ikinci init'te duplicate hata çıkmadığını doğrula.
- Redirect veya HTTP 200, hedef Blade ekranının sağlıklı olduğunu kanıtlamaz. Hedef ekranın runtime ve kritik etkileşimleri ayrıca test edilmelidir.

## Sınırlar

- CDN veya üçüncü taraf ağ hatasını ancak URL, ekran ve kullanıcı akışıyla ilgisiz olduğu kanıtlanırsa istisna olarak kaydet.
- Kaynağı belirsiz console/network hatasını filtreleme; `UNKNOWN`/`BROWSER_BLOCKED` olarak raporla.
- Bu yetenek migration, seed, production deploy veya canlı veri düzeltme yetkisi vermez.
