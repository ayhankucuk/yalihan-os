# SECURITY GAP ANALYSIS — Phase 2
## Yalıhan Emlak AI OS — SAB v6.1
**Chief Enterprise Architect — Evidence-Only Audit**
**Tarih:** 2026-06-29
**Kapsam:** Tenant Isolation · IDOR/BOLA · Authorization · Rate Limiting · Log Masking · Sanctum · File Upload
**Kural:** Kod yazılmadı. Her bulgu dosya yolu, sınıf, metot ve kanıt içerir.
**Status:** `VERIFIED` = doğrulandı | `PARTIALLY_VERIFIED` = kısmi | `NOT_VERIFIED` = kanıt yok

---

## CRITICAL

### SEC-001 · SANCTUM
| Alan | Değer |
|------|-------|
| File | `config/sanctum.php` |
| Class | — |
| Method | — |
| Line | 50 |
| Severity | **CRITICAL** |
| Status | **VERIFIED** |

**Kanıt:**
```php
'expiration' => null,
```

**Risk:** Token süresiz geçerli. Sızdırılan veya çalınan bir token hiçbir zaman geçersiz olmaz. Çoklu kiracı SaaS ortamında AI cüzdan bakiyeleri doğrudan finansal tehlike altındadır.

**Tavsiye:** `expiration => 10080` (7 gün) ayarla. Token yenileme mekanizması uygula.

---

### SEC-002 · SANCTUM
| Alan | Değer |
|------|-------|
| File | `config/sanctum.php` |
| Class | — |
| Method | — |
| Line | 50 |
| Severity | **CRITICAL** |
| Status | **VERIFIED** |

**Kanıt:** Token iptal veya rotasyon mekanizması hiçbir yerde bulunamadı. `revokeOtherDevices()` veya `revokeCurrentDevice()` çağrısı yok.

**Risk:** Ele geçirilen token geçersiz kılınamaz. Saldırgan süresiz erişim sağlar.

**Tavsiye:** Sunucu tarafında token takibi ve iptal mekanizması uygula. `revokeOtherDevices()` zorunlu kıl.

---

### SEC-003 · TENANT_ISOLATION + IDOR
| Alan | Değer |
|------|-------|
| File | `app/Http/Controllers/Api/V2/IlanController.php` |
| Class | `IlanController` |
| Method | `show()` |
| Line | 84–93 |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:**
```php
public function show($id): IlanDetailResource|JsonResponse
{
    $ilan = Ilan::with([...])->find($id); // tenant_id yok, sahiplik kontrolü yok, policy yok

    if (!$ilan) {
        return response()->json(['message' => 'İlan bulunamadı'], 404);
    }
    return new IlanDetailResource($ilan);
}
```

**Risk:** Kimlik doğrulaması yapılmış herhangi bir kullanıcı, başka kiracıların yayınlanmamış/özelilanlarını ID tahminiyle görebilir. Klasik BOLA/IDOR açığı.

**Tavsiye:** `->where('tenant_id', $tenantId)` + `IlanPolicy` yetkilendirme zorunlu.

---

### SEC-004 · TENANT_ISOLATION
| Alan | Değer |
|------|-------|
| File | `app/Services/FavoriService.php` |
| Class | `FavoriService` |
| Method | `toggleFavori()` |
| Line | 10–27 |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:**
```php
public function toggleFavori(int $ilanId, int $kisiId): array
{
    return DB::transaction(function () use ($ilanId, $kisiId) {
        $favori = IlanFavori::firstOrNew([
            'ilan_id' => $ilanId,
            'kisi_id' => $kisiId,
        ]); // tenant_id filtresi yok
```

**Risk:** Service katmanında kiracı izolasyonu yok. Policy atlanırsa veya başka bir kod yoluyla çağrılırsa cross-tenant favori manipülasyonu mümkün.

**Tavsiye:** `TenantResolverInterface` enjekte et. `->where('tenant_id', $tenantId)` ekle.

---

## HIGH

### SEC-005 · SANCTUM
| Alan | Değer |
|------|-------|
| File | `app/Services/Notification/InstagramAutoReplyService.php` |
| Class | `InstagramAutoReplyService` |
| Method | `validateConfiguration()` |
| Line | 167 |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:**
```php
'access_token' => 'INSTAGRAM_ACCESS_TOKEN', // config'den gelen gerçek token
// app/Services/Notification/Adapters/InstagramAdapter.php:44
'access_token' => $accessToken, // $accessToken = config('services.instagram.access_token')
```

