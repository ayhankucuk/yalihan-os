# TECH-DEBT-001: routes/admin.php Modüler Parçalama

**Durum:** OPEN  
**Öncelik:** P1 (sprint blocker değil, teknik borç)  
**Keşif tarihi:** 2026-08-06  
**Bağlantılı ticket:** PRR-R002 (kapalı — memory_limit=1G ile çözüldü)

---

## Problem

`routes/admin.php` 1.728 satır ve yaklaşık 1.675 route içeriyor.  
Her test process'i başlatıldığında bu dosya tamamen yükleniyor → peak memory ~512 MB+.  
Memory limitini 1G'a yükseltmek P0'ı çözdü ancak asıl yük azalmadı.

### Mevcut mimari

```
RouteServiceProvider::boot()
  └── routes/admin.php          ← 1.728 satır, tek dosya
        ├── require admin/ai.php        (59 satır)  ✅ ayrılmış
        └── require admin/ilanlar.php   (109 satır) ✅ ayrılmış
```

### routes/admin/ klasörü — hazır ama bağlanmamış

Aşağıdaki 24 dosya `routes/admin/` altında mevcut ancak **hiçbir yerde require edilmiyor**:

| Dosya | Boyut |
|-------|-------|
| adres_yonetimi.php | 3.7 KB |
| ayarlar.php | 1.5 KB |
| blog.php | 2.9 KB |
| crm.php | 3.4 KB |
| danismanlar.php | 2.9 KB |
| dashboard.php | 6.8 KB |
| eslesmeler.php | 2.5 KB |
| integrations.php | 4.5 KB |
| intelligence.php | 2.4 KB |
| kisiler.php | 2.0 KB |
| kullanicilar.php | 1.3 KB |
| notifications.php | 1.6 KB |
| ozellikler.php | 5.6 KB |
| page_analyzer.php | 1.8 KB |
| profilim.php | 0.7 KB |
| property_hub.php | 11.2 KB |
| property_types.php | 4.8 KB |
| reports.php | 0.9 KB |
| site.php | 2.6 KB |
| takim.php | 1.8 KB |
| talepler.php | 1.3 KB |
| ups.php | 6.7 KB |
| wikimapia.php | 1.4 KB |
| ilanlarim.php | 0.8 KB |

Bu dosyalar önceki bir parçalama girişiminden kalmış taslaklar — ancak içerikleri homojen değil (bkz. Adım 1 bulguları).

---

## Gerçek durum — Adım 1 doğrulama tamamlandı (2026-08-06)

### Senaryo: C — Karışık (dosya bazında karar gerekiyor)

Her `routes/admin/` dosyası incelendi. Üç farklı durum tespit edildi:

#### Tip A — Alt dosya, admin.php'deki inline bloğun **kopyası** (duplicate)
Bu dosyaları require ile bağlamak **route çakışması** yaratır. Önce admin.php'deki inline blok silinmeli, sonra require eklenmelidir.

| Dosya | admin.php'deki karşılık (satır) | Not |
|-------|----------------------------------|-----|
| `crm.php` | L1175–L1216 `prefix('crm')` | Birebir kopya |
| `talepler.php` | L861–L876 `prefix('talepler')` | Kopya |
| `notifications.php` | L1234–L1282 `prefix('notifications')` + `activity-events` | Kopya |
| `profilim.php` | L1290–L1307 `taleplerim`, `raporlarim`, `profilim` | Kopya |
| `wikimapia.php` | L826–L839 `prefix('/wikimapia-search')` | Kopya |
| `kullanicilar.php` | L633–L641 `resource('/kullanicilar')` + L1380 `bulk-kisi` | Kopya |
| `kisiler.php` | L802–L825 `prefix('/kisiler')` + `kisilerim` | Kopya |
| `danismanlar.php` | L841–L860 `prefix('/danisman')` + `danisman-ai` + `kisilerim` | Kopya |
| `eslesmeler.php` | L877–L903 `prefix('/eslesmeler')` + `talep-portfolyo` + `matching.feedback` | Kopya |
| `reports.php` | L1047–L1060 `prefix('/reports')` | Kopya |
| `ayarlar.php` | L1112–L1134 `prefix('/ayarlar')` | Kopya |
| `blog.php` | L422–L524 `prefix('/blog')` | Kopya |
| `page_analyzer.php` | L1357–L1379 `prefix('/page-analyzer')` | Kopya |
| `adres_yonetimi.php` | L1314–L1356 `prefix('adres-yonetimi')` | Kopya |
| `intelligence.php` | L128–L135 `prefix('/intelligence')` + market-intelligence grupları | Kopya |
| `ilanlarim.php` | L540–L565 `prefix('/ilanlarim')` | Kopya |

