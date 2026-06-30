# API Routes Structure - Modüler Yapı

**Versiyon:** 1.0.0  
**Tarih:** 22 Kasım 2025  
**Durum:** ✅ Aktif - Tüm IDE'ler için referans dokümantasyon

---

## 📋 İçindekiler

1. [Genel Bakış](#genel-bakış)
2. [Yapı](#yapı)
3. [Modüller](#modüller)
4. [Kullanım](#kullanım)
5. [IDE Entegrasyonu](#ide-entegrasyonu)
6. [Context7 Uyumluluk](#context7-uyumluluk)

---

## 🎯 Genel Bakış

Bu proje, API route'larını modüler bir yapıda organize eder. Tüm route'lar `routes/api/v1/` klasörü altında kategorize edilmiştir.

### Temel Prensipler

- ✅ **Modüler Yapı**: Her modül kendi dosyasında
- ✅ **Versioning**: `/api/v1/` prefix ile versiyonlama
- ✅ **Geriye Uyumluluk**: Eski route'lar korunuyor
- ✅ **Context7 Uyumlu**: Tüm standartlara uygun
- ✅ **IDE Dostu**: Tüm IDE'ler için anlaşılır yapı

---

## 📁 Yapı

```
routes/
├── api.php                    # Ana dosya - v1 route'larını include eder
├── api-admin.php              # Legacy admin routes (geriye uyumluluk)
├── api-location.php           # Legacy location routes (geriye uyumluluk)
└── api/
    └── v1/                    # API v1 Modüler Yapı
        ├── location.php        # Location API endpoints
        ├── frontend.php        # Frontend/public API endpoints
        ├── admin.php           # Admin panel API endpoints
        ├── ai.php              # AI-powered API endpoints
        └── common.php          # Common/shared API endpoints
```

---

## 📦 Modüller

### 1. Location API (`routes/api/v1/location.php`)

**Amaç:** Lokasyon verileri (İl, İlçe, Mahalle, Geocoding)

**Endpoint Prefix:** `/api/v1/location/`

**Örnek Endpoints:**
- `GET /api/v1/location/districts/{id}` - İlçeleri getir
- `GET /api/v1/location/neighborhoods/{id}` - Mahalleleri getir
- `POST /api/v1/location/geocode` - Adres → Koordinat
- `POST /api/v1/location/reverse-geocode` - Koordinat → Adres

**Middleware:** Yok (Public)

**Controller:** `App\Http\Controllers\Api\LocationController`

---

### 2. Frontend API (`routes/api/v1/frontend.php`)

**Amaç:** Frontend/public API endpoints

**Endpoint Prefix:** `/api/v1/frontend/`

**Örnek Endpoints:**
- `GET /api/v1/frontend/properties/` - Tüm ilanlar
- `GET /api/v1/frontend/properties/featured` - Öne çıkan ilanlar
- `GET /api/v1/frontend/properties/{propertyId}` - İlan detayı

**Middleware:** Yok (Public)

**Controller:** `App\Http\Controllers\Api\Frontend\PropertyFeedController`

---

### 3. Admin API (`routes/api/v1/admin.php`)

**Amaç:** Admin panel API endpoints

**Endpoint Prefix:** `/api/v1/admin/`

**Örnek Endpoints:**
- `POST /api/v1/admin/bulk/assign-category` - Toplu kategori atama
- `POST /api/v1/admin/bulk/toggle-status` - Toplu durum değiştirme
- `GET /api/v1/admin/features/category/{categoryId}` - Kategori özellikleri
- `POST /api/v1/admin/api/arsa/calculate` - Arsa hesaplama

**Middleware:** `['web', 'auth']` (Authentication Required)

**Controller'lar:**
- `App\Http\Controllers\Api\BulkOperationsController`
- `App\Http\Controllers\Admin\ArsaCalculationController`
- `App\Http\Controllers\Admin\SiteController`
- Ve diğerleri...

---

### 4. AI API (`routes/api/v1/ai.php`)

**Amaç:** AI-powered API endpoints

**Endpoint Prefix:** `/api/v1/ai/` veya `/api/v1/admin/ai/`

**Örnek Endpoints:**
- `POST /api/v1/admin/ai/analyze` - AI analiz
- `POST /api/v1/admin/ai/suggest` - AI öneri
- `POST /api/v1/admin/ai/generate` - AI içerik üretimi
- `GET /api/v1/ai/health` - AI sağlık kontrolü

**Middleware:** 
- Admin routes: `['auth']`
- Public routes: `['throttle:30,1']`

**Controller'lar:**
- `App\Http\Controllers\Api\AIController`
- `App\Http\Controllers\Api\AdminAIController`
- `App\Http\Controllers\Api\IlanAIController`

---

### 5. Common API (`routes/api/v1/common.php`)

**Amaç:** Ortak/paylaşılan API endpoints

**Endpoint Prefix:** `/api/v1/` (doğrudan)

**Kategoriler:**
- **Categories:** `/api/v1/categories/*`
- **Features:** `/api/v1/features/*`
- **Currency:** `/api/v1/currency/*`
- **Geocoding:** `/api/v1/geocoding/*`
- **QR Code:** `/api/v1/qrcode/*`
- **Search:** `/api/v1/api/search/*`
- **Webhooks:** `/api/v1/webhook/n8n/*`

**Middleware:** Modüle göre değişir (çoğu public)

---

## 🚀 Kullanım

### Yeni Route Ekleme

1. **Doğru modülü seç:**
   - Location → `routes/api/v1/location.php`
   - Frontend → `routes/api/v1/frontend.php`
   - Admin → `routes/api/v1/admin.php`
   - AI → `routes/api/v1/ai.php`
   - Diğer → `routes/api/v1/common.php`

2. **Route ekle:**
```php
Route::prefix('your-prefix')->name('api.your-module.')->group(function () {
    Route::get('/endpoint', [YourController::class, 'method'])->name('endpoint');
});
```

3. **Controller import et:**
```php
use App\Http\Controllers\Api\YourController;
```

### Response Format

**ZORUNLU:** Tüm API endpoint'leri `ResponseService` kullanmalı:

```php
use App\Services\Response\ResponseService;

// Başarılı
return ResponseService::success($data, 'Mesaj');

// Hata
return ResponseService::error('Hata mesajı', 400);
```

---

## 🤖 IDE Entegrasyonu

### Warp Terminal

Warp, route dosyalarını otomatik olarak tanır. Route'ları görmek için:

```bash
php artisan route:list --path=api/v1
```

### Trea AI

Trea AI, bu README dosyasını ve `authority.json`'ı referans alır. Yeni route eklerken:

1. Doğru modül dosyasını seç
2. Context7 standartlarına uy
3. `ResponseService` kullan

### GitHub Copilot

Copilot, dosya yapısını ve mevcut pattern'leri öğrenir. Öneriler:

- Mevcut route pattern'lerini takip et
- Controller namespace'lerini doğru kullan
- Middleware'leri belirt

### Google Antigravity (Gemini)

Antigravity, `authority.json`'daki API yapısı bilgilerini kullanır. Yeni endpoint eklerken:

1. `authority.json`'daki `api_structure_2025_11_22` bölümünü kontrol et
2. Modül yapısına uy
3. Response format'ını takip et

---

## ✅ SAB Uyumluluk

### Zorunlu Standartlar

1. **Route Naming:**
   - ✅ `api.{module}.{action}` formatı
   - ❌ `crm.*` prefix (YASAK)
   - ❌ Double prefix (YASAK)

2. **Response Format:**
   - ✅ `ResponseService::success()` / `error()`
   - ❌ Direkt `response()->json()`

3. **Field Naming:**
   - ✅ `status` (NOT `status`, `aktif`, `durum`)
   - ✅ `display_order` (NOT `order`)
   - ✅ `il_id`, `mahalle_id` (NOT `sehir_id`, `semt_id`)

4. **Middleware:**
   - Admin routes: `['web', 'auth']`
   - Public routes: Rate limiting ile

### Referans Dosyalar

- `.sab.authority.json` - Ana standartlar
- `.context7/FORBIDDEN_PATTERNS.md` - Yasak pattern'ler
- `app/Services/Response/ResponseService.php` - Response standardı

---

## 📝 Örnekler

### Örnek 1: Yeni Location Endpoint

```php
// routes/api/v1/location.php
use App\Http\Controllers\Api\LocationController;

Route::prefix('location')->name('api.location.')->group(function () {
    Route::get('/cities/{countryId}', [LocationController::class, 'getCitiesByCountry'])
        ->name('cities');
});
```

### Örnek 2: Yeni Admin Endpoint

```php
// routes/api/v1/admin.php
use App\Http\Controllers\Admin\YourController;

Route::prefix('admin')->name('api.admin.')->middleware(['web', 'auth'])->group(function () {
    Route::post('/your-action', [YourController::class, 'method'])
        ->name('your-action');
});
```

### Örnek 3: Response Service Kullanımı

```php
use App\Services\Response\ResponseService;

public function yourMethod(Request $request)
{
    try {
        $data = // ... işlemler
        
        return ResponseService::success($data, 'İşlem başarılı');
    } catch (\Exception $e) {
        return ResponseService::error('Hata: ' . $e->getMessage(), 500);
    }
}
```

---

## 🔍 Debugging

### Route'ları Listele

```bash
# Tüm v1 route'ları
php artisan route:list --path=api/v1

# Belirli modül
php artisan route:list --path=api/v1/location

# Route cache temizle
php artisan route:clear
```

### Route Test

```bash
# Health check
curl http://localhost:8002/api/v1/common/health

# Location test
curl http://localhost:8002/api/v1/location/districts/34
```

---

## 📚 Referanslar

- **Context7 Authority:** `.sab.authority.json`
- **Response Service:** `app/Services/Response/ResponseService.php`
- **Route Standards:** `.context7/standards/ROUTE_NAMING_STANDARD.md`

---

## 🎯 Sonraki Adımlar

1. ✅ Modüler yapı oluşturuldu
2. ✅ SAB authority.json güncellendi
3. ⏳ Legacy route'ları temizle
4. ⏳ Route testleri yaz
5. ⏳ API dokümantasyonu oluştur

---

**Son Güncelleme:** 22 Kasım 2025  
**Versiyon:** 1.0.0  
**Durum:** ✅ Aktif

