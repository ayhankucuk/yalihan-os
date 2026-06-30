# YALIHAN EMLAK — DOKÜMANTASYON MERKEZİ

> Son güncelleme: 2026-06-16 (WenOX — MD Audit & Reorganizasyon)
> Toplam aktif MD: ~130 | docs/ kökü: 12 SSOT dosyası

---

> [!IMPORTANT]
> **OTORİTE HİYERARŞİSİ (SSOT)**
> 1. **İnsan (Kullanıcı)** — Mutlak ve nihai otorite.
> 2. **Canlı Kod & DB Şeması** — Runtime gerçeği.
> 3. **[.sab/authority.json](../.sab/authority.json)** — Merkezi Teknik Governance SSOT.
> 4. **[README (Proje Kökü)](../README.md)** — Operasyonel Anayasa.
> 5. **Dokümantasyon Dosyaları** — Açıklayıcı & Mimari Rehber.

---

## 📌 SSOT Çekirdek Dosyalar (docs/ Kökü)

| Dosya | Amaç |
|-------|------|
| [`SAB.md`](./SAB.md) | Teknik Anayasa — değişmez kurallar, katman disiplini |
| [`PROGRESS-TRACKER.md`](./PROGRESS-TRACKER.md) | Phase ilerlemesi, sprint durumu |
| [`BEKCI_CHANGELOG.md`](./BEKCI_CHANGELOG.md) | Governance log, Bekçi oturum kayıtları |
| [`ROADMAP.md`](./ROADMAP.md) | Yol haritası |
| [`known-debt.md`](./known-debt.md) | Teknik borç kayıtları |
| [`DAP_CORE.md`](./DAP_CORE.md) | DAP otopilot haritası ve karar matrisi |
| [`DAP_DECISION_TABLE.md`](./DAP_DECISION_TABLE.md) | DAP karar tablosu detayı |
| [`yalihan-project-brain-v3.md`](./yalihan-project-brain-v3.md) | Proje beyin belgesi (v3 — güncel) |
| [`authority-map.md`](./authority-map.md) | Otorite ve servis sahipliği haritası |
| [`architecture-lite.md`](./architecture-lite.md) | Tek dosya hızlı referans (%80 kapsam) |
| [`MD_AUDIT_REPORT.md`](./MD_AUDIT_REPORT.md) | MD dosya denetim raporu (2026-06-16) |

---

## 🏛️ Mimari (docs/architecture/)

| Dosya | İçerik |
|-------|--------|
| [`domains.md`](./architecture/domains.md) | Domain haritası, ilişkiler |
| [`pages.md`](./architecture/pages.md) | URL → sayfa → iş amacı |
| [`models.md`](./architecture/models.md) | Model kataloğu |
| [`flows.md`](./architecture/flows.md) | İş akışları, risk noktaları |
| [`service-ownership.md`](./architecture/service-ownership.md) | Servis sahipliği, write authority |
| [`ui-to-route-map.md`](./architecture/ui-to-route-map.md) | UI → route → controller → servis |
| [`governance-boundaries.md`](./architecture/governance-boundaries.md) | Governance ilkeleri ve tuzaklar |
| [`telemetry-map.md`](./architecture/telemetry-map.md) | Dashboard metrikleri, false-healthy riski |
| [`side-menu-map.md`](./architecture/side-menu-map.md) | Sidebar menüsü haritası |
| [`open-questions.md`](./architecture/open-questions.md) | Doğrulanmamış açık sorular |
| [`security-boundaries.md`](./architecture/security-boundaries.md) | Güvenlik sınırları |
| [`ai-provider-strategy.md`](./architecture/ai-provider-strategy.md) | AI sağlayıcı stratejisi |
| [`automation-layer.md`](./architecture/automation-layer.md) | Otomasyon katmanı |
| [`integration-contracts.md`](./architecture/integration-contracts.md) | Entegrasyon kontratları |
| [`ARCHITECTURE_DEEP_DIVE.md`](./architecture/ARCHITECTURE_DEEP_DIVE.md) | Derin mimari analiz |
| [`YALIHAN_CORTEX_ARCHITECTURE.md`](./architecture/YALIHAN_CORTEX_ARCHITECTURE.md) | YalihanCortex mimarisi |
| [`AI_COLLABORATION_DESIGN.md`](./architecture/AI_COLLABORATION_DESIGN.md) | AI işbirliği tasarımı |
| [`FRONTEND_DESIGN_VISION.md`](./architecture/FRONTEND_DESIGN_VISION.md) | Frontend tasarım vizyonu |

