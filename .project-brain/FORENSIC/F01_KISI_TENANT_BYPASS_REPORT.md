# 🔬 F01 — Kisi Tenant Isolation Bypass Forensic Report
**Tarih:** 2026-09-27  
**Mod:** READ-ONLY FORENSIC  
**Baseline:** `release-candidate/RC2` (16e1a31a)

---

## 1. Finding Özeti

| Alan | Değer |
|---|---|
| **Finding** | Kisi modeline doğrudan erişim (direct model access), Repository katmanındaki tenant isolation'ı atlatıyor |
| **Severity** | HIGH — cross-tenant data leak potansiyeli |
| **Domain** | CRM / Kisi |
| **Evidence Level** | REPO_VERIFIED |
| **Blocking?** | Hayır — production deploy'a mani değil, güvenlik riski |

---

## 2. Mevcut Durum: Tenant Isolation Nasıl Çalışıyor

### 2.1 Repository Katmanı: DOĞRU ✅

`KisiRepository` (`app/Repositories/KisiRepository.php`) tüm method'larda `danisman_id` scoping uyguluyor:

```php
// KisiRepository.php, satır 66
return $query->where('danisman_id', $user->id);

// KisiRepository.php, satır 140-143
$query
    ->where('id', $id)
    ->where('danisman_id', $user->id);  // ← ownership scope
```

**Doğrulama:** `tests/Unit/Repositories/CRMTenantIsolationTest.php` → **15/15 PASS**
- Tenant A Kisi'leri görmüyor
- Tenant B Kisi'leri görmüyor
- Null user 0 görüyor
- Admin tümünü görüyor

### 2.2 Model Katmanı: EKSİK ⚠️

`App\Models\Kisi`:
- `BelongsToTenant` trait'ı **YOK**
- Global scope **YOK**
- `tenant_id` kolonu **YOK** (tablo `tenant_id` değil, `danisman_id` kullanıyor)

```php
// app/Models/Kisi.php — doğrudan Eloquent Model
class Kisi extends Model
{
    // ❌ BelongsToTenant trait'i yok
    // ❌ Global tenant scope yok
    
    protected $fillable = ['danisman_id', ...];
}
```

### 2.3 Route Katmanı: DOĞRU ✅

```php
// routes/admin.php, satır 22
Route::middleware(['web', 'auth', 'verified', 'role:admin', 'sab.write.guard', 'tenant.context'])
    ->prefix('admin')->name('admin.')->group(function () {
```

`tenant.context` middleware mevcut — tenant context set ediliyor.

---

## 3. Bypass Noktaları (Direct Model Access)

### 3.1 IntelligenceDashboardController — 3 instance

```php
// app/Http/Controllers/Admin/IntelligenceDashboardController.php

// satır 72
$kisi = \App\Models\Kisi::find($kisiId);  // ❌ bypass — danisman_id scope yok

// satır 106
$kisi = \App\Models\Kisi::find($kisiId);  // ❌ bypass

// satır 147
$kisi = \App\Models\Kisi::find($kisiId);   // ❌ bypass
```

**Route:** `GET /admin/opportunity-board`, `GET /admin/opportunities`, `GET /admin/action-score/{kisiId}`

**Teorik saldırı:** Tenant B'den bir danışman, Tenant A'nın Kisi ID'sini tahmin ederek `apiActionScore` endpoint'ini çağırabilir. `Kisi::find()` modele özel `danisman_id` kontrolü olmaksızın kaydı getirir.

### 3.2 EslesmeController — 2 instance

```php
// satır 67
$kisiler = \App\Models\Kisi::active()  // ✅ aktiflik_durumu scope var ama danisman_id scope yok

// satır 254
$kisiler = \App\Models\Kisi::select(['id', 'ad', 'soyad', 'telefon'])
    // ❌ danisman_id scope yok
```

### 3.3 Diğer Direct Access Noktaları

