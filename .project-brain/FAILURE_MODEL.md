# ATLAS — FAILURE MODEL

**LAST_VERIFIED_HEAD:** 657311cb
**Purpose:** Hata patternlerini tanıma, yenilerini önleme

---

## BİLİNEN HATA PATTERNLERİ

### 1. TENANT ISOLATION VIOLATION

**Pattern:** tenant_id enjekte edilmedi veya sorguya eklenmedi

**Örnek (CDA-REZ-01):**
```
IlanReservation::create([
    'ilan_id' => $ilan->id,
    'property_id' => $ilan->property_id,
    // tenant_id EKSİK!
]);
```

**Sonuç:** Cross-tenant veri sızıntısı

**Koruma:**
- [x] BelongsToTenant trait kullan
- [x] Model::create() override et
- [x] Tenant isolation testleri yaz
- [ ] **FinansalIslem** — HENÜZ YOK (P0)

**Evidence:** CDA-REZ-01

---

### 2. SCHEMA/SERVICE FIELD MISMATCH

**Pattern:** Service farklı field ismi kullanıyor, schema başka

**Örnek (CDA-REZ-01):**
```
// Service kullanıyor:
$reservation->starts_at
$reservation->ends_at

// Schema'da var:
start_date
end_date
```

**Sonuç:** Veri kaybı veya silent failure

**Koruma:**
- [x] Field isimlerini migration + model + service'te uyumlu tut
- [x] Field naming convention belirle
- [ ] Unit test ile field contract doğrula

---

### 3. SPLIT BRAIN MODEL

**Pattern:** Aynı kavram için iki farklı model/tablo

**Örnek:**
```
IlanReservation (canonical)
vs
YazlikReservation / property_reservations (legacy)
```

**Sonuç:** Hangi model canonical? Hangisi güncellenmeli?

**Koruma:**
- [x] Canonical authority belirle
- [x] Duplicate model oluşturmadan önce araştır
- [x] Strangler Fig pattern ile migration

**Evidence:** REZERVASYON_04 investigation

---

### 4. DEAD CODE (Fallback Masking)

**Pattern:** Fallback/else branch hiç çalışmıyor

**Örnek:**
```php
$response = Http::post($url);
if ($response->failed()) {
    logFailure(); // Bu hiç çalışmıyor!
    throw new RequestException(); // Http::retry zaten fırlatıyor
}
```

**Sonuç:** Error handling çalışmıyor ama kimse fark etmiyor

**Koruma:**
- [ ] Branch coverage testleri yaz
- [ ] Exception path testleri ekle
- [x] Lint/static analysis

**Evidence:** AI Circuit Breaker split-brain

---

### 5. ROUTE/TENANT BINDING DRIFT

**Pattern:** Route auth düzgün ama tenant scope eksik

**Örnek:**
```php
Route::get('/ilan/{ilan}', [IlanController::class, 'show'])
    ->middleware('auth:sanctum');
    // tenant_id kontrolü yok!
```

**Sonuç:** Authenticated ama başka tenant'ın verisini görebilir

**Koruma:**
- [x] Policy kullan
- [x] Tenant scope query builder macro
- [x] Cross-tenant isolation testleri

---

### 6. TEST EXPECTATION MISMATCH

**Pattern:** Test yanlış expectation'a göre yazılmış

**Örnek:**
```php
// Kod düzeltildi ama test eski davranışı bekliyor
$test->assertEquals('old_value', $result); // ❌
$test->assertEquals('new_value', $result); // ✅
```

**Sonuç:** Test PASS ama kod yanlış çalışıyor

**Koruma:**
- [x] Test'i koda göre değil, requirement'a göre yaz
- [x] Test review yap
- [ ] Semantic diff before test

---

### 7. VERIFICATION INFRASTRUCTURE DEFECT

**Pattern:** Test infrastructure sorunu, test sonucu güvenilir değil

**Örnek:**
```php
// Test assertion doğru ama test environment yanlış
$user = User::where('email', 'ayhankucuk@gmail.com')->first(); // Non-deterministic!
```

