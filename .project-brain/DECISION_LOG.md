## [2026-09-22] AI_TELEMETRY_ARG_MISMATCH_REMEDIATION_02 — BLOCKED

- **Task:** `AI_TELEMETRY_ARG_MISMATCH_REMEDIATION_02`
- **Actor:** IMPLEMENTER (Cline → Claude Opus 4.7)
- **Decision:** STOP — BLOCKED
- **Reason:** `DeepSeekCortexProvider.php:78` non-2xx `logFailure()` branch is dead code — Laravel 10 `Http::retry(3, 100, callback)` converts non-2xx to `RequestException` before `$response->failed()` guard executes. Fix scope forbids retry modification → regression test cannot reach fixed code path.
- **Bypass attempted:** None — blocking condition is architectural, not workaround-eligible
- **Evidence:** `EVIDENCE_INDEX.md`, `KNOWN_ISSUES.md`
- **Next action:** Escalate to Ayhan for architectural decision on retry strategy change

---


# DECISION LOG — Yalıhan OS

Mimari kararlar, bypass理由 ve kapsam değişiklikleri bu dosyada kaydedilir.

---

## Karar #007 — 2026-09-20

**Konu:** RC2 Production Release Closure

**Gerekçe:**
- 107 commitlik RC2 production release'i `e346660c` commit'inde tamamlandı
- Bounded migration-contract remediation (`2026_09_06_000001_add_ilceler_il_id_foreign_key.php`) independently verified (`PASS`)
- 4 adet release migration sorunsuz çalıştı
- Bağımsız READ-ONLY production verification (`RC2_PRODUCTION_INDEPENDENT_VERIFY_04`) tüm kapılarda PASS verdi

