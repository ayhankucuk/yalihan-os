# 🏛️ YALIHAN OS — CANONICAL INTEGRITY GATE

**Tam Adı:** Canonical Integrity Gate  
**Versiyon:** v1.0  
**Oluşturulma:** 2026-09-26  
**Durum:** ACTIVE — Öğrenme döngüsü devrede  

---

## Özet

YALIHAN OS'nin doğrulanmış kurallarını koruyan bütünsel güvence mimarisi. Doktor/Bekçi alternatifi değil — mevcut üç sistemin (Sentinel + Doktor + Bekçi) üzerinde ortak bir **Canonical Contract Layer** ile çalışmasını sağlayan çerçeve.

---

## Mimari Katmanlar

```
🏛️ YALIHAN OS
       │
       ▼
📜 CANONICAL CONTRACT LAYER  ← TEK OTORİTE KAYNAĞI
       │
 ┌─────┼─────┐
 ▼     ▼     ▼
🛡️    🩺    👁️
Sentinel Doktor  Bekçi
FAST    DEEP    OBSERVE
 │       │       │
 └───┬───┘       │
     ▼           ▼
  15 CONTROL LAYERS
     │
     ▼
PASS / FAIL / WARN / UNKNOWN
     │
     ▼
REPO_VERIFIED
TEST_VERIFIED
PRODUCTION_VERIFIED
or UNKNOWN
```

---

## 3 Çalışma Motoru

### 🛡️ Sentinel — FAST GATE
- **Amaç:** Saniyelik kontroller, commit/remediation öncesi kritik contract kırılmalarını bloke eder
- **Trigger:** Pre-commit hook, her Cline oturumu başlangıcı
- **Süre:** < 5 saniye hedef
- **Kapsam:** Sadece hard FAIL yakalar, WARN üretebilir

### 🩺 Doktor — DEEP DIAGNOSIS
- **Amaç:** Daha pahalı testler, schema/model ilişkileri, disposable DB, AST, full contract suite
- **Trigger:** Manual, CI/CD, veya Sentinel WARN sonrası derin inceleme
- **Süre:** Dakikalar
- **Kapsam:** "Neden bozuk?" sorusunu araştırır, root cause标识

### 👁️ Bekçi — OBSERVABILITY / TELEMETRY
- **Amaç:** Sonuçları ve runtime durumunu izler
- **Trigger:** Sürekli veya periyodik
- **Kapsam:** Yeni bir doğruluk otoritesi yaratmaması hedefleniyor — sadece izleme

---

## 3 Çalışma Modu

| Mod | Motor | Kullanım | Evidence Hedefi |
|-----|-------|----------|-----------------|
| **FAST** | Sentinel | Pre-commit, her oturum başı | REPO_VERIFIED |
| **DEEP** | Doktor | Tam regression, contract doğrulaması | TEST_VERIFIED |
| **PRODUCTION AUDIT** | Bekçi | Read-only runtime/production doğrulaması | PRODUCTION_VERIFIED |

### Kritik Ayrım

```
FAST PASS ≠ Production PASS
DEEP PASS ≠ Production PASS
```

**Production kanıtı yoksa → UNKNOWN**

---

## 15 Kontrol Katmanı

Her katman Canonical Contract Layer'a bağlıdır ve her motor farklı derinlikte kontrol eder.

### 1. Data Authority
- Canonical schema, model, relation ve tek veri otoritesi
- Migration → Model → DB schema senkronizasyonu

### 2. Tenant & Security
- Tenant isolation (schema, queries, uniqueness, seeders)
- Authentication, authorization, mutation sınırları

### 3. Data Semantics / Silent Corruption
- NULL ≠ 0 ≠ "" semantics
- Yanlış default/cast nedeniyle sessiz veri bozulmasını yakalar
- **Örnek Contract:** `BULK_LISTING_COORDINATE_NULL_SEMANTICS`

### 4. Financial Immutability
- Komisyon, owner payable, fiyat/kur gibi mühürlenmiş finansal snapshot'ların değişmemesi
- Ledger bütünlüğü

### 5. Bounded Context Integrity
- Emlak Proje / Team Proje gibi domain'lerin birbirine karışmasını engeller
- Domain boundary koruması

### 6. API & Resource Contracts
- DB/model doğru olsa bile API'nin legacy/yanlış alan kullanmasını yakalar
- Contract-first doğrulama

