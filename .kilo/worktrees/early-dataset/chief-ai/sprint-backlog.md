# Sprint Backlog

> Chief AI — Sprint iş listesi
> Son güncelleme: **2026-06-29**
> v6.1 Master Audit Entegrasyonu — Phase 2 (Security) + Phase 3 (Reliability) bulguları entegre edildi.

---

## Aktif Sprint: Sprint 3.1 EXTENDED — P0 Implementation

**Tarih:** 2026-06-29 — 2026-07-05
**Chief AI Not:**
- Architecture Office → Yeni audit üretmiyor. P0+P1 paketleme moduna geçti.
- 3 Implementation Package hazır: `audits/implementation/P0-G01_*.md`, `P0-G02_*.md`, `P0-G03_*.md`
- Yeni öncelik sırası: after_commit → Sanctum expiration (client etkisi kontrol edilecek) → IDOR

**Eklenen:** 9 yeni item (P0:3, P1:6) — ~12.5 saat ek iş
**Bloker:** Yok

---

## Sprint 3.1 EXTENDED — Emergency P0 + P1 Bulgu Kapatma

**2026-06-29 — Phase 2 (Security) + Phase 3 (Reliability) audit bulguları**

| ID | Audit Ref | Bulgu | Öncelik | Süre | Durum | Atanan |
|----|-----------|-------|----------|------|--------|--------|
| S3.1-E01 | REL-002 | `config/queue.php` — `after_commit: false` → `true` | **P0** | 1h | 🔴 Acil | Kilo |
| S3.1-E02 | SEC-001 | `config/sanctum.php` — `expiration: null` → `10080` | **P0** | 30m | 🔴 Acil | Kilo |
| S3.1-E03 | SEC-003 | `V2/IlanController.php:87` — IDOR: tenant scope + IlanPolicy | **P0** | 2h | 🔴 Acil | Kilo |
| S3.1-E04 | SEC-004 | `FavoriService.php:13` — tenant_id guard | P1 | 2h | 🔄 Devam | Kilo |
| S3.1-E05 | REL-003 | `ListingProjector.php` — try/catch + $tries + $backoff | P1 | 2h | 🔄 Devam | Kilo |
| S3.1-E06 | REL-004 | `LeadProjector.php` — try/catch + $tries + $backoff | P1 | 2h | 🔄 Devam | Kilo |
| S3.1-E07 | REL-005+006 | `PhotoService.php` — DB/Storage ordering (deletePhoto + bulkDelete) | P1 | 3h | 🔄 Devam | Kilo |
| S3.1-N01 | CONTEXT | `app/Services/Wizard/` type→tip cleanup | P1 | — | 🔄 Devam | Kilo |
| S3.1-N02 | CONTEXT | `app/Traits/Filterable.php` active field fix | P1 | — | 🔄 Devam | Kilo |

**Audit Referansları:**
- `audits/SECURITY_GAP_ANALYSIS.md` — 15 bulgu (2 CRITICAL, 4 HIGH, 7 MEDIUM, 2 LOW)
- `audits/RELIABILITY_GAP_ANALYSIS.md` — 12 bulgu (2 CRITICAL, 4 HIGH, 5 MEDIUM, 1 LOW)
- `audits/MASTER_ARCHITECTURE_BACKLOG.md` — konsolide backlog
- `audits/RISK_PRIORITY_MATRIX.md` — öncelik matrisi
- `audits/IMPLEMENTATION_ROADMAP.md` — sprint-by-sprint yol haritası

---

## Sprint 3.x — Naming Authority Cleanup

| ID | Görev | Öncelik | Risk | Durum | Atanan |
|----|--------|----------|------|--------|--------|
| S3.1-N01 | `app/Services/Wizard/` type→tip cleanup | P1 | 3 | 🔄 Devam | Kilo |
| S3.1-N02 | `app/Traits/Filterable.php` active field fix | P1 | 4 | 🔄 Devam | Kilo |
| S3.1-N03 | context7-ignore baseline güncelle | P2 | 2 | 📋 Beklemede | — |
| S3.1-N04 | sab:integrity-scan 4500→3000 hedef | P2 | 2 | 📋 Beklemede | — |

---

## Sprint 4 — Planlandı

**Hedef:** Hetzner deploy + JSONB migration + Reliability Foundation
**Chief AI Not:** SSH bloker çözülmeli önce. Sprint 3.1 Extended tamamlanması şart.
**Eklenen:** 10 yeni item (P1:1, P2:9) — ~31 saat ek iş

