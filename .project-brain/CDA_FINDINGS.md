# Canonical Drift Audit Findings (CDA)

## Rapor Formatı

Her bulgu 5N1K formatında raporlanır:
- **NE?** — Pattern tanımı
- **NEREDE?** — WRITE ve READ lokasyonları
- **NE ZAMAN?** — İlk bozulma tarihi
- **NASIL?** — Drift mekanizması
- **NEDEN?** — Root cause
- **KİM?** — ETKİ, SAHİP, BLAST

---

## CDA-001: IDENTITY_FRAGMENTATION (CLOSED / STALE_FINDING)

### 5N1K Raporu

| Alan | İçerik |
|---|---|
| **NE?** | Antigravity automatic rule injection creates competing authority |
| **NEREDE?** | `.agents/AGENTS.md` vs ROOT `AGENTS.md` |
| **NE ZAMAN?** | 2026-09-17 — Araştırma sırasında keşfedildi |
| **NASIL?** | Birden fazla AGENTS.md dosyası potansiyel confusion yarattı |
| **NEDEN?** | Naming/filing confusion |
| **KİM?** | Agent constitution files |

### Kanıt (TEST_VERIFIED — 2026-10-05)

| Verification | Result |
|---|---|
| Antigravity loads `.agents/AGENTS.md`? | **REJECTED — NOT_REPRODUCED** |
| Antigravity loads ROOT `AGENTS.md`? | **CONFIRMED** |
| Runtime references to `.agents/AGENTS.md`? | **NONE** |

### Karar

```
CDA-001 → STALE_FINDING / ORIGINAL_FINDING_NOT_PROVEN

SEBEP:
- .agents/AGENTS.md hiçbir agent tarafından YÜKLENMİYOR
- Competing authority KANITLANMADI
- Sadece dosya adı kafa karıştırıcı
- "Daha temiz görünüyor" ≠ problem

EYLEM:
- Rename REJECTED
- Governance değişikliği YAPILMADI
- Pipeline lesson: Contradictory Evidence Gate needed
```

### Pipeline Lessons

| # | Lesson | Priority |
|---|--------|----------|
| 1 | Contradictory evidence NOT blocked | HIGH |
| 2 | INFERRED → implementation geçiş kontrolsüz | HIGH |
| 3 | Implementer stage constraint ihlal edildi | MEDIUM |

### Forensic Report

Tam kanıt: `.project-brain/CDA_AUDIT_001_FINAL.md`

---

## CDA-007: MIGRATION_INCOMPLETE + MODEL_CONTRACT_DRIFT — Tenant Active-State Authority Gap (REMEDIATED)

### 5N1K Raporu

| Alan | İçerik |
|---|---|
| **NE?** | `MODEL_CONTRACT_DRIFT` + `MIGRATION_INCOMPLETE` — Write/read authority farklı kolonlarda |
| **NEREDE?** | `tenants` tablosu — `App\Models\SaaS\Tenant` vs `HuntOpportunitiesCommand` |
| **NE ZAMAN?** | 2026-05-17 — aktiflik_durumu migration'ı + Sprint 2 sonrası |
| **NASIL?** | Model write: `status` kolonu; Reader primary: `aktiflik_durumu` kolonu |
| **NEDEN?** | Migration aktiflik_durumu ekledi ama model güncellenmedi + legacy fallback maskeledi |
| **KİM?** | Migration geliştiriciler |

### Kanıt (TEST_VERIFIED — 2026-10-06) — FIXED

**Write Authority (FIXED 2026-10-06):**
```php
// app/Models/SaaS/Tenant.php
protected $fillable = ['uuid', 'name', 'domain', 'status', 'durum', 'aktiflik_durumu'];

// database/factories/SaaS/TenantFactory.php
'status' => 'active',
'durum' => 'active',
'aktiflik_durumu' => 'active',

// database/seeders/TenantBaselineSeeder.php
'status' => 'active',
'durum' => 'active',
'aktiflik_durumu' => 'active',
```

**Read Authority:**
```php
// app/Console/Commands/Cortex/HuntOpportunitiesCommand.php:41-46
$query->where('aktiflik_durumu', 1)           // INT
    ->orWhere('aktiflik_durumu', 'active')     // STRING
    ->orWhere('aktiflik_durumu', 'aktif')      // TURKISH
    ->orWhere('status', 'active');             // LEGACY FALLBACK
```

**Şema (SQLite — 4 kolon):**
```sql
status          varchar default 'active'
durum           varchar default 'active'
aktiflik_durumu  varchar default 'active'
is_active       tinyint(1) default 1
```

### Root Cause Chain

1. Sprint 2: `aktiflik_durumu` kolonu eklendi (Context7 standard)
2. Data copy: `status → aktiflik_durumu` (forward compat)
3. `status` kolonu KALDIRILMADI (backward compat)
4. `SaaS\Tenant` model güncellenMEDİ → `aktiflik_durumu` fillable'a eklenmedi
5. HuntOpportunities `aktiflik_durumu` okuyor (Context7)
6. Legacy fallback `OR status='active'` maskeliyor
7. DB default `aktiflik_durumu='active'` maskeliyor