---

## 🤖 AI Motorları (docs/ai-engines/)

11 AI motoru spec dosyası: `advisor_command_center`, `buyer_match_queue`, `conversational_advisor`, `deal_radar`, `market_intelligence`, `market_valuation`, `opportunity_engine`, `owner_discovery`, `portfolio_doctor`, `pricing_intelligence_sync`, `seller_strategy`, `ai_learning_loop`

---

## ⚖️ Governance (docs/governance/)

| Dosya | İçerik |
|-------|--------|
| [`CLAUDE_MEMORY.md`](./governance/CLAUDE_MEMORY.md) | AI oturum hafızası (her oturumda oku) |
| [`AI_FINDINGS.md`](./governance/AI_FINDINGS.md) | AI denetim bulguları |
| [`SEEDER_GOVERNANCE.md`](./governance/SEEDER_GOVERNANCE.md) | Seed veri governance kuralları |
| [`WENOX_SYSTEM_PROMPT.md`](./governance/WENOX_SYSTEM_PROMPT.md) | WenOX sistem prompt kaydı |

---

## 📋 ADR — Mimari Karar Kayıtları (docs/adr/)

20 ADR dosyası. Tarih sıralı, immutable.
→ [`ADR Dizini`](./adr/README.md)

Son ADR: [`2026-05-15-bekci-v2-1-cognitive-guardian-ast.md`](./adr/2026-05-15-bekci-v2-1-cognitive-guardian-ast.md)

---

## 🔧 Teknik Referans (docs/technical/)

| Dosya | İçerik |
|-------|--------|
| [`SYSTEM_MAP.md`](./technical/SYSTEM_MAP.md) | Sistem haritası |
| [`NAMING-AUTHORITY.md`](./technical/NAMING-AUTHORITY.md) | Context7 isimlendirme otoritesi |
| [`DOMAIN_CONSOLIDATION.md`](./technical/DOMAIN_CONSOLIDATION.md) | app/Domain/ yapısı — Sprint 2 sonucu ⭐ |
| [`MONITORING.md`](./technical/MONITORING.md) | İzleme & alerting |
| [`IDE_AGENT_SYSTEM.md`](./technical/IDE_AGENT_SYSTEM.md) | IDE agent sistemi |
| [`IDE_ONBOARDING_GUIDE.md`](./technical/IDE_ONBOARDING_GUIDE.md) | Yeni geliştirici onboarding |
| [`SEED_AUDIT_REPORT.md`](./technical/SEED_AUDIT_REPORT.md) | Seed audit raporu |
| [`PERFORMANCE_PROFILER.md`](./technical/PERFORMANCE_PROFILER.md) | Performans profiler belgeleri |
| [`walkthrough_queue_hardening.md`](./technical/walkthrough_queue_hardening.md) | Queue hardening walkthrough |
| [`webhook-tenant-security.md`](./technical/webhook-tenant-security.md) | Webhook tenant güvenliği |
| [`api/API_CONTRACT.md`](./technical/api/API_CONTRACT.md) | API kontrat (SSOT) |
| [`frontend/FRONTEND_FILTER_SYSTEM.md`](./technical/frontend/FRONTEND_FILTER_SYSTEM.md) | Frontend filtre sistemi |
| [`system/COMMAND_GUARD.md`](./technical/system/COMMAND_GUARD.md) | Artisan komut guard (G1) |

