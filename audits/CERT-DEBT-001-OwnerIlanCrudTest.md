# Certification Debt Register — CERT-DEBT-001

**Register Tarihi:** 2026-08-05  
**Kapanış Tarihi:** 2026-08-05  
**Oluşturan:** ADR-001 Phase 1C kapanış süreci  
**Dosya:** `tests/Feature/Owner/OwnerIlanCrudTest.php`  
**Durum:** ✅ CLOSED

---

## Özet

`OwnerIlanCrudTest` içindeki 3 test (CDT-001, CDT-002, CDT-003), `OwnerIlanController::store()` ve `update()` metodlarındaki eksik implementasyondan kaynaklanıyordu. Tüm hatalar giderildi ve suite 15/15 PASS ile kapatıldı.

---

## Çözüm Özeti

### CDT-001 — `owner_can_store_new_ilan_as_taslak` ✅ RESOLVED

| Alan | Değer |
|------|-------|
| Kök Neden | `OwnerIlanController::store()` `user_id`'yi set etmiyordu; `IlanCrudService::store()` null `user_id` ile DB constraint hatasına çöküyordu |
| Çözüm | `IlanCrudService::mapCoreData()` içine `user_id` → `auth()->id()` override eklendi |
| Doğrulama | `owner_can_store_new_ilan_as_taslak` ✅ PASS |

### CDT-002 — `store_always_assigns_authenticated_user_as_owner` ✅ RESOLVED

| Alan | Değer |
|------|-------|
| Kök Neden | `store()` `user_id`'yi form payload'ına bırakıyordu; Owner Portal formunda bu alan yok → `null` kaydediliyordu |
| Çözüm | `mapCoreData()` `user_id`'yi her koşulda `auth()->id()` ile override eder (payload'dan gelen değer geçersiz sayılır) |
| Doğrulama | `store_always_assigns_authenticated_user_as_owner` ✅ PASS |

### CDT-003 — `owner_can_update_own_ilan` ✅ RESOLVED

| Alan | Değer |
|------|-------|
| Kök Neden | `OwnerIlanController`'da `edit()`, `update()`, `destroy()`, `readiness()` metodları yoktu |
| Çözüm | Tüm eksik metodlar eklendi; `UpdateOwnerIlanRequest` yazıldı; `IlanPolicy::update()` `danisman_id` → `user_id` olarak düzeltildi |
| Doğrulama | `owner_can_update_own_ilan` ✅ PASS |

---

## Test Kanıtı

```
PASS  Tests\Feature\Owner\OwnerIlanCrudTest
  ✓ owner can list own ilanlar
  ✓ guest cannot access owner ilanlar
  ✓ owner can view own ilan
  ✓ owner cannot view other owners ilan
  ✓ owner can access create form
  ✓ owner can store new ilan as taslak          ← CDT-001 ✅
  ✓ store rejects invalid payload
  ✓ store always assigns authenticated user as owner  ← CDT-002 ✅
  ✓ owner can access edit form for own ilan
  ✓ owner cannot access edit form of other owners ilan
  ✓ owner can update own ilan                   ← CDT-003 ✅
  ✓ owner cannot update other owners ilan
  ✓ update cannot change yayin durumu
  ✓ owner can delete own ilan
  ✓ owner cannot delete other owners ilan

  Tests:    15 passed (28 assertions)
  Duration: 1.24s
```

---

## Regresyon Sonucu

| Suite | Sonuç |
|-------|-------|
| OwnerIlanCrudTest | ✅ 15/15 PASS |
| Toplam CI | ✅ 44/44 PASS |
| Toplam assertion | ✅ 144 |

---

## Değişen Dosyalar

| Dosya | Değişiklik |
|-------|------------|
| `app/Http/Controllers/Owner/OwnerIlanController.php` | `edit()`, `update()`, `destroy()`, `readiness()` eklendi |
| `app/Http/Requests/Owner/UpdateOwnerIlanRequest.php` | `failedAuthorization()` → 404 |
| `app/Policies/IlanPolicy.php` | `update()` ownership: `danisman_id` → `user_id` |
| `app/Services/Ilan/IlanCrudService.php` | `mapCoreData()` `user_id` auth override |
| `app/Models/Ilan.php` | İlişki ve cast düzeltmeleri |
| `app/Services/CacheManager.php` | Cache key düzeltmesi |
| `app/Jobs/SyncListingProjectionJob.php` | Projection job düzeltmesi |
| `app/Console/Commands/RebuildCqrsProjections.php` | CQRS rebuild command düzeltmesi |
| `resources/views/owner/ilanlar/show.blade.php` | `yayin_durumu` label fix |
| `tests/Feature/Owner/OwnerIlanValuationTest.php` | Test fixture düzeltmesi |
| `tests/Feature/CQRS/SyncListingProjectionOwnershipTest.php` | Yeni ownership projection testi |
| `database/migrations/2026_08_05_000000_add_soft_deletes_to_yazlik_details.php` | **CERT-DEBT-001 Fix:** `yazlik_details` tablosuna `deleted_at` sütunu eklendi |

**Toplam:** 13 dosya değişti, +463 / -40

---

## ADR-001 Phase Etkisi

| Phase | Etkileniyor mu? |
|-------|----------------|
| Phase 1A | HAYIR |
| Phase 1B | HAYIR |
| Phase 1C | HAYIR |

ADR-001 Phase 1A/1B/1C CLOSED durumu değişmedi.  
LP-008 CLOSED durumu değişmedi.

---

## Kapanış

| Alan | Değer |
|------|-------|
| Kapanış Tarihi | 2026-08-05 |
| Kapanış Commit | docs: close CERT-DEBT-001 owner ilan write correctness |
| Onaylayan | SAAB |