#### Tip B — Alt dosya, admin.php'deki bloktan **fazlasını** içeriyor (superset)
Admin.php'deki inline blok silinmeli; alt dosyadaki ekstra route'lar korunmalı.

| Dosya | Fark |
|-------|------|
| `dashboard.php` | Admin.php'de olmayan: `yalihan-bekci`, `governance` (tüm DecisionEngine), `monitoring` grupları |
| `integrations.php` | Admin.php L375–L390'a göre `ai-settings` ve `outbound-notifications` grupları fazladan var |
| `intelligence.php` | `market-intelligence` ve `api/market-intelligence` grupları admin.php'de farklı middleware ile çıplak duruyor (L1503–L1528) |

#### Tip C — Alt dosya, admin.php'deki blokla **kısmen örtüşüyor** (dikkatli birleştirme)

| Dosya | Sorun |
|-------|-------|
| `ozellikler.php` | Admin.php L964–L1046'da `ozellikler` grubu var; alt dosya bu grubun içine sarılmış — middleware wrapper eksik |
| `property_hub.php` | Admin.php L210–L316 ile örtüşüyor; alt dosyada `observability` alt grubu fazladan var, `field-suggestions` grubu admin.php'de yok |
| `property_types.php` | Admin.php L766–L801 `property-type-manager` + L947–L963 `features-management` ile örtüşüyor |
| `takim.php` | Admin.php L1157–L1174 `telegram-bot` ile örtüşüyor; `site-apartman` admin.php'de comment'li |
| `site.php` | Admin.php L1392–L1419 `yazlik-kiralama` ile örtüşüyor; `site-apartman` comment'li |

#### ⚠️ Tip D — Alt dosya bağımsız middleware wrapper kullanıyor (kritik)

| Dosya | Sorun |
|-------|-------|
| `ups.php` | `Route::prefix('admin/ups')->name('admin.ups.')` ile **kendi middleware grubunu** tanımlıyor (`['web', 'auth', 'admin', 'role:admin', 'verified', 'throttle:30,1']`). Admin.php L1542'deki blokla birebir aynı yapı. Bu dosya require edilirse **çift kayıt** olur. |

#### ⚠️ Admin.php'de middleware wrapper **dışında** çıplak route grupları (L1380+)

Şu prefix grupları ana `middleware(['web','auth','verified','role:admin','sab.write.guard'])` wrapper'ının **dışında** tanımlanmış — kendi middleware'lerini tekrar belirtiyorlar:

| Satır aralığı | Prefix | Middleware |
|---------------|--------|-----------|
| L1380–L1391 | `admin/bulk-kisi` | `['web','auth','admin','role:admin']` |
| L1392–L1419 | `admin/yazlik-kiralama` | `['web','auth','admin','role:admin']` |
| L1420–L1436 | `admin/danisman-ai` | `['web','auth','admin','role:admin']` |
| L1437–L1450 | `admin/kisi-not` | `['web','auth','admin','role:admin']` |
| L1451–L1461 | `admin/ai-category` | middleware yok |
| L1462–L1470 | `admin/analytics` | middleware yok |
| L1471–L1502 | `admin/address`, `admin/locations` | `['web','auth','role:admin']` |
| L1503–L1528 | `admin/market-intelligence`, api grupları | `['web','auth','role:admin']` |
| L1529–L1541 | `api/admin` events | `['web','auth']` |
| L1542–L1625 | `admin/ups` | `['web','auth','admin','role:admin','verified','throttle:30,1']` |
| L1627–L1636 | Danışman reservation | özel middleware |
| L1637–L1652 | `admin/ilanlar/{ilan}/calendar` | `['web','auth','verified','throttle:30,1']` |
| L1653–L1663 | `matching/feedback` | yok |
| L1664–L1675 | `api/v1/admin/address` | `['web','auth','admin','role:admin']` |
| L1676–L1700 | `admin/finance` | `['auth','role:admin']` |
| L1701–L1711 | `admin/my-wallet` | `['auth']` |
| L1712–L1722 | `danisman` | `['auth','role:danisman']` |
| L1723–1729 | `monitoring` | yok |

Bu grupların **`sab.write.guard` middleware'inden muaf** olması kasıtlı mı, hata mı — doğrulanması gerekiyor.

