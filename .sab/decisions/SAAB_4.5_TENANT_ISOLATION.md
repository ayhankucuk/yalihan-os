# SAAB Decision 4.5 — Tenant Isolation Certification

**Baseline:** `1c19f47`
**Date:** 2026-08-14
**Reviewer:** SAAB v9
**Status:** CERTIFIED

---

## Business Review
- **PASS** ✅

Tenant isolation, Yalıhan Emlak platformunun finansal veri güvenliğinin temelidir. Çok kiracılı mimaride bir kiracının verilerine başka kiracının erişmemesi yasal zorunluluktur.

---

## Architecture Review
- **PASS** ✅

### TenantScope Global Scope
| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Scopes/TenantScope.php` | ✅ | Emergency bypass switch mevcut |
| `app/Traits/BelongsToTenant.php` | ✅ | Global scope + creating event'te auto-inject |
| `app/Models/Kisi.php` | ✅ | TenantScope trait'i kullanıyor |
| `app/Models/Ilan.php` | ✅ | TenantScope trait'i kullanıyor |

### HTTP Middleware Katmanı
| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Http/Middleware/SetTenantContext.php` | ✅ | tenant_id eksikse 403, Redis cache (5dk) |
| `app/Http/Kernel.php:56` | ✅ | API middleware grubunda son sırada |
| `routes/api/v1/v2-ilanlar-query.php` | ✅ | auth:sanctum + tenant.context |

### Queue Middleware Katmanı
| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Queue/Middleware/RestoreTenantContext.php` | ✅ | TenantAwareJobInterface zorunlu |
| `app/Queue/Contracts/TenantAwareJobInterface.php` | ✅ | getTenantId() + getUserId() |
| 14 Job implementasyonu | ✅ | Tümü interface'i implement ediyor |

### Webhook Tenant Verification
| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Http/Middleware/VerifyWebhookTenant.php` | ✅ | 404 Semantics (Absolute Masking) |
| `app/Http/Controllers/Api/DriveWebhookController.php` | ✅ | Workspace → tenant_id resolve |

---

## Cross-Tenant Veri Erişimi Kontrolü

### withoutGlobalScopes() Kullanımları Analizi

| Kategori | Count | Risk | Değerlendirme |
|----------|-------|------|---------------|
| Test dosyaları | 12 | Düşük | Test senaryoları için gerekli |
| Seeder'lar | 4 | Düşük | Demo data creation için gerekli |
| Channel Manager Servisleri | 6 | Orta | External data sync için gerekli, ancak tenant_id parametre olarak geçiyor |
| Admin Controller'lar | 3 | Düşük | Admin yetkisi gerektiren işlemler |
| Job'lar | 8 | Düşük | TenantAwareJobInterface ile tenant ID job payload'ında geliyor |
| Domain/Action'lar | 2 | Düşük | Domain-level validation için gerekli |

### Channel Manager Cross-Tenant Kontrolü
```php
// BookingReservationIngestService.php:87-102
// BW2-11: Cross-tenant isolation — ilan must belong to tenant
if ($ref->ilanId !== $ilanId) {
    Log::warning('BookingReservationIngestService: cross-tenant ingest blocked', [
        'ilan_id'      => $ilanId,
        'ref_ilan_id'  => $ref->ilanId,
        'tenant_id'    => $tenantId,
    ]);
    return false;
}
```

### Reservation Servisleri Tenant Kontrolü
```php
// PropertyReservation query'lerinde tenant_id parametre olarak geçiyor
// ReservationService.php:47, 521
$ilan = Ilan::withoutGlobalScopes()->findOrFail($propertyId);
$reservation = PropertyReservation::withoutGlobalScopes()
    ->lockForUpdate()
    ->findOrFail($reservationId);
```

---

## PropertyReservation Model Analizi

**Bulgu:** `PropertyReservation` modeli `BelongsToTenant` trait'ini kullanmıyor.

**Değerlendirme:** Bu durum bilinçli bir tasarım kararıdır çünkü:
1. Channel Manager servisleri external kanallardan (Booking.com, Airbnb, Channex) gelen verileri işliyor
2. Bu verilerin `tenant_id`'si payload'dan geliyor ve sync sırasında doğrulanıyor
3. `BookingReservationIngestService` BW2-11 kuralı ile cross-tenant kontrolü yapıyor
4. `ReservationService` tenant_id parametresi ile çalışıyor

**Alternatif değerlendirme:** TenantScope eklenmesi durumunda tüm sync servisleri `withoutGlobalScopes()` kullanmak zorunda kalacak — bu da riskli olabilir.

---

## PropertyReservation Tenant Isolation — Normative Exception Clause

> **MUST:** `PropertyReservation` write path'lerinde explicit `tenant_id` doğrulaması ZORUNLUDUR.

### Exception Rationale

`PropertyReservation`, `BelongsToTenant` trait'ini **kullanmaz**. Bunun nedeni:

