# ADR: Sidebar 5-Layer Product Architecture

**Date:** 2026-04-03
**Status:** Accepted
**SAB Reference:** SAB 1 — Production Sidebar + Navigation Architecture Refactor v2.0

---

## Context

The admin sidebar had grown organically to **10 fragmented groups + 2 solo items** across 600 lines of config. Several critical problems:

1. **AI Fragmentation:** AI features scattered across 4 separate groups (Analytics, AI Sistemi, Property Master Hub, Sistem Ayarları)
2. **SAB Misplaced:** Governance Dashboard buried under "Sistem İzleme" alongside Telescope/Horizon
3. **Integration Burial:** n8n workflows, Telegram Bot, and external integrations hidden in unrelated sections
4. **Telegram in Wrong Layer:** Telegram Bot grouped under "AI Sistemi" instead of Automation
5. **AI Alan Önerileri Leak:** AI field suggestions placed in Property Engine instead of Intelligence

## Decision

Restructure the entire sidebar into a **5-layer semantic architecture**:

| Layer | Name | Purpose | Items |
|-------|------|---------|-------|
| **L1** | Business | Day-to-day operational use | Dashboard, İlanlar, CRM, Takım, Finans, Bildirimler |
| **L2** | Property Engine | Schema, features, templates, rules | Property Hub Dashboard, Özellik Havuzu, Şablonlar, Paketler, Kategoriler, Matris, Bağımlılıklar |
| **L3** | Intelligence | AI brain (Cortex) + Governance (SAB) | AI Dashboard, Cortex Analytics, Cortex Monitoring, AI Öneriler, Kullanım & Maliyet, İstatistikler, Raporlar, Governance Dashboard, Özellik Sağlık Matrisi, AI Governance, Denetim, Bekçi |
| **L4** | Automation | Execution, integrations, channels | Telegram Bot, n8n Workflows, Entegrasyonlar, Sesli Arama |
| **L5** | System | Infrastructure, technical admin | Sistem Sağlığı, Telescope, Horizon, Kullanıcılar, Genel Ayarlar, AI Ayarları, Adres Yönetimi |

### Key Moves

| Item | From | To | Reason |
|------|------|----|--------|
| AI Alan Önerileri | Property Master Hub | Cortex (L3) | AI feature, not schema config |
| Telegram Bot | AI Sistemi | Automation Hub (L4) | Channel automation, not AI brain |
| Governance Dashboard | Sistem İzleme | Governance (L3) | Decision engine, not infra |
| Özellik Sağlık Matrisi | Gelişmiş & İzleme | Governance (L3) | Health monitoring under decisions |
| Entegrasyonlar | Sistem Ayarları | Automation Hub (L4) | Execution layer, not settings |
| n8n Workflows | (buried in settings) | Automation Hub (L4) | Workflow automation |
| Yalıhan Bekçi | (hidden) | Governance (L3) | Enforcer belongs with governance |
| Cortex Revenue | Analitik & Raporlar | Cortex Analytics (L3) | AI analytics under AI brain |

## Consequences

- **Positive:** Clear mental model — users know exactly where to find each feature class
- **Positive:** AI features consolidated under one "Cortex" section
- **Positive:** SAB/Governance has dedicated space at L3
- **Positive:** No route changes — only menu grouping changed
- **Negative:** Existing muscle memory for old locations needs adjustment
- **Mitigated:** Sidebar search (Alpine.js) still works for quick access

## Alternatives Considered

1. **Keep 10 groups, just rename** — Rejected: doesn't fix fragmentation
2. **3-layer (Business/Tech/Admin)** — Rejected: too few layers, Intelligence and Automation get merged
3. **7-layer (add Analytics, Reports, Monitoring)** — Rejected: too granular, sidebar becomes overwhelming