---

## 🚀 Runbook'lar (docs/runbooks/)

Operasyonel müdahale rehberleri:

| Runbook | Senaryo |
|---------|---------|
| [`ROLLBACK.md`](./runbooks/ROLLBACK.md) | Deployment geri alma |
| [`CI_GATE.md`](./runbooks/CI_GATE.md) | CI gate başarısızlığı |
| [`DRIFT_INCIDENT.md`](./runbooks/DRIFT_INCIDENT.md) | Env/schema drift olayı |
| [`ISOLATION_BREACH.md`](./runbooks/ISOLATION_BREACH.md) | Tenant isolation ihlali |
| [`CACHE_POISONING.md`](./runbooks/CACHE_POISONING.md) | Cache zehirlenmesi |
| [`SIGNATURE_MISMATCH.md`](./runbooks/SIGNATURE_MISMATCH.md) | İmza uyuşmazlığı |
| [`production-server-setup.md`](./runbooks/production-server-setup.md) | Sunucu kurulum |

---

## 📊 Raporlar (docs/_reports/)

17 rapor dosyası — audit, hygiene, drift ve walkthrough raporları.

---

## 📦 Kayıt Defteri (docs/registry/)

| Dosya | İçerik |
|-------|--------|
| [`MUHENDISLIK_DERSLERI.md`](./registry/MUHENDISLIK_DERSLERI.md) | Mühendislik dersleri ve anti-patternler ⭐ |
| [`FAZLAR_GECMIS_RAPORLAR.md`](./registry/FAZLAR_GECMIS_RAPORLAR.md) | Geçmiş fazlar özeti |
| [`architecture-timeline.md`](./registry/architecture-timeline.md) | Mimari zaman çizelgesi |
| [`governance_report.md`](./registry/governance_report.md) | Governance raporu |
| [`REFACTORING_LOG.md`](./registry/REFACTORING_LOG.md) | Refactoring geçmişi |
| [`production-launch.md`](./registry/production-launch.md) | Üretim lansmanı kaydı |

---

## 📐 Planlar (docs/plans/)

| Dosya | İçerik |
|-------|--------|
| [`owner-portal-roadmap.md`](./plans/owner-portal-roadmap.md) | Owner portal yol haritası |
| [`market-intelligence-engine-v1-spec.md`](./plans/market-intelligence-engine-v1-spec.md) | Market intelligence motor spec |
| [`phase-12-monetization-spec.md`](./plans/phase-12-monetization-spec.md) | Phase 12 monetizasyon spec |
| [`TELEGRAM_OUTBOUND_PLAN.md`](./plans/TELEGRAM_OUTBOUND_PLAN.md) | Telegram outbound plan |

---

## 🏠 Owner Portal (docs/owner-portal/)

- [`AI_MARKET_VALUATION.md`](./owner-portal/AI_MARKET_VALUATION.md)
- [`AI_MARKET_VALUATION_SUMMARY.md`](./owner-portal/AI_MARKET_VALUATION_SUMMARY.md)

---

## 🔑 Feature Spec'leri (docs/features/)

- [`ILAN_NO_AUTO_GENERATION.md`](./features/ILAN_NO_AUTO_GENERATION.md)
- [`SEARCH_AND_FILTER_SPEC.md`](./features/SEARCH_AND_FILTER_SPEC.md)

---

## 🔧 Governance Araçları

```bash
# SAB & Bekçi
php artisan sab:integrity-scan          # Bütünlük taraması
php artisan bekci:audit --all           # Tam AST denetimi
php artisan bekci:health                # Sistem sağlığı

# Antigravity Gate (commit öncesi zorunlu)
./scripts/tools/antigravity-full-gate.sh
```

**Aktif CI:** `.github/workflows/core-ci.yml`
**MCP Governance:** `mcp/src/index.ts`

---

*Context7 Kuralları, SAB Protokolleri ve Bekçi AST Koruma sistemine tabidir.*