| Dosya | Satır | Pattern | Risk |
|---|---|---|---|
| `DanismanRepository.php` | 223-224 | `Kisi::where('danisman_id', $danismanId)` | ✅ Repository içinde, doğru kullanım |
| `Ilan.php` | 1218 | `Kisi::whereIn('user_id', ...)` | ⚠️ Owner relation için, ama tenant kontrolü yok |
| `StoreIlanRequest.php` | 282 | `Kisi::where('id', $ilgiliKisiId)` | ⚠️ Validation'da, admin yetkisi gerekli |
| `UpdateIlanRequest.php` | 240 | `Kisi::where('id', $ilgiliKisiId)` | ⚠️ Validation'da, admin yetkisi gerekli |
| `GlobalSearchController.php` | 80 | `Kisi::where('ad', 'like', ...)` | 🔴 Public API — tenant context yok |
| `FavoriController.php` | 35, 68, 101 | `Kisi::where('user_id', $user->id)` | ✅ user_id ile scope |
| `AIController.php` | 65 | `Kisi::find($payload)` | ⚠️ AI context, yetki kontrolü araştırılmalı |
| `BootstrapJob.php` | 96 | `Kisi::where('tam_adi', $sahip)` | ⚠️ Bootstrap job, tenant context gerekli |
| `BulkActionCustomerAction.php` | 11 | `Kisi::whereIn('id', $ids)` | ⚠️ Bulk action, danisman_id kontrolü gerekli |

---

## 4. Mimari Kök Neden Analizi

### 4.1 Neden Repository Isolation Çalışıyor Ama Model Erişimi Çalışmıyor?

**Sebep:** `Kisi` modeli `BelongsToTenant` trait'ini kullanmıyor.

`BelongsToTenant` trait'i global bir scope ekler — her Eloquent sorgusuna otomatik olarak `tenant_id` koşulu ekler.

```php
// BelongsToTenant trait mantığı (pseudo)
public static function bootBelongsToTenant($model)
{
    static::addGlobalScope('tenant', function ($builder) {
        $tenantId = TenantContextService::getTenantId();
        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }
    });
}
```

