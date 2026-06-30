# Naming Authority - Hybrid Governance Policy

**Status:** ACTIVE
**Version:** 1.0
**Date:** 2026-05-11
**Risk Level:** LOW-MEDIUM (controlled approach)
**Sustainability:** Production-safe, Governable, Sustainable

---

## Executive Summary

Naming Authority artık **governance concern** olarak ele alınıyor, "cleanup işi" değil.

**Adopted Strategy:** **Hybrid Naming Governance**

```
Domain Language    → Türkçe
Framework Language → İngilizce
Governance         → CI observable
Migration          → Gradual
```

Bu şu anda en düşük riskli, en sürdürülebilir, en production-safe karar.

---

## Core Problem Statement

### What Was The Real Problem?

**NOT:** Turkish naming itself
**YES:** Naming inconsistency and authority absence

**Example of the actual problem:**

```
Migration  → aktiflik_durumu
Model      → is_active
Query      → active
Service    → status
```

**Root Cause:**

```
No Naming Authority
```

**The issue was NOT:**
- "Türkçe isim kullanılması"

**The issue WAS:**
- Aynı kavramın farklı yerlerde farklı isimlerle yaşaması
- SSOT (Single Source of Truth) kaybı
- Predictability eksikliği

**This is what we're solving now.**

---

## Integration with Level 5 Governance

Naming Authority, mevcut roadmap'i iptal etmiyor. **Yeni bir governance domain'i olarak ekleniyor.**

```
Level 5 Governance
├── Operational Digest
├── CI Governance Guards
├── Runtime Validation
├── Drift Detection
└── Naming Authority Governance   ← yeni katman
```

---

## Hybrid Policy Definition

### Türkçe Kalacaklar (Domain Language)

**Kategori:** Business domain vocabulary

**Örnekler:**

```
aktiflik_durumu
yayin_tipi
ilan_kategori
aciklama
baslik
para_birimi
danisman_id
musteri_id
konum_bilgileri
ozellikler
```

**Neden Türkçe?**

* Business semantics
* Domain vocabulary
* Ekip dili
* Operasyon dili
* Stakeholder communication

---

### İngilizce Kalacaklar (Framework Language)

**Kategori:** Laravel/framework conventions

**Örnekler:**

```
id
created_at
updated_at
deleted_at
email_verified_at
remember_token
```

**Neden İngilizce?**

* Framework standardı
* Ecosystem compatibility
* Laravel expectation
* Community conventions

---

## Why NOT Full English Migration?

### Current Reality

Sistem zaten:

```
63% Turkish domain naming
```

üzerine kurulmuş.

### Risk Assessment

Full-English dönüşüm şu anda:

**HIGH RISK**

**Etkilenen Alanlar:**

* ❌ Migrations (schema rewrite)
* ❌ Models (property rename)
* ❌ Scopes (query rewrite)
* ❌ Services (method signature change)
* ❌ Forms (field name change)
* ❌ API contracts (breaking change)
* ❌ Dashboards (UI coupling)
* ❌ Seeders (data coupling)
* ❌ Telemetry labels (observability break)
* ❌ Exports (format change)
* ❌ Filters (query coupling)

**Conclusion:**

```
Big-bang English migration = ❌ FORBIDDEN
```

---

## The Real Problem

Asıl problem **dil seçimi değil**, **consistency kaybı**:

### Problem Example

```
Migration → aktiflik_durumu
Model     → is_active
Query     → isActive
```

**Sorun:** SSOT kaybı

**Çözüm:** Consistency enforcement

---

## New Naming Rules

### Rule 1: Domain Fields → Türkçe

Yeni domain field yazılırken:

```php
// ✅ CORRECT
Schema::create('ilanlar', function (Blueprint $table) {
    $table->string('baslik');
    $table->text('aciklama');
    $table->enum('aktiflik_durumu', ['aktif', 'pasif']);
});
```

```php
// ❌ WRONG
Schema::create('ilanlar', function (Blueprint $table) {
    $table->string('title');
    $table->text('description');
    $table->enum('status', ['active', 'inactive']);
});
```

---

### Rule 2: Framework Fields → İngilizce

Framework conventions:

```php
// ✅ CORRECT
Schema::create('ilanlar', function (Blueprint $table) {
    $table->id();
    $table->timestamps();
    $table->softDeletes();
});
```