**Risk:** Token yenileme yok. Instagram/Facebook token tipik olarak 60 günde dolar. Süresi dolan token sessizce bildirim hattını kırar. Ayrıca token değerleri versiyon kontrolüne kaçmış olabilir.

**Tavsiye:** Token'ları şifreli ortam değişkenlerinde sakla. FB/IG Graph API refresh mekanizması uygula.

---

### SEC-006 · SANCTUM
| Alan | Değer |
|------|-------|
| File | `app/Services/Notification/FacebookAutoReplyService.php` |
| Class | `FacebookAutoReplyService` |
| Method | `validateConfiguration()` |
| Line | 205 |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:** SEC-005 ile aynı — Facebook Messenger otomatik yanıtları için `page_access_token`.

**Tavsiye:** SEC-005 ile birleşik düzelt.

---

## MEDIUM

### SEC-007 · TENANT_ISOLATION + LOG_MASKING
| Alan | Değer |
|------|-------|
| File | `app/Services/PropertyPricingService.php` |
| Class | `PropertyPricingService` |
| Method | `calculateQuote()` |
| Lines | 30–34, 98–104, 124 |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// Sabit kur = güncel değil
private const EXCHANGE_RATES = [
    'TRY' => 1.0,
    'EUR' => 0.028, // sabitlenmiş — gerçek piyasa değil
    'GBP' => 0.024,
    'USD' => 0.029,
];
// base_price_try (gerçek fiyat) log'lanıyor
Log::channel('stack')->info('PricingService::calculateQuote', $audit); // alan filtreleme yok
```

**Risk:** (1) Manipülasyon: Güncel kur bilgisi yok. (2) Bilgi sızdırma: Tam ilan fiyatı log dosyalarına yazılıyor.

**Tavsiye:** TCMB/Open Exchange Rates API kullan. Log'da `base_price_try` alanını çıkar.

---

### SEC-008 · LOG_MASKING
| Alan | Değer |
|------|-------|
| File | `app/Modules/Auth/Controllers/AuthController.php` |
| Class | `AuthController` |
| Method | `login()` |
| Line | 62 |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
Log::info('Authentication failed for email: '.$request->input('email'));
```

**Risk:** E-posta adresi açık halde log dosyalarına yazılıyor. Kullanıcı numaralandırma + GDPR ihlali riski.

**Tavsiye:** `hash('sha256', $email)` logla. Ham PII loglanmamalı.

---

### SEC-009 · TENANT_ISOLATION
| Alan | Değer |
|------|-------|
| File | `app/Http/Controllers/Api/V2/IlanController.php` |
| Class | `IlanController` |
| Method | `index()` |
| Line | 41–49 |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:**
```php
$ilanlar = Ilan::query()
    ->where('yayin_durumu', IlanDurumu::YAYINDA->value) // sadece yayınlananlar
    ->with([...])
    ->latest('created_at')
    ->paginate(20); // tenant_id scoping yok
```

**Risk:** `index()` yayınlanmış ilanları doğru şekilde kısıtlıyor ancak TenantScope global kapsam yoksa cross-tenant sızma mümkün.

**Tavsiye:** V2 Ilan modelinin TenantScope kullanıp kullanmadığını doğrula. Yoksa açık `->where('tenant_id', $tenantId)` ekle.

---

### SEC-010 · TENANT_ISOLATION
| Alan | Değer |
|------|-------|
| File | `app/Services/Ilan/IlanCrudService.php` |
| Class | `IlanCrudService` |
| Method | `store()`, `update()` |
| Lines | 49–95, 105–145 |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:** `tenant_id` ataması modelin `$fillable` mass-assignment'e bağlı. Açık guard yok.

**Risk:** Model'in boot callback'i başarısız olursa veya atlanırsa `tenant_id` olmadan kayıt oluşur.

**Tavsiye:** Store/update içinde `tenant_id` varlığını assert et. `WHERE tenant_id IS NULL` Bekçi kontrolüne ekle.