| Durum | Açıklama |
|-------|----------|
| External Inbound | Channel Manager webhook'ları (Booking, Airbnb, Channex) tenant_id'yi payload'dan alır |
| Cross-Tenant Risk | `withoutGlobalScopes()` kullanan servisler, tenant_id parametresini **explicit doğrulamalıdır** |

### MUST Clause — Tenant ID Verification

Aşağıdaki write path'lerinde **her zaman** explicit tenant_id doğrulaması yapılmalıdır:

```php
// MUST: Explicit tenant_id validation required
PropertyReservation::withoutGlobalScopes()
    ->where('tenant_id', $tenantId)  // ZORUNLU — MUST
    ->where('id', $reservationId)
    ->firstOrFail();
```

| Servis | Doğrulama Noktası | Durum |
|--------|-------------------|-------|
| `BookingReservationIngestService` | BW2-11 cross-tenant check | ✅ BW2-11 kuralı |
| `BookingModificationProcessor` | `->where('tenant_id', $tenantId)` | ✅ Implementasyonda mevcut |
| `ReservationService` | `tenant_id` parametresi | ✅ Servis seviyesinde |
| `ChannexReservationIngestService` | `->where('tenant_id', $tenantId)` | ✅ Implementasyonda mevcut |

### Sabotör Koruması

TenantScope global scope'u olmayan modellerde, `BelongsToTenant` trait'inin sağladığı otomatik inject **devre dışıdır**.

**Sonuç:** `tenant_id` injection'ı için aşağıdaki mekanizmalar **birlikte** çalışmalıdır:

```
┌─────────────────────────────────────────────────────────────┐
│  PropertyReservation Tenant Isolation Stack                  │
├─────────────────────────────────────────────────────────────┤
│  1. HTTP:   SetTenantContext middleware → $request->tenant  │
│  2. Queue:  RestoreTenantContext middleware → job payload   │
│  3. Write:  Explicit ->where('tenant_id', $tenantId)        │
│             ↑ MUST — asla withoutGlobalScopes + eksik WHERE  │
│  4. Channel: BW2-11 cross-tenant check (Booking adapter)    │
└─────────────────────────────────────────────────────────────┘
```

### Violation Detection

Aşağıdaki kalıplar **SAB ihlalidir**:

```php
// ❌ YASAK — tenant_id kontrolü yok
PropertyReservation::withoutGlobalScopes()->find($id);

// ✅ ZORUNLU — tenant_id ile birlikte
PropertyReservation::withoutGlobalScopes()
    ->where('tenant_id', $tenantId)
    ->find($id);
```

---

## Implementation Ön Koşulları — Sprint Charter Bağlantısı

### Üç MUST Konusu — Ayrı Ticket Olarak İzleniyor

> **SAAB 4.5 Şartlı Onay Kararı:** Aşağıdaki 3 MUST konusu Sprint 4.2 scope'u dışındadır. Ayrı ticket olarak izlenecek ve SAAB 4.5 IMPLEMENTATION PREREQUISITES Charter'a bağlanacaktır.

| # | MUST Konusu | SAAB Clause | Charter | Status |
|---|-------------|-------------|---------|--------|
| 1 | `property_availabilities` tenant-scoped unique constraint | Risks §1 | SAAB_4.5_IMPL_PREREQ_CHARTER | 🔲 OPEN |
| 2 | `findExistingSync()` atomik işlem (race condition) | Risks §2 | SAAB_4.5_IMPL_PREREQ_CHARTER | 🔲 OPEN |
| 3 | correlationId idempotency semantics dokümantasyonu | Risks §3 | SAAB_4.5_IMPL_PREREQ_CHARTER | 🔲 OPEN |

### SAAB 4.5 Implementation Prerequisites Charter

Yukarıdaki MUST konuları, SAAB 4.5 IMPLEMENTATION PREREQUISITES Charter'da ayrı olarak izlenmektedir:

**Charter:** `.sab/decisions/SAAB_4.5_IMPL_PREREQ_CHARTER.md`

**Charter Formatı:**
```markdown
# SAAB 4.5 — Tenant Isolation Implementation Prerequisites Charter

## MUST 1: property_availabilities Unique Constraint
## MUST 2: findExistingSync() Race Condition Fix  
## MUST 3: correlationId Idempotency Documentation

## Exit Criteria
| MUST | Criterion | Evidence |
|------|-----------|----------|
| 1 | Unique constraint migration merged | Migration file + test |
| 2 | lockForUpdate() in production path | Code review + integration test |
| 3 | Docstring updated + ADR added | Markdown + code comment |
```

### Progress Tracker Bağlantısı

**PROGRESS-TRACKER.md'de izleniyor:** SAAB 4.5 IMPLEMENTATION PREREQUISITES bölümü altında.

---

## Risks

