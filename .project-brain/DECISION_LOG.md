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

