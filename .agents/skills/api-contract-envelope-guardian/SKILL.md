---
name: api-contract-envelope-guardian
description: Frontend fetch/axios çağrıları ile Laravel route ve JSON yanıt zarflarını eşleştirir; endpoint, HTTP durum kodu, alan adı ve tenant kapsamı drift'ini release öncesi yakalar.
---

# API Contract Envelope Guardian

Bu yetenek, browser kodunun beklediği API sözleşmesiyle backend rotasının gerçekten sunduğu sözleşmeyi birlikte inceler.

## Denetim

- `fetch`, `axios` ve API helper çağrılarını çıkar; URL, HTTP metodu ve çağıran ekranı kaydet.
- URL'nin `routes/api.php`, `routes/admin.php` veya ilgili route modülünde varlığını ve middleware/tenant sınırını doğrula.
- Başarı ve hata durumlarını ayrı sözleşme olarak kontrol et. `200`, `201`, `204`, `401`, `403`, `404`, `419`, `422`, `429`, `500` değerlerini frontend davranışıyla eşleştir.
- JSON zarfının `success`, `data`, `message` ve hata kodu alanlarını; pagination ve boş sonuç davranışını endpoint bazında doğrula.
- `status`/`yayin_durumu`, `id`/`ilan_id`, `email`/`eposta` gibi alan eşleşmelerini kanonik API sözleşmesine bağla; sessiz fallback ekleme.
- Contract testinde yalnız URL'nin cevap vermesini yeterli sayma; HTTP metodu, response body şekli, auth/tenant davranışı ve frontend tüketimini birlikte doğrula.

## Kanıt

Statik tarama `REPO_VERIFIED` olabilir. Gerçek endpoint ve browser tüketimi ancak test koşusuyla `TEST_VERIFIED`/`BROWSER_VERIFIED` olur; production rotası için ayrıca `PRODUCTION_VERIFIED` gerekir.

## Sınırlar

API kontratını düzeltmek migration, seed, canlı veri değişikliği veya production deploy yetkisi vermez. Belirsiz bir endpoint'i `200` döndüren sahte route ile kapatma.