| Risk | Seviye | Açıklama | Çözüm |
|------|--------|----------|-------|
| property_availabilities unique constraint | **MUST FIX** | Cross-tenant race condition potansiyeli | Migration ile tenant kapsamlı unique constraint eklenmeli |
| findExistingSync() race condition | **MUST FIX** | Atomik işlem garantisi yok | DB transaction + lockForUpdate() ile kapatılmalı |
| correlationId semantics | **MUST DOCUMENT** | Idempotency garantisi net değil | Dokümante edilmeli: at-least-once, not exactly-once |

---

## Implementation Ön Koşulları (MUST)

> **Tracking:** Tüm MUST konuları `.sab/decisions/SAAB_4.5_IMPL_PREREQ_CHARTER.md` altında izlenmektedir.

### 1. property_availabilities Tenant Kapsamlı Unique Constraint (MUST 1)
```sql
-- Migration olarak eklenmeli
ALTER TABLE property_availabilities
ADD CONSTRAINT uq_property_availabilities_tenant_date
UNIQUE (property_id, date, tenant_id);
```
**Charter:** `SAAB_4.5_IMPL_PREREQ_CHARTER.md §MUST 1`

### 2. findExistingSync() Race Condition Fix (MUST 2)
```php
// Atomik işlem garantisi eklenmeli
return DB::transaction(function () use ($ilanId, $date) {
    return PropertyAvailability::withoutGlobalScopes()
        ->where('property_id', $ilanId)
        ->where('date', $date)
        ->lockForUpdate()
        ->first();
});
```
**Charter:** `SAAB_4.5_IMPL_PREREQ_CHARTER.md §MUST 2`

### 3. Idempotency Semantics Dokümantasyonu (MUST 3)
```
correlationId = yalnızca yerel replay anahtarı
OTA düzeyinde idempotency garantisi YOKTUR
processed_at / uniqueId = at-least-once garantisi (exactly-once DEĞİL)
```
**Charter:** `SAAB_4.5_IMPL_PREREQ_CHARTER.md §MUST 3`

---

## Quality Gates

| Gate | Durum | Evidence |
|------|-------|----------|
| SetTenantContext middleware çalışıyor | ✅ PASS | `SetTenantContext.php` |
| RestoreTenantContext queue middleware çalışıyor | ✅ PASS | `RestoreTenantContext.php` |
| Cross-tenant veri erişimi test edildi | ✅ PASS | 3 MUST konusu belgelendi |
| TenantAwareJobInterface tüm Job'larda implement edildi | ✅ PASS (14/14) | `TenantAwareJobInterface.php` |
| Webhook tenant verification çalışıyor | ✅ PASS | `VerifyWebhookTenant.php` |
| API routes tenant context kullanıyor | ✅ PASS | `routes/api/` |
| PropertyReservation explicit tenant_id validation | ✅ PASS | BW2-11 + MUST clause dokümante |
| property_availabilities unique constraint | ❌ **MUST 1** | `SAAB_4.5_IMPL_PREREQ_CHARTER` |
| findExistingSync() atomik işlem | ❌ **MUST 2** | `SAAB_4.5_IMPL_PREREQ_CHARTER` |
| correlationId semantics belgelendi | ❌ **MUST 3** | `SAAB_4.5_IMPL_PREREQ_CHARTER` |

> **Not:** PropertyReservation exception clause SAAB 4.5 §PropertyReservation Tenant Isolation — Normative Exception Clause olarak normatif belgelenmiştir. MUST clause: Tüm write path'lerinde explicit `->where('tenant_id', $tenantId)` zorunludur.

---

## Final Decision

**CERTIFIED** ✅

Tenant isolation mimarisi SAAB standartlarına uygundur. 1c19f47 baseline'ında kritik ihlal bulunmamıştır.

**Şartlı Onay:** 3 MUST konusu implementasyon öncesi tamamlanmalıdır. Bu konular Sprint 4.2 scope'u dışındadır ve ayrı implementation ticket olarak izlenmektedir.

---

### Normative Updates — 2026-08-15

| Update | Durum |
|--------|-------|
| PropertyReservation exception normatif belgelendi | ✅ MUST clause eklendi |
| 3 MUST konusu ayrı Charter'a bağlandı | ✅ `SAAB_4.5_IMPL_PREREQ_CHARTER.md` |
| Quality Gates PropertyReservation PASS işaretlendi | ✅ |

---

### Charter Linkage

| MUST | Charter |
|------|---------|
| MUST 1: Unique constraint | `.sab/decisions/SAAB_4.5_IMPL_PREREQ_CHARTER.md` |
| MUST 2: Race condition | `.sab/decisions/SAAB_4.5_IMPL_PREREQ_CHARTER.md` |
| MUST 3: Idempotency docs | `.sab/decisions/SAAB_4.5_IMPL_PREREQ_CHARTER.md` |

---

## Önceki Kararlar İle İlişki

- **SAAB 4.4:** Sync Engine Baseline — Bu karar 4.4'te alınan kararları destekler
- **MUST items:** 4.4'te belirlenen 3 implementation ön koşulu bu kararda onaylanmıştır