### 7. Write-Path Integrity
- create/update/cancel/delete yazma yollarının canonical service ve security sınırlarından geçmesi
- Thin Controller + Service Layer kontrolü

### 8. Bootstrap / Fresh Install
- Boş DB → canonical schema → canonical seeders → beklenen temiz sistem
- Installer doğrulaması
- **PRENSİP #12 bağlantısı:** `#8` + `#10` birlikte **CANONICAL_BOOTSTRAP_INTEGRITY** invariant'ını oluşturur

### 9. Migration Safety
- Baseline, ancestry, post-baseline migration ve replay güvenliği
- Forward/backward compatibility
- **PRENSİP #12 bağlantısı:** `MIGRATION_BOUNDARY_CONSISTENCY` invariant'ı — physical baseline otoritedir; post-baseline migration yalnız forward evolution'dır; historical migration yeniden runtime authority olamaz

### 10. Seeder Authority
- Duplicate/legacy seeder'ın ikinci veri otoritesi oluşturmasını engeller
- Canonical seeder sadece bir tane olmalı
- **PRENSİP #12 bağlantısı:** `SEEDER_IS_NOT_AUTHORITY` invariant'ı — seeder schema/model/runtime'dan bağımsız ikinci truth oluşturamaz

### 11. Health Self-Check
- Sentinel/Doktor/Bekçi'nin kendisinin doğru çalıştığını doğrular
- Failure'ın gerçekten failure exit code ürettiğini kontrol eder

### 12. Scheduler / Job / Command Integrity
- Scheduler'ın olmayan command/job/handler çağırmasını yakalar
- Kernel registration kontrolü

### 13. External Integration Contracts
- Hermes, n8n, webhook, AI/provider payload
- Tenant propagation, correlation, idempotency kontratları

### 14. Secret & Production Safety
- Hardcoded secret tespiti
- Tehlikeli production default'u
- Production guard'ları

### 15. Dirty Tree & Task Integrity
- Agent'ın scope dışına çıkmasını engeller
- Beklenmeyen HEAD değişimini yakalar
- Unrelated dosyaların commit'e girmesini engeller

---

## 4 Sonuç Durumu

Her contract şunları döndürür:

| Durum | Anlamı | Aksiyon |
|-------|--------|---------|
| **PASS** | Doğrulandı — invariant korunuyor | Devam |
| **FAIL** | Canonical invariant kırıldı | Bloke et veya düzelt |
| **WARN** | Şüpheli pattern / finding candidate | İncelemeye al |
| **UNKNOWN** | Yeterli evidence yok | Kanıt topla — PASS'a çevirme |

**Kural:** `UNKNOWN` hiçbir zaman otomatik olarak `PASS`'a çevrilmez.

---

## Evidence Seviyeleri

| Seviye | Anlamı |
|--------|--------|
| `UNVERIFIED` | Henüz test edilmedi |
| `REPO_VERIFIED` | Code review geçti, schema ile uyumlu |
| `TEST_VERIFIED` | Automated testler geçiyor |
| `PRODUCTION_VERIFIED` | Canlı production kanıtı mevcut |
| `BLOCKED_PENDING_PRODUCTION_AUTH` | Migration/deploy kullanıcı onayı bekliyor |

---

## Öğrenme Mekanizması (Contract Promotion Pipeline)

Sistemin en kritik özelliği: her yeni finding otomatik olarak Gate'e eklenmez.

```
Finding → Reproduce → Root Cause → Bounded Fix → Regression →
Independent Verification → Genellenebilir mi? → Contract Promotion
```

### Örnek: Bulk Import Coordinate Problem

```
Problem: Bulk import sırasında koordinat eksik → 0,0 default
         ↓
Reproduce: Import test case ile koordinat null durumu
         ↓
Root Cause: BulkImportService koordinat validation eksik
         ↓
Bounded Fix: Koordinat zorunlu alan olarak ekle
         ↓
Regression: Mevcut testler + yeni coordinate test
         ↓
Independent Verification: Farklı agent tarafından doğrulama
         ↓
Genellenebilir mi?: Evet — tüm import senaryoları için geçerli
         ↓
Contract Promotion: BULK_LISTING_COORDINATE_NULL_SEMANTICS
                    (Layer 3: Data Semantics)
```

### Örnek: Reservation Tenant Mutation

