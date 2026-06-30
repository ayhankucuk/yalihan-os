# YALIHAN EMLAK - DOKÜMANTASYON MERKEZİ

> [!IMPORTANT]
> **AUTHORITY HIERARCHY (SSOT)**
> 1. **Human (User)** - Final decision maker.
> 2. **Live Code & DB Schema** - Runtime truth.
> 3. **Living Memory ([LEARNED_PATTERNS.json](./governance/LEARNED_PATTERNS.json))** - Cognitive Guardian Knowledge.
> 4. **[.sab/authority.json](../.sab/authority.json)** - Centralized Technical Governance SSOT.
> 5. **[README (Repository Root)](../README.md)** - Operational Constitution.
> 5. **Documentation Files** - Explanatory & Architectural Guide.

- **Phase 10:** State Authority & 4D Convergence (COMPLETED)
- **Phase 11:** The Learner & Cognitive Guardian (COMPLETED)
- **Phase 12:** Monetization Core & SaaS Global Scaling (IN PROGRESS 🛡️)

## 🏛️ Domain Otoriteleri
1. **Cortex Domain:** AI Karar, Tahmin ve Analiz Motorları.
2. **Governance Domain:** SAB, Bekçi v2.1 ve AST Denetim Sistemi.
3. **Monetization Domain:** Multi-tenant Faturalandırma, AI Kredi ve Abonelik Yönetimi.
4. **Ilan Domain:** Gayrimenkul Veri ve Yaşam Döngüsü Yönetimi.

Bu proje geliştirme dokümantasyonları 5 temel bileşenden oluşur:

- [Teknik Anayasa (SAB.md)](./SAB.md): Projenin değişmez teknik kuralları, mühür protokolleri ve katman disiplini.
- [Mühendislik Dersleri (MUHENDISLIK_DERSLERI.md)](./registry/MUHENDISLIK_DERSLERI.md): Proje boyunca öğrenilen dersler, anti-patternler ve altın kurallar.
- [İlerleme Takibi (PROGRESS-TRACKER.md)](./PROGRESS-TRACKER.md): Phase 4C sonrası güncel sistem durumu ve tamamlanma oranları.
- [DAP Core (DAP_CORE.md)](./DAP_CORE.md): DAP otopilot haritası, karar matrisi ve operasyonel kurallar SSOT.
- [G1 Guard (COMMAND_GUARD.md)](./technical/system/COMMAND_GUARD.md): Artisan komut bütünlüğü ve drift koruma sistemi.
- [Teknik Borçlar (known-debt.md)](./known-debt.md): Çözülmeyi bekleyen teknik, mimari ve governance borçları.

_Context7 Kuralları, SAB Protokolleri ve Zero-Regeneration altyapısına tabidir._

## Architecture SSOT (Operasyonel Referans)

Sistemin nasıl çalıştığını anlatan tek doğru referans kaynağı:

- [Domains — Domain Haritası](./architecture/domains.md): Hangi domain var, birbirleriyle nasıl ilişkili?
- [Pages — Sayfa Haritası](./architecture/pages.md): Bu URL ne sayfası, ne iş yapıyor?
- [Models — Model Kataloğu](./architecture/models.md): Bu model kimin, nerede kullanılıyor?
- [Flows — İş Akışları](./architecture/flows.md): Bu iş akışı nasıl çalışıyor, riskler nerede?
- [Side Menu Map](./architecture/side-menu-map.md): Sidebar menüsü → alt sayfa → amaç
- [Service Ownership](./architecture/service-ownership.md): Bu servis kimin, write authority var mı?
- [UI to Route Map](./architecture/ui-to-route-map.md): UI sayfa → route → controller → servis
- [Governance Boundaries](./architecture/governance-boundaries.md): Governance ilkeleri ve tuzaklar
- [Telemetry Map](./architecture/telemetry-map.md): Hangi dashboard neyi ölçüyor, false healthy riski
- [Open Questions](./architecture/open-questions.md): Doğrulanmamış açık sorular
- [Quick Reference](./architecture-lite.md): Tek dosya hızlı referans (%80 kapsam)

## Governance & Hygiene

- **Active CI Pipeline:** [Gold Line CI](../.github/workflows/gold-line.yml) — 6-gate PR blocking governance.
- **MCP Governance Bridge:** [mcp/src/index.ts](../mcp/src/index.ts) — TypeScript adapter for AI agents.
- **Command Authority:** `sab:integrity-scan`, `sab:integrity-scan --format=json`, `bekci:audit`, `bekci:pattern:learn`, `sab:guard`, `guard:cqrs`, `guard:routes:v2`, `quality:gate`, `sab:preflight`

### Raporlar & Arşiv
- [Dokümantasyon Arşivi](./archive/): Geçmiş fazlara ait tüm raporlar ve planlar burada saklanır.
- [Governance Arşivi](./archive/2026_05/governance/): Tamamlanan Phase 2/3/4A/4B süreçlerine ait tarihsel dokümanlar.
- [SAB Audit Raporu (Arşiv)](./archive/2026_05/SAB_AUDIT_REPORT.md): 175 ihlal içeren son büyük denetim raporu.