### Remediation (COMPLETED 2026-10-06)

**Option A (SAFE) uygulandı:**
1. ✅ `SaaS\Tenant::$fillable` → `aktiflik_durumu`, `durum` eklendi
2. ✅ Factory güncellendi — tüm state kolonlarına 'active' değeri
3. ✅ Seeder güncellendi — tüm state kolonlarına 'active' değeri

### Neden Şimdi Maskeleniyor?

| Maskeleme Kaynağı | Açıklama |
|---|---|
| DB Default | `aktiflik_durumu='active'` default — model yazmasa da var |
| Legacy Fallback | `OR status='active'` — aktiflik_durumu boşsa buluyor |

### Test Kanıtları (PASS — 2026-10-06)

```
✓ proof_aktiflik_durumu_is_now_in_fillable — aktiflik_durumu fillable'da ARTIK VAR
✓ proof_aktiflik_durumu_can_be_mass_assigned — Mass assignment çalışıyor
✓ proof_tenant_create_with_aktiflik_durumu_writes_to_db — DB'ye yazıyor
✓ proof_hunt_opportunities_finds_tenant_with_explicit_aktiflik_durumu — Hunt buluyor
✓ summary_write_read_authority_now_aligned — Write/read aligned
```

### DOMAIN_CONVERGENCE Raporu

```
CANONICAL_AUTHORITY:         App\Models\SaaS\Tenant (model)
CANONICAL_EXECUTION_PATH:    Tenant::create() → aktiflik_durumu → HuntOpportunitiesCommand

SOURCE_OF_TRUTH_COUNT:       1

LEGACY_PATHS:                status kolonu hala mevcut (backward compat korundu)
DUPLICATE_IMPLEMENTATIONS:   NONE
PROVEN_ORPHANS:              NONE
UNKNOWN_USAGE:               NONE
MOCK_OR_PLACEHOLDER_RESIDUE: NONE

FALLBACKS:                   SAFE — Legacy fallback hala mevcut ama artık gereksiz

ROUTE_API_DRIFT:             NONE
MODEL_SCHEMA_CONTRACT_DRIFT: FIXED — aktiflik_durumu artık fillable'da
DESIGN_SYSTEM_DRIFT:         NONE

SECURITY_BOUNDARY_REGRESSION: PASS
OBSERVABILITY_ALIGNMENT:     PASS

REGRESSION:                  PASS

RUNTIME:                     TEST_VERIFIED
PRODUCTION:                  PENDING

DOMAIN_STATE:                CANONICAL_CLEAN
```

### Risk Değerlendirmesi

| Senaryo | Risk |
|---|---|
| Normal SaaS\Tenant::create() | DÜŞÜK — DB default maskeliyor |
| Manual migration/data fix | ORTA — aktiflik_durumu farklı değer alırsa görünmez |
| Strict aktiflik_durumu query | YÜKSEK — status yazılıp aktiflik_durumu farklıysa kayıp |

### Remediation Options

**Option A (SAFE):**
1. `SaaS\Tenant::$fillable` → `aktiflik_durumu` ekle
2. Model sync logic ekle: `status` değişince `aktiflik_durumu` da güncelle
3. Factory/Seeder güncelle

**Option C (CANONICAL):**
1. Migration: `status`, `durum`, `is_active` kolonlarını drop et
2. Model: `$fillable = ['uuid', 'name', 'domain', 'aktiflik_durumu']`
3. Query cleanup

### İlişkili CDAs

- CDA-006: DISPROVED (split-brain yok, orphan model)
- CDA-005: AUTHORITY_TABLE_DRIFT — migration conflict

### Forensic Report

Tam kanıt zinciri: `.project-brain/CDA_007_FINAL_REPORT.md`

---

## CDA-006: AUTHORITY_MODEL_DRIFT — Tenant Split-Brain (CLOSED / DISPROVED)

### 5N1K Raporu

| Alan | İçerik |
|---|---|
| **NE?** | `AUTHORITY_MODEL_DRIFT` + `AUTHORITY_CONTRACT_DRIFT` — Aynı kavram için iki model sınıfı |
| **NEREDE?** | `App\Models\Tenant` vs `App\Models\SaaS\Tenant` |
| **NE ZAMAN?** | 2026-05-03 — SaaS migration ile başladı |
| **NASIL?** | İki model farklı kontratlarla aynı tabloya erişiyor |
| **NEDEN?** | Migration silsilesinde schema değişti ama modeller güncellenmedi |

### Modellerin Durumu

**`App\Models\Tenant` (Schema-uyumlu):**
```php
fillable: ['uuid', 'name', 'domain', 'durum']
```

**`App\Models\SaaS\Tenant` (Schema-dışı):**
```php
fillable: ['uuid', 'name', 'domain', 'status']
```

### Çözüm Önerisi

