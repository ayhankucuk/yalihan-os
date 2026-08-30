---
name: golden-thread-certification
description: Yalıhan OS ilan wizard’ı için Step 1–5 browser E2E sertifikasyonu ve kanıt raporlama.
---

# Golden Thread Certification

## Kapsam

İlan Oluştur → Cortex zenginleştirme → fotoğraf/konum → taslak kaydetme → yönetici onayı → yayın → CRM eşleşmesi → danışman görevi zincirini test eder.

## Test ilkeleri

- Gerçek kullanıcı/browser akışını PHPUnit sonucundan ayrı değerlendir.
- Step 1–5 arasında kategori, yayın tipi, dynamic fields, fotoğraf SSOT, lokasyon cascade ve önizleme verisini doğrula.
- Console error, failed network request, HTTP 401/403/419/422/500 ve tenant ihlalini başarısızlık say.
- Gerçek production ilanı yerine açıkça ayrılmış test fixture kullan.
- Authentication bilgilerini isteme veya rapora yazma.
- Testin çalışmadığı durumda sertifikasyon verme; `BLOCKED` veya `UNKNOWN` kullan.

## Kanıt tablosu

Her test için test adı, ortam, tarih, URL, gözlenen sonuç, screenshot/trace yolu ve kanıt etiketi kaydedilir. `BROWSER_VERIFIED` yalnızca browser kanıtıdır; production iddiası için `PRODUCTION_VERIFIED` gerekir.

## Blokaj yönetimi

Lokasyon tabloları boşsa veya parent ID’leri uyuşmuyorsa TC-GT-05/06’yı atla ve blokajı veri/schema sebebiyle kaydet. Seeder’ı körlemesine çalıştırma; `location-data-reconciliation` skill’ine yönlendir.

## Release sınırı

E2E çalıştırmak kod commit’i, production deploy’u, migration veya seed yetkisi vermez. Her canlı veri değişikliği ayrıca açık onay gerektirir.