---

## Risk

Memory limit 1G şu an yeterli; ancak route sayısı büyümeye devam ederse 1G da yetersiz kalabilir.  
Daha kritik risk: tek dosyada 1.700+ satır → merge conflict sıklığı, reviewer yorgunluğu, yanlış route silinmesi.

---

## Hedef mimari

```
RouteServiceProvider::boot()
  └── routes/admin.php   ← sadece middleware wrapper + require listesi (~30 satır)
        ├── require admin/dashboard.php
        ├── require admin/ilanlar.php
        ├── require admin/ilanlarim.php
        ├── require admin/kullanicilar.php
        ├── require admin/danismanlar.php
        ├── require admin/kisiler.php
        ├── require admin/talepler.php
        ├── require admin/crm.php
        ├── require admin/eslesmeler.php
        ├── require admin/ozellikler.php
        ├── require admin/property_hub.php
        ├── require admin/property_types.php
        ├── require admin/blog.php
        ├── require admin/site.php
        ├── require admin/ayarlar.php
        ├── require admin/adres_yonetimi.php
        ├── require admin/integrations.php
        ├── require admin/intelligence.php
        ├── require admin/notifications.php
        ├── require admin/reports.php
        ├── require admin/takim.php
        ├── require admin/profilim.php
        ├── require admin/page_analyzer.php
        ├── require admin/wikimapia.php
        ├── require admin/ups.php
        └── require admin/ai.php
```

---

## Uygulama planı

> ✅ Adım 1 tamamlandı — bkz. "Gerçek durum" bölümü.

### Adım 2 — Middleware anomalilerini karara bağla (önce yap)

Admin.php'nin L1380+ satırlarında `sab.write.guard` middleware'inden muaf route grupları var.
Her grup için sorulması gereken: **Bu muafiyet kasıtlı mı?**

```
[ ] bulk-kisi      — sab.write.guard yok, kasıtlı mı?
[ ] yazlik-kiralama — sab.write.guard yok, kasıtlı mı?
[ ] danisman-ai    — sab.write.guard yok, kasıtlı mı?
[ ] ai-category    — middleware tamamen yok, kasıtlı mı?
[ ] analytics      — middleware tamamen yok, kasıtlı mı?
[ ] monitoring     — middleware tamamen yok, kasıtlı mı?
[ ] matching/feedback — middleware yok, public erişim mi?
[ ] ups            — sab.write.guard yok, throttle:30,1 var — kasıtlı
[ ] finance        — sab.write.guard yok, kasıtlı mı?
```

**Bu karar verilmeden taşıma yapılmamalı** — yanlış middleware wrapper'a almak gizli güvenlik değişikliği olur.

### Adım 3 — Tip A dosyaları ile başla (en az riskli)

Her modül ayrı commit; sıra önemli (bağımlılık yok):

```
[ ] 1. crm.php          → admin.php L1175–L1216 + L1207 customers bloğu sil, require ekle
[ ] 2. talepler.php     → admin.php L861–L876 sil, require ekle
[ ] 3. notifications.php → admin.php L1234–L1282 sil, require ekle
[ ] 4. profilim.php     → admin.php L1290–L1307 sil, require ekle
[ ] 5. wikimapia.php    → admin.php L826–L839 sil, require ekle
[ ] 6. kullanicilar.php → admin.php L633–L641 + L1380 bulk-kisi sil, require ekle
[ ] 7. kisiler.php      → admin.php L802–L825 + L1284 kisilerim sil, require ekle
[ ] 8. danismanlar.php  → admin.php L841–L860 + L1420 danisman-ai sil, require ekle
[ ] 9. eslesmeler.php   → admin.php L877–L903 + L1217 talep-portfolyo + L1653 feedback sil, require ekle
[ ] 10. reports.php     → admin.php L1047–L1060 sil, require ekle
[ ] 11. ayarlar.php     → admin.php L1112–L1134 sil, require ekle
[ ] 12. blog.php        → admin.php L422–L524 sil, require ekle
[ ] 13. page_analyzer.php → admin.php L1357–L1379 sil, require ekle
[ ] 14. adres_yonetimi.php → admin.php L1314–L1356 + L1471 address + L1489 locations + L1664 api/v1/admin/address sil, require ekle
[ ] 15. ilanlarim.php   → admin.php L540–L565 sil, require ekle
```