```php
// ❌ WRONG
Schema::create('ilanlar', function (Blueprint $table) {
    $table->id();
    $table->timestamp('olusturulma_tarihi');
    $table->timestamp('guncellenme_tarihi');
});
```

---

### Rule 3: SSOT Consistency

**Migration ve Model aynı naming kullanmalı:**

```php
// ✅ CORRECT
// Migration
$table->enum('aktiflik_durumu', ['aktif', 'pasif']);

// Model
protected $fillable = ['aktiflik_durumu'];

// Query
$ilan->aktiflik_durumu
```

```php
// ❌ WRONG
// Migration
$table->enum('aktiflik_durumu', ['aktif', 'pasif']);

// Model
public function getIsActiveAttribute() // ← mismatch!
```

---

## Governance Implementation

### 1. Naming Policy = Authority

**SSOT Document:**

[`NAMING-AUTHORITY.md`](docs/technical/NAMING-AUTHORITY.md) (this file)

**Status:** Single Source of Truth

---

### 2. Guard = Visibility (Bekçi v2.1)

**Primary Guardian:**

[`BekciAuditCommand.php`](../../app/Console/Commands/Governance/BekciAuditCommand.php)

**Command:**
```bash
php artisan bekci:audit --naming
```

**Mechanism:** 
**AST (Abstract Syntax Tree)** based semantic analysis. Unlike simple regex, Bekçi understands the code structure and identifies violations even in complex nested migrations or model properties.

**Current Mode:** Report-only (Observation via WARNINGS)

**Future Mode:** Prevent (Enforcement via BLOCKING)

---

### 3. Zero New Drift

**Critical KPI:**

```
NEW violations = 0
```

**Baseline:** 18 violations (frozen, known technical debt)

**New Drift:** Not allowed

**This is production-grade approach.**

---

## Current Operations

### What We're Doing NOW

```
✅ Naming Governance Stabilization
❌ Rename Operations
```

**Focus:**

* Prevent new drift
* Observe patterns
* Document baseline
* Validate runtime
* Build evidence

---

### What We're NOT Doing

**Forbidden Operations:**

* ❌ Big-bang rename
* ❌ Full schema rewrite
* ❌ Aggressive enforcement
* ❌ Auto-remediation
* ❌ Breaking changes

**Reason:** Foundation still in observation phase.

---

## Affected Layers

Naming governance artık etkiliyor:

### 1. Schema Layer
* Migrations
* Database columns
* Indexes

### 2. Model Layer
* Model properties
* Accessors/mutators
* Relationships

### 3. Service Layer
* Method signatures
* DTOs
* Contracts

### 4. API Layer
* Request validation
* Response formatting
* API contracts

### 5. Telemetry Layer
* Metric labels
* Log fields
* Trace attributes

### 6. CI Layer
* Guard validation
* Drift detection
* Baseline tracking

### 7. Governance Layer
* Policy enforcement
* Evidence collection
* Operational review

---

## Safe Approach

**Current Posture:**

```
Keep existing architecture stable
Freeze naming policy
Prevent new drift
Fix runtime mismatches gradually
```

**This is production-grade engineering approach.**

---

## Daily Validation

### Commands

```bash
# Naming governance check
./scripts/ci-guard-naming-authority.sh

# Migration integrity
php artisan migrate:status
```

### Checks

- [ ] Yeni naming drift oluşuyor mu?
- [ ] Runtime mismatch geri geliyor mu?
- [ ] Baseline büyüyor mu?
- [ ] SSOT consistency korunuyor mu?

---

## Migration Strategy

### Phase 1: Stabilization (Current)

**Week 1-3:**

* ✅ Baseline documented
* ✅ Guard operational
* ✅ Zero new drift
* ✅ Runtime validation

---

### Phase 2: Gradual Cleanup (Future)

**Week 4+:**

* Opportunistic fixes
* Low-risk renames
* Runtime mismatch resolution
* Baseline reduction

---

### Phase 3: Enforcement (Future)

**TBD:**

* Guard blocking mode
* Strict validation
* Zero tolerance
* Full compliance

---

## Success Metrics

### Current Metrics