```
Problem: Reservation üzerinde yanlış tenant mutation
         ↓
...
         ↓
Contract: PROPERTY_RESERVATION_TENANT_MUTATION_BOUNDARY
          (Layer 2: Tenant & Security)
```

---

## Kanıt Paketi (Evidence Package)

Her finding için toplanması gereken kanıt:

```
EVIDENCE_PACKAGE:
  finding_id:        <unique identifier>
  finding_date:      <ISO date>
  severity:          P0 / P1 / P2 / P3
  layer:             <1-15>
  root_cause:        <description>
  affected_files:   <list>
  reproduction:     <test case or steps>
  fix_scope:        <bounded fix description>
  regression_tests: <test names>
  verifier:         <agent/session>
  promotion_candidate: YES / NO
  promotion_reason: <if YES, why generalizable>
```

---

---

## Mimari Otorite ve Tek Yönlü Çağrı Zinciri (Anti-Circular Authority)

Sistemde bölünmüş akıl (split-brain) ve döngüsel bağımlılık (circular authority) kesinlikle yasaktır:

```
CANONICAL CHECKS (Boundary, Baseline, Contracts)
      │
 ┌────┴────┐
 ▼         ▼
SENTINEL  DOKTOR
(FAST)    (DEEP)
 │         │
 └────┬────┘
      ▼
RESULTS & EVIDENCE
      │
      ▼
    BEKÇİ (OBSERVE & TELEMETRY ONLY)
```

- **Sentinel & Doktor:** Denetçidir, kararı verir, process exit code üretir.
- **Bekçi:** Asla karar verici / dördüncü otorite değildir; ortaya çıkan evidence ve telemetriyi toplayan, loglayan, trendleri izleyen gözlemcidir (Observer / Telemetry Aggregator).
- Sentinel Bekçi'den skor alıp karar vermez; Bekçi Sentinel ve test sonuçlarını tüketir.

---

## Altın Kural: Kapsam Sınırı (Contract Scope <= Evidence Scope)

> **Contract scope, mevcut evidence scope'undan daha geniş olamaz.**
- Bir domainde kanıtlanan kural (örn. `PROPERTY_RESERVATION_TENANT_MUTATION_BOUNDARY`) doğrudan tüm sisteme genellenerek `GLOBAL_TENANT_MUTATION_BOUNDARY` ilan edilemez.
- Yalnızca `BulkListingController` üzerinde kanıtlanan davranış `BULK_LISTING_COORDINATE_NULL_SEMANTICS`tir; "tüm nullable alanlar çözüldü" denemez.
- Genel invariantlar (`NULLABLE_DATA_SEMANTICS`) Doktor için statik scanner/warning kuralı olarak kalır; test ile kilitlenmeyen durumlar `UNKNOWN` olarak korunur.

---

## İlk Canonical Contract Envanteri (Remediation-to-Contract Promotion)

Mevcut kapatılmış ve doğrulanmış görevlerden çıkarılan sözleşmeler:

