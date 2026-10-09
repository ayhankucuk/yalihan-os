# V2/ Modelleri Analiz Raporu

**Tarih:** 2026-10-09
**Konu:** Split Brain Analizi - V2/ modelleri

---

## 1. V2/ Modelleri Durumu

| Dosya | Satır | Durum |
|-------|-------|-------|
| `app/Models/V2/Ilan.php` | 148 | **AKTIF KULLANIMDA** |
| `app/Models/V2/User.php` | ~100 | **AKTIF KULLANIMDA** |
| `app/Models/V2/AiIlanTaslagi.php` | 867 | **AKTIF KULLANIMDA** |

---

## 2. Kullanim Analizi

### V2 Ilan Kullanan Dosyalar (7 adet):
- `app/Providers/AuthServiceProvider.php`
- `app/Policies/Api/V2/IlanPolicy.php`
- `app/Policies/Api/V2/DraftPolicy.php`
- `app/Http/Controllers/Api/V1/MobileSearchController.php`
- `app/Http/Controllers/Api/V1/MobileMapController.php`
- `app/Http/Controllers/Api/V1/MobileLeadController.php`
- `app/Http/Controllers/Api/V1/MobileListingController.php`

### V1 Ilan Kullanan Dosyalar:
- `database/seeders/` (DemoIlanSeeder, MiniDemoIlanSeeder, vb.)
- Observer'lar
- Admin controller'lar

---

## 3. Model Farklari

### V2/Ilan (148 satir) vs Ilan (2018 satir)

| Ozellik | V2/Ilan | Ilan |
|---------|---------|------|
| Satir sayisi | 148 | 2018 |
| Fillable | Minimal | Full |
| Accessors | **VAR** | Yok |
| Comment | "Context7 uyumlu" | - |

### V2/Ilan Accessors (7 adet):
```php
getYayinDurumuAttribute()      // yayin_durumu -> Türkçe label
getBirimFiyatAttribute()       // Hesaplanmış birim fiyat
getAlanM2Attribute()           // alan normalizasyonu
getDansismanIdAttribute()      // TYPO: "dansisman" (d an sisman)
getIlAttribute()               // Il ilişkisi
getIlceAttribute()             // Ilce ilişkisi
getMahalleAttribute()          // Mahalle ilişkisi
getOneCikanAttribute()         // one_cikan -> bool
```

### Kritik Bulgu: TYPO
V2/Ilan.php:98:
```php
public function getDansismanIdAttribute()
```
**TYPO:** `getDansisman` yerine `getDanisman` olmalı.

---

## 4. Split Brain Durumu

### Sorun: Iki Ayrı Model, Aynı Tablo
- **V2/Ilan** -> `$table = 'ilanlar'` (aynı tablo)
- **Ilan** -> `$table = 'ilanlar'` (aynı tablo)

### Sonuc:
- **Admin panel:** `App\Models\Ilan` kullanıyor
- **Mobile API:** `App\Models\V2\Ilan` kullanıyor
- **Aynı veri, farklı model**, farklı accessor'lar

---

## 5. V2 User Durumu

### V2/User vs User

| Ozellik | V2/User | User |
|---------|---------|------|
| Base class | Authenticatable | Authenticatable |
| Table | users | users |
| Traits | HasApiTokens, HasRoles | HasApiTokens, HasRoles |
| Field mapping | VAR (ad_soyad, sifre_hash) | Yok |

### V2/User Field Mapping (Accessors):
```php
ad_soyad         // name -> ad_soyad
sifre_hash       // password -> sifre_hash  
aktiflik_durumu  // user_state -> aktiflik_durumu
```

---

## 6. Database/Factories Durumu

`database/factories/V2/` dizini **YOK**.

V2 modelleri şu factory'leri kullanıyor:
```php
// V2/Ilan
return \Database\Factories\V2\IlanFactory::new();

// V2/User  
return \Database\Factories\UserFactory::new();
```

---

## 7. Sonuc ve Oneriler

### Canonicals:
- **Admin panel:** `App\Models\Ilan` (2018 satır, full)
- **Mobile API:** `App\Models\V2\Ilan` (148 satır, Context7 accessors)

### Bu Split Brain Kazara mi Yapildi?
**Hayir** — Kasıtlı bir tasarım kararı gibi görünüyor:
- V2, Context7 API için field mapping yapıyor
- V2, sadece Mobile API controller'larında kullanılıyor
- Admin, V1 model'i kullanıyor

### Oneriler:

| # | Oneri | Oncelik | Aciklama |
|---|-------|---------|----------|
| 1 | TYPO duzeltme | Yuksek | `DansismanId` -> `DanismanId` |
| 2 | V2 accessors'i V1'e tasima | Orta | Tek model, iki ayrı dosya yerine |
| 3 | Birleştirme dusunme | Dusuk | Migration maliyeti yuksek |

### Dusuk Oncelikli Not:
Bu split brain **bug degil, tasarım kararı**. Context7 uyumu icin V2 ayrı tutulmus. Birleştirme sadece migration ile mumkun.

---

## 8. Bulgu Ozeti

| Bulgu | Tur | Oncelik |
|-------|-----|---------|
| TYPO: DansismanId | Hata | Yuksek |
| Iki model, aynı tablo | Mimari | Orta |
| V2 factory dizini yok | Eksik | Dusuk |

---

## Commit Gerekli mi?
**HAYIR** — Sadece analiz raporu. Degisiklik ayrı gorev gerektirir.
