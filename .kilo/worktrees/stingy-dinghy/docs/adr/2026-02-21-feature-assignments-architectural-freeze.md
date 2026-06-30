# ADR-002: feature_assignments Modülü — Architectural Freeze

**Tarih:** 21 Şubat 2026
**Durum:** KABUL EDİLDİ — Yürürlükte
**Yazar:** Yalıhan Mühendislik
**İlgili:** [ADR-001](2026-02-21-governance-enforcement-layer.md)

---

## Bağlam (Context)

`feature_assignments` tablosu, UPS Template sisteminin çekirdeğidir. Her `YayinTipiSablonu` için hangi `Feature`'ların aktif olduğunu, sırasını ve görünürlüğünü tutar. Bu tablo üzerindeki her yazma işlemi:

1. Cache invalidation gerektirir (`UpsCacheService`)
2. Audit log gerektirir (`TemplateChangeLog`)
3. Wizard context yenilenmesini tetiklemelidir (`WizardContextService`)

**Sprint gov/phase4 boyunca gerçekleştirilen work:**

| Commit     | Değişiklik                                                                                                  |
| ---------- | ----------------------------------------------------------------------------------------------------------- |
| `37999429` | Observer v2: `created/deleted` → `invalidateForJunction` + ChangeLog                                        |
| `e0d818ae` | SmartFormController, KonutNeuralSync, KonutArkadasiCleanup bypass kapatıldı; `getFallbackContext()` silindi |
| `24ac35a0` | UpsCleanupOrphans 6× raw delete → Eloquent; `ci-guard-raw-db-write.sh` + QG STEP 5.1                        |

**Sonuç:** Tüm write path'ler Observer zincirine alındı. Raw DB bypass kalmadı. CI guard bunu gelecekte de zorluyor.

---

## Karar (Decision)

### `feature_assignments` modülü **"Çekirdek Stabil"** olarak mühürlenir.

Aşağıdaki invariant'lar **değiştirilemez** — değişim ancak yeni bir ADR ile mümkündür:

---

### 🔒 Invariant 1: Tek Write Authority

```
FeatureAssignment Eloquent model = tek yazma noktası
```

- `DB::table('feature_assignments')->insert/update/delete/updateOrInsert/upsert` **yasaktır**
- `FeatureAssignment::create()`, `updateOrCreate()`, `fa->delete()` kullanılır
- CI guard (`scripts/ci-guard-raw-db-write.sh`) bunu otomatik zorlar
- Quality Gate STEP 5.1 her PR'da çalışır → ihlal = GOVERNANCE BLOCKER

---

### 🔒 Invariant 2: Observer Zinciri Dokunulmaz

```
FeatureAssignment::created  → Observer::created  → invalidateForJunction + logFeatureAdded
FeatureAssignment::updated  → Observer::updated  → invalidateForJunction + logFeatureUpdated
FeatureAssignment::deleted  → Observer::deleted  → invalidateForJunction + logFeatureRemoved
```

- `FeatureAssignmentObserver` sınıfı yapısal değişime **kapalıdır**
- Yeni event hook eklenmesi ADR gerektirir
- Observer'da `try/catch` ile hata yutma **yasaktır** — yalnızca `Log::warning` + devam

---

### 🔒 Invariant 3: UpsCacheService Sole Cache Authority

```
UpsCacheService::invalidateForJunction() = tek cache temizleme noktası
```

- `Cache::forget()`, `Cache::tags()->flush()` doğrudan çağrısı `feature_assignments` bağlamında **yasaktır**
- Tüm cache operasyonları `UpsCacheService` üzerinden geçer
- Başka bir servis `UpsCacheService`'i bypass edemez

---

### 🔒 Invariant 4: WizardContextService — Silent Fallback Yasak

```
WizardContextService::getContext() = hata fırlatır, sessizce fallback vermez
```

- `getFallbackContext()` metodu **kalıcı olarak silindi** (commit `e0d818ae`)
- Yeniden eklenmesi ADR gerektirir
- Hata durumunda: exception propagate edilir → UI hata gösterir → sistem tutarlı kalır

---

### 🔒 Invariant 5: Domain Service Deferral

```
Domain Service katmanı şu an EKLENMEYECEKTİR
```

Domain Service ancak şu koşulların **en az ikisi** gerçekleşirse düşünülür:

1. Aynı tabloya 3+ bağımsız servis/controller bağımsız iş kuralı uygular
2. Cross-aggregate işlem kuralı oluşur
3. Transaction orchestration karmaşıklaşır (nested saga vb.)
4. Event choreography büyür (3+ downstream consumer)

Şu an bu koşulların **hiçbiri** yok. Erken DDD refactor = gereksiz complexity.

---

## Sonuçlar (Consequences)

**Olumlu:**

- Modül tamamen öngörülebilir — her yazma işleminin yan etkileri garantili
- Yeni geliştirici onboarding hızlı — tek nokta, clear contract
- AI agent drift riski minimal — CI guard + QG otomatik engelliyor
- Performans regresyonu riski düşük — cache invalidation tek otoritede

**Kısıtlar:**

- `feature_assignments` üzerinde hızlı hotfix yapılamaz — Observer zinciri şart
- Bulk insert performansı gerekirse özel ADR açılmalı (quarantine pattern uygulanabilir)
- Test'lerde `DB::table` ile orphan fixture oluştururken `Schema::disableForeignKeyConstraints()` zorunlu

---

## Alternatifler Değerlendirmesi

| Alternatif                       | Neden Reddedildi                                                |
| -------------------------------- | --------------------------------------------------------------- |
| Domain Service katmanı (şimdi)   | Observer zaten de facto service; complexity artışı gereksiz     |
| Bulk Eloquent (chunked `update`) | Mevcut hacimde N+1 problemi yok; erken optimizasyon             |
| Event Sourcing                   | Overengineering; TemplateChangeLog yeterli audit trail sağlıyor |
| Raw DB + manual cache call       | Observer bypass → tutarsızlık riski; CI guard zaten engelliyor  |

---

## Freeze Kapsamı — Dosya Listesi

Aşağıdaki dosyalar **frozen** kabul edilir. Değişiklik = ADR zorunlu:

```
app/Observers/FeatureAssignmentObserver.php     ← Observer zinciri
app/Services/Ups/UpsCacheService.php            ← Cache authority (invalidateForJunction)
app/Services/Wizard/WizardContextService.php    ← No silent fallback
scripts/ci-guard-raw-db-write.sh                ← CI enforcement
scripts/quality-gate.sh (STEP 5.1)              ← QG blocker
```

Aşağıdaki dosyalar **genişletilebilir** (frozen değil):

```
app/Console/Commands/UpsCleanupOrphans.php      ← Yeni orphan tipi eklenebilir
app/Http/Controllers/Admin/TemplateController.php ← Yeni endpoint eklenebilir
app/Models/FeatureAssignment.php                ← Yeni scope/accessor eklenebilir
```

---

## Referanslar

- [ADR-001: Governance Enforcement Layer](2026-02-21-governance-enforcement-layer.md)
- `docs/adr/` — Tüm architectural kararlar
- `scripts/ci-guard-raw-db-write.sh` — CI enforcement script
- `tests/Feature/Governance/` — Freeze invariant testleri
- `tests/Unit/Scripts/CiGuardRawDbWriteTest.php` — CI guard testleri