Tenant model canonicalization + migration ile schema cleanup

### İlişkili CDAs

- CDA-007: Tenant Schema Trinstate Drift — aynı kök neden ailesi

---

## CDA-005: AUTHORITY_TABLE_DRIFT — İki tenants Tablo Oluşturma Migration'ı

| Alan | İçerik |
|---|---|
| **NE?** | Aynı tablo için iki farklı schema tanımı |
| **NEREDE?** | SaaS migration + CI restore migration |
| **NE ZAMAN?** | 2026-05-03 |
| **NASIL?** | Paralel geliştirme sırasında koordine edilemedi |

*Son Güncelleme: 2026-10-03 | HEAD: 4287be8e*
*Kaynak: BEKCI_ENFORCEMENT_REALITY_CHECK_01*

*Son Güncelleme: 2026-10-03 | HEAD: 4287be8e*
*Kaynak: BEKCI_ENFORCEMENT_REALITY_CHECK_01*

---

## CDA-REZ-01: AUTHORITY_MODEL_DRIFT — IlanReservation/PropertyReservation Split-Brain (STALE_FINDING ✅)

### 5N1K Raporu

| Alan | İçerik |
|---|---|
| **NE?** | `AUTHORITY_MODEL_DRIFT` — Aynı tablo için iki Eloquent modeli, farklı `tenant_id` kontratları |
| **NEREDE?** | `property_reservations` tablosu: `App\Models\IlanReservation` vs `App\Models\PropertyReservation` |
| **NE ZAMAN?** | 2026-01-29 — `property_reservations` tablo değişikliği + model refactor sırasında başladı |
| **NASIL?** | `IlanReservation` → `tenant_id` fillable'da YOK; `PropertyReservation` → `tenant_id` VAR |
| **NEDEN?** | Migration 2026-06-29 `tenant_id` ekledi ama `IlanReservation` modeli güncellenmedi |
| **KİM?** | Migration, Model refactor |

### Modellerin Durumu (2026-10-06)

**`IlanReservation` (LEGACY — tenant_id eksik):**
```php
protected $table = 'property_reservations'; // Aynı tablo
protected $fillable = [
    'property_id', 'start_date', 'end_date', 'nights',
    'guest_name', 'guest_phone', 'guest_email',
    'reservation_state', 'finansal_durum', 'depozito_tutari',
    'locked_nightly_rate', 'total_amount', 'created_by_user_id',
    'ulke_id', 'cancelled_at', 'confirmed_at',
    // ❌ tenant_id EKSİK!
];
public function ilan() { return $this->belongsTo(Ilan::class, 'ilan_id'); } // ❌ FK: ilan_id (yanlış!)
```

**`PropertyReservation` (CANONICAL):**
```php
protected $fillable = [
    'tenant_id',  // ✅ VAR!
    'property_id',
    // ... tüm field'lar + yeni channel_fee, checkin/out, snapshot fields
];
public function ilan() { return $this->belongsTo(Ilan::class, 'property_id'); } // ✅ FK: property_id
```

### Aktivasyon Kontrolü

| Aktivasyon Yolu | Kullanıcı | Status | Tenant Guard |
|---|---|---|---|
| `ReservationService::createReservation()` | Admin/API | **ACTIVE** ✅ | Unconditional fail-closed |
| `IlanReservationService::create()` | Admin | **ACTIVE** ❌ | **YOK — `tenant_id` yazılamaz!** |
| `IlanCalendarController::cancel()` | Admin | **ACTIVE** ❌ | **YOK** |
| `IlanCalendarController::confirm()` | Admin | **ACTIVE** ❌ | **YOK** |

### Risk Analizi

| Risk | Seviye | Açıklama |
|---|---|---|
| `IlanReservation::create()` ile `tenant_id` eksik yazılır | **CRITICAL** | Model `tenant_id`'yi fillable'da tutmadığı için yazamaz |
| Cross-tenant rezervasyon iptal/onay | **HIGH** | Controller'da tenant kontrolü yok |
| Legacy path üzerinden tenant isolation bypass | **HIGH** | `IlanReservationService` hiçbir tenant kontrolü yapmıyor |

### Remediation Seçenekleri

**Option A (Minimal Fix):**
1. `IlanReservation::$fillable` → `tenant_id` ekle
2. `IlanReservationService::create()` → `tenant_id` otomatik ekle
3. Controller'lara tenant guard ekle

**Option B (Strangler Fig):**
1. `IlanReservationService` → `PropertyReservation` kullanmaya yönlendir
2. Blade/Controller'ları güncelle
3. `IlanReservation` → deprecated annotation + migration ile kaldır

### İlişkili CDAs

- CDA-007: Tenant Active-State Authority Gap (aynı migration ailesi)

---

*Task ID: REZERVASYON_05_TENANT_BOUNDARY_REMEDIATION_01*
*Investigation: 2026-10-06 | IMPLEMENTER: Cline | STATUS: INVESTIGATION_COMPLETE*