| ID | Audit Ref | Görev | Öncelik | Risk | Bağımlılık |
|----|-----------|-------|----------|------|-------------|
| S4-H01 | DEPLOY | Hetzner CX33 deploy (#20-25) | P0 | 🔴5 | SSH bloker çözümü |
| S4-DB01 | DEPLOY | JSONB tam göçü (T-UPS-V2-FULL) | P1 | 🔴4 | Sprint 4 başında |
| **S4-R01** | **P1-G04** | **Transactional Outbox — `FinancialLedgerService` outbox tablo** | **P1** | 🔴4 | Sprint 4 başı |
| **S4-R02** | **P1-G09** | **Token revocation — revokeOtherDevices() + revokeCurrentDevice()** | **P1** | 🟠3 | P0-G02 |
| **S4-R03** | **P2-G05** | **`FinanceProcessor` → YalihanCortex + timeout(10)** | **P2** | 🟠3 | — |
| **S4-R04** | **P2-G01** | **Dual `AiBudgetGuard` konsolidasyon — tek impl.** | **P2** | 🟠3 | — |
| **S4-R05** | **P2-G07** | **`CircuitBreaker` → DB-backed atomic / Redis INCR+EXPIRE** | **P2** | 🟡2 | — |
| **S4-R06** | **P2-G08** | **`SyncListingProjectionJob` — $tries=3, $backoff=[5,15,30]** | **P2** | 🟡2 | — |
| **S4-R07** | **P2-G09** | **`IlanProjectionHandler` — firstOrCreate + drift detection** | **P2** | 🟡2 | P0-G01 |
| **S4-R08** | **P2-G10** | **`ProcessProjectionJob` failed() → proj_dlq routing** | **P2** | 🟡2 | — |
| S4-DB02 | DEPLOY | ilan_favorileri FK uyumsuzluğu (T-FAV-01) | P2 | 🟠3 | — |
| S4-AI01 | DEPLOY | AIController → AICrmGatewayService (FIX-06) | P2 | 🟠3 | — |
| S4-AI02 | DEPLOY | PropertyHubController AI methods (FIX-07) | P2 | 🟠3 | — |
| S4-AI05 | P2-G05 | FinanceProcessor OpenAI dependency kaldır (#16) | P3 | 🟡2 | S4-R03 |
| S4-AI06 | DEPLOY | PortfolioProcessor whereBetween→Haversine (#17) | P3 | 🟡2 | — |
| S4-AI07 | DEPLOY | yayin_durumu standardizasyonu (#18) | P3 | 🟡1 | — |
| S4-AI08 | DEPLOY | bekci:pattern:sync komutu (#26) | P4 | 🟡1 | — |

---

## Sprint 5+ — Mimari Olgunluk

| ID | Görev | Öncelik | Risk |
|----|--------|----------|------|
| S5-CR01 | CRM V1 + V2 → tek model konsolidasyon | P1 | 🔴5 |
| S5-FI01 | Finance modül çakışması | P1 | 🔴4 |
| S5-TM01 | 11 controller → tek template hiyerarşisi | P2 | 🟠3 |
| S5-API01 | 14 controller API/Admin namespace migration | P3 | 🟡2 |

---

## Sprint 6 — Chief AI Layer

| ID | Görev | Öncelik | Durum |
|----|--------|----------|--------|
| S6-C01 | tasks.json görev havuzu oluştur | P1 | 📋 Planlandı |
| S6-C02 | PROJECT_STATE.json oluştur | P1 | 📋 Planlandı |
| S6-C03 | chief-ai/ tam kur | P2 | 📋 Planlandı |
| S6-C04 | Agent Orchestrator pilot | P2 | 📋 Planlandı |

---

## Tamamlanan Sprintler

| Sprint | Tarih | Durum |
|--------|--------|--------|
| Sprint 1 | 2026-05-10 | ✅ TAMAMLANDI |
| Sprint 2 | 2026-06-15 | ✅ TAMAMLANDI |
| Sprint 3 | 2026-06-15 | 🔄 DEVAM |

---

## SPRINT 3.1: NAMING AUTHORITY CLEANUP + TEST STABILIZATION

**Chief AI Execution Plan — 2026-06-25**
**Hedef:** Project Health 59.25% → 75%+ | 89 fail test önceliklendirme
**Süre:** 2026-06-25 — 2026-07-02 (7 gün)
**Status:** 🔄 AKTIF (REVISED — D08)
**Reason:** P0 infrastructure blocker identified in test analysis

### PHASE 0: Test Infrastructure Recovery ✅ CLOSED

**Chief AI Decision:** D09 — FALSE POSITIVE
**Result:** Tüm test sorunları FALSE POSITIVE çıktı

| ID | Görev | Agent | Durum |
|----|-------|-------|--------|
| T-P0-01 | RepositoryInstrumentation.php:65 syntax | Kilo | ✅ `php -l` clean |
| T-P0-02 | Route: admin.ilanlarim.index | Kilo | ✅ Route EXISTS |
| T-P0-03 | Route: admin.ilanlar.create-wizard | Kilo | ✅ Route EXISTS |

**Root Cause:** Test hatası cache/migration sorunu olabilir
**Next Action:** `composer dump-autoload && php artisan view:clear`

### PHASE 1: Naming Authority Cleanup ✅ UNBLOCKED

**Dependency:** Phase 0 MUST be completed

| ID | Görev | Agent | Durum |
|----|-------|-------|--------|
| S3.1-N01 | `type` → `tip` | Kilo | ⛔ Blocked |
| S3.1-N02 | `active` → `aktiflik_durumu` | Kilo | ⛔ Blocked |
| S3.1-N03 | context7-ignore (50 dosya) | Cline | ⛔ Blocked |
| S3.1-N04 | Framework naming | Windsurf | ⛔ Blocked |
| S3.1-N05 | Local variable ignore | Cursor | ⛔ Blocked |

### PHASE 2: CI Baseline ⛔ BLOCKED

**Dependency:** Phase 1 MUST be completed

| ID | Görev | Agent | Durum |
|----|-------|-------|--------|
| S3.1-G01 | CI baseline stabilization | Cline | ⛔ Blocked |
| S3.1-G02 | Drift monitoring | Cline | ⛔ Blocked |

### FASE 1: Stabilizasyon (Gün 1)

| ID | Görev | Agent | Çıktı | Verifikasyon |
|----|-------|-------|-------|--------------|
| S3.1-T01 | 89 fail test analizi | Kilo | öncelik-matris.md | `php artisan test --compact` |
| S3.1-T02 | Kritik test önceliklendirme | Claude Desktop | test-listesi.md | 37 acil liste |

**Verifikasyon:** `grep -E "FAIL|Error" | wc -l` → Hedef: 89 → 52

### FASE 2: Naming Authority Cleanup (Gün 2-5)

#### Wave A: Domain Model Fields
| ID | Görev | Agent | Dosyalar | Hedef |
|----|-------|-------|----------|-------|
| S3.1-N01 | `type` → `tip` | Kilo | app/Services/Wizard/ (12) | 0 ihlal |
| S3.1-N02 | `active` → `aktiflik_durumu` | Kilo | app/Traits/ (1) | 0 ihlal |

#### Wave B: Prompt/AI Content (context7-ignore)
| ID | Görev | Agent | Dosyalar | Hedef |
|----|-------|-------|----------|-------|
| S3.1-N03 | context7-ignore ekle | Cline | Legacy servisler (50) | 175 → 125 |

#### Wave C: Framework Conventions (English — koruma)
| ID | Görev | Agent | Dosyalar | Durum |
|----|-------|-------|----------|-------|
| S3.1-N04 | Timestamps/Relations | Windsurf | 25 dosya | İngilizce bırak |

#### Wave D: Local Variables (context7-ignore)
| ID | Görev | Agent | Dosyalar |
|----|-------|-------|----------|
| S3.1-N05 | Local var ignore | Cursor | 30 dosya |

**Verifikasyon:** `php artisan sab:integrity-scan | grep "Context7" | wc -l`

### FASE 3: Governance Baseline (Gün 6-7)

| ID | Görev | Agent | Verifikasyon |
|----|-------|-------|--------------|
| S3.1-G01 | CI baseline stabilization | Cline | `php artisan sab:integrity-scan --baseline` |
| S3.1-G02 | Drift monitoring | Cline | `./scripts/guards/quality-gate.sh` |

### Başarı Kriterleri

| Metric | Başlangıç | Hedef |
|--------|-----------|-------|
| Project Health | 59.25% | 75%+ |
| Naming violations | 175 | < 50 |
| Fail tests (kritik) | 37 | < 10 |
| CI gate stability | ⚠️ Pre-existing | ✅ Stable |

### Rollback Plan

```
1. Her görev sonrası: git commit
2. Test başarısız → git revert
3. CI failure → baseline koruma
4. Sprint rollback → Chief AI kararı
```

---

## Chief AI Notları

### 2026-06-29 — v6.1 Architecture Office Audit + P0 Implementation Packages

**Tarih:** 2026-06-29
**Chief Ajan:** Architecture Office — Phase 2 (Security) + Phase 3 (Reliability) tarandı.
**Bulgu:** 27 toplam (4 CRITICAL, 8 HIGH, 12 MEDIUM, 3 LOW) + ~175 Naming Authority ihlali

**Audit Belgeleri:**
- `audits/SECURITY_GAP_ANALYSIS.md` — 15 bulgu
- `audits/RELIABILITY_GAP_ANALYSIS.md` — 12 bulgu
- `audits/MASTER_ARCHITECTURE_BACKLOG.md` — konsolide backlog
- `audits/RISK_PRIORITY_MATRIX.md` — öncelik matrisi
- `audits/IMPLEMENTATION_ROADMAP.md` — sprint-by-sprint yol haritası

**Implementation Packages:**
- `audits/implementation/P0-G01_AFTER_COMMIT.md` — ✅ Hazır
- `audits/implementation/P0-G02_SANCTUM_EXPIRATION.md` — ✅ Hazır (client etkisi analiz edildi)
- `audits/implementation/P0-G03_V2_IDOR_FIX.md` — ✅ Hazır (admin kullanım senaryosu not edildi)

**Architecture Score Revize:**
- Önceki: 86/100 (Naming odaklı, security/reliability忽略)
- Revize: **65/100** (CRITICAL/HIGH bulgular yayınlandıktan sonra)
- Hedef Sprint 6: 85/100

**Chief AI Karar Noktaları (2026-06-29):**
- **D-05**: Sprint 3.1 Extended başlatıldı. 2026-07-05'e kadar P0+P1 kapatılacak.
- **D-06**: P1-G04 (Transactional Outbox, 8h) Sprint 4 Wave A'ya taşındı. Yeterli test coverage olmadan yapılmaz.
- **D-07**: FinanceProcessor YalihanCortex yönlendirmesi öncelikli. Telegram bildirim hattı aktif risk.
- **D-08**: CRM V1+V2 konsolidasyonu (S5-CR01) Sprint 5'te TBD. Daha fazla analiz gerekli.
- **D-09**: Agent Manager worktree Sprint 4'ten itibaren paralel item'lar için kullanılacak.
- **D-10**: **Architecture Office yeni audit üretmiyor.** Görev: P0+P1 sistematik kapatma. Implementation Package'lar hazır.
- **D-11**: P0 öncelik sırası: P0-G01 (after_commit, 1h) → P0-G02 (Sanctum, 3-5h, client etkisi!) → P0-G03 (IDOR, 2h).

---

## SPRINT 3.1 EXTENDED — Implementation Packages

**Mevcut:** 3 package hazır. Geri kalan P1 package'ları Sprint 4'e taşındı.

| ID | Package | Durum | Öncelik |
|----|---------|-------|----------|
| P0-G01 | `audits/implementation/P0-G01_AFTER_COMMIT.md` | ✅ Hazır | **1 / 3** |
| P0-G02 | `audits/implementation/P0-G02_SANCTUM_EXPIRATION.md` | ✅ Hazır | **2 / 3** |
| P0-G03 | `audits/implementation/P0-G03_V2_IDOR_FIX.md` | ✅ Hazır | **3 / 3** |

### Devam Eden Notlar
- Sprint 4 başlamadan önce SSH bloker çözülmeli
- 89 fail test Sprint 3.x'e taşındı (sprint-backlog içinde izleniyor)
- Sprint 3.1 Extended: Sprint 3.1-N01..N05 + S3.1-E01..E07 paralel yürütülecek
- P0-G01 (`after_commit: false`) Sprint 3.1 Extended'de ilk sırada — REL-001 ve REL-011 için önkoşul
- P0-G02 (`Sanctum expiration: null`) ikinci sırada — 30 dakikada kapatılır
- P0-G03 (V2 IlanController IDOR) üçüncü sırada — production veri sızıntısı aktif risk

### Kapanış Kontrol Listesi (Her Sprint Sonu)
- [ ] `php artisan bekci:security-scan` → 0 CRITICAL, 0 HIGH
- [ ] `php artisan test --filter=ProjectionTest` → PASS
- [ ] `php artisan test --filter=LedgerTest` → PASS
- [ ] `php artisan sab:integrity-scan` → 0 new violations
- [ ] `php artisan queue:work --once` → DLQ empty
- [ ] Architecture Score hedefine ulaşıldı
- [ ] Audit belgeleri güncellendi