```
Baseline Violations: 18 (frozen)
New Violations: 0 (target)
Runtime Mismatches: 0 (fixed)
CI Guard Status: PASSING (report-only)
```

### Target Metrics (Week 1)

```
New Violations: 0
Runtime Stability: 100%
Guard Runs: 7/7 successful
Observation Days: 7/7 complete
```

---

## Risk Assessment

### Current Approach Risk

**Hybrid Naming Governance:** LOW-MEDIUM (CONTROLLED)

**Why LOW-MEDIUM?**

* ✅ Preserves existing architecture
* ✅ Prevents new drift
* ✅ Gradual migration path
* ✅ Production-safe
* ✅ Rollback possible
* ⚠️ Hybrid complexity exists
* ⚠️ Requires discipline

### Alternative Approach Risk

**Full English Migration:** HIGH

**Why HIGH?**

* ❌ Breaking changes across entire codebase
* ❌ High remediation cost
* ❌ Telemetry disruption
* ❌ API contract break
* ❌ Rollback difficult
* ❌ Runtime destabilization
* ❌ Observability break

**Conclusion:**

```
Migration Cost > Benefit (at this time)
```

**Reason:**

* Sistem büyük
* Runtime aktif
* Coupling yüksek
* Observability yeni kuruluyor

Şu an full-English migration çok yüksek riskli olurdu.

---

## Decision Rationale

### Why Hybrid?

1. **Existing Investment:** 63% Turkish domain naming already in place
2. **Business Alignment:** Domain vocabulary matches business language
3. **Risk Mitigation:** Avoids big-bang migration
4. **Team Efficiency:** Developers already familiar with Turkish domain terms
5. **Stakeholder Communication:** Business terms remain consistent

### Why NOT Full English?

1. **High Risk:** Breaking changes across entire codebase
2. **High Cost:** Massive refactoring effort
3. **Low Value:** Doesn't solve the real problem (consistency)
4. **Business Disconnect:** Domain terms lose business meaning
5. **Migration Complexity:** Coordinated change across all layers

---

## Does Hybrid Model Create Systemic Problems?

### Answer: NO (if properly governed)

**Hybrid Naming Governance kendi başına sistemsel problem üretmez.**

**Why?**

Asıl problem:
* ❌ Dilin Türkçe veya İngilizce olması değil
* ✅ Tutarsızlık ve authority eksikliğidir

**The separation makes sense:**

```
Domain Language    → Türkçe (business semantics)
Framework Language → İngilizce (technical conventions)
```

**This is actually DDD-aligned (Domain-Driven Design).**

Örneğin:
* Ekip "ilan", "danışman", "yayın tipi" diyorsa → Domain Türkçe olmalı
* Laravel `created_at`, `updated_at` bekliyorsa → Framework İngilizce olmalı

**Bu hibrit yapı sürdürülebilir olabilir.**

---

## When Does It Become A Problem?

### Risk Scenario 1: Authority Loss ❌

Eğer tekrar:

```
aktiflik_durumu
is_active
active
status
durum
```

aynı anda yaşamaya başlarsa.

**Mitigation:** Naming Authority policy (this document) + CI guard

---

### Risk Scenario 2: Policy Non-Compliance ❌

Policy yazıp uygulanmazsa, drift geri gelir.

**Mitigation:**

* ✅ CI guard
* ✅ Baseline tracking
* ✅ Report-only mode
* ✅ Weekly review
* ✅ Operational Digest

---

### Risk Scenario 3: "Convenience Rename" ❌

Developer: "Ben bunu English yapayım" demeye başlarsa, yeni drift oluşur.

**Mitigation:** SSOT artık policy dokümanı. Değişiklik için policy update gerekli.

---

## Advantages of Current Model

### ✅ Advantages

1. **Low Risk**
   - Mevcut sistem kırılmıyor

2. **Operational Stability**
   - Runtime destabilize olmuyor

3. **Incremental Cleanup**
   - Kritik yerler zamanla temizleniyor

4. **Governance Visibility**
   - Drift artık görünür

5. **Team Alignment**
   - Ekip mevcut domain dilini koruyor

6. **Business Alignment**
   - Domain vocabulary matches business language

7. **DDD Compliance**
   - Domain language preserved

---

## Disadvantages of Current Model

### ⚠️ Disadvantages (Realistic Assessment)