Her commit sonrası:
```bash
php artisan route:list | grep admin | wc -l   # sayı değişmemeli
php artisan test --testsuite=Feature           # 0 failure
```

### Adım 4 — Tip B/C dosyaları (dikkatli birleştirme)

```
[ ] dashboard.php   → admin.php'deki kopyayı sil; yalihan-bekci + governance + monitoring
                      zaten alt dosyada var; require ekle
[ ] integrations.php → admin.php L375–L390 sil; ai-settings + outbound-notifications
                       alt dosyada zaten var; require ekle
[ ] intelligence.php → admin.php L128–L135 inline + L1503–L1528 market-intelligence
                       gruplarını alt dosyayla birleştir; middleware farkını çöz
[ ] ozellikler.php  → alt dosyaya middleware wrapper ekle; admin.php L964–L1046 sil
[ ] property_hub.php → admin.php L210–L316 ile karşılaştır; observability + field-suggestions
                       farkını doğrula; require ekle
[ ] property_types.php → admin.php L766–L801 + L947–L963 sil; require ekle
[ ] takim.php       → admin.php L1157–L1174 sil; site-apartman comment durumunu çöz
[ ] site.php        → admin.php L1392–L1419 + L404 resource kısmını birleştir
```

### Adım 5 — ups.php (Tip D — en dikkatli)

`ups.php` kendi middleware wrapper'ını tanımlıyor ve admin.php L1542–L1625 ile birebir aynı.
Sadece bir tanesi yaşamalı:

```
[ ] admin.php L1542–L1625 bloğunu sil
[ ] ups.php'yi require etme — zaten RouteServiceProvider tarafından yüklenmeli
    VEYA ups.php'yi require et ve admin.php bloğunu sil
[ ] Test: route:list'te ups route'ları tek kez görünmeli
```

**Not:** `ups.php` middleware wrapper'ı içerdiğinden, ana group içine require edilemez.
RouteServiceProvider'da ayrı `require __DIR__.'/../routes/admin/ups.php'` olarak yüklenmeli.

### Adım 6 — admin.php'yi incelt

Tüm modüller ayrıldıktan sonra admin.php:
```php
<?php
// Admin route loader
// ⚠️ Bu dosya middleware wrapper'ı içerir — alt dosyalar wrapper içine require edilir
Route::middleware(['web', 'auth', 'verified', 'role:admin', 'sab.write.guard'])
    ->prefix('admin')->name('admin.')->group(function () {
        require __DIR__ . '/admin/dashboard.php';
        require __DIR__ . '/admin/ilanlar.php';
        // ... diğerleri (middleware wrapper gerektirmeyen tüm modüller)
    });

// Kendi middleware'ini tanımlayan modüller — wrapper dışında
require __DIR__ . '/admin/ups.php';
```

### Adım 7 — Doğrulama

```bash
php artisan route:list | grep "^  GET\|^  POST\|^  PUT\|^  PATCH\|^  DELETE" | wc -l
php artisan route:cache
php artisan route:clear
php artisan test --testsuite=Feature
```

---

## Kabul kriterleri

- [ ] `routes/admin.php` 60 satırın altına iner (ups.php ayrı wrapper nedeniyle 50 yerine 60)
- [ ] `routes/admin/` altındaki tüm dosyalar require ile bağlanmış
- [ ] Taşıma öncesi `php artisan route:list | grep "^  " | wc -l` baseline alındı
- [ ] Taşıma sonrası route sayısı baseline ile aynı (±0)
- [ ] `php artisan route:list` çıktısında duplicate route adı yok
- [ ] Full test suite: 312+ test, 0 failure, <120s
- [ ] `php artisan route:cache` hata vermez
- [ ] Middleware muafiyetleri (sab.write.guard dışı gruplar) kasıtlı olduğu onaylandı veya düzeltildi

---

## Notlar

- `routes/admin-ai.php` ve `routes/ai-advanced.php` bu kapsamın dışında — zaten ayrı yükleniyor
- `routes/api/v1/admin.php` bu kapsamın dışında — web middleware değil, ayrı prefix
- Taşıma sırasında middleware wrapper'ı (`['web', 'auth', 'verified', 'role:admin', 'sab.write.guard']`) her alt dosyada tekrar etme; admin.php'de merkezi tut ve alt dosyalar grup içinde `require` edilsin
- `ups.php` istisnası: kendi middleware wrapper'ını içerdiğinden ana group dışında require edilmeli
- Adım 2 (middleware anomali kararı) atlanmamalı — güvenlik değişikliği riski var