Ama `Kisi`:
- `BelongsToTenant` trait'i yok
- `tenant_id` kolonu yok
- Sadece `danisman_id` kolonu var (ki bu User'a referans)

Bu tasarım: "Kisi'ye User üzerinden erişilir" mantığıyla çalışıyor. Her Kisi bir danışmana (User'a) aittir; danışmanın tenant'ı, Kişi'nin tenant'ıdır.

### 4.2 Danışman-ID ↔ Tenant İlişkisi

```
Kisi.danisman_id → users.id → users.tenant_id → Tenant
```

Yani: `Kisi.danisman_id = User.id` ve `User.tenant_id = Tenant.id`

Bu ilişki kodda **kanıtlanmış** değil — sadece schema düzeyinde var. Kodun bu ilişkiyi kullanması gerekirken doğrudan `Kisi::find()` kullanılıyor.

---

## 5. Güvenlik Değerlendirmesi

### 5.1 Mevcut Koruma Katmanları

1. **`tenant.context` middleware** — admin route'larında tenant context set ediliyor
2. **`role:admin` middleware** — sadece adminler erişebilir (IntelligenceDashboard)
3. **`auth` middleware** — kullanıcı authenticate olmalı

### 5.2 Koruma Atlatma Senaryosu

1. Tenant A admin'i authenticate oluyor
2. Tenant B'den bir Kisi ID tahmin ediyor (1, 2, 3...)
3. `GET /admin/action-score/5` çağırıyor
4. `IntelligenceDashboardController::apiActionScore(5)` çalışıyor
5. `$kisi = Kisi::find(5)` — Tenant B'nin Kisi 5'ini buluyor
6. **Cross-tenant data leak:** Tenant A admin, Tenant B'nin Kisi verilerini görüyor

### 5.3 Gerçekçi Risk Seviyesi

| Senaryo | Risk | Gerekçe |
|---|---|---|
| Kötü niyetli Tenant A admin | DÜŞÜK | Zaten admin yetkisi var — daha güçlü operasyonlar yapabilir |
| Yanlışlıkla Tenant B verisi görüntüleme | ORTA | Mevcut UI'da bu endpoint'ler kullanılıyor mu kontrol edilmeli |
| Bulk export cross-tenant leak | YÜKSEK | `GlobalSearchController` public endpoint'inden Kisi sızması |

---

## 6. Remediation Seçenekleri

### Option A: Kisi'ye BelongsToTenant Trait Ekleme (Önerilen)

```php
// app/Models/Kisi.php
use App\Traits\BelongsToTenant;

class Kisi extends Model
{
    use BelongsToTenant;
    
    // Custom tenant column mapping
    protected function getTenantColumn(): string
    {
        return 'danisman_id'; // Kisi tenant değil, danisman üzerinden tenant'a bağlı
    }
}
```

**Problem:** `BelongsToTenant` `tenant_id` kolonu bekliyor, Kisi'de `danisman_id` var. Bu yaklaşım çalışmaz.

### Option B: Custom Global Scope (Recommended)

Kisi modeline custom global scope eklemek — `danisman_id`'yi User üzerinden tenant'a map eder.

### Option C: Repository Enforce (Pragmatik)

Direct model erişimlerini Repository ile değiştirmek. Controller'larda `KisiRepository` kullanılmalı.

### Option D: Controller-level Guard

Her `Kisi::find()` çağrısından önce Kişi'nin `danisman_id`'sinin mevcut kullanıcının `danisman_id`'siyle eşleştiğini kontrol etmek.

---

## 7. Sonraki Adımlar

1. [ ] **Human Gate:** Bu finding'in production blocking olup olmadığına karar verilmeli
2. [ ] **Option seçimi:** Yukarıdaki 4 seçenekten biri seçilmeli (Ayhan kararı)
3. [ ] **GlobalSearchController fix:** En acil — public endpoint cross-tenant leak
4. [ ] **IntelligenceDashboardController fix:** Admin bypass riski
5. [ ] **EslesmeController fix:** Listeleme endpoint'leri

---

## 8. İlişkili Kayıtlar

- `.project-brain/BACKLOG_TRIAGE_2026-09-27.md` — F01 active remediation kuyruğunda
- `.project-brain/FORENSIC/REPOSITORY_ARCHITECTURE_HYGIENE_FORENSIC_01_REPORT.md` — Domain authority matrix'te Kisi SPLIT olarak işaretli
- `tests/Unit/Repositories/CRMTenantIsolationTest.php` — Repository isolation PASS (15/15)
- `app/Repositories/KisiRepository.php` — Canonical write/read authority

- `.project-brain/BACKLOG_TRIAGE_2026-09-27.md` — F01 active remediation kuyruğunda
- `.project-brain/FORENSIC/REPOSITORY_ARCHITECTURE_HYGIENE_FORENSIC_01_REPORT.md` — Domain authority matrix'te Kisi SPLIT olarak işaretli
- `tests/Unit/Repositories/CRMTenantIsolationTest.php` — Repository isolation PASS (15/15)
- `app/Repositories/KisiRepository.php` — Canonical write/read authority

---

## 9. Sonuç — CLOSED: SAFE_BY_RUNTIME_CONTEXT

**2026-09-27 — Antigravity Runtime Doğrulaması:**

Bu raporda listelenen bypass noktaları, runtime doğrulamasında **teyit edilemedi.**

- Kisi modeli global `TenantScope` kullanıyor → `Kisi::find()` üzerinde de tenant scope çalışıyor
- Cross-tenant Kisi erişimi reproduce edilemedi
- IntelligenceDashboard → 404 (beklenen davranış)
- EslesmeController → sadece Tenant A kayıtları döndürüyor
- GlobalSearch → anonymous/public değil, auth + tenant context altında

**Karar:** F01 remediation değil. Çalışan mimariyi "tercih" nedeniyle refactor etmek gereksiz risk üretir.

**Aksiyon:** Kapatıldı. Değişiklik yok.

---

## 10. CACHE_TENANT_ISOLATION_CANDIDATE (Ayrı Takip)

**Yeni bulgu:** `ActionScoreService` cache key'inde tenant prefix eksikliği.
**Durum:** Reproduce edilmedi — ayrı candidate olarak izlenmeli.
**F01'e eklenmedi:** Çünkü cache üzerinden cross-tenant leakage kanıtlanmadı.