1. **Hybrid Complexity**
   - İki dil birlikte yaşıyor
   - Requires clear boundaries

2. **Onboarding Cost**
   - Yeni developer policy öğrenmeli
   - Documentation critical

3. **Tooling Complexity**
   - Bazı otomasyonlar zorlaşabilir
   - Custom tooling may be needed

4. **International Scaling**
   - İleride global ekipte English baskısı olabilir
   - May require future migration

**But these are smaller risks than current production risk.**

---

## Long-Term Strategy

### 6-12 Months From Now

**Healthy Scenario:**

Sistem:

* ✅ Daha tutarlı
* ✅ Drift'i durmuş
* ✅ Naming governance oturmuş
* ✅ Baseline küçülmüş

**At that point:**

İsterseniz domain English migration bile planlanabilir.

**But:**

❌ Şimdi değil
✅ Foundation stabilize olduktan sonra

---

### Future Migration Path (If Needed)

**Phase 1: Stabilization (Current)**
- Week 1-3: Observation & evidence
- Baseline frozen at 18
- Zero new drift

**Phase 2: Gradual Cleanup (Months 1-3)**
- Opportunistic fixes
- Critical mismatch resolution
- Baseline reduction

**Phase 3: Optional English Migration (Months 6-12)**
- IF business need exists
- IF foundation stable
- IF team ready
- THEN gradual domain English migration possible

**Key:** Not now. Foundation first.

---

## Governance Principles

### 1. Consistency Over Language

**Priority:**

```
SSOT consistency > Language choice
```

### 2. Gradual Over Big-Bang

**Strategy:**

```
Incremental improvement > Complete rewrite
```

### 3. Evidence Over Opinion

**Decision Making:**

```
Operational evidence > Theoretical preference
```

### 4. Stability Over Perfection

**Approach:**

```
Production safety > Ideal architecture
```

---

## References

### Governance Documents

* [`OPERATIONAL-DIGEST-WEEK-1-DAY-1.md`](OPERATIONAL-DIGEST-WEEK-1-DAY-1.md) - Day 1 report
* [`WEEK-1-OPERATIONAL-PLAN.md`](operational-digest/WEEK-1-OPERATIONAL-PLAN.md) - Operational plan
* [`observation-log.md`](operational-digest/observation-log.md) - Daily observations

### Technical Documents

* [`repo-gov-02b-execution-summary.md`](audits/repo-gov-02b-execution-summary.md) - Runtime audit
* [`BASELINE-SNAPSHOTS.md`](BASELINE-SNAPSHOTS.md) - Baseline registry

### Scripts

* [`ci-guard-naming-authority.sh`](../../scripts/ci-guard-naming-authority.sh) - Governance guard

---

## Validation & Monitoring

### Continuous Checks

```bash
# Daily
./scripts/ci-guard-naming-authority.sh
php artisan migrate:status
```

**Monitor:**

- [ ] Yeni drift oluşuyor mu?
- [ ] Baseline büyüyor mu?
- [ ] Runtime mismatch tekrar ediyor mu?
- [ ] Policy compliance korunuyor mu?

---

## Conclusion

### Final Assessment

**Hayır — mevcut seçilen yaklaşım kendi başına sistemsel problem üretmez.**

**Çünkü artık:**

* ✅ Naming rastgele değil
* ✅ Governance altında
* ✅ Policy var
* ✅ Guard var
* ✅ Baseline var
* ✅ Observation var
* ✅ Gradual migration var

**Gerçek problem:**

* ❌ "Türkçe olması" değil
* ✅ Authority ve consistency eksikliği idi

**Şimdi çözüldü.**

---

### Adopted Strategy

**Hybrid Naming Governance**

**Rationale:**

* Lowest risk (at this time)
* Most sustainable
* Production-safe
* Business-aligned
* DDD-compliant
* Governable

**Approach:**

```
Domain     = Türkçe
Framework  = İngilizce
Governance = CI observable
Migration  = Gradual
Authority  = Policy-driven
```

**Assessment:**

```
Production-safe    ✅
Governable         ✅
Sustainable        ✅
```

**This approach will continue.**

---

**Policy Status:** ACTIVE
**Last Updated:** 2026-05-11
**Next Review:** Week 1 End (2026-05-17)
