# Sprint Backlog

> Chief AI — Sprint iş listesi
> Son güncelleme: 2026-06-25

---

## Aktif Sprint: Sprint 3

**Tarih:** 2026-06-15 — devam
**Chief AI Not:** Sprint 3 devam ediyor. Sprint 3.1 öncelik.

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

**Hedef:** Hetzner deploy + JSONB migration
**Chief AI Not:** SSH bloker çözülmeli önce.

| ID | Görev | Öncelik | Risk | Bağımlılık |
|----|--------|----------|------|-------------|
| S4-H01 | Hetzner CX33 deploy (#20-25) | P0 | 🔴5 | SSH bloker çözümü |
| S4-DB01 | JSONB tam göçü (T-UPS-V2-FULL) | P1 | 🔴4 | Sprint 4 başında |
| S4-DB02 | ilan_favorileri FK uyumsuzluğu (T-FAV-01) | P2 | 🟠3 | — |
| S4-AI01 | AIController → AICrmGatewayService (FIX-06) | P2 | 🟠3 | — |
| S4-AI02 | PropertyHubController AI methods (FIX-07) | P2 | 🟠3 | — |
| S4-AI03 | PropertyHubController refactor (FIX-11) | P3 | 🟡2 | — |
| S4-AI04 | DecisionEngineController refactor (FIX-12) | P3 | 🟡2 | — |
| S4-AI05 | FinanceProcessor OpenAI bağımlılığı kaldır (#16) | P3 | 🟡2 | — |
| S4-AI06 | PortfolioProcessor whereBetween→Haversine (#17) | P3 | 🟡2 | — |
| S4-AI07 | yayin_durumu standardizasyonu (#18) | P3 | 🟡1 | — |
| S4-AI08 | bekci:pattern:sync komutu (#26) | P4 | 🟡1 | — |

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

- Sprint 4 başlamadan önce SSH bloker çözülmeli
- 89 fail test Sprint 3 bitiminde Sprint 3.x'e taşınmalı
- Chief AI Sprint 4 öncesi risk analizi yapmalı