**Karar:**
- RC2 release `CLOSED` ve production `PRODUCTION_VERIFIED` olarak mühürlendi
- `ilceler.il_id` FK canonical `CASCADE` policy korundu
- TalepCreate business workflow `UNKNOWN` kalarak kapatıldı (production'a sentetik veri yazılmadı)
- ADR #006 ve mevcut anayasa kararları korundu

**Sahip:** Primary Session (Ayhan onayıyla)

---

## Karar #001 — 2026-09-06

**Konu:** Context Cache Manager Skill Oluşturulması

**Gerekçe:**
- Her yeni oturumda aynı doğrulama komutları tekrar çalışıyordu
- Token maliyeti gereksiz yere yüksek
- `.project-brain/` dosyaları zaten mevcut ama sistematik kullanılmıyordu

**Karar:**
- `.clinerules` §10'a `context-cache-manager` skill tanımı eklendi
- Cache write: her material görev sonunda EVIDENCE_INDEX + PROJECT_STATE + DECISION_LOG güncellenir
- Cache read: oturum başında ve kullanıcı geçmiş sorduğunda cache okunur
- Token hedefi: cache hit < 100 token, cache miss ~1,000-2,000 token

**Etki:**
- %90+ token tasarrufu hedefi (yeni oturumlar için)
- Evidence kayıtları commit bazlı ve doğrulanmış bilgi içerir

**Sahip:** Codex

---

## Karar #002 — 2026-09-06

**Konu:** `7f467b8a` Handoff — TEST_VERIFIED Label Uygulaması

**Gerekçe:**
- `agent-handoff-verifier` + `api-contract-regression-guard` + `test-fixture-integrity-checker` üçü de temiz döndü
- Evidence kaydı commit bazlı, komut çıktıları ve dosya satır referansları ile dokümante edildi
- Production doğrulaması ayrı kapsam olarak kapalı kaldı

**Karar:**
- Label: `TEST_VERIFIED` — commit `7f467b8a`
- Tüm kanıtlar `.project-brain/EVIDENCE_INDEX.md`'ye kaydedildi
- Production deploy: ayrı yetki gerektirir

**Sahip:** Codex

---

## Karar #003 — 2026-09-06

**Konu:** P4 Location Research — `ilceler→iller` FK eksikliği kararı

**Gerekçe:**
- FK constraint MySQL schema'da tanımlı değil — MEDIUM risk
- Tüm referans veren tablolarda 0 kayıt — LOW mevcut impact
- `ilceler` tablosunda orphan `il_id` değerleri riski mevcut
- TKGM polygon persistence ve cross-table spatial query'ler için FK şart

**Karar:**
- `ReconcileLocationsCommand` çalıştırılıp orphan durumu doğrulanacak (OPERATOR yetkisi)
- Orphan doğrulaması sonrası `ilceler→iller` FK constraint migration'ı eklenecek
- `bina_yasi` migration backward compatible — DOKÜMANTE EDİLDİ, işlem gerekmiyor
- PHASE2-ROADMAP.md P4 RESEARCH COMPLETE olarak güncellendi

**Sahip:** Kodex (Architect)

---

## Karar #005 — 2026-09-11

**Konu:** P4 Location Reconciliation — Command Onarım ve Canlı Veri Doğrulaması

**Gerekçe:**
- `ReconcileLocationsCommand` `BadMethodCallException` veriyordu — `canonicalMahalleler()` metodu tanımsızdı
- `bodrumMahalleler()` yanlışlıkla `array` return type ile tanımlanmıştı, gerçekte `Collection` döndürüyordu
- MySQL canlı veritabanında orphan FK durumu doğrulanmadı

**Bulgu:**
- MySQL `yalihanai_clone`: iller=81, ilceler=13, mahalleler=20, orphan ilceler=**0**
- `ilanlar`: 47 kayıt (33 Muğla, 4 Adana, 1 İstanbul) — tüm FK temiz
- `talepler`, `proj_listings`: 0 kayıt
- MySQL 9.3.0 — full spatial extension desteği mevcut

**Karar:**
- `canonicalMahalleler()` çağrısı → `bodrumMahalleler()` olarak düzeltildi (satır 121, 148)
- `bodrumMahalleler()` return type `array` → `\Illuminate\Support\Collection` olarak düzeltildi
- `count()` → `->count()` olarak güncellendi
- `php artisan location:reconcile --pretend` temiz çalışıyor → **0 orphan**
- TKGM polygon persistence: `tkgm_parcel_geometries` tablosu gelecek sprint'e ertelendi (mevcutta parsel verisi yok)

**Etki:**
- Commit: `8999a588`
- Command artık idempotent pretend/dry-run/apply döngüsü tamamlayabilir

**Sahip:** Kilo

---

## Karar #004 — 2026-09-09

**Konu:** SEC-08 — V2 API Route Model Binding + TenantScope fail-closed 404

**Gerekçe:**
- V2 `PUT /api/v1/ilanlar/{id}` ve türevi endpoint'lerde (delete/publish/unpublish) 404 dönüyordu
- Kök neden: Laravel Route Model Binding, controller method'undan **önce** çalışır
- `V2\Ilan` modeli `BelongsToTenant` trait kullanıyor → `TenantScope` global scope ekliyor
- Test ortamında `TenantContextService::hasTenant() = FALSE` → `TenantScope` `WHERE 1 = 0` ekliyor (fail-closed)
- Sonuç: `ModelNotFoundException` → 404 — controller'a ulaşamıyor

**Karar:**
- `update`, `destroy`, `publish`, `unpublish` method'larında implicit route model binding (`Ilan $ilan`) yerine explicit query:
  ```php
  $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);
  if (!$ilan) { return response()->json(['message' => 'İlan bulunamadı'], 404); }
  ```
- `show()` method'unda zaten aynı pattern mevcuttu — diğer method'lara de uygulandı
- `authorizeIlanAccess()` auth kontrolü `find()` sonrası çalışmaya devam eder → 404/403 doğru döner
- `TenantScope` global scope'u kaldırılmadı — fail-closed koruması yerinde kaldı

**Etki:**
- Test: 5/5 PASS (`V2RouteBindingCountryScopeTest` + `V2IlanAuthResearchTest`)
- Commit: `83dd1e8a` (`release-candidate/RC2`)

**Önlem:** Yeni V2 controller method'ları yazılırken `show()` pattern'i referans alınmalı

**Sahip:** Kilo

---

## Yeni Çalışma Protokolü — Kök Neden Düzeltmesi

**Tarih:** 2026-09-13
**Tetikleyici:** SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 ve RC2 dirty envanter analizi

### Tespit Edilen Kök Neden

Mevcut çalışma biçimi:
```
Önce değişiklik
sonra test
sonra etki ve yetki araştırması
```

Parçalanmanın görünen sonuçları:
- Birden fazla form alanı kaynağı (legacy resolver, feature assignments, Smart Forms, Domain policy)
- Tenant kolonu yazma/sorgu/rollback zincirinde her yerde zorunlu değil
- API route'ları auth/role/tenant bağlamından geçmeden yazma yapabiliyor
- Migration, seeder ve model aynı veriyi farklı niyetlerle yönetiyor
- Dirty worktree'lerde paket sahipliği belirsiz
- Gate geçişi gerçek güvenlik veya runtime doğrulaması sayılmıyor

### Karar: Olması Gereken Çalışma Biçimi

```
Önce owner + contract + tenant + rollback
sonra izole worktree
sonra değişiklik
sonra gerçek runtime testi
```

Dört somut hedef:

**1. Açık API yazma kapılarını güvene almak**
→ Her public write endpoint: auth + tenant.context + role + engine-level guard
→ Endpoint açılmadan önce tüm savunma hatları tasarlanmış olmalı

**2. Tenant/global veri ayrımını kesinleştirmek**
→ tenant_id nullable + unique constraint her yerde
→ Global yazma: yalnız platform-schema yetkisiyle
→ Her sorgu ve rollback zinciri tenant context zorunlu

**3. Migration–seeder–runtime için tek veri sözleşmesi kurmak**
→ Aynı veri için üç kaynak: migration (schema), seeder (seed verisi), model (Eloquent)
→ Bu üçü birbirine bağlıdır — biri değişince diğerleri otomatik review edilmeli
→ Tek veri sözleşmesi: migration comment'inde version, seeder ve model buna referans verir

**4. Agent'ların yalnız sahipli, izole paketlerde çalışmasını zorunlu yapmak**
→ Her görev: sahip atanmış, ayrı worktree, dar kapsam
→ GÖREV AÇILMADAN ÖNCE: owner + contract + rollback planı + pre_write_gates tamamlanmış olmalı
→ Dirty worktree'ler: sahip bilinmeyen değişiklikler karantinaya alınır

### Bu Kararın Önceliklendirdiği Görevler

Bu protokol oturunca güvenle hızlanır:
- Wizard pipeline (FeatureTemplateResolver → Domain Policy → Smart Forms)
- Feature Catalog
- Domain geçişleri

### Bu Kararın Önceliklendirmediği Görevler

Yeni özellik geliştirme — bu protokol oturana kadar beklemede kalabilir.

### Önlem: Tüm Future Görev Taleplerinde Zorunlu Sorular

Her yeni görev açılmadan önce:
1. Veri sözleşmesi (contract) nedir — kim, neyi, hangi tenant context'le yazar?
2. Rollback nedir?
3. Hangi worktree / kimin sahipliği?
4. Pre-write gate'leri neler?

Bunlara cevap verilmeden kod yazılmaz.

---

## BEKCI-IMMUNE-V1: Bekçi Öğrenme Döngüsü Mimari Kararı

**Tarih:** 2026-09-14  
**Karar verici:** Yalıhan OS Arch + User  
**Durum:** KABUL EDİLDİ — uygulama ayrı worktree'de yapılacak  
**Worktree önerisi:** `worktree-bekci-immune/feature/BEKCI-IMMUNE-V1`

### Problem

Bekçi mevcut durumda incident'ları hafızaya almıyor, benzer hataları sınıflandırmıyor, ve yeni kural önerisi üretmiyor. Her hata yeniden keşfediliyor. Self-healing mekanizması yok.

### Çözüm: 5'li Öğrenme Döngüsü

```
Incident Memory
    ↓
Bug Class Registry
    ↓
Rule Proposal Engine
    ↓
Approval Gate
    ↓
Permanent Guard (test + invariant + gate)
```

### V1 Entity Tasarımı

| Entity | Sorumluluk | Key Alanlar |
|--------|-----------|-------------|
| `BekciIncident` | Olay kaydı + kök neden + kanıt | `bug_class_id`, `root_cause`, `evidence_path`, `regression_count` |
| `BekciBugClass` | Hata sınıfı + invariant tanımı | `slug`, `invariant_rule`, `severity`, `occurrence_count` |
| `BekciRuleProposal` | Önerilen sistem kuralı | `bug_class_id`, `proposed_rule`, `rationale`, `gate_type` |
| `BekciApprovalGate` | State machine | `proposal_id`, `state` (pending/approved/rejected), `approver`, `decision_at` |
| `BekciPermanentGuard` | Onaylı kuralın somut karşılığı | `approval_id`, `guard_type` (test/invariant/gate), `guard_ref` (test dosyası / gate name) |

### CLI Yüzeyi

```bash
php artisan bekci:incident:add           # Yeni incident kaydı
php artisan bekci:learn                  # Bug class → rule proposal üret
php artisan bekci:proposals               # Bekleyen önerileri listele
php artisan bekci:proposal:approve {id}   # Öneriyi onayla (test + guard oluştur)
php artisan bekci:proposal:reject {id}   # Öneriyi reddet
php artisan bekci:guards                   # Kalıcı guard'ları listele
php artisan bekci:class {slug}           # Bug class detayı + incident history
```

### Temel Prensipler

1. **Model ağırlığı değiştirmek değil** — sistemin geçmiş hataları kurala dönüştürmek
2. **Approval gate zorunlu** — öğrenme otomatik aktif olmaz
3. **Regression detection** — aynı bug class tekrar oluşursa `regression_count++`, öncelik artar
4. **Tam izlenebilirlik** — incident → bug class → proposal → approval → guard
5. **Production kuralı Bekçi tek başına değiştirmez** — approval gerekir

### Kapı Tipleri (Guard Type)

| Type | Açıklama | Örnek |
|------|----------|-------|
| `test` | Regression testi | `tests/Bekci/Regression/BugClass_XYZ_Test.php` |
| `invariant` | AST invariant | `SabIntegrityScanCommand` yeni kural |
| `gate` | CLI gate | `antigravity-*` script yeni kontrol |

### Veri Depolama

JSON dosya tabanlı (migration gerektirmez, hızlı prototipleme):

```
storage/bekci/
├── incidents/          # BekciIncident JSON'ları
├── bug-classes/        # BekciBugClass JSON'ları
├── proposals/          # BekciRuleProposal JSON'ları
├── approvals/          # BekciApprovalGate JSON'ları
└── guards/             # BekciPermanentGuard JSON'ları
```

### Uygulanmayacaklar (V1 scope dışı)

- AI/LLM tabanlı otomatik rule generation (agentic değil, human-in-the-loop öncelikli)
- Multi-tenancy derinliği (tek repo odaklı)
- Otomatik rollback (approval + manual review gerekir)

### Bu Kararın Önceliklendirdiği Görevler

- `BEKCI-IMMUNE-V1` worktree'sinde 5 entity + 6 CLI komutu implementasyonu

### Referans

- User onay: 2026-09-14 bu sohbet
- Mimari: Yalıhan OS Arch

---

## Karar #006 — 2026-09-17

**Konu:** Proje Domain Bounded Context Ayrımı (`Takım Projesi` ≠ `Emlak Projesi`)

**Gerekçe:**
- Phase 1A & 1B repo discovery ve derin izleme sonuçlarına göre:
  1. **Takım Projesi:** Fiziksel `projeler` tablosuna dayanır, `gorevler.proje_id` (FK) ile bağlıdır, `App\Models\Proje` canonical modeline sahiptir ve aktif UI/API rotalarına bağlıdır (`/admin/takim-yonetimi/projeler`).
  2. **Emlak Projesi:** `App\Modules\Emlak\Models\Proje` taslağına dayanır, veritabanında karşılığı olmayan kolonlar (`gelistirici_adi`, `adres_il`, `lat`, `lng`) ve tablolar (`proje_translations`, `proje_gorselleri`) bekler; aktif HTTP rotası bulunmamaktadır.
  3. `ilanlar.proje_id` alanı şu an fiziksel `projeler` (Takım Yönetimi) tablosuna bakmaktadır; semantik data migration riski içermektedir.

**Status:** `IMPLEMENTED_AND_PRODUCTION_VERIFIED` (Canonical Commit: `3ced67c16f228f50ba1b375a0b15fcd9acf00718`)

**Mimari Durum Ayrımı (Master Truth Principle):**

```text
PRODUCTION_VERIFIED (2026-09-18)
  Takım Projesi:
    - Table: projeler
    - SSOT Model: App\Models\Proje
    - Foreign Key: gorevler.proje_id → projeler.id

  Emlak Projesi:
    - Table: emlak_projeleri (created via migration 2026_09_17_000001)
    - Model: App\Modules\Emlak\Models\Proje ($table = 'emlak_projeleri')
    - Relation: Ilan::proje() → emlak_projeleri.id (ilanlar.proje_id added via migration 2026_09_17_000002)
    - Production Status: ADR006_PRODUCTION_VERIFIED
```

**Karar Kuralları:**
1. **Bounded Context Ayrımı:** `Takım Projesi` ve `Emlak Projesi` iki tamamen ayrı domain entity'si olarak tanımlanmıştır.
2. **Takım Projesi Persistence:** `projeler` tablosu ve `App\Models\Proje` canonical authority olarak korunmuştur.
3. **Emlak Projesi Persistence:** Emlak Proje domaini için `emlak_projeleri` adıyla ayrı tablo ve ilişki kolonları `ilanlar.proje_id` oluşturulmuştur (`PRODUCTION_VERIFIED`).
4. **Sınıf Adlandırma Güvenliği:** PHP sınıflarında yüksek riskli toplu yeniden adlandırmadan kaçınılmış; `App\Models\Proje` Takım Yönetimi SSOT authority olarak bırakılmıştır.
5. **Phase 1C & Production Compliance:** Path-constrained migrationlar production MySQL DB'de çalıştırılmış ve container DB sorgularıyla doğrulanmıştır.

**Sahip:** Arch & User



---

## Karar #007 — 2026-09-17

**Konu:** G2.3 Router'ın Canonical Operational Routing Contract Olarak Kabul Edilmesi

**Gerekçe:**
- G2.3 adli pilot çalıştırması başarıyla tamamlanmış ve `TEST_VERIFIED / TOOL_RUNTIME` seviyesinde ampirik kanıt üretilmiştir.
- Router zinciri (`Task → Classification → Task Contract → Role/Executor/Skill → Governance Pointers → Parent→Subagent Handoff → Bounded Execution → STOP`) doğrulanmıştır.

**Karar:**
- `docs/architecture/AGENT_SKILL_ROUTER.md` adresi Human Decision Owner Ayhan tarafından **Canonical Operational Routing Contract** olarak kabul edilmiştir.
- **Authority Source: NO.** Router yeni bir Otorite Kaynağı değildir. Yetki hiyerarşisi ve anayasa zinciri `AGENTS.md` ve `.sab/authority.json` üzerinde aynen kalır.
- Router'ın görevi yalnız: `CLASSIFY → ROUTE → CONSTRAIN → HANDOFF` yapmaktır.

**Decision Owner:** Ayhan  
**Session Owner:** Antigravity Parent Agent  
**Evidence:** `TEST_VERIFIED / TOOL_RUNTIME`  

---

*Son güncelleme: 2026-09-17*

---

## PRENSİP #11 — Domain Convergence Contract (2026-09-23)

**Karar Sahibi:** Ayhan
**Kaynak:** TASK_34 retrospektif — görev tamamlandıktan sonra human review sırasında oluşturuldu
**AGENTS.md:** Rule 14 (canon kaynak)
**Etki:** Tüm gelecek remediation görevleri (_35, _36, _37, _38, ...)

### Temel Özet
"Fix tamamlandı" artık yalnız yeni kodun çalışması anlamına gelmiyor.
Her görevde hedef: ilgili domain/surface'i **tek canonical akışa** indirmek.

### ⭕ Primary Fix Scope + Cleanup Radius
Her görev iki kapsamla tanımlanır:
- **Primary Fix Scope:** agent task contract'ta "Files Allowed to Modify"
- **Cleanup Radius:** Canonical path'ten bağımsız olarak etkilenen alan
  Örnek: International = route → controller/service → model/query → Blade → component/assets → tests
- Cleanup Radius **dışında:** CRM, finans, Hermes, auth — forensics bataklığına girilmez

### 12 Alan Araştırılır (Cleanup Radius içinde)
1. **Authority Convergence** — Aynı kavramın iki otoritesi olmamalı. SOURCE_OF_TRUTH_COUNT > 1 = cleanup debt
2. **Data-Contract Drift** — Model↔Migration↔Enum↔Request↔Controller↔UI aynı dili konuşmalı
3. **Fallback Audit** — REQUIRED | SAFE | LEGACY | MOCK | MASKING_FAILURE sınıflandırması zorunlu. MOCK production UI'da kalmamalı
4. **Placeholder/Test-Data Leakage** — test, demo, lorem ipsum, fake data, href="#", dummy değerler taranmalı
5. **Route/API Convergence** — /v1–/v2 kalıntıları, eski route names, redirect zincirleri
6. **Frontend Asset Convergence** — Blade düzeltip eski CSS/JS bırakılmamalı. Vite, inline styles, legacy scripts
7. **Dependency Hygiene** — composer.json/npm'de var ≠ gerekiyor. Import+runtime+build kullanımı doğrulanmalı
8. **Database Residue** ⛨ — Column/table/FK silmeden önce model+query+runtime+production araştırması. Ayrı Human Gate zorunlu
9. **Error-State Integrity** — empty/partial/error/offline durumları da canonical contract'ın parçası
10. **Security Residue** — Auth/authorization/tenant isolation cleanup sırasında kaybolmamalı. Duplicate endpoint = güvenlik riski olabilir
11. **Observability Residue** — Log channel, event, metric, scheduler eski path'i izliyor olabilir
12. **Documentation Truth** — Authoritative doküman güncellenmeli; tarihsel kanıt silinmemeli ama PROJECT_STATE/EVIDENCE_INDEX çelişmemeli

### 🛡️ SAFE_REMOVAL_EVIDENCE Kanıt Paketi
Bir artifact'ı silmek için tek grep sonucu YETMEZ. 8 noktada negatif kanıt:
route_reference | import_reference | blade_include | container_binding | event_job | build_entry | test_dependency | runtime_reference
→ SAFE_REMOVAL: ≥5 NO | PROBABLE_REMOVAL: ≥3 NO (WHY kalanlar dokümante edilmeli)
→ Hiçbir kanıt toplanamıyorsa → UNKNOWN_USAGE → silinmez

### 🔄 Replacement-Before-Deletion Protokolü
Invariant: Canonical replacement, eski davranışın gerekli kısmını karşılıyor mu?
Sıra: Discover → Classify → Establish Canonical Authority → Fix/Converge → Regression → Prove Replacement → Remove Legacy → Regression Again → Independent Verify

### 📊 Final DoD Raporu
IMPLEMENTER/VERIFIER şunları raporlar:
DOMAIN_STATE: CANONICAL_CLEAN | WITH_DOCUMENTED_LEGACY | FUNCTIONALLY_FIXED_CLEANUP_REMAINS | BLOCKED
+ SOURCE_OF_TRUTH_COUNT, LEGACY_PATHS, DUPLICATE_IMPL, PROVEN_ORPHANS,
  MOCK_RESIDUE, FALLBACKS, ROUTE_API_DRIFT, MODEL_SCHEMA_DRIFT,
  DESIGN_SYSTEM_DRIFT, SECURITY_BOUNDARY_REGRESSION, OBSERVABILITY_ALIGNMENT,
  REGRESSION, RUNTIME, PRODUCTION

"Test geçti" ≠ "bu domain gerçekten toparlandı" — CANONICAL_CLEAN kapanış kriteridir.

### Örnek Uygulama Senaryosu
> International'ı düzeltirken artık sadece query düzeltilmez.
> Eski `ulke_id` yolu + `yurt-disi` category yolu + keyword araması + hardcoded fallback + mock yield kartları birlikte incelenir.
> Canonical belirlendikten sonra eski mekanizma gerçekten gereksizse bırakılmaz.

**Evidence:** Ayhan onayı 2026-09-23 + AGENTS.md commit 840e7f2a

---

## PRENSİP #12 — CANONICAL_BOOTSTRAP_INTEGRITY (2026-09-28)

**Karar Sahibi:** Ayhan
**Kaynak:** TENANT_CANONICAL_AUTHORITY_RESOLVE_01 retrospektif — TenantBaselineSeeder forensics sonucu
**AGENTS.md:** Rule 14 (canon kaynak)
**Etki:** Tüm gelecek bootstrap, seeder, migration ve model canonicalization görevleri

### Temel Özet

YALIHAN OS, canonical baseline'dan temiz bir veritabanına deterministik olarak kurulabilmeli; oluşan veri Model ve Runtime tarafından aynı anlamla okunabilmeli.

Seeder hiçbir zaman bağımsız schema veya business authority değildir. Her canonical seeder'ın yazdığı tablo, kolon, ilişki, state ve identifier; canonical physical schema, migration boundary, model/relation contract ve effective runtime authority ile uyumlu olmalıdır.

**Tam authority zinciri:**

```
Domain Authority → Physical Schema → Migration Lineage → Seeder/Bootstrap → Model/Relations → Runtime Consumers → Tests → Production
```

**Tenant vakası örneği:**

TenantBaselineSeeder `uuid` + `status` yazıyordu. Ama physical schema `durum` bekliyordu, `uuid` kolonu yoktu. Runtime middleware `App\Models\SaaS\Tenant` kullanıyordu — fillable'da `status` vardı. Mevcut `App\Models\Tenant` fillable'da `durum` vardı — schema-uyumlu ama runtime'da aktif değildi. → Seeder otorite değildir. Önce runtime authority, sonra model, sonra schema, sonra seeder kontrol edilir.

### 5 Zorunlu Invariant

**1. SEEDER_IS_NOT_AUTHORITY**
Seeder schema/model/runtime'dan bağımsız ikinci truth oluşturamaz. Seeder bir authority değildir; physical schema ve runtime authority uyumlu olmalıdır. Seeder ancak o uyuma hizmet eder.

**2. WRITE_READ_CONSISTENCY**
Seeder'ın yazdığı field/state/pivot, canonical model ve runtime'ın okuduğu contract ile aynı olmalıdır. Field name drift, state vocabulary drift, pivot column drift. Seeder otoritesi değildir — model ve runtime otoritedir.

**3. MIGRATION_BOUNDARY_CONSISTENCY**
Physical baseline otoritedir. Post-baseline migration yalnız forward evolution'dır. Historical migration yeniden runtime authority olamaz. İki migration aynı tabloyu farklı schema ile oluşturmaya çalışıyorsa → authority çatışması.

**4. DETERMINISTIC_CLEAN_BOOTSTRAP**
Disposable boş DB: canonical baseline → post-baseline migrations → canonical seeders → valid runtime-readable state üretebilmelidir. Bu invariant clean-room bootstrap doğrulaması gerektirir; mevcut local DB'nin tarihsel kalıntıları sonucu maskelemez.

**5. NON_DESTRUCTIVE_IDEMPOTENCY**
Seeder tekrar çalıştığında duplicate/orphan üretmemeli ve mevcut business verisini yanlış lookup/ID varsayımıyla sessizce değiştirmemeli. updateOrInsert/updateOrCreate kullanımında lookup key doğru olmalıdır.

### 12 Kontrol Noktası (Clean-Room Audit için)

1. Seeder execution order / FK dependencies
2. updateOrInsert/updateOrCreate destructive potential
3. Hard-coded numeric ID assumptions
4. Enum/state vocabulary drift
5. Mass-assignment silent drops
6. Pivot write/read contracts
7. Role ↔ Permission bootstrap completeness
8. Idempotent execution safety
9. Clean-room bootstrap readiness
10. Schema checkpoint / migration boundary integrity
11. Foreign-key orphan detection
12. Hidden config/env dependency inventory

### Otomasyon Kararı

| Katman | Mekanizma | Kapsam |
|---|---|---|
| Sentinel / FAST | Statik, ucuz — AST + schema check | Seeder missing column, stale pivot field, boundary ihlali |
| Doctor / DEEP | Disposable DB clean-room bootstrap + runtime contract test | Full chain validation |
| Bekçi | Gözlem/telemetry | Production/local bootstrap motoru değil; monitoring tarafında kalır |

**Yeni "Seeder Guard" motoru kurulmaz.** Mevcut Sentinel + Doctor + Bekçi yapısına compose edilir.

### Model ↔ Migration ↔ Relation Contract Guard İlişkisi

Model ↔ Migration ↔ Relation Contract Guard backlog adayı, CANONICAL_BOOTSTRAP_INTEGRITY invariant'ın önemli bir alt kümesini kapsar. Ayrı bir sistem yaratmak yerine mevcut Guard yapısına compose edilir.

**Evidence:** Ayhan onayı 2026-09-28 + DECISION_LOG.md commit. Kaynak: TENANT_CANONICAL_AUTHORITY_RESOLVE_01 forensic sonucu (CDA-006)

---

## BEKCI v3 5-CAPABILITY ARCHITECTURE — 2026-10-03

**Session:** BEKCI_ENFORCEMENT_REALITY_CHECK_01 + AYHAN_ARCHITECTURE_FEEDBACK
**Kaynak:** Ayhan'ın 5 canonical Bekçi capability vizyonu + 10 yeni koruma önerisi

### 1. Mimari Kararlar

**Nihai Cümle (Ayhan):**
> "Bir değişiklik production'a ulaşmadan önce Bekçi 'ne değişti, neyi etkiliyor, hangi canonical authority'ye bağlı, hangi invariant'ları geçti, hangi istisnaları kullandı ve production'ın hangi execution surfaces'ında hangi release çalışıyor?' sorularının tamamına makine-okunabilir cevap verebilmeli."

**Değişiklik Yapılmadı:**
- Silent Observer: Auto-block KESİNLİKLE YOK
- Deployment Readiness Score: Boolean gates, yüzde DEĞİL
- FULL_BLOCKING: Sadece zero legitimate legacy exceptions durumunda
- Rename/backup varsayılan DEĞİL: ADDITIVE first, DESTRUCTIVE last

### 2. Yeni Kavramlar

| Kavram | Kontrat | Kapasite |
|--------|---------|----------|
| Change Impact Graph | "Bu değişiklik başka neyi etkileyebilir?" | Architecture Integrity |
| Execution Boundary Registry | Default + explicit exception model | Tenant Contracts |
| Canonical Exception Registry | CE-001 ID'li, scoped, expires'li | Guard Integrity |
| Consumer Retirement Gate | Static+runtime usage verification öncesi cleanup | Release Integrity |
| Web/Worker/Scheduler Parity | Long-lived process release identity | Release Integrity |

### 3. Execution Boundary Registry Kontratı

```yaml
SURFACE: QUEUE
  default:
    tenant_context: REQUIRED_FOR_TENANT_BOUND_WORK
    cleanup: REQUIRED
    correlation: REQUIRED
  contracts:
    tenant_bound_job: TenantAwareJobInterface, RestoreTenantContext
  exceptions:
    - SystemRankingJob: system-wide
    - CacheWarmupJob: infrastructure

SURFACE: HTTP
  default:
    tenant_context: REQUIRED
    auth: REQUIRED
  exceptions:
    - /health: NONE
    - /api/v1/public/*: NONE
```

### 4. Rule Maturity Ladder

```
v1.0 DISCOVERY        → Reports only, no blocking
v1.1 OBSERVATION      → Reports, precision measured
v1.2 BASELINED        → NEW_CODE_ONLY blocking
v2.0 REGRESSION       → ALL code blocking (except legacy exceptions)
v2.1 FULL_BLOCKING    → Only when zero legitimate legacy exceptions
```

**Rule Metadata:**
```yaml
RULE_ID: FORBIDDEN_STATUS
VERSION: v1.2
MATURITY: BASELINED
PRECISION: HIGH
BASELINE: 16 violations
EXCEPTIONS: [CE-001, CE-002]
SELF_TEST_STATUS: PASS
BLOCKING_POLICY: NEW_CODE_ONLY
LAST_VERIFIED_SHA: 4287be8e
```

### 5. CDA-007 Cleanup Stratejisi

```
PRODUCTION READ-ONLY DISCOVERY
            ↓
DATA/WRITERS/READERS/USAGE MAP
            ↓
CANONICAL AUTHORITY DECISION
            ↓
ADDITIVE CONVERGENCE (first)
            ↓
ALL CONSUMERS → CANONICAL
            ↓
REGRESSION + INDEPENDENT VERIFY
            ↓
OBSERVATION WINDOW
            ↓
LEGACY COLUMN REMOVAL CANDIDATE
            ↓
HUMAN GATE
            ↓
DROP (last)
```

**Şu anda hiçbir legacy kolona dokunmuyoruz. Production schema UNKNOWN.**

### 6. Consumer Retirement Gate Kontratı

```yaml
LEGACY_ARTIFACT: tenants.status
STATIC_READERS: 2
ex: HuntOpportunitiesCommand + TenantSeeder
STATIC_WRITERS: 1
ex: TenantSeeder
RUNTIME_OBSERVED: 0  # Silent Observer
JOBS: 0
COMMANDS: 0
SEEDERS: 1
TEST_FIXTURES: 0
RETIREMENT_ELIGIBLE: NO
REASON: "HuntOpportunitiesCommand still uses status"
```

### 7. Contract Coverage Map

```yaml
TENANT_BOUNDARY:
  HTTP:      COVERED
  WEBHOOK:   COVERED
  QUEUE:     PARTIAL  # TenantAwareJobInterface ✓, cleanup ?
  CLI:       UNKNOWN
  SCHEDULER: UNKNOWN
  HERMES:    COVERED
  IMPORT:    LIMITED
```

### 8. Uygulama Öncelik Sırası (Ayhan)

8. Uygulama Öncelik Sırası (Ayhan)

1. NOW: Task 10 Production Audit (SSH)
2. NEXT: CDA-007 Read-only Discovery
3. NEXT: Guard Integrity (Blueprint precision, self-test, maturity, fingerprints, ratchet, exception registry)
4. NEXT: Change Integrity (Task Boundary, Dirty Tree, Ownership)
5. NEXT: Architecture Integrity (Impact Graph, Drift Propagation, Contract checks, Boundary Registry)
6. NEXT: Release Integrity (Fingerprint, Parity, Schema Classification, Consumer Retirement, Deploy State Machine)
7. LATER: Runtime Integrity (Exception/Fallback provenance, Silent Observer)
8. LATER: Agent Ergonomics (MCP discovery, capability handshake)

---

## BEKCI v3 — IMPLEMENTATION CONTRACTS (2026-10-03)

**Session:** Ayhan Architecture Review Round 2
**Kaynak:** 5 öneri karar toplantısı
**Prensip:** Yeni mimari doküman YOK. Mevcut v3'e implementation contract olarak absorbe edilecek.

### Mimari Özet Pipeline

```
BEKÇİ v3
CDA / existing evidence
       ↓
Change Impact View (READ-ONLY)
       ↓
Capability Evidence Pipeline
       ↓
Evidence-aware Guard Result
       ↓
Canonical Result Envelope
       ↓
CI / MCP / Release consumers
```

**Yeni engine YOK. Yeni authority YOK. Yeni paralel scanner YOK.**

### Karar Tablosu

| Öneri | Karar | Gerekçe |
|-------|-------|---------|
| #1 CDA + Impact Graph | ✅ KABUL | Paralel authority yaratmayı önler |
| #2 Capability Data Flow | ✅ KABUL | 5 capability'yi gerçek sistem haline getirir |
| #3 3-layer Precision | ⚠️ REVİZE | Katman bazlı evidence gerekli, matematiksel ağırlık gereksiz |
| #4 Maturity Formula | ❌ REDDET | Sayısal eşikler maturity gerçeğini temsil etmiyor |
| #5 Structured Output | ✅ KABUL | CI/MCP/release aynı sonucu tüketebilmeli |

---

### #1 — Change Impact View Kontratı

**Yanlış:** Ayrı analyzer/engine olarak Impact Graph
**Doğru:** READ-ONLY OUTPUT VIEW — mutation yapmaz, finding üretmez

**Kaynaklar (hepsi mevcut):**

```yaml
SOURCES:
  - Canonical Discovery / CDA
  - Migration Diff
  - Schema Drift
  - Changed Files (git diff)
  - AST Dependency Evidence

CONSTRAINTS:
  - READ_ONLY: true
  - MUTATION: false
  - FINDING_AUTHORITY: false
  - SECONDARY_TO: [CDA, Migration Audit, Sentinel, Doctor]
```

**Kritik:** Yoksa birkaç ay sonra CDA başka şey, Impact Graph başka şey söyleyebilir.

---

### #2 — Capability Evidence Pipeline (EN ÖNEMLİ MİMARİ EKSİK)

**Prensip:** 5 capability bağımsız araçlar DEĞİL — tek evidence pipeline.

```yaml
CHANGE INTEGRITY
  outputs:
    - changed_paths: Set<path>
    - declared_scope: ScopeContract
    - out_of_scope_changes: Set<path>
    - change_fingerprint: string
  CONSTRAINTS:
    - affected_tenants: PROHIBITED  # statik çıkarım yapamaz

ARCHITECTURE INTEGRITY
  consumes: [changed_paths, declared_scope, out_of_scope_changes, change_fingerprint]
  outputs:
    - affected_domains: Set<domain>
    - affected_contracts: Set<contract_id>
    - affected_surfaces: Set<SURFACE>  # HTTP/QUEUE/CLI/HERMES
    - canonical_authorities: Map<artifact, authority>
    - drift_candidates: Set<CDA>
    - unknown_dependencies: Set<path>

GUARD INTEGRITY
  consumes: [affected_domains, affected_contracts, canonical_authorities]
  outputs:
    - violations: Set<Violation>
    - exceptions: Set<Exception>
    - maturity: MaturityLevel
    - evidence: EvidenceMap
    - blocking_status: BLOCKING_STATUS

RELEASE INTEGRITY
  consumes:
    - verified gate results: [change_integrity, architecture_integrity, guard_integrity]
    - schema compatibility: SchemaCompatibility
    - release identity: ReleaseIdentity
    - worker_web_scheduler_parity: ParityCheck
  outputs:
    - decision: PASS | BLOCKED | HUMAN_GATE_REQUIRED

RUNTIME INTEGRITY
  role: FEEDBACK_LOOP  # pipeline'ın sonunda DEĞİL
  outputs:
    - runtime_evidence: RuntimeEvidence
  feeds_back_to: [architecture_integrity, release_integrity]
```

**Prensip:** Runtime Integrity "sonraki stage" DEĞİL — production/runtime evidence üretip Architecture ve Release Integrity'ye geri besleme yapar.

---

### #3 — Evidence-Level Precision Metadata (REVİZE EDİLDİ)

**Yanlış:** Ağırlıklı matematiksel skor (0.8, 0.6...)
**Doğru:** Katman bazlı evidence type + confidence + blocking eligibility

**Örnek — FORBIDDEN_STATUS_FIELD:**

```yaml
RULE: FORBIDDEN_STATUS_FIELD

MODEL:
  evidence: AST_INVARIANT
  level: REPO_VERIFIED
  confidence: VALIDATED

MIGRATION:
  evidence: DATABASE_SCHEMA_CONTRACT
  level: REPO_VERIFIED
  confidence: LIMITED  # Seeder ≠ physical schema

SEEDER:
  evidence: AST_INVARIANT
  level: REPO_VERIFIED
  confidence: DISCOVERY  # Henüz production doğrulaması yok

RUNTIME:
  evidence: TOOL_RUNTIME
  level: UNKNOWN  # Production schema bilinmiyor

BLOCKING_ELIGIBLE: NEW_REGRESSION_ONLY
```

**Kritik Nokta — STATIC MATCH ≠ RUNTIME VULNERABILITY:**

Bu, Filterable vakasında yaşanan hatayı önler. Blueprint'te `status` görülür — ama runtime'da farklı field'a map edilmiş olabilir.

**Blocking Eligibility Levels:**

```yaml
BLOCKING_ELIGIBLE:
  NONE              # Discovery — report only
  NEW_ONLY          # v1.2 — yeni kod block
  NEW_REGRESSION    # v2.0 — yeni + regression block
  ALL               # v2.1 — tüm kod block (zero legacy exceptions şartı)
```

---

### #4 — Maturity Promotion Criteria (RED — FORMULA DEĞİL)

**Red Gerekçesi:**

```yaml
# YANLIŞ:
IF violations >= 50 → v2.0

# Neden yanlış:
50 violation = rule olgun demek DEĞİL
50 violation = rule çok kötü durumda olabilir
Blueprint örneği bunun kanıtı
```

**Doğru — Promotion Criteria (Invariant-Based):**

```yaml
PROMOTION_REQUIREMENTS:

DISCOVERY → OBSERVATION:
  - SELF_TESTS_EXIST: true
  - FALSE_POSITIVES_CLASSIFIED: >= 80%

OBSERVATION → BASELINED:
  - SELF_TESTS_PASS: true
  - FALSE_POSITIVES_CLASSIFIED: 100%
  - KNOWN_EXCEPTIONS_EXPLICIT: true
  - BASELINE_STABLE: true  # violations > %5 değişim yok

BASELINED → NEW_REGRESSION_BLOCKING:
  - DETERMINISTIC_OUTPUT: true
  - REPRESENTATIVE_FIXTURES_PASS: true
  - NO_UNKNOWN_CRITICAL_BEHAVIOR: true

NEW_REGRESSION_BLOCKING → DOMAIN_BLOCKING:
  - DOMAIN_COVERAGE: COMPLETE
  - LEGACY_EXCEPTIONS_STABLE: true

DOMAIN_BLOCKING → FULL_BLOCKING:
  - LEGACY_EXCEPTIONS: 0
  - ALL_CONSUMERS_MIGRATED: true
  - PRODUCTION_VERIFIED: true
```

**Prensip:** Maturity = formül DEĞİL, invariant karşılandığında promotion.

---

### #5 — Canonical Result Envelope (ZORUNLU)

**Prensip:** Tek JSON dosyası YENİ AUTHORITY DEĞİL — tüm consumer'ların tükettiği kontrat.

```yaml
BEKCI_RESULT:
  run_id: string
  timestamp: ISO8601
  source_sha: string
  environment: LOCAL | STAGING | PRODUCTION
  mode: DISCOVERY | OBSERVATION | BLOCKING

  capabilities:
    change_integrity:
      status: PASS | FAIL
      outputs: {...}
    architecture_integrity:
      status: PASS | FAIL
      outputs: {...}
    guard_integrity:
      status: PASS | FAIL
      outputs: {...}
    runtime_integrity:
      status: PASS | FAIL | SKIPPED
      outputs: {...}
    release_integrity:
      status: PASS | FAIL
      outputs: {...}

  findings:
    - rule_id: string
      fingerprint: string
      evidence_level: REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED | DOCUMENTED | INFERRED | UNKNOWN
      evidence_type: AST_INVARIANT | DATABASE_SCHEMA_CONTRACT | TOOL_RUNTIME | MANUAL_AUDIT
      maturity: DISCOVERY | OBSERVATION | BASELINED | NEW_REGRESSION | DOMAIN | FULL_BLOCKING
      blocking: true | false
      exception_id: string | null
      location: path:line

  unknowns:
    - type: PRODUCTION_SCHEMA | RUNTIME_BEHAVIOR | DEPENDENCY
      description: string
      severity: BLOCKING | WARNING

  decision:
    PASS | BLOCKED | HUMAN_GATE_REQUIRED
    reasons:  # BLOCKED veya HUMAN_GATE_REQUIRED durumunda
      - code: string
        message: string
        requires_resolution: boolean

  HUMAN_GATE_REQUIRED:
    triggers:
      - PRODUCTION_SCHEMA_UNKNOWN
      - RUNTIME_BEHAVIOR_UNVERIFIED
      - CRITICAL_UNKNOWN_DEPENDENCY
    authorization: Ayhan | System | Human
```

**Kritik Karar — CONDITIONAL KULLANILMAZ:**

```yaml
# YANLIŞ:
decision: CONDITIONAL

# DOĞRU:
decision: BLOCKED
reason:
  code: PRODUCTION_SCHEMA_UNKNOWN
  message: "..."

# VEYA:
decision: HUMAN_GATE_REQUIRED
triggers:
  - PRODUCTION_SCHEMA_UNKNOWN
```

**Prensip:** Fail-closed. Çözülmemiş koşul varsa BLOCKED. İnsan override gerekirse HUMAN_GATE_REQUIRED.

---

### Uygulama Sırası (Değişmedi)

```
Task 10 → CDA-007 discovery → Guard Integrity implementation
                                      ↓
                    Contract'lar burada uygulanacak:
                    - #2 Capability Evidence Pipeline
                    - #5 Canonical Result Envelope
                    - #3 Evidence-Level Precision (revize)
                    - #1 Change Impact View (CDA'ya entegre)
```

**Şu anda YOK: Yeni mimari doküman, v3.1, v3.2**

Bu kontratlar Guard Integrity implementation başladığında uygulanacak.

---

### En Kritik Çıkarım (Ayhan)

> "#2 Capability Evidence Pipeline ve #5 Canonical Result Envelope, Bekçi'yi gerçekten parçalı script koleksiyonundan tek bir sisteme dönüştürecek iki unsur."

**Bekçi v3.1 yok. Bekçi v3 + implementation contracts var.**

---

## BEKÇİ v3 — DESIGN CLOSED / EXECUTION OPEN (2026-10-03)

**Session:** Ayhan Architecture Review Round 2 — FINAL
**Commit:** 84409735

### Evidence Classification

| Artifact | Evidence Level |
|----------|----------------|
| Bekçi v3 architecture + implementation contracts | `REPO_VERIFIED` |
| Bekçi v3 capability implementation'ları | `UNKNOWN` / `NOT_STARTED` |
| Production davranışı | `UNKNOWN` |

### Tasarım Kapanış Kararı

> "STOP DESIGN / START EXECUTION. Yeni öneri eklemiyorum."

**84409735 artık implementation'ın contract referansı:**
- Self-tests
- Rule maturity
- Fingerprint
- Ratchet
- Exception registry
- Canonical Result Envelope

### Implementation Sırası (Değişmedi)

```
Task 10 Production Read-Only Audit
         ↓
   (SSH erişimi resume)
         ↓
CDA-007 Read-Only Discovery
         ↓
   (consumer/data haritası çıkınca)
         ↓
Guard Integrity implementation
```

### Hard Stops

1. **Task 10:** Audit tamamlanıp compatibility sonucu çıkmadan migration/deploy YAPILMAYACAK
2. **CDA-007:** Status/durum/aktiflik_durumu için gerçek consumer/data haritası çıkmadan legacy kolonlara dokunulmayacak
3. **Guard Integrity:** 84409735 contract referansı olarak uygulanacak

### Durum

```
BEKÇİ v3 DESIGN   ████████████████████ 100% — CLOSED
BEKÇİ v3 EXECUTION                  █░░░░░░░░░░░░░░░░  0% — STARTING

---

## BEKÇİ v3 — VİZYON VE ÇALIŞMA PRENSİPLERİ (2026-10-03)

**Kaynak:** Ayhan — "Bekçi bir AI agent olmayacak"

### Temel Prensip

Bekçi şunlara GÜVENMEYECEK:
- Cline "iyi" dedi
- Claude "doğruladım" dedi
- Antigravity "geçti" dedi
- Ayhan "kontrol ettim" dedi

Bekçi SADEce **executable technical evidence** üzerinden karar verecek.

### Bekçi Ne Yapmaz

| YAPMAZ | NEDEN |
|--------|-------|
| Kod yazmaz | Tamirci Cline'ın işi |
| Karar sahibinin yerine geçmez | Karar Ayhan'ın |
| Production'ı kendi başına değiştirmez | Human gate + Ayhan override |
| AI agent değildir | Otomatik teknik denetim sistemi |

### Bekçi Ne Yapar

1. **Kanıt toplar** — git diff, AST, schema, runtime evidence
2. **Canonical contract'larla karşılaştırır** — v3 implementation contracts
3. **İhlali sınıflandırır** — rule_id, fingerprint, evidence_type, maturity, exception_id
4. **Sonuç üretir** — PASS | BLOCKED | HUMAN_GATE_REQUIRED

### Çalışma Zamanları

```
A — Agent görev başlamadan önce
   bekci task:start
   → BASE_SHA, dirty_tree, declared_scope, canonical_state kaydedilir

B — Agent değişiklik yaptıktan sonra (FAST kontroller)
   → scope, secrets, schema_parity, routes, AST_invariants, new_violations

C — Commit öncesi (PRE-COMMIT BEKÇİ)
   → NEW regression var mı?
   → Scope dışı değişiklik var mı?
   → Secret var mı?
   → Canonical contract bozuldu mu?
   → Kritik problem varsa commit DURDURULUR

D — GitHub CI
   → Local hook bypass edilse bile aynı kontroller CI'da tekrar çalışır
   → Cline, Claude, Antigravity, Ayhan — kim yazmış olursa olsun aynı kurallar

E — Deploy öncesi (RELEASE INTEGRITY)
   → source SHA, migration compatibility, critical workflows
   → schema expectation, release fingerprint, worker compatibility
   → rollback readiness
   → UNKNOWN varsa → BLOCKED veya HUMAN_GATE_REQUIRED

F — Production'da (SİLENT OBSERVER)
   → Tenant violation? Queue failure? Fallback? Schema mismatch?
   → Exception? Release mismatch?
   → Sadece gözlemler ve raporlar
   → KENDİ BAŞINA DEĞİŞTİRMEZ
```

### Worker/Scheduler Parity (Production)

```
WEB SHA       abc123
WORKER SHA    abc123
SCHEDULER SHA abc123
SCHEMA        compatible

PRODUCTION RELEASE = VERIFIED
```

**Kritik:** Laravel queue:work worker'ları uzun yaşayan process'lerdir. Yeni kod deploy edildiğinde çalışan worker eski boot edilmiş application state'i kullanmaya devam edebilir. Bekçi bunu kontrol eder.

### Change Impact View Örneği

```yaml
CHANGE: migration: status → aktiflik_durumu

IMPACT:
  Tenant schema
    ├── Tenant.php
    ├── SaaS/Tenant.php
    ├── TenantBaselineSeeder
    ├── HuntOpportunitiesCommand
    ├── HermesDashboardService
    └── tests

UNKNOWN CONSUMERS: 2
```

**Not:** Bu ayrı bir tarayıcı/authority DEĞİL. Mevcut CDA/evidence'ın agent için okunabilir görünümü.

### Sonuç Formatı

```
╔════════════════════════════════════╗
║           YALIHAN BEKÇİ           ║
╠════════════════════════════════════╣
║ Change Integrity        PASS       ║
║ Architecture Integrity  PASS       ║
║ Guard Integrity         PASS       ║
║ Runtime Integrity       PASS       ║
║ Release Integrity       BLOCKED    ║
╠════════════════════════════════════╣
║ BLOCKER                            ║
║ Production worker release UNKNOWN ║
╠════════════════════════════════════╣
║ DECISION: BLOCKED                  ║
╚════════════════════════════════════╝
```

### Mevcut Altyapıdan Bekçi'ye

```
                    BEKÇİ v3
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
     Sentinel FAST  Doktor DEEP  Bekçi Health
          │            │            │
          └────────────┼────────────┘
                       ▼
             Canonical Result Envelope
```

**Bekçi mevcut motorları çöpe atmaz.** Sentinel, Doctor, Health zaten çalışıyor. v3 bunları tek contract altında birleştirir.

### MCP'nin Yeri

```
                  BEKÇİ ENGINE
                       │
         ┌─────────────┼──────────────┐
         │             │              │
      Artisan         CI             MCP
         │             │              │
       Human         GitHub          Agent
```

**MCP kolaylık sağlar; authority DEĞİLDİR.**

MCP bozulursa `php artisan bekci:...` veya mevcut gate script'leri yine çalışır.

Örnek: "Cline, Bekçi'ye 'bu değişikliğin impact'ini göster' der → MCP Bekçi'yi çağırır."

### Blocking Örneği (TENANT_LEGACY_STATUS)

```yaml
RULE: TENANT_LEGACY_STATUS
Fingerprint: ...
Evidence Type: AST_INVARIANT
Evidence Level: REPO_VERIFIED
Maturity: NEW_REGRESSION_BLOCKING
Legacy baseline: NO
Canonical exception: NONE

DECISION: BLOCKED
```

**Bekçi "STATUS YASAK!" DEMEZ.** Finding'in niteliğini de bilir.

---

## BEKÇİ ROL TANIMI

| Rol | Kim | Ne yapar |
|-----|-----|----------|
| **Tamirci** | Cline | Kod yazar, değişiklik yapar |
| **Yönlendirici/Doğrulayıcı** | Antigravity | Agent'ı yönlendirir, doğrular |
| **Karar Sahibi** | Ayhan | İnsan override, Human Gate |
| **Kapıdaki Teknik Kontrol** | **Bekçi** | Evidence toplar, PASS/BLOCKED/HUMAN_GATE üretir |
```

---

## 🏛️ ADR-CDH-002: User Model aktiflik_durumu Canonical Authority & Isolated Production Hotfix (2026-10-04)

- **Karar:** `users.aktiflik_durumu` tablonun tek ve kanonik aktiflik otoritesidir. `is_active` fiziksel sütunu veritabanında mevcut değildir ve model fillable/cast listelerine eklenemez.
- **Source Implementation Commit:** `fdc421bc3f55ac1c4f2ee73b8b67df87b8c1c2d6` (Main worktree, REPO_VERIFIED + TEST_VERIFIED)
- **Certified Production Artifact:** `9cb41e20405ae8b561c0d6ecc71f78d76f4bdcd1` (Isolated hotfix backport branch: `release/cdh002-prod-hotfix`)
- **Production Base:** `1172824699243659c87977ccca8a9b0c307101fa` (Direct parent of `9cb41e20`)
- **Doğrulama:** `CDH002_INDEPENDENT_PRODUCTION_VERIFY_07` = PASS. Host, App ve Queue container dosyaları sha256 (`a129ad4880e865a649cb33d83c2711e3cc4027f366269e8f95a4677612a2b0d0`) ile birebir doğrulanmıştır.
- **Kural & İlke:** Primary worktree HEAD (`fdc421bc`) ile production HEAD (`9cb41e20`) aynı olmak zorunda değildir; primary worktree'de aktif geliştirme ve unpushed commit'ler bulunurken izole hotfix production'a bağımsız sokulmuştur. `fdc421bc` tekrar CDH-002 olarak deploy edilmemelidir.


