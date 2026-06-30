# Executive Dashboard

> Chief AI — Sistem Durumu Özeti
> Chief AI açıldığında BAKILACAK ilk dosya
> Her oturum başında güncellenir
> Son güncelleme: 2026-06-25

---

## PROJECT STATUS

```
╔═══════════════════════════════════════════════════════════════╗
║                 YALIHAN AI OS — EXECUTIVE DASHBOARD          ║
║                        2026-06-25 16:36 +03:00                ║
╚═══════════════════════════════════════════════════════════════╝

  ┌─────────────────────────────────────────────────────────┐
  │  OVERALL HEALTH                                        │
  │  ██████████████████████░░░░░░░░░░░░░  59.25%  ⚠️      │
  │  Hedef: 75%                                            │
  └─────────────────────────────────────────────────────────┘

  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
  │ ARCHITECTURE │  │   KNOWLEDGE  │  │ DOCUMENTATION │
  │      A-      │  │     94%      │  │     96%      │
  │   86/100     │  │    ✅        │  │     ✅       │
  └──────────────┘  └──────────────┘  └──────────────┘

  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
  │    RISK      │  │TECHNICAL DEBT│  │  VELOCITY    │
  │   MEDIUM     │  │     445      │  │    +18%      │
  │  2 Critical  │  │    🔴       │  │     ✅       │
  └──────────────┘  └──────────────┘  └──────────────┘
```

---

## CRITICAL METRICS

| Metric | Value | Status | Trend |
|--------|-------|--------|-------|
| **Overall Health** | 59.25% | ⚠️ | → |
| **Architecture Score** | 86/100 | ✅ A- | ↑ |
| **Project Health** | 59.25% | ⚠️ | → |
| **Technical Debt** | 445 pts | 🔴 | → |
| **Naming Violations** | 175 | ⚠️ | → |
| **Fail Tests** | 89 (37 kritik) | 🔴 | → |
| **Agent Utilization** | 62% | 🟡 | → |

---

## ACTIVE SPRINT

| Alan | Değer |
|------|-------|
| **Sprint** | Sprint 3.1 |
| **Hedef** | Health 59% → 75%+ |
| **Süre** | 7 gün |
| **Başlangıç** | 2026-06-25 |
| **Bitiş** | 2026-07-02 |
| **Kalan** | 6 gün 23 saat |
| **Tamamlanma** | 0% |
| **Status** | 🔴 ACTIVE |

---

## RISK SUMMARY

| ID | Risk | Puan | Durum |
|----|------|------|--------|
| R01 | SSH Blocker | 🔴 8 | ⚠️ İnsan gerekli |
| R02 | 89 Fail Tests | 🟠 7 | 🔄 Sprint 3.1 |
| R03 | Naming 175 | 🟠 6 | 🔄 Sprint 3.1 |
| R04 | JSONB Migration | 🟠 6 | 📋 Sprint 4 |

---

## TECHNICAL DEBT BREAKDOWN

```
TD-01: 105 ████████████████████████████  🔴  Fail Tests
TD-03: 125 ██████████████████████████████  🔴  SSH Blocker
TD-04:  64 ██████████████                   🟠  JSONB
TD-02:  48 ██████████                       🟠  Naming
TD-05:  36 ████████                        🟡  Controllers
TD-08:  27 ██████                          🟡  Legacy Naming
TD-07:  16 ████                            🟡  CI Gates
TD-06:  12 ███                             🟢  AI Workspace
TD-09:  12 ███                             🟢  MCP Test
─────────────────────────────────────────────
TOPLAM:  445                                 🔴
```

---

## AGENT STATUS

| Agent | Sprint | Yük | Durum |
|-------|--------|-----|-------|
| Kilo | Sprint 3.1 | 85% | 🔄 Aktif |
| Claude Desktop | Sprint 3.1 | 20% | 📋 Beklemede |
| Windsurf | Sprint 3.1 | 40% | 📋 Beklemede |
| Cursor | Sprint 3.1 | 40% | 📋 Beklemede |
| Cline | Sprint 3.1 | 40% | 📋 Beklemede |
| Human | R01 | 20% | ⚠️ Action Required |

---

## BLOCKED ITEMS

### Human Action Required

| Görev | Impact | Owner | Son Tarih |
|-------|--------|-------|-----------|
| SSH Blocker Resolution | Sprint 4 blocked | Human | ⚠️ Acil |

---

## NEXT MILESTONE

| Milestone | Tarih | Durum |
|-----------|-------|-------|
| Sprint 3.1 Bitimi | 2026-07-02 | 📋 6 gün kaldı |
| Sprint 4 Başlangıç | 2026-07-02 | ⏳ Bloke |
| YALIHAN AI OS v4 | TBD | 📋 Planlandı |

---

## QUICK COMMANDS

```bash
# Sistem sağlığı
php artisan bekci:health --detailed

# Mimari ihlaller
php artisan sab:integrity-scan

# Test durumu
php artisan test --compact

# MCP durumu
php artisan bekci:health | grep MCP
```

---

## ACTIVE INCIDENTS

| Incident ID | Risk | Priority | Status | Owner |
|-------------|------|----------|--------|-------|
| INC-2026-0625-R08 | R08 | 🔴 P0 | ✅ FALSE POSITIVE | Kilo |
| INC-2026-0625-R09 | R09 | 🟠 P1 | ✅ FALSE POSITIVE | Kilo |
| INC-2026-0625-R10 | R10 | 🟠 P1 | ✅ FALSE POSITIVE | Kilo |

**Chief AI Note:** R08, R09, R10 false positive. Phase 1 ACTIVE.

---

## SPRINT 3.1 STATUS

| Phase | Status | Blocked By |
|-------|--------|------------|
| Phase 0: Test Infrastructure | ✅ CLOSED | — |
| Phase 1: Naming Cleanup | 🔄 ACTIVE | — |
| Phase 2: CI Baseline | ⏳ PENDING | Phase 1 |

---

## Chief AI Notu

> Bu dashboard Chief AI'ın ilk baktığı dosyadır.
> Tüm sistem durumu 10 saniyede görünür.
> Detay için ilgili chief-ai/ dosyasına bak.
> **Chief AI v3.0 Directive: ACTIVE**