**Sonuç:** Test PASS ama production'da farklı davranış

**Koruma:**
- [x] Deterministic data kullan
- [x] Factory/Seeder ile test data oluştur
- [x] Order by/id koy

---

### 8. BUDGET GUARD SPLIT BRAIN

**Pattern:** İki farklı Budget Guard farklı logic kullanıyor

**Örnek:**
```
AIOrchestrator → App\Services\AI\AiBudgetGuard (token bazlı)
DeepSeekProvider → Monetization\AiBudgetGuard (kredi bazlı)
```

**Sonuç:** Tutarsız budget kontrolü

**Koruma:**
- [x] Tek canonical Budget Guard
- [x] Provider-specific logic ayır
- [ ] Shared interface kullan

---

### 9. SCHEMA EVOLUTION WITHOUT MIGRATION

**Pattern:** Kod değişti ama migration yok veya tersi

**Örnek:**
```
Migration: status kolonu string
Model: $casts = ['status' => 'integer']
```

**Sonuç:** Type casting tutarsızlığı

**Koruma:**
- [x] Migration audit komutu
- [x] Schema seeding
- [x] Type casting review

---

### 10. STALE REFERENCE

**Pattern:** Eski doküman/code güncel değil

**Örnek:**
```
README.md: "API /v1 kullanır"
Ama kod: /v2'ye geçti
```

**Sonuç:** Developer yanlış path kullanıyor

**Koruma:**
- [x] REVALIDATION kuralı
- [x] HEAD kontrolü
- [x] Stale findings logla

---

## HATA ÖNCESİ DURUMLAR

### Tetikleyiciler

| Trigger | Açıklama |
|---------|----------|
| Yeni field ekleme | Migration + model + service + test eşzamanlı |
| Tenant multi-user | Isolation testi şart |
| External API değişikliği | Contract test ekle |
| Legacy migration | Audit + regression test |

---

## ROOT CAUSE KATEGORİLERİ

| Kategori | Açıklama | Örnek |
|----------|-----------|-------|
| DATA_MISMATCH | Schema/service field uyumsuzluğu | CDA-REZ-01 |
| TENANT_VIOLATION | tenant_id eksikliği | P0 bulgu |
| SPLIT_BRAIN | Duplicate authority | property_reservations |
| DEAD_CODE | unreachable branch | Circuit breaker |
| BINDING_DRIFT | Auth var, tenant yok | Route binding |
| INFRA_DEFECT | Test infrastructure | Determinism |

---

## PREVENTION CHECKLIST

### Yeni Kod Eklemeden Önce

- [ ] tenant_id injection noktasını bul
- [ ] Field isimlerini migration + model + service'te doğrula
- [ ] Test deterministic mi?
- [ ] Fallback branch gerçekten çalışıyor mu?
- [ ] Policy/Gate var mı?
- [ ] Legacy duplicate var mı kontrol et

### Migration Öncesi

- [ ] Mevcut data backward compatible mi?
- [ ] Fallback value var mı?
- [ ] Rollback stratejisi belirli mi?

---

## KNOWLEDGE TRANSFER

Her yeni bulgu bu dokümana eklenir:

```
## [PATTERN_NAME]
Pattern: [kısa açıklama]
Örnek: [kod/snippet]
Sonuç: [ne oldu]
Koruma: [yapıldı/yapılacak]
Evidence: [commit/issue]
```

---

## STALE FINDING LOG

| Date | Finding | Reason |
|------|---------|--------|
| 2026-10-07 | Finans tenant isolation | HENÜZ AÇIK |

---

## REFERENCES

- CDA-REZ-01: Reservation tenant violation + field mismatch
- AI-CIRCUIT-BREAKER-SPLIT: Budget guard split
- ILAN_CROSS_TENANT: Security gap (RESOLVED)
- VERIFIER_INFRASTRUCTURE_DEFECT: Test expectation issue