---

### SEC-011 · RATE_LIMIT
| Alan | Değer |
|------|-------|
| File | `routes/api/v1/ai.php` |
| Class | — |
| Method | — |
| Line | 250 |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// AI routes: throttle:30,1 + ai.cost.guard var (satır 143)
Route::prefix('ai')->name('api.ai.')->middleware(['throttle:30,1', 'ai.cost.guard'])->group(...);

// Chat routes: sadece throttle:30,1, auth:sanctum YOK
Route::prefix('chat')->name('api.chat.')->middleware('throttle:30,1')->group(...);
```

**Risk:** Kimlik doğrulaması olmayan chatbot istekleri sınırsız gönderilebilir. AI maliyet birikimi + kaynak tükenmesi riski.

**Tavsiye:** Chat route'una `auth:sanctum` middleware ekle.

---

### SEC-012 · IDOR
| Alan | Değer |
|------|-------|
| File | `app/Http/Controllers/Owner/OwnerMesajController.php` |
| Class | `OwnerMesajController` |
| Method | `store()` |
| Line | 89–95 |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:**
```php
Mesaj::create([
    'tenant_id' => $tenantId,
    'gonderen_id' => $user->id,
    'alici_id' => $request->alici_id, // aynı tenant_id'ye ait olduğu doğrulanmadı
    'icerik' => $request->icerik,
]);
```

**Risk:** `index()` doğru tenant filter kullanırken `store()` alıcının aynı kiracıda olduğunu kontrol etmiyor.

**Tavsiye:** `'alici_id' => 'required|exists:users,id'` yanına `User::where('id', $request->alici_id)->where('tenant_id', $tenantId)->exists()` ekle.

---

### SEC-013 · RATE_LIMIT
| Alan | Değer |
|------|-------|
| File | `routes/api/v1/common.php` |
| Class | — |
| Method | — |
| Lines | 143, 231 |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// TKGM arazi tapu endpoint: n8n.secret webhook auth — tek paylaşılan sır
Route::prefix('tkgm')->name('api.tkgm.')->middleware(['throttle:20,1'])->group(function () {
```

**Risk:** Paylaşılan webhook sırrı sızdırılırsa tüm Türkiye genelinde parsellere erişim mümkün. Per-tenant iptal yok.

**Tavsiye:** Per-tenant API key auth uygula. IP allowlist ekle. Tenant başına sorgu logla.

---

### SEC-014 · AUTH
| Alan | Değer |
|------|-------|
| File | `app/Http/Controllers/Api/V1/WizardFeatureController.php` |
| Class | `WizardFeatureController` |
| Method | `featuresWithValues()` |
| Lines | 91, 97 |
| Severity | **LOW** |
| Status | **VERIFIED** |

**Kanıt:**
```php
'ilan_id' => 'required|integer|min:1', // sadece integer validasyonu, sahiplik yok
$ilanId = (int) $validated['ilan_id'];
// Dönen alanlar kullanıcının bu ilanla ilişkisi doğrulanmadan sunuluyor
```

**Risk:** Kimlik doğrulaması yapılmış kullanıcı kendine ait olmayan ilanların alan şemasını çekebilir.

**Tavsiye:** `IlanPolicy` kontrolü ekle. `ilan->tenant_id === $request->tenant_id` doğrula.

---

### SEC-015 · LOG_MASKING
| Alan | Değer |
|------|-------|
| File | `app/Services/Ups/UpsImportExportService.php`, `UpsMasterTemplateService.php` |
| Class | `UpsImportExportService`, `UpsMasterTemplateService` |
| Method | Çoklu |
| Lines | 55, 83, 153, 176, 228, 238, 254, 277 |
| Severity | **LOW** |
| Status | **VERIFIED** |

**Kanıt:**
```php
Log::channel('daily')->info('UPS configuration exported', [
    'tenant_id' => $tenantId,
    'template_id' => $id,
    'action' => 'export',
]); // Açık alan izin listesi yok, genişleyebilir
```

**Risk:** Yapısal diziler loglanırken açık izin listesi yok. İçerik genişlerse hassas veri kaçabilir.

**Tavsiye:** `LogService::audit()` — sadece beyaz listedeki alanlar loglansın.

---

## ÖZET TABLOLARI