| ID | Contract Adı | Orijin Görev | Invariant (Kural) | Motor / Mod | Durum & Kanıt |
|---|---|---|---|---|---|
| **C01** | `MIGRATION_BASELINE_BOUNDARY` | `35b7e64b` | Baseline (`ec6ba05a`) öncesi historical migration'lar yeni DB üzerinde replay edilemez. Post-baseline migration sayısı fail-closed yönetilir. | Sentinel (FAST) / Doktor (DEEP) | **PROMOTED** (`REPO_VERIFIED` + `TEST_VERIFIED`) |
| **C02** | `BOOTSTRAP_LOCAL_CANONICAL` | `2098d781` | Canonical bootstrap; hardcoded credential basamaz, uzak DB hedefleyemez, demo seeder'ları kanonik akışa sokamaz. | Doktor (DEEP) | **PROMOTED** (`TEST_VERIFIED`) |
| **C03** | `PROPERTY_RESERVATION_TENANT_MUTATION_BOUNDARY` | `e8b9effe` (TASK_10) | PropertyReservation üzerindeki hiçbir modify/cancel operasyonu tenant_id doğrulaması olmadan yapılamaz. | Sentinel (FAST) / Doktor (DEEP) | **PROMOTED** (`REPO_VERIFIED` + `TEST_VERIFIED`) |
| **C04** | `BULK_LISTING_COORDINATE_NULL_SEMANTICS` | `db840b34` (TASK_12) | Bulk import'ta eksik, null veya empty string koordinatlar NULL kaydedilir; açık 0 ve "0" korunur (`NULL ≠ 0`). | Doktor (DEEP) / Tests | **PENDING_FINAL_VERIFY** (`TEST_VERIFIED`) |
| **C05** | `CANONICAL_RESOURCE_COORDINATE_FIELDS` | `8d2ceab1` / `f15f0448` | Resource/API çıkışında legacy alias (`latitude`/`longitude`) okunamaz; yalnızca canonical model kolonları (`lat`/`lng`) tüketilir ve null değerler 0.0 olarak serialize edilemez. | Sentinel (FAST) / Tests (DEEP) | **PROMOTED** (`REPO_VERIFIED` + `TEST_VERIFIED`) |
| **C06** | `HEALTH_GATE_THRESHOLD_ENFORCEMENT` | `37f6ae04` (TASK_35) | Sağlık skoru < 70 olduğunda veya kritik kapı kırıldığında process exit code 1 (FAILURE) dönmelidir; sessizce 0 dönülemez. | Sentinel (FAST) | **PROMOTED** (`REPO_VERIFIED`) |
| **C07** | `SEEDER_SINGLE_AUTHORITY` | `6720b4b8` / `970c6e28` | Aynı canonical state'i birden fazla seeder üretemez; legacy seeder'lar kanonik bootstrap hattından çıkarılır. | Doktor (DEEP) | **PROMOTED** (`REPO_VERIFIED` + `TEST_VERIFIED`) |
| **C08** | `AUTHORIZATION_SINGLE_AUTHORITY` | `b4765cd9` | Rol/yetki denetimi legacy integer `role_id` üzerinden yapılamaz; tek kanonik otorite Spatie RBAC olmalıdır. | Sentinel (FAST) / Tests (DEEP) | **PROMOTED** (`REPO_VERIFIED` + `TEST_VERIFIED`) |
| **C09** | `FINANCIAL_SNAPSHOT_IMMUTABILITY` | Bekleyen (C3 finding) | Rezervasyon oluştuktan sonra geriye dönük hesaplama değişse bile mühürlenmiş finansal snapshot verisi değiştirilemez. | Tests (DEEP) | **CANDIDATE — NOT PROMOTED** (`WARN / BLOCKED`) |
| **C10** | `SCHEDULER_COMMAND_INTEGRITY` | Bekleyen (Task 9 finding) | Kernel schedule içindeki komut ve job'lar container'da kayıtlı ve çalıştırılabilir olmalıdır. | Doktor (DEEP) | **CANDIDATE — NOT PROMOTED** (`UNKNOWN`) |
| **C11** | `CANONICAL_BOOTSTRAP_INTEGRITY` | CDA-004 / TENANT_CANONICAL_AUTHORITY_RESOLVE_01 | YALIHAN OS, canonical baseline'dan temiz bir veritabanına deterministik olarak kurulabilmeli; oluşan veri Model ve Runtime tarafından aynı anlamla okunabilmeli. Seeder hiçbir zaman bağımsız schema veya business authority değildir. | Doktor (DEEP) | **ACTIVE — PRENSİP #12** (`REPO_VERIFIED`) |

---

## PRENSİP #12 — CANONICAL_BOOTSTRAP_INTEGRITY (2026-09-28)

**Kaynak:** CDA-004 (TenantBaselineSeeder forensics) — TENANT_CANONICAL_AUTHORITY_RESOLVE_01
**Decision:** DECISION_LOG.md PrENSİP #12
**Etki:** Tüm bootstrap, seeder, migration ve model canonicalization görevleri

### Tam Authority Zinciri

```
Domain Authority → Physical Schema → Migration Lineage
  → Seeder/Bootstrap → Model/Relations → Runtime Consumers
  → Tests → Production
```

### 5 Zorunlu Invariant