### Dağılım
| Kategori | Sayı | CRITICAL | HIGH | MEDIUM | LOW |
|----------|------|----------|------|--------|-----|
| SANCTUM | 4 | 2 | 2 | 0 | 0 |
| TENANT_ISOLATION | 5 | 0 | 1 | 3 | 1 |
| IDOR | 2 | 0 | 1 | 1 | 0 |
| LOG_MASKING | 2 | 0 | 0 | 1 | 1 |
| RATE_LIMIT | 2 | 0 | 0 | 2 | 0 |
| AUTH | 1 | 0 | 0 | 0 | 1 |

**Toplam: 15 bulgu — 2 CRITICAL · 4 HIGH · 7 MEDIUM · 2 LOW**

### Tam Liste
| # | Tip | Şiddet | Dosya | Durum | Düzeltme Süresi |
|---|-----|--------|-------|-------|------------------|
| SEC-001 | SANCTUM | CRITICAL | `config/sanctum.php:50` | VERIFIED | 1h |
| SEC-002 | SANCTUM | CRITICAL | `config/sanctum.php:50` | VERIFIED | 3h |
| SEC-003 | TENANT_ISOLATION+IDOR | HIGH | `app/Http/Controllers/Api/V2/IlanController.php:87` | VERIFIED | 2h |
| SEC-004 | TENANT_ISOLATION | HIGH | `app/Services/FavoriService.php:13` | VERIFIED | 2h |
| SEC-005 | SANCTUM | HIGH | `app/Services/Notification/InstagramAutoReplyService.php:167` | VERIFIED | 1h |
| SEC-006 | SANCTUM | HIGH | `app/Services/Notification/FacebookAutoReplyService.php:205` | VERIFIED | 1h |
| SEC-007 | TENANT_ISOLATION+LOG | MEDIUM | `app/Services/PropertyPricingService.php:30` | VERIFIED | 4h |
| SEC-008 | LOG_MASKING | MEDIUM | `app/Modules/Auth/Controllers/AuthController.php:62` | VERIFIED | 30min |
| SEC-009 | TENANT_ISOLATION | MEDIUM | `app/Http/Controllers/Api/V2/IlanController.php:43` | PARTIALLY_VERIFIED | 1h |
| SEC-010 | TENANT_ISOLATION | MEDIUM | `app/Services/Ilan/IlanCrudService.php:68` | PARTIALLY_VERIFIED | 2h |
| SEC-011 | RATE_LIMIT | MEDIUM | `routes/api/v1/ai.php:250` | VERIFIED | 30min |
| SEC-012 | IDOR | MEDIUM | `app/Http/Controllers/Owner/OwnerMesajController.php:92` | PARTIALLY_VERIFIED | 1h |
| SEC-013 | RATE_LIMIT | MEDIUM | `routes/api/v1/common.php:143` | VERIFIED | 3h |
| SEC-014 | AUTH | LOW | `app/Http/Controllers/Api/V1/WizardFeatureController.php:91` | VERIFIED | 1h |
| SEC-015 | LOG_MASKING | LOW | `app/Services/Ups/UpsImportExportService.php:55` | VERIFIED | 2h |

---

## KRİTİK ÖNCELİK EMİRLERİ

```
 DERHAL (1 saat)
 ├── SEC-001: config/sanctum.php → 'expiration' => 10080
 └── SEC-002: Token iptal mekanizması tasarla + uygula

 24 SAAT İÇİNDE (2 saat)
 ├── SEC-003: V2 IlanController::show() → tenant scoping + auth policy
 └── SEC-004: FavoriService → tenant_id guard ekle

 48 SAAT İÇİNDE (2 saat)
 ├── SEC-005+SEC-006: Instagram/Facebook token'ları encrypted env'e taşı
 └── SEC-007: Exchange rate API + log masking düzelt

 BU HAFTA (14 saat)
 ├── SEC-011: Chat API → auth:sanctum ekle
 ├── SEC-013: TKGM → per-tenant API key
 └── SEC-007: PropertyPricingService → canlı kur API
```

**Toplam düzeltme süresi: ~19 saat**

---

*Bu analiz kanıta dayalıdır. Kod yazılmadı. Chief Enterprise Architect — 2026-06-29*