| # | Invariant | Tanım |
|---|---|---|
| 1 | `SEEDER_IS_NOT_AUTHORITY` | Seeder schema/model/runtime'dan bağımsız ikinci truth oluşturamaz. Seeder ancak physical schema + runtime authority uyumuna hizmet eder. |
| 2 | `WRITE_READ_CONSISTENCY` | Seeder'ın yazdığı field/state/pivot, canonical model ve runtime'ın okuduğu contract ile aynı olmalıdır. Field name drift, state vocabulary drift, pivot column drift yakalanmalı. |
| 3 | `MIGRATION_BOUNDARY_CONSISTENCY` | Physical baseline otoritedir. Post-baseline migration yalnız forward evolution'dır. Historical migration yeniden runtime authority olamaz. |
| 4 | `DETERMINISTIC_CLEAN_BOOTSTRAP` | Disposable boş DB: canonical baseline → post-baseline migrations → canonical seeders → valid runtime-readable state üretebilmelidir. Mevcut local DB'nin tarihsel kalıntıları sonucu maskelemez. |
| 5 | `NON_DESTRUCTIVE_IDEMPOTENCY` | Seeder tekrar çalıştığında duplicate/orphan üretmemeli ve mevcut business verisini yanlış lookup/ID varsayımıyla sessizce değiştirmemeli. |

### Doktor (DEEP) Clean-Room Bootstrap Kontrolü

12 kontrol noktası:

1. **Seeder execution order / FK dependencies** — Tek tek PASS olsa bile DatabaseSeeder sırası yanlışsa bootstrap kırılır
2. **updateOrInsert/updateOrCreate destructive potential** — Lookup key yanlışsa gerçek business kaydı overwrite edilebilir
3. **Hard-coded numeric ID assumptions** — tenant_id=1, role_id=1 gibi sabit ID'ler; seeder ile runtime'ın aynı numeric ID'yi business truth kabul edip etmediği
4. **Enum/state vocabulary drift** — active, aktif, durum, status; kolon mevcut olsa bile anlam farklıysa schema check yakalayamaz
5. **Mass-assignment silent drops** — create()/fill() kullanıyor ama field $fillable içinde değilse hata vermeden eksik veri üretebilir
6. **Pivot write/read contracts** — Seeder pivot'a A kolonunu dolduruyor, relation withPivot(B) veya UI B okuyor olabilir
7. **Role ↔ Permission bootstrap completeness** — RoleSeeder yalnız role yaratıyorsa, permission setini kim oluşturuyor?
8. **Idempotent execution safety** — İkinci bootstrap mevcut business verisini canonical seed değerlerine geri zorlamamalı
9. **Clean-room bootstrap readiness** — Disposable boş DB'de canonical baseline + post-baseline migrations + canonical seeders gerçekten sıfırdan ayağa kalkabilmeli
10. **Schema checkpoint / migration boundary integrity** — Baseline değişmiş mi? Pre-baseline migration yanlışlıkla devreye giriyor mu?
11. **Foreign-key orphan detection** — FK kapalı/gevşek test ortamında orphan üretebilir; relation'lar gerçekten resolve edilmeli
12. **Hidden config/env dependency inventory** — ADMIN_LOCAL_PASSWORD gibi gizli env/config önkoşulları clean bootstrap'ta patlar

### Otomasyon

| Katman | Mekanizma | Kapsam |
|---|---|---|
| **Sentinel / FAST** | Statik, ucuz — AST + schema check | Seeder missing column, stale pivot field, boundary ihlali |
| **Doktor / DEEP** | Disposable DB clean-room bootstrap + runtime contract test | Full chain validation |
| **Bekçi** | Gözlem/telemetry | Production/local bootstrap motoru değil; monitoring tarafında kalır |

**Yeni "Seeder Guard" motoru kurulmaz.** Mevcut Sentinel + Doctor + Bekçi yapısına compose edilir.

### Model ↔ Migration ↔ Relation Contract Guard İlişkisi

Model ↔ Migration ↔ Relation Contract Guard backlog adayı, CANONICAL_BOOTSTRAP_INTEGRITY invariant'ın önemli bir alt kümesini kapsar. Ayrı bir sistem yaratmak yerine mevcut Guard yapısına compose edilecek.


---

## Compose, Don't Duplicate Prensibi

- `verify-migration-boundary.sh` varken yeni migration scanner yazılmaz; compose edilir.
- `ModelSchemaContractTest` (556 assertions) varken yeni fillable/cast scanner yazılmaz; compose edilir.
- Her test ve script tek bir otoritedir; Sentinel ve Doktor bu mevcut araçları orkestre eder.

---

## Kaynak ve Tarihçe

- **Mimari Karar:** Ayhan + Antigravity IDE + Cline
- **Tarih:** 2026-09-26
- **İlgili:** AGENTS.md, DECISION_LOG.md (ADR #008), EVIDENCE_INDEX.md